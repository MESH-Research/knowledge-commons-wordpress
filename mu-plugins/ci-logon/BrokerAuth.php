<?php
/**
 * Broker Authentication Handler
 *
 * Handles authentication via the Profiles identity broker, replacing
 * the direct CILogon OIDC integration.
 *
 * Flow:
 * 1. Login -> redirect to Profiles login with return_to callback URL
 * 2. Profiles authenticates, redirects to /broker-callback/?broker_token={encrypted}
 * 3. WordPress decrypts payload (AES-256-CBC), validates, verifies nonce
 * 4. Finds/creates WP user, syncs via Plugin::sync_user(), sets auth cookie
 *
 * @package MeshResearch\CILogon
 */

namespace MeshResearch\CILogon;

use WP_Error;
use WP_User;

class BrokerAuth
{
    private array $config;

    private const SESSION_COOKIE_NAME = 'cilogon_auth_session_id';
    private const SESSION_EXPIRATION = 3600;

    private const SSO_CHECK_COOKIE = 'broker_sso_checked';
    private const SSO_CHECK_TTL = 900; // 15 minutes
    private const SSO_OPTOUT_COOKIE = 'broker_sso_optout';
    private const SSO_OPTOUT_TTL = 86400; // 24 hours

    private const BOT_SIGNATURES = [
        'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baidu',
        'yandex', 'sogou', 'exabot', 'facebot', 'ia_archiver',
        'semrush', 'ahrefsbot', 'mj12bot', 'dotbot', 'petalbot',
        'bytespider', 'gptbot', 'chatgpt', 'claudebot',
    ];

    public function __construct()
    {
        $this->config = [
            'profiles_url' => getenv('PROFILES_API_URL') ?: 'https://profile.hcommons.org/',
            'profiles_api_bearer_token' => getenv('PROFILES_API_BEARER_TOKEN') ?: '',
        ];

        $this->init_hooks();
    }

    private function init_hooks(): void
    {
        add_action('login_init', [$this, 'do_login_redirect_wrapper']);
        add_action('init', [$this, 'maybe_handle_broker_callback']);
        add_action('template_redirect', [$this, 'maybe_silent_login']);
    }

    /**
     * Intercept /broker-callback/ requests early in init.
     *
     * Uses direct URI matching instead of rewrite rules so it works
     * immediately without flush_rewrite_rules() (MU plugins have no
     * activation hook on deploy).
     */
    public function maybe_handle_broker_callback(): void
    {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($request_uri, PHP_URL_PATH);

        if ($path !== '/broker-callback/' && $path !== '/broker-callback') {
            return;
        }

        $this->handle_broker_callback();
        $this->terminate();
    }

    // =========================================================================
    // Login redirect wrapper (gate: secret_key bypass, skip non-login, logout)
    // =========================================================================

    public function do_login_redirect_wrapper(): void
    {
        if ($this->maybe_secret_key_login()) {
            return;
        }

        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login';

        if (
            isset($_GET['loggedout'])
            || in_array(
                $action,
                ['lostpassword', 'resetpass', 'rp', 'register', 'confirmaction'],
                true
            )
        ) {
            error_log('BrokerAuth: skipping, action=' . $action);
            return;
        } elseif ($action === 'logout') {
            $this->handle_logout();
            return;
        }

        $this->do_login_redirect();
    }

    // =========================================================================
    // Login redirect — send user to Profiles login
    // =========================================================================

