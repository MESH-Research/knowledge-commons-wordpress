<?php
/**
 * Plugin Name: HC — network-correct mail subjects
 * Description: On this wp-multi-network install several plugins stamp the wrong
 *              network's name (the primary network / blog 1 / BP_ROOT_BLOG) into
 *              notification email subjects. This mu-plugin centralises the
 *              correction so mail names the CURRENT network. Add a handler here
 *              for each plugin that has this problem, so we keep a single
 *              multi-network mail-subject fix rather than one mu-plugin per plugin.
 *
 * Handlers:
 *   - bp-group-documents: upstream hardcodes get_blog_option( 1, ... ) and exposes
 *     no subject filter, so we bracket its notifier and rewrite on wp_mail().
 *   - buddypress-group-email-subscription (GES): sends via bp_send_email() (not
 *     wp_mail), but exposes subject filters that pass the blogname, so we hook
 *     those directly.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------- *
 *  Shared helpers
 * -------------------------------------------------------------------------- */

/**
 * The blogname of the current network's root blog — the name mail *should* carry.
 */
function hc_mnsm_current_network_blogname(): string {
	if ( ! function_exists( 'bp_get_root_blog_id' ) ) {
		return '';
	}

	$blogname = get_blog_option( bp_get_root_blog_id(), 'blogname' );

	return is_string( $blogname ) ? $blogname : '';
}

/**
 * Replace a leading bracketed prefix with one naming $blogname.
 * Content-agnostic: used where we don't know the wrong name (bp-group-documents).
 * Subjects without a leading bracketed prefix are returned untouched.
 */
function hc_mnsm_rewrite_leading_prefix( string $subject, string $blogname ): string {
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
 * Literally swap one bracketed token for another anywhere in the subject.
 * Used where the plugin hands us the wrong blogname (GES). Literal (str_replace),
 * so a blogname containing $1 or a backslash survives intact. No-op when the
 * tokens are equal or the wrong token is empty.
 */
function hc_mnsm_swap_token( string $subject, string $wrong_token, string $correct_token ): string {
	if ( '' === $wrong_token || $wrong_token === $correct_token ) {
		return $subject;
	}

	return str_replace( $wrong_token, $correct_token, $subject );
}

/* -------------------------------------------------------------------------- *
 *  Handler: bp-group-documents (wp_mail window)
 * -------------------------------------------------------------------------- */

function hc_mnsm_bpgd_filter_mail( array $atts ): array {
	if ( ! hc_mnsm_bpgd_is_notifying() ) {
		return $atts;
	}

	if ( ! isset( $atts['subject'] ) || ! is_string( $atts['subject'] ) ) {
		return $atts;
	}

	$blogname = hc_mnsm_current_network_blogname();
	if ( '' === $blogname ) {
		return $atts;
	}

	$atts['subject'] = hc_mnsm_rewrite_leading_prefix( $atts['subject'], $blogname );

	return $atts;
}

function hc_mnsm_bpgd_begin_notification(): void {
	$GLOBALS['hc_mnsm_bpgd_notifying'] = true;
}

function hc_mnsm_bpgd_end_notification(): void {
	$GLOBALS['hc_mnsm_bpgd_notifying'] = false;
}

function hc_mnsm_bpgd_is_notifying(): bool {
	return ! empty( $GLOBALS['hc_mnsm_bpgd_notifying'] );
}

/* -------------------------------------------------------------------------- *
 *  Handler: buddypress-group-email-subscription (subject filters)
 * -------------------------------------------------------------------------- */

/**
 * Fix the blogname in a GES single-activity notification subject.
 * GES passes $blogname already bracketed, e.g. '[Primary Network]'.
 *
 * @param string $subject  Subject, e.g. "Bob posted an update [Primary Network]".
 * @param string $action   The activity action text (unused; kept for filter signature).
 * @param string $blogname The bracketed wrong blogname GES inserted.
 */
function hc_mnsm_ges_fix_single_subject( string $subject, string $action, string $blogname ): string {
	$correct = hc_mnsm_current_network_blogname();
	if ( '' === $correct ) {
		return $subject;
	}

	// GES passes $blogname already bracketed, e.g. '[Primary Network]'.
	return hc_mnsm_swap_token( $subject, $blogname, '[' . $correct . ']' );
}

/**
 * Fix the blogname in a GES digest/summary subject.
 * GES passes $blogname raw (no brackets); subject is "$title [$blogname]".
 *
 * @param string $subject  Subject, e.g. "Weekly Summary [Primary Network]".
 * @param string $blogname The raw wrong blogname GES inserted.
 * @param string $title    Digest title (unused; kept for filter signature).
 * @param string $type     Digest type (unused; kept for filter signature).
 */
function hc_mnsm_ges_fix_digest_subject( string $subject, string $blogname, string $title, string $type ): string {
	$correct = hc_mnsm_current_network_blogname();
	if ( '' === $correct ) {
		return $subject;
	}

	// GES passes $blogname raw and wrote it into the subject as "[$blogname]".
	return hc_mnsm_swap_token( $subject, '[' . $blogname . ']', '[' . $correct . ']' );
}

/* -------------------------------------------------------------------------- *
 *  Registration
 * -------------------------------------------------------------------------- */

// bp-group-documents: its own notifier runs at priority 10; bracket it.
add_action( 'bp_group_documents_add_success', 'hc_mnsm_bpgd_begin_notification', 9 );
add_action( 'bp_group_documents_add_success', 'hc_mnsm_bpgd_end_notification', 11 );
add_filter( 'wp_mail', 'hc_mnsm_bpgd_filter_mail' );

// GES: rewrite blognames in its notification subjects. Late priority so we run
// after GES's own default filters have set the value.
add_filter( 'bp_ass_activity_notification_subject', 'hc_mnsm_ges_fix_single_subject', 20, 3 );
add_filter( 'ass_digest_subject', 'hc_mnsm_ges_fix_digest_subject', 20, 4 );
