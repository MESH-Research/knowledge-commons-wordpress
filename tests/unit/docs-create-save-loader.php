<?php
/**
 * Loader for the docs create-save current-doc unit tests.
 *
 * The code under test lives in plugins/hc-custom/includes/buddypress-docs.php,
 * which is already loaded (with its WordPress/BuddyPress stubs) by the
 * group-permissions and group-nav loaders. This loader only adds the stubs
 * that the create-save tests additionally need.
 */

require_once __DIR__ . '/group-permissions-loader.php';
require_once __DIR__ . '/group-nav-loader.php';

if ( ! function_exists( 'bp_docs_get_post_type_name' ) ) {
	function bp_docs_get_post_type_name() {
		return 'bp_doc';
	}
}

if ( ! function_exists( 'get_post_status' ) ) {
	/**
	 * Status of a mocked post, driven by $GLOBALS['_hc_mock']['post_statuses'].
	 */
	function get_post_status( $post = null ) {
		$post_id = is_object( $post ) ? ( $post->ID ?? 0 ) : (int) $post;
		return $GLOBALS['_hc_mock']['post_statuses'][ $post_id ] ?? false;
	}
}

if ( ! function_exists( '_test_apply_captured_filters' ) ) {
	/**
	 * Apply every callback captured for a hook, mimicking apply_filters().
	 */
	function _test_apply_captured_filters( $hook, $value ) {
		foreach ( $GLOBALS['_captured_filters'][ $hook ] ?? [] as $callback ) {
			$value = call_user_func( $callback, $value );
		}
		return $value;
	}
}
