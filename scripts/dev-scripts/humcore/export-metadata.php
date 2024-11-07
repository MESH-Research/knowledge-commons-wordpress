<?php

/**
 * Exports metadata from WordPress into CSV file.
 * 
 * Usage wp eval-file export-metadata.php [csv|json] [outputfilename] 
 */

const MAX_ROWS = 99999999;

function main( $args ) {
	$mode = $args[0];
	$base_sites = get_base_sites();
	$combined_metadata = [];
	foreach ( $base_sites as $base_site ) {
		$metadata = get_deposit_metadata( $base_site['blog_id'], $base_site['domain'] );
		$combined_metadata = array_merge( $combined_metadata, $metadata );
	}
	$regularized_metadata = regularize_metadata( $combined_metadata );
	if ( $mode ===  'csv' ) {
		write_to_csv( $regularized_metadata, $args[1] );
	} elseif ( $mode === 'json' ) {
		write_to_json( $regularized_metadata, $args[1] );
	} else {
		echo "Invalid mode: $mode\n";
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

/**
 * Gets deposit metadata for all deposits in a blog.
 * 
 * @param int $blog_id The blog ID.
 * 
 * @return array The deposit metadata. List of associative arrays with dynamically generated keys.
 */
function get_deposit_metadata( $blog_id, $domain ) {
	global $wpdb;

	switch_to_blog( $blog_id );

	if ( intval( $blog_id ) === 1 ) {
		$blog_prefix = '';
	} else {
		$blog_prefix = $blog_id . '_';
	}

	$result = $wpdb->get_results(
		$wpdb->prepare(
			"
			SELECT DISTINCT 
				d.post_id AS deposit_id,
				d.meta_value AS metadata,
				f.meta_value AS filedata,
				u.user_login AS submitter_login, 
				u.user_email AS submitter_email
			FROM {$wpdb->base_prefix}{$blog_prefix}postmeta as d
			LEFT JOIN {$wpdb->base_prefix}{$blog_prefix}posts as p ON d.post_id = p.ID
			LEFT JOIN {$wpdb->base_prefix}{$blog_prefix}postmeta as f ON d.post_id = f.post_id
			LEFT JOIN {$wpdb->base_prefix}users as u ON p.post_author = u.ID
			WHERE d.meta_key = '_deposit_metadata'
			AND f.meta_key = '_deposit_file_metadata'
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
		$pid = $deposit_metadata['pid'];
		$downloads = get_post_meta( $row->deposit_id, "_total_downloads_CONTENT_$pid", true );
		$views = get_post_meta( $row->deposit_id, "_total_views_CONTENT_$pid", true );
		$metadata_row = array_merge( 
			[ 
				'deposit_post_id' => $row->deposit_id,
				'total_downloads' => $downloads ? $downloads : 0,
				'total_views' => $views ? $views : 0,
				'submitter_login' => $row->submitter_login, 
				'submitter_email' => $row->submitter_email 
			], 
			$deposit_metadata, 
			$deposit_file_metadata
		);
		$metadata[] = $metadata_row;
	}

	restore_current_blog();

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
 * Writes the metadata to a CSV file.
 */
function write_to_csv( $metadata, $filename ) {
	$fp = fopen( $filename, 'w' );
	fputcsv( $fp, array_keys( $metadata[0] ) );
	foreach ( $metadata as $row ) {
		foreach ( $row as $key => $value ) {
			if ( ! is_array( $value ) && strpos( $value, 'hc:43939' ) !== false ) {
				xdebug_break();
			}
			if ( is_array( $value ) ) {
				array_walk( $value, function( &$value, $key ) {
					$value = str_replace( '\"', '"', $value );
				} );
				$value = json_encode( $value );
			} elseif ( is_string( $value ) ) {
				$value = str_replace( '\"', '""', $value );
			}
			$value = str_replace( [ "\n", "\r" ], [ "\\n", "" ], $value );
			$row[$key] = $value;
		}
		fputcsv( $fp, $row );
	}
	fclose( $fp );
}

function write_to_json( $metadata, $filename ) {
	$fp = fopen( $filename, 'w' );
	fwrite( $fp, json_encode( $metadata ) );
	fclose( $fp );
}

main( $args );