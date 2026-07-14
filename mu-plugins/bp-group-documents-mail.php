<?php
/**
 * Plugin Name: BP Group Documents — network-correct mail subjects
 * Description: Restores the correct network name in bp-group-documents upload
 *              notification subjects. Upstream hardcodes blog 1, which is wrong
 *              on a multi-network install.
 *
 * bp-group-documents builds its subject as:
 *
 *     '[' . get_blog_option( 1, 'blogname' ) . '] A document was uploaded to X'
 *
 * On wp-multi-network that stamps the primary network's name onto mail sent
 * from every other network. Upstream exposes no filter for the subject, so we
 * rewrite it on wp_mail, scoped to the window in which the plugin's own
 * notification callback runs (bp_group_documents_add_success @ 10).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Replace a leading bracketed prefix with one naming $blogname.
 *
 * Subjects without a leading bracketed prefix are returned untouched.
 */
function hc_bpgd_rewrite_subject_prefix( string $subject, string $blogname ): string {
	if ( '' === $subject || '[' !== $subject[0] ) {
		return $subject;
	}

	$close = strpos( $subject, ']' );
	if ( false === $close ) {
		return $subject;
	}

	// Built by concatenation, not preg_replace: a blogname may contain
	// characters ($1, backslashes) that a replacement pattern would eat.
	return '[' . $blogname . '] ' . ltrim( substr( $subject, $close + 1 ), ' ' );
}

/**
 * Rewrite the subject of mail sent during a bp-group-documents upload
 * notification so that it names the current network's root blog.
 *
 * @param array $atts wp_mail arguments (to, subject, message, headers, attachments).
 */
function hc_bpgd_filter_mail( array $atts ): array {
	if ( ! hc_bpgd_is_notifying() ) {
		return $atts;
	}

	if ( ! isset( $atts['subject'] ) || ! is_string( $atts['subject'] ) ) {
		return $atts;
	}

	if ( ! function_exists( 'bp_get_root_blog_id' ) ) {
		return $atts;
	}

	$blogname = get_blog_option( bp_get_root_blog_id(), 'blogname' );
	if ( ! is_string( $blogname ) || '' === $blogname ) {
		return $atts;
	}

	$atts['subject'] = hc_bpgd_rewrite_subject_prefix( $atts['subject'], $blogname );

	return $atts;
}

/** Mark the start of the plugin's notification callback. */
function hc_bpgd_begin_notification(): void {
	$GLOBALS['hc_bpgd_notifying'] = true;
}

/** Mark the end of the plugin's notification callback. */
function hc_bpgd_end_notification(): void {
	$GLOBALS['hc_bpgd_notifying'] = false;
}

/** Whether a bp-group-documents notification is currently being sent. */
function hc_bpgd_is_notifying(): bool {
	return ! empty( $GLOBALS['hc_bpgd_notifying'] );
}

// The plugin's own notifier runs at priority 10; bracket it.
add_action( 'bp_group_documents_add_success', 'hc_bpgd_begin_notification', 9 );
add_action( 'bp_group_documents_add_success', 'hc_bpgd_end_notification', 11 );
add_filter( 'wp_mail', 'hc_bpgd_filter_mail' );
