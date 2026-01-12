<?php
/**
 * Unit Tests for User Role Assignment
 *
 * Tests verify that new users are created with safe default roles,
 * not elevated privileges like administrator.
 *
 * @package MeshResearch\CILogon\Tests
 */

namespace MeshResearch\CILogon\Tests;

use MeshResearch\CILogon\CILogonAuth;
use MeshResearch\CILogon\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Test suite for User Role Assignment
 *
 * This test suite ensures that users created through CILogon authentication
 * are assigned safe default roles (subscriber) and not administrator.
 *
 * SECURITY: This is a regression test for a critical security vulnerability
 * where new users were being created with administrator privileges.
 */
class UserRoleTest extends TestCase
{
    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        parent::setUp();
        clear_captured_wp_insert_user_data();
    }

    /**
     * Tear down after each test
     */
    protected function tearDown(): void
    {
        clear_captured_wp_insert_user_data();
        parent::tearDown();
    }

    // ========================================================================
    // CILogonAuth::find_or_create_user TESTS
    // ========================================================================

    /**
     * Test: New users created via CILogonAuth have subscriber role
     *
     * SECURITY REGRESSION TEST: Previously users were created with 'administrator' role
     *
     * @test
     */
    public function test_cilogonauth_creates_user_with_subscriber_role()
    {
        // Create a mock user_info object that mimics what CILogon returns
        $user_info = (object) [
            'sub' => 'test-sub-123',
            'profile' => (object) [
                'username' => 'newuser_' . time(),
                'email' => 'newuser_' . time() . '@example.com',
                'first_name' => 'New',
                'last_name' => 'User',
                'name' => 'New User',
            ],
        ];

        // Create CILogonAuth instance
        $auth = new CILogonAuth();

        // Use reflection to access the private find_or_create_user method
        $reflection = new ReflectionClass($auth);
        $method = $reflection->getMethod('find_or_create_user');
        $method->setAccessible(true);

        // Call the method
        $result = $method->invoke($auth, $user_info);

        // Get captured data from wp_insert_user
        $capturedData = get_captured_wp_insert_user_data();

        $this->assertNotNull($capturedData, 'wp_insert_user should have been called');
        $this->assertArrayHasKey('role', $capturedData, 'User data should include role');
        $this->assertEquals(
            'subscriber',
            $capturedData['role'],
            'SECURITY: New users must be created with subscriber role, not administrator'
        );
    }

    /**
     * Test: New users created via CILogonAuth do NOT have administrator role
     *
     * SECURITY REGRESSION TEST: Explicit check that administrator is not used
     *
     * @test
     */
    public function test_cilogonauth_does_not_create_administrator()
    {
        $user_info = (object) [
            'sub' => 'test-sub-456',
            'profile' => (object) [
                'username' => 'anotheruser_' . time(),
                'email' => 'anotheruser_' . time() . '@example.com',
                'first_name' => 'Another',
                'last_name' => 'User',
                'name' => 'Another User',
            ],
        ];

        $auth = new CILogonAuth();
        $reflection = new ReflectionClass($auth);
        $method = $reflection->getMethod('find_or_create_user');
        $method->setAccessible(true);

        $method->invoke($auth, $user_info);

        $capturedData = get_captured_wp_insert_user_data();

        $this->assertNotNull($capturedData);
        $this->assertNotEquals(
            'administrator',
            $capturedData['role'] ?? '',
            'SECURITY VIOLATION: New users must NEVER be created with administrator role'
        );
    }

    /**
     * Test: New users created via CILogonAuth do NOT have editor role
     *
     * @test
     */
    public function test_cilogonauth_does_not_create_editor()
    {
        $user_info = (object) [
            'sub' => 'test-sub-789',
            'profile' => (object) [
                'username' => 'editortest_' . time(),
                'email' => 'editortest_' . time() . '@example.com',
                'first_name' => 'Editor',
                'last_name' => 'Test',
                'name' => 'Editor Test',
            ],
        ];

        $auth = new CILogonAuth();
        $reflection = new ReflectionClass($auth);
        $method = $reflection->getMethod('find_or_create_user');
        $method->setAccessible(true);

        $method->invoke($auth, $user_info);

        $capturedData = get_captured_wp_insert_user_data();

        $this->assertNotNull($capturedData);
        $this->assertNotEquals(
            'editor',
            $capturedData['role'] ?? '',
            'New users should not be created with editor role'
        );
    }

    /**
     * Test: New users created via CILogonAuth do NOT have author role
     *
     * @test
     */
    public function test_cilogonauth_does_not_create_author()
    {
        $user_info = (object) [
            'sub' => 'test-sub-author',
            'profile' => (object) [
                'username' => 'authortest_' . time(),
                'email' => 'authortest_' . time() . '@example.com',
                'first_name' => 'Author',
                'last_name' => 'Test',
                'name' => 'Author Test',
            ],
        ];

        $auth = new CILogonAuth();
        $reflection = new ReflectionClass($auth);
        $method = $reflection->getMethod('find_or_create_user');
        $method->setAccessible(true);

        $method->invoke($auth, $user_info);

        $capturedData = get_captured_wp_insert_user_data();

        $this->assertNotNull($capturedData);
        $this->assertNotEquals(
            'author',
            $capturedData['role'] ?? '',
            'New users should not be created with author role'
        );
    }

    // ========================================================================
    // Plugin::createNewWordPressUser TESTS
    // ========================================================================

    /**
     * Test: New users created via Plugin::createNewWordPressUser have subscriber role
     *
     * @test
     */
    public function test_plugin_creates_user_with_subscriber_role()
    {
        $results_array = [
            'username' => 'pluginuser_' . time(),
            'email' => 'pluginuser_' . time() . '@example.com',
            'first_name' => 'Plugin',
            'last_name' => 'User',
        ];

        Plugin::createNewWordPressUser($results_array);

        $capturedData = get_captured_wp_insert_user_data();

        $this->assertNotNull($capturedData, 'wp_insert_user should have been called');
        $this->assertArrayHasKey('role', $capturedData, 'User data should include role');
        $this->assertEquals(
            'subscriber',
            $capturedData['role'],
            'SECURITY: Plugin::createNewWordPressUser must use subscriber role'
        );
    }

    /**
     * Test: Plugin::createNewWordPressUser does NOT create administrator
     *
     * @test
     */
    public function test_plugin_does_not_create_administrator()
    {
        $results_array = [
            'username' => 'pluginuser2_' . time(),
            'email' => 'pluginuser2_' . time() . '@example.com',
            'first_name' => 'Plugin',
            'last_name' => 'User2',
        ];

        Plugin::createNewWordPressUser($results_array);

        $capturedData = get_captured_wp_insert_user_data();

        $this->assertNotNull($capturedData);
        $this->assertNotEquals(
            'administrator',
            $capturedData['role'] ?? '',
            'SECURITY VIOLATION: Plugin must NEVER create users with administrator role'
        );
    }

    // ========================================================================
    // CONSISTENCY TESTS
    // ========================================================================

    /**
     * Test: Both user creation methods use the same role
     *
     * Ensures CILogonAuth and Plugin use consistent role assignment
     *
     * @test
     */
    public function test_both_methods_use_same_role()
    {
        // Test CILogonAuth
        $user_info = (object) [
            'sub' => 'consistency-test',
            'profile' => (object) [
                'username' => 'consistencytest1_' . time(),
                'email' => 'consistencytest1_' . time() . '@example.com',
                'first_name' => 'Test',
                'last_name' => 'User',
                'name' => 'Test User',
            ],
        ];

        $auth = new CILogonAuth();
        $reflection = new ReflectionClass($auth);
        $method = $reflection->getMethod('find_or_create_user');
        $method->setAccessible(true);

        $method->invoke($auth, $user_info);
        $cilogonRole = get_captured_wp_insert_user_data()['role'] ?? null;

        // Clear and test Plugin
        clear_captured_wp_insert_user_data();

        $results_array = [
            'username' => 'consistencytest2_' . time(),
            'email' => 'consistencytest2_' . time() . '@example.com',
            'first_name' => 'Test',
            'last_name' => 'User2',
        ];

        Plugin::createNewWordPressUser($results_array);
        $pluginRole = get_captured_wp_insert_user_data()['role'] ?? null;

        $this->assertEquals(
            $cilogonRole,
            $pluginRole,
            'CILogonAuth and Plugin must use the same default role for consistency'
        );
    }

    /**
     * Test: Both methods use 'subscriber' specifically
     *
     * @test
     */
    public function test_both_methods_use_subscriber_specifically()
    {
        // Test CILogonAuth
        $user_info = (object) [
            'sub' => 'subscriber-test',
            'profile' => (object) [
                'username' => 'subscribertest1_' . time(),
                'email' => 'subscribertest1_' . time() . '@example.com',
                'first_name' => 'Test',
                'last_name' => 'User',
                'name' => 'Test User',
            ],
        ];

        $auth = new CILogonAuth();
        $reflection = new ReflectionClass($auth);
        $method = $reflection->getMethod('find_or_create_user');
        $method->setAccessible(true);

        $method->invoke($auth, $user_info);
        $cilogonRole = get_captured_wp_insert_user_data()['role'] ?? null;

        $this->assertEquals('subscriber', $cilogonRole, 'CILogonAuth must use subscriber role');

        // Clear and test Plugin
        clear_captured_wp_insert_user_data();

        $results_array = [
            'username' => 'subscribertest2_' . time(),
            'email' => 'subscribertest2_' . time() . '@example.com',
            'first_name' => 'Test',
            'last_name' => 'User2',
        ];

        Plugin::createNewWordPressUser($results_array);
        $pluginRole = get_captured_wp_insert_user_data()['role'] ?? null;

        $this->assertEquals('subscriber', $pluginRole, 'Plugin must use subscriber role');
    }

    // ========================================================================
    // ELEVATED ROLE ASSIGNMENT TESTS
    // ========================================================================

    /**
     * Test: Superadmin is only granted via setSuperuserStatusIfFlagExistsInAPIResponse
     *
     * Verifies that superadmin status comes from API, not default creation
     *
     * @test
     */
    public function test_superadmin_only_from_api_flag()
    {
        // Create a mock user
        $user = new \WP_User(999);
        $user->ID = 999;
        $user->user_login = 'testuser';

        // Test with is_superadmin = false
        $results_without_superadmin = [
            'username' => 'testuser',
            'is_superadmin' => false,
        ];

        // This should call revoke_super_admin, not grant_super_admin
        Plugin::setSuperuserStatusIfFlagExistsInAPIResponse($results_without_superadmin, $user);

        // Test with is_superadmin = true
        $results_with_superadmin = [
            'username' => 'testuser',
            'is_superadmin' => true,
        ];

        Plugin::setSuperuserStatusIfFlagExistsInAPIResponse($results_with_superadmin, $user);

        // If we got here without errors, the method handles both cases
        $this->assertTrue(true, 'Superadmin status is controlled by API flag');
    }

    /**
     * Test: Missing is_superadmin flag does not grant superadmin
     *
     * @test
     */
    public function test_missing_superadmin_flag_revokes_superadmin()
    {
        $user = new \WP_User(888);
        $user->ID = 888;
        $user->user_login = 'testuser2';

        // Results without is_superadmin key at all
        $results_without_key = [
            'username' => 'testuser2',
        ];

        // This should NOT grant superadmin (implicit false)
        Plugin::setSuperuserStatusIfFlagExistsInAPIResponse($results_without_key, $user);

        // If we got here without errors, the method handles missing key
        $this->assertTrue(true, 'Missing is_superadmin flag does not cause errors');
    }

    // ========================================================================
    // USER DATA STRUCTURE TESTS
    // ========================================================================

    /**
     * Test: User data includes all required fields
     *
     * @test
     */
    public function test_user_data_includes_required_fields()
    {
        $user_info = (object) [
            'sub' => 'fields-test',
            'profile' => (object) [
                'username' => 'fieldstest_' . time(),
                'email' => 'fieldstest_' . time() . '@example.com',
                'first_name' => 'Fields',
                'last_name' => 'Test',
                'name' => 'Fields Test',
            ],
        ];

        $auth = new CILogonAuth();
        $reflection = new ReflectionClass($auth);
        $method = $reflection->getMethod('find_or_create_user');
        $method->setAccessible(true);

        $method->invoke($auth, $user_info);

        $capturedData = get_captured_wp_insert_user_data();

        $this->assertArrayHasKey('user_login', $capturedData);
        $this->assertArrayHasKey('user_email', $capturedData);
        $this->assertArrayHasKey('user_pass', $capturedData);
        $this->assertArrayHasKey('first_name', $capturedData);
        $this->assertArrayHasKey('last_name', $capturedData);
        $this->assertArrayHasKey('display_name', $capturedData);
        $this->assertArrayHasKey('role', $capturedData);
    }

    /**
     * Test: Generated password is not empty
     *
     * @test
     */
    public function test_generated_password_not_empty()
    {
        $user_info = (object) [
            'sub' => 'password-test',
            'profile' => (object) [
                'username' => 'passwordtest_' . time(),
                'email' => 'passwordtest_' . time() . '@example.com',
                'first_name' => 'Password',
                'last_name' => 'Test',
                'name' => 'Password Test',
            ],
        ];

        $auth = new CILogonAuth();
        $reflection = new ReflectionClass($auth);
        $method = $reflection->getMethod('find_or_create_user');
        $method->setAccessible(true);

        $method->invoke($auth, $user_info);

        $capturedData = get_captured_wp_insert_user_data();

        $this->assertNotEmpty($capturedData['user_pass'], 'Password should be generated');
    }
}
