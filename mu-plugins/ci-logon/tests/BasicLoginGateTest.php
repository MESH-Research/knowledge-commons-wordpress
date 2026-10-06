<?php
/**
 * Unit tests for the KC basic-login gate in Plugin::basic_login_requested().
 *
 * The contract: when (and only when) KC_BASIC_LOGIN_KEY is configured and the
 * request presents the matching key — either as the ?basic-login= query
 * parameter or as a valid signed session cookie — the broker auth flow is
 * bypassed so the native WordPress login form is reachable. A missing feature
 * flag, a wrong key, or a tampered/expired cookie must leave the broker flow in
 * charge. These assert behaviour (bypass or not), not how it is wired.
 *
 * @package MeshResearch\CILogon\Tests
 */

namespace MeshResearch\CILogon\Tests;

use PHPUnit\Framework\TestCase;
use MeshResearch\CILogon\Plugin;

// The gate reuses the signing/verification helpers from the kc-basic-login
// MU plugin. Load them for the tests (defining functions only; it arms no hooks
// unless KC_BASIC_LOGIN_KEY is set, which it is not at load time).
require_once dirname( __DIR__, 2 ) . '/kc-basic-login.php';

class BasicLoginGateTest extends TestCase
{
    private string|false $originalKey;

    private const TEST_SECRET = 'basic-login-shared-key-abcdef0123456789';

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalKey = getenv( 'KC_BASIC_LOGIN_KEY' );
        $_GET    = [];
        $_COOKIE = [];
    }

    protected function tearDown(): void
    {
        if ( $this->originalKey !== false ) {
            putenv( 'KC_BASIC_LOGIN_KEY=' . $this->originalKey );
        } else {
            putenv( 'KC_BASIC_LOGIN_KEY' );
        }
        $_GET    = [];
        $_COOKIE = [];
        parent::tearDown();
    }

    public function test_no_bypass_when_feature_flag_unset(): void
    {
        putenv( 'KC_BASIC_LOGIN_KEY' );
        $_GET['basic-login'] = self::TEST_SECRET;

        $this->assertFalse( Plugin::basic_login_requested() );
    }

    public function test_bypass_when_valid_key_in_query_string(): void
    {
        putenv( 'KC_BASIC_LOGIN_KEY=' . self::TEST_SECRET );
        $_GET['basic-login'] = self::TEST_SECRET;

        $this->assertTrue( Plugin::basic_login_requested() );
    }

    public function test_no_bypass_when_wrong_key_in_query_string(): void
    {
        putenv( 'KC_BASIC_LOGIN_KEY=' . self::TEST_SECRET );
        $_GET['basic-login'] = 'wrong-key';

        $this->assertFalse( Plugin::basic_login_requested() );
    }

    public function test_no_bypass_when_nothing_presented(): void
    {
        putenv( 'KC_BASIC_LOGIN_KEY=' . self::TEST_SECRET );

        $this->assertFalse( Plugin::basic_login_requested() );
    }

    public function test_bypass_when_valid_session_cookie_present(): void
    {
        putenv( 'KC_BASIC_LOGIN_KEY=' . self::TEST_SECRET );
        $_COOKIE[ \KC\BasicLogin\COOKIE_NAME ] =
            \KC\BasicLogin\issue_token( self::TEST_SECRET, time(), 3600 );

        $this->assertTrue( Plugin::basic_login_requested() );
    }

    public function test_no_bypass_when_cookie_is_expired(): void
    {
        putenv( 'KC_BASIC_LOGIN_KEY=' . self::TEST_SECRET );
        $_COOKIE[ \KC\BasicLogin\COOKIE_NAME ] =
            \KC\BasicLogin\issue_token( self::TEST_SECRET, time() - 7200, 3600 );

        $this->assertFalse( Plugin::basic_login_requested() );
    }

    public function test_no_bypass_when_cookie_signed_with_other_secret(): void
    {
        putenv( 'KC_BASIC_LOGIN_KEY=' . self::TEST_SECRET );
        $_COOKIE[ \KC\BasicLogin\COOKIE_NAME ] =
            \KC\BasicLogin\issue_token( 'some-other-secret', time(), 3600 );

        $this->assertFalse( Plugin::basic_login_requested() );
    }
}
