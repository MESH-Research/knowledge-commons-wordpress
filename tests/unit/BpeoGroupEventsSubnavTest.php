<?php
/**
 * Tests for hc_custom_bpeo_register_group_events_subnav(): the group events
 * subnav (Calendar / Upcoming / Manage / New Event) must be registered once
 * group context is available, since BP Event Organiser's own registration runs
 * too early on BuddyPress 12+.
 */

use PHPUnit\Framework\TestCase;

class BpeoGroupEventsSubnavTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_hc_mock']              = array();
		$GLOBALS['_hc_registered_subnav'] = array();
		$GLOBALS['_mock_group_meta']      = array();
	}

	/**
	 * Configure a current group with the given membership flags.
	 */
	private function setUpCurrentGroup( $is_member = true ) {
		$group                 = new stdClass();
		$group->id             = 50;
		$group->slug           = 'test-group';
		$group->is_user_member = $is_member;

		$GLOBALS['_hc_mock']['is_group']         = true;
		$GLOBALS['_hc_mock']['current_group']    = $group;
		$GLOBALS['_hc_mock']['current_group_id'] = 50;
	}

	/**
	 * Collect the slugs registered by the function under test.
	 */
	private function registeredSlugs() {
		return array_map(
			function ( $item ) {
				return $item['args']['slug'];
			},
			$GLOBALS['_hc_registered_subnav']
		);
	}

	public function test_no_subnav_registered_outside_group_context() {
		$GLOBALS['_hc_mock']['is_group'] = false;

		hc_custom_bpeo_register_group_events_subnav();

		$this->assertSame( array(), $GLOBALS['_hc_registered_subnav'] );
	}

	public function test_no_duplicate_registration_when_subnav_already_exists() {
		$this->setUpCurrentGroup();
		$GLOBALS['_hc_mock']['existing_secondary_nav'] = array( (object) array( 'slug' => 'calendar' ) );

		hc_custom_bpeo_register_group_events_subnav();

		$this->assertSame( array(), $GLOBALS['_hc_registered_subnav'] );
	}

	public function test_group_member_gets_new_event_item_under_member_setting() {
		// Default setting: any group member may connect events. The New Event
		// button must appear based on that setting, not on WP blog caps.
		$this->setUpCurrentGroup();
		$GLOBALS['_hc_mock']['current_user_id'] = 5;
		$GLOBALS['_hc_mock']['group_members']   = array( '5:50' );

		hc_custom_bpeo_register_group_events_subnav();

		$slugs = $this->registeredSlugs();
		$this->assertContains( 'calendar', $slugs );
		$this->assertContains( 'upcoming', $slugs );
		$this->assertContains( 'new', $slugs );
	}

	public function test_group_member_does_not_get_new_event_item_under_admin_mod_setting() {
		$this->setUpCurrentGroup();
		$GLOBALS['_hc_mock']['current_user_id'] = 5;
		$GLOBALS['_hc_mock']['group_members']   = array( '5:50' );
		$GLOBALS['_hc_mock']['group_min_role']  = array( 50 => 'admin_mod' );

		hc_custom_bpeo_register_group_events_subnav();

		$slugs = $this->registeredSlugs();
		$this->assertContains( 'calendar', $slugs );
		$this->assertContains( 'upcoming', $slugs );
		$this->assertNotContains( 'new', $slugs );
	}

	public function test_group_admin_gets_new_event_item_under_admin_mod_setting() {
		$this->setUpCurrentGroup();
		$GLOBALS['_hc_mock']['current_user_id'] = 5;
		$GLOBALS['_hc_mock']['group_admins']    = array( '5:50' );
		$GLOBALS['_hc_mock']['group_min_role']  = array( 50 => 'admin_mod' );

		hc_custom_bpeo_register_group_events_subnav();

		$this->assertContains( 'new', $this->registeredSlugs() );
	}

	public function test_non_member_does_not_get_new_event_item() {
		$this->setUpCurrentGroup();
		$GLOBALS['_hc_mock']['current_user_id'] = 6;

		hc_custom_bpeo_register_group_events_subnav();

		$this->assertNotContains( 'new', $this->registeredSlugs() );
	}

	public function test_hidden_events_nav_suppresses_subnav_for_regular_member() {
		// Group admins can hide nav items per group (groupmeta slug => 'hide');
		// the events subnav must respect that setting for regular members.
		$this->setUpCurrentGroup();
		$GLOBALS['_hc_mock']['current_user_id']    = 5;
		$GLOBALS['_hc_mock']['group_members']      = array( '5:50' );
		$GLOBALS['_mock_group_meta'][50]['events'] = 'hide';

		hc_custom_bpeo_register_group_events_subnav();

		$this->assertSame( array(), $GLOBALS['_hc_registered_subnav'] );
	}

	public function test_hidden_events_nav_still_registers_for_group_admin() {
		$this->setUpCurrentGroup();
		$GLOBALS['_hc_mock']['current_user_id']    = 5;
		$GLOBALS['_hc_mock']['group_admins']       = array( '5:50' );
		$GLOBALS['_mock_group_meta'][50]['events'] = 'hide';

		hc_custom_bpeo_register_group_events_subnav();

		$this->assertNotEmpty( $GLOBALS['_hc_registered_subnav'] );
	}

	public function test_hidden_events_nav_still_registers_for_super_admin() {
		$this->setUpCurrentGroup();
		$GLOBALS['_hc_mock']['current_user_id']    = 7;
		$GLOBALS['_hc_mock']['is_super_admin']     = true;
		$GLOBALS['_mock_group_meta'][50]['events'] = 'hide';

		hc_custom_bpeo_register_group_events_subnav();

		$this->assertNotEmpty( $GLOBALS['_hc_registered_subnav'] );
	}

	public function test_subnav_items_registered_for_groups_component_under_events_parent() {
		$this->setUpCurrentGroup();
		$GLOBALS['_hc_mock']['current_user_id'] = 5;
		$GLOBALS['_hc_mock']['group_members']   = array( '5:50' );

		hc_custom_bpeo_register_group_events_subnav();

		$this->assertNotEmpty( $GLOBALS['_hc_registered_subnav'] );
		foreach ( $GLOBALS['_hc_registered_subnav'] as $item ) {
			$this->assertSame( 'groups', $item['component'] );
			$this->assertSame( 'test-group_events', $item['args']['parent_slug'] );
		}
	}

	public function test_group_admin_gets_manage_item() {
		$this->setUpCurrentGroup();
		$GLOBALS['_hc_mock']['current_user_id'] = 5;
		$GLOBALS['_hc_mock']['group_admins']    = array( '5:50' );

		hc_custom_bpeo_register_group_events_subnav();

		$manage = null;
		foreach ( $GLOBALS['_hc_registered_subnav'] as $item ) {
			if ( 'manage' === $item['args']['slug'] ) {
				$manage = $item;
			}
		}

		$this->assertNotNull( $manage );
		$this->assertTrue( (bool) $manage['args']['user_has_access'] );
	}

	public function test_non_admin_does_not_have_access_to_manage_item() {
		$this->setUpCurrentGroup();
		$GLOBALS['_hc_mock']['current_user_id'] = 6;

		hc_custom_bpeo_register_group_events_subnav();

		$manage = null;
		foreach ( $GLOBALS['_hc_registered_subnav'] as $item ) {
			if ( 'manage' === $item['args']['slug'] ) {
				$manage = $item;
			}
		}

		$this->assertNotNull( $manage );
		$this->assertFalse( (bool) $manage['args']['user_has_access'] );
	}
}
