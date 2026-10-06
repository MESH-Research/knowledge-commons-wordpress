/**
 * Stop the event date/time pickers scrolling the page to the top.
 *
 * The jQuery UI datepicker and the Event Organiser timepicker render their
 * day/hour/minute cells as `<a href="#">`. Their click handlers are supposed
 * to `return false`, but if anything in the selection callbacks throws (or a
 * handler is missing), the browser follows the `#` href and jumps to the top
 * of the page.
 *
 * This capture-phase listener cancels that default navigation for hash-only
 * anchors inside the picker widgets. It does not stop propagation, so the
 * widgets' own click handling continues to work exactly as before.
 */
( function() {
	'use strict';

	if ( ! document.addEventListener || ! Element.prototype.closest ) {
		return;
	}

	var PICKER_CONTAINERS = '#ui-datepicker-div, #ui-timepicker-div, .ui-datepicker, .ui-timepicker';

	document.addEventListener( 'click', function( event ) {
		var target = event.target;

		if ( ! target || ! target.closest ) {
			return;
		}

		var anchor = target.closest( 'a' );

		if ( ! anchor ) {
			return;
		}

		var href = anchor.getAttribute( 'href' );

		if ( '#' !== href && '' !== href ) {
			return;
		}

		if ( anchor.closest( PICKER_CONTAINERS ) ) {
			event.preventDefault();
		}
	}, true );
} )();
