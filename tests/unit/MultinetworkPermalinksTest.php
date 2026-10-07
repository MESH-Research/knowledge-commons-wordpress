<?php
/**
 * Tests for the multinetwork forum/topic permalink filters in hc-custom.
 *
 * The forum filter must only act while an activity loop is rendering an item,
 * must leave permalinks alone for activities on the current network, and must
 * never read a null activity or throw on a missing society constant.
 */

use PHPUnit\Framework\TestCase;

class MultinetworkPermalinksTest extends TestCase {

	const HOME_FORUM_LINK  = 'https://hc.test/forums/forum/general/';
	const GROUP_FORUM_LINK = 'https://hc.test/groups/my-group/forum/';
	const SOC_FORUM_LINK   = 'https://mocksoc.test/forums/forum/general/';
	const SOC_TOPIC_LINK   = 'https://mocksoc.test/forums/topic/hello/';

	protected function setUp(): void {
		$GLOBALS['_hc_mock'] = array(
			'current_blog_id'  => 1,
			'blog_stack'       => array(),
			'activity_meta'    => array(
				42 => array( 'society_id' => 'mocksoc' ),
				43 => array( 'society_id' => '' ),
				44 => array( 'society_id' => 'nosuchsociety' ),
				45 => array( 'society_id' => 'mockhome' ),
			),
			'forum_permalinks' => array(
				1 => array( 10 => self::HOME_FORUM_LINK ),
				7 => array( 10 => self::SOC_FORUM_LINK ),
			),
			'topic_permalinks' => array(
				7 => array( 20 => self::SOC_TOPIC_LINK ),
			),
		);
		unset( $GLOBALS['activities_template'], $GLOBALS['_mock_bp_activity_get_callback'] );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['activities_template'], $GLOBALS['_mock_bp_activity_get_callback'] );
	}

	/**
	 * Install a fake BP_Activity_Template in the global.
	 *
	 * @param int|null $activity_id Current item ID, or null for no current item.
	 * @param bool     $in_the_loop Whether the loop is mid-iteration.
	 */
	private function set_activity_template( $activity_id, $in_the_loop ) {
		$template              = new stdClass();
		$template->in_the_loop = $in_the_loop;
		$template->activity    = null === $activity_id ? null : (object) array( 'id' => $activity_id );

		$GLOBALS['activities_template'] = $template;
	}

	private function activity_lookup_returns( array $ids ) {
		$GLOBALS['_mock_bp_activity_get_callback'] = function () use ( $ids ) {
			$activities = array();
			foreach ( $ids as $id ) {
				$activities[] = (object) array( 'id' => $id );
			}
			return array(
				'activities' => $activities,
				'total'      => count( $activities ),
			);
		};
	}

	// -----------------------------------------------------------------------
	// hcommons_get_current_loop_activity_id()
	// -----------------------------------------------------------------------

	public function test_loop_activity_id_is_zero_when_no_template_exists() {
		$this->assertSame( 0, hcommons_get_current_loop_activity_id() );
	}

	public function test_loop_activity_id_is_zero_when_loop_has_no_items() {
		$this->set_activity_template( null, false );

		$this->assertSame( 0, hcommons_get_current_loop_activity_id() );
	}

	public function test_loop_activity_id_is_zero_after_loop_has_ended() {
		$this->set_activity_template( 42, false );

		$this->assertSame( 0, hcommons_get_current_loop_activity_id() );
	}

	public function test_loop_activity_id_is_current_item_inside_loop() {
		$this->set_activity_template( 42, true );

		$this->assertSame( 42, hcommons_get_current_loop_activity_id() );
	}

	// -----------------------------------------------------------------------
	// hcommons_get_activity_network_blog_id()
	// -----------------------------------------------------------------------

	public function test_network_blog_id_resolves_from_society_meta() {
		$this->assertSame( 7, hcommons_get_activity_network_blog_id( 42 ) );
	}

	public function test_network_blog_id_is_zero_when_society_meta_empty() {
		$this->assertSame( 0, hcommons_get_activity_network_blog_id( 43 ) );
	}

	public function test_network_blog_id_is_zero_when_society_constant_undefined() {
		$this->assertSame( 0, hcommons_get_activity_network_blog_id( 44 ) );
	}

	public function test_network_blog_id_is_zero_for_unknown_activity() {
		$this->assertSame( 0, hcommons_get_activity_network_blog_id( 999 ) );
	}

	// -----------------------------------------------------------------------
	// hcommons_fix_multinetwork_forum_permalinks()
	// -----------------------------------------------------------------------

	public function test_forum_permalink_unchanged_when_no_activity_loop_exists() {
		$this->assertSame(
			self::GROUP_FORUM_LINK,
			hcommons_fix_multinetwork_forum_permalinks( self::GROUP_FORUM_LINK, 10 )
		);
	}

	public function test_forum_permalink_unchanged_when_loop_has_no_items() {
		$this->set_activity_template( null, false );

		$this->assertSame(
			self::GROUP_FORUM_LINK,
			hcommons_fix_multinetwork_forum_permalinks( self::GROUP_FORUM_LINK, 10 )
		);
	}

	public function test_forum_permalink_unchanged_after_loop_has_ended() {
		$this->set_activity_template( 42, false );

		$this->assertSame(
			self::GROUP_FORUM_LINK,
			hcommons_fix_multinetwork_forum_permalinks( self::GROUP_FORUM_LINK, 10 )
		);
	}

	public function test_forum_permalink_rewritten_for_activity_on_other_network() {
		$this->set_activity_template( 42, true );

		$this->assertSame(
			self::SOC_FORUM_LINK,
			hcommons_fix_multinetwork_forum_permalinks( self::GROUP_FORUM_LINK, 10 )
		);
	}

	public function test_forum_permalink_unchanged_for_activity_on_current_network() {
		$this->set_activity_template( 45, true );

		$this->assertSame(
			self::GROUP_FORUM_LINK,
			hcommons_fix_multinetwork_forum_permalinks( self::GROUP_FORUM_LINK, 10 )
		);
	}

	public function test_forum_permalink_unchanged_when_society_meta_missing() {
		$this->set_activity_template( 43, true );

		$this->assertSame(
			self::GROUP_FORUM_LINK,
			hcommons_fix_multinetwork_forum_permalinks( self::GROUP_FORUM_LINK, 10 )
		);
	}

	public function test_forum_permalink_unchanged_when_society_constant_undefined() {
		$this->set_activity_template( 44, true );

		$this->assertSame(
			self::GROUP_FORUM_LINK,
			hcommons_fix_multinetwork_forum_permalinks( self::GROUP_FORUM_LINK, 10 )
		);
	}

	public function test_current_blog_is_restored_after_rewrite() {
		$this->set_activity_template( 42, true );

		hcommons_fix_multinetwork_forum_permalinks( self::GROUP_FORUM_LINK, 10 );

		$this->assertSame( 1, get_current_blog_id() );
	}

	// -----------------------------------------------------------------------
	// hcommons_fix_multinetwork_topic_permalinks()
	// -----------------------------------------------------------------------

	public function test_topic_permalink_unchanged_when_already_set() {
		$this->activity_lookup_returns( array( 42 ) );

		$this->assertSame(
			'https://hc.test/forums/topic/hello/',
			hcommons_fix_multinetwork_topic_permalinks( 'https://hc.test/forums/topic/hello/', 20 )
		);
	}

	public function test_topic_permalink_resolved_from_creation_activity_on_other_network() {
		$this->activity_lookup_returns( array( 42 ) );

		$this->assertSame( self::SOC_TOPIC_LINK, hcommons_fix_multinetwork_topic_permalinks( '', 20 ) );
	}

	public function test_topic_permalink_unchanged_when_no_creation_activity_found() {
		$this->assertSame( '', hcommons_fix_multinetwork_topic_permalinks( '', 20 ) );
	}

	public function test_topic_permalink_unchanged_when_society_meta_missing() {
		$this->activity_lookup_returns( array( 43 ) );

		$this->assertSame( '', hcommons_fix_multinetwork_topic_permalinks( '', 20 ) );
	}

	public function test_topic_permalink_unchanged_when_society_constant_undefined() {
		$this->activity_lookup_returns( array( 44 ) );

		$this->assertSame( '', hcommons_fix_multinetwork_topic_permalinks( '', 20 ) );
	}

	public function test_current_blog_is_restored_after_topic_rewrite() {
		$this->activity_lookup_returns( array( 42 ) );

		hcommons_fix_multinetwork_topic_permalinks( '', 20 );

		$this->assertSame( 1, get_current_blog_id() );
	}
}
