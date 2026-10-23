<?php
/**
 * Tests for the null-safe replacement of Event Organiser's occurrence-date
 * cache priming on 'the_posts'.
 *
 * Event Organiser LEFT JOINs its occurrences table onto event queries, so an
 * event post with no occurrence row comes back with NULL date columns. The
 * plugin's own filter builds a DateTime from those and throws, which turns a
 * single orphaned event into a fatal error for every listing it appears in.
 * The replacement must skip such rows and otherwise behave like the original.
 *
 * It lives in mu-plugins/event-organiser-dates-guard.php so that it loads on
 * every site of every network, including sites where hc-custom is not active.
 */

use PHPUnit\Framework\TestCase;

class EoEventDatesCacheGuardTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_hc_mock']              = array( 'blog_timezone' => 'Europe/London' );
		$GLOBALS['_mock_wp_cache']        = array();
		$GLOBALS['_removed_filters']      = array();
	}

	private function eventQuery() {
		return new WP_Query( array( 'post_type' => 'event' ) );
	}

	private function occurrence( $id, $occurrence_id, $start_date, $start_time, $end_date, $end_time ) {
		return (object) array(
			'ID'            => $id,
			'occurrence_id' => $occurrence_id,
			'StartDate'     => $start_date,
			'StartTime'     => $start_time,
			'EndDate'       => $end_date,
			'FinishTime'    => $end_time,
		);
	}

	private function cached( $id ) {
		return wp_cache_get( 'eventorganiser_occurrences_' . $id );
	}

	// -----------------------------------------------------------------------
	// Hook rewiring.
	// -----------------------------------------------------------------------

	public function test_plugin_filter_is_replaced_by_the_guarded_one() {
		hc_custom_eo_replace_event_dates_cache_filter();

		$removed = $GLOBALS['_removed_filters']['the_posts'] ?? array();
		$this->assertContains(
			array( 'callback' => '_eventorganiser_update_event_dates_cache', 'priority' => 10 ),
			$removed
		);

		$this->assertContains( 'hc_custom_eo_update_event_dates_cache', $GLOBALS['_captured_filters']['the_posts'] ?? array() );
	}

	// -----------------------------------------------------------------------
	// The guarded filter.
	// -----------------------------------------------------------------------

	public function test_orphaned_event_with_null_dates_is_skipped_and_posts_are_returned_intact() {
		$orphan = $this->occurrence( 7, null, null, null, null, null );
		$good   = $this->occurrence( 8, 81, '2026-11-01', '10:00:00', '2026-11-01', '11:00:00' );
		$events = array( $orphan, $good );

		$out = hc_custom_eo_update_event_dates_cache( $events, $this->eventQuery() );

		$this->assertSame( $events, $out );
		$this->assertFalse( $this->cached( 7 ) );
		$this->assertArrayHasKey( 81, $this->cached( 8 ) );
	}

	public function test_event_with_unparseable_dates_is_skipped_without_throwing() {
		$bad    = $this->occurrence( 9, 91, 'not a date', '', 'not a date', '' );
		$events = array( $bad );

		$out = hc_custom_eo_update_event_dates_cache( $events, $this->eventQuery() );

		$this->assertSame( $events, $out );
		$this->assertFalse( $this->cached( 9 ) );
	}

	public function test_valid_occurrence_is_cached_as_start_and_end_in_blog_timezone() {
		$events = array( $this->occurrence( 5, 51, '2026-11-01', '10:00:00', '2026-11-02', '11:30:00' ) );

		hc_custom_eo_update_event_dates_cache( $events, $this->eventQuery() );

		$cached = $this->cached( 5 );
		$this->assertInstanceOf( DateTime::class, $cached[51]['start'] );
		$this->assertInstanceOf( DateTime::class, $cached[51]['end'] );
		$this->assertSame( '2026-11-01 10:00:00', $cached[51]['start']->format( 'Y-m-d H:i:s' ) );
		$this->assertSame( '2026-11-02 11:30:00', $cached[51]['end']->format( 'Y-m-d H:i:s' ) );
		$this->assertSame( 'Europe/London', $cached[51]['start']->getTimezone()->getName() );
	}

	public function test_multiple_occurrences_of_one_event_share_a_cache_entry() {
		$events = array(
			$this->occurrence( 5, 51, '2026-11-01', '10:00:00', '2026-11-01', '11:00:00' ),
			$this->occurrence( 5, 52, '2026-11-08', '10:00:00', '2026-11-08', '11:00:00' ),
		);

		hc_custom_eo_update_event_dates_cache( $events, $this->eventQuery() );

		$cached = $this->cached( 5 );
		$this->assertArrayHasKey( 51, $cached );
		$this->assertArrayHasKey( 52, $cached );
	}

	public function test_already_cached_occurrence_is_left_untouched() {
		$existing = array( 51 => 'sentinel' );
		wp_cache_set( 'eventorganiser_occurrences_5', $existing );
		$events = array( $this->occurrence( 5, 51, '2026-11-01', '10:00:00', '2026-11-01', '11:00:00' ) );

		hc_custom_eo_update_event_dates_cache( $events, $this->eventQuery() );

		$this->assertSame( 'sentinel', $this->cached( 5 )[51] );
	}

	public function test_non_event_query_is_ignored() {
		$events = array( $this->occurrence( 5, 51, '2026-11-01', '10:00:00', '2026-11-01', '11:00:00' ) );

		$out = hc_custom_eo_update_event_dates_cache( $events, new WP_Query( array( 'post_type' => 'post' ) ) );

		$this->assertSame( $events, $out );
		$this->assertFalse( $this->cached( 5 ) );
	}

	public function test_empty_result_set_is_returned_unchanged() {
		$this->assertSame( array(), hc_custom_eo_update_event_dates_cache( array(), $this->eventQuery() ) );
	}

	public function test_ordinary_post_on_category_archive_shared_with_events_is_skipped() {
		// The production trigger: when the 'category' taxonomy is attached to
		// the event post type, a category archive counts as a (non-exclusive)
		// event query, but the plugin's SELECT/JOIN filters only apply to
		// exclusive ones. Rows are then plain WP_Post objects whose magic
		// getters answer '' for the occurrence columns.
		$post   = new EoGuardTestFakeWpPost( 21 );
		$events = array( $post );

		$out = hc_custom_eo_update_event_dates_cache( $events, new WP_Query( array( 'post_type' => '', 'category_name' => 'digital-publishing' ) ) );

		$this->assertSame( $events, $out );
		$this->assertFalse( $this->cached( 21 ) );
	}

	public function test_event_without_joined_date_columns_is_skipped() {
		// e.g. a query with fields => 'ids' or one where the join did not apply.
		$bare   = (object) array( 'ID' => 12 );
		$events = array( $bare );

		$out = hc_custom_eo_update_event_dates_cache( $events, $this->eventQuery() );

		$this->assertSame( $events, $out );
		$this->assertFalse( $this->cached( 12 ) );
	}
}

/**
 * Mirrors WP_Post's magic property handling: unknown properties read as post
 * meta, which for a missing key is '' and does not count as set.
 */
class EoGuardTestFakeWpPost {
	public $ID;
	public $post_type = 'post';

	public function __construct( $id ) {
		$this->ID = $id;
	}

	public function __isset( $key ) {
		return false;
	}

	public function __get( $key ) {
		return '';
	}
}
