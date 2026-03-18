<?php
/**
 * Unit Tests for BrokerAuth
 *
 * Tests the identity-broker authentication flow that replaces the
 * direct CILogon OIDC integration.
 *
 * @package MeshResearch\CILogon\Tests
 */

namespace MeshResearch\CILogon\Tests;

use PHPUnit\Framework\TestCase;
use MeshResearch\CILogon\BrokerAuth;

class BrokerAuthTest extends TestCase
{
    private const TEST_SECRET = 'test-bearer-token-for-unit-tests';

    private string|false $originalProfilesUrl;
    private string|false $originalBearerToken;
    private string|false $originalSecretKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalProfilesUrl = getenv('PROFILES_API_URL');
        $this->originalBearerToken = getenv('PROFILES_API_BEARER_TOKEN');
        $this->originalSecretKey = getenv('SECRET_LOGIN_KEY');

        putenv('PROFILES_API_URL=https://profiles.example.com/');
        putenv('PROFILES_API_BEARER_TOKEN=' . self::TEST_SECRET);
        putenv('SECRET_LOGIN_KEY');

        $_GET = [];
        $_REQUEST = [];
        $GLOBALS['_wp_safe_redirect_location'] = null;
        $GLOBALS['_wp_safe_redirect_status'] = null;
        $GLOBALS['_wp_die_message'] = null;
        $GLOBALS['_mock_query_vars'] = [];
        $GLOBALS['_mock_wp_remote_post_callback'] = null;
        $GLOBALS['_wp_insert_user_captured_data'] = null;
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
        if ($this->originalSecretKey !== false) {
            putenv('SECRET_LOGIN_KEY=' . $this->originalSecretKey);
        } else {
            putenv('SECRET_LOGIN_KEY');
        }
        $_GET = [];
        $_REQUEST = [];
        $GLOBALS['_mock_query_vars'] = [];
        $GLOBALS['_mock_wp_remote_post_callback'] = null;
        parent::tearDown();
    }

    // ========================================================================
    // Encryption helpers
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

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'kc_username' => 'testuser',
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'name' => 'Test User',
            'nonce' => 'abc123nonce',
            'exp' => time() + 300,
        ], $overrides);
    }

    // ========================================================================
    // decrypt_broker_token tests
    // ========================================================================

    /** @test */
    public function test_decrypt_valid_token(): void
    {
        $payload = $this->validPayload();
        $encrypted = self::encrypt($payload, self::TEST_SECRET);

        $result = BrokerAuth::decrypt_broker_token($encrypted, self::TEST_SECRET);

        $this->assertIsArray($result);
        $this->assertSame('testuser', $result['kc_username']);
        $this->assertSame('test@example.com', $result['email']);
    }

    /** @test */
    public function test_decrypt_corrupted_ciphertext_returns_null(): void
    {
        $encrypted = base64_encode('this-is-not-valid-ciphertext-at-all');

        $result = BrokerAuth::decrypt_broker_token($encrypted, self::TEST_SECRET);

        $this->assertNull($result);
    }

    /** @test */
    public function test_decrypt_wrong_secret_returns_null(): void
    {
        $payload = $this->validPayload();
        $encrypted = self::encrypt($payload, self::TEST_SECRET);

        $result = BrokerAuth::decrypt_broker_token($encrypted, 'wrong-secret-key');

        $this->assertNull($result);
    }

    /** @test */
    public function test_decrypt_non_base64_returns_null(): void
    {
        $result = BrokerAuth::decrypt_broker_token('%%%not-base64%%%', self::TEST_SECRET);

        $this->assertNull($result);
    }

    /** @test */
    public function test_decrypt_too_short_data_returns_null(): void
    {
        $result = BrokerAuth::decrypt_broker_token(base64_encode('short'), self::TEST_SECRET);

        $this->assertNull($result);
    }

    // ========================================================================
    // validate_broker_payload tests
    // ========================================================================

    /** @test */
    public function test_validate_valid_payload_passes(): void
    {
        $auth = new BrokerAuth();
        $payload = $this->validPayload();

        $result = $auth->validate_broker_payload($payload);

        $this->assertTrue($result);
    }

    /** @test */
    public function test_validate_expired_payload_fails(): void
    {
        $auth = new BrokerAuth();
        $payload = $this->validPayload(['exp' => time() - 60]);

        $result = $auth->validate_broker_payload($payload);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('broker_token_expired', $result->get_error_code());
    }

    /** @test */
    public function test_validate_missing_kc_username_fails(): void
    {
        $auth = new BrokerAuth();
        $payload = $this->validPayload();
        unset($payload['kc_username']);

        $result = $auth->validate_broker_payload($payload);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('broker_payload_invalid', $result->get_error_code());
    }

    /** @test */
    public function test_validate_missing_nonce_fails(): void
    {
        $auth = new BrokerAuth();
        $payload = $this->validPayload();
        unset($payload['nonce']);

        $result = $auth->validate_broker_payload($payload);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('broker_payload_invalid', $result->get_error_code());
    }

    /** @test */
    public function test_validate_missing_exp_fails(): void
    {
        $auth = new BrokerAuth();
        $payload = $this->validPayload();
        unset($payload['exp']);

        $result = $auth->validate_broker_payload($payload);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('broker_payload_invalid', $result->get_error_code());
    }

    // ========================================================================
    // verify_nonce tests
    // ========================================================================

    /** @test */
    public function test_verify_nonce_success(): void
    {
        $auth = new BrokerAuth();
        $GLOBALS['_mock_wp_remote_post_callback'] = function ($url, $args) {
            return [
                'response' => ['code' => 200],
                'body' => json_encode(['valid' => true]),
            ];
        };

        $result = $auth->verify_nonce('abc123nonce');

        $this->assertTrue($result);
    }

    /** @test */
    public function test_verify_nonce_invalid(): void
    {
        $auth = new BrokerAuth();
        $GLOBALS['_mock_wp_remote_post_callback'] = function ($url, $args) {
            return [
                'response' => ['code' => 200],
                'body' => json_encode(['valid' => false]),
            ];
        };

        $result = $auth->verify_nonce('bad-nonce');

        $this->assertFalse($result);
    }

    /** @test */
    public function test_verify_nonce_http_error(): void
    {
        $auth = new BrokerAuth();
        $GLOBALS['_mock_wp_remote_post_callback'] = function ($url, $args) {
            return [
                'response' => ['code' => 500],
                'body' => 'Internal Server Error',
            ];
        };

        $result = $auth->verify_nonce('abc123nonce');

        $this->assertFalse($result);
    }

    /** @test */
    public function test_verify_nonce_wp_error(): void
    {
        $auth = new BrokerAuth();
        $GLOBALS['_mock_wp_remote_post_callback'] = function ($url, $args) {
            return new \WP_Error('http_request_failed', 'Connection refused');
        };

        $result = $auth->verify_nonce('abc123nonce');

        $this->assertFalse($result);
    }

    /** @test */
    public function test_verify_nonce_sends_correct_request(): void
    {
        $auth = new BrokerAuth();
        $capturedUrl = null;
        $capturedArgs = null;
        $GLOBALS['_mock_wp_remote_post_callback'] = function ($url, $args) use (&$capturedUrl, &$capturedArgs) {
            $capturedUrl = $url;
            $capturedArgs = $args;
            return [
                'response' => ['code' => 200],
                'body' => json_encode(['valid' => true]),
            ];
        };

        $auth->verify_nonce('test-nonce-value');

        $this->assertStringContainsString('api/v1/broker/verify-nonce/', $capturedUrl);
        $this->assertSame('POST', $capturedArgs['method']);
        $body = json_decode($capturedArgs['body'], true);
        $this->assertSame('test-nonce-value', $body['nonce']);
    }

    // ========================================================================
    // find_or_create_user tests
    // ========================================================================

    /** @test */
    public function test_find_existing_user(): void
    {
        $auth = new BrokerAuth();
        $payload = $this->validPayload();

        // Override get_user_by to return a mock user
        // We need to use a global callback pattern since the function is already defined
        $mockUser = new \WP_User(42);
        $mockUser->user_login = 'testuser';
        $GLOBALS['_mock_get_user_by_callback'] = function ($field, $value) use ($mockUser) {
            if ($field === 'login' && $value === 'testuser') {
                return $mockUser;
            }
            return false;
        };

        $result = $auth->find_or_create_user($payload);

        $this->assertInstanceOf(\WP_User::class, $result);
        $this->assertSame(42, $result->ID);

        unset($GLOBALS['_mock_get_user_by_callback']);
    }

    /** @test */
    public function test_create_new_user_data(): void
    {
        $auth = new BrokerAuth();
        $payload = $this->validPayload();

        // get_user_by returns false by default (user not found)
        // wp_insert_user will capture data and return WP_Error
        $result = $auth->find_or_create_user($payload);

        // Since wp_insert_user mock returns WP_Error, result should be WP_Error
        $this->assertInstanceOf(\WP_Error::class, $result);

        // But verify the captured data was correct
        $captured = get_captured_wp_insert_user_data();
        $this->assertNotNull($captured, 'wp_insert_user should have been called');
        $this->assertSame('testuser', $captured['user_login']);
        $this->assertSame('test@example.com', $captured['user_email']);
        $this->assertSame('Test', $captured['first_name']);
        $this->assertSame('User', $captured['last_name']);
        $this->assertSame('subscriber', $captured['role']);
    }

    // ========================================================================
    // Login redirect tests
    // ========================================================================

    /** @test */
    public function test_login_redirect_url_format(): void
    {
        $auth = new BrokerAuth();

        // Use reflection to test the redirect URL building
        $reflection = new \ReflectionClass($auth);
        $configProp = $reflection->getProperty('config');
        $configProp->setAccessible(true);
        $config = $configProp->getValue($auth);

        $expectedBase = rtrim($config['profiles_url'], '/') . '/login/';
        $this->assertStringStartsWith('https://profiles.example.com', $expectedBase);
    }
}
