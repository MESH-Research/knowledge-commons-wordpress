<?php
/**
 * Restores accessible hover/focus contrast on the buttons used in the
 * BuddyPress group-creation wizard and the Create a Site page.
 *
 * bp-legacy's buddypress.css sets `color: #555` on button/submit hover while
 * the theme sets the hover background to dark green, leaving near-invisible
 * grey text on a dark background. This enqueues a small stylesheet that
 * re-asserts white text in those creation flows.
 *
 * @see https://github.com/MESH-Research/knowledge-commons-wordpress/issues/97
 */

if ( ! function_exists( 'hc_styles_is_creation_context' ) ) {
	/**
	 * Whether the current request is a group- or site-creation page.
	 *
	 * @return bool
	 */
	function hc_styles_is_creation_context() {
		$is_group_create = function_exists( 'bp_is_group_create' ) && bp_is_group_create();
		$is_site_create  = function_exists( 'bp_is_create_blog' ) && bp_is_create_blog();

		return $is_group_create || $is_site_create;
	}
}

if ( ! function_exists( 'hc_styles_enqueue_creation_button_hover_fix' ) ) {
	/**
	 * Enqueue the hover-contrast fix stylesheet on creation pages.
	 *
	 * @return bool True if the stylesheet was enqueued, false otherwise.
	 */
	function hc_styles_enqueue_creation_button_hover_fix() {
		if ( ! hc_styles_is_creation_context() ) {
			return false;
		}

		wp_enqueue_style(
			'hc-styles-creation-button-hover-fix',
			plugins_url( '/hc-styles/css/creation-button-hover-fix.css' )
		);

		return true;
	}
}

if ( function_exists( 'add_action' ) ) {
	add_action( 'wp_enqueue_scripts', 'hc_styles_enqueue_creation_button_hover_fix', 20 );
}
