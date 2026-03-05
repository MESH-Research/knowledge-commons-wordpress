<?php
/**
 * Unit Tests for Secret-Key Login Bypass
 *
 * Tests verify that CILogonAuth::maybe_secret_key_login() properly gates
 * access behind the SECRET_LOGIN_KEY environment variable and performs
 * timing-safe comparison of the provided key.
 *
 * @package MeshResearch\CILogon\Tests
 */

namespace MeshResearch\CILogon\Tests;

use PHPUnit\Framework\TestCase;
use MeshResearch\CILogon\CILogonAuth;

class LoginBypassTest extends TestCase
{
    private string|false $originalSecretKey;
    private string|false $originalIdentity;
    private CILogonAuth $auth;

    private const TEST_SECRET = 'e2e-test-key-12345';

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalSecretKey = getenv('SECRET_LOGIN_KEY');
        $this->originalIdentity = getenv('SECRET_LOGIN_IDENTITY');
        $_GET = [];
        $GLOBALS['_wp_safe_redirect_location'] = null;
        $GLOBALS['_wp_safe_redirect_status'] = null;

        // CILogonAuth constructor calls init_hooks() which registers the
        // login_init action. The mock add_action is a no-op, so this is safe.
        $this->auth = new CILogonAuth();
    }

    protected function tearDown(): void
    {
        if ($this->originalSecretKey !== false) {
            putenv('SECRET_LOGIN_KEY=' . $this->originalSecretKey);
        } else {
            putenv('SECRET_LOGIN_KEY');
        }
        if ($this->originalIdentity !== false) {
            putenv('SECRET_LOGIN_IDENTITY=' . $this->originalIdentity);
        } else {
            putenv('SECRET_LOGIN_IDENTITY');
        }
        $_GET = [];
        parent::tearDown();
    }

    /**
     * @test
     */
    public function test_bypass_skipped_when_secret_key_env_is_empty(): void
    {
        putenv('SECRET_LOGIN_KEY');
        $_GET['secret_key'] = 'anything';

        $result = $this->auth->maybe_secret_key_login();

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function test_bypass_skipped_when_query_param_missing(): void
    {
        putenv('SECRET_LOGIN_KEY=' . self::TEST_SECRET);

        $result = $this->auth->maybe_secret_key_login();

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function test_bypass_skipped_when_key_does_not_match(): void
    {
        putenv('SECRET_LOGIN_KEY=' . self::TEST_SECRET);
        $_GET['secret_key'] = 'wrong-key';

        $result = $this->auth->maybe_secret_key_login();

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function test_bypass_skipped_when_user_not_found(): void
    {
        putenv('SECRET_LOGIN_KEY=' . self::TEST_SECRET);
        putenv('SECRET_LOGIN_IDENTITY=nonexistent_user');
        $_GET['secret_key'] = self::TEST_SECRET;

        // The mock get_user_by always returns false, so user won't be found.
        $result = $this->auth->maybe_secret_key_login();

        $this->assertFalse($result);
    }
}
