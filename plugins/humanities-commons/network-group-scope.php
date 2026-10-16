<?php
/**
 * Network scoping for BuddyPress groups.
 *
 * In this multi-network install all networks share the BuddyPress groups
 * tables, so every groups query and every group count must be restricted to
 * the current network's group type. Without this, directory tabs count and
 * list groups from every network in the install.
 *
 * @package Humanities Commons
 * @subpackage Configuration
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Restrict a bp_has_groups() argument set to the current network's group type.
 *
 * Applies to every directory scope, including 'personal' (My Groups) and the
 * hc network, which historically showed groups from all networks.
 *
 * @param array  $args       Parsed bp_has_groups() arguments.
 * @param string $society_id Society id of the current network.
 * @return array
 */
function hcommons_apply_network_group_scope( $args, $society_id ) {
	if ( empty( $society_id ) ) {
		return $args;
	}

	$args['group_type'] = $society_id;

	// only show hc groups on /members/*/invite-anyone: society group
	// membership is managed externally, so invitations are hc-only
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
	if (
		! is_super_admin() &&
		( bp_is_user() && false !== strpos( $request_uri, 'invite-anyone' ) )
	) {
		$args['group_type'] = 'hc';
	}

	return $args;
}

/**
 * Filter the sitewide group count down to the current network's groups.
 *
 * @param int $count Install-wide group count.
 * @return int
 */
function hcommons_network_total_group_count( $count ) {
	$society_id = Humanities_Commons::$society_id;

	if ( empty( $society_id ) || ! class_exists( 'BP_Groups_Group' ) ) {
		return (int) $count;
	}

	static $cache = array();

	if ( ! isset( $cache[ $society_id ] ) ) {
		$groups = BP_Groups_Group::get(
			array(
				'group_type'        => $society_id,
				'show_hidden'       => false,
				'per_page'          => 1,
				'page'              => 1,
				'fields'            => 'ids',
				'update_meta_cache' => false,
			)
		);

		$cache[ $society_id ] = isset( $groups['total'] ) ? (int) $groups['total'] : (int) $count;
	}

	return $cache[ $society_id ];
}
add_filter( 'bp_get_total_group_count', 'hcommons_network_total_group_count' );

/**
 * Filter a user's group count down to their groups on the current network.
 *
 * @param int $count   Install-wide group count for the user.
 * @param int $user_id User being counted. Defaults to displayed, then logged-in user.
 * @return int
 */
function hcommons_network_total_group_count_for_user( $count, $user_id = 0 ) {
	$society_id = Humanities_Commons::$society_id;

	if ( empty( $society_id ) || ! class_exists( 'BP_Groups_Group' ) ) {
		return (int) $count;
	}

	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id() ? bp_displayed_user_id() : bp_loggedin_user_id();
	}

	if ( empty( $user_id ) ) {
		return (int) $count;
	}

	static $cache = array();

	$cache_key = $society_id . ':' . $user_id;

	if ( ! isset( $cache[ $cache_key ] ) ) {
		$show_hidden = ( (int) $user_id === (int) bp_loggedin_user_id() ) || bp_current_user_can( 'bp_moderate' );

		$groups = BP_Groups_Group::get(
			array(
				'user_id'           => $user_id,
				'group_type'        => $society_id,
				'show_hidden'       => $show_hidden,
				'per_page'          => 1,
				'page'              => 1,
				'fields'            => 'ids',
				'update_meta_cache' => false,
			)
		);

		$cache[ $cache_key ] = isset( $groups['total'] ) ? (int) $groups['total'] : (int) $count;
	}

	return $cache[ $cache_key ];
}
add_filter( 'bp_get_total_group_count_for_user', 'hcommons_network_total_group_count_for_user', 10, 2 );
