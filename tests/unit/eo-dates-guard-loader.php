<?php
/**
 * Loads the Event Organiser occurrence-date cache guard mu-plugin with stubs
 * for the plugin functions it calls. Relies on the _hc_mock() helper and the
 * WP_Query stand-in from group-permissions-loader.php, so it must be required
 * after that loader.
 */

// ---------------------------------------------------------------------------
// Event Organiser stubs.
// ---------------------------------------------------------------------------

if ( ! function_exists( 'eo_get_blog_timezone' ) ) {
	function eo_get_blog_timezone() {
		return new DateTimeZone( _hc_mock( 'blog_timezone', 'UTC' ) );
	}
}

if ( ! function_exists( 'eventorganiser_is_event_query' ) ) {
	function eventorganiser_is_event_query( $query, $exclusive = false ) {
		$post_types = $query->get( 'post_type' );
		if ( 'event' === $post_types || array( 'event' ) === $post_types ) {
			return true;
		}
		// Non-exclusive match: a category archive when 'category' is attached
		// to the event post type (see eo_is_event_taxonomy()).
		return ! $exclusive && empty( $post_types ) && '' !== $query->get( 'category_name' );
	}
}

if ( ! function_exists( '_eventorganiser_update_event_dates_cache' ) ) {
	/**
	 * Stand-in for the plugin's own filter so the hc-custom swap sees it as
	 * present. Never meant to run in tests.
	 */
	function _eventorganiser_update_event_dates_cache( $events, $query ) {
		return $events;
	}
}

require_once dirname( __DIR__, 2 ) . '/mu-plugins/event-organiser-dates-guard.php';
