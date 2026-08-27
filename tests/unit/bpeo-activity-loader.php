<?php
/**
 * Loads the real bp-event-organiser activity functions with WP/BP stubs
 * sufficient for unit-testing bpeo_remove_duplicates_from_activity_stream().
 */

if ( ! function_exists( 'wp_list_pluck' ) ) {
	function wp_list_pluck( $input_list, $field ) {
		$output = array();
		foreach ( $input_list as $key => $value ) {
			$output[ $key ] = is_object( $value ) ? $value->{$field} : $value[ $field ];
		}
		return $output;
	}
}

if ( ! function_exists( 'bp_activity_get' ) ) {
	/**
	 * Overridable per-test via $GLOBALS['_mock_bp_activity_get_callback'].
	 * Defaults to an empty result set (nothing left to backfill).
	 */
	function bp_activity_get( $args = array() ) {
		if ( isset( $GLOBALS['_mock_bp_activity_get_callback'] ) ) {
			return call_user_func( $GLOBALS['_mock_bp_activity_get_callback'], $args );
		}
		return array(
			'activities' => array(),
			'total'      => 0,
		);
	}
}

require_once dirname( __DIR__, 2 ) . '/plugins/bp-event-organiser/includes/activity.php';
