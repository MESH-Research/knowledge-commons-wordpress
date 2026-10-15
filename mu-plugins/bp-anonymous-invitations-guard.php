<?php
/**
 * Plugin Name: BuddyPress Anonymous Invitations Guard
 * Description: Stops BuddyPress loading every pending group invitation on the network when a capability check runs for a logged-out visitor.
 * Version:     1.0.0
 * Author:      Mesh Research
 *
 * BuddyPress (14.5.2, bp-groups-functions.php bp_get_user_groups()) asks
 * BP_Invitation::get() for the invitations of user 0 whenever a group
 * membership check runs for a logged-out visitor. BP_Invitation::get_where_sql()
 * tests `! empty( $args['user_id'] )`, so a user ID of 0 silently removes the
 * user filter and the query becomes "every pending group invitation and
 * membership request on the network". Each result is then hydrated, cached and
 * looped over twice. BuddyPress Docs runs that check several times per comment
 * on a group document, which is enough to burn the full CPU time limit on a
 * single page view.
 *
 * This guard rewrites an invitation query whose only subject is user 0 into
 * one that matches nothing, which is the correct answer: a visitor who is not
 * logged in cannot hold invitations. Queries scoped by group (item_id), by
 * inviter, by email or by explicit invitation ids are left alone, because
 * there user_id 0 means "any user" (for example the REST group-invites
 * endpoint used by group administrators).
 *
 * @package Commons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filter the raw arguments of BP_Invitation::get() / get_total_count().
 *
 * @param array $args Arguments as passed by the caller, before defaults are merged.
 * @return array
 */
function kc_bp_guard_anonymous_invitation_args( $args ) {
	if ( ! is_array( $args ) || ! array_key_exists( 'user_id', $args ) ) {
		return $args;
	}

	// Only an explicit 0 (int or string). false/null is BuddyPress's own
	// "no user filter" default and arrays are multi-user queries.
	if ( 0 !== $args['user_id'] && '0' !== $args['user_id'] ) {
		return $args;
	}

	// Scoped queries use user_id 0 to mean "any user"; leave them alone.
	foreach ( array( 'item_id', 'invitee_email', 'id' ) as $scope ) {
		if ( ! empty( $args[ $scope ] ) ) {
			return $args;
		}
	}
	if ( isset( $args['inviter_id'] ) && false !== $args['inviter_id'] && '' !== $args['inviter_id'] ) {
		return $args;
	}

	// id IN (0) matches no row.
	$args['id'] = array( 0 );

	return $args;
}

add_filter( 'bp_before_bp_invitations_invitation_get_parse_args', 'kc_bp_guard_anonymous_invitation_args' );
add_filter( 'bp_before_bp_invitations_invitation_get_total_count_parse_args', 'kc_bp_guard_anonymous_invitation_args' );
