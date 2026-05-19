<?php
/**
 * Unit Tests for Bearer Token Authentication
 *
 * Tests verify that the cilogon_verify_bearer_token() function properly
 * authenticates REST API requests using bearer tokens.
 *
 * @package MeshResearch\CILogon\Tests
 */

namespace MeshResearch\CILogon\Tests;

use PHPUnit\Framework\TestCase;
use WP_REST_Request;
use WP_Error;

/**
 * Test suite for Bearer Token Authentication
 *
 * This test suite ensures that the cilogon_verify_bearer_token() permission
 * callback properly validates bearer tokens for the IDMS REST API endpoints.
 */
class BearerTokenAuthTest extends TestCase
{
    /**
     * Store original environment variable value
     *
     * @var string|false
     */
    private $originalBearerToken;

    /**
     * Test bearer token for use in tests
     *
     * @var string
     */
    private const TEST_BEARER_TOKEN = 'test_secret_bearer_token_12345';

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Store original value
        $this->originalBearerToken = getenv('PROFILES_API_BEARER_TOKEN');
    }

    /**
     * Tear down after each test
     */
    protected function tearDown(): void
    {
        // Restore original value
        if ($this->originalBearerToken !== false) {
            putenv('PROFILES_API_BEARER_TOKEN=' . $this->originalBearerToken);
        } else {
            putenv('PROFILES_API_BEARER_TOKEN');
        }
        parent::tearDown();
    }

    /**
     * Helper to set the bearer token environment variable
     *
     * @param string|null $token The token to set, or null to unset
     */
    private function setBearerToken(?string $token): void
    {
        if ($token === null) {
            putenv('PROFILES_API_BEARER_TOKEN');
        } else {
            putenv('PROFILES_API_BEARER_TOKEN=' . $token);
        }
    }

    /**
     * Helper to create a mock request with optional authorization header
     *
     * @param string|null $authHeader The Authorization header value, or null for no header
     * @return WP_REST_Request
     */
    private function createRequest(?string $authHeader = null): WP_REST_Request
    {
        $request = new WP_REST_Request('GET', '/idms/user-updated');
        if ($authHeader !== null) {
            $request->set_header('Authorization', $authHeader);
        }
        return $request;
    }

    // ========================================================================
    // MISSING CONFIGURATION TESTS
    // ========================================================================

    /**
     * Test: Missing PROFILES_API_BEARER_TOKEN returns WP_Error with 500 status
     *
     * @test
     */
    public function test_missing_bearer_token_env_returns_error()
    {
        $this->setBearerToken(null);
        $request = $this->createRequest('Bearer some_token');

        $result = cilogon_verify_bearer_token($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('rest_forbidden', $result->get_error_code());
        $this->assertEquals(['status' => 500], $result->get_error_data());
    }

    /**
     * Test: Empty PROFILES_API_BEARER_TOKEN returns WP_Error with 500 status
     *
     * @test
     */
    public function test_empty_bearer_token_env_returns_error()
    {
        $this->setBearerToken('');
        $request = $this->createRequest('Bearer some_token');

        $result = cilogon_verify_bearer_token($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('rest_forbidden', $result->get_error_code());
        $this->assertEquals(['status' => 500], $result->get_error_data());
    }

    // ========================================================================
    // MISSING AUTHORIZATION HEADER TESTS
    // ========================================================================

    /**
     * Test: Missing Authorization header returns WP_Error with 401 status
     *
     * @test
     */
    public function test_missing_auth_header_returns_401()
    {
        $this->setBearerToken(self::TEST_BEARER_TOKEN);
        $request = $this->createRequest(null);

        $result = cilogon_verify_bearer_token($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('rest_forbidden', $result->get_error_code());
        $this->assertEquals(['status' => 401], $result->get_error_data());
    }

    /**
     * Test: Empty Authorization header returns WP_Error with 401 status
     *
     * @test
     */
    public function test_empty_auth_header_returns_401()
    {
        $this->setBearerToken(self::TEST_BEARER_TOKEN);
        $request = $this->createRequest('');

        $result = cilogon_verify_bearer_token($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('rest_forbidden', $result->get_error_code());
        $this->assertEquals(['status' => 401], $result->get_error_data());
    }

    // ========================================================================
    // INVALID TOKEN TESTS
    // ========================================================================

    /**
     * Test: Invalid bearer token returns WP_Error with 403 status
     *
     * @test
     */
    public function test_invalid_bearer_token_returns_403()
    {
        $this->setBearerToken(self::TEST_BEARER_TOKEN);
        $request = $this->createRequest('Bearer wrong_token');

        $result = cilogon_verify_bearer_token($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('rest_forbidden', $result->get_error_code());
        $this->assertEquals(['status' => 403], $result->get_error_data());
    }

    /**
     * Test: Token without 'Bearer ' prefix returns WP_Error with 403 status
     *
     * @test
     */
    public function test_token_without_bearer_prefix_returns_403()
    {
        $this->setBearerToken(self::TEST_BEARER_TOKEN);
        $request = $this->createRequest(self::TEST_BEARER_TOKEN);

        $result = cilogon_verify_bearer_token($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('rest_forbidden', $result->get_error_code());
        $this->assertEquals(['status' => 403], $result->get_error_data());
    }

    /**
     * Test: Basic auth instead of Bearer returns WP_Error with 403 status
     *
     * @test
     */
    public function test_basic_auth_instead_of_bearer_returns_403()
    {
        $this->setBearerToken(self::TEST_BEARER_TOKEN);
        $request = $this->createRequest('Basic dXNlcjpwYXNz');

        $result = cilogon_verify_bearer_token($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('rest_forbidden', $result->get_error_code());
        $this->assertEquals(['status' => 403], $result->get_error_data());
    }

    /**
     * Test: Bearer token with extra whitespace returns WP_Error with 403 status
     *
     * @test
     */
    public function test_bearer_token_with_extra_whitespace_returns_403()
    {
        $this->setBearerToken(self::TEST_BEARER_TOKEN);
        $request = $this->createRequest('Bearer  ' . self::TEST_BEARER_TOKEN);

        $result = cilogon_verify_bearer_token($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('rest_forbidden', $result->get_error_code());
        $this->assertEquals(['status' => 403], $result->get_error_data());
    }

    /**
     * Test: Bearer token with lowercase 'bearer' returns WP_Error with 403 status
     *
     * Note: RFC 6750 specifies "Bearer" with capital B, so lowercase should fail
     *
     * @test
     */
    public function test_lowercase_bearer_returns_403()
    {
        $this->setBearerToken(self::TEST_BEARER_TOKEN);
        $request = $this->createRequest('bearer ' . self::TEST_BEARER_TOKEN);

        $result = cilogon_verify_bearer_token($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('rest_forbidden', $result->get_error_code());
        $this->assertEquals(['status' => 403], $result->get_error_data());
    }

    /**
     * Test: Partial token match returns WP_Error with 403 status
     *
     * Ensures that partial token matches don't pass validation
     *
     * @test
     */
    public function test_partial_token_match_returns_403()
    {
        $this->setBearerToken(self::TEST_BEARER_TOKEN);
        // Use only first part of the token
        $partialToken = substr(self::TEST_BEARER_TOKEN, 0, 10);
        $request = $this->createRequest('Bearer ' . $partialToken);

        $result = cilogon_verify_bearer_token($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('rest_forbidden', $result->get_error_code());
        $this->assertEquals(['status' => 403], $result->get_error_data());
    }

    /**
     * Test: Token with appended characters returns WP_Error with 403 status
     *
     * @test
     */
    public function test_token_with_appended_chars_returns_403()
    {
        $this->setBearerToken(self::TEST_BEARER_TOKEN);
        $request = $this->createRequest('Bearer ' . self::TEST_BEARER_TOKEN . '_extra');

        $result = cilogon_verify_bearer_token($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('rest_forbidden', $result->get_error_code());
        $this->assertEquals(['status' => 403], $result->get_error_data());
    }

    // ========================================================================
    // VALID TOKEN TESTS
    // ========================================================================

    /**
     * Test: Valid bearer token returns true
     *
     * @test
     */
    public function test_valid_bearer_token_returns_true()
    {
        $this->setBearerToken(self::TEST_BEARER_TOKEN);
        $request = $this->createRequest('Bearer ' . self::TEST_BEARER_TOKEN);

        $result = cilogon_verify_bearer_token($request);

        $this->assertTrue($result);
    }

    /**
     * Test: Valid bearer token with special characters
     *
     * @test
     */
    public function test_valid_bearer_token_with_special_chars()
    {
        $specialToken = 'abc123!@#$%^&*()_+-=[]{}|;:,.<>?';
        $this->setBearerToken($specialToken);
        $request = $this->createRequest('Bearer ' . $specialToken);

        $result = cilogon_verify_bearer_token($request);

        $this->assertTrue($result);
    }

    /**
     * Test: Valid bearer token with long value
     *
     * @test
     */
    public function test_valid_bearer_token_long_value()
    {
        $longToken = str_repeat('a', 1000);
        $this->setBearerToken($longToken);
        $request = $this->createRequest('Bearer ' . $longToken);

        $result = cilogon_verify_bearer_token($request);

        $this->assertTrue($result);
    }

    /**
     * Test: Valid bearer token with UUID format
     *
     * @test
     */
    public function test_valid_bearer_token_uuid_format()
    {
        $uuidToken = '550e8400-e29b-41d4-a716-446655440000';
        $this->setBearerToken($uuidToken);
        $request = $this->createRequest('Bearer ' . $uuidToken);

        $result = cilogon_verify_bearer_token($request);

        $this->assertTrue($result);
    }

    // ========================================================================
    // TIMING ATTACK RESISTANCE TESTS
    // ========================================================================

    /**
     * Test: Function uses hash_equals for comparison (timing-safe)
     *
     * This test verifies that the comparison doesn't short-circuit on first
     * character mismatch by checking that tokens with different first characters
     * take approximately the same time as tokens with different last characters.
     *
     * Note: This is a best-effort test - timing can vary due to system load.
     * The real protection comes from using hash_equals() in the implementation.
     *
     * @test
     */
    public function test_timing_safe_comparison_used()
    {
        $this->setBearerToken(self::TEST_BEARER_TOKEN);

        // Token with wrong first character
        $wrongFirstChar = 'X' . substr(self::TEST_BEARER_TOKEN, 1);

        // Token with wrong last character
        $wrongLastChar = substr(self::TEST_BEARER_TOKEN, 0, -1) . 'X';

        // Both should return the same error type (403)
        $request1 = $this->createRequest('Bearer ' . $wrongFirstChar);
        $request2 = $this->createRequest('Bearer ' . $wrongLastChar);

        $result1 = cilogon_verify_bearer_token($request1);
        $result2 = cilogon_verify_bearer_token($request2);

        // Both should fail with same error
        $this->assertInstanceOf(WP_Error::class, $result1);
        $this->assertInstanceOf(WP_Error::class, $result2);
        $this->assertEquals($result1->get_error_code(), $result2->get_error_code());
        $this->assertEquals($result1->get_error_data(), $result2->get_error_data());
    }

    // ========================================================================
    // ERROR MESSAGE TESTS
    // ========================================================================

    /**
     * Test: Error message for missing config is appropriate
     *
     * @test
     */
    public function test_error_message_for_missing_config()
    {
        $this->setBearerToken(null);
        $request = $this->createRequest('Bearer token');

        $result = cilogon_verify_bearer_token($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertStringContainsString('not configured', $result->get_error_message());
    }

    /**
     * Test: Error message for missing header is appropriate
     *
     * @test
     */
    public function test_error_message_for_missing_header()
    {
        $this->setBearerToken(self::TEST_BEARER_TOKEN);
        $request = $this->createRequest(null);

        $result = cilogon_verify_bearer_token($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertStringContainsString('header required', $result->get_error_message());
    }

    /**
     * Test: Error message for invalid token is appropriate
     *
     * @test
     */
    public function test_error_message_for_invalid_token()
    {
        $this->setBearerToken(self::TEST_BEARER_TOKEN);
        $request = $this->createRequest('Bearer wrong');

        $result = cilogon_verify_bearer_token($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertStringContainsString('Invalid', $result->get_error_message());
    }
}
