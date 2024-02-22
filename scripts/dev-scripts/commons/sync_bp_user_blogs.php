<?php
/**
 * Syncs user site roles to the BuddyPress wp_bp_user_blogs table.
 *
 * The BuddyPress table wp_bp_user_blogs keeps track of site membership for
 * BuddyPress components, such as those on user profile pages. It is possible
 * for site roles as reflected in wp_usermeta to get out of sync with those in
 * wp_bp_user_blogs, in which case those blogs, or posts within those blogs,
 * won't show up on their profiles.
 *
 * This script will add users to sites in the wp_bp_user_blogs table if they
 * have author, editor, or administrator roles on those sites, which corrects
 * the above issue.
 *
 * @see https://github.com/MESH-Research/hc-admin-docs-support/issues/310
 *
 * Usage: wp eval-file sync_bp_user_blogs.php
 */

/**
 * This function calls bp_blogs_record_existing_blogs() to rebuild the wp_bp_user_blogs table.
 */
function buddypress_repair() {
	
	// It appears that the bp_blogs_record_exisiting blogs function only works on
	// a per-network basis, so need to iterate through each network. 
	foreach ( get_networks() as $network ) {
		echo "Recording blogs for network id {$network->id}...\n";
		$blogs = get_sites(
			[
				'fields' => 'ids',
				'number' => 99999,
				'network_id' => $network->id
			]
		);

		echo "Recording membership for " . count( $blogs ) . " sites.\n";
		
		// Passing blog_ids explicitly so that the user_blogs table is not truncated.
		$success = bp_blogs_record_existing_blogs(
			[
				'limit'    => 99999,
				'site_id'  => $network->id,
				'blog_ids' => $blogs,
			]
		);

		if ( $success ) {
			echo "...success\n";
		} else {
			echo "...failed\n";
		}
	}
}

buddypress_repair();