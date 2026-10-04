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
		$_GET                = array();
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
		$GLOBALS['_hc_mock']['is_group'] = false;
		$_GET['bp_group']                = '50';

		$query = hc_custom_bpeo_filter_calendar_query_for_group( array( 'showpastevents' => true ) );

		$this->assertSame( 50, $query['bp_group'] );
	}

	public function test_calendar_query_scoped_to_group_from_page_context() {
		$GLOBALS['_hc_mock']['is_group']         = true;
		$GLOBALS['_hc_mock']['current_group_id'] = 50;

		$query = hc_custom_bpeo_filter_calendar_query_for_group( array() );

		$this->assertSame( 50, $query['bp_group'] );
	}

	public function test_calendar_query_preserves_existing_arguments() {
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
}
