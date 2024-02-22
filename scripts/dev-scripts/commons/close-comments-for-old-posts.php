<?php
/**
 * Script to close comments for old posts and to set blogs to automatically
 * close comments after a period of time.
 *
 * Syntax: wp --path=/srv/www/commons/current/web/wp eval-file ./close-comments-for-old-posts.php
 * 
 * Addresses issue where Akismet API usage was very high:
 * @link https://github.com/MESH-Research/commons/issues/157
 * 
 * @author Mike Thicke
 */

global $wpdb;

$num_sites = 0;

$sites = get_sites (
	[
		'number' => 0,
	]
);
foreach ( $sites as $blog ) {
	$num_sites++;
	echo ".";
	switch_to_blog( $blog->blog_id );
	update_option( 'close_comments_for_old_posts', true );
	update_option( 'close_comments_days_old', 14 );
	$wpdb->query(
			"UPDATE {$wpdb->posts} 
			 SET `comment_status` = 'closed' 
			 WHERE `post_date` < DATE_SUB( curdate(), INTERVAL 2 WEEK );"
	);
	restore_current_blog();
}

echo "\nProcessed: $num_sites\n";