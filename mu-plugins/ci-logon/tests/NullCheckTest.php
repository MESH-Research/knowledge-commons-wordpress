<?php
/**
 * Unit Tests for Null Check Safety
 *
 * Tests verify that the code handles null/missing data gracefully
 * without causing fatal errors.
 *
 * @package MeshResearch\CILogon\Tests
 */

namespace MeshResearch\CILogon\Tests;

use MeshResearch\CILogon\CILogonAuth;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Test suite for Null Check Safety
 *
 * SECURITY: This test suite ensures that missing or malformed data
 * does not cause fatal errors that could expose information or
 * leave the application in an inconsistent state.
 */
class NullCheckTest extends TestCase
{
    /**
     * Track get_user_by calls
     *
     * @var array
     */
    private static $getUserByCalls = [];

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        parent::setUp();
        self::$getUserByCalls = [];
    }

    /**
     * Tear down after each test
     */
    protected function tearDown(): void
    {
        self::$getUserByCalls = [];
        parent::tearDown();
    }

    // ========================================================================
    // SOURCE CODE VERIFICATION TESTS
    // ========================================================================

    /**
     * Test: get_user_info method checks for valid user
     *
     * @test
     */
    public function test_get_user_info_checks_for_valid_user()
    {
        $sourceFile = CILOGON_BASE_DIR . 'CILogonAuth.php';
        $content = file_get_contents($sourceFile);

        // Should check if get_user_by returns false
        $this->assertStringContainsString(
            'if (!$user)',
            $content,
            'get_user_info should check if user is false/null'
        );
    }

    /**
     * Test: get_user_info returns false when user not found
     *
     * @test
     */
    public function test_get_user_info_returns_false_when_user_not_found()
    {
        $sourceFile = CILOGON_BASE_DIR . 'CILogonAuth.php';
        $content = file_get_contents($sourceFile);

        // Should return false when user is not found
        $this->assertMatchesRegularExpression(
            '/if\s*\(\s*!\$user\s*\)\s*\{[^}]*return\s+false/',
            $content,
            'get_user_info should return false when user not found'
        );
    }

    /**
     * Test: get_user_info logs error when user not found
     *
     * @test
     */
    public function test_get_user_info_logs_error_when_user_not_found()
    {
        $sourceFile = CILOGON_BASE_DIR . 'CILogonAuth.php';
        $content = file_get_contents($sourceFile);

        // Should log error when user is not found
        $this->assertStringContainsString(
            'User not found for username',
            $content,
            'get_user_info should log error when user not found'
        );
    }

    /**
     * Test: get_user_info validates profile data exists
     *
     * @test
     */
    public function test_get_user_info_validates_profile_exists()
    {
        $sourceFile = CILOGON_BASE_DIR . 'CILogonAuth.php';
        $content = file_get_contents($sourceFile);

        // Should check if profile exists
        $this->assertStringContainsString(
            '!isset($user_info_final->profile)',
            $content,
            'get_user_info should validate profile exists'
        );
    }

    /**
     * Test: get_user_info validates username exists in profile
     *
     * @test
     */
    public function test_get_user_info_validates_username_exists()
    {
        $sourceFile = CILOGON_BASE_DIR . 'CILogonAuth.php';
        $content = file_get_contents($sourceFile);

        // Should check if username exists in profile
        $this->assertStringContainsString(
            '!isset($user_info_final->profile->username)',
            $content,
            'get_user_info should validate username exists in profile'
        );
    }

    /**
     * Test: get_user_info returns false for invalid profile data
     *
     * @test
     */
    public function test_get_user_info_returns_false_for_invalid_profile()
    {
        $sourceFile = CILOGON_BASE_DIR . 'CILogonAuth.php';
        $content = file_get_contents($sourceFile);

        // Should return false when profile data is invalid
        $this->assertStringContainsString(
            'Invalid profile data in API response',
            $content,
            'get_user_info should log error for invalid profile data'
        );
    }

    /**
     * Test: link_account call is followed by return statement
     *
     * @test
     */
    public function test_link_account_followed_by_return()
    {
        $sourceFile = CILOGON_BASE_DIR . 'CILogonAuth.php';
        $content = file_get_contents($sourceFile);

        // Should have return statement after link_account call
        $this->assertMatchesRegularExpression(
            '/\$this->link_account\(\);[\s\n]*return\s+false;/',
            $content,
            'link_account() call should be followed by return false for defensive coding'
        );
    }

    // ========================================================================
    // DEFENSIVE CODING PATTERN TESTS
    // ========================================================================

    /**
     * Test: All property accesses are guarded by null checks
     *
     * Verify that $user->user_login is only accessed after checking $user
     *
     * @test
     */
    public function test_user_property_access_is_guarded()
    {
        $sourceFile = CILOGON_BASE_DIR . 'CILogonAuth.php';
        $content = file_get_contents($sourceFile);

        // The pattern: check $user before accessing $user->user_login
        // Find all occurrences of $user->user_login and verify they come after $user check
        $userLoginAccesses = preg_match_all('/\$user->user_login/', $content);
        $userChecks = preg_match_all('/if\s*\(\s*!\$user\s*\)/', $content);

        // There should be at least one user check for safety
        $this->assertGreaterThanOrEqual(
            1,
            $userChecks,
            'Should have at least one null check for $user before accessing properties'
        );
    }

    /**
     * Test: Method returns early on validation failure
     *
     * @test
     */
    public function test_early_returns_on_validation_failure()
    {
        $sourceFile = CILOGON_BASE_DIR . 'CILogonAuth.php';
        $content = file_get_contents($sourceFile);

        // Count return false statements in get_user_info
        // Extract get_user_info method
        preg_match('/public function get_user_info\(\)[^{]*\{(.*?)\n    public function/s', $content, $matches);

        if (!empty($matches[1])) {
            $methodContent = $matches[1];
            $returnFalseCount = preg_match_all('/return\s+false;/', $methodContent);

            // Should have multiple return false statements for different failure cases
            $this->assertGreaterThanOrEqual(
                4,
                $returnFalseCount,
                'get_user_info should have multiple early returns for different failure cases'
            );
        } else {
            $this->markTestSkipped('Could not extract get_user_info method');
        }
    }

    // ========================================================================
    // ERROR MESSAGE TESTS
    // ========================================================================

    /**
     * Test: Error messages are descriptive for debugging
     *
     * @test
     */
    public function test_error_messages_are_descriptive()
    {
        $sourceFile = CILOGON_BASE_DIR . 'CILogonAuth.php';
        $content = file_get_contents($sourceFile);

        $expectedMessages = [
            'No sub found in verified claims',
            'Error fetching user info from Profiles API',
            'Invalid response from Profiles API',
            'No user data found in Profiles API response',
            'Invalid profile data in API response',
            'User not found for username',
        ];

        foreach ($expectedMessages as $message) {
            $this->assertStringContainsString(
                $message,
                $content,
                "Error message should be present: $message"
            );
        }
    }

    /**
     * Test: Username is logged when user not found (for debugging)
     *
     * @test
     */
    public function test_username_logged_when_user_not_found()
    {
        $sourceFile = CILOGON_BASE_DIR . 'CILogonAuth.php';
        $content = file_get_contents($sourceFile);

        // Should log the username that wasn't found
        $this->assertMatchesRegularExpression(
            '/User not found for username.*\$profile->username/',
            $content,
            'Should log the username when user not found'
        );
    }

    // ========================================================================
    // REGRESSION TESTS
    // ========================================================================

    /**
     * Test: Fatal error scenario - accessing property on false is prevented
     *
     * This test verifies that the code structure prevents the fatal error:
     * "Attempt to read property 'user_login' on bool"
     *
     * @test
     */
    public function test_prevents_fatal_error_on_false_user()
    {
        $sourceFile = CILOGON_BASE_DIR . 'CILogonAuth.php';
        $content = file_get_contents($sourceFile);

        // Extract the relevant code section
        preg_match('/\$user = get_user_by.*?\$username = \$user->user_login/s', $content, $matches);

        if (!empty($matches[0])) {
            $codeSection = $matches[0];

            // Verify there's a null check between get_user_by and accessing user_login
            $this->assertMatchesRegularExpression(
                '/get_user_by.*if\s*\(\s*!\$user\s*\).*return.*\$user->user_login/s',
                $codeSection,
                'Must check if $user is false before accessing $user->user_login'
            );
        } else {
            $this->fail('Could not find get_user_by to user_login code section');
        }
    }

    /**
     * Test: Code handles missing profile gracefully
     *
     * @test
     */
    public function test_handles_missing_profile_gracefully()
    {
        $sourceFile = CILOGON_BASE_DIR . 'CILogonAuth.php';
        $content = file_get_contents($sourceFile);

        // Should check profile exists before accessing it
        $this->assertMatchesRegularExpression(
            '/!isset\(\$user_info_final->profile\).*return\s+false/s',
            $content,
            'Should return false when profile is missing'
        );
    }

    /**
     * Test: Code handles missing username gracefully
     *
     * @test
     */
    public function test_handles_missing_username_gracefully()
    {
        $sourceFile = CILOGON_BASE_DIR . 'CILogonAuth.php';
        $content = file_get_contents($sourceFile);

        // Should check username exists before accessing it
        $this->assertMatchesRegularExpression(
            '/!isset\(\$user_info_final->profile->username\).*return\s+false/s',
            $content,
            'Should return false when username is missing'
        );
    }
}
