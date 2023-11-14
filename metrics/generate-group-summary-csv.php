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

function main() {
	get_groups_data();
}

/**
 * Get the data for the groups summary table.
 *
 * @return array An array of arrays, each of which contains the data for a single group.
 */
function get_groups_data() {
	$groups = groups_get_groups( [ 'per_page' => 0 ] );
	$data = [];
	foreach ( $groups as $group ) {
		$group_data = [
			'name' => $group->name,
			'link' => bp_get_group_permalink( $group ),
			'member_count' => $group->total_member_count,
			'deposit_count' => get_group_deposit_count( $group->id ),
			'blog_post_count' => get_group_blog_post_count( $group->id ),
			'group_status' => $group->status,
			'discussion_topic_count' => get_group_discussion_topic_count( $group->id ),
			'discussion_reply_count' => get_group_discussion_reply_count( $group->id ),
			'days_since_last_interaction' => get_days_since_last_interaction( $group->id ),
		];
		$data[] = $group_data;
	}
	return $data;
}

main();