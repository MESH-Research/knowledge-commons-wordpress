<?php
/**
 * Unit Tests for Logout Input Validation
 *
 * Tests verify that the logout endpoint properly validates input parameters.
 *
 * @package MeshResearch\CILogon\Tests
 */

namespace MeshResearch\CILogon\Tests;

use MeshResearch\CILogon\Plugin;
use PHPUnit\Framework\TestCase;
use WP_REST_Request;

/**
 * Test suite for Logout Input Validation
 *
 * This test suite ensures that the Plugin::logout() method properly validates
 * and sanitizes input parameters to prevent security issues.
 */
class LogoutInputValidationTest extends TestCase
{
    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Tear down after each test
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Helper to create a mock request with optional username parameter
     *
     * @param string|null $username The username parameter, or null for no parameter
     * @return WP_REST_Request
     */
    private function createRequest(?string $username = null): WP_REST_Request
    {
        $request = new WP_REST_Request('GET', '/idms/logout');
        if ($username !== null) {
            $request->set_param('username', $username);
        }
        return $request;
    }

    // ========================================================================
    // MISSING PARAMETER TESTS
    // ========================================================================

    /**
     * Test: Missing username parameter returns false
     *
     * @test
     */
    public function test_missing_username_returns_false()
    {
        $request = $this->createRequest(null);

        $result = Plugin::logout($request);

        $this->assertFalse($result, 'Logout should fail when username is missing');
    }

    /**
     * Test: Empty username parameter returns false
     *
     * @test
     */
    public function test_empty_username_returns_false()
    {
        $request = $this->createRequest('');

        $result = Plugin::logout($request);

        $this->assertFalse($result, 'Logout should fail when username is empty');
    }

    /**
     * Test: Whitespace-only username returns false
     *
     * @test
     */
    public function test_whitespace_username_returns_false()
    {
        $request = $this->createRequest('   ');

        $result = Plugin::logout($request);

        // After sanitize_user, whitespace becomes empty
        $this->assertFalse($result, 'Logout should fail when username is only whitespace');
    }

    // ========================================================================
    // NON-EXISTENT USER TESTS
    // ========================================================================

    /**
     * Test: Non-existent user returns false
     *
     * @test
     */
    public function test_nonexistent_user_returns_false()
    {
        $request = $this->createRequest('nonexistent_user_12345');

        $result = Plugin::logout($request);

        $this->assertFalse($result, 'Logout should fail for non-existent user');
    }

    /**
     * Test: Valid-looking but non-existent username returns false
     *
     * @test
     */
    public function test_valid_format_nonexistent_user_returns_false()
    {
        $request = $this->createRequest('john.doe');

        $result = Plugin::logout($request);

        $this->assertFalse($result, 'Logout should fail for non-existent user even with valid format');
    }

    // ========================================================================
    // SANITIZATION TESTS
    // ========================================================================

    /**
     * Test: Username with special characters is sanitized
     *
     * @test
     */
    public function test_username_with_special_chars_is_sanitized()
    {
        // Username with characters that should be stripped
        $request = $this->createRequest('user<script>alert(1)</script>');

        $result = Plugin::logout($request);

        // Should fail because user doesn't exist, but shouldn't cause errors
        $this->assertFalse($result);
    }

    /**
     * Test: Username with SQL injection attempt is handled safely
     *
     * @test
     */
    public function test_sql_injection_attempt_is_handled()
    {
        $request = $this->createRequest("admin'; DROP TABLE users; --");

        $result = Plugin::logout($request);

        // Should fail safely without causing database issues
        $this->assertFalse($result);
    }

    /**
     * Test: Username with null bytes is handled safely
     *
     * @test
     */
    public function test_null_byte_injection_is_handled()
    {
        $request = $this->createRequest("admin\x00malicious");

        $result = Plugin::logout($request);

        // Should fail safely
        $this->assertFalse($result);
    }

    /**
     * Test: Very long username is handled safely
     *
     * @test
     */
    public function test_very_long_username_is_handled()
    {
        $longUsername = str_repeat('a', 10000);
        $request = $this->createRequest($longUsername);

        $result = Plugin::logout($request);

        // Should fail safely without causing buffer issues
        $this->assertFalse($result);
    }

    /**
     * Test: Unicode username is handled
     *
     * @test
     */
    public function test_unicode_username_is_handled()
    {
        $request = $this->createRequest('用户名');

        $result = Plugin::logout($request);

        // Should fail (user doesn't exist) but handle unicode safely
        $this->assertFalse($result);
    }

    // ========================================================================
    // RETURN TYPE TESTS
    // ========================================================================

    /**
     * Test: Return type is boolean for missing username
     *
     * @test
     */
    public function test_return_type_is_boolean_for_missing_username()
    {
        $request = $this->createRequest(null);

        $result = Plugin::logout($request);

        $this->assertIsBool($result);
    }

    /**
     * Test: Return type is boolean for non-existent user
     *
     * @test
     */
    public function test_return_type_is_boolean_for_nonexistent_user()
    {
        $request = $this->createRequest('nonexistent');

        $result = Plugin::logout($request);

        $this->assertIsBool($result);
    }
}
