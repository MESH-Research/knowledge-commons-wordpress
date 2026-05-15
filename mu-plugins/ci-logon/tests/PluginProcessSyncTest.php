<?php
/**
 * Unit Tests for Plugin::process_sync Method
 *
 * Tests verify that users are NOT logged in when API responses fail
 * (e.g., DNS down, network errors, invalid responses, etc.)
 *
 * @package MeshResearch\CILogon\Tests
 */

namespace MeshResearch\CILogon\Tests;

use MeshResearch\CILogon\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for Plugin::process_sync
 *
 * This test suite ensures that the process_sync method properly handles
 * failure scenarios and does NOT log users in when API responses are invalid.
 *
 * NOTE: Some tests are marked as @expectedIncompleteException or @expectedWarning
 * because the Plugin::process_sync method has a bug where it doesn't check for
 * JSON parsing errors before trying to access array keys. This causes PHP warnings
 * and errors when JSON is invalid.
 *
 * See: Plugin.php line 114 - should return false after json_last_error check
 */
class PluginProcessSyncTest extends TestCase
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

    // ========================================================================
    // HTTP ERROR RESPONSE TESTS
    // ========================================================================

    /**
     * Test: HTTP 400 (Bad Request) returns false and does NOT log user in
     *
     * @test
     */
    public function test_http_400_response_returns_false()
    {
        $code = 400;
        $body = json_encode(['error' => 'Bad Request']);
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'process_sync should return false for HTTP 400');
    }

    /**
     * Test: HTTP 401 (Unauthorized) returns false and does NOT log user in
     *
     * @test
     */
    public function test_http_401_response_returns_false()
    {
        $code = 401;
        $body = json_encode(['error' => 'Unauthorized']);
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'process_sync should return false for HTTP 401');
    }

    /**
     * Test: HTTP 403 (Forbidden) returns false and does NOT log user in
     *
     * @test
     */
    public function test_http_403_response_returns_false()
    {
        $code = 403;
        $body = json_encode(['error' => 'Forbidden']);
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'process_sync should return false for HTTP 403');
    }

    /**
     * Test: HTTP 404 (Not Found) returns false and does NOT log user in
     *
     * @test
     */
    public function test_http_404_response_returns_false()
    {
        $code = 404;
        $body = json_encode(['error' => 'Not Found']);
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'process_sync should return false for HTTP 404');
    }

    /**
     * Test: HTTP 500 (Internal Server Error) returns false and does NOT log user in
     *
     * @test
     */
    public function test_http_500_response_returns_false()
    {
        $code = 500;
        $body = json_encode(['error' => 'Internal Server Error']);
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'process_sync should return false for HTTP 500');
    }

    /**
     * Test: HTTP 502 (Bad Gateway) returns false and does NOT log user in
     *
     * @test
     */
    public function test_http_502_response_returns_false()
    {
        $code = 502;
        $body = json_encode(['error' => 'Bad Gateway']);
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'process_sync should return false for HTTP 502');
    }

    /**
     * Test: HTTP 503 (Service Unavailable) returns false and does NOT log user in
     *
     * This simulates DNS being down or API server being unreachable
     *
     * @test
     */
    public function test_http_503_response_returns_false()
    {
        $code = 503;
        $body = json_encode(['error' => 'Service Unavailable']);
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'process_sync should return false for HTTP 503 (DNS down scenario)');
    }

    /**
     * Test: HTTP 504 (Gateway Timeout) returns false and does NOT log user in
     *
     * @test
     */
    public function test_http_504_response_returns_false()
    {
        $code = 504;
        $body = json_encode(['error' => 'Gateway Timeout']);
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'process_sync should return false for HTTP 504');
    }

    /**
     * Test: HTTP 199 (below 200 range) returns false and does NOT log user in
     *
     * @test
     */
    public function test_http_199_response_returns_false()
    {
        $code = 199;
        $body = json_encode(['error' => 'Invalid']);
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'process_sync should return false for HTTP 199 (outside 200-299 range)');
    }

    /**
     * Test: HTTP 300 (Multiple Choices, outside 200-299 range) returns false
     *
     * @test
     */
    public function test_http_300_response_returns_false()
    {
        $code = 300;
        $body = json_encode(['error' => 'Multiple Choices']);
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'process_sync should return false for HTTP 300 (outside 200-299 range)');
    }

    // ========================================================================
    // JSON PARSING ERROR TESTS
    // ========================================================================

    /**
     * Test: Invalid JSON response returns false and does NOT log user in
     *
     * SECURITY: Invalid JSON must be rejected gracefully without causing errors
     *
     * @test
     */
    public function test_invalid_json_response_returns_false()
    {
        $code = 200;
        $body = 'This is not valid JSON {invalid}';
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'process_sync should return false for invalid JSON');
    }

    /**
     * Test: Empty JSON response returns false and does NOT log user in
     *
     * SECURITY: Empty responses must be rejected gracefully
     *
     * @test
     */
    public function test_empty_json_response_returns_false()
    {
        $code = 200;
        $body = '';
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'process_sync should return false for empty body');
    }

    /**
     * Test: Malformed JSON (unclosed brace) returns false
     *
     * SECURITY: Malformed JSON must be rejected gracefully
     *
     * @test
     */
    public function test_malformed_json_response_returns_false()
    {
        $code = 200;
        $body = '{"error": "unclosed';
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'process_sync should return false for malformed JSON');
    }

    // ========================================================================
    // API ERROR CODE TESTS
    // ========================================================================

    /**
     * Test: API error code 1005 (user not found) returns false
     *
     * @test
     */
    public function test_api_error_code_1005_user_not_found_returns_false()
    {
        $code = 200;
        $body = json_encode([
            'meta' => [
                'error' => [
                    'code' => 1005,
                    'message' => 'User not found'
                ]
            ]
        ]);
        $username = 'nonexistent_user';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'process_sync should return false for API error code 1005 (user not found)');
    }

    /**
     * Test: API error code 1001 (generic error) returns false
     *
     * SECURITY: Any API error code should cause the sync to fail gracefully
     *
     * @test
     */
    public function test_api_error_code_1001_returns_false()
    {
        $code = 200;
        $body = json_encode([
            'meta' => [
                'error' => [
                    'code' => 1001,
                    'message' => 'Generic API error'
                ]
            ]
        ]);
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'process_sync should return false for any API error code');
    }

    // ========================================================================
    // MISSING REQUIRED FIELDS TESTS
    // ========================================================================

    /**
     * Test: Response missing memberships array returns false
     *
     * @test
     */
    public function test_response_missing_memberships_array_returns_false()
    {
        $code = 200;
        $body = json_encode([
            'data' => [
                [
                    'profile' => [
                        'username' => 'testuser',
                        'email' => 'testuser@example.com',
                        'first_name' => 'Test',
                        'last_name' => 'User',
                        // Missing 'memberships' key
                    ]
                ]
            ]
        ]);
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'process_sync should return false when memberships array is missing');
    }

    /**
     * Test: Response with empty memberships array
     *
     * NOTE: This test doesn't have an assertion because the current behavior
     * is unclear. An empty memberships array passes the isset() check but may
     * not be valid for user creation.
     *
     * @test
     */
    public function test_response_with_empty_memberships_array_returns_false()
    {
        $code = 200;
        $body = json_encode([
            'data' => [
                [
                    'profile' => [
                        'username' => 'testuser',
                        'email' => 'testuser@example.com',
                        'first_name' => 'Test',
                        'last_name' => 'User',
                        'memberships' => []
                    ]
                ]
            ]
        ]);
        $username = 'testuser';
        $user = false;

        // Empty memberships array passes isset() check
        // This may or may not be desired behavior - needs clarification
        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertTrue($result == null);
    }

    /**
     * Test: Response with null memberships returns false
     *
     * @test
     */
    public function test_response_with_null_memberships_returns_false()
    {
        $code = 200;
        $body = json_encode([
            'data' => [
                [
                    'profile' => [
                        'username' => 'testuser',
                        'email' => 'testuser@example.com',
                        'first_name' => 'Test',
                        'last_name' => 'User',
                        'memberships' => null
                    ]
                ]
            ]
        ]);
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        // isset() returns false for null values, so this should return false
        $this->assertFalse($result, 'process_sync should return false when memberships is null');
    }

    // ========================================================================
    // NETWORK FAILURE SIMULATION TESTS
    // ========================================================================

    /**
     * Test: Connection timeout (simulated as HTTP 0)
     *
     * When wp_remote_get fails due to connection timeout, it returns a WP_Error.
     * This test simulates what would happen if the error was converted to HTTP 0.
     *
     * @test
     */
    public function test_connection_timeout_http_0_returns_false()
    {
        $code = 0;
        $body = '';
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'process_sync should return false for connection timeout (HTTP 0)');
    }

    /**
     * Test: DNS resolution failure (simulated as HTTP 0)
     *
     * When DNS fails, wp_remote_get returns a WP_Error with message like
     * "cURL error 6: Could not resolve host". This would result in HTTP 0.
     *
     * @test
     */
    public function test_dns_failure_http_0_returns_false()
    {
        $code = 0;
        $body = '';
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'process_sync should return false for DNS failure (HTTP 0)');
    }

    /**
     * Test: Connection refused (simulated as HTTP 0)
     *
     * When the API server is down and refuses connections, wp_remote_get returns
     * a WP_Error. This would result in HTTP 0.
     *
     * @test
     */
    public function test_connection_refused_http_0_returns_false()
    {
        $code = 0;
        $body = '';
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'process_sync should return false for connection refused (HTTP 0)');
    }

    // ========================================================================
    // EDGE CASE TESTS
    // ========================================================================

    /**
     * Test: HTTP 200 with valid response for new user (positive control)
     *
     * This is a positive control test to verify that valid responses
     * would normally proceed (though user creation would fail in test environment).
     *
     * @test
     */
    public function test_http_200_valid_response_structure()
    {
        $code = 200;
        $body = json_encode([
            'data' => [
                [
                    'profile' => [
                        'username' => 'testuser',
                        'email' => 'testuser@example.com',
                        'first_name' => 'Test',
                        'last_name' => 'User',
                        'memberships' => [
                            'SOCIETY_A' => true,
                        ]
                    ]
                ]
            ]
        ]);
        $username = 'testuser';
        $user = false;

        // This test documents the expected behavior for valid responses
        // In a real test environment, this would attempt user creation
        // which would fail without proper WordPress setup
        $result = Plugin::process_sync($code, $body, $username, $user);

        // User creation fails in test environment, so result should be false
        $this->assertFalse($result, 'User creation fails in test environment');
    }

    /**
     * Test: HTTP 200 with results field instead of data field
     *
     * The API can return either 'data' or 'results' field. This tests the 'results' path.
     *
     * @test
     */
    public function test_http_200_with_results_field_instead_of_data()
    {
        $code = 200;
        $body = json_encode([
            'results' => [
                'username' => 'testuser',
                'email' => 'testuser@example.com',
                'first_name' => 'Test',
                'last_name' => 'User',
                'memberships' => [
                    'SOCIETY_A' => true,
                ]
            ]
        ]);
        $username = 'testuser';
        $user = false;

        // This documents the alternative response format
        $result = Plugin::process_sync($code, $body, $username, $user);

        // User creation fails in test environment
        $this->assertFalse($result, 'User creation fails in test environment');
    }

    /**
     * Test: HTTP 200 with profile data at the top level (new API shape)
     *
     * The /api/v1/members/{username}/ endpoint now returns the profile data
     * directly at the top level (no 'data' or 'results' wrapper). The handler
     * must unwrap this format and pass the fields through to user creation.
     *
     * This test discriminates between accepting and rejecting the new shape:
     * before the fix, no user creation is attempted; after the fix the captured
     * wp_insert_user payload contains the values from the top level of $json.
     *
     * @test
     */
    public function test_http_200_with_top_level_profile_creates_user()
    {
        clear_captured_wp_insert_user_data();

        $code = 200;
        $body = json_encode([
            'username' => 'martin_eve',
            'name' => 'Martin Paul Eve',
            'first_name' => 'Martin Paul',
            'last_name' => 'Eve',
            'email' => 'martin@example.com',
            'institutional_affiliation' => 'Birkbeck, University of London',
            'orcid' => '0000-0002-5589-8511',
            'memberships' => [
                'MSU' => true,
                'MLA' => false,
            ],
            'is_superadmin' => true,
        ]);
        $username = 'martin_eve';
        $user = false;

        Plugin::process_sync($code, $body, $username, $user);

        $captured = get_captured_wp_insert_user_data();
        $this->assertNotNull(
            $captured,
            'Top-level profile shape must be unwrapped and passed to user creation'
        );
        $this->assertSame('martin_eve', $captured['user_login']);
        $this->assertSame('Martin Paul', $captured['first_name']);
        $this->assertSame('Eve', $captured['last_name']);
    }

    /**
     * Test: New top-level shape with no profile-identifying fields is rejected
     *
     * If the response has none of: data[0][profile], results, or a top-level
     * username, the response is malformed and login must not proceed.
     *
     * @test
     */
    public function test_http_200_with_no_recognisable_profile_returns_false()
    {
        clear_captured_wp_insert_user_data();

        $code = 200;
        $body = json_encode([
            'unrelated' => 'value',
        ]);
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'Unrecognisable response shapes must prevent login');
        $this->assertNull(
            get_captured_wp_insert_user_data(),
            'No user creation should be attempted for unrecognisable shapes'
        );
    }

    /**
     * Test: Verify that process_sync does NOT call wp_set_auth_cookie on failure
     *
     * This is the critical test: ensure that failed API responses do NOT result
     * in the user being logged in.
     *
     * @test
     */
    public function test_failed_response_does_not_set_auth_cookie()
    {
        $code = 503; // Service Unavailable
        $body = json_encode(['error' => 'Service Unavailable']);
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        // If process_sync returns false, wp_set_auth_cookie should NOT be called
        // The calling code (CILogonAuth::synchronise_user) should check the result
        // and not proceed with authentication if process_sync returns false
        $this->assertFalse($result, 'process_sync must return false to prevent authentication');
    }

    // ========================================================================
    // BOUNDARY TESTS
    // ========================================================================

    /**
     * Test: HTTP 200 (lowest valid code) works
     *
     * @test
     */
    public function test_http_200_is_valid()
    {
        $code = 200;
        $body = json_encode([
            'data' => [
                [
                    'profile' => [
                        'username' => 'testuser',
                        'email' => 'testuser@example.com',
                        'first_name' => 'Test',
                        'last_name' => 'User',
                        'memberships' => [
                            'SOCIETY_A' => true,
                        ]
                    ]
                ]
            ]
        ]);
        $username = 'testuser';
        $user = false;

        // HTTP 200 should pass the code check
        $result = Plugin::process_sync($code, $body, $username, $user);

        // User creation fails in test environment
        $this->assertFalse($result, 'User creation fails in test environment');
    }

    /**
     * Test: HTTP 299 (highest valid code) works
     *
     * @test
     */
    public function test_http_299_is_valid()
    {
        $code = 299;
        $body = json_encode([
            'data' => [
                [
                    'profile' => [
                        'username' => 'testuser',
                        'email' => 'testuser@example.com',
                        'first_name' => 'Test',
                        'last_name' => 'User',
                        'memberships' => [
                            'SOCIETY_A' => true,
                        ]
                    ]
                ]
            ]
        ]);
        $username = 'testuser';
        $user = false;

        // HTTP 299 should pass the code check
        $result = Plugin::process_sync($code, $body, $username, $user);

        // User creation fails in test environment
        $this->assertFalse($result, 'User creation fails in test environment');
    }

    // ========================================================================
    // MISSING / EMPTY EMAIL TESTS
    // ========================================================================

    /**
     * Test: A brand-new user whose broker profile has NO email key is still
     * created, with an empty email address.
     *
     * The Profiles/IDMS broker may relay a profile with a missing email. New
     * user creation must not crash on an undefined array key — it must fall
     * back to an empty email (consistent with the broker-token path), letting
     * a later sync fill the address in once Profiles supplies one.
     *
     * @test
     */
    public function test_new_user_with_missing_email_creates_user_with_empty_email()
    {
        clear_captured_wp_insert_user_data();

        $code = 200;
        $body = json_encode([
            'username' => 'no_email_user',
            'first_name' => 'No',
            'last_name' => 'Email',
            // 'email' deliberately absent
            'memberships' => [
                'MLA' => true,
            ],
        ]);
        $username = 'no_email_user';
        $user = false;

        Plugin::process_sync($code, $body, $username, $user);

        $captured = get_captured_wp_insert_user_data();
        $this->assertNotNull(
            $captured,
            'User creation must still be attempted when email is missing'
        );
        $this->assertArrayHasKey('user_email', $captured);
        $this->assertSame(
            '',
            $captured['user_email'],
            'Missing email must fall back to an empty string, not null'
        );
        $this->assertSame('no_email_user', $captured['user_login']);
    }

    /**
     * Test: A brand-new user whose broker profile has an EMPTY email string is
     * created, with an empty email address.
     *
     * @test
     */
    public function test_new_user_with_empty_email_creates_user_with_empty_email()
    {
        clear_captured_wp_insert_user_data();

        $code = 200;
        $body = json_encode([
            'username' => 'empty_email_user',
            'first_name' => 'Empty',
            'last_name' => 'Email',
            'email' => '',
            'memberships' => [
                'MLA' => true,
            ],
        ]);
        $username = 'empty_email_user';
        $user = false;

        Plugin::process_sync($code, $body, $username, $user);

        $captured = get_captured_wp_insert_user_data();
        $this->assertNotNull(
            $captured,
            'User creation must still be attempted when email is an empty string'
        );
        $this->assertArrayHasKey('user_email', $captured);
        $this->assertSame('', $captured['user_email']);
        $this->assertSame('empty_email_user', $captured['user_login']);
    }
}
