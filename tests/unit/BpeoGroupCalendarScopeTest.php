<?php
/**
 * Tests for scoping the group Events calendar to the current group.
 *
 * The calendar grid is rendered on the group page, but its events are fetched
 * by a separate admin-ajax request in which BuddyPress has no group context
 * (bp_is_group() is false). The group must therefore be resolvable either from
 * the page context or from a 'bp_group' request parameter, and the Event
 * Organiser calendar query must carry it so only that group's events are
 * returned.
 */

use PHPUnit\Framework\TestCase;

class BpeoGroupCalendarScopeTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_hc_mock'] = array();
		$GLOBALS['hc_test']  = array();
		$_GET                = array();
	}

	/**
	 * Register a group with the given status in the groups_get_group() stub.
	 */
	private function setUpGroup( $group_id, $status = 'public' ) {
		$GLOBALS['hc_test']['groups'][ $group_id ] = (object) array(
			'id'     => $group_id,
			'status' => $status,
		);
	}

	protected function tearDown(): void {
		$_GET = array();
	}

	// -----------------------------------------------------------------------
	// Resolving the group a calendar request is for.
	// -----------------------------------------------------------------------

	public function test_group_resolved_from_page_context_when_viewing_a_group() {
		$GLOBALS['_hc_mock']['is_group']         = true;
		$GLOBALS['_hc_mock']['current_group_id'] = 50;

		$this->assertSame( 50, hc_custom_bpeo_get_calendar_group_id() );
	}

	public function test_group_resolved_from_request_outside_group_context() {
		// admin-ajax: no BP group context, but the calendar passes bp_group.
		$GLOBALS['_hc_mock']['is_group'] = false;

		$this->assertSame( 50, hc_custom_bpeo_get_calendar_group_id( array( 'bp_group' => '50' ) ) );
	}

	public function test_group_resolved_from_superglobal_when_no_request_given() {
		$GLOBALS['_hc_mock']['is_group'] = false;
		$_GET['bp_group']                = '72';

		$this->assertSame( 72, hc_custom_bpeo_get_calendar_group_id() );
	}

	public function test_page_context_wins_over_request_parameter() {
		$GLOBALS['_hc_mock']['is_group']         = true;
		$GLOBALS['_hc_mock']['current_group_id'] = 50;

		$this->assertSame( 50, hc_custom_bpeo_get_calendar_group_id( array( 'bp_group' => '99' ) ) );
	}

	public function test_no_group_when_neither_context_nor_request_identify_one() {
		$GLOBALS['_hc_mock']['is_group'] = false;

		$this->assertSame( 0, hc_custom_bpeo_get_calendar_group_id( array() ) );
	}

	/**
	 * @dataProvider invalidGroupParameters
	 */
	public function test_invalid_request_parameter_yields_no_group( $value ) {
		$GLOBALS['_hc_mock']['is_group'] = false;

		$this->assertSame( 0, hc_custom_bpeo_get_calendar_group_id( array( 'bp_group' => $value ) ) );
	}

	public function invalidGroupParameters() {
		return array(
			'non-numeric'    => array( 'mpes-grp' ),
			'negative'       => array( '-5' ),
			'zero'           => array( '0' ),
			'empty string'   => array( '' ),
			'array'          => array( array( '50' ) ),
			'sql-ish'        => array( '50 OR 1=1' ),
		);
	}

	// -----------------------------------------------------------------------
	// The Event Organiser calendar query.
	// -----------------------------------------------------------------------

	public function test_calendar_query_scoped_to_group_from_request() {
		$this->setUpGroup( 50 );
		$GLOBALS['_hc_mock']['is_group'] = false;
		$_GET['bp_group']                = '50';

		$query = hc_custom_bpeo_filter_calendar_query_for_group( array( 'showpastevents' => true ) );

		$this->assertSame( 50, $query['bp_group'] );
	}

	public function test_calendar_query_scoped_to_group_from_page_context() {
		$this->setUpGroup( 50 );
		$GLOBALS['_hc_mock']['is_group']         = true;
		$GLOBALS['_hc_mock']['current_group_id'] = 50;

		$query = hc_custom_bpeo_filter_calendar_query_for_group( array() );

		$this->assertSame( 50, $query['bp_group'] );
	}

	public function test_calendar_query_preserves_existing_arguments() {
		$this->setUpGroup( 50 );
		$GLOBALS['_hc_mock']['is_group'] = false;
		$_GET['bp_group']                = '50';

		$in  = array(
			'event_start_before' => '2026-11-01',
			'event_end_after'    => '2026-09-28',
			'post_status'        => array( 'publish', 'private' ),
		);
		$out = hc_custom_bpeo_filter_calendar_query_for_group( $in );

		foreach ( $in as $key => $value ) {
			$this->assertSame( $value, $out[ $key ] );
		}
	}

	public function test_calendar_query_untouched_outside_any_group_context() {
		$GLOBALS['_hc_mock']['is_group'] = false;

		$in = array( 'event_start_before' => '2026-11-01' );

		$this->assertSame( $in, hc_custom_bpeo_filter_calendar_query_for_group( $in ) );
	}

	public function test_calendar_query_untouched_for_invalid_request_group() {
		$GLOBALS['_hc_mock']['is_group'] = false;
		$_GET['bp_group']                = 'not-a-group';

		$in = array( 'event_start_before' => '2026-11-01' );

		$this->assertSame( $in, hc_custom_bpeo_filter_calendar_query_for_group( $in ) );
	}

	// -----------------------------------------------------------------------
	// Group access: the AJAX endpoint has none of the group page's gating, so
	// the scope must only be honoured for groups the caller may see. Denied
	// requests yield an empty scope (no events), never the unscoped calendar.
	// -----------------------------------------------------------------------

	public function test_public_group_scoped_for_anonymous_caller() {
		$this->setUpGroup( 50, 'public' );
		$GLOBALS['_hc_mock']['is_group'] = false;
		$_GET['bp_group']                = '50';

		$query = hc_custom_bpeo_filter_calendar_query_for_group( array() );

		$this->assertSame( 50, $query['bp_group'] );
	}

	public function test_private_group_denied_for_anonymous_caller() {
		$this->setUpGroup( 50, 'private' );
		$GLOBALS['_hc_mock']['is_group'] = false;
		$_GET['bp_group']                = '50';

		$query = hc_custom_bpeo_filter_calendar_query_for_group( array() );

		$this->assertSame( array(), $query['bp_group'] );
	}

	public function test_private_group_denied_for_logged_in_non_member() {
		$this->setUpGroup( 50, 'private' );
		$GLOBALS['_hc_mock']['is_group']        = false;
		$GLOBALS['_hc_mock']['current_user_id'] = 7;
		$GLOBALS['_hc_mock']['group_members']   = array( '7:60' );
		$_GET['bp_group']                       = '50';

		$query = hc_custom_bpeo_filter_calendar_query_for_group( array() );

		$this->assertSame( array(), $query['bp_group'] );
	}

	public function test_private_group_scoped_for_member() {
		$this->setUpGroup( 50, 'private' );
		$GLOBALS['_hc_mock']['is_group']        = false;
		$GLOBALS['_hc_mock']['current_user_id'] = 7;
		$GLOBALS['_hc_mock']['group_members']   = array( '7:50' );
		$_GET['bp_group']                       = '50';

		$query = hc_custom_bpeo_filter_calendar_query_for_group( array() );

		$this->assertSame( 50, $query['bp_group'] );
	}

	public function test_hidden_group_denied_for_non_member_and_scoped_for_member() {
		$this->setUpGroup( 50, 'hidden' );
		$GLOBALS['_hc_mock']['is_group']        = false;
		$GLOBALS['_hc_mock']['current_user_id'] = 7;
		$_GET['bp_group']                       = '50';

		$GLOBALS['_hc_mock']['group_members'] = array();
		$this->assertSame( array(), hc_custom_bpeo_filter_calendar_query_for_group( array() )['bp_group'] );

		$GLOBALS['_hc_mock']['group_members'] = array( '7:50' );
		$this->assertSame( 50, hc_custom_bpeo_filter_calendar_query_for_group( array() )['bp_group'] );
	}

	public function test_private_group_scoped_for_moderator_who_is_not_a_member() {
		$this->setUpGroup( 50, 'private' );
		$GLOBALS['_hc_mock']['is_group']        = false;
		$GLOBALS['_hc_mock']['current_user_id'] = 7;
		$GLOBALS['_hc_mock']['user_can']        = array( 'bp_moderate' => true );
		$_GET['bp_group']                       = '50';

		$query = hc_custom_bpeo_filter_calendar_query_for_group( array() );

		$this->assertSame( 50, $query['bp_group'] );
	}

	public function test_nonexistent_group_yields_empty_scope() {
		$GLOBALS['_hc_mock']['is_group'] = false;
		$_GET['bp_group']                = '999';

		$query = hc_custom_bpeo_filter_calendar_query_for_group( array( 'perm' => 'readable' ) );

		$this->assertSame( array(), $query['bp_group'] );
	}

	public function test_access_check_also_applies_in_page_context() {
		$this->setUpGroup( 50, 'private' );
		$GLOBALS['_hc_mock']['is_group']         = true;
		$GLOBALS['_hc_mock']['current_group_id'] = 50;
		$GLOBALS['_hc_mock']['current_user_id']  = 7;

		$query = hc_custom_bpeo_filter_calendar_query_for_group( array() );

		$this->assertSame( array(), $query['bp_group'] );
	}

	public function test_granted_scope_does_not_filter_private_events_by_capability() {
		// Members of a private group must see its (private) events; access has
		// already been verified against the group, so EO's capability-based
		// 'readable' restriction must not apply on top.
		$this->setUpGroup( 50, 'private' );
		$GLOBALS['_hc_mock']['is_group']        = false;
		$GLOBALS['_hc_mock']['current_user_id'] = 7;
		$GLOBALS['_hc_mock']['group_members']   = array( '7:50' );
		$_GET['bp_group']                       = '50';

		$query = hc_custom_bpeo_filter_calendar_query_for_group( array( 'perm' => 'readable' ) );

		$this->assertSame( '', $query['perm'] );
	}

	public function test_anonymous_request_to_public_group_keeps_capability_restriction() {
		// A public group's calendar is open to all, but an event connected to
		// both this public group and a private group is a private post. Only
		// members of the requested group (or moderators) may bypass EO's
		// capability check; anonymous visitors must keep it.
		$this->setUpGroup( 50, 'public' );
		$GLOBALS['_hc_mock']['is_group'] = false;
		$_GET['bp_group']                = '50';

		$query = hc_custom_bpeo_filter_calendar_query_for_group( array( 'perm' => 'readable' ) );

		$this->assertSame( 50, $query['bp_group'] );
		$this->assertSame( 'readable', $query['perm'] );
	}

	public function test_logged_in_non_member_of_public_group_keeps_capability_restriction() {
		$this->setUpGroup( 50, 'public' );
		$GLOBALS['_hc_mock']['is_group']        = false;
		$GLOBALS['_hc_mock']['current_user_id'] = 7;
		$GLOBALS['_hc_mock']['group_members']   = array( '7:60' );
		$_GET['bp_group']                       = '50';

		$query = hc_custom_bpeo_filter_calendar_query_for_group( array( 'perm' => 'readable' ) );

		$this->assertSame( 50, $query['bp_group'] );
		$this->assertSame( 'readable', $query['perm'] );
	}

	public function test_member_of_public_group_bypasses_capability_restriction() {
		$this->setUpGroup( 50, 'public' );
		$GLOBALS['_hc_mock']['is_group']        = false;
		$GLOBALS['_hc_mock']['current_user_id'] = 7;
		$GLOBALS['_hc_mock']['group_members']   = array( '7:50' );
		$_GET['bp_group']                       = '50';

		$query = hc_custom_bpeo_filter_calendar_query_for_group( array( 'perm' => 'readable' ) );

		$this->assertSame( '', $query['perm'] );
	}

	public function test_moderator_bypasses_capability_restriction_on_public_group() {
		$this->setUpGroup( 50, 'public' );
		$GLOBALS['_hc_mock']['is_group']        = false;
		$GLOBALS['_hc_mock']['current_user_id'] = 7;
		$GLOBALS['_hc_mock']['user_can']        = array( 'bp_moderate' => true );
		$_GET['bp_group']                       = '50';

		$query = hc_custom_bpeo_filter_calendar_query_for_group( array( 'perm' => 'readable' ) );

		$this->assertSame( '', $query['perm'] );
	}

	public function test_denied_scope_leaves_capability_restriction_in_place() {
		$this->setUpGroup( 50, 'private' );
		$GLOBALS['_hc_mock']['is_group'] = false;
		$_GET['bp_group']                = '50';

		$query = hc_custom_bpeo_filter_calendar_query_for_group( array( 'perm' => 'readable' ) );

		$this->assertSame( 'readable', $query['perm'] );
	}
}
