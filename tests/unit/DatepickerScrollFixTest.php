<?php

use PHPUnit\Framework\TestCase;

if ( ! defined( 'BUDDYPRESS_EVENT_ORGANISER_URL' ) ) {
	define( 'BUDDYPRESS_EVENT_ORGANISER_URL', 'https://example.org/app/plugins/bp-event-organiser/' );
}
if ( ! defined( 'BUDDYPRESS_EVENT_ORGANISER_VERSION' ) ) {
	define( 'BUDDYPRESS_EVENT_ORGANISER_VERSION', '0.2' );
}

require_once __DIR__ . '/../../plugins/bp-event-organiser/includes/datepicker-scroll-fix.php';

class DatepickerScrollFixTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_enqueued_scripts'] = [];
		unset( $GLOBALS['_mock_wp_script_is_callback'] );
	}

	protected function tearDown(): void {
		$GLOBALS['_enqueued_scripts'] = [];
		unset( $GLOBALS['_mock_wp_script_is_callback'] );
	}

	/**
	 * On a screen where Event Organiser's event edit script is queued,
	 * the scroll-fix script is enqueued and its handle returned.
	 */
	public function testEnqueuesFixWhenEventEditScriptQueued(): void {
		$GLOBALS['_mock_wp_script_is_callback'] = function ( $handle, $status ) {
			return 'eo_event' === $handle;
		};

		$result = bpeo_enqueue_datepicker_scroll_fix();

		$this->assertSame( 'bpeo-datepicker-scroll-fix', $result );
		$this->assertArrayHasKey( 'bpeo-datepicker-scroll-fix', $GLOBALS['_enqueued_scripts'] );
	}

	/**
	 * The edit-event controller script implies the event edit form too.
	 */
	public function testEnqueuesFixWhenEditControllerQueued(): void {
		$GLOBALS['_mock_wp_script_is_callback'] = function ( $handle, $status ) {
			return 'eo-edit-event-controller' === $handle;
		};

		$result = bpeo_enqueue_datepicker_scroll_fix();

		$this->assertSame( 'bpeo-datepicker-scroll-fix', $result );
		$this->assertArrayHasKey( 'bpeo-datepicker-scroll-fix', $GLOBALS['_enqueued_scripts'] );
	}

	/**
	 * On any other page the fix is not enqueued and false is returned.
	 */
	public function testDoesNotEnqueueOnOtherPages(): void {
		$result = bpeo_enqueue_datepicker_scroll_fix();

		$this->assertFalse( $result );
		$this->assertSame( [], $GLOBALS['_enqueued_scripts'] );
	}

	/**
	 * The enqueued src points at a real asset shipped by the plugin,
	 * loaded in the footer with the plugin version.
	 */
	public function testEnqueuedScriptPointsToRealAsset(): void {
		$GLOBALS['_mock_wp_script_is_callback'] = function () {
			return true;
		};

		bpeo_enqueue_datepicker_scroll_fix();

		$script = $GLOBALS['_enqueued_scripts']['bpeo-datepicker-scroll-fix'];

		$this->assertStringStartsWith( BUDDYPRESS_EVENT_ORGANISER_URL, $script['src'] );

		$relative = substr( $script['src'], strlen( BUDDYPRESS_EVENT_ORGANISER_URL ) );
		$asset    = __DIR__ . '/../../plugins/bp-event-organiser/' . $relative;

		$this->assertFileExists( $asset );
		$this->assertGreaterThan( 0, filesize( $asset ) );
		$this->assertTrue( $script['in_footer'] );
		$this->assertSame( BUDDYPRESS_EVENT_ORGANISER_VERSION, $script['ver'] );
	}
}
