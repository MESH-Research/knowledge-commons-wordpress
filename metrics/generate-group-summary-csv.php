<?php
/**
 * Generate a groups summary table showing the following for each group:
 *   - Group name
 *   - Link
 *   - Number of members
 *   - Number of deposits
 *   - Number of blog posts
 *   - Is it public / private / hidden?
 *   - Number of discussion topics
 *   - Number of replies
 *   - Number of days since last interaction, as an integer
 *
 * usage: wp eval-file generate-groups-summary.php <output-file.csv>
 */

function main( $args ) {
	$data = get_groups_data();
	write_data_to_csv( $data, $args[0] );
	echo "Output written to $args[0]\n";
}

/**
 * Get the data for the groups summary table.
 *
 * @return array An array of arrays, each of which contains the data for a single group.
 */
function get_groups_data() {
	global $wpdb;
	$groups = groups_get_groups( [ 'per_page' => 0 ] );
	$data = [];
	
	echo "Getting group data\n";
	foreach ( $groups['groups'] as $group ) {
		$forum_ids = bbp_get_group_forum_ids( $group->id  );
		$topic_count = null;
		$reply_count = null;
		$last_active_time = null;
		if ( !empty( $forum_ids ) ) {
			$forum_id = $forum_ids[0]; // Get the first forum ID associated with the group
			$topic_count = bbp_get_forum_topic_count( $forum_id );
			$reply_count = bbp_get_forum_reply_count( $forum_id );
			$last_active_time = bbp_get_forum_last_active_time_in_days( $forum_id );
		}
		
		$blog_post_count = false;
		$groupblog_id = groups_get_groupmeta($group->id, 'groupblog_blog_id');
		if ( $groupblog_id ) {
			$posts_table = $wpdb->get_blog_prefix( $groupblog_id ) . 'posts';
			$query = "SELECT COUNT(*) FROM $posts_table WHERE post_status = 'publish' AND post_author != 0";
			$blog_post_count = $wpdb->get_var( $query );
		}

		$group_data = [
			'name'                        => $group->name,
			'link'                        => bp_get_group_permalink( $group ),
			'member_count'                => $group->total_member_count,
			'deposit_count'               => get_group_deposit_count( $group->id, $group->name ),
			'blog_post_count'             => $blog_post_count,
			'group_status'                => $group->status,
			'discussion_topic_count'      => $topic_count,
			'discussion_reply_count'      => $reply_count,
			'days_since_last_interaction' => $last_active_time,
		];
		
		echo ".";
		$data[] = $group_data;
	}
	echo "\nDone getting group data\n";
	return $data;
}

function write_data_to_csv( $data, $filename ) {
	$fp = fopen( $filename, 'w' );
	$header = array_keys( $data[0] );
	fputcsv( $fp, $header );
	foreach ( $data as $row ) {
		fputcsv( $fp, $row );
	}
	fclose( $fp );
}

function get_group_deposit_count( $group_id, $group_name ) {
	if ( in_array( $group_id, humcore_member_groups_with_authorship() ) ) {
		humcore_has_deposits( sprintf( 'facets[author_facet][]=%s', urlencode( $group_name ) ) );
	} else {
		humcore_has_deposits( sprintf( 'facets[group_facet][]=%s', urlencode( $group_name ) ) );
	}
	return intval( humcore_get_deposit_count() );
}

/**
 * Get the last active time for a forum in days.
 * 
 * Fork of bbp_get_forum_last_active_time() that returns the time in days instead of a string.
 * @see https://www.buddyboss.com/resources/reference/functions/bbp_get_forum_last_active_time/
 */
function bbp_get_forum_last_active_time_in_days( $forum_id = 0 ) {
 
    // Verify forum and get last active meta
    $forum_id    = bbp_get_forum_id( $forum_id );
    $last_active = get_post_meta( $forum_id, '_bbp_last_active_time', true );
 
    if ( empty( $last_active ) ) {
        $reply_id = bbp_get_forum_last_reply_id( $forum_id );
        if ( !empty( $reply_id ) ) {
            $last_active = get_post_field( 'post_date', $reply_id );
        } else {
            $topic_id = bbp_get_forum_last_topic_id( $forum_id );
            if ( !empty( $topic_id ) ) {
                $last_active = bbp_get_topic_last_active_time( $topic_id );
            }
        }
    }

	if ( ! $last_active ) {
		return null;
	}

	$last_active_timestamp = bbp_convert_date( $last_active );
	$time_in_days_since_last_active = round( ( time() - $last_active_timestamp ) / ( 60 * 60 * 24 ) );
 
    return $time_in_days_since_last_active;
}

main( $args );