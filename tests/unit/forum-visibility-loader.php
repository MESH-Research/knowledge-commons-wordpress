<?php
/**
 * Stubs for the WP/BP/bbPress functions used by
 * plugins/hc-custom/includes/bbpress-forum-visibility.php, then loads it.
 *
 * Shares the $GLOBALS['_hc_mock'] convention of group-permissions-loader.php.
 */

if ( ! function_exists( '_hc_mock' ) ) {
	function _hc_mock( $key, $default = null ) {
		return isset( $GLOBALS['_hc_mock'][ $key ] ) ? $GLOBALS['_hc_mock'][ $key ] : $default;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['_hc_registered_filters'][ $hook ][] = array( 'callback' => $callback, 'priority' => $priority );
		return true;
	}
}
if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		return add_filter( $hook, $callback, $priority, $accepted_args );
	}
}
if ( ! function_exists( 'remove_filter' ) ) {
	function remove_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		return true;
	}
}
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value ) {
		return $value;
	}
}
if ( ! function_exists( 'wp_parse_id_list' ) ) {
	function wp_parse_id_list( $list ) {
		if ( ! is_array( $list ) ) {
			$list = preg_split( '/[\s,]+/', (string) $list, -1, PREG_SPLIT_NO_EMPTY );
		}
		return array_values( array_unique( array_map( 'absint', $list ) ) );
	}
}
if ( ! function_exists( 'wp_parse_list' ) ) {
	function wp_parse_list( $list ) {
		if ( is_array( $list ) ) {
			return $list;
		}
		return preg_split( '/[\s,]+/', (string) $list, -1, PREG_SPLIT_NO_EMPTY );
	}
}
if ( ! function_exists( 'absint' ) ) {
	function absint( $n ) {
		return abs( (int) $n );
	}
}
if ( ! function_exists( 'esc_sql' ) ) {
	function esc_sql( $s ) {
		return addslashes( $s );
	}
}
if ( ! function_exists( 'bbp_get_forum_post_type' ) ) {
	function bbp_get_forum_post_type() { return 'forum'; }
}
if ( ! function_exists( 'bbp_get_topic_post_type' ) ) {
	function bbp_get_topic_post_type() { return 'topic'; }
}
if ( ! function_exists( 'bbp_get_reply_post_type' ) ) {
	function bbp_get_reply_post_type() { return 'reply'; }
}
if ( ! function_exists( 'bbp_get_private_status_id' ) ) {
	function bbp_get_private_status_id() { return 'private'; }
}
if ( ! function_exists( 'bbp_get_hidden_status_id' ) ) {
	function bbp_get_hidden_status_id() { return 'hidden'; }
}
if ( ! function_exists( 'bbp_get_current_user_id' ) ) {
	function bbp_get_current_user_id() { return (int) _hc_mock( 'current_user_id', 0 ); }
}
if ( ! function_exists( 'get_current_blog_id' ) ) {
	function get_current_blog_id() { return 1; }
}

require_once dirname( __DIR__, 2 ) . '/plugins/hc-custom/includes/bbpress-forum-visibility.php';
