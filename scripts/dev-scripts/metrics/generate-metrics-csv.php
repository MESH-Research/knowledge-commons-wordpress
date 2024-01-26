<?php
/**
 * This script generates a CSV file containing selected metrics for the Commons.
 * It is intentended to be a stop-gap solution to gathering metrics until a more
 * robust dashboard can be created.
 *
 * The script is intended to be run at regular intervals as part of a cron job.
 * It will email the resulting CSV file to the address specified.
 * 
 * Usage: wp eval-file dev-scripts/metrics/generate-metrics-csv.php mail=<email address>
 */

function main( $args ) {
	$args = parse_args( $args );

	$metrics = [
		'general' => general_metrics(),
		'groups'  => group_metrics(),
		'sites'   => site_metrics(),
	];

	$combined_metrics = array_merge( $metrics['general'], $metrics['groups'], $metrics['sites'] );

	$filename = sprintf( 'metrics-%s.csv', date( 'Y-m-d' ) );
	generate_csv( $combined_metrics, $filename );
}

/**
 * Parse arguments into associative array.
 *
 * Arguments should be passed to script as key=value (no spaces).
 *
 * @param Array $args Array of arguments as passed to script by WP CLI eval-file.
 */
function parse_args( $args ) {
	$parsed_args = [];
	foreach ( $args as $arg ) {
		$split_arg = explode( '=', $arg );
		if ( count( $split_arg ) !== 2 ) {
			throw new Exception( "Misformatted argument: '$arg'" );
		}
		if ( is_numeric( $split_arg[1] ) ) {
			$split_arg[1] = intval( $split_arg[1] );
		} elseif ( 'true' === $split_arg[1] ) {
			$split_arg[1] = true;
		} elseif ( 'false' === $split_arg[1] ) {
			$split_arg[1] = false;
		} elseif ( strpos( $split_arg[1], ',' ) !== false ) {
			$split_arg[1] = explode( ',', $split_arg[1] );
		}
		$parsed_args[ $split_arg[0] ] = $split_arg[1];
	}
	return $parsed_args;
}

/**
 * Retrieve general usage metrics.
 * 
 * @return Array Array of metrics.
 *     'total_users' => Total number of users.
 *     'recent_users' => Number of users who have logged in in the last 30 days.
 */
function general_metrics() {
	global $wpdb;

	$bp_activity_table = buddypress()->members->table_name_last_activity;

	$sql = "SELECT 	u.ID, 
					a.date_recorded AS last_activity, 
					m.name AS member_type
			FROM $wpdb->users AS u
			LEFT JOIN $bp_activity_table AS a ON a.user_id = u.ID AND a.type = 'last_activity'
			LEFT JOIN wp_1000360_term_relationships as r ON u.ID = r.object_id
			LEFT JOIN wp_1000360_term_taxonomy as t ON r.term_taxonomy_id = t.term_taxonomy_id AND t.taxonomy = 'bp_member_type'
			LEFT JOIN wp_1000360_terms as m ON t.term_id = m.term_id
			AND u.deleted = 0
			AND u.spam = 0
			AND u.user_email IS NOT NULL
			AND u.user_email <> ''";
	
	$users = $wpdb->get_results( $sql );

	$categorized_users = [
		'all'     => [],
		'recent'  => [],
		'hc'      => [],
		'mla'     => [],
		'arlisna' => [],
		'hastac'  => [],
		'up'      => [],
		'sah'     => [],
		'msu'     => [],
	];

	$recent_date = date( 'Y-m-d H:i:s', strtotime( '-30 days' ) );

	foreach ( $users as $user ) {
		if ( ! in_array( $user->ID, $categorized_users['all'] ) ) {
			$categorized_users['all'][] = $user->ID;
		}

		if ( is_array( $categorized_users[ $user->member_type ] ) && ! in_array( $user->ID, $categorized_users[ $user->member_type ] ) ) {
			$categorized_users[ $user->member_type ][] = $user->ID;
		}

		if ( 
			! in_array( $user->ID, $categorized_users['recent'] ) &&
			$user->last_activity > $recent_date
		) {
			$categorized_users['recent'][] = $user->ID;	
		}
	}

	$categorized_user_counts = [
		'total_members'   => count( $categorized_users['all'] ),
		'recent_members'  => count( $categorized_users['recent'] ),
		'hc_members'      => count( $categorized_users['hc'] ),
		'mla_members'     => count( $categorized_users['mla'] ),
		'arlisna_members' => count( $categorized_users['arlisna'] ),
		'hastac_members'  => count( $categorized_users['hastac'] ),
		'up_members'      => count( $categorized_users['up'] ),
		'sah_members'     => count( $categorized_users['sah'] ),
		'msu_members'     => count( $categorized_users['msu'] ),
	];

	return $categorized_user_counts;
}

