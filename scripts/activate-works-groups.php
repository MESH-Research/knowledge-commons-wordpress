<?php
/**
 * Activates the Works groups extension for all groups that had CORE deposits associated with them.
 * 
 * Usage: wp eval-file activate-works-groups.php
 */

const MAX_ROWS = 99999999;


function main( $args ) {
	$base_sites = get_base_sites();
	foreach ( $base_sites as $base_site ) {
		$group_ids = get_groups_with_deposits( $base_site['blog_id'] );
		activate_works_groups( $base_site['blog_id'], $group_ids );
	}
}

/**
 * Gets the blog IDs for each base site for all the networks.
 * 
 * @return array List of [domain, blog_id]
 */
function get_base_sites() {
	global $wpdb;

	$result = $wpdb->get_results(
		"
		SELECT wp_blogs.domain, blog_id FROM wp_blogs
		INNER JOIN wp_site
		ON wp_blogs.domain = wp_site.domain;
		",
		ARRAY_A
	);

	return $result;
}

function get_groups_with_deposits( $blog_id ) {
	global $wpdb;

	if ( intval( $blog_id ) === 1 ) {
		$blog_prefix = '';
	} else {
		$blog_prefix = $blog_id . '_';
	}

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"
			SELECT DISTINCT 
			d.post_id AS deposit_id,
				d.meta_value AS metadata
			FROM {$wpdb->base_prefix}{$blog_prefix}postmeta as d
			WHERE d.meta_key = '_deposit_metadata'
			LIMIT %d
			",
			MAX_ROWS
		),
		OBJECT
	);

	$groups = [];
	foreach ( $results as $result ) {
		$metadata = json_decode( $result->metadata, true );
		if ( ! array_key_exists( 'group_ids', $metadata ) ) {
			continue;
		}
		$group_ids = $metadata['group_ids'];
		foreach ( $group_ids as $group_id ) {
			if ( ! in_array( $group_id, $groups ) ) {
				$groups[] = $group_id;
			}
		}
	}

	printf( "For blog_id %d found %d groups with deposits\n", $blog_id, count( $groups ) );
	return $groups;
}

function activate_works_groups( $blog_id, $group_ids ) {
	switch_to_blog( $blog_id );
	foreach ( $group_ids as $group_id ) {
		printf( "Activating Works for group %d\n", $group_id );
		groups_update_groupmeta( $group_id, 'kcworks-enable', 1 );
	}
	restore_current_blog();
}

main($args);
