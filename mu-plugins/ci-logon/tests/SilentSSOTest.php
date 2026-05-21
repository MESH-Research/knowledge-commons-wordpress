<?php
/**
 * Unit Tests for Silent SSO
 *
 * Tests the transparent/silent SSO flow that automatically checks
 * whether a user has an active Profiles session and logs them in.
 *
 * @package MeshResearch\CILogon\Tests
 */

namespace MeshResearch\CILogon\Tests;

use PHPUnit\Framework\TestCase;
use MeshResearch\CILogon\BrokerAuth;

/**
 * Test-safe subclass that prevents exit() from killing PHPUnit.
 */
class TestableBrokerAuth extends BrokerAuth
{
    protected function terminate(): void
    {
        // No-op in tests — prevent exit() from killing PHPUnit
    }
}

class SilentSSOTest extends TestCase
{
    private const TEST_SECRET = 'test-bearer-token-for-unit-tests';

    private string|false $originalProfilesUrl;
    private string|false $originalBearerToken;
    private string|false $originalSilentSSO;

    /** @var array Captures setcookie() calls */
    private array $capturedCookies = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalProfilesUrl = getenv('PROFILES_API_URL');
        $this->originalBearerToken = getenv('PROFILES_API_BEARER_TOKEN');
        $this->originalSilentSSO = getenv('BROKER_SILENT_SSO_ENABLED');

        putenv('PROFILES_API_URL=https://profiles.example.com/');
        putenv('PROFILES_API_BEARER_TOKEN=' . self::TEST_SECRET);
        putenv('BROKER_SILENT_SSO_ENABLED=1');

        $_GET = [];
        $_REQUEST = [];
        $_COOKIE = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/some-page/';
        $_SERVER['HTTP_HOST'] = 'example.com';
        unset($_SERVER['HTTP_USER_AGENT']);

        $GLOBALS['_mock_is_user_logged_in'] = false;
        $GLOBALS['_mock_is_admin'] = false;
        $GLOBALS['_mock_wp_doing_ajax'] = false;
        $GLOBALS['_mock_wp_doing_cron'] = false;
        $GLOBALS['_wp_safe_redirect_location'] = null;
        $GLOBALS['_wp_safe_redirect_status'] = null;
        $GLOBALS['_wp_die_message'] = null;
        $GLOBALS['_mock_query_vars'] = [];
        $GLOBALS['_mock_wp_remote_post_callback'] = null;
        $GLOBALS['_wp_insert_user_captured_data'] = null;
        $GLOBALS['_mock_get_user_by_callback'] = null;
        $GLOBALS['_mock_setcookie_calls'] = [];
        $GLOBALS['_mock_set_transient_store'] = [];
        $GLOBALS['_mock_get_transient_store'] = [];

