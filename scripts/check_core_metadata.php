<?php

function main( $args ) {
	$base_sites = get_base_sites();

	foreach ($base_sites as $base_site) {
		
		$posts = get_humcore_posts($base_site['blog_id']);
		switch_to_blog($base_site['blog_id']);
		foreach ($posts as $post) {
			check_metadata($post);
		}
		restore_current_blog();
	}
}

function get_humcore_posts(int $blog_id) {
	global $wpdb;

	if ($blog_id === 1) {
		$blog_string = '';
	} else {
		$blog_string = "{$blog_id}_";
	}

	$result = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT ID, post_title, post_name, post_author FROM wp_{$blog_string}posts WHERE post_type = 'humcore_deposit' AND post_parent = 0",
		),
		OBJECT
	);

	printf("For blog ID %d, found %d posts\n", $blog_id, count($result));

	return $result;
}

function check_metadata(
	$post, 
	$keys_to_check = [
		'_deposit_metadata', 
		'_total_views_CONTENT_%', 
		'_total_downloads_CONTENT_%',
		'_deposit_file_metadata'
	]
) {

	$postmeta = get_post_meta($post->ID, '', false);

	$values = [];

	foreach ($keys_to_check as $key) {
		$values[$key] = $postmeta[$key] ?? null;
	}

	foreach ($values as $key => $value) {
		if (empty($value) && str_ends_with($key, '%')) {
			foreach ($postmeta as $meta_key => $meta_value) {
				if (str_starts_with($meta_key, trim($key, '%'))) {
					$values[$key] = $meta_value;
				}
			}	
		}
	}

	foreach ($values as $key => $value) {
		if (empty($value)) {
			printf("ID: %d, Title: %s, Name: %s, Author: %s, Key: %s\n", $post->ID, $post->post_title, $post->post_name, $post->post_author, $key);
		}
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

main($args);