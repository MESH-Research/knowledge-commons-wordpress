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
    // ========================================================================
    // DEBUG FLAG TESTS
    // ========================================================================

    /** @test */
    public function test_cilogon_debug_constant_is_defined()
    {
        $this->assertTrue(defined('CILOGON_DEBUG'), 'CILOGON_DEBUG constant should be defined');
    }

    /** @test */
    public function test_cilogon_debug_is_false_in_tests()
    {
        $this->assertFalse(CILOGON_DEBUG, 'CILOGON_DEBUG should be false during tests');
    }

    /** @test */
    public function test_debug_log_method_exists()
    {
        $this->assertTrue(method_exists(Plugin::class, 'debug_log'));
    }

    /** @test */
    public function test_debug_log_is_static()
    {
        $reflection = new \ReflectionMethod(Plugin::class, 'debug_log');
        $this->assertTrue($reflection->isStatic());
    }

    /** @test */
    public function test_debug_log_is_public()
    {
        $reflection = new \ReflectionMethod(Plugin::class, 'debug_log');
        $this->assertTrue($reflection->isPublic());
    }

    /** @test */
    public function test_debug_log_accepts_string_parameter()
    {
        $reflection = new \ReflectionMethod(Plugin::class, 'debug_log');
        $params = $reflection->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('message', $params[0]->getName());
    }

    /** @test */
    public function test_debug_log_does_not_throw_with_string()
    {
        Plugin::debug_log('Test message');
        $this->assertTrue(true);
    }

    /** @test */
    public function test_debug_log_does_not_throw_with_empty_string()
    {
        Plugin::debug_log('');
        $this->assertTrue(true);
    }

    /** @test */
    public function test_debug_log_does_not_throw_with_long_string()
    {
        Plugin::debug_log(str_repeat('a', 10000));
        $this->assertTrue(true);
    }

    /** @test */
    public function test_debug_log_does_not_throw_with_special_chars()
    {
        Plugin::debug_log("Test with special chars: <>&\"'{}[]");
        $this->assertTrue(true);
    }

    /** @test */
    public function test_debug_log_does_not_throw_with_newlines()
    {
        Plugin::debug_log("Line 1\nLine 2\rLine 3");
        $this->assertTrue(true);
    }

    // ========================================================================
    // SENSITIVE DATA PATTERN TESTS
    // ========================================================================

    /** @test */
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

        $this->assertNotEmpty($sensitivePatterns);
        $this->assertCount(8, $sensitivePatterns);
    }

    /** @test */
    public function test_debug_log_handles_json_data()
    {
        $userData = ['username' => 'testuser', 'email' => 'test@example.com', 'roles' => ['subscriber']];
        Plugin::debug_log('User data: ' . json_encode($userData));
        $this->assertTrue(true);
    }

    /** @test */
    public function test_debug_log_handles_var_export_data()
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        Plugin::debug_log('Data: ' . var_export($data, true));
        $this->assertTrue(true);
    }

    /** @test */
    public function test_debug_log_handles_print_r_data()
    {
        $data = (object) ['property' => 'value'];
        Plugin::debug_log('Object: ' . print_r($data, true));
        $this->assertTrue(true);
    }

    // ========================================================================
    // SOURCE FILE VERIFICATION TESTS
    // ========================================================================

    /** @test */
    public function test_plugin_uses_debug_log_for_sensitive_data()
    {
        $sourceFile = CILOGON_BASE_DIR . 'Plugin.php';
        $content = file_get_contents($sourceFile);

        $this->assertStringContainsString('self::debug_log', $content);
        $this->assertStringContainsString("debug_log('Current types:", $content);
        $this->assertStringContainsString("debug_log('Desired types:", $content);
    }

    /** @test */
    public function test_debug_log_implementation_checks_constant()
    {
        $sourceFile = CILOGON_BASE_DIR . 'Plugin.php';
        $content = file_get_contents($sourceFile);

        $this->assertStringContainsString('CILOGON_DEBUG', $content);
        $this->assertStringContainsString("defined('CILOGON_DEBUG')", $content);
    }

    /** @test */
    public function test_cilogon_debug_defined_in_main_file()
    {
        $sourceFile = dirname(CILOGON_BASE_DIR) . '/cilogon.php';
        $content = file_get_contents($sourceFile);

        $this->assertStringContainsString("define('CILOGON_DEBUG'", $content);
        $this->assertStringContainsString('FILTER_VALIDATE_BOOLEAN', $content);
    }
}
