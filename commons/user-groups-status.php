<?php
/**
 * A script to check user memberships in groups and societies and compare with
 * groups data passed from COmanage.
 * 
 * usage: wp eval-file user-groups-status.php <username>
 */

function main( $args ) {
	if ( ! check_args( $args ) ) {
		show_help();
		exit();
	}

	$username = $args[0];
	$user_id = get_user_id( $username );
	$groups = get_group_memberships( $user_id );
	$ldap_groups = get_user_memberships_from_ldap( $username );
	$merged_groups = merge_groups_with_ldap_groups( $groups, $ldap_groups );
	output_results( $username, $merged_groups );
}

/**
 * Output help text.
 */
function show_help() {
	echo <<< HELP

	A script to check user memberships in groups and societies and compare with
	groups data passed from COmanage.

	usage: wp eval-file user-groups-status.php <username>

	HELP;
}

/**
 * Ensures that the command line arguments have been entered correctly.
 */
function check_args( $args ) {
	if ( count( $args ) !== 1 ) {
		return false;
	}

	if ( $args[0] === '--help' || $args[0] === '-h' || $args[0] === 'help' ) {
		return false;
	}

	return true;
}

function get_user_id( $username ) {
	$user = get_user_by( 'login', $username );
	return $user->ID;
}

/**
 * Get group memberships for a user.
 * 
 * @param int $user_id The user's WordPress id.
 * @return array Array of groups the user is a member of.
 */
function get_group_memberships( $user_id ) {
	buddypress();
	$group_ids = BP_Groups_Member::get_group_ids( $user_id )['groups'];
	$groups = [];
	foreach ( $group_ids as $gid ) {
		$group = groups_get_group( [ 'group_id' => $gid ] );
		$groups[] = [
			'name' => $group->name,
			'id' => $group->id,
			'autopopulate' => is_managed_group( $group->id ) ? 'Y' : 'N',
		];
	}
	return $groups;
}

/**
 * Check whether a group is a managed group.
 *
 * @param int $group_id ID of the group
 * @return bool True if the group is a managed group.
 */
function is_managed_group( $group_id ) {
	$autopopulate = groups_get_groupmeta( $group_id, 'autopopulate' );
	return $autopopulate === 'Y' || $autopopulate === 'y';
}

/**
 * Get societies a user is a member of.
 *
 * @param int $user_id User's WordPress ID.
 * @return array Array of society slugs the user is a member of.
 */
function get_society_memberships( $user_id ) {

}

/**
 * Get 
 */
function get_user_memberships_from_ldap( $username ) {
	$ldap_result = shell_exec(
		"ldapsearch -LLL -h 10.101.11.181 -D uid=comanage,ou=system,o=HC,dc=commons,dc=mla,dc=org -x -w ESe4LNZoN7Y3T7Qypwwk -b ou=people2,o=HC,dc=commons,dc=mla,dc=org \"(employeeNumber=$username)\""
	);

	$regex = '/isMemberOf\: Humanities Commons\:.*?_(.*)/';
	preg_match_all(
		$regex,
		$ldap_result,
		$matches
	);
	return $matches[1];
}

function merge_groups_with_ldap_groups( $groups, $ldap_groups ) {
	foreach ( $groups as &$group ) {
		if ( in_array( $group['name'], $ldap_groups ) ) {
			$group['ldap'] = 'Y';
			$ldap_groups = array_filter( $ldap_groups, 
			function( $element ) use ( $group ) { 
				return $element !== $group['name']; 
			} );
		}
		else {
			$group['ldap'] = 'N';
		}
	}
	foreach ( $ldap_groups as $leftover_group ) {
		$groups[] = [
			'name' => $leftover_group,
			'id' => 0,
			'autopopulate' => 'N/A'
		];
	}
	return $groups;
}

function output_results( $username, $groups ) {
	print( "Memberships for $username: \n\n" );
	$format_string = "%-60s %-7s %-4s\n";
	printf( $format_string, 'Name', 'Managed', 'LDAP' );
	printf( $format_string, '----', '-------', '----' );
	foreach ( $groups as $group ) {
		printf(
			$format_string,
			$group['name'],
			$group['autopopulate'],
			$group['ldap']
		);
	}
}

main( $args );