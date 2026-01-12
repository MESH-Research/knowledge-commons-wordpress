<?php
/**
 * Main Plugin Class
 *
 * @package MeshResearch\CILogon
 */

namespace MeshResearch\CILogon;

/**
 * Main plugin initialization and management
 */
class Plugin {

    /**
     * Plugin instance
     *
     * @var Plugin
     */
    private static $instance = null;

    /**
     * CI Logon authentication handler
     *
     * @var CILogonAuth
     */
    private $auth_handler;

    /**
     * Get plugin instance (singleton)
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Initialize the plugin
     */
    private function init() {
        // Start session if not already started
        if (!session_id()) {
            session_start();
        }

        // Check configuration and log warnings
        $this->check_configuration();

        // Initialize authentication handler
        $this->auth_handler = new CILogonAuth();

        // Hook into WordPress
        add_action('init', [$this, 'load_textdomain']);
    }

    /**
     * Check configuration and log any issues
     */
    private function check_configuration() {
        $client_id = getenv('CILOGON_CLIENT_ID');
        $client_secret = getenv('CILOGON_CLIENT_SECRET');

        if (!$client_id || !$client_secret) {
            error_log('CI Logon Plugin: Missing required environment variables. Please set CILOGON_CLIENT_ID and CILOGON_CLIENT_SECRET.');
        } else {
            error_log('CI Logon Plugin: Configuration loaded successfully.');
        }
    }

    /**
     * @param int $code
     * @param string $body
     * @param $username
     * @param \WP_User|bool $user
     * @return false|Plugin|null
     */
    public static function process_sync(int $code, string $body, $username, \WP_User|bool $user): null|false|Plugin
    {
        // test the response code for bad responses
        if ($code < 200 || $code >= 300) {
            error_log(sprintf('CILogon Plugin: HTTP %d — Body: %s', $code, Plugin::truncate($body)));
            return false;
        }

        // decode the JSON
        $json = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log(sprintf('CILogon Plugin: JSON decode error: %s', json_last_error_msg()));
        }

        // check if we have a remote API error (1005 = user not found)
        if (isset($json["meta"]["error"]["code"]) and $json["meta"]["error"]["code"] == 1005) {
            error_log(sprintf('CILogon Plugin: User with username "%s" not found in remote API.', $username));
            return false;
        }

        // basically, if we have come from the sub endpoint, we get "data" not "results" so we need to put it in there
        error_log('CILogon Plugin: Testing form of API response.');
        if ( isset($json["data"])) {
            error_log('CILogon Plugin: Injecting array for login sync.');
            $results_array = $json['data'][0]['profile'];

        } else {
            error_log('CILogon Plugin: Using standard results field.');
            $results_array = $json['results'];
        }

        // so we have no WordPress user but a remote API user
        if (!$user) {
            $user_id = self::createNewWordPressUser($results_array);

            if (is_wp_error($user_id)) {
                error_log('CILogon Plugin: User creation failed: ' . $user_id->get_error_message());
                return false;
            } else {
                // Success; $user_id is the new user's ID.
                error_log('CILogon Plugin: User creation succeeded, ID: ' . $user_id);
                $user = get_user_by('id', $user_id);
            }
        }

        if (!$user) {
            error_log(sprintf('CILogon Plugin: User with username "%s" not found in WordPress after creation attempt.', $username));
            return false;
        }

        // test for external sync memberships
        if (!isset($results_array["memberships"])) {
            error_log('CILogon Plugin: Response did not include a valid "memberships" array.');
            return false;
        }
        $roles_found = self::processMemberships($results_array["memberships"]);

        // synchronise with BuddyPress
        self::kc_sync_bp_member_types_for_username($user, $roles_found);

        // set user data
        self::setUserData($results_array, $user);

        // set superuser status if flag exists in API response
        self::setSuperuserStatusIfFlagExistsInAPIResponse($results_array, $user);

