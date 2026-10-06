<?php
/**
 * Loads the real humanities-commons network group scoping include with stubs
 * sufficient to unit-test its policy functions in isolation.
 *
 * The include registers filters at load time; the unit bootstrap provides a
 * no-op add_filter(), so requiring the file is safe.
 */

if ( ! class_exists( 'Humanities_Commons' ) ) {
	class Humanities_Commons {
		public static $society_id;
	}
}

if ( ! class_exists( 'BP_Groups_Group' ) ) {
	/**
	 * Stand-in for the BuddyPress groups data layer. Behaviour is injected per
	 * test via $GLOBALS['_mock_bp_groups_group_get'], a callback receiving the
	 * query args and returning array( 'groups' => [...], 'total' => n ).
	 */
	class BP_Groups_Group {
		public static function get( $args = array() ) {
			if ( isset( $GLOBALS['_mock_bp_groups_group_get'] ) ) {
				return call_user_func( $GLOBALS['_mock_bp_groups_group_get'], $args );
			}
			return array(
				'groups' => array(),
				'total'  => 0,
			);
		}
	}
}

if ( ! function_exists( 'bp_loggedin_user_id' ) ) {
	function bp_loggedin_user_id() {
		return $GLOBALS['_mock_loggedin_user_id'] ?? 0;
	}
}

if ( ! function_exists( 'bp_displayed_user_id' ) ) {
	function bp_displayed_user_id() {
		return $GLOBALS['_mock_displayed_user_id'] ?? 0;
	}
}

if ( ! function_exists( 'bp_current_user_can' ) ) {
	function bp_current_user_can( $capability ) {
		return ! empty( $GLOBALS['_mock_current_user_can'][ $capability ] );
	}
}

if ( ! function_exists( 'bp_is_user' ) ) {
	function bp_is_user() {
		return ! empty( $GLOBALS['_mock_bp_is_user'] );
	}
}

if ( ! function_exists( 'is_super_admin' ) ) {
	function is_super_admin() {
		return ! empty( $GLOBALS['_mock_is_super_admin'] );
	}
}

require_once dirname( __DIR__, 2 ) . '/plugins/humanities-commons/network-group-scope.php';
