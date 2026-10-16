<?php
/**
 * Loads the cbox-auth group sync classes so the queueing logic can be tested
 * without WordPress, BuddyPress or the MLA API.
 *
 * WP-Cron scheduling is recorded in $GLOBALS['_mock_cron'] (hook => list of
 * [timestamp, args]). Group meta comes from the shared $GLOBALS['_mock_group_meta']
 * store in bootstrap.php. The current group id is _hc_mock( 'current_group_id' ).
 */

if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

if ( ! function_exists( 'bp_get_group_id' ) ) {
	function bp_get_group_id() {
		return (int) _hc_mock( 'current_group_id', 0 );
	}
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
	function wp_next_scheduled( $hook, $args = array() ) {
		foreach ( $GLOBALS['_mock_cron'][ $hook ] ?? array() as $event ) {
			if ( $event['args'] === $args ) {
				return $event['timestamp'];
			}
		}
		return false;
	}
}

if ( ! function_exists( 'wp_schedule_single_event' ) ) {
	function wp_schedule_single_event( $timestamp, $hook, $args = array(), $wp_error = false ) {
		$GLOBALS['_mock_cron'][ $hook ][] = array( 'timestamp' => $timestamp, 'args' => $args );
		return true;
	}
}

if ( ! function_exists( 'hcommons_write_error_log' ) ) {
	function hcommons_write_error_log( $level, $message ) {}
}

if ( ! function_exists( 'plugins_url' ) ) {
	function plugins_url( $path = '', $plugin = '' ) {
		return 'https://example.test/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}

$cbox = dirname( __DIR__, 2 ) . '/plugins/cbox-auth/includes/';
require_once $cbox . 'interface-http-driver.php';
require_once $cbox . 'class-base.php';
require_once $cbox . 'class-logger.php';
require_once $cbox . 'class-mla-api.php';
require_once $cbox . 'class-mla-group.php';
require_once $cbox . 'class-group-behavior.php';