        return self::$instance;
    }

    /**
     * @param mixed $results_array
     * @param \WP_User|bool $user
     * @return void
     */
    public static function setSuperuserStatusIfFlagExistsInAPIResponse(mixed $results_array, \WP_User|bool $user): void
    {
        if (isset($results_array["is_superadmin"]) && $results_array["is_superadmin"]) {
            grant_super_admin($user->ID);
            error_log(sprintf('CILogon Plugin: Updating user to be SUPERADMIN: %s', $results_array["username"]));
        } else {
            revoke_super_admin($user->ID);
            error_log(sprintf('CILogon Plugin: Updating user, NOT SUPERADMIN: %s', $results_array["username"]));
        }
    }

    /**
     * @param mixed $results_array
     * @param \WP_User|bool $user
     * @return void
     */
    public static function setUserData(mixed $results_array, \WP_User|bool $user): void
    {
        error_log(sprintf('CILogon Plugin: Updating user info: %s', $results_array["username"]));

        $field_id = xprofile_get_field_id_from_name('Name');
        xprofile_set_field_data($field_id, $user->ID, $results_array["first_name"] . " " . $results_array["last_name"]);

        // set other user features
        wp_update_user([
            'ID' => $user->ID,
            'first_name' => $results_array["first_name"],
            'last_name' => $results_array["last_name"],
            'display_name' => $results_array["first_name"] . " " . $results_array["last_name"],
        ]);
    }

    /**
     * @param $memberships
     * @return array
     */
    public static function processMemberships($memberships): array
    {
// extract external sync memberships
        $roles = $memberships;

        // retrieve current society COU from API or retrieve all
        $cous = Plugin::get_cous("");
        $roles_found = array();

        // loop over COUs
        foreach ($cous as $cou) {
            // loop over memberships
            foreach ($roles as $key => $value) {
                if ($key == strtoupper($cou['name']) && $value) {
                    $roles_found[$cou['name']] = [
                        'status' => "ACTIVE",
                        'affiliation' => $key,
                        'o' => $key,
                    ];
                } else if ($key == strtoupper($cou['name']) && !$value) {
                    $roles_found[$cou['name']] = [
                        'status' => "INACTIVE",
                        'affiliation' => $key,
                        'o' => $key,
                    ];
                }
            }
        }
        return $roles_found;
    }

    /**
     * @param mixed $results_array
     * @return int|\WP_Error
     */
    public static function createNewWordPressUser(mixed $results_array): int|\WP_Error
    {
        $user_data = array(
            'user_login' => $results_array["username"],
            'user_pass' => wp_generate_password(12, true),
            'user_email' => $results_array["email"],
            'first_name' => $results_array["first_name"],
            'last_name' => $results_array["last_name"],
            'display_name' => $results_array["first_name"] . " " . $results_array["last_name"],
            'role' => 'subscriber',
        );

        $user_id = wp_insert_user($user_data);
        return $user_id;
    }

    /**
     * Load plugin textdomain for translations
     */
    public function load_textdomain() {
        load_muplugin_textdomain('ci-logon', false, dirname(plugin_basename(CILOGON_BASE_DIR . 'ci-logon.php')) . '/languages');
    }

    /**
     * Plugin activation
     */
    public function activate() {
        error_log('CI Logon Plugin: Activated');
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        error_log('CI Logon Plugin: Deactivated');
        flush_rewrite_rules();
    }

    /**
     * Get the authentication handler
     */
    public function get_auth_handler() {
        return $this->auth_handler;
    }

    /**
     * Check if plugin is properly configured
     */
    public function is_configured() {
        $client_id = getenv('CILOGON_CLIENT_ID');
        $client_secret = getenv('CILOGON_CLIENT_SECRET');

        return !empty($client_id) && !empty($client_secret);
    }

    private static function build_headers( ?string $token ) : array {
        $headers = [
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];
        if ( $token ) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }
        return $headers;
    }

    private static function user_agent() : string {
        global $wp_version;
        return sprintf( 'IDMSSyncCLI/0.1 (WordPress/%s; IDMSSync/0.1; %s)', $wp_version, home_url() );
    }

    private static function truncate( string $s, int $len = 400 ) : string {
        $s = trim( $s );
        return ( strlen( $s ) > $len ) ? substr( $s, 0, $len ) . '…' : $s;
    }

    public static function get_cous( $society_id = '' ) {

        $req = wp_cache_get( 'comanage_cous', 'hcommons_settings' );

        if ( ! $req ) {

            //Hard code COU values becasue REST API call gets a memory error on COmanage - PMO bug #329
            $temp_cous = array();
            $temp_cous['Cous'][] = [ 'Id' => '1', 'Name' => 'MLA',
                'Description' => 'Modern Language Association' ];
            $temp_cous['Cous'][] = [ 'Id' => '2', 'Name' => 'CAA',
                'Description' => 'College Art Association' ];
            $temp_cous['Cous'][] = [ 'Id' => '3', 'Name' => 'ASEEES',
                'Description' => 'Association for Slavic, Eastern European, and Eurasian Studies' ];
            $temp_cous['Cous'][] = [ 'Id' => '4', 'Name' => 'AJS',
                'Description' => 'Association for Jewish Studies' ];
            $temp_cous['Cous'][] = [ 'Id' => '5', 'Name' => 'HC',
                'Description' => 'Humanities Commons' ];
            $temp_cous['Cous'][] = [ 'Id' => '6', 'Name' => 'UP',
                'Description' => 'Association of American University Presses' ];
            $temp_cous['Cous'][] = [ 'Id' => '7', 'Name' => 'MSU',
                'Description' => 'Michigan State University' ];
            $temp_cous['Cous'][] = [ 'Id' => '8', 'Name' => 'ARLISNA',
                'Description' => 'ARLIS/NA' ];
            $temp_cous['Cous'][] = [ 'Id' => '10', 'Name' => 'SAH',
                'Description' => 'SAH' ];
            $temp_cous['Cous'][] = [ 'Id' => '11', 'Name' => 'HUB',
                'Description' => 'HUB' ];
            $temp_cous['Cous'][] = [ 'Id' => '12', 'Name' => 'SOCSCI',
                'Description' => 'SOCSCI' ];
            $temp_cous['Cous'][] = [ 'Id' => '13', 'Name' => 'STEM',
                'Description' => 'STEM' ];
            $temp_cous['Cous'][] = [
                'Id'          => '13',
                'Name'        => 'STEM',
                'Description' => 'STEM'
            ];
            $temp_cous['Cous'][] = [
                'Id'          => '14',
                'Name'        => 'HASTAC',
                'Description' => 'HASTAC'
            ];
            $temp_cous['Cous'][] = [
                'Id'          => '15',
                'Name'        => 'STEMEd+',
                'Description' => 'STEM Ed+'
            ];
            $req['body'] = json_encode( $temp_cous );

            //$req = wp_remote_get( $this->url . '/cous.' . $this->format . '?coid=2', $this->api_args );
            wp_cache_set( 'comanage_cous', $req, 'hcommons_settings', 24 * HOUR_IN_SECONDS );
        }

        //json_decode the data from the request
        $data = json_decode( $req['body'], true );
        $cous = array();

        //loops through cou data to find the one matching the string in param
        foreach( $data['Cous'] as $item ) {

            if ( empty( $society_id ) || $item['Name'] == strtoupper( $society_id ) ) {

                $cous[] = [
                    'id' => $item['Id'],
                    'name' => $item['Name'],
                    'description' => $item['Description']
                ];

            }
        }

        return $cous;

    }

    static function kc_sync_bp_member_types_for_username( $user, array $memberships ): void {
        if ( ! function_exists( 'bp_set_member_type' ) ) {
            // BuddyPress not loaded.
            error_log( sprintf( 'CILogon Plugin: No BuddyPress' ) );
            return;
        }

        $user_id = $user->ID;

        // All the member-type slugs you’ve registered in hcommons_register_member_types().
        $all_known_types = [
            'arlisna',
            'aseees',
            'hc',
            'hub',
            'mla',
            'msu',
            'sah',
            'socsci',
            'stem',
            'up',
            'hastac',
            'dhri',
        ];

        // What the API says this user should have.
        $desired_types = array_map('strtolower', array_keys(array_filter($memberships)));
        $desired_types = array_intersect( array_map('strtolower', $desired_types), $all_known_types ); // safety

        // What they currently have in BuddyPress.
        // Passing false gives all types; can be string, array, or false.
        $current_types = bp_get_member_type( $user_id, false );
        if ( ! is_array( $current_types ) ) {
            $current_types = $current_types ? [ $current_types ] : [];
        }

        error_log('CILogon Plugin: Current types: ' . var_export($current_types, true));
        error_log('CILogon Plugin: Desired types: ' . var_export($desired_types, true));

        // Add missing types.
        foreach ( $desired_types as $type ) {
            error_log( sprintf( 'CILogon Plugin: Checking: %s for addition', $type ) );
            if ( ! in_array( $type, $current_types, true ) ) {
                // append = true means "add, don't overwrite existing types"
                bp_set_member_type( $user_id, $type, true );
                error_log( sprintf( 'CILogon Plugin: Added: %s', $type ) );
            }
        }

        // Remove types that are no longer valid.
        foreach ( $all_known_types as $type ) {
            error_log( sprintf( 'CILogon Plugin: Checking: %s for removal', $type ) );
            if ( in_array( $type, $current_types, true ) && ! in_array( $type, $desired_types, true ) && $type != "hc") {
                bp_remove_member_type( $user_id, $type );
                // Alternatively: bp_set_member_type( $user_id, '' ) to clear ALL, but here we just remove one.
                error_log( sprintf( 'CILogon Plugin: Removed: %s', $type ) );
            }
        }

        // always set hc
        bp_set_member_type( $user_id, "hc", true );

    }

    public static function sync_user($username) {
        error_log(sprintf( 'CILogon Plugin: Attempting sync for: %s', $username ) );

        // get the WordPress user
        $user = get_user_by( 'login', $username );

        // if there is no user, we may have to create one later
        if ( ! $user ) {
            error_log( sprintf( 'CILogon Plugin: User with username "%s" not found... checking API response.', $username ) );
        }

        // get environment and other variables
        $url = getenv( 'PROFILES_API_URL' );
        $shared_bearer_key = getenv( 'PROFILES_API_BEARER_TOKEN' );
        $timeout  = 15;

        // build the arguments for the remote request
        $request_args = [
            'method'      => 'GET',
            'headers'     => Plugin::build_headers($shared_bearer_key),
            'timeout'     => $timeout,
            'user-agent'  => Plugin::user_agent(),
        ];

        // build the endpoint and add filters for overrides
        $endpoint = trailingslashit($url . 'members') . $username;
        $endpoint     = apply_filters( 'IDMS/sync_endpoint', $endpoint, $user );
        $request_args = apply_filters( 'IDMS/sync_request_args', $request_args, $user );

        // make the request
        error_log( sprintf( 'CILogon Plugin: GET %s (timeout=%ds) …', $endpoint, $timeout ) );
        $res = wp_remote_get( $endpoint, $request_args );

        if ( is_wp_error( $res ) ) {
            error_log( sprintf( 'CILogon Plugin: HTTP request failed: %s', $res->get_error_message() ) );
            return false;
        }

        // extract the response code and body
        $code = (int) wp_remote_retrieve_response_code( $res );
        $body = (string) wp_remote_retrieve_body( $res );
        return self::process_sync($code, $body, $username, $user);
    }

    public static function logout($request) {
        error_log( 'CILogon Plugin: Logout request via API' );

        // Note: Bearer token validation is handled by the permission_callback in cilogon.php

        // Get and validate the username from the querystring
        $username = $request->get_param('username');
        if ( empty( $username ) ) {
            error_log( 'CILogon Plugin: Logout request missing username parameter' );
            return false;
        }

        // Sanitize the username
        $username = sanitize_user( $username );

        $user = get_user_by( 'login', $username );
        if ( ! $user ) {
            error_log( 'CILogon Plugin: Logout request for non-existent user: ' . $username );
            return false;
        }

        if ( ! class_exists( '\WP_Session_Tokens' ) ) {
            require_once ABSPATH . 'wp-includes/class-wp-session-tokens.php';
        }

        $manager = \WP_Session_Tokens::get_instance( $user->ID );
        $manager->destroy_all();

        return true;
    }
}
