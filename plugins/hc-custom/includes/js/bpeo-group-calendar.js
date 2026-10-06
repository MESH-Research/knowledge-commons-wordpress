/**
 * Send the group a calendar belongs to along with its event requests.
 *
 * Event Organiser fetches calendar events via admin-ajax, where BuddyPress has
 * no group context. bp-event-organiser already places 'bp_group' in the
 * localized calendar settings (eventorganiser.calendars); this forwards it on
 * the request so the server can scope the results to the group.
 */
( function ( window ) {
	'use strict';

	/**
	 * Map a calendar element ID ("#eo_fullcalendar_N") to its index in
	 * eventorganiser.calendars.
	 */
	function calendarIndexFromId( id ) {
		var match = /eo_fullcalendar_(\d+)/.exec( String( id || '' ) );
		return match ? parseInt( match[ 1 ], 10 ) - 1 : -1;
	}

	function addGroupToRequest( request, start, end, timezone, options ) {
		var calendars = window.eventorganiser && window.eventorganiser.calendars;
		var index     = calendarIndexFromId( options && options.id );
		var calendar  = calendars && index >= 0 ? calendars[ index ] : null;

		if ( calendar && calendar.bp_group ) {
			request.bp_group = calendar.bp_group;
		}

		return request;
	}

	var hooks = window.wp && window.wp.hooks;

	if ( ! hooks || typeof hooks.addFilter !== 'function' ) {
		return;
	}

	// Event Organiser ships a legacy hooks shim (event-manager.js) with the
	// signature addFilter( name, callback, priority ), but defers to WordPress
	// core's @wordpress/hooks when that is already on the page, which takes
	// addFilter( name, namespace, callback, priority ). Core exposes hasFilter;
	// the shim does not.
	if ( typeof hooks.hasFilter === 'function' ) {
		hooks.addFilter( 'eventorganiser.fullcalendar_request', 'hc-custom/bpeo-group-calendar', addGroupToRequest, 10 );
	} else {
		hooks.addFilter( 'eventorganiser.fullcalendar_request', addGroupToRequest, 10 );
	}
} )( window );