    public function do_login_redirect(): void
    {
        if (is_user_logged_in() && isset($_GET['redirect_to'])) {
            return;
        }

        // Store redirect_to in transient before redirecting (fallback for no_session)
        if (isset($_GET['redirect_to'])) {
            $validated_redirect = wp_validate_redirect(
                wp_sanitize_redirect($_GET['redirect_to']),
                home_url()
            );
            $this->setSessionValue('broker_redirect_to', $validated_redirect);
        }

        // Determine the user's original page for final_redirect
        $final_redirect = home_url();
        if (!empty($_GET['redirect_to'])) {
            $final_redirect = wp_validate_redirect(
                wp_sanitize_redirect($_GET['redirect_to']),
                home_url()
            );
        } elseif (!empty($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
            $referer_host = parse_url($referer, PHP_URL_HOST);
            $site_host = parse_url(home_url(), PHP_URL_HOST);
            if ($referer_host === $site_host) {
                $final_redirect = wp_validate_redirect(
                    wp_sanitize_redirect($referer),
                    home_url()
                );
            }
        }

        $callback_url = home_url('/broker-callback/');
        $profiles_login_url = rtrim($this->config['profiles_url'], '/') . '/login/';
        $redirect_url = $profiles_login_url . '?return_to=' . rawurlencode($callback_url)
            . '&final_redirect=' . rawurlencode($final_redirect);

        error_log('BrokerAuth: Redirecting to Profiles login: ' . $redirect_url);

        // Allow Profiles host in redirects
        $profiles_host = parse_url($this->config['profiles_url'], PHP_URL_HOST);
        add_filter('allowed_redirect_hosts', function ($hosts) use ($profiles_host) {
            if ($profiles_host) {
                $hosts[] = $profiles_host;
            }
            return $hosts;
        });

        wp_safe_redirect($redirect_url);
        $this->terminate();
    }

    // =========================================================================
    // Broker callback — decrypt, validate, verify nonce, login
    // =========================================================================

    public function handle_broker_callback(): void
    {
        // Handle no-session response from silent SSO check
        if (isset($_GET['no_session'])) {
            $stored_url = $this->getSessionValue('broker_sso_return_url');
            $this->unsetSessionValue('broker_sso_return_url');
            wp_safe_redirect($stored_url ?: home_url());
            return;
        }

        $broker_token = isset($_GET['broker_token']) ? $_GET['broker_token'] : '';
        if (empty($broker_token)) {
            error_log('BrokerAuth: broker_callback hit but no broker_token');
            wp_die('Authentication error: missing broker token.');
            return;
        }

        // Decrypt
        $payload = self::decrypt_broker_token($broker_token, $this->config['profiles_api_bearer_token']);
        if ($payload === null) {
            error_log('BrokerAuth: Failed to decrypt broker token');
            wp_die('Authentication error: invalid broker token.');
            return;
        }

        // Validate
        $valid = $this->validate_broker_payload($payload);
        if (is_wp_error($valid)) {
            error_log('BrokerAuth: Payload validation failed: ' . $valid->get_error_message());
            wp_die('Authentication error: ' . esc_html($valid->get_error_message()));
            return;
        }

        // Verify nonce
        if (!$this->verify_nonce($payload['nonce'])) {
            error_log('BrokerAuth: Nonce verification failed');
            wp_die('Authentication error: nonce verification failed.');
            return;
        }

        // Find or create user
        $user = $this->find_or_create_user($payload);
        if (is_wp_error($user)) {
            error_log('BrokerAuth: User creation failed: ' . $user->get_error_message());
            wp_die('Authentication error: ' . esc_html($user->get_error_message()));
            return;
        }

        // Sync user data from Profiles
        Plugin::sync_user($user->user_login);

        // Log user in (pass payload so synchronise_user can read final_redirect)
        $this->synchronise_user($user, $payload);
    }

    // =========================================================================
    // Decrypt broker token (AES-256-CBC, key derived from bearer token)
    // =========================================================================

    public static function decrypt_broker_token(string $encrypted, string $secret): ?array
    {
        // Handle URL-safe base64 (convert -_ back to +/)
        $encrypted = strtr($encrypted, '-_', '+/');
        $raw = base64_decode($encrypted, true);
        if ($raw === false || strlen($raw) < 17) {
            return null;
        }

        $iv = substr($raw, 0, 16);
        $ciphertext = substr($raw, 16);

        $key = hash('sha256', $secret, true);
        $decrypted = openssl_decrypt(
            $ciphertext,
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            return null;
        }

        $data = json_decode($decrypted, true);
        if (!is_array($data)) {
            return null;
        }

        return $data;
    }

    // =========================================================================
    // Validate broker payload
    // =========================================================================

    public function validate_broker_payload(array $payload): true|WP_Error
    {
        $required = ['kc_username', 'nonce', 'exp'];
        foreach ($required as $field) {
            if (!isset($payload[$field]) || $payload[$field] === '') {
                return new WP_Error(
                    'broker_payload_invalid',
                    'Missing required field: ' . $field
                );
            }
        }

        if ((int) $payload['exp'] < time()) {
            return new WP_Error(
                'broker_token_expired',
                'Broker token has expired'
            );
        }

        return true;
    }

    // =========================================================================
    // Verify nonce via back-channel POST to Profiles
    // =========================================================================

    public function verify_nonce(string $nonce): bool
    {
        $endpoint = rtrim($this->config['profiles_url'], '/') . '/broker/verify-nonce/';
        $token = $this->config['profiles_api_bearer_token'];

        $args = [
            'method' => 'POST',
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 15,
            'body' => wp_json_encode(['nonce' => $nonce]),
        ];

        $response = wp_remote_post($endpoint, $args);

        if (is_wp_error($response)) {
            error_log('BrokerAuth: Nonce verify request failed: ' . $response->get_error_message());
            return false;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            error_log('BrokerAuth: Nonce verify returned HTTP ' . $code);
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return isset($body['valid']) && $body['valid'] === true;
    }

    // =========================================================================
    // Find or create WordPress user
    // =========================================================================

    public function find_or_create_user(array $payload): WP_User|WP_Error
    {
        $username = $payload['kc_username'];
        $user = get_user_by('login', $username);

        if ($user instanceof WP_User) {
            error_log('BrokerAuth: Found existing user: ' . $user->ID);
            return $user;
        }

        // Create new user
        error_log('BrokerAuth: Creating new user: ' . $username);
        // Read the real broker-token shape. The account email comes from
        // primary_email only (never the IdP-asserted userinfo.email). The
        // token carries no name parts; the members-API sync is authoritative
        // for first/last name, so they are left blank here and refined on
        // sync. display_name falls back to userinfo.name, then the username.
        $userinfo_name = $payload['userinfo']['name'] ?? '';
        $user_data = [
            'user_login' => $username,
            'user_email' => $payload['primary_email'] ?? '',
            'user_pass' => wp_generate_password(32, true, true),
            'first_name' => $payload['first_name'] ?? '',
            'last_name' => $payload['last_name'] ?? '',
            'display_name' => $userinfo_name !== '' ? $userinfo_name : $username,
            'role' => 'subscriber',
        ];

        $user_id = wp_insert_user($user_data);

        if (is_wp_error($user_id)) {
            error_log('BrokerAuth: Failed to create user: ' . $user_id->get_error_message());
            return $user_id;
        }

        error_log('BrokerAuth: Created user with ID: ' . $user_id);
        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('broker_user_creation_failed', 'User created but could not be retrieved');
        }

        // Trigger full sync from Profiles members API
        Plugin::sync_user($user->user_login);

        return $user;
    }

    // =========================================================================
    // Methods carried over from CILogonAuth
    // =========================================================================

    public function handle_logout(): void
    {
        $user = wp_get_current_user();
        if (!$user || 0 === $user->ID) {
            error_log('BrokerAuth: handle_logout called but no current user.');
            return;
        }

        $endpoint = trailingslashit($this->config['profiles_url']) . 'api/v1/actions/logout/';
        $token = $this->config['profiles_api_bearer_token'];

        if (empty($token)) {
            error_log('BrokerAuth: handle_logout - missing PROFILES_API_BEARER_TOKEN.');
            return;
        }

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $user_name = $user->user_login;
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';

        $body = [
            'user_name' => $user_name,
            'user_agent' => $user_agent,
        ];

        $args = [
            'method' => 'POST',
            'headers' => $headers,
            'timeout' => 15,
            'body' => wp_json_encode($body),
        ];

        error_log(sprintf('BrokerAuth: Sending logout for "%s" to Profiles API', $user_name));

        $response = wp_remote_post($endpoint, $args);

        if (is_wp_error($response)) {
            error_log('BrokerAuth: Error sending logout: ' . $response->get_error_message());
            return;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            $resp_body = (string) wp_remote_retrieve_body($response);
            error_log(sprintf('BrokerAuth: Logout API returned HTTP %d – Body: %s', $code, substr(trim($resp_body), 0, 400)));
            return;
        }

        error_log(sprintf('BrokerAuth: Logout API success for "%s" (HTTP %d).', $user_name, $code));
    }

    public function maybe_secret_key_login(): bool
    {
        $secret = getenv('SECRET_LOGIN_KEY');
        if (empty($secret)) {
            return false;
        }

        $provided = $_GET['secret_key'] ?? '';
        if (empty($provided) || !hash_equals($secret, $provided)) {
            return false;
        }

        $identity = getenv('SECRET_LOGIN_IDENTITY') ?: 'gihctester';
        $user = get_user_by('login', $identity);
        if (!$user) {
            error_log('BrokerAuth: secret-key bypass failed - user "' . $identity . '" not found.');
            return false;
        }

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);

        $redirect_to = isset($_GET['redirect_to'])
            ? wp_validate_redirect(wp_sanitize_redirect($_GET['redirect_to']), home_url())
            : home_url();

        wp_safe_redirect($redirect_to);
        $this->terminate();
    }

