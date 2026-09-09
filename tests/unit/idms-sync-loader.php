<?php
/**
 * Loads the real IDMS Sync CLI mu-plugin with stubs sufficient to unit-test
 * its member-type sync function in isolation.
 *
 * The mu-plugin only defines its code inside a WP_CLI guard, so the loader
 * provides the constant and a recording WP_CLI stub before requiring it.
 */

if ( ! defined( 'WP_CLI' ) ) {
	define( 'WP_CLI', true );
}

if ( ! class_exists( 'WP_CLI' ) ) {
	class WP_CLI {
		public static function add_command( $name, $callable ) {}
		public static function log( $message ) {}
		public static function success( $message ) {}
		public static function print_value( $value, $args = array() ) {}
		public static function error( $message ) {
			$GLOBALS['_wp_cli_errors'][] = $message;
		}
	}
}

if ( ! function_exists( 'get_user_by' ) ) {
	function get_user_by( $field, $value ) {
		return $GLOBALS['_mock_users'][ $value ] ?? false;
	}
}

if ( ! function_exists( 'bp_set_member_type' ) ) {
	function bp_set_member_type( $user_id, $type, $append = false ) {
		$GLOBALS['_bp_set_calls'][] = array(
			'user_id' => $user_id,
			'type'    => $type,
			'append'  => $append,
		);
		return $type;
	}
}

if ( ! function_exists( 'bp_remove_member_type' ) ) {
	function bp_remove_member_type( $user_id, $type ) {
		$GLOBALS['_bp_remove_calls'][] = array(
			'user_id' => $user_id,
			'type'    => $type,
		);
		return true;
	}
}

if ( ! function_exists( 'bp_get_member_type' ) ) {
	function bp_get_member_type( $user_id, $single = true ) {
		return $GLOBALS['_bp_current_types'] ?? false;
	}
}

require_once dirname( __DIR__, 2 ) . '/mu-plugins/idms-api.php';
