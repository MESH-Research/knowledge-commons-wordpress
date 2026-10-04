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
		$GLOBALS['wpdb']     = (object) array(
			'posts'              => 'wp_posts',
			'term_relationships' => 'wp_term_relationships',
			'term_taxonomy'      => 'wp_term_taxonomy',
			'terms'              => 'wp_terms',
		);
	}

	/**
	 * Log in user 3 as a member of groups 50 and 60.
	 */
	private function setUpMemberWithPrivateEvents() {
		$GLOBALS['_hc_mock']['current_user_id'] = 3;
		$GLOBALS['_hc_mock']['user_groups']     = array( 3 => array( 50, 60 ) );
	}

	// -----------------------------------------------------------------------
	// The calendar query.
	// -----------------------------------------------------------------------

	public function test_unscoped_query_unchanged_for_anonymous_caller() {
		$in = array( 'perm' => 'readable', 'post_status' => array( 'publish', 'private' ) );

		$this->assertSame( $in, hc_custom_bpeo_filter_calendar_query_for_member_groups( $in ) );
	}

	public function test_unscoped_query_unchanged_for_user_in_no_groups() {
		$GLOBALS['_hc_mock']['current_user_id'] = 4;

		$in = array( 'perm' => 'readable', 'post_status' => array( 'publish', 'private' ) );

		$this->assertSame( $in, hc_custom_bpeo_filter_calendar_query_for_member_groups( $in ) );
	}

	public function test_unscoped_query_admits_members_private_group_events() {
		$this->setUpMemberWithPrivateEvents();

		$out = hc_custom_bpeo_filter_calendar_query_for_member_groups( array( 'perm' => 'readable', 'post_status' => array( 'publish', 'private' ) ) );

		$this->assertSame( array( 50, 60 ), $out['hc_bpeo_member_group_ids'] );
		$this->assertSame( '', $out['perm'] );
		$this->assertContains( 'private', (array) $out['post_status'] );
		$this->assertContains( 'publish', (array) $out['post_status'] );
	}

	public function test_unscoped_query_unchanged_for_caller_whose_role_reads_private_events() {
		// An editor/admin is a member of groups with private events (7, 9) but
		// is also entitled, by role, to private events outside those groups
		// (e.g. 11). The membership-based restriction must not be applied on
		// top of EO's 'readable' query, which already lets them see all of them.
		$this->setUpMemberWithPrivateEvents();
		$GLOBALS['_hc_mock']['user_can'] = array( 'read_private_events' => true );

		$in  = array( 'perm' => 'readable', 'post_status' => array( 'publish', 'private' ) );
		$out = hc_custom_bpeo_filter_calendar_query_for_member_groups( $in );

		$this->assertSame( $in, $out );
		$this->assertArrayNotHasKey( 'hc_bpeo_member_group_ids', $out );
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

	public function test_where_clause_limits_private_posts_to_member_groups_and_own_posts() {
		// The restriction must be expressed against the group connection
		// taxonomy inside the main query, so it is bounded by that query's
		// date range rather than by the groups' whole event history.
		$GLOBALS['_hc_mock']['current_user_id'] = 3;
		$q = new WP_Query( array( 'post_type' => 'event', 'hc_bpeo_member_group_ids' => array( 50, 60 ) ) );

		$where = hc_custom_bpeo_restrict_private_events_where( ' AND 1=1', $q );

		$this->assertStringStartsWith( ' AND 1=1', $where );
		$this->assertStringContainsString( "wp_posts.post_status <> 'private'", $where );
		$this->assertStringContainsString( 'wp_posts.post_author = 3', $where );
		$this->assertStringContainsString( "taxonomy = 'bpeo_event_group'", $where );
		$this->assertStringContainsString( "'group_50','group_60'", $where );
		$this->assertStringContainsString( 'wp_term_relationships', $where );
	}

	public function test_where_clause_only_ever_contains_integer_group_ids() {
		$GLOBALS['_hc_mock']['current_user_id'] = 3;
		$q = new WP_Query( array( 'hc_bpeo_member_group_ids' => array( '50', "60) OR (1=1", 'x', 0 ) ) );

		$where = hc_custom_bpeo_restrict_private_events_where( '', $q );

		$this->assertStringContainsString( "'group_50','group_60'", $where );
		$this->assertStringNotContainsString( '1=1', $where );
		$this->assertStringNotContainsString( "'group_0'", $where );
	}
}
