<?php
/**
 * Event Organiser occurrence-date cache guard.
 *
 * Event Organiser's _eventorganiser_update_event_dates_cache(), hooked to
 * 'the_posts', builds DateTime objects from the joined occurrence columns
 * without checking they exist. Two situations leave them empty:
 *
 *  - a category or tag archive, when that taxonomy is attached to the event
 *    post type: the plugin counts it as a (non-exclusive) event query, but its
 *    SELECT/JOIN filters only apply to exclusive ones, so rows are plain
 *    WP_Post objects whose magic getters answer '' for the columns;
 *  - an event post with no occurrence row, which the LEFT JOIN returns with
 *    NULL dates.
 *
 * Either way the plugin throws an uncaught DateMalformedStringException and
 * takes the page down. This swaps the filter for a null-safe copy once all
 * plugins have loaded. It is a must-use plugin so that it applies on every
 * site of every network, whether or not hc-custom is active there, and it
 * leaves the plugin itself unforked.
 *
 * @package Commons
 */

/**
 * Swap Event Organiser's occurrence-date cache priming for the null-safe copy.
 *
 * @see hc_custom_eo_update_event_dates_cache()
 */
function hc_custom_eo_replace_event_dates_cache_filter() {
	if ( ! function_exists( '_eventorganiser_update_event_dates_cache' ) ) {
		return;
	}
	remove_filter( 'the_posts', '_eventorganiser_update_event_dates_cache', 10 );
	add_filter( 'the_posts', 'hc_custom_eo_update_event_dates_cache', 10, 2 );
}
add_action( 'plugins_loaded', 'hc_custom_eo_replace_event_dates_cache_filter' );

/**
 * Null-safe replacement for _eventorganiser_update_event_dates_cache().
 *
 * Primes the per-event occurrence cache that eo_get_the_start() and friends
 * read from, exactly as the plugin does, but skips any row whose dates are
 * missing or unparseable instead of throwing.
 *
 * @param WP_Post[] $events The posts matched by the query.
 * @param WP_Query  $query  The query.
 * @return WP_Post[] The same posts, unchanged.
 */
function hc_custom_eo_update_event_dates_cache( $events, $query ) {
	if ( ! $events || ! eventorganiser_is_event_query( $query ) ) {
		return $events;
	}

	$tz = eo_get_blog_timezone();

	foreach ( $events as $event ) {
		if ( ! is_object( $event ) || ! isset( $event->ID, $event->occurrence_id ) ) {
			continue;
		}

		if ( empty( $event->StartDate ) || empty( $event->EndDate ) ) {
			continue;
		}

		$cache_key    = 'eventorganiser_occurrences_' . $event->ID;
		$cached_event = (array) wp_cache_get( $cache_key );

		if ( isset( $cached_event[ $event->occurrence_id ] ) ) {
			continue;
		}

		try {
			$cached_event[ $event->occurrence_id ] = array(
				'start' => new DateTime( $event->StartDate . ' ' . $event->StartTime, $tz ),
				'end'   => new DateTime( $event->EndDate . ' ' . $event->FinishTime, $tz ),
			);
		} catch ( Exception $e ) {
			continue;
		}

		wp_cache_set( $cache_key, $cached_event );
	}

	return $events;
}
