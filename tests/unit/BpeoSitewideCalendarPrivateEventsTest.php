<?php
/**
 * Tests for showing members their private groups' events on the unscoped
 * (sitewide) calendar.
 *
 * Private-group events are private posts. Anonymous visitors and non-members
 * must not see them, but a logged-in member should see those belonging to
 * their own groups alongside the public events. Group-scoped and member
 * calendars handle their own visibility and are left alone.
 */

use PHPUnit\Framework\TestCase;

class BpeoSitewideCalendarPrivateEventsTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_hc_mock'] = array();
		$GLOBALS['hc_test']  = array();
		$GLOBALS['wpdb']     = (object) array( 'posts' => 'wp_posts' );
	}

	/**
	 * Log in user 3 as a member of groups 50 and 60, whose private events are
	 * 7 and 9.
	 */
	private function setUpMemberWithPrivateEvents() {
		$GLOBALS['_hc_mock']['current_user_id'] = 3;
		$GLOBALS['_hc_mock']['user_groups']     = array( 3 => array( 50, 60 ) );
		$GLOBALS['_hc_mock']['wp_query_callback'] = function ( $args ) {
			return array( 7, 9 );
		};
	}

	// -----------------------------------------------------------------------
	// Which private events a member may see.
	// -----------------------------------------------------------------------

	public function test_member_private_event_ids_come_from_their_groups() {
		$this->setUpMemberWithPrivateEvents();

		$this->assertSame( array( 7, 9 ), hc_custom_bpeo_get_member_private_event_ids( 3 ) );
	}

	public function test_user_in_no_groups_has_no_private_event_ids() {
		$GLOBALS['_hc_mock']['wp_query_callback'] = function ( $args ) {
			return array( 7, 9 );
		};

		$this->assertSame( array(), hc_custom_bpeo_get_member_private_event_ids( 4 ) );
	}

	public function test_anonymous_user_has_no_private_event_ids() {
		$this->assertSame( array(), hc_custom_bpeo_get_member_private_event_ids( 0 ) );
	}

	// -----------------------------------------------------------------------
	// The calendar query.
	// -----------------------------------------------------------------------

	public function test_unscoped_query_unchanged_for_anonymous_caller() {
		$in = array( 'perm' => 'readable', 'post_status' => array( 'publish', 'private' ) );

		$this->assertSame( $in, hc_custom_bpeo_filter_calendar_query_for_member_groups( $in ) );
	}

	public function test_unscoped_query_unchanged_for_member_without_private_group_events() {
		$GLOBALS['_hc_mock']['current_user_id'] = 3;
		$GLOBALS['_hc_mock']['user_groups']     = array( 3 => array( 50 ) );

		$in = array( 'perm' => 'readable', 'post_status' => array( 'publish', 'private' ) );

		$this->assertSame( $in, hc_custom_bpeo_filter_calendar_query_for_member_groups( $in ) );
	}

	public function test_unscoped_query_admits_members_private_group_events() {
		$this->setUpMemberWithPrivateEvents();

		$out = hc_custom_bpeo_filter_calendar_query_for_member_groups( array( 'perm' => 'readable', 'post_status' => array( 'publish', 'private' ) ) );

		$this->assertSame( array( 7, 9 ), $out['hc_bpeo_private_event_ids'] );
		$this->assertSame( '', $out['perm'] );
		$this->assertContains( 'private', (array) $out['post_status'] );
		$this->assertContains( 'publish', (array) $out['post_status'] );
	}

	public function test_group_scoped_query_is_left_alone() {
		$this->setUpMemberWithPrivateEvents();

		$in = array( 'bp_group' => 50, 'perm' => '' );

		$this->assertSame( $in, hc_custom_bpeo_filter_calendar_query_for_member_groups( $in ) );
	}

	public function test_denied_group_scope_is_left_alone() {
		$this->setUpMemberWithPrivateEvents();

		$in = array( 'bp_group' => array(), 'perm' => 'readable' );

		$this->assertSame( $in, hc_custom_bpeo_filter_calendar_query_for_member_groups( $in ) );
	}

	public function test_member_calendar_query_is_left_alone() {
		$this->setUpMemberWithPrivateEvents();

		$in = array( 'bp_displayed_user_id' => 3, 'perm' => 'readable' );

		$this->assertSame( $in, hc_custom_bpeo_filter_calendar_query_for_member_groups( $in ) );
	}

	// -----------------------------------------------------------------------
	// The SQL restriction that keeps other private events out.
	// -----------------------------------------------------------------------

	public function test_where_clause_untouched_without_visible_private_events() {
		$q = new WP_Query( array( 'post_type' => 'event' ) );

		$this->assertSame( ' AND 1=1', hc_custom_bpeo_restrict_private_events_where( ' AND 1=1', $q ) );
	}

	public function test_where_clause_limits_private_posts_to_visible_ids_and_own_posts() {
		$GLOBALS['_hc_mock']['current_user_id'] = 3;
		$q = new WP_Query( array( 'post_type' => 'event', 'hc_bpeo_private_event_ids' => array( 7, 9 ) ) );

		$where = hc_custom_bpeo_restrict_private_events_where( ' AND 1=1', $q );

		$this->assertStringStartsWith( ' AND 1=1', $where );
		$this->assertStringContainsString( "wp_posts.post_status <> 'private'", $where );
		$this->assertStringContainsString( 'wp_posts.ID IN (7,9)', $where );
		$this->assertStringContainsString( 'wp_posts.post_author = 3', $where );
	}

	public function test_where_clause_only_ever_contains_integer_ids() {
		$GLOBALS['_hc_mock']['current_user_id'] = 3;
		$q = new WP_Query( array( 'hc_bpeo_private_event_ids' => array( '7', "9) OR (1=1", 'x', 0 ) ) );

		$where = hc_custom_bpeo_restrict_private_events_where( '', $q );

		$this->assertStringContainsString( 'wp_posts.ID IN (7,9)', $where );
		$this->assertStringNotContainsString( '1=1', $where );
	}
}
