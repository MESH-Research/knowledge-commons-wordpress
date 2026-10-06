<?php
/**
 * Tests for hc_custom_bpeo_user_can_connect_event_to_group(): whether a user
 * may connect (create) events for a group must be decided by the group's
 * "minimum member role" setting (groupmeta 'bpeo_connect_member_role') and the
 * user's role within the group — not by WP blog-role capabilities, which
 * society-site users do not reliably have.
 */

use PHPUnit\Framework\TestCase;

class BpeoConnectEventPermissionTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_hc_mock']        = array();
		$GLOBALS['_mock_group_meta'] = array();
	}

	public function test_member_can_connect_under_default_member_setting() {
		$GLOBALS['_hc_mock']['group_members'] = array( '20:60' );

		$this->assertTrue( hc_custom_bpeo_user_can_connect_event_to_group( 20, 60 ) );
	}

	public function test_non_member_cannot_connect() {
		$this->assertFalse( hc_custom_bpeo_user_can_connect_event_to_group( 21, 60 ) );
	}

	public function test_group_admin_can_connect_under_member_setting() {
		$GLOBALS['_hc_mock']['group_admins'] = array( '22:60' );

		$this->assertTrue( hc_custom_bpeo_user_can_connect_event_to_group( 22, 60 ) );
	}

	public function test_member_cannot_connect_under_admin_mod_setting() {
		$GLOBALS['_hc_mock']['group_members']  = array( '23:61' );
		$GLOBALS['_hc_mock']['group_min_role'] = array( 61 => 'admin_mod' );

		$this->assertFalse( hc_custom_bpeo_user_can_connect_event_to_group( 23, 61 ) );
	}

	public function test_group_admin_can_connect_under_admin_mod_setting() {
		$GLOBALS['_hc_mock']['group_admins']   = array( '24:61' );
		$GLOBALS['_hc_mock']['group_min_role'] = array( 61 => 'admin_mod' );

		$this->assertTrue( hc_custom_bpeo_user_can_connect_event_to_group( 24, 61 ) );
	}

	public function test_group_mod_can_connect_under_admin_mod_setting() {
		$GLOBALS['_hc_mock']['group_mods']     = array( '25:61' );
		$GLOBALS['_hc_mock']['group_min_role'] = array( 61 => 'admin_mod' );

		$this->assertTrue( hc_custom_bpeo_user_can_connect_event_to_group( 25, 61 ) );
	}

	public function test_banned_member_cannot_connect() {
		$GLOBALS['_hc_mock']['group_members'] = array( '26:60' );
		$GLOBALS['_hc_mock']['group_banned']  = array( '26:60' );

		$this->assertFalse( hc_custom_bpeo_user_can_connect_event_to_group( 26, 60 ) );
	}

	public function test_super_admin_can_connect() {
		$GLOBALS['_hc_mock']['is_super_admin'] = true;

		$this->assertTrue( hc_custom_bpeo_user_can_connect_event_to_group( 27, 60 ) );
	}

	public function test_unvetted_current_user_cannot_connect() {
		$GLOBALS['_hc_mock']['group_members']   = array( '28:60' );
		$GLOBALS['_hc_mock']['current_user_id'] = 28;
		$GLOBALS['_hc_mock']['vetted_user']     = false;

		$this->assertFalse( hc_custom_bpeo_user_can_connect_event_to_group( 28, 60 ) );
	}

	public function test_missing_user_or_group_is_denied() {
		$GLOBALS['_hc_mock']['group_members'] = array( '20:60' );

		$this->assertFalse( hc_custom_bpeo_user_can_connect_event_to_group( 0, 60 ) );
		$this->assertFalse( hc_custom_bpeo_user_can_connect_event_to_group( 20, 0 ) );
	}
}
