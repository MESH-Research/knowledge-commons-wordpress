<?php
/**
 * Tests for group calendar event links.
 *
 * When the calendar is being built for a group, each event connected to that
 * group must link to the group's rendering of the event
 * (<group>/events/<slug>/) rather than the canonical event page. This has to
 * work inside the admin-ajax request that builds the calendar payload, where
 * BuddyPress has no group context and the group arrives as a request param.
 */

use PHPUnit\Framework\TestCase;

class BpeoGroupCalendarLinksTest extends TestCase {

	const CANONICAL = 'https://example.org/events/event/zoo-visit/';

	protected function setUp(): void {
		$GLOBALS['_hc_mock'] = array();
		$_GET                = array();

		$event             = new stdClass();
		$event->ID         = 7;
		$event->post_type  = 'event';
		$event->post_name  = 'zoo-visit';

		$GLOBALS['_hc_mock']['posts']                   = array( 7 => $event );
		$GLOBALS['_hc_mock']['event_groups']            = array( 7 => array( 50 ) );
		$GLOBALS['_hc_mock']['events_group_permalinks'] = array(
			50 => 'https://example.org/groups/mpes-grp/events/',
			60 => 'https://example.org/groups/other-grp/events/',
		);
	}

	protected function tearDown(): void {
		$_GET = array();
	}

	public function test_link_rewritten_to_group_event_url_in_ajax_context() {
		$GLOBALS['_hc_mock']['is_group'] = false;
		$_GET['bp_group']                = '50';

		$link = hc_custom_bpeo_filter_calendar_event_link_for_group( self::CANONICAL, 7, 1 );

		$this->assertSame( 'https://example.org/groups/mpes-grp/events/zoo-visit/', $link );
	}

	public function test_link_rewritten_to_group_event_url_in_page_context() {
		$GLOBALS['_hc_mock']['is_group']         = true;
		$GLOBALS['_hc_mock']['current_group_id'] = 50;

		$link = hc_custom_bpeo_filter_calendar_event_link_for_group( self::CANONICAL, 7 );

		$this->assertSame( 'https://example.org/groups/mpes-grp/events/zoo-visit/', $link );
	}

	public function test_link_uses_the_requested_group_not_another_connected_group() {
		$GLOBALS['_hc_mock']['is_group']     = false;
		$GLOBALS['_hc_mock']['event_groups'] = array( 7 => array( 50, 60 ) );
		$_GET['bp_group']                    = '60';

		$link = hc_custom_bpeo_filter_calendar_event_link_for_group( self::CANONICAL, 7, 1 );

		$this->assertSame( 'https://example.org/groups/other-grp/events/zoo-visit/', $link );
	}

	public function test_link_unchanged_outside_group_context() {
		$GLOBALS['_hc_mock']['is_group'] = false;

		$this->assertSame( self::CANONICAL, hc_custom_bpeo_filter_calendar_event_link_for_group( self::CANONICAL, 7, 1 ) );
	}

	public function test_link_unchanged_when_event_not_connected_to_requested_group() {
		$GLOBALS['_hc_mock']['is_group'] = false;
		$_GET['bp_group']                = '60';

		$this->assertSame( self::CANONICAL, hc_custom_bpeo_filter_calendar_event_link_for_group( self::CANONICAL, 7, 1 ) );
	}

	public function test_link_unchanged_when_event_cannot_be_loaded() {
		$GLOBALS['_hc_mock']['is_group'] = false;
		$GLOBALS['_hc_mock']['posts']    = array();
		$_GET['bp_group']                = '50';

		$this->assertSame( self::CANONICAL, hc_custom_bpeo_filter_calendar_event_link_for_group( self::CANONICAL, 7, 1 ) );
	}

	public function test_link_unchanged_when_no_event_id_given() {
		$GLOBALS['_hc_mock']['is_group'] = false;
		$_GET['bp_group']                = '50';

		$this->assertSame( self::CANONICAL, hc_custom_bpeo_filter_calendar_event_link_for_group( self::CANONICAL ) );
	}
}
