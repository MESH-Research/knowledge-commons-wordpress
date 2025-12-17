<?php
/**
 * PHPUnit Bootstrap File for CI Logon Plugin
 *
 * Sets up the test environment for running unit tests.
 * Designed to work with the mu-plugins/ci-logon/ directory structure.
 *
 * @package MeshResearch\CILogon\Tests
 */

// Get the plugin directory (parent of tests directory)
$plugin_dir = dirname(__DIR__);

// Define the plugin base directory
define('CILOGON_BASE_DIR', $plugin_dir . '/');

// Find the root vendor directory (traverse up from plugin directory)
$vendor_file = null;
$current_dir = $plugin_dir;

// Traverse up the directory tree to find vendor/autoload.php
// Starting from mu-plugins/ci-logon, go up to find the root vendor
for ($i = 0; $i < 10; $i++) {
    $test_path = $current_dir . '/vendor/autoload.php';
    if (file_exists($test_path)) {
        $vendor_file = $test_path;
        break;
    }
    $current_dir = dirname($current_dir);
}

// Load Composer autoloader
if ($vendor_file && file_exists($vendor_file)) {
    require_once $vendor_file;
} else {
    echo "ERROR: Composer autoload file not found.\n";
    echo "Expected to find vendor/autoload.php in the project root.\n";
    echo "Please ensure composer dependencies are installed by running: composer install\n";
    exit(1);
}

// Mock WordPress functions that are used by the plugin
// These are minimal mocks to allow the tests to run without a full WordPress installation

if (!function_exists('error_log')) {
    /**
     * Mock error_log function
     */
    function error_log($message, $message_type = 0, $destination = null, $extra_headers = null) {
        // In tests, we suppress error logs to keep test output clean
        // Uncomment the line below to see error logs during testing:
        // echo "[ERROR LOG] $message\n";
    }
}

if (!function_exists('get_user_by')) {
    /**
     * Mock get_user_by function
     */
    function get_user_by($field, $value) {
        // Return false by default (user not found)
        return false;
    }
}

if (!function_exists('wp_insert_user')) {
    /**
     * Mock wp_insert_user function
     */
    function wp_insert_user($userdata) {
        // Return a WP_Error to simulate failure in test environment
        return new \WP_Error('test_error', 'User creation failed in test environment');
    }
}

if (!function_exists('wp_update_user')) {
    /**
     * Mock wp_update_user function
     */
    function wp_update_user($userdata) {
        return $userdata['ID'] ?? 1;
    }
}

if (!function_exists('grant_super_admin')) {
    /**
     * Mock grant_super_admin function
     */
    function grant_super_admin($user_id) {
        // No-op in tests
    }
}

if (!function_exists('revoke_super_admin')) {
    /**
     * Mock revoke_super_admin function
     */
    function revoke_super_admin($user_id) {
        // No-op in tests
    }
}

if (!function_exists('xprofile_get_field_id_from_name')) {
    /**
     * Mock xprofile_get_field_id_from_name function (BuddyPress)
     */
    function xprofile_get_field_id_from_name($field_name) {
        return 1;
    }
}

if (!function_exists('xprofile_set_field_data')) {
    /**
     * Mock xprofile_set_field_data function (BuddyPress)
     */
    function xprofile_set_field_data($field_id, $user_id, $value) {
        // No-op in tests
    }
}

if (!function_exists('add_action')) {
    /**
     * Mock add_action function
     */
    function add_action($hook, $function_to_add, $priority = 10, $accepted_args = 1) {
        // No-op in tests
    }
}

if (!function_exists('wp_cache_get')) {
    /**
     * Mock wp_cache_get function
     */
    function wp_cache_get($key, $group = '') {
        return false;
    }
}

if (!function_exists('wp_cache_set')) {
    /**
     * Mock wp_cache_set function
     */
    function wp_cache_set($key, $value, $group = '', $expire = 0) {
        return true;
    }
}

if (!function_exists('load_muplugin_textdomain')) {
    /**
     * Mock load_muplugin_textdomain function
     */
    function load_muplugin_textdomain($domain, $deprecated = false, $plugin_rel_path = false) {
        // No-op in tests
    }
}

if (!function_exists('plugin_basename')) {
    /**
     * Mock plugin_basename function
     */
    function plugin_basename($file) {
        return 'ci-logon/ci-logon.php';
    }
}

if (!function_exists('flush_rewrite_rules')) {
    /**
     * Mock flush_rewrite_rules function
     */
    function flush_rewrite_rules($hard = true) {
        // No-op in tests
    }
}

if (!function_exists('home_url')) {
    /**
     * Mock home_url function
     */
    function home_url($path = '', $scheme = null) {
        return 'https://example.com' . ($path ? '/' . $path : '');
    }
}