function group_metrics() {
	global $wpdb;

	$min_group_membership = 2;

	$sql = "SELECT 	g.id,
					g.name,
					COUNT(DISTINCT m.id) AS member_count,
					s.meta_value AS groupblog_id,
					COUNT(DISTINCT q.id) + COUNT(DISTINCT r.id) AS post_count
			FROM wp_bp_groups AS g
			LEFT JOIN wp_bp_groups_members AS m ON g.id = m.group_id
			LEFT JOIN wp_bp_groups_groupmeta AS f ON g.id = f.group_id AND f.meta_key = 'forum_id'
			LEFT JOIN wp_bp_groups_groupmeta AS s ON g.id = s.group_id AND s.meta_key = 'groupblog_blog_id'
			LEFT JOIN wp_posts as p ON p.ID = REPLACE( REPLACE(f.meta_value, 'a:1:{i:0;i:', ''), ';}', '')
			LEFT JOIN wp_posts as q ON q.post_parent = p.ID
			LEFT JOIN wp_posts as r ON r.post_parent = q.ID
			GROUP BY g.id;
	";

	$groups = $wpdb->get_results( $sql );

	$group_member_counts = [];
	$group_post_counts = [];
	$group_deposit_counts = [];
	$blog_post_counts = [];
	$blog_post_counts_greater_than_2 = [];
	$blog_comment_counts = [];
	$blog_comment_counts_greater_than_2 = [];
	$greater_than_two_post_groups = 0;
	foreach ( $groups as $group ) {
		if ( $group->member_count < $min_group_membership ) {
			continue;
		}
		$group_member_counts[] = intval( $group->member_count );
		$group_post_counts[] = intval( $group->post_count );
		$group_deposit_counts[] = get_group_deposit_count( $group->id, $group->name );

		$groupblog_id = groups_get_groupmeta($group->id, 'groupblog_blog_id');
		if ( $groupblog_id ) {
			$posts_table = $wpdb->get_blog_prefix( $groupblog_id ) . 'posts';
			$query = "
				SELECT COUNT(*)
				FROM $posts_table 
				WHERE ( post_type = 'post' OR post_type = 'page' )
				      AND post_status = 'publish' 
					  AND post_author != 0
			";
			$blog_posts = intval( $wpdb->get_var( $query ) );
			$blog_post_counts[] = $blog_posts;
			if ( $blog_posts >= 2 ) {
				$blog_post_counts_greater_than_2[] = $blog_posts;
				$greater_than_two_post_groups++;
			}

			$comments_table = $wpdb->get_blog_prefix( $groupblog_id ) . 'comments';
			$query = "
				SELECT COUNT(*)
				FROM $comments_table 
				WHERE comment_approved = 1
			";
			$blog_comments = intval( $wpdb->get_var( $query ) );
			$blog_comment_counts[] = $blog_comments;
			if ( $blog_posts >= 2 ) {
				$blog_comment_counts_greater_than_2[] = $blog_comments;
			}

		}
	}

	$total_groups = count( $groups );

	$group_metrics = [
		'total_groups'                               => $total_groups,
		'greater_than_two_post_groups'               => $greater_than_two_post_groups,
		'median_group_members'                       => median( $group_member_counts ),
		'average_group_members'                      => array_sum( $group_member_counts ) / $total_groups,
		'median_group_post_counts'                   => median( $group_post_counts ),
		'average_group_post_counts'                  => array_sum( $group_post_counts ) / $total_groups,
		'median_group_deposit_counts'                => median( $group_deposit_counts ),
		'average_group_deposit_counts'               => array_sum( $group_deposit_counts ) / $total_groups,
		'median_blog_post_counts'                    => median( $blog_post_counts ),
		'average_blog_post_counts'                   => array_sum( $blog_post_counts ) / $total_groups,
		'median_blog_comment_counts'                 => median( $blog_comment_counts ),
		'average_blog_comment_counts'                => array_sum( $blog_comment_counts ) / $total_groups,
		'median_blog_post_counts_greater_than_2'     => median( $blog_post_counts_greater_than_2 ),
		'average_blog_post_counts_greater_than_2'    => array_sum( $blog_post_counts_greater_than_2 ) / $greater_than_two_post_groups,
		'median_blog_comment_counts_greater_than_2'  => median( $blog_comment_counts_greater_than_2 ),
		'average_blog_comment_counts_greater_than_2' => array_sum( $blog_comment_counts_greater_than_2 ) / $greater_than_two_post_groups,
	];

	return $group_metrics;
}

function core_metrics() {

}

function site_metrics() {
	global $wpdb;

	$sql = "	SELECT blog_id
				FROM wp_blogs
	";

	$blogs = $wpdb->get_results( $sql );

	$blog_post_counts = [];
	$blog_comment_counts = [];
	foreach ( $blogs as $blog ) {
		switch_to_blog( $blog->blog_id );
		$blog_post_counts[] = wp_count_posts()->publish;
		$blog_comment_counts[] = wp_count_comments()->total_comments;	
	}

	$total_blogs = count( $blogs );

	$site_metrics = [
		'total_blogs'                 => $total_blogs,
		'median_blog_post_counts'     => median( $blog_post_counts ),
		'average_blog_post_counts'    => array_sum( $blog_post_counts ) / $total_blogs,
		'median_blog_comment_counts'  => median( $blog_comment_counts ),
		'average_blog_comment_counts' => array_sum( $blog_comment_counts ) / $total_blogs,
	];
	return $site_metrics;
}

function generate_csv( $metrics, $filename ) {
	$stream = fopen( $filename, 'w' );
	fputcsv( $stream, array_keys( $metrics ) );
	fputcsv( $stream, $metrics );
}

function median( $list_of_numbers ) {
	sort( $list_of_numbers );
	$middle_index = floor( count( $list_of_numbers ) / 2 );
	if ( count( $list_of_numbers ) % 2 ) {
		return $list_of_numbers[ $middle_index ];
	} else {
		return ( $list_of_numbers[ $middle_index - 1 ] + $list_of_numbers[ $middle_index ] ) / 2;
	}
}

function get_group_deposit_count( $group_id, $group_name ) {
	if ( in_array( $group_id, humcore_member_groups_with_authorship() ) ) {
		humcore_has_deposits( sprintf( 'facets[author_facet][]=%s', urlencode( $group_name ) ) );
	} else {
		humcore_has_deposits( sprintf( 'facets[group_facet][]=%s', urlencode( $group_name ) ) );
	}
	return intval( humcore_get_deposit_count() );
}

main( $args );
