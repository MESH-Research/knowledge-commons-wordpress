<?php
/**
 * WP CLI script to delete sites from a CSV file.
 *
 * Syntax: wp eval-file delete-sites.php <csvfile.csv> [-dry-run]
 */

function parse_file() {
	global $argv;
	$site_data = [];
	if ( ! isset( $argv[3] ) ) {
		return $site_data;
	}
	
	$handle = fopen( $argv[3], 'r' );
	if ( ! $handle ) {
		return $site_data;
	}

	while ( $row = fgetcsv( $handle ) ) {
		$site_data[] = $row;
	}
	fclose( $handle );
	return $site_data;
}

function fix_base_domain ( $domain ) {
	global $current_site;
	$fixed_domain = '';
	$domain_parts = explode( '.', $domain );
	$domain_parts_count = count( $domain_parts );
	$correct_domain_parts = explode( '.', $current_site->domain );
	$correct_domain_parts_count = count( $correct_domain_parts );
	for ( $i = 0; $i < $domain_parts_count - 2; $i++) {
		$fixed_domain .= $domain_parts[$i] . '.';
	}
	$fixed_domain .= $correct_domain_parts[ $correct_domain_parts_count -2 ] . '.' . $correct_domain_parts[ $correct_domain_parts_count -1 ];
	return $fixed_domain;
}

global $argv;
$dry_run = ( isset( $argv[4] ) && $argv[4] === '-dry-run' );
if ( $dry_run ) {
	echo "***Dry Run***\n";
}
$site_data = parse_file();
foreach ( $site_data as $site_row ) {
	if ( ! str_ends_with( $site_row[2], '.org' ) ) {
		echo "Skipping {$site_row[2]}\n";
		continue;
	}
	$domain = fix_base_domain( $site_row[0] );
	$network = get_networks( [ 'domain' => $domain ] )[0];
	switch_to_network( $network );
	$blog_url = fix_base_domain( $site_row[2] );
	$blog_id = get_blog_id_from_url( $blog_url );
	echo "Deleting $blog_url with ID $blog_id on network ID {$network->id}\n";
	if ( ! $dry_run ) {
		wp_delete_site( $blog_id );
	}
}