    public function synchronise_user(WP_User $user, array $payload = []): void
    {
        error_log('BrokerAuth: Setting up user session for: ' . $user->user_login);
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);

        $redirect_to = home_url();

        // 1. Prefer final_redirect from decrypted broker_token payload
        if (!empty($payload['final_redirect'])) {
            $redirect_to = wp_validate_redirect(
                wp_sanitize_redirect($payload['final_redirect']),
                home_url()
            );
            error_log('BrokerAuth: Redirecting to final_redirect from payload: ' . $redirect_to);
        }
        // 2. Fall back to broker_redirect_to transient (explicit login flow)
        elseif ($stored_redirect = $this->getSessionValue('broker_redirect_to')) {
            $redirect_to = $stored_redirect;
            $this->unsetSessionValue('broker_redirect_to');
            error_log('BrokerAuth: Redirecting to stored URL: ' . $redirect_to);
        }
        // 3. Fall back to broker_sso_return_url transient (silent SSO flow)
        elseif ($sso_redirect = $this->getSessionValue('broker_sso_return_url')) {
            $redirect_to = $sso_redirect;
            $this->unsetSessionValue('broker_sso_return_url');
            error_log('BrokerAuth: Redirecting to SSO return URL: ' . $redirect_to);
        }
        // 4. Final fallback: home_url() (already set above)

