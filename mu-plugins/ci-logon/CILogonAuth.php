<?php
/**
 * CI Logon Authentication Handler
 *
 * @package MeshResearch\CILogon
 */

namespace MeshResearch\CILogon;

use MeshResearch\CILogon\CustomOpenIDConnectClient;
use WP_Error;
use WP_User;
use Exception;

/**
 * Handles CI Logon authentication via OpenID Connect
 */
class CILogonAuth
{
    /**
     * OpenID Connect client instance
     *
     * @var CustomOpenIDConnectClient
     */
    private $oidc_client;

    /**
     * CI Logon OIDC configuration
     *
     * @var array
     */
    private $config;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->config = [
            "provider_url" =>
                getenv("CILOGON_PROVIDER_URL") ?: "https://cilogon.org",
            "client_id" => getenv("CILOGON_CLIENT_ID"),
            "client_secret" => getenv("CILOGON_CLIENT_SECRET"),
            "redirect_uri" =>
                getenv("CILOGON_REDIRECT_URI") ?:
                "https://profile.hcommons.org/cilogon/callback/",
            "callback_next" =>
                getenv("CILOGON_CALLBACK_NEXT") ?:
                "https://commons-wordpress.lndo.site/wp-login.php",
            "scopes" => ["openid", "email", "profile"],
            "profiles_url" => getenv("PROFILES_API_URL") ?: "https://profile.hcommons.org/",
            "profiles_api_bearer_token" =>
                getenv("PROFILES_API_BEARER_TOKEN") ?: "",
        ];

        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks()
    {
        // Redirect login page to CI Logon
        add_action("login_init", [$this, "do_cilogon_wrapper"]);
    }

    public function do_cilogon_wrapper() {
        // Figure out what WP thinks is happening on this request
        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login';

        // Bail out for any non-login actions
        if (
            isset($_GET['loggedout'])             // second step of logout
            || in_array(
                $action,
                [ 'lostpassword', 'resetpass', 'rp', 'register', 'confirmaction' ],
                true
            )
        ) {
            error_log( 'CILogon Plugin: skipping, action=' . $action . ', loggedout=' . (isset($_GET['loggedout']) ? '1' : '0') );
            return;
        } elseif (
            in_array(
                $action,
                [ 'logout' ],
                true
            )
        ) {
            // handle logout
            $this->handle_logout();
            return;
        }

        // Only now run the actual CILogon logic
        $this->do_cilogon();
    }

    public function handle_logout() {
        // send signal to remote API to invalidate token
        // this is at api/v1/actions/logout/ on profiles_api_url
        // it needs a bearer token that is from profiles_api_bearer_token
        // it also needs POST data of user_name and user_agent
        $user = wp_get_current_user();
        if ( ! $user || 0 === $user->ID ) {
            error_log( 'CILogon Plugin: handle_logout called but no current user.' );
            return;
        }

        // Build endpoint: {profiles_url}api/v1/actions/logout/
        $endpoint = trailingslashit( $this->config['profiles_url'] ) . 'api/v1/actions/logout/';

        // Prepare headers with bearer token.
        $token = $this->config['profiles_api_bearer_token'];
        if ( empty( $token ) ) {
            error_log( 'CILogon Plugin: handle_logout – missing PROFILES_API_BEARER_TOKEN.' );
            return;
        }

        $headers = array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        );

        // Collect POST data.
        $user_name  = $user->user_login;
        $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';

        $body = array(
            'user_name'  => $user_name,
            'user_agent' => $user_agent,
        );

        $args = array(
            'method'      => 'POST',
            'headers'     => $headers,
            'timeout'     => 15,
            'body'        => wp_json_encode( $body ),
        );

        error_log( sprintf(
            'CILogon Plugin: Sending logout for "%s" to Profiles API endpoint: %s',
            $user_name,
            $endpoint
        ) );

        $response = wp_remote_post( $endpoint, $args );

        if ( is_wp_error( $response ) ) {
            error_log(
                'CILogon Plugin: Error sending logout to Profiles API: ' .
                $response->get_error_message()
            );
            return;
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $resp_body = (string) wp_remote_retrieve_body( $response );

        if ( $code < 200 || $code >= 300 ) {
            error_log( sprintf(
                'CILogon Plugin: Logout API returned HTTP %d – Body: %s',
                $code,
                mb_substr( trim( $resp_body ), 0, 400 ) . ( strlen( trim( $resp_body ) ) > 400 ? '…' : '' )
            ) );
            return;
        }

        error_log( sprintf(
            'CILogon Plugin: Logout API success for "%s" (HTTP %d).',
            $user_name,
            $code
        ) );
    }