        $this->capturedCookies = [];
    }

    protected function tearDown(): void
    {
        if ($this->originalProfilesUrl !== false) {
            putenv('PROFILES_API_URL=' . $this->originalProfilesUrl);
        } else {
            putenv('PROFILES_API_URL');
        }
        if ($this->originalBearerToken !== false) {
            putenv('PROFILES_API_BEARER_TOKEN=' . $this->originalBearerToken);
        } else {
            putenv('PROFILES_API_BEARER_TOKEN');
        }
        if ($this->originalSilentSSO !== false) {
            putenv('BROKER_SILENT_SSO_ENABLED=' . $this->originalSilentSSO);
        } else {
            putenv('BROKER_SILENT_SSO_ENABLED');
        }

        $_GET = [];
        $_REQUEST = [];
        $_COOKIE = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        unset($_SERVER['HTTP_USER_AGENT']);

        $GLOBALS['_mock_is_user_logged_in'] = false;
        $GLOBALS['_mock_is_admin'] = false;
        $GLOBALS['_mock_wp_doing_ajax'] = false;
        $GLOBALS['_mock_wp_doing_cron'] = false;
        $GLOBALS['_mock_query_vars'] = [];
        $GLOBALS['_mock_wp_remote_post_callback'] = null;
        $GLOBALS['_mock_get_user_by_callback'] = null;
        $GLOBALS['_mock_setcookie_calls'] = [];
        $GLOBALS['_mock_set_transient_store'] = [];
        $GLOBALS['_mock_get_transient_store'] = [];

        parent::tearDown();
    }

    // ========================================================================
    // is_sso_excluded_request() guard condition tests
    // ========================================================================

    /** @test */
    public function test_excluded_when_user_logged_in(): void
    {
        $auth = new TestableBrokerAuth();
        $GLOBALS['_mock_is_user_logged_in'] = true;

        $this->assertTrue($auth->is_sso_excluded_request());
    }

    /** @test */
    public function test_excluded_when_sso_cookie_exists(): void
    {
        $auth = new TestableBrokerAuth();
        $_COOKIE['broker_sso_checked'] = '1';

        $this->assertTrue($auth->is_sso_excluded_request());
    }

    /** @test */
    public function test_excluded_when_feature_flag_disabled(): void
    {
        putenv('BROKER_SILENT_SSO_ENABLED');
        $auth = new TestableBrokerAuth();

        $this->assertTrue($auth->is_sso_excluded_request());
    }

    /** @test */
    public function test_excluded_for_admin_pages(): void
    {
        $auth = new TestableBrokerAuth();
        $GLOBALS['_mock_is_admin'] = true;

        $this->assertTrue($auth->is_sso_excluded_request());
    }

    /** @test */
    public function test_excluded_for_ajax(): void
    {
        $auth = new TestableBrokerAuth();
        $GLOBALS['_mock_wp_doing_ajax'] = true;

        $this->assertTrue($auth->is_sso_excluded_request());
    }

    /** @test */
    public function test_excluded_for_cron(): void
    {
        $auth = new TestableBrokerAuth();
        $GLOBALS['_mock_wp_doing_cron'] = true;

        $this->assertTrue($auth->is_sso_excluded_request());
    }

    /** @test */
    public function test_excluded_for_rest_api(): void
    {
        $auth = new TestableBrokerAuth();
        $_SERVER['REQUEST_URI'] = '/wp-json/wp/v2/posts';

        $this->assertTrue($auth->is_sso_excluded_request());
    }

    /** @test */
    public function test_excluded_for_post_requests(): void
    {
        $auth = new TestableBrokerAuth();
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->assertTrue($auth->is_sso_excluded_request());
    }

    /** @test */
    public function test_excluded_for_xmlrpc(): void
    {
        $auth = new TestableBrokerAuth();
        $_SERVER['REQUEST_URI'] = '/xmlrpc.php';

        $this->assertTrue($auth->is_sso_excluded_request());
    }

    /** @test */
    public function test_excluded_for_bot_user_agent(): void
    {
        $auth = new TestableBrokerAuth();
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

        $this->assertTrue($auth->is_sso_excluded_request());
    }

    /** @test */
    public function test_excluded_when_optout_cookie_set(): void
    {
        $auth = new TestableBrokerAuth();
        $_COOKIE['broker_sso_optout'] = '1';

        $this->assertTrue($auth->is_sso_excluded_request());
    }

    /** @test */
    public function test_not_excluded_for_normal_get(): void
    {
        $auth = new TestableBrokerAuth();

        $this->assertFalse($auth->is_sso_excluded_request());
    }

    // ========================================================================
    // Silent login redirect tests
    // ========================================================================

    /** @test */
    public function test_silent_login_redirect_url(): void
    {
        $auth = new TestableBrokerAuth();

        $auth->maybe_silent_login();

        $location = $GLOBALS['_wp_safe_redirect_location'] ?? '';
        $this->assertStringStartsWith(
            'https://profiles.example.com/broker/silent-login/',
            $location
        );
        $this->assertStringContainsString('return_to=', $location);
        // The mock home_url() produces a double slash; real WP does not
        $this->assertStringContainsString('broker-callback', $location);
    }

    /** @test */
    public function test_silent_login_sets_sso_cookie(): void
    {
        $auth = new TestableBrokerAuth();

        $auth->maybe_silent_login();

        $cookies = $GLOBALS['_mock_setcookie_calls'] ?? [];
        $ssoCookie = null;
        foreach ($cookies as $c) {
            if ($c['name'] === 'broker_sso_checked') {
                $ssoCookie = $c;
                break;
            }
        }

        $this->assertNotNull($ssoCookie, 'broker_sso_checked cookie should be set');
        $this->assertSame('1', $ssoCookie['value']);
        // TTL should be ~15 minutes (900 seconds) from now
        $this->assertGreaterThan(time() + 895, $ssoCookie['expires']);
        $this->assertLessThanOrEqual(time() + 900, $ssoCookie['expires']);
    }

    /** @test */
    public function test_silent_login_stores_current_url(): void
    {
        $_SERVER['REQUEST_URI'] = '/my-groups/';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $auth = new TestableBrokerAuth();

        $auth->maybe_silent_login();

        $store = $GLOBALS['_mock_set_transient_store'] ?? [];
        // Find the transient that stores the return URL
        $found = false;
        foreach ($store as $entry) {
            if (str_contains($entry['key'], 'broker_sso_return_url') && str_contains($entry['value'], '/my-groups/')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Current URL should be stored in transient via setSessionValue');
    }

    /** @test */
    public function test_no_sso_param_sets_optout_cookie(): void
    {
        $_GET['no_sso'] = '1';
        $auth = new TestableBrokerAuth();

        $auth->maybe_silent_login();

        $cookies = $GLOBALS['_mock_setcookie_calls'] ?? [];
        $optoutCookie = null;
        foreach ($cookies as $c) {
            if ($c['name'] === 'broker_sso_optout') {
                $optoutCookie = $c;
                break;
            }
        }

        $this->assertNotNull($optoutCookie, 'broker_sso_optout cookie should be set');
        $this->assertSame('1', $optoutCookie['value']);
        // TTL should be ~24 hours from now
        $this->assertGreaterThan(time() + 86300, $optoutCookie['expires']);
        $this->assertLessThanOrEqual(time() + 86400, $optoutCookie['expires']);

        // Should NOT redirect
        $this->assertNull($GLOBALS['_wp_safe_redirect_location']);
    }

    // ========================================================================
    // No-session callback tests
    // ========================================================================

    /** @test */
    public function test_no_session_callback_redirects_to_stored_url(): void
    {
        // Simulate a stored return URL in transient
        $GLOBALS['_mock_get_transient_store'] = ['broker_sso_return_url' => 'https://example.com/my-groups/'];

        $_SERVER['REQUEST_URI'] = '/broker-callback/';
        $_GET['no_session'] = '1';
        $auth = new TestableBrokerAuth();

        $auth->handle_broker_callback();

        $this->assertSame('https://example.com/my-groups/', $GLOBALS['_wp_safe_redirect_location']);
    }

    /** @test */
    public function test_no_session_callback_redirects_to_home_if_no_stored_url(): void
    {
        $_SERVER['REQUEST_URI'] = '/broker-callback/';
        $_GET['no_session'] = '1';
        $auth = new TestableBrokerAuth();

        $auth->handle_broker_callback();

        $this->assertSame('https://example.com', $GLOBALS['_wp_safe_redirect_location']);
    }

    /** @test */
    public function test_no_session_callback_does_not_create_user(): void
    {
        $_SERVER['REQUEST_URI'] = '/broker-callback/';
        $_GET['no_session'] = '1';
        $auth = new TestableBrokerAuth();

        $auth->handle_broker_callback();

        $this->assertNull(get_captured_wp_insert_user_data());
    }

    // ========================================================================
    // Integration tests
    // ========================================================================

    /** @test */
    public function test_broker_token_callback_still_works(): void
    {
        // Set up nonce verification mock
        $GLOBALS['_mock_wp_remote_post_callback'] = function ($url, $args) {
            return [
                'response' => ['code' => 200],
                'body' => json_encode(['valid' => true]),
            ];
        };

        // Set up user lookup mock
        $mockUser = new \WP_User(42);
        $mockUser->user_login = 'testuser';
        $GLOBALS['_mock_get_user_by_callback'] = function ($field, $value) use ($mockUser) {
            if ($field === 'login' && $value === 'testuser') {
                return $mockUser;
            }
            return false;
        };

        $payload = [
            'kc_username' => 'testuser',
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'name' => 'Test User',
            'nonce' => 'abc123nonce',
            'exp' => time() + 300,
        ];
        $encrypted = self::encrypt($payload, self::TEST_SECRET);

        $_SERVER['REQUEST_URI'] = '/broker-callback/';
        $_GET['broker_token'] = $encrypted;
        $auth = new TestableBrokerAuth();

        $auth->handle_broker_callback();

        // Should redirect (synchronise_user calls wp_safe_redirect)
        $this->assertNotNull($GLOBALS['_wp_safe_redirect_location']);
    }

    /** @test */
    public function test_silent_sso_uses_stored_url_after_login(): void
    {
        // Simulate the stored SSO return URL
        $GLOBALS['_mock_get_transient_store'] = ['broker_sso_return_url' => 'https://example.com/original-page/'];

        // Set up nonce verification mock
        $GLOBALS['_mock_wp_remote_post_callback'] = function ($url, $args) {
            return [
                'response' => ['code' => 200],
                'body' => json_encode(['valid' => true]),
            ];
        };

        // Set up user lookup mock
        $mockUser = new \WP_User(42);
        $mockUser->user_login = 'testuser';
        $GLOBALS['_mock_get_user_by_callback'] = function ($field, $value) use ($mockUser) {
            if ($field === 'login' && $value === 'testuser') {
                return $mockUser;
            }
            return false;
        };

        $payload = [
            'kc_username' => 'testuser',
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'name' => 'Test User',
            'nonce' => 'abc123nonce',
            'exp' => time() + 300,
        ];
        $encrypted = self::encrypt($payload, self::TEST_SECRET);

        $_SERVER['REQUEST_URI'] = '/broker-callback/';
        $_GET['broker_token'] = $encrypted;
        $auth = new TestableBrokerAuth();

        $auth->handle_broker_callback();

        // Should redirect to the stored SSO return URL
        $this->assertSame('https://example.com/original-page/', $GLOBALS['_wp_safe_redirect_location']);
    }

    // ========================================================================
    // final_redirect pass-through tests
    // ========================================================================

    /** @test */
    public function test_login_redirect_includes_final_redirect_from_redirect_to(): void
    {
        $_GET['redirect_to'] = '/groups/';
        $auth = new TestableBrokerAuth();

        $auth->do_login_redirect();

        $location = $GLOBALS['_wp_safe_redirect_location'] ?? '';
        $this->assertStringContainsString('final_redirect=', $location);
        $this->assertStringContainsString(rawurlencode('/groups/'), $location);
    }

    /** @test */
    public function test_login_redirect_uses_referer_when_no_redirect_to(): void
    {
        $_SERVER['HTTP_REFERER'] = 'https://example.com/groups/';
        $auth = new TestableBrokerAuth();

        $auth->do_login_redirect();

        $location = $GLOBALS['_wp_safe_redirect_location'] ?? '';
        $this->assertStringContainsString('final_redirect=', $location);
        $this->assertStringContainsString(rawurlencode('https://example.com/groups/'), $location);
    }

    /** @test */
    public function test_login_redirect_defaults_final_redirect_to_home(): void
    {
        // No redirect_to, no referer
        unset($_SERVER['HTTP_REFERER']);
        $auth = new TestableBrokerAuth();

        $auth->do_login_redirect();

        $location = $GLOBALS['_wp_safe_redirect_location'] ?? '';
        $this->assertStringContainsString('final_redirect=', $location);
        $this->assertStringContainsString(rawurlencode(home_url()), $location);
    }

    /** @test */
    public function test_silent_login_includes_final_redirect(): void
    {
        $_SERVER['REQUEST_URI'] = '/groups/';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $auth = new TestableBrokerAuth();

        $auth->maybe_silent_login();

        $location = $GLOBALS['_wp_safe_redirect_location'] ?? '';
        $this->assertStringContainsString('final_redirect=', $location);
        // The current URL should be included as final_redirect
        $this->assertStringContainsString(rawurlencode('/groups/'), $location);
    }

    /** @test */
    public function test_synchronise_user_uses_final_redirect_from_payload(): void
    {
        // Set up nonce verification mock
        $GLOBALS['_mock_wp_remote_post_callback'] = function ($url, $args) {
            return [
                'response' => ['code' => 200],
                'body' => json_encode(['valid' => true]),
            ];
        };

        // Set up user lookup mock
        $mockUser = new \WP_User(42);
        $mockUser->user_login = 'testuser';
        $GLOBALS['_mock_get_user_by_callback'] = function ($field, $value) use ($mockUser) {
            if ($field === 'login' && $value === 'testuser') {
                return $mockUser;
            }
            return false;
        };

        $payload = [
            'kc_username' => 'testuser',
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'name' => 'Test User',
            'nonce' => 'abc123nonce',
            'exp' => time() + 300,
            'final_redirect' => 'https://example.com/groups/',
        ];
        $encrypted = self::encrypt($payload, self::TEST_SECRET);

        $_SERVER['REQUEST_URI'] = '/broker-callback/';
        $_GET['broker_token'] = $encrypted;
        $auth = new TestableBrokerAuth();

        $auth->handle_broker_callback();

        $this->assertSame('https://example.com/groups/', $GLOBALS['_wp_safe_redirect_location']);
    }

    /** @test */
    public function test_synchronise_user_falls_back_to_transient(): void
    {
        // Store a transient redirect URL but don't include final_redirect in payload
        $GLOBALS['_mock_get_transient_store'] = ['broker_redirect_to' => 'https://example.com/members/'];

        // Set up nonce verification mock
        $GLOBALS['_mock_wp_remote_post_callback'] = function ($url, $args) {
            return [
                'response' => ['code' => 200],
                'body' => json_encode(['valid' => true]),
            ];
        };

        // Set up user lookup mock
        $mockUser = new \WP_User(42);
        $mockUser->user_login = 'testuser';
        $GLOBALS['_mock_get_user_by_callback'] = function ($field, $value) use ($mockUser) {
            if ($field === 'login' && $value === 'testuser') {
                return $mockUser;
            }
            return false;
        };

        $payload = [
            'kc_username' => 'testuser',
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'name' => 'Test User',
            'nonce' => 'abc123nonce',
            'exp' => time() + 300,
            // No final_redirect — should fall back to transient
        ];
        $encrypted = self::encrypt($payload, self::TEST_SECRET);

        $_SERVER['REQUEST_URI'] = '/broker-callback/';
        $_GET['broker_token'] = $encrypted;
        $auth = new TestableBrokerAuth();

        $auth->handle_broker_callback();

        $this->assertSame('https://example.com/members/', $GLOBALS['_wp_safe_redirect_location']);
    }

    /** @test */
    public function test_no_session_still_uses_transient(): void
    {
        // For no_session, Profiles doesn't pass final_redirect — transient is the only source
        $GLOBALS['_mock_get_transient_store'] = ['broker_sso_return_url' => 'https://example.com/groups/'];

        $_SERVER['REQUEST_URI'] = '/broker-callback/';
        $_GET['no_session'] = '1';
        $auth = new TestableBrokerAuth();

        $auth->handle_broker_callback();

        $this->assertSame('https://example.com/groups/', $GLOBALS['_wp_safe_redirect_location']);
    }

    // ========================================================================
    // Encryption helper (same as BrokerAuthTest)
    // ========================================================================

    private static function encrypt(array $payload, string $secret): string
    {
        $key = hash('sha256', $secret, true);
        $iv = random_bytes(16);
        $cipherRaw = openssl_encrypt(
            json_encode($payload),
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        return base64_encode($iv . $cipherRaw);
    }
}
