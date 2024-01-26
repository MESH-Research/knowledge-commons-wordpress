<?php

/**
 * Script to export a subset of the Commons database and corresponding uploads folders.
 * 
 * This script should be run on the source server. DB and Uploads files will be saved to the current directory.
 * 
 * Usage:
 * wp eval-file export-partial-commons.php [domain=<new domain>] [uploads=true|false]
 * 
 * After database is imported, replace @ with @sign to prevent spurious email notifications:
 * mysql -u$dev_db_user -p$dev_db_pass -h$dev_db_host $dev_db_name -e "UPDATE wp_users SET user_email = REPLACE(user_email,'@','@sign');"
 */

// These sites will be exported in addition to all base network sites.
const USER_SITES = [
	'sustaining',
	'building',
	'growing',
	'support',
	'team',
	'digitalpedagogy',
	'dahd',
	'news.mla',
	'president.mla',
	'jobs.up',
	'publishing-archives.hastac',
	'social-political-issues.hastac',
	'technology-networks-sciences.hastac',
	'humanities-arts-media.hastac',
	'teaching-learning.hastac',
	'schopie1.msu',
];

const EXPORT_UPLOADS          = true;   // Export uploads folders. If false, overrides uploads=true|false.

const EXCLUDE_HUMCORE_UPLOADS = true;   // HumCORE uploads are large and not needed for most exports.
const EXCLUDE_GROUP_DOCUMENTS = true;   // Group documents are large and not needed for most exports.
const EXCLUDE_SITES           = false;  // Exclude user sites from export.

const MSU_COMMONS_NAME = 'MSU Commons'; // Name of MSU Commons -- needed for finding MSU user sites.

const WP_TABLE_PREFIX = 'wp_';

const UPLOADS_PARENT_DIRECTORY = '/srv/www/commons/shared/';
const UPLOADS_DIRECTORY = 'uploads/';

const DB_EXPORT_FILE      = 'db.sql';
const UPLOADS_EXPORT_FILE = 'uploads.tar'; // .gz will be appended to this filename when compressed.

function main( $args ) {
	if ( ! status_check( $args ) ) {
		echo "Exiting.\n";
		exit;
	}

	$site_ids    = site_ids();
	$table_names = get_table_names_for_dump( $site_ids );

	echo "Generating database export...\n";
	echo "Database Export File: " . DB_EXPORT_FILE . "\n";
	generate_db_export( $table_names, $args['domain'] );

	if ( EXPORT_UPLOADS && ( ! array_key_exists( 'uploads', $args ) || $args['uploads'] === 'true' ) ) {
		echo "Generating uploads archive...\n";
		echo "Uploads Archive File: " . UPLOADS_EXPORT_FILE . "\n";
		generate_uploads_archive( $site_ids );
	} else {
		echo "Skipping uploads archive.\n";
	}

	echo "Done.\n";
}

/**
 * Parse args from command line.
 * 
 * Passed by wp-cli when using eval-file.
 * Args should be in the form of key=value (no spaces).
 *
 * @param array $args Command line args.
 * @return array Map of arg keys to values.
 */
function parse_args( $args ) {
	$arg_map = [];
	foreach ( $args as $arg ) {
		$arg_parts = explode( '=', $arg );
		$arg_map[ $arg_parts[0] ] = $arg_parts[1];
	}
	if ( ! $arg_map['domain'] ) {
		$arg_map['domain'] = '';
	}
	return $arg_map;
}

/**
 * Makes sure everything is ok for running the script.
 */
function status_check( $args ) {
	if ( file_exists( DB_EXPORT_FILE ) ) {
		echo "Database export file " . DB_EXPORT_FILE . " already exists.\n";
		return false;
	}

	if ( EXPORT_UPLOADS && ( ! array_key_exists( 'uploads', $args ) || $args['uploads'] === 'true' ) ) {
		if ( file_exists( UPLOADS_EXPORT_FILE ) ) {
			echo "Uploads archive file " . UPLOADS_EXPORT_FILE . " already exists.\n";
			return false;
		}
	
		if ( file_exists( UPLOADS_EXPORT_FILE . '.gz' ) ) {
			echo "Uploads archive file " . UPLOADS_EXPORT_FILE . " already exists.\n";
			return false;
		}
	}

	if ( $args['domain'] && ! shell_exec( "which go-search-replace") ) {
		echo "go-search-replace is required for domain replacement.\n";
		return false;
	}

	return true;
}

