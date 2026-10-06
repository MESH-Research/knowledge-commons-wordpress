<?php
/**
 * Enqueue a shim that stops the event date/time pickers scrolling the page
 * to the top when a date or time is selected.
 *
 * @package bp-event-organiser
 */

/**
 * Enqueue the date/time picker scroll fix on event edit screens.
 *
 * @return string|false The enqueued script handle, or false when not on an
 *                      event edit screen.
 */
function bpeo_enqueue_datepicker_scroll_fix() {
	// Only load alongside Event Organiser's event edit form assets. These are
	// enqueued (at default priority) by the frontend admin screens used for
	// creating and editing group and member events.
	if ( ! wp_script_is( 'eo_event' ) && ! wp_script_is( 'eo-edit-event-controller' ) ) {
		return false;
	}

	wp_enqueue_script(
		'bpeo-datepicker-scroll-fix',
		BUDDYPRESS_EVENT_ORGANISER_URL . 'assets/js/datepicker-scroll-fix.js',
		array(),
		BUDDYPRESS_EVENT_ORGANISER_VERSION,
		true
	);

	return 'bpeo-datepicker-scroll-fix';
}
add_action( 'wp_enqueue_scripts', 'bpeo_enqueue_datepicker_scroll_fix', 20 );