if (!function_exists('admin_url')) {
    /**
     * Mock admin_url function
     */
    function admin_url($path = '', $scheme = 'admin') {
        return 'https://example.com/wp-admin/' . ($path ? $path : '');
    }
}

if (!function_exists('trailingslashit')) {
    /**
     * Mock trailingslashit function
     */
    function trailingslashit($string) {
        return rtrim($string, '/\\') . '/';
    }
}

if (!function_exists('wp_remote_get')) {
    /**
     * Mock wp_remote_get function
     */
    function wp_remote_get($url, $args = array()) {
        return new \WP_Error('http_request_failed', 'A valid URL was not provided.');
    }
}

if (!function_exists('wp_remote_post')) {
    /**
     * Mock wp_remote_post function
     */
    function wp_remote_post($url, $args = array()) {
        return new \WP_Error('http_request_failed', 'A valid URL was not provided.');
    }
}

if (!function_exists('wp_remote_retrieve_response_code')) {
    /**
     * Mock wp_remote_retrieve_response_code function
     */
    function wp_remote_retrieve_response_code($response) {
        if (is_wp_error($response)) {
            return 0;
        }
        return $response['response']['code'] ?? 0;
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    /**
     * Mock wp_remote_retrieve_body function
     */
    function wp_remote_retrieve_body($response) {
        if (is_wp_error($response)) {
            return '';
        }
        return $response['body'] ?? '';
    }
}

if (!function_exists('wp_generate_password')) {
    /**
     * Mock wp_generate_password function
     */
    function wp_generate_password($length = 12, $special_chars = true, $extra_special_chars = false) {
        return 'test_password_123';
    }
}

if (!function_exists('wp_set_current_user')) {
    /**
     * Mock wp_set_current_user function
     */
    function wp_set_current_user($user_id, $user_login = '') {
        // No-op in tests
    }
}

if (!function_exists('wp_set_auth_cookie')) {
    /**
     * Mock wp_set_auth_cookie function
     */
    function wp_set_auth_cookie($user_id, $remember = false, $secure = '') {
        // No-op in tests
    }
}

if (!function_exists('wp_safe_redirect')) {
    /**
     * Mock wp_safe_redirect function
     */
    function wp_safe_redirect($location = '', $status = 302) {
        // No-op in tests
    }
}

if (!function_exists('wp_get_current_user')) {
    /**
     * Mock wp_get_current_user function
     */
    function wp_get_current_user() {
        return new \WP_User(0);
    }
}

if (!function_exists('is_user_logged_in')) {
    /**
     * Mock is_user_logged_in function
     */
    function is_user_logged_in() {
        return false;
    }
}

if (!function_exists('is_wp_error')) {
    /**
     * Mock is_wp_error function
     */
    function is_wp_error($thing) {
        return ($thing instanceof \WP_Error);
    }
}

if (!function_exists('wp_json_encode')) {
    /**
     * Mock wp_json_encode function
     */
    function wp_json_encode($data, $options = 0, $depth = 512) {
        return json_encode($data, $options, $depth);
    }
}

// Mock WP_Error class if it doesn't exist
if (!class_exists('WP_Error')) {
    /**
     * Mock WP_Error class
     */
    class WP_Error {
        private $code;
        private $message;
        private $data;

        public function __construct($code = '', $message = '', $data = '') {
            $this->code = $code;
            $this->message = $message;
            $this->data = $data;
        }

        public function get_error_code() {
            return $this->code;
        }

        public function get_error_message() {
            return $this->message;
        }

        public function get_error_data() {
            return $this->data;
        }
    }
}

// Mock WP_User class if it doesn't exist
if (!class_exists('WP_User')) {
    /**
     * Mock WP_User class
     */
    class WP_User {
        public $ID = 0;
        public $user_login = '';
        public $user_email = '';
        public $user_nicename = '';
        public $user_url = '';
        public $user_registered = '';
        public $user_activation_key = '';
        public $user_status = 0;
        public $display_name = '';
        public $first_name = '';
        public $last_name = '';

        public function __construct($id = 0) {
            if ($id) {
                $this->ID = $id;
            }
        }
    }
}

// Register autoloader for plugin classes
// This looks for classes in the plugin directory, not in src/
spl_autoload_register(function ($class) {
    // Only autoload classes in the MeshResearch\CILogon namespace
    if (strpos($class, 'MeshResearch\\CILogon\\') !== 0) {
        return;
    }

    // Remove the namespace prefix
    $class_path = substr($class, strlen('MeshResearch\\CILogon\\'));
    
    // Convert namespace to file path
    // For example: Plugin -> Plugin.php, CILogonAuth -> CILogonAuth.php
    $file = CILOGON_BASE_DIR . str_replace('\\', '/', $class_path) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
