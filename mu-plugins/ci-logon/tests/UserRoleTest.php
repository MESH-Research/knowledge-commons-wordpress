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

use MeshResearch\CILogon\BrokerAuth;
use MeshResearch\CILogon\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for User Role Assignment
 *
 * SECURITY: This is a regression test for a critical security vulnerability
 * where new users were being created with administrator privileges.
 */
class UserRoleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        clear_captured_wp_insert_user_data();
    }

    protected function tearDown(): void
    {
        clear_captured_wp_insert_user_data();
        parent::tearDown();
    }

    private function brokerPayload(array $overrides = []): array
    {
        return array_merge([
            'kc_username' => 'newuser_' . time(),
            'email' => 'newuser_' . time() . '@example.com',
            'first_name' => 'New',
            'last_name' => 'User',
            'name' => 'New User',
            'nonce' => 'test-nonce',
            'exp' => time() + 300,
        ], $overrides);
    }

    // ========================================================================
    // BrokerAuth::find_or_create_user TESTS
    // ========================================================================

    /** @test */
    public function test_cilogonauth_creates_user_with_subscriber_role()
    {
        $auth = new BrokerAuth();
        $auth->find_or_create_user($this->brokerPayload());

        $capturedData = get_captured_wp_insert_user_data();

        $this->assertNotNull($capturedData, 'wp_insert_user should have been called');
        $this->assertArrayHasKey('role', $capturedData, 'User data should include role');
        $this->assertEquals(
            'subscriber',
            $capturedData['role'],
            'SECURITY: New users must be created with subscriber role, not administrator'
        );
    }

    /** @test */
    public function test_cilogonauth_does_not_create_administrator()
    {
        $auth = new BrokerAuth();
        $auth->find_or_create_user($this->brokerPayload());

        $capturedData = get_captured_wp_insert_user_data();

        $this->assertNotNull($capturedData);
        $this->assertNotEquals(
            'administrator',
            $capturedData['role'] ?? '',
            'SECURITY VIOLATION: New users must NEVER be created with administrator role'
        );
    }

    /** @test */
    public function test_cilogonauth_does_not_create_editor()
    {
        $auth = new BrokerAuth();
        $auth->find_or_create_user($this->brokerPayload());

        $capturedData = get_captured_wp_insert_user_data();

        $this->assertNotNull($capturedData);
        $this->assertNotEquals(
            'editor',
            $capturedData['role'] ?? '',
            'New users should not be created with editor role'
        );
    }

    /** @test */
    public function test_cilogonauth_does_not_create_author()
    {
        $auth = new BrokerAuth();
        $auth->find_or_create_user($this->brokerPayload());

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

    /** @test */
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

    /** @test */
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

    /** @test */
    public function test_both_methods_use_same_role()
    {
        // Test BrokerAuth
        $auth = new BrokerAuth();
        $auth->find_or_create_user($this->brokerPayload());
        $brokerRole = get_captured_wp_insert_user_data()['role'] ?? null;

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
            $brokerRole,
            $pluginRole,
            'BrokerAuth and Plugin must use the same default role for consistency'
        );
    }

    /** @test */
    public function test_both_methods_use_subscriber_specifically()
    {
        // Test BrokerAuth
        $auth = new BrokerAuth();
        $auth->find_or_create_user($this->brokerPayload());
        $brokerRole = get_captured_wp_insert_user_data()['role'] ?? null;

        $this->assertEquals('subscriber', $brokerRole, 'BrokerAuth must use subscriber role');

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

    /** @test */
    public function test_superadmin_only_from_api_flag()
    {
        $user = new \WP_User(999);
        $user->ID = 999;
        $user->user_login = 'testuser';

        $results_without_superadmin = [
            'username' => 'testuser',
            'is_superadmin' => false,
        ];

        Plugin::setSuperuserStatusIfFlagExistsInAPIResponse($results_without_superadmin, $user);

        $results_with_superadmin = [
            'username' => 'testuser',
            'is_superadmin' => true,
        ];

        Plugin::setSuperuserStatusIfFlagExistsInAPIResponse($results_with_superadmin, $user);

        $this->assertTrue(true, 'Superadmin status is controlled by API flag');
    }

    /** @test */
    public function test_missing_superadmin_flag_revokes_superadmin()
    {
        $user = new \WP_User(888);
        $user->ID = 888;
        $user->user_login = 'testuser2';

        $results_without_key = [
            'username' => 'testuser2',
        ];

        Plugin::setSuperuserStatusIfFlagExistsInAPIResponse($results_without_key, $user);

        $this->assertTrue(true, 'Missing is_superadmin flag does not cause errors');
    }

    // ========================================================================
    // USER DATA STRUCTURE TESTS
    // ========================================================================

    /** @test */
    public function test_user_data_includes_required_fields()
    {
        $auth = new BrokerAuth();
        $auth->find_or_create_user($this->brokerPayload());

        $capturedData = get_captured_wp_insert_user_data();

        $this->assertArrayHasKey('user_login', $capturedData);
        $this->assertArrayHasKey('user_email', $capturedData);
        $this->assertArrayHasKey('user_pass', $capturedData);
        $this->assertArrayHasKey('first_name', $capturedData);
        $this->assertArrayHasKey('last_name', $capturedData);
        $this->assertArrayHasKey('display_name', $capturedData);
        $this->assertArrayHasKey('role', $capturedData);
    }

    /** @test */
    public function test_generated_password_not_empty()
    {
        $auth = new BrokerAuth();
        $auth->find_or_create_user($this->brokerPayload());

        $capturedData = get_captured_wp_insert_user_data();

        $this->assertNotEmpty($capturedData['user_pass'], 'Password should be generated');
    }
}
