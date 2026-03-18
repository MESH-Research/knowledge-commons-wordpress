<?php
/**
 * Plugin Name: WordPress CI Logon (MU Loader)
 * Description: MU loader for the CI Logon integration.
 * Version: 1.0.0
 * Author: Mesh Research
 * Text Domain: ci-logon
 */

use MeshResearch\CILogon\Plugin;

if ( ! defined('ABSPATH') ) { exit; }

// Define base paths for MU context.
define('CILOGON_MU_DIR', WPMU_PLUGIN_DIR . '/ci-logon');
define('CILOGON_MU_URL', WPMU_PLUGIN_URL . '/ci-logon');

// Autoload (optional)
if ( file_exists( CILOGON_MU_DIR . '/vendor/autoload.php' ) ) {
    require_once CILOGON_MU_DIR . '/vendor/autoload.php';
}

if (!defined('CILOGON_BASE_DIR')) {
    define('CILOGON_BASE_DIR', __DIR__ . '/');
}
if (!defined('CILOGON_BASE_URL')) {
    define('CILOGON_BASE_URL', (defined('WPMU_PLUGIN_URL') ? WPMU_PLUGIN_URL : WP_PLUGIN_URL) . '/ci-logon/');
}
if (!defined('CILOGON_REST_BASE')) {
    define('CILOGON_REST_BASE', 'ci-logon/v1');
}
if (!defined('CILOGON_DEBUG')) {
    define('CILOGON_DEBUG', filter_var(getenv('CILOGON_DEBUG'), FILTER_VALIDATE_BOOLEAN));
}

// Require implementation files (no duplicate class defs).
require_once CILOGON_MU_DIR . '/Plugin.php';
require_once CILOGON_MU_DIR . '/BrokerAuth.php';

// Load translations for MU plugins.
add_action('muplugins_loaded', function () {
    // e.g., languages at: wp-content/mu-plugins/ci-logon/languages/ci-logon-xx_YY.mo
    load_muplugin_textdomain('ci-logon', 'ci-logon/languages');
});

// Boot the plugin.
add_action('muplugins_loaded', function () {
    Plugin::get_instance();
});

/**
 * Permission callback to verify bearer token for IDMS API endpoints.
 *
 * @param WP_REST_Request $request The REST request object.
 * @return bool|WP_Error True if authorized, WP_Error otherwise.
 */
function cilogon_verify_bearer_token(WP_REST_Request $request) {
    $shared_bearer_key = getenv('PROFILES_API_BEARER_TOKEN');

    if (empty($shared_bearer_key)) {
        error_log('CILogon Plugin: PROFILES_API_BEARER_TOKEN not configured');
        return new WP_Error(
            'rest_forbidden',
            __('API not configured.', 'ci-logon'),
            ['status' => 500]
        );
    }

    $auth_header = $request->get_header('Authorization');
    if (empty($auth_header)) {
        return new WP_Error(
            'rest_forbidden',
            __('Authorization header required.', 'ci-logon'),
            ['status' => 401]
        );
    }

    $expected = 'Bearer ' . $shared_bearer_key;

    // Use timing-safe comparison to prevent timing attacks
    if (!hash_equals($expected, $auth_header)) {
        error_log('CILogon Plugin: Invalid bearer token in API request');
        return new WP_Error(
            'rest_forbidden',
            __('Invalid authorization token.', 'ci-logon'),
            ['status' => 403]
        );
    }

    return true;
}

// add the webhook for pings
add_action('rest_api_init', function () {
    register_rest_route('idms', '/user-updated', [
        'methods' => 'GET',
        'callback' => function (WP_REST_Request $request) {
            $params = $request->get_query_params();
            if (!isset($params["username"]) || empty($params["username"])) {
                return new WP_Error(
                    'rest_invalid_param',
                    __('Username parameter is required.', 'ci-logon'),
                    ['status' => 400]
                );
            }
            $username = sanitize_user($params["username"]);
            Plugin::sync_user($username);
            return ['message' => "SYNC REQUEST FOR " . $username];
        },
        'permission_callback' => 'cilogon_verify_bearer_token',
    ]);
});

add_action('rest_api_init', function () {
    register_rest_route('idms', '/update-email', [
        'methods' => 'POST',
        'callback' => function (WP_REST_Request $request) {
            return Plugin::update_email($request);
        },
        'permission_callback' => 'cilogon_verify_bearer_token',
    ]);
});

add_action('rest_api_init', function () {
    register_rest_route('idms', '/logout', [
        'methods' => 'POST',
        'callback' => function (WP_REST_Request $request) {
            if (Plugin::logout($request)) {
                return ['message' => "LOGOUT"];
            } else {
                return ['message' => "LOGOUT FAILED"];
            }

        },
        'permission_callback' => 'cilogon_verify_bearer_token',
    ]);
});