<?php
/**
 * Tests for hc_custom_bpeo_group_event_meta_cap(): group admins must be able to
 * edit and delete events connected to their groups; existing read/connect
 * behaviour must be preserved.
 */

use PHPUnit\Framework\TestCase;

class BpeoGroupEventCapsTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_hc_mock'] = array();
	}

	/**
	 * Configure an event (post) connected to a group.
	 */
	private function setUpGroupEvent( $event_id, $group_id, $status = 'publish' ) {
		$event              = new stdClass();
		$event->ID          = $event_id;
		$event->post_type   = 'event';
		$event->post_status = $status;

		$GLOBALS['_hc_mock']['posts']        = array( $event_id => $event );
		$GLOBALS['_hc_mock']['event_groups'] = array( $event_id => array( $group_id ) );
	}

	public function test_group_admin_can_edit_group_event() {
		$this->setUpGroupEvent( 400, 30 );
		$GLOBALS['_hc_mock']['group_admins'] = array( '5:30' );

		$caps = hc_custom_bpeo_group_event_meta_cap( array( 'edit_others_events' ), 'edit_event', 5, array( 400 ) );

		$this->assertSame( array( 'exist' ), $caps );
	}

	public function test_group_admin_can_delete_group_event() {
		$this->setUpGroupEvent( 401, 31 );
		$GLOBALS['_hc_mock']['group_admins'] = array( '6:31' );

		$caps = hc_custom_bpeo_group_event_meta_cap( array( 'delete_others_events' ), 'delete_event', 6, array( 401 ) );

		$this->assertSame( array( 'exist' ), $caps );
	}

	public function test_group_mod_can_edit_group_event() {
		$this->setUpGroupEvent( 402, 32 );
		$GLOBALS['_hc_mock']['group_mods'] = array( '7:32' );

		$caps = hc_custom_bpeo_group_event_meta_cap( array( 'edit_others_events' ), 'edit_event', 7, array( 402 ) );

		$this->assertSame( array( 'exist' ), $caps );
	}

	public function test_group_mod_cannot_delete_group_event() {
		$this->setUpGroupEvent( 403, 33 );
		$GLOBALS['_hc_mock']['group_mods'] = array( '8:33' );

		$caps = hc_custom_bpeo_group_event_meta_cap( array( 'delete_others_events' ), 'delete_event', 8, array( 403 ) );

		$this->assertSame( array( 'delete_others_events' ), $caps );
	}

	public function test_plain_member_cannot_edit_group_event() {
		$this->setUpGroupEvent( 404, 34 );
		$GLOBALS['_hc_mock']['group_members'] = array( '9:34' );

		$caps = hc_custom_bpeo_group_event_meta_cap( array( 'edit_others_events' ), 'edit_event', 9, array( 404 ) );

		$this->assertSame( array( 'edit_others_events' ), $caps );
	}

	public function test_event_without_groups_is_unchanged() {
		$event              = new stdClass();
		$event->ID          = 405;
		$event->post_type   = 'event';
		$event->post_status = 'publish';

		$GLOBALS['_hc_mock']['posts']        = array( 405 => $event );
		$GLOBALS['_hc_mock']['event_groups'] = array( 405 => array() );
		$GLOBALS['_hc_mock']['group_admins'] = array( '10:35' );

		$caps = hc_custom_bpeo_group_event_meta_cap( array( 'edit_others_events' ), 'edit_event', 10, array( 405 ) );

		$this->assertSame( array( 'edit_others_events' ), $caps );
	}

	public function test_group_member_can_connect_event_to_group_without_site_role() {
		// The granted primitive must not depend on the user holding a WP role
		// (and thus 'read') on the current site: group membership is enough.
		$GLOBALS['_hc_mock']['group_members'] = array( '11:36' );

		$caps = hc_custom_bpeo_group_event_meta_cap( array( 'connect_event_to_group' ), 'connect_event_to_group', 11, array( 36 ) );

		$this->assertSame( array( 'exist' ), $caps );
	}

	public function test_group_admin_can_connect_event_to_group() {
		$GLOBALS['_hc_mock']['group_admins'] = array( '17:41' );

		$caps = hc_custom_bpeo_group_event_meta_cap( array( 'connect_event_to_group' ), 'connect_event_to_group', 17, array( 41 ) );

		$this->assertSame( array( 'exist' ), $caps );
	}

	public function test_non_member_cannot_connect_event_to_group() {
		$caps = hc_custom_bpeo_group_event_meta_cap( array( 'connect_event_to_group' ), 'connect_event_to_group', 12, array( 37 ) );

		$this->assertSame( array( 'connect_event_to_group' ), $caps );
	}

	public function test_admin_mod_connection_setting_restricts_members() {
		$GLOBALS['_hc_mock']['group_members']  = array( '13:38' );
		$GLOBALS['_hc_mock']['group_min_role'] = array( 38 => 'admin_mod' );

		$caps = hc_custom_bpeo_group_event_meta_cap( array( 'connect_event_to_group' ), 'connect_event_to_group', 13, array( 38 ) );

		$this->assertSame( array( 'connect_event_to_group' ), $caps );
	}

	public function test_banned_member_cannot_connect_event_to_group() {
		$GLOBALS['_hc_mock']['group_members'] = array( '18:42' );
		$GLOBALS['_hc_mock']['group_banned']  = array( '18:42' );

		$caps = hc_custom_bpeo_group_event_meta_cap( array( 'connect_event_to_group' ), 'connect_event_to_group', 18, array( 42 ) );

		$this->assertSame( array( 'connect_event_to_group' ), $caps );
	}

	public function test_publish_events_granted_in_group_context_to_member_who_can_connect() {
		// Publishing from a group's New Event screen must not additionally
		// require blog-role primitives such as 'read'.
		$GLOBALS['_hc_mock']['current_group_id'] = 43;
		$GLOBALS['_hc_mock']['group_members']    = array( '19:43' );

		$caps = hc_custom_bpeo_group_event_meta_cap( array( 'exist', 'read' ), 'publish_events', 19, array() );

		$this->assertSame( array( 'exist' ), $caps );
	}

	public function test_publish_events_unchanged_outside_group_context() {
		$GLOBALS['_hc_mock']['group_members'] = array( '19:43' );

		$caps = hc_custom_bpeo_group_event_meta_cap( array( 'exist', 'read' ), 'publish_events', 19, array() );

		$this->assertSame( array( 'exist', 'read' ), $caps );
	}

	public function test_publish_events_unchanged_in_group_where_user_cannot_connect() {
		$GLOBALS['_hc_mock']['current_group_id'] = 44;
		$GLOBALS['_hc_mock']['group_members']    = array( '19:43' );

		$caps = hc_custom_bpeo_group_event_meta_cap( array( 'exist', 'read' ), 'publish_events', 19, array() );

		$this->assertSame( array( 'exist', 'read' ), $caps );
	}

	public function test_private_event_readable_by_fellow_group_member() {
		$this->setUpGroupEvent( 406, 39, 'private' );
		$GLOBALS['_hc_mock']['user_groups'] = array( 14 => array( 39 ) );

		$caps = hc_custom_bpeo_group_event_meta_cap( array( 'read_private_posts' ), 'read_event', 14, array( 406 ) );

		$this->assertSame( array( 'read' ), $caps );
	}

	public function test_public_event_readable_by_anyone() {
		$this->setUpGroupEvent( 407, 40, 'publish' );

		$caps = hc_custom_bpeo_group_event_meta_cap( array( 'read' ), 'read_event', 15, array( 407 ) );

		$this->assertSame( array( 'exist' ), $caps );
	}

	public function test_non_event_cap_is_unchanged() {
		$caps = hc_custom_bpeo_group_event_meta_cap( array( 'edit_posts' ), 'edit_post', 16, array( 1 ) );

		$this->assertSame( array( 'edit_posts' ), $caps );
	}
}
