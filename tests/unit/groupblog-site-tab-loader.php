<?php
/**
 * Loads plugins/hc-custom/includes/bp-groupblog.php for unit testing.
 *
 * Relies on the shared stubs from the bootstrap and group-permissions loader
 * (_hc_mock(), bp_is_group(), bp_get_current_group_id(), buddypress(),
 * groups_get_groupmeta()). Adds the multisite helpers the group site tab code
 * needs, driven by:
 *
 *  - $GLOBALS['_mock_home_urls'][ $blog_id ]  => home URL for a blog
 *  - $GLOBALS['_mock_site_options'][ $name ]  => network option values
 */

if ( ! function_exists( 'get_home_url' ) ) {
	function get_home_url( $blog_id = null, $path = '', $scheme = null ) {
		$url = $GLOBALS['_mock_home_urls'][ $blog_id ] ?? '';
		if ( $url && $path ) {
			$url = rtrim( $url, '/' ) . '/' . ltrim( $path, '/' );
		}
		return $url;
	}
}

if ( ! function_exists( 'get_site_option' ) ) {
	function get_site_option( $option, $default = false ) {
		return $GLOBALS['_mock_site_options'][ $option ] ?? $default;
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( $url, $component );
	}
}

if ( ! function_exists( 'bp_get_current_group_slug' ) ) {
	function bp_get_current_group_slug() {
		return (string) _hc_mock( 'current_group_slug', '' );
	}
}

if ( ! function_exists( 'get_groupblog_blog_id' ) ) {
	function get_groupblog_blog_id( $group_id = '' ) {
		if ( '' === $group_id ) {
			$group_id = bp_get_current_group_id();
		}
		return groups_get_groupmeta( $group_id, 'groupblog_blog_id' );
	}
}

if ( ! function_exists( 'remove_user_from_blog' ) ) {
	function remove_user_from_blog( $user_id, $blog_id = 0, $reassign = 0 ) {
		return true;
	}
}

require_once __DIR__ . '/../../plugins/hc-custom/includes/bp-groupblog.php';