    /**
     * Redirect WordPress login to CI Logon
     */
    public function do_cilogon()
    {
        // Don't do anything if user is already logged out
        if ( isset($_GET['loggedout']) ) {
            error_log('CILogon: loggedout flag set, skipping.');
            return;
        }

        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login';

        // Don't redirect for logout, lostpassword, reset, etc.
        if ( in_array(
            $action,
            [ 'logout', 'lostpassword', 'resetpass', 'rp', 'register', 'confirmaction' ],
            true
        )
        ) {
            error_log( 'CILogon Plugin received action: ' . $action . ' – skipping.' );
            return;
        }

        // Don't redirect if this is a callback or specific WP login action
        if (isset($_GET["code"]) && isset($_GET["state"])) {

            $this->oidc_client = new CustomOpenIDConnectClient(
                $this->config["provider_url"],
                $this->config["client_id"],
                $this->config["client_secret"]
            );

            $this->oidc_client->setRedirectURL($this->config["redirect_uri"]);
            $this->oidc_client->addScope($this->config["scopes"]);

            $authenticated = $this->oidc_client->authenticate();

            if ($authenticated) {
                $user_info = $this->get_user_info();
            } else {
                return;
            }

            if (isset($user_info) and $user_info) {
                $user = $this->find_or_create_user($user_info);
            } else {
                return;
            }

            if (isset($user)) {
                $this->synchronise_user($user);
            } else {
                return;
            }
        }

        // Don't redirect if user is already logged in and accessing wp-admin
        if (is_user_logged_in() && isset($_GET["redirect_to"])) {
            return;
        }

        try {
            error_log("CI Logon: Starting authentication redirect");
            $this->init_oidc_client();

            // Store redirect URL in session if provided
            if (isset($_GET["redirect_to"])) {
                if (!session_id()) {
                    session_start();
                }
                $_SESSION["cilogon_redirect_to"] = $_GET["redirect_to"];
                error_log(
                    "CI Logon: Stored redirect URL: " . $_GET["redirect_to"]
                );
            }

            error_log("CILogon PLugin: Redirecting to CI Logon");

            $reflection = new \ReflectionMethod($this->oidc_client, 'getState');
            $reflection->setAccessible(true);
            $current_state = $reflection->invoke($this->oidc_client);

            error_log($current_state);

            $authenticated = $this->oidc_client->authenticate();
        } catch (Exception $e) {
            error_log("CI Logon authentication error: " . $e->getMessage());
            wp_die(
                "CI Logon authentication error: " . esc_html($e->getMessage())
            );
        }

        if ($authenticated) {
            $user_info = $this->get_user_info();
        } else {
            return;
        }

        if (isset($user_info) and $user_info) {
            // make sure there's a profile field in $user_info
            if (!isset($user_info->profile)) {
                error_log("CILogon Plugin: No profile field in user info. API misconfiguration.");
                return;
            }

            $user = $this->find_or_create_user($user_info);
        } else {
            return;
        }

        if ($user) {
            $this->synchronise_user($user);
        } else {
            return;
        }
    }

    /**
     * Initialize OpenID Connect client
     */
    private function init_oidc_client()
    {
        if (!$this->config["client_id"] || !$this->config["client_secret"]) {
            error_log("CILogon Plugin: Missing client credentials");
            throw new Exception(
                "CILogon Plugin client credentials not configured. Please set CILOGON_CLIENT_ID and CILOGON_CLIENT_SECRET environment variables."
            );
        }

        error_log(
            "CILogon Plugin: Initializing OIDC client with provider: " .
                $this->config["provider_url"]
        );

        error_log(
            "CILogon Plugin: Using remote endpoint: " .
            $this->config["redirect_uri"]
        );

        $this->oidc_client = new CustomOpenIDConnectClient(
            $this->config["provider_url"],
            $this->config["client_id"],
            $this->config["client_secret"]
        );

        $this->oidc_client->setRedirectURL($this->config["redirect_uri"]);
        $this->oidc_client->addScope($this->config["scopes"]);

        $reflection = new \ReflectionMethod($this->oidc_client, 'getState');
        $reflection->setAccessible(true);
        $current_state = $reflection->invoke($this->oidc_client);

        $state = [
            "session_key" => bin2hex(random_bytes(16)),
            "callback_next" => $this->config["callback_next"],
        ];
        // $encoded_state = base64_encode(json_encode($state));
        $encoded_state = strtr(base64_encode(json_encode($state)), '+/', '-_');

        error_log("Setting state: " . var_export($encoded_state, true));

        $reflection_set = new \ReflectionMethod($this->oidc_client, 'setState');
        $reflection_set->setAccessible(true);
        $reflection_set->invoke($this->oidc_client, $encoded_state);

        $reflection = new \ReflectionMethod($this->oidc_client, 'getState');
        $reflection->setAccessible(true);
        $current_state = $reflection->invoke($this->oidc_client);

    }

    public function synchronise_user($user) {
        error_log("CILogon Plugin got a user.");
        wp_set_current_user($user->ID);

        error_log("CILogon Plugin setting auth cookie.");
        wp_set_auth_cookie($user->ID);

        error_log("CILogon redirecting.");
        $redirect_to = admin_url();

        wp_safe_redirect($redirect_to);
        exit();
    }

