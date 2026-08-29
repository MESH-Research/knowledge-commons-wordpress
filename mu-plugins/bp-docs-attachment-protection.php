<?php
/**
 * Plugin Name: BuddyPress Docs Access Hardening
 * Description: Deployment-independent access control for BuddyPress Docs single
 *              views and file downloads. Re-enforces each Doc's own "read"
 *              permission at the application layer on every Doc view and file
 *              request, so access is decided by WordPress consistently and does
 *              not depend on web-server, theme, or rewrite configuration.
 *
 * @package MESH
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Decide how a BuddyPress Docs request should be handled.
 *
 * Pure decision function: no WordPress state is touched here so the security
 * policy can be exercised in isolation. The caller is responsible for supplying
 * the resolved facts about the current request.
 *
 * @param bool $is_docs_request Whether the request targets a single Doc read
 *                              view or a Doc attachment download.
 * @param int  $doc_id          The Doc's post ID, or 0 when the request is not
 *                              for an identifiable Doc.
 * @param bool $can_read        Whether the current user satisfies the Doc's
 *                              "read" permission.
 * @param bool $is_logged_in    Whether the current user is authenticated.
 * @return string One of 'allow' (serve normally), 'login' (send an anonymous
 *                user to authenticate) or 'forbid' (deny an authenticated user).
 */
function mesh_bp_docs_access_decision( bool $is_docs_request, int $doc_id, bool $can_read, bool $is_logged_in ): string {
	// Never interfere with requests that are not for an identifiable Doc.
	if ( ! $is_docs_request || $doc_id <= 0 ) {
		return 'allow';
	}

	// Permitted readers (including "anyone" on public Docs) pass through.
	if ( $can_read ) {
		return 'allow';
	}

	// Not permitted: authenticate anonymous visitors, deny known users.
	return $is_logged_in ? 'forbid' : 'login';
}

/**
 * Enforce the Doc "read" permission on Doc read views and attachment downloads.
 *
 * Runs early on template_redirect (before BuddyPress Docs' own attachment
 * handler) so an unauthorized request is stopped before any file is streamed.
 */
function mesh_bp_docs_enforce_read_access() {
	// BuddyPress Docs must be loaded for any of this to be meaningful.
	if ( ! function_exists( 'bp_docs_get_post_type_name' ) ) {
		return;
	}

	$is_attachment_request = ! empty( $_GET['bp-attachment'] );
	$is_single_doc         = function_exists( 'bp_docs_is_single_doc' ) && bp_docs_is_single_doc();

	if ( ! $is_attachment_request && ! $is_single_doc ) {
		return;
	}

	$doc_id = (int) get_queried_object_id();

	// Confirm the queried object really is a Doc before we assert authority over it.
	if ( $doc_id > 0 && get_post_type( $doc_id ) !== bp_docs_get_post_type_name() ) {
		$doc_id = 0;
	}

	$can_read = false;
	if ( $doc_id > 0 ) {
		$can_read = function_exists( 'bp_docs_user_can' )
			? (bool) bp_docs_user_can( 'read', get_current_user_id(), $doc_id )
			: current_user_can( 'bp_docs_read', $doc_id );
	}

	$decision = mesh_bp_docs_access_decision(
		$is_attachment_request || $is_single_doc,
		$doc_id,
		$can_read,
		is_user_logged_in()
	);

	if ( 'allow' === $decision ) {
		return;
	}

	if ( 'login' === $decision ) {
		// Route anonymous visitors through the platform's normal no-access flow
		// (broker login), preserving the requested URL for return-after-login.
		// Fall back to core auth_redirect() if BuddyPress is unavailable.
		if ( function_exists( 'bp_core_no_access' ) ) {
			$redirect_to = ( function_exists( 'bp_docs_get_doc_link' ) && $doc_id > 0 )
				? bp_docs_get_doc_link( $doc_id )
				: home_url( add_query_arg( array() ) );

			bp_core_no_access(
				array(
					'mode'     => 2,
					'redirect' => $redirect_to,
				)
			);
			exit;
		}

		auth_redirect();
		exit;
	}

	// Authenticated but not permitted to read this Doc.
	wp_die(
		esc_html__( 'You do not have permission to access this item.', 'buddypress-docs' ),
		esc_html__( 'Access denied', 'buddypress-docs' ),
		array( 'response' => 403 )
	);
}

// Priority 5: ahead of BuddyPress Docs' own protect_doc_access (10) and its
// attachment handler (20), so enforcement cannot be outrun by either.
add_action( 'template_redirect', 'mesh_bp_docs_enforce_read_access', 5 );
