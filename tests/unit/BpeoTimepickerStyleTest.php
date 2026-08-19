<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../plugins/bp-event-organiser/includes/timepicker-style.php';

class BpeoTimepickerStyleTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_enqueued_styles']       = [];
		$GLOBALS['_mock_bpeo_is_component'] = false;

		if ( ! defined( 'BUDDYPRESS_EVENT_ORGANISER_URL' ) ) {
			define( 'BUDDYPRESS_EVENT_ORGANISER_URL', 'https://example.org/wp-content/plugins/bp-event-organiser/' );
		}
		if ( ! defined( 'BUDDYPRESS_EVENT_ORGANISER_VERSION' ) ) {
			define( 'BUDDYPRESS_EVENT_ORGANISER_VERSION', '0.2' );
		}
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_enqueued_styles'], $GLOBALS['_mock_bpeo_is_component'] );
		parent::tearDown();
	}

	/**
	 * On a BuddyPress events component page the override stylesheet is enqueued.
	 */
	public function test_enqueues_stylesheet_on_event_component_page(): void {
		$GLOBALS['_mock_bpeo_is_component'] = true;

		$this->assertTrue( bpeo_enqueue_timepicker_style() );
		$this->assertArrayHasKey( 'bpeo-timepicker', $GLOBALS['_enqueued_styles'] );
	}

	/**
	 * The enqueued stylesheet is a CSS file served from the plugin's own assets.
	 */
	public function test_stylesheet_src_is_plugin_css_asset(): void {
		$GLOBALS['_mock_bpeo_is_component'] = true;

		bpeo_enqueue_timepicker_style();

		$style = $GLOBALS['_enqueued_styles']['bpeo-timepicker'];
		$this->assertStringStartsWith( BUDDYPRESS_EVENT_ORGANISER_URL, $style['src'] );
		$this->assertStringEndsWith( '.css', $style['src'] );

		// The referenced asset must actually exist in the plugin.
		$relative = substr( $style['src'], strlen( BUDDYPRESS_EVENT_ORGANISER_URL ) );
		$this->assertFileExists(
			__DIR__ . '/../../plugins/bp-event-organiser/' . $relative
		);
	}

	/**
	 * Outside the events component nothing is enqueued.
	 */
	public function test_does_not_enqueue_outside_event_component(): void {
		$GLOBALS['_mock_bpeo_is_component'] = false;

		$this->assertFalse( bpeo_enqueue_timepicker_style() );
		$this->assertArrayNotHasKey( 'bpeo-timepicker', $GLOBALS['_enqueued_styles'] );
	}
}
