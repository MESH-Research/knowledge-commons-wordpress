<?php
/**
 * Flush rewrite rules for all sites. Run when bringing up container. Called by
 * wp eval-file.
 */

function main() {
	$blogs = get_sites(
		[
			'number' => -1,
		]
	);
	foreach ( $blogs as $blog ) {
		switch_to_blog( $blog->blog_id );
		flush_rewrite_rules();
		restore_current_blog();
	}
}

main();