    /**
     * Get user info from Profiles subs endpoint
     */
    public function get_user_info()
    {
        $subs_endpoint = $this->config["profiles_url"] . "api/v1/subs/";
        $headers = [
            "Authorization" =>
                "Bearer " . $this->config["profiles_api_bearer_token"],
        ];
        // error_log("Headers: " . var_export($headers, true));
        $sub = $this->oidc_client->getVerifiedClaims()->sub;
        error_log("Sending request to: " . $subs_endpoint . "?sub=" . $sub);
        error_log("Sub: " . var_export($sub, true));
        if (!$sub) {
            error_log("CILogon Plugin: No sub found in verified claims");
            return false;
        }

        $response = wp_remote_get($subs_endpoint . "?sub=" . $sub, [
            "headers" => $headers,
        ]);

        if (is_wp_error($response)) {
            error_log(
                "CILogon Plugin: Error fetching user info from Profiles API: " .
                    $response->get_error_message()
            );
            return false;
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = (string) wp_remote_retrieve_body( $response );

        $user_info = json_decode(wp_remote_retrieve_body($response));
        error_log("Received user info: " . print_r($user_info, true));

        if (!$user_info) {
            error_log("CILogon Plugin: CRITICAL ERROR: Profiles API appears misconfigured");
            error_log("CILogon Plugin: Invalid response from Profiles API");
            return false;
        }

        if (
            !isset($user_info->data) ||
            !is_array($user_info->data) ||
            count($user_info->data) == 0
        ) {
            error_log(
                "CILogon Plugin: No user data found in Profiles API response. Redirecting to link account page."
            );
            $this->link_account();
        }

        // synchronise the user data
        $user_info_final = $user_info->data[0];
        $profile = $user_info_final->profile;

        $user = get_user_by("login", $profile->username);
        $username = $user->user_login;
        Plugin::process_sync($code, $body, $username, $user);

        return $user_info_final;
    }

    public function link_account()
    {
        $id_token = $this->oidc_client->getIdToken();
        error_log(
            "ID Token payload:" .
                print_r($this->oidc_client->getIdTokenPayload(), true)
        );
        $key = hash("sha256", $this->config["profiles_api_bearer_token"], true);
        $iv = random_bytes(16);
        $cipherRaw = openssl_encrypt(
            $id_token,
            "AES-256-CBC",
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        $payload = strtr(base64_encode($iv . $cipherRaw), '+/', '-_');

        // $payload = base64_encode($iv . $cipherRaw);
        $url =
            $this->config["profiles_url"] .
            "associate?userinfo=" .
            rawurlencode($payload);
        error_log("CI Logon: Redirecting to link account page: " . $url);
        wp_redirect($url);
        exit();
    }

    /**
     * Find existing user or create new one based on CI Logon data
     */
    private function find_or_create_user($user_info)
    {
        $profile = $user_info->profile;
        $user = get_user_by("login", $profile->username);

        if ($user) {
            error_log(
                "CI Logon: Updating user meta for existing user: " . $user->ID
            );
            $this->update_user_meta($user, $user_info);
            return $user;
        }

        // Create new user
        error_log("CI Logon: Creating new user for email: " . $profile->email);
        $username = $this->generate_username($profile);

        $user_data = [
            "user_login" => $username,
            "user_email" => $profile->email,
            "user_pass" => wp_generate_password(32, true, true), // Random password
            "first_name" => $profile->first_name ?? "",
            "last_name" => $profile->last_name ?? "",
            "display_name" => $profile->name ?? $profile->email,
            "role" => "administrator", // Default role
        ];

        $user_id = wp_insert_user($user_data);

        if (is_wp_error($user_id)) {
            error_log(
                "CI Logon: Failed to create user: " .
                    $user_id->get_error_message()
            );
            return $user_id;
        }

        error_log("CI Logon: Successfully created user with ID: " . $user_id);
        $user = get_user_by("id", $user_id);
        $this->update_user_meta($user, $user_info);

        return $user;
    }

    /**
     * Generate unique username from user info
     */
    private function generate_username($profile)
    {
        $base_username = "";

        if (isset($profile->username)) {
            $base_username = sanitize_user($profile->username);
        } elseif (isset($profile->email)) {
            $base_username = sanitize_user(strstr($profile->email, "@", true));
        } else {
            $base_username = "cilogon_user";
        }

        $username = $base_username;
        $counter = 1;

        while (username_exists($username)) {
            $username = $base_username . "_" . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Update user meta with CI Logon data
     */
    private function update_user_meta(WP_User $user, $user_info)
    {
        error_log("CILogon Plugin: Updating user meta for user: " . $user->ID);
        // log the full object
        error_log("CILogon Plugin: User info: " . json_encode($user_info));

        update_user_meta($user->ID, "cilogon_sub", $user_info->sub);

        if (isset($user_info->iss)) {
            update_user_meta($user->ID, "cilogon_iss", $user_info->iss);
        }

        if (isset($user_info->eppn)) {
            update_user_meta($user->ID, "cilogon_eppn", $user_info->eppn);
        }

        if (isset($user_info->eptid)) {
            update_user_meta($user->ID, "cilogon_eptid", $user_info->eptid);
        }
    }

    /**
     * Get configuration for debugging
     */
    public function get_config()
    {
        return array_merge($this->config, [
            "client_secret" => $this->config["client_secret"]
                ? "[REDACTED]"
                : null,
        ]);
    }
}
