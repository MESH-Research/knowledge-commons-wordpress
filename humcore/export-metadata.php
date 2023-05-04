<?php
/**
 * Exports metadata from WordPress into CSV file.
 * 
 * Usage wp eval-file export-metadata.php [outputfilename]
 */

const MAX_ROWS = 5;

function main( $args ) {
	get_deposit_metadata( 1000360 );
}

/**
 * Gets deposit metadata for all deposits in a blog.
 * 
 * @param int $blog_id The blog ID.
 * 
 * @return array The deposit metadata. List of associative arrays with dynamically generated keys.
 */
function get_deposit_metadata( $blog_id ) {
	global $wpdb;

	xdebug_break();
	
	$result = $wpdb->get_results(
		$wpdb->prepare(
			"
			SELECT d.meta_value AS metadata, f.meta_value AS filedata
			FROM {$wpdb->base_prefix}{$blog_id}_postmeta as d
			LEFT JOIN {$wpdb->base_prefix}{$blog_id}_postmeta as f
			ON d.post_id = f.post_id
			WHERE d.meta_key = '_deposit_metadata' AND f.meta_key = '_deposit_file_metadata'
			LIMIT %d
			",
			MAX_ROWS
		),
		OBJECT
	);

	$metadata = [];
	foreach ( $result as $row ) {
		$metadata[] = json_decode( $row );
	}

	return $metadata;
}

main( $args );