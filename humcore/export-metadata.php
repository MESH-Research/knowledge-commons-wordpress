<?php

/**
 * Exports metadata from WordPress into CSV file.
 * 
 * Usage wp eval-file export-metadata.php [outputfilename]
 */

const MAX_ROWS = 999999999;

function main( $args ) {
	$base_sites = get_base_sites();
	$combined_metadata = [];
	foreach ( $base_sites as $base_site ) {
		$metadata = get_deposit_metadata( $base_site['blog_id'], $base_site['domain'] );
		$combined_metadata = array_merge( $combined_metadata, $metadata );
	}
	$regularized_metadata = regularize_metadata( $combined_metadata );
	write_to_csv( $regularized_metadata, $args[0] );
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

/**
 * Gets deposit metadata for all deposits in a blog.
 * 
 * @param int $blog_id The blog ID.
 * 
 * @return array The deposit metadata. List of associative arrays with dynamically generated keys.
 */
function get_deposit_metadata( $blog_id, $domain ) {
	global $wpdb;

	if ( intval( $blog_id ) === 1 ) {
		$blog_prefix = '';
	} else {
		$blog_prefix = $blog_id . '_';
	}
	
	$result = $wpdb->get_results(
		$wpdb->prepare(
			"
			SELECT d.meta_value AS metadata, f.meta_value AS filedata
			FROM {$wpdb->base_prefix}{$blog_prefix}postmeta as d
			LEFT JOIN {$wpdb->base_prefix}{$blog_prefix}postmeta as f
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
		$deposit_metadata = json_decode( $row->metadata, true );
		$deposit_metadata = array_merge( [ 'blog_id' => $blog_id, 'domain' => $domain ], $deposit_metadata );
		$deposit_file_metadata = json_decode( $row->filedata, true );
		if ( $deposit_file_metadata['files'] && count( $deposit_file_metadata['files'] ) > 0 ) {
			$deposit_file_metadata = $deposit_file_metadata['files'][0];
			$deposit_file_metadata['file_pid'] = $deposit_file_metadata['pid'];
			unset( $deposit_file_metadata['pid'] );
		} else {
			$deposit_file_metadata = [];
		}
		$metadata[] = array_merge( $deposit_metadata, $deposit_file_metadata );
	}

	return $metadata;
}

/**
 * Regularizes the metadata so that all rows have the same keys.
 *
 * @param array $metadata The metadata.
 * @return array The regularized metadata.
 */
function regularize_metadata( $metadata ) {
	$metadata_fields = [];
	foreach ( $metadata as $row ) {
		foreach ( $row as $key => $value ) {
			if ( ! in_array( $key, $metadata_fields ) ) {
				$metadata_fields[] = $key;
			}
		}
	}

	$regularized_metadata = [];
	foreach ( $metadata as $row ) {
		foreach ( $metadata_fields as $field ) {
			if ( array_key_exists( $field, $row ) ) {
				$regularized_row[$field] = $row[$field];
			} else {
				$regularized_row[$field] = '';
			}
		}
		$regularized_metadata[] = $regularized_row;
	}

	return $regularized_metadata;
}

/**
 * Recursively implodes an array.
 * 
 * @param array $field The array to implode.
 */
function recursive_implode( $field, $delimiter = ';' ) {
	if ( is_array( $field ) ) {
		return implode( $delimiter, array_map( 
			function( $field ) {
				return recursive_implode( $field, '|' );
			},
			$field ) );
	} else {
		return $field;
	}
}

/**
 * Writes the metadata to a CSV file.
 */
function write_to_csv( $metadata, $filename ) {
	$fp = fopen( $filename, 'w' );
	fputcsv( $fp, array_keys( $metadata[0] ) );
	foreach ( $metadata as $row ) {
		foreach ( $row as $key => $value ) {
			$row[$key] = recursive_implode( $value, ';' );
		}
		fputcsv( $fp, $row );
	}
	fclose( $fp );
}

main( $args );