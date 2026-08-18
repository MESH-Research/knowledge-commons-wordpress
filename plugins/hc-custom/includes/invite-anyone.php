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