        wp_safe_redirect($redirect_to);
        $this->terminate();
    }

    // =========================================================================
    // Silent SSO — auto-check Profiles session on front-end page loads
    // =========================================================================

    /**
     * Check whether this request should be excluded from silent SSO.
     */
    public function is_sso_excluded_request(): bool
    {
        // Feature flag must be enabled
        $enabled = getenv('BROKER_SILENT_SSO_ENABLED');
        if (empty($enabled)) {
            error_log('BrokerAuth SSO: excluded — BROKER_SILENT_SSO_ENABLED not set');
            return true;
        }

        // Already logged in
        if (is_user_logged_in()) {
            error_log('BrokerAuth SSO: excluded — user already logged in');
            return true;
        }

        // Already checked recently
        if (!empty($_COOKIE[self::SSO_CHECK_COOKIE])) {
            error_log('BrokerAuth SSO: excluded — broker_sso_checked cookie present');
            return true;
        }

        // User opted out
        if (!empty($_COOKIE[self::SSO_OPTOUT_COOKIE])) {
            error_log('BrokerAuth SSO: excluded — broker_sso_optout cookie present');
            return true;
        }

        // Admin pages
        if (is_admin()) {
            error_log('BrokerAuth SSO: excluded — admin page');
            return true;
        }

        // AJAX
        if (wp_doing_ajax() || (defined('DOING_AJAX') && DOING_AJAX)) {
            return true;
        }

        // Cron
        if (wp_doing_cron() || (defined('DOING_CRON') && DOING_CRON)) {
            return true;
        }

        // REST API
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($request_uri, PHP_URL_PATH) ?: '';
        if (strpos($path, '/wp-json/') !== false) {
            return true;
        }

        // xmlrpc
        if (strpos($path, '/xmlrpc.php') !== false) {
            return true;
        }

        // Non-GET/HEAD requests
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (!in_array($method, ['GET', 'HEAD'], true)) {
            return true;
        }

        // Bot user-agents
        $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        if ($ua !== '') {
            foreach (self::BOT_SIGNATURES as $bot) {
                if (strpos($ua, $bot) !== false) {
                    return true;
                }
            }
        }

        error_log('BrokerAuth SSO: not excluded — will attempt silent login');
        return false;
    }

    /**
     * On front-end page loads, silently check for an active Profiles session.
     *
     * Hooked to `template_redirect`.
     */
    public function maybe_silent_login(): void
    {
        if ($this->is_sso_excluded_request()) {
            return;
        }

        // ?no_sso=1 opt-out: set 24-hour cookie and bail
        if (!empty($_GET['no_sso'])) {
            error_log('BrokerAuth SSO: no_sso=1 param — setting optout cookie');
            $this->setSSOCookie(self::SSO_OPTOUT_COOKIE, '1', self::SSO_OPTOUT_TTL);
            return;
        }

        // Set the SSO check cookie so we don't repeat for 30 seconds
        $this->setSSOCookie(self::SSO_CHECK_COOKIE, '1', self::SSO_CHECK_TTL);

        // Store the current URL so we can redirect back after the SSO check
        $scheme = is_ssl() ? 'https' : 'http';
        $current_url = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
        $this->setSessionValue('broker_sso_return_url', $current_url);

        // Build silent login redirect URL
        $callback_url = home_url('/broker-callback/');
        $silent_login_url = rtrim($this->config['profiles_url'], '/') . '/broker/silent-login/'
            . '?return_to=' . rawurlencode($callback_url)
            . '&final_redirect=' . rawurlencode($current_url);

        error_log('BrokerAuth SSO: redirecting to silent login: ' . $silent_login_url);
        error_log('BrokerAuth SSO: stored return URL: ' . $current_url);

        // Allow Profiles host in redirects
        $profiles_host = parse_url($this->config['profiles_url'], PHP_URL_HOST);
        add_filter('allowed_redirect_hosts', function ($hosts) use ($profiles_host) {
            if ($profiles_host) {
                $hosts[] = $profiles_host;
            }
            return $hosts;
        });

        wp_safe_redirect($silent_login_url);
    }

    /**
     * Set an SSO-related cookie, capturing for test verification.
     */
    private function setSSOCookie(string $name, string $value, int $ttl): void
    {
        $expires = time() + $ttl;
        $options = [
            'expires' => $expires,
            'path' => COOKIEPATH,
            'domain' => COOKIE_DOMAIN,
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        // Capture for test verification
        if (isset($GLOBALS['_mock_setcookie_calls']) && is_array($GLOBALS['_mock_setcookie_calls'])) {
            $GLOBALS['_mock_setcookie_calls'][] = [
                'name' => $name,
                'value' => $value,
                'expires' => $expires,
                'options' => $options,
            ];
        }

        if (!headers_sent()) {
            setcookie($name, $value, $options);
        }
        $_COOKIE[$name] = $value;
    }

    /**
     * Halt execution after a redirect.
     *
     * Extracted so tests can override without killing the PHPUnit process.
     *
     * @codeCoverageIgnore
     */
    protected function terminate(): void
    {
        exit();
    }

    public function get_config(): array
    {
        return $this->config;
    }

    // =========================================================================
    // Transient-based session storage (carried over from CILogonAuth)
    // =========================================================================

    private function getTransientSessionId(): string
    {
        if (isset($_COOKIE[self::SESSION_COOKIE_NAME])) {
            return sanitize_key($_COOKIE[self::SESSION_COOKIE_NAME]);
        }

        $session_id = 'broker_auth_' . bin2hex(random_bytes(16));

        $secure = is_ssl();
        $options = [
            'expires' => time() + self::SESSION_EXPIRATION,
            'path' => COOKIEPATH,
            'domain' => COOKIE_DOMAIN,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        if (!headers_sent()) {
            if (PHP_VERSION_ID >= 70300) {
                setcookie(self::SESSION_COOKIE_NAME, $session_id, $options);
            } else {
                setcookie(
                    self::SESSION_COOKIE_NAME,
                    $session_id,
                    $options['expires'],
                    $options['path'],
                    $options['domain'],
                    $options['secure'],
                    $options['httponly']
                );
            }
        }

        $_COOKIE[self::SESSION_COOKIE_NAME] = $session_id;
        return $session_id;
    }

    private function getTransientKey(string $key): string
    {
        $session_id = $this->getTransientSessionId();
        return $session_id . '_' . $key;
    }

    private function getSessionValue(string $key)
    {
        $transient_key = $this->getTransientKey($key);
        $value = get_transient($transient_key);
        return $value !== false ? $value : false;
    }

    private function setSessionValue(string $key, $value): void
    {
        $transient_key = $this->getTransientKey($key);
        set_transient($transient_key, $value, self::SESSION_EXPIRATION);
    }

    private function unsetSessionValue(string $key): void
    {
        $transient_key = $this->getTransientKey($key);
        delete_transient($transient_key);
    }
}
