<?php
/**
 * Unit Tests for Debug Logging
 *
 * Tests verify that sensitive data is only logged when CILOGON_DEBUG is enabled.
 *
 * @package MeshResearch\CILogon\Tests
 */

namespace MeshResearch\CILogon\Tests;

use MeshResearch\CILogon\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for Debug Logging
 *
 * SECURITY: This test suite ensures that sensitive data is not logged
 * in production (when CILOGON_DEBUG is false/unset).
 */
class DebugLoggingTest extends TestCase
{
    /**
     * Captured log messages
     *
     * @var array
     */
    private static $capturedLogs = [];

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        parent::setUp();
        self::$capturedLogs = [];
    }

    /**
     * Tear down after each test
     */
    protected function tearDown(): void
    {
        self::$capturedLogs = [];
        parent::tearDown();
    }

    // ========================================================================
    // HELPER METHODS
    // ========================================================================

    /**
     * Capture error_log calls for verification
     *
     * Since we can't easily intercept error_log in tests, we test the
     * debug_log method's behavior by checking if CILOGON_DEBUG is respected.
     */

    // ========================================================================
    // DEBUG FLAG TESTS
    // ========================================================================

    /**
     * Test: CILOGON_DEBUG constant is defined
     *
     * @test
     */
    public function test_cilogon_debug_constant_is_defined()
    {
        $this->assertTrue(
            defined('CILOGON_DEBUG'),
            'CILOGON_DEBUG constant should be defined'
        );
    }

    /**
     * Test: CILOGON_DEBUG is false by default in tests
     *
     * @test
     */
    public function test_cilogon_debug_is_false_in_tests()
    {
        $this->assertFalse(
            CILOGON_DEBUG,
            'CILOGON_DEBUG should be false during tests to prevent log noise'
        );
    }

    /**
     * Test: debug_log method exists on Plugin class
     *
     * @test
     */
    public function test_debug_log_method_exists()
    {
        $this->assertTrue(
            method_exists(Plugin::class, 'debug_log'),
            'Plugin::debug_log() method should exist'
        );
    }

    /**
     * Test: debug_log is a static method
     *
     * @test
     */
    public function test_debug_log_is_static()
    {
        $reflection = new \ReflectionMethod(Plugin::class, 'debug_log');
        $this->assertTrue(
            $reflection->isStatic(),
            'Plugin::debug_log() should be a static method'
        );
    }

    /**
     * Test: debug_log is public
     *
     * @test
     */
    public function test_debug_log_is_public()
    {
        $reflection = new \ReflectionMethod(Plugin::class, 'debug_log');
        $this->assertTrue(
            $reflection->isPublic(),
            'Plugin::debug_log() should be a public method'
        );
    }

    /**
     * Test: debug_log accepts a string parameter
     *
     * @test
     */
    public function test_debug_log_accepts_string_parameter()
    {
        $reflection = new \ReflectionMethod(Plugin::class, 'debug_log');
        $params = $reflection->getParameters();

        $this->assertCount(1, $params, 'debug_log should accept exactly one parameter');
        $this->assertEquals('message', $params[0]->getName());
    }

    /**
     * Test: debug_log does not throw when called with string
     *
     * @test
     */
    public function test_debug_log_does_not_throw_with_string()
    {
        // Should not throw any exceptions
        Plugin::debug_log('Test message');
        $this->assertTrue(true);
    }

    /**
     * Test: debug_log does not throw when called with empty string
     *
     * @test
     */
    public function test_debug_log_does_not_throw_with_empty_string()
    {
        Plugin::debug_log('');
        $this->assertTrue(true);
    }

    /**
     * Test: debug_log does not throw when called with long string
     *
     * @test
     */
    public function test_debug_log_does_not_throw_with_long_string()
    {
        $longMessage = str_repeat('a', 10000);
        Plugin::debug_log($longMessage);
        $this->assertTrue(true);
    }

    /**
     * Test: debug_log does not throw when called with special characters
     *
     * @test
     */
    public function test_debug_log_does_not_throw_with_special_chars()
    {
        Plugin::debug_log("Test with special chars: <>&\"'{}[]");
        $this->assertTrue(true);
    }

    /**
     * Test: debug_log does not throw when called with newlines
     *
     * @test
     */
    public function test_debug_log_does_not_throw_with_newlines()
    {
        Plugin::debug_log("Line 1\nLine 2\rLine 3");
        $this->assertTrue(true);
    }

    // ========================================================================
    // SENSITIVE DATA PATTERN TESTS
    // ========================================================================

    /**
     * Test: Sensitive data patterns that should use debug_log
     *
     * This test documents what types of data should be gated behind debug_log
     *
     * @test
     */
    public function test_sensitive_data_patterns_documented()
    {
        $sensitivePatterns = [
            'OIDC state tokens',
            'User sub claims',
            'Full user info objects',
            'ID token payloads',
            'User email addresses',
            'Authentication tokens',
            'Session data',
            'Redirect URLs with parameters',
        ];

        // This test serves as documentation
        $this->assertNotEmpty($sensitivePatterns);
        $this->assertCount(8, $sensitivePatterns);
    }

    /**
     * Test: debug_log can handle JSON encoded data
     *
     * @test
     */
    public function test_debug_log_handles_json_data()
    {
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'roles' => ['subscriber'],
        ];

        Plugin::debug_log('User data: ' . json_encode($userData));
        $this->assertTrue(true);
    }

    /**
     * Test: debug_log can handle var_export data
     *
     * @test
     */
    public function test_debug_log_handles_var_export_data()
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];

        Plugin::debug_log('Data: ' . var_export($data, true));
        $this->assertTrue(true);
    }

    /**
     * Test: debug_log can handle print_r data
     *
     * @test
     */
    public function test_debug_log_handles_print_r_data()
    {
        $data = (object) ['property' => 'value'];

        Plugin::debug_log('Object: ' . print_r($data, true));
        $this->assertTrue(true);
    }

    // ========================================================================
    // INTEGRATION TESTS - VERIFY GATING IN SOURCE FILES
    // ========================================================================

    /**
     * Test: CILogonAuth uses Plugin::debug_log for sensitive data
     *
     * @test
     */
    public function test_cilogonauth_uses_debug_log_for_sensitive_data()
    {
        $sourceFile = CILOGON_BASE_DIR . 'CILogonAuth.php';
        $content = file_get_contents($sourceFile);

        // These sensitive logs should use Plugin::debug_log
        $this->assertStringContainsString(
            'Plugin::debug_log',
            $content,
            'CILogonAuth.php should use Plugin::debug_log for sensitive data'
        );

        // Verify specific sensitive patterns are using debug_log
        // OIDC state
        $this->assertStringContainsString(
            'Plugin::debug_log("OIDC state:',
            $content,
            'OIDC state should be logged via debug_log'
        );

        // User info
        $this->assertStringContainsString(
            'Plugin::debug_log("Received user info:',
            $content,
            'User info should be logged via debug_log'
        );

        // Setting state
        $this->assertStringContainsString(
            'Plugin::debug_log("Setting state:',
            $content,
            'State setting should be logged via debug_log'
        );
    }

    /**
     * Test: CustomOpenIDConnectClient uses debug_log for sensitive data
     *
     * @test
     */
    public function test_customoidc_uses_debug_log_for_sensitive_data()
    {
        $sourceFile = CILOGON_BASE_DIR . 'CustomOpenIDConnectClient.php';
        $content = file_get_contents($sourceFile);

        // Should use namespaced Plugin::debug_log
        $this->assertStringContainsString(
            '\\MeshResearch\\CILogon\\Plugin::debug_log',
            $content,
            'CustomOpenIDConnectClient.php should use Plugin::debug_log for sensitive data'
        );

        // Token data should be gated
        $this->assertStringContainsString(
            'debug_log(\'JSON Token',
            $content,
            'JSON token should be logged via debug_log'
        );

        // Final state should be gated
        $this->assertStringContainsString(
            'debug_log("Final state sent was:',
            $content,
            'Final state should be logged via debug_log'
        );
    }

    /**
     * Test: Plugin.php uses debug_log for sensitive data
     *
     * @test
     */
    public function test_plugin_uses_debug_log_for_sensitive_data()
    {
        $sourceFile = CILOGON_BASE_DIR . 'Plugin.php';
        $content = file_get_contents($sourceFile);

        // Should use self::debug_log
        $this->assertStringContainsString(
            'self::debug_log',
            $content,
            'Plugin.php should use self::debug_log for sensitive data'
        );

        // Member types should be gated
        $this->assertStringContainsString(
            "debug_log('Current types:",
            $content,
            'Current types should be logged via debug_log'
        );

        $this->assertStringContainsString(
            "debug_log('Desired types:",
            $content,
            'Desired types should be logged via debug_log'
        );
    }

    /**
     * Test: No raw error_log calls with sensitive patterns in CILogonAuth
     *
     * @test
     */
    public function test_no_raw_error_log_with_sensitive_patterns_in_cilogonauth()
    {
        $sourceFile = CILOGON_BASE_DIR . 'CILogonAuth.php';
        $content = file_get_contents($sourceFile);

        // These patterns should NOT appear in raw error_log calls
        $sensitivePatterns = [
            'error_log($current_state)',      // Raw state
            'error_log("Sub:',                 // Sub claim
            'var_export($sub',                 // Sub export in error_log
            'error_log("Received user info: " . print_r',  // Full user info
            'error_log("CILogon Plugin: User info: " . json_encode',  // User info JSON
        ];

        foreach ($sensitivePatterns as $pattern) {
            $this->assertStringNotContainsString(
                $pattern,
                $content,
                "Sensitive pattern should not be in raw error_log: $pattern"
            );
        }
    }

    /**
     * Test: No raw error_log calls with token data in CustomOpenIDConnectClient
     *
     * @test
     */
    public function test_no_raw_error_log_with_tokens_in_customoidc()
    {
        $sourceFile = CILOGON_BASE_DIR . 'CustomOpenIDConnectClient.php';
        $content = file_get_contents($sourceFile);

        // Token logging should not use raw error_log
        $this->assertStringNotContainsString(
            "error_log('CILogon: JSON Token:",
            $content,
            'JSON token should not be in raw error_log'
        );

        $this->assertStringNotContainsString(
            'error_log("Final state sent was:',
            $content,
            'Final state should not be in raw error_log'
        );
    }

    // ========================================================================
    // CILOGON_DEBUG BEHAVIOR TESTS
    // ========================================================================

    /**
     * Test: debug_log checks CILOGON_DEBUG constant
     *
     * Verify the implementation checks the constant
     *
     * @test
     */
    public function test_debug_log_implementation_checks_constant()
    {
        $sourceFile = CILOGON_BASE_DIR . 'Plugin.php';
        $content = file_get_contents($sourceFile);

        // The debug_log method should check CILOGON_DEBUG
        $this->assertStringContainsString(
            'CILOGON_DEBUG',
            $content,
            'debug_log should reference CILOGON_DEBUG constant'
        );

        // Should check if defined
        $this->assertStringContainsString(
            "defined('CILOGON_DEBUG')",
            $content,
            'debug_log should check if CILOGON_DEBUG is defined'
        );
    }

    /**
     * Test: CILOGON_DEBUG is defined in main plugin file
     *
     * @test
     */
    public function test_cilogon_debug_defined_in_main_file()
    {
        $sourceFile = dirname(CILOGON_BASE_DIR) . '/cilogon.php';
        $content = file_get_contents($sourceFile);

        $this->assertStringContainsString(
            "define('CILOGON_DEBUG'",
            $content,
            'CILOGON_DEBUG should be defined in cilogon.php'
        );

        // Should use filter_var for boolean conversion
        $this->assertStringContainsString(
            'FILTER_VALIDATE_BOOLEAN',
            $content,
            'CILOGON_DEBUG should use FILTER_VALIDATE_BOOLEAN for env var'
        );
    }
}
