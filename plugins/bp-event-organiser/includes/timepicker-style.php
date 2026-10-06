<?php
/**
 * Frontend override styles for the Event Organiser jQuery UI timepicker.
 *
 * The active theme applies a global `box-sizing: border-box` reset, which
 * collapses the timepicker's hour/minute cells (sized for the default
 * content-box model), leaving the grid crowded and unreadable. This loads a
 * small stylesheet, scoped to the timepicker container, that restores
 * comfortable cell sizing on BuddyPress event pages.
 */

/**
 * Enqueue the timepicker override stylesheet on BPEO component pages.
 *
 * @return bool True if the stylesheet was enqueued, false otherwise.
 */
function bpeo_enqueue_timepicker_style() {
	if ( ! bpeo_is_component() ) {
		return false;
	}

	wp_enqueue_style(
		'bpeo-timepicker',
		BUDDYPRESS_EVENT_ORGANISER_URL . 'assets/css/bpeo-timepicker.css',
		array(),
		BUDDYPRESS_EVENT_ORGANISER_VERSION
	);

	return true;
}
add_action( 'wp_enqueue_scripts', 'bpeo_enqueue_timepicker_style', 20 );
