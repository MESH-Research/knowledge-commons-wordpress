<?php
/**
 * Loads the hc-custom group cover image disabling code for unit testing,
 * with a capturing add_filter stub so hook registration can be exercised
 * behaviorally without loading WordPress.
 */

if ( ! isset( $GLOBALS['_captured_filters'] ) ) {
	$GLOBALS['_captured_filters'] = [];
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['_captured_filters'][ $hook ][] = $callback;
		return true;
	}
}

if ( ! function_exists( '_test_apply_captured_filters' ) ) {
	/**
	 * Minimal apply_filters equivalent: folds a value through every callback
	 * captured for a hook, mimicking how WordPress would resolve the filter.
	 */
	function _test_apply_captured_filters( $hook, $value ) {
		foreach ( $GLOBALS['_captured_filters'][ $hook ] ?? [] as $callback ) {
			$value = call_user_func( $callback, $value );
		}
		return $value;
	}
}

require_once __DIR__ . '/../../plugins/hc-custom/includes/buddypress/bp-groups-cover-image.php';
