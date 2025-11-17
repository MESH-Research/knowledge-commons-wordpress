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

        if ( ! $user ) {
            error_log( sprintf( 'CILogon Plugin: User with username "%s" not found.', $username ) );
            return false;
        }

        // get environment and other variables
        $url = getenv( 'PROFILES_API_URL' );
        $shared_bearer_key = getenv( 'PROFILES_API_BEARER' );
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
        $endpoint     = apply_filters( 'IDMS/sync_endpoint', $endpoint, $user, $assoc_args );
        $request_args = apply_filters( 'IDMS/sync_request_args', $request_args, $user, $assoc_args );

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

        // test the response code for bad responses
        if ( $code < 200 || $code >= 300 ) {
            error_log( sprintf( 'CILogon Plugin: HTTP %d — Body: %s', $code, Plugin::truncate($body) ) );
            return false;
        }

        // decode the JSON
        $json = json_decode( $body, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            error_log( sprintf( 'CILogon Plugin: JSON decode error: %s', json_last_error_msg() ) );
        }

        // test for external sync memberships
        if ( ! isset( $json["results"]["external_sync_memberships"] ) ) {
            error_log('CILogon Plugin: Response did not include a valid "external_sync_memberships" array.');
            return false;
        }

        // extract external sync memberships
        $roles = $json["results"]["external_sync_memberships"];

        // retrieve current society COU from API or retrieve all
        $cous = Plugin::get_cous( "" );
        $roles_found = array();

        // loop over COUs
        foreach( $cous as $cou ) {
            // loop over external_sync_memberships
            foreach ( $roles as $key => $value ) {
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

        // print the API response
        error_log(print_r($roles_found, true));

        // synchronise with BuddyPress
        Plugin::kc_sync_bp_member_types_for_username($user, $roles_found);

        return self::$instance;
    }
}