/**
 * Gets site ids of all sites to be exported.
 */
function site_ids( $user_sites = USER_SITES ) {
	//Get base site domains
	$site_domains  = [];
	$msu_domain    = '';
	$networks = get_networks();
	foreach ( $networks as $network ) {
		$site_domains[] = $network->domain;
		if ( $network->site_name === MSU_COMMONS_NAME ) {
			$msu_domain = $network->domain;
		}
	}

	//Get user site domains
	foreach ( $user_sites as $user_site ) {
		if ( strpos( $user_site, '.msu') !== false ) {
			$domain_parts = explode( '.', $user_site );
			$domain = implode( '.', array_slice( $domain_parts, 0, -1 ) );
			$site_domains[] = $domain . '.' . $msu_domain;
		} else {
			$site_domains[] = $user_site . '.' . $_SERVER['WP_DOMAIN'];
		}
	}

	//Get site ids
	$site_ids = [];
	foreach ( $site_domains as $domain ) {
		$site = get_site_by_path( $domain, '/' );
		if ( $site ) {
			$site_ids[] = $site->id;
		}
	}

	return $site_ids;
}

/**
 * Gets names of all tables to be exported.
 */
function get_table_names_for_dump( $site_ids ) {
		global $wpdb;
	
		$sql = "
			SELECT DISTINCT table_name
			FROM information_schema.tables
			WHERE table_schema = 'hcommons'
			AND table_name REGEXP '^[^0-9]*$'
		";

		foreach ( $site_ids as $site_id ) {
			$sql .= " OR table_name LIKE '" . WP_TABLE_PREFIX . $site_id . "\\_%'";
		}
		$results = $wpdb->get_results( $sql );
		if ( ! $results ) {
			return [];
		}
		$table_names = array_map( function( $result ) {
			return $result->table_name;
		}, $results );
		return $table_names;
}

/**
 * Generates mysqldump file.
 */
function generate_db_export( $table_names, $new_domain ) {
	$table_list = implode( ' ', $table_names );
	$command = "mysqldump -h {$_SERVER['DB_HOST']} -u {$_SERVER['DB_USER']} -p{$_SERVER['DB_PASSWORD']} --lock-tables=false hcommons $table_list > " . DB_EXPORT_FILE;
	echo $command . "\n";
	exec( $command );
	if ( $new_domain ) {
		echo "Replacing " . $_SERVER['WP_DOMAIN'] . " with $new_domain in database export...\n";
		$command = "cat " . DB_EXPORT_FILE . " | go-search-replace " . $_SERVER['WP_DOMAIN'] . " $new_domain >" . DB_EXPORT_FILE;
		echo $command . "\n";
		exec( $command );
	}
}

/**
 * Generates uploads archive.
 */
function generate_uploads_archive( $site_ids ) {
	$tar_file_path = getcwd() . '/' . UPLOADS_EXPORT_FILE;
	$exclude_clauses = "--exclude='sites'";
	if ( EXCLUDE_HUMCORE_UPLOADS ) {
		$exclude_clauses .= " --exclude='humcore'";
	}
	if ( EXCLUDE_GROUP_DOCUMENTS ) {
		$exclude_clauses .= " --exclude='group-documents'";
	}
	$command = "tar $exclude_clauses -cvf $tar_file_path -C " . UPLOADS_PARENT_DIRECTORY . " " . UPLOADS_DIRECTORY;
	echo $command . "\n";
	$result = exec( $command );
	if ( ! EXCLUDE_SITES ) {
		foreach ( $site_ids as $site_id ) {
			if ( $site_id === 1 ) {
				continue;
			}
			echo "Adding site $site_id...\n";
			$command = "tar -rvf $tar_file_path -C " . UPLOADS_PARENT_DIRECTORY . " " . UPLOADS_DIRECTORY . "sites/$site_id ";
			$result = exec( $command );
		}
	}
	$command = "gzip " . $tar_file_path;
	echo $command . "\n";
	$result = exec( $command );
}

$parsed_args = parse_args( $args );
main( $parsed_args );