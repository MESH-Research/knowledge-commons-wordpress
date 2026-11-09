<?php
/**
 * Loads the humanities-commons signup page URL include with stubs for the
 * BuddyPress / WordPress URL helpers it relies on. The root URL is injected per
 * test through $GLOBALS['_mock_bp_root_url'].
 */

if ( ! function_exists( 'bp_get_root_url' ) ) {
	function bp_get_root_url() {
		return $GLOBALS['_mock_bp_root_url'] ?? 'http://example.test';
	}
}

if ( ! function_exists( 'network_home_url' ) ) {
	function network_home_url( $path = '' ) {
		return ( $GLOBALS['_mock_network_home_url'] ?? 'http://example.test' ) . $path;
	}
}

require_once dirname( __DIR__, 2 ) . '/plugins/humanities-commons/signup-page.php';
