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
 * Decide whether the current user may read a Doc, enforcing group privacy
 * independently of the (possibly empty or misconfigured) per-Doc "read" setting.
 *
 * Security invariant: a Doc that lives in a non-public (private or hidden)
 * BuddyPress group is readable only by a member of that group — never by the
 * public — unless the Doc has been *explicitly* published to "anyone". This
 * must hold even when a Doc's per-Doc settings are absent, so that a Doc in a
 * private group is never treated as public by default.
 *
 * Pure function: no WordPress state is touched here so the policy can be tested
 * in isolation. The caller supplies the resolved facts.
 *
 * @param bool $is_moderator      Current user is a site moderator / super admin.
 * @param bool $in_private_group  Doc is associated with a non-public group.
 * @param bool $is_group_member   Current user is a member of that group.
 * @param bool $explicitly_public Doc's saved "read" setting is explicitly "anyone".
 * @param bool $base_cap_allows   The Doc's own read capability grants access.
 * @return bool True if reading is permitted.
 */
function mesh_bp_docs_read_allowed( bool $is_moderator, bool $in_private_group, bool $is_group_member, bool $explicitly_public, bool $base_cap_allows ): bool {
	// Moderators and super admins can always read.
	if ( $is_moderator ) {
		return true;
	}

	// Fail-closed group gate: a non-public group's Docs require membership,
	// unless the Doc was deliberately published to "anyone". This overrides a
	// permissive per-Doc capability that a missing setting would otherwise grant.
	if ( $in_private_group && ! $is_group_member && ! $explicitly_public ) {
		return false;
	}

	// Otherwise the Doc's own read capability decides.
	return $base_cap_allows;
}

/**
 * Resolve whether the current user may read the given Doc, gathering the facts
 * for mesh_bp_docs_read_allowed() from BuddyPress/WordPress.
 *
 * @param int $doc_id The Doc's post ID.
 * @return bool
 */
function mesh_bp_docs_current_user_can_read( int $doc_id ): bool {
	$user_id = get_current_user_id();

	$is_moderator = (bool) ( ( $user_id && is_super_admin( $user_id ) ) || current_user_can( 'bp_moderate' ) );

	$in_private_group = false;
	$is_group_member  = false;

	if ( function_exists( 'bp_docs_get_associated_group_id' ) && function_exists( 'groups_get_group' ) ) {
		$group_ids = (array) bp_docs_get_associated_group_id( $doc_id, false, true );
		$group_ids = array_filter( array_map( 'intval', $group_ids ) );

		foreach ( $group_ids as $group_id ) {
			$group  = groups_get_group( $group_id );
			$status = isset( $group->status ) ? $group->status : 'public';

			if ( 'public' !== $status ) {
				$in_private_group = true;
				if ( $user_id && function_exists( 'groups_is_user_member' ) && groups_is_user_member( $user_id, $group_id ) ) {
					$is_group_member = true;
				}
			}
		}
	}

	// A Doc deliberately published to "anyone" overrides the group gate.
	$raw_settings      = get_post_meta( $doc_id, 'bp_docs_settings', true );
	$explicitly_public = is_array( $raw_settings ) && isset( $raw_settings['read'] ) && 'anyone' === $raw_settings['read'];

	// The Doc's own capability check (fail-closed for group-restricted Docs).
	$base_cap_allows = current_user_can( 'bp_docs_read', $doc_id );

	return mesh_bp_docs_read_allowed( $is_moderator, $in_private_group, $is_group_member, $explicitly_public, $base_cap_allows );
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

	// Resolve the target Doc the same way BuddyPress Docs locates the file it
	// would stream, so this check can never fall out of step with what gets
	// served. Prefer the plugin's own current-doc resolver; fall back to the
	// queried object.
	$doc_id  = 0;
	$pt_name = bp_docs_get_post_type_name();

	if ( function_exists( 'bp_docs_get_current_doc' ) ) {
		$current_doc = bp_docs_get_current_doc();
		if ( $current_doc instanceof WP_Post && $current_doc->post_type === $pt_name ) {
			$doc_id = (int) $current_doc->ID;
		}
	}

	if ( ! $doc_id ) {
		$queried_id = (int) get_queried_object_id();
		if ( $queried_id > 0 && get_post_type( $queried_id ) === $pt_name ) {
			$doc_id = $queried_id;
		}
	}

	$can_read = $doc_id > 0 ? mesh_bp_docs_current_user_can_read( $doc_id ) : false;

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
