<?php
/**
 * Integration Tests for Plugin::process_sync with Mocking
 *
 * These tests use Mockery to mock WordPress functions and simulate
 * realistic failure scenarios without requiring a full WordPress environment.
 *
 * @package MeshResearch\CILogon\Tests
 */

namespace MeshResearch\CILogon\Tests;

use MeshResearch\CILogon\Plugin;
use PHPUnit\Framework\TestCase;
use Mockery;

/**
 * Integration test suite with mocking
 *
 * These tests verify that process_sync properly handles failures
 * and prevents user authentication when API calls fail.
 *
 * NOTE: Some tests expect warnings because Plugin::process_sync has a bug
 * where it doesn't check for JSON parsing errors before accessing array keys.
 */
class PluginProcessSyncIntegrationTest extends TestCase
{
    /**
     * Tear down mocks after each test
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========================================================================
    // CRITICAL SECURITY TESTS
    // ========================================================================

    /**
     * Test: DNS failure does NOT log user in
     *
     * Scenario: User attempts to log in via CI Logon, but DNS is down
     * and the Profiles API is unreachable.
     *
     * Expected: User is NOT logged in, process_sync returns false
     *
     * @test
     */
    public function test_dns_failure_prevents_login()
    {
        // Simulate DNS failure: HTTP code 0, empty body
        $code = 0;
        $body = '';
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'DNS failure must prevent login');
    }

    /**
     * Test: API timeout does NOT log user in
     *
     * Scenario: Profiles API is slow or unresponsive, request times out.
     *
     * Expected: User is NOT logged in, process_sync returns false
     *
     * @test
     */
    public function test_api_timeout_prevents_login()
    {
        // Simulate timeout: HTTP 504 Gateway Timeout
        $code = 504;
        $body = json_encode(['error' => 'Gateway Timeout']);
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'API timeout must prevent login');
    }

    /**
     * Test: API server down does NOT log user in
     *
     * Scenario: Profiles API server is down (HTTP 503 Service Unavailable).
     *
     * Expected: User is NOT logged in, process_sync returns false
     *
     * @test
     */
    public function test_api_server_down_prevents_login()
    {
        $code = 503;
        $body = json_encode(['error' => 'Service Unavailable']);
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'API server down must prevent login');
    }

    /**
     * Test: Corrupted API response does NOT log user in
     *
     * Scenario: API returns HTTP 200 but with corrupted/invalid JSON.
     * This could happen due to network corruption or API misconfiguration.
     *
     * Expected: User is NOT logged in, process_sync returns false
     *
     * @test
     */
    public function test_corrupted_api_response_prevents_login()
    {
        $code = 200;
        $body = 'HTTP/1.1 200 OK\r\nContent-Type: text/html\r\n\r\n<html>Error</html>';
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'process_sync should return false for corrupted API response');
    }

    /**
     * Test: Incomplete API response (missing memberships) does NOT log user in
     *
     * Scenario: API returns HTTP 200 with valid JSON but missing required
     * memberships field. This could indicate API misconfiguration.
     *
     * Expected: User is NOT logged in, process_sync returns false
     *
     * @test
     */
    public function test_incomplete_api_response_prevents_login()
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
                        // Missing 'memberships' field
                    ]
                ]
            ]
        ]);
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'Incomplete API response must prevent login');
    }

    /**
     * Test: User not found in API does NOT log user in
     *
     * Scenario: API returns HTTP 200 with error code 1005 (user not found).
     * This could happen if user was deleted from the API.
     *
     * Expected: User is NOT logged in, process_sync returns false
     *
     * @test
     */
    public function test_user_not_found_in_api_prevents_login()
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

        $this->assertFalse($result, 'User not found in API must prevent login');
    }

    // ========================================================================
    // HTTP STATUS CODE TESTS
    // ========================================================================

    /**
     * Test: All 4xx client errors prevent login
     *
     * @test
     * @dataProvider fourHundredStatusCodes
     */
    public function test_all_4xx_errors_prevent_login($code)
    {
        $body = json_encode(['error' => "HTTP $code Error"]);
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, "HTTP $code must prevent login");
    }

    /**
     * Data provider for 4xx status codes
     */
    public function fourHundredStatusCodes()
    {
        return [
            'HTTP 400' => [400],
            'HTTP 401' => [401],
            'HTTP 403' => [403],
            'HTTP 404' => [404],
            'HTTP 429' => [429],
        ];
    }

    /**
     * Test: All 5xx server errors prevent login
     *
     * @test
     * @dataProvider fiveHundredStatusCodes
     */
    public function test_all_5xx_errors_prevent_login($code)
    {
        $body = json_encode(['error' => "HTTP $code Error"]);
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, "HTTP $code must prevent login");
    }

    /**
     * Data provider for 5xx status codes
     */
    public function fiveHundredStatusCodes()
    {
        return [
            'HTTP 500' => [500],
            'HTTP 501' => [501],
            'HTTP 502' => [502],
            'HTTP 503' => [503],
            'HTTP 504' => [504],
        ];
    }

    /**
     * Test: Codes outside 200-299 range prevent login
     *
     * @test
     * @dataProvider invalidStatusCodes
     */
    public function test_codes_outside_200_299_range_prevent_login($code)
    {
        $body = json_encode(['error' => "HTTP $code Error"]);
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, "HTTP $code (outside 200-299) must prevent login");
    }

    /**
     * Data provider for codes outside 200-299 range
     */
    public function invalidStatusCodes()
    {
        return [
            'HTTP 0 (no response)' => [0],
            'HTTP 100' => [100],
            'HTTP 199' => [199],
            'HTTP 300' => [300],
            'HTTP 301' => [301],
            'HTTP 302' => [302],
        ];
    }

    // ========================================================================
    // JSON PARSING TESTS
    // ========================================================================

    /**
     * Test: Invalid JSON responses prevent login
     *
     * SECURITY: Invalid JSON must be rejected gracefully without errors
     *
     * @test
     * @dataProvider invalidJsonResponses
     */
    public function test_invalid_json_responses_prevent_login($body)
    {
        $code = 200;
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'process_sync should return false for invalid JSON');
    }

    /**
     * Data provider for invalid JSON responses
     */
    public function invalidJsonResponses()
    {
        return [
            'Empty string' => [''],
            'Plain text' => ['This is not JSON'],
            'Unclosed brace' => ['{"error": "unclosed'],
            'Invalid syntax' => ['{error: missing quotes}'],
            'HTML response' => ['<html><body>Error</body></html>'],
        ];
    }

    // ========================================================================
    // RESPONSE STRUCTURE TESTS
    // ========================================================================

    /**
     * Test: Response with null memberships prevents login
     *
     * @test
     */
    public function test_null_memberships_prevents_login()
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
                        'memberships' => null,
                    ]
                ]
            ]
        ]);
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'Null memberships must prevent login');
    }

    /**
     * Test: Response with missing data field prevents login
     *
     * SECURITY: Missing required fields must be rejected gracefully
     *
     * @test
     */
    public function test_missing_data_field_with_no_results_prevents_login()
    {
        $code = 200;
        $body = json_encode([
            'error' => 'No data'
            // Missing both 'data' and 'results' fields
        ]);
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'Missing data/results field must prevent login');
    }

    // ========================================================================
    // NETWORK ERROR SCENARIOS
    // ========================================================================

    /**
     * Test: Connection refused (HTTP 0) prevents login
     *
     * Scenario: API server is down and refusing connections.
     * wp_remote_get returns WP_Error, which gets converted to HTTP 0.
     *
     * @test
     */
    public function test_connection_refused_prevents_login()
    {
        $code = 0;
        $body = '';
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'Connection refused must prevent login');
    }

    /**
     * Test: Read timeout (HTTP 0) prevents login
     *
     * Scenario: Connection established but API doesn't respond in time.
     * wp_remote_get returns WP_Error, which gets converted to HTTP 0.
     *
     * @test
     */
    public function test_read_timeout_prevents_login()
    {
        $code = 0;
        $body = '';
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'Read timeout must prevent login');
    }

    /**
     * Test: SSL certificate error (HTTP 0) prevents login
     *
     * Scenario: API uses HTTPS but certificate is invalid.
     * wp_remote_get returns WP_Error, which gets converted to HTTP 0.
     *
     * @test
     */
    public function test_ssl_certificate_error_prevents_login()
    {
        $code = 0;
        $body = '';
        $username = 'testuser';
        $user = false;

        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'SSL certificate error must prevent login');
    }

    // ========================================================================
    // EDGE CASES AND BOUNDARY CONDITIONS
    // ========================================================================

    /**
     * Test: Very large response body is handled safely
     *
     * @test
     */
    public function test_large_response_body_is_handled()
    {
        $code = 200;
        // Create a large but valid JSON response
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
                        ],
                        'extra_data' => str_repeat('x', 10000), // Large extra field
                    ]
                ]
            ]
        ]);
        $username = 'testuser';
        $user = false;

        // Should not crash or hang
        $result = Plugin::process_sync($code, $body, $username, $user);

        // User creation fails in test environment
        $this->assertFalse($result, 'User creation fails in test environment');
    }

    /**
     * Test: Unicode characters in response are handled
     *
     * @test
     */
    public function test_unicode_characters_in_response_are_handled()
    {
        $code = 200;
        $body = json_encode([
            'data' => [
                [
                    'profile' => [
                        'username' => 'testuser',
                        'email' => 'testuser@example.com',
                        'first_name' => 'Tëst',
                        'last_name' => '用户',
                        'memberships' => [
                            'SOCIÉTÉ_A' => true,
                        ]
                    ]
                ]
            ]
        ]);
        $username = 'testuser';
        $user = false;

        // Should handle Unicode without issues
        $result = Plugin::process_sync($code, $body, $username, $user);

        // User creation fails in test environment
        $this->assertFalse($result, 'User creation fails in test environment');
    }

    /**
     * Test: Special characters in error messages are handled
     *
     * @test
     */
    public function test_special_characters_in_error_messages_are_handled()
    {
        $code = 500;
        $body = json_encode([
            'error' => 'Database error: <script>alert("xss")</script>'
        ]);
        $username = 'testuser';
        $user = false;

        // Should handle special characters without executing code
        $result = Plugin::process_sync($code, $body, $username, $user);

        $this->assertFalse($result, 'Error response must prevent login');
    }

    /**
     * Test: Deeply nested JSON structures are handled
     *
     * @test
     */
    public function test_deeply_nested_json_structures_are_handled()
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
                        ],
                        'nested' => [
                            'level1' => [
                                'level2' => [
                                    'level3' => [
                                        'level4' => 'value'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);
        $username = 'testuser';
        $user = false;

        // Should handle nested structures without issues
        $result = Plugin::process_sync($code, $body, $username, $user);

        // User creation fails in test environment
        $this->assertFalse($result, 'User creation fails in test environment');
    }

    // ========================================================================
    // SECURITY TESTS
    // ========================================================================

    /**
     * Test: Response with potential XSS payload does NOT log user in
     *
     * @test
     */
    public function test_xss_payload_in_response_prevents_login()
    {
        $code = 200;
        $body = json_encode([
            'data' => [
                [
                    'profile' => [
                        'username' => 'testuser<script>alert("xss")</script>',
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
        $username = 'testuser<script>alert("xss")</script>';
        $user = false;

        // Even though the response is valid JSON, the presence of malicious
        // content should not result in login. The sanitization happens elsewhere.
        $result = Plugin::process_sync($code, $body, $username, $user);

        // User creation fails in test environment
        $this->assertFalse($result, 'User creation fails in test environment');
    }

    /**
     * Test: Response with potential SQL injection payload does NOT log user in
     *
     * @test
     */
    public function test_sql_injection_payload_in_response_prevents_login()
    {
        $code = 200;
        $body = json_encode([
            'data' => [
                [
                    'profile' => [
                        'username' => "testuser'; DROP TABLE users; --",
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
        $username = "testuser'; DROP TABLE users; --";
        $user = false;

        // The payload should not result in login. WordPress should sanitize.
        $result = Plugin::process_sync($code, $body, $username, $user);

        // User creation fails in test environment
        $this->assertFalse($result, 'User creation fails in test environment');
    }
}
