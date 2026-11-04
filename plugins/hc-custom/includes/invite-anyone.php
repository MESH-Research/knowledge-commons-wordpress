<?php
/**
 * Custom changes to the Invite Anyone plugin.
 *
 * @package Hc_Custom
 */

/**
 * Whether the current user may use invite-anyone in a group.
 *
 * Mirrors the checks in BP_Invite_Anyone::enable_nav_item(), but is safe to
 * call once the current group has actually been resolved.
 *
 * @param int $group_id Group ID. Defaults to the current group.
 * @param int $user_id  User ID. Defaults to the logged-in user.
 * @return bool
 */
function hc_custom_user_can_use_invite_anyone( $group_id = 0, $user_id = 0 ) {
	if ( ! function_exists( 'bp_groups_user_can_send_invites' ) || ! function_exists( 'invite_anyone_group_invite_access_test' ) ) {
		return false;
	}

	if ( ! $group_id && function_exists( 'bp_get_current_group_id' ) ) {
		$group_id = bp_get_current_group_id();
	}

	if ( ! $group_id ) {
		return false;
	}

	if ( ! bp_groups_user_can_send_invites( $group_id, $user_id ) ) {
		return false;
	}

	return 'anyone' === invite_anyone_group_invite_access_test( $group_id, $user_id );
}

/**
 * Restore the invite-anyone "Send Invites" group tab on BuddyPress 12+.
 *
 * This addresses @link https://github.com/MESH-Research/knowledge-commons-wordpress/issues/101
 *
 * Invite Anyone computes its enable_nav_item() value in the constructor of
 * BP_Invite_Anyone, which BuddyPress instantiates at bp_init:11. Since
 * BuddyPress 12 the requested group is not resolved until bp_parse_query, so
 * bp_get_current_group_id() is still 0 at that point, the capability check
 * fails, and the extension registers itself with access and visibility set
 * to 'noone' — the Send Invites tab disappears from every existing group.
 *
 * By bp_actions the current group is known, so constructing a fresh
 * instance here lets the extension evaluate its own access rules correctly.
 * We register it at priority 7, just before BuddyPress registers group
 * extensions at priority 8; re-registering the same slug simply overwrites
 * the earlier, broken registration.
 *
 * @return bool True if the extension was re-registered.
 */
function hc_custom_restore_invite_anyone_group_nav() {
	if ( ! function_exists( 'bp_is_group' ) || ! bp_is_group() ) {
		return false;
	}

	if ( ! class_exists( 'BP_Invite_Anyone' ) ) {
		return false;
	}

	if ( ! hc_custom_user_can_use_invite_anyone() ) {
		return false;
	}

	$extension = new BP_Invite_Anyone();
	$extension->_register();

	return true;
}
add_action( 'bp_actions', 'hc_custom_restore_invite_anyone_group_nav', 7 );

/**
 * Invitations sent to an email address that have not been accepted yet.
 *
 * @param string $email Invited email address.
 * @return WP_Post[] Pending ia_invites posts, oldest first.
 */
function hc_custom_pending_email_invitations( $email ) {
	if ( '' === (string) $email || ! function_exists( 'invite_anyone_get_invitations_by_invited_email' ) ) {
		return array();
	}

	$query   = invite_anyone_get_invitations_by_invited_email( $email );
	$pending = array();

	foreach ( (array) ( $query->posts ?? array() ) as $invite ) {
		if ( empty( $invite->ID ) ) {
			continue;
		}

		if ( '' !== (string) get_post_meta( $invite->ID, 'bp_ia_accepted', true ) ) {
			continue;
		}

		$pending[] = $invite;
	}

	return $pending;
}

/**
 * Accept the email invitations addressed to a user and send the group invites.
 *
 * Replaces Invite Anyone's invite_anyone_activate_user(), which only runs on
 * BuddyPress account activation. Commons accounts are created by the login
 * broker, so that hook never fires here.
 *
 * @param int $user_id User whose email address was invited.
 * @return int[] IDs of the groups the user was invited to.
 */
function hc_custom_process_email_invitations( $user_id ) {
	$user_id = (int) $user_id;
	$pending = hc_custom_pending_email_invitations( bp_core_get_user_email( $user_id ) );

	if ( empty( $pending ) ) {
		return array();
	}

	$taxonomy = invite_anyone_get_invited_groups_tax_name();
	$inviters = array();
	$groups   = array(); // group_id => inviter_id; first invitation wins.

	foreach ( $pending as $invite ) {
		$inviter_id = (int) $invite->post_author;
		$inviters[] = $inviter_id;

		foreach ( (array) wp_get_post_terms( $invite->ID, $taxonomy ) as $term ) {
			$group_id = (int) $term->name;
			if ( $group_id && ! isset( $groups[ $group_id ] ) ) {
				$groups[ $group_id ] = $inviter_id;
			}
		}

		update_post_meta( $invite->ID, 'bp_ia_accepted', gmdate( 'Y-m-d H:i:s' ) );
	}

	foreach ( $groups as $group_id => $inviter_id ) {
		groups_invite_user(
			array(
				'user_id'     => $user_id,
				'group_id'    => $group_id,
				'inviter_id'  => $inviter_id,
				'send_invite' => 1,
			)
		);
	}

	/** This action is documented in invite-anyone/by-email/by-email.php */
	do_action( 'accepted_email_invite', $user_id, array_values( array_unique( $inviters ) ) );

	return array_keys( $groups );
}

/**
 * Process email invitations once per user per network.
 *
 * @return bool True if invitations were looked up on this request.
 */
function hc_custom_maybe_process_email_invitations() {
	if ( ! is_user_logged_in() || ! bp_is_root_blog() || ! bp_is_active( 'groups' ) ) {
		return false;
	}

	if ( ! function_exists( 'invite_anyone_get_invitations_by_invited_email' ) ) {
		return false;
	}

	$user_id  = get_current_user_id();
	$meta_key = 'hc_ia_invites_checked_' . (int) bp_get_root_blog_id();

	if ( '' !== (string) get_user_meta( $user_id, $meta_key, true ) ) {
		return false;
	}

	hc_custom_process_email_invitations( $user_id );
	update_user_meta( $user_id, $meta_key, gmdate( 'Y-m-d H:i:s' ) );

	return true;
}
add_action( 'bp_init', 'hc_custom_maybe_process_email_invitations', 20 );
