<?php
/**
 * Regression tests for bpeo_remove_duplicates_from_activity_stream().
 *
 * A logged-in group member sees both the canonical 'events' activity item and
 * the per-group (hide_sitewide) copy for the same event, which sends the
 * function down its dedup + backfill path. That path must not fatal and must
 * return a correctly deduplicated, correctly sized stream.
 */

use PHPUnit\Framework\TestCase;

class BpeoActivityDedupTest extends TestCase {

	protected function setUp(): void {
		unset( $GLOBALS['_mock_bp_activity_get_callback'] );
	}

	private function make_activity( $id, $type, $secondary_item_id, $component ) {
		return (object) array(
			'id'                => $id,
			'type'              => $type,
			'secondary_item_id' => $secondary_item_id,
			'component'         => $component,
		);
	}

	private function default_args() {
		return array(
			'exclude'  => false,
			'scope'    => 'groups',
			'per_page' => 3,
		);
	}

	public function test_stream_without_duplicates_is_returned_unchanged() {
		$activity = array(
			'activities' => array(
				$this->make_activity( 1, 'bpeo_create_event', 100, 'groups' ),
				$this->make_activity( 2, 'activity_update', 0, 'activity' ),
			),
			'total'      => 2,
		);

		$result = bpeo_remove_duplicates_from_activity_stream( $activity, $this->default_args() );

		$this->assertSame(
			array( 1, 2 ),
			array_values( wp_list_pluck( $result['activities'], 'id' ) )
		);
	}

	public function test_duplicate_event_items_are_deduplicated_keeping_canonical() {
		$activity = array(
			'activities' => array(
				$this->make_activity( 1, 'bpeo_create_event', 100, 'groups' ),
				$this->make_activity( 2, 'bpeo_create_event', 100, 'events' ),
				$this->make_activity( 3, 'activity_update', 0, 'activity' ),
			),
			'total'      => 3,
		);

		$result = bpeo_remove_duplicates_from_activity_stream( $activity, $this->default_args() );

		$this->assertSame(
			array( 2, 3 ),
			array_values( wp_list_pluck( $result['activities'], 'id' ) )
		);
	}

	public function test_first_duplicate_is_kept_when_no_canonical_item_exists() {
		$activity = array(
			'activities' => array(
				$this->make_activity( 1, 'bpeo_create_event', 100, 'groups' ),
				$this->make_activity( 2, 'bpeo_create_event', 100, 'groups' ),
			),
			'total'      => 2,
		);

		$result = bpeo_remove_duplicates_from_activity_stream( $activity, $this->default_args() );

		$this->assertSame(
			array( 1 ),
			array_values( wp_list_pluck( $result['activities'], 'id' ) )
		);
	}

	public function test_backfill_results_are_trimmed_to_originally_requested_count() {
		$GLOBALS['_mock_bp_activity_get_callback'] = function ( $args ) {
			return array(
				'activities' => array(
					$this->make_activity( 4, 'activity_update', 0, 'activity' ),
					$this->make_activity( 5, 'activity_update', 0, 'activity' ),
				),
				'total'      => 2,
			);
		};

		$activity = array(
			'activities' => array(
				$this->make_activity( 1, 'bpeo_create_event', 100, 'groups' ),
				$this->make_activity( 2, 'bpeo_create_event', 100, 'events' ),
				$this->make_activity( 3, 'activity_update', 0, 'activity' ),
			),
			'total'      => 3,
		);

		$result = bpeo_remove_duplicates_from_activity_stream( $activity, $this->default_args() );

		$this->assertCount( 3, $result['activities'] );
		$this->assertSame(
			array( 2, 3, 4 ),
			array_values( wp_list_pluck( $result['activities'], 'id' ) )
		);
	}
}
