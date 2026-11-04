<?php
/**
 * Signup page URL for the Commons.
 *
 * Registration happens through the external enrollment flow linked from each
 * network's /membership/ page, so BuddyPress's signup URL has to point there.
 * The URL must be absolute: plugins such as Invite Anyone embed it in emails,
 * where a bare "/membership/" is not resolvable and ends up rendered as
 * "https://membership/".
 *
 * @package Humanities Commons
 * @subpackage Configuration
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build the absolute membership page URL for a network root URL.
 *
 * @param string $root_url Root URL of the current network, with or without a trailing slash.
 * @return string
 */
function hcommons_membership_page_url( $root_url ) {
	return rtrim( (string) $root_url, '/' ) . '/membership/';
}

/**
 * Filter callback for 'bp_get_signup_page'.
 *
 * @param string $url URL BuddyPress computed; ignored.
 * @return string Absolute URL of the current network's membership page.
 */
function hcommons_signup_page_url( $url = '' ) {
	if ( function_exists( 'bp_get_root_url' ) ) {
		$root_url = bp_get_root_url();
	} else {
		$root_url = network_home_url( '/' );
	}

	return hcommons_membership_page_url( $root_url );
}
