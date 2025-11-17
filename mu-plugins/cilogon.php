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

define ("CILOGON_BASE_DIR", __DIR__ . '/');
define ("CILOGON_BASE_URL", (defined('WPMU_PLUGIN_URL') ? WPMU_PLUGIN_URL : WP_PLUGIN_URL) . '/ci-logon/');
define ("CILOGON_REST_BASE", "ci-logon/v1");

// Require implementation files (no duplicate class defs).
require_once CILOGON_MU_DIR . '/Plugin.php';
require_once CILOGON_MU_DIR . '/CILogonAuth.php';
require_once CILOGON_MU_DIR . '/CustomOpenIDConnectClient.php';

// Load translations for MU plugins.
add_action('muplugins_loaded', function () {
    // e.g., languages at: wp-content/mu-plugins/ci-logon/languages/ci-logon-xx_YY.mo
    load_muplugin_textdomain('ci-logon', 'ci-logon/languages');
});

// Boot the plugin.
add_action('muplugins_loaded', function () {
    Plugin::get_instance();
});

// add the webhook
add_action('rest_api_init', function () {
    register_rest_route('idms', '/user-updated', [
        'methods' => 'GET',
        'callback' => function (WP_REST_Request $request) {
            $params = $request->get_query_params();
            Plugin::sync_user($params["username"]);
            return ['message' => "SYNC REQUEST FOR " . $params["username"]];
        },
        'permission_callback' => '__return_true',
    ]);
});
