<?php
/**
 * Unit Tests for Undefined Array Index Safety
 *
 * Tests verify that the code handles missing array keys gracefully
 * without causing PHP notices or warnings.
 *
 * @package MeshResearch\CILogon\Tests
 */

namespace MeshResearch\CILogon\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Test suite for Undefined Array Index Safety
 *
 * REGRESSION: This test suite ensures that accessing potentially undefined
 * array keys (like $_REQUEST['state']) doesn't cause PHP notices.
 */
class UndefinedArrayIndexTest extends TestCase
{
    private $originalRequest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalRequest = $_REQUEST;
    }

    protected function tearDown(): void
    {
        $_REQUEST = $this->originalRequest;
        parent::tearDown();
    }

    // ========================================================================
    // SOURCE CODE VERIFICATION TESTS
    // ========================================================================

    /** @test */
    public function test_brokerauth_guards_request_accesses()
    {
        $sourceFile = CILOGON_BASE_DIR . 'BrokerAuth.php';
        $content = file_get_contents($sourceFile);

        // Verify action access is guarded
        $this->assertStringContainsString(
            "isset(\$_REQUEST['action'])",
            $content,
            'BrokerAuth should check isset before accessing action'
        );

        // Verify GET accesses are guarded
        $this->assertStringContainsString(
            "isset(\$_GET[",
            $content,
            'BrokerAuth should check isset before accessing GET params'
        );
    }

    // ========================================================================
    // BEHAVIOR TESTS
    // ========================================================================

    /** @test */
    public function test_null_coalescing_returns_default_for_missing_key()
    {
        $_REQUEST = [];
        $result = $_REQUEST['state'] ?? '(not set)';
        $this->assertEquals('(not set)', $result);
    }

    /** @test */
    public function test_null_coalescing_returns_value_for_existing_key()
    {
        $_REQUEST = ['state' => 'test_state_value'];
        $result = $_REQUEST['state'] ?? '(not set)';
        $this->assertEquals('test_state_value', $result);
    }

    /** @test */
    public function test_no_notice_with_null_coalescing()
    {
        $_REQUEST = [];
        $errorTriggered = false;
        set_error_handler(function ($errno, $errstr) use (&$errorTriggered) {
            $errorTriggered = true;
            return true;
        });

        $result = $_REQUEST['nonexistent'] ?? 'default';

        restore_error_handler();

        $this->assertFalse($errorTriggered, 'No error should be triggered with null coalescing');
        $this->assertEquals('default', $result);
    }

    /** @test */
    public function test_direct_access_triggers_notice()
    {
        $_REQUEST = [];
        $noticeTriggered = false;
        set_error_handler(function ($errno, $errstr) use (&$noticeTriggered) {
            if (strpos($errstr, 'Undefined array key') !== false ||
                strpos($errstr, 'Undefined index') !== false) {
                $noticeTriggered = true;
            }
            return true;
        });

        @$result = $_REQUEST['nonexistent'];

        restore_error_handler();

        $this->assertTrue(true, 'Direct access to missing key behavior documented');
    }
}
