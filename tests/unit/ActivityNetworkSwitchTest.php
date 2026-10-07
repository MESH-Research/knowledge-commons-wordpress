<?php
/**
 * Tests for the blog switch around bbPress activity action-string regeneration.
 *
 * BuddyPress regenerates bbPress action strings on every activity fetch. For a
 * topic or reply created on another network the topic and forum posts do not
 * exist on the current blog, so the lookups come back null. Switching to the
 * activity's network for the duration of the format callback fixes that.
 */

use PHPUnit\Framework\TestCase;

class ActivityNetworkSwitchTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_hc_mock'] = array(
			'current_blog_id' => 1,
			'blog_stack'      => array(),
			'activity_meta'   => array(
				42 => array( 'society_id' => 'mocksoc' ),
				43 => array( 'society_id' => '' ),
				45 => array( 'society_id' => 'mockhome' ),
			),
		);
		unset( $GLOBALS['hcommons_activity_network_switches'] );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['hcommons_activity_network_switches'] );
	}

	private function activity( $id, $component = 'bbpress', $type = 'bbp_topic_create' ) {
		return (object) array(
			'id'        => $id,
			'component' => $component,
			'type'      => $type,
			'action'    => '',
		);
	}

	public function test_switches_to_network_of_cross_network_forum_activity() {
		hcommons_switch_to_activity_network( '', $this->activity( 42 ) );

		$this->assertSame( 7, get_current_blog_id() );
	}

	public function test_switch_returns_action_unchanged() {
		$this->assertSame( 'the action', hcommons_switch_to_activity_network( 'the action', $this->activity( 42 ) ) );
	}

	public function test_does_not_switch_for_activity_on_current_network() {
		hcommons_switch_to_activity_network( '', $this->activity( 45 ) );

		$this->assertSame( 1, get_current_blog_id() );
	}

	public function test_does_not_switch_when_network_cannot_be_resolved() {
		hcommons_switch_to_activity_network( '', $this->activity( 43 ) );

		$this->assertSame( 1, get_current_blog_id() );
	}

	public function test_does_not_switch_for_non_forum_activity() {
		hcommons_switch_to_activity_network( '', $this->activity( 42, 'groups', 'created_group' ) );

		$this->assertSame( 1, get_current_blog_id() );
	}

	public function test_restore_returns_to_original_blog_after_switch() {
		$activity = $this->activity( 42 );

		hcommons_switch_to_activity_network( '', $activity );
		hcommons_restore_from_activity_network( '', $activity );

		$this->assertSame( 1, get_current_blog_id() );
	}

	public function test_restore_returns_action_unchanged() {
		$activity = $this->activity( 42 );

		hcommons_switch_to_activity_network( '', $activity );

		$this->assertSame( 'the action', hcommons_restore_from_activity_network( 'the action', $activity ) );
	}

	public function test_restore_without_prior_switch_leaves_blog_alone() {
		// Simulate an outer switch_to_blog() made by someone else.
		switch_to_blog( 3 );

		hcommons_restore_from_activity_network( '', $this->activity( 45 ) );

		$this->assertSame( 3, get_current_blog_id() );
	}

	public function test_consecutive_activities_each_restore_correctly() {
		$cross = $this->activity( 42 );
		$local = $this->activity( 45 );

		hcommons_switch_to_activity_network( '', $cross );
		$inside_cross = get_current_blog_id();
		hcommons_restore_from_activity_network( '', $cross );

		hcommons_switch_to_activity_network( '', $local );
		$inside_local = get_current_blog_id();
		hcommons_restore_from_activity_network( '', $local );

		$this->assertSame( array( 7, 1, 1 ), array( $inside_cross, $inside_local, get_current_blog_id() ) );
	}
}
