<?php
/**
 * Changes group type of a BuddyPress group to move it between Commons networks.
 * 
 * Syntax: wp eval-file change-group-type.php [group_id] [new_type]
 * 
 * If both parameters are ommitted, will list available group types.
 * If new_type is ommitted, will list available group types and the current group type.
 */

function main( $args ) {
	[ $group_id, $new_type ] = parse_args( $args );

	$old_type = get_group_type( $group_id );

	echo $old_type;
}

/**
 * Parse command line arguments and exit if invalid.
 *
 * @return array [ $group_id, $new_type ]
 */
function parse_args( $args ) {
	$group_id = $args[0];
	$new_type = $args[1];

	if ( ! $group_id || ! $new_type ) {
		echo "Usage: wp eval-file change-group-type.php [group_id] [new_type] \n";
		echo "Available group types: \n";
		list_group_types();
		if ( $group_id ) {
			echo "Current group type: " . get_group_type( $group_id ) . PHP_EOL;
		}
		exit;
	}

	return [ $group_id, $new_type ];
}

/**
 * Update a group's type.
 */
function update_group_type( $group_id, $new_type ) {
}

/**
 * Get a group's type.
 * 
 * @param int $group_id
 * @return string The group type.
 */
function get_group_type( $group_id ) {
	return bp_groups_get_group_type( $group_id );
}

/**
 * Get all group types and list them.
 */
function list_group_types() {
	$group_types = bp_groups_get_group_types();

	foreach ( $group_types as $group_type ) {
		echo $group_type . PHP_EOL;
	}
}

main( $args );