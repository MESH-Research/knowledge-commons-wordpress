#!/usr/bin/env php
<?php

/**
 * Script to export a subset of the Commons database and corresponding uploads folders.
 * 
 * This script should be run on the source server. DB and Uploads files will be saved to the current directory.
 * 
 * Usage:
 * export-partial-commons.php [args]
 * 	domain=<new domain>
 * 	uploads=true|false] [s3-prefix=<s3 prefix>]
 * 
 * After database is imported, replace @ with @sign to prevent spurious email notifications:
 * mysql -u$dev_db_user -p$dev_db_pass -h$dev_db_host $dev_db_name -e "UPDATE wp_users SET user_email = REPLACE(user_email,'@','@sign');"
 */

require_once __DIR__ . '/vendor/autoload.php';

use \Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\S3\MultipartUploader;
use Aws\Exception\MultipartUploadException;

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

const MSU_COMMONS_NAME         = 'MSU Commons'; // Name of MSU Commons -- needed for finding MSU user sites.
const WP_TABLE_PREFIX          = 'wp_';
const UPLOADS_PARENT_DIRECTORY = '/srv/www/commons/shared/';
const UPLOADS_DIRECTORY        = 'uploads/';
const S3_BUCKET                = 'kcommons-dev-content'; // AWS S3 bucket to upload files to.

function main( $args ) {
	load_env();
	
	$default_args = [
		'export-uploads'              => true,              // Export uploads folders.
		'export-sites'                => true,              // Export user sites.
		'only-domains'                => null,              // Export only these domains.
		'full-export'                 => false,             // Export all content.	
		'skip-content-generation'     => false,             // Skip generating content and just upload existing files to S3.
		'exclude-humcore-uploads'     => true,              // Exclude HumCORE uploads.
		'exclude-group-documents'     => true,              // Exclude group documents.
		'exclude-profile-attachments' => false,             // Exclude profile attachments. (CVs, etc.)
		'exclude-bp-attachments'      => false,             // Exclude BuddyPress attachments.
		'db-export-file'              => 'db.sql',          // Database export file.
		'uploads-export-file'         => 'uploads.tar.gz',  // Uploads export file.
		'summary-file'                => 'summary.txt',     // Summary file.
		's3-prefix'                   => null,              // S3 prefix to upload files to.
		'user-sites'                  => USER_SITES,        // User sites to export.
		'domain'                      => 'commons-wordpress.lndo.site', // New domain to replace old domain with.
		'source-domain'               => $_SERVER['WP_DOMAIN'],
		'help'                        => false,
		'randomize-emails'            => true,
	];

	$args = array_merge( $default_args, $args );

	if ( $args['help'] ) {
		show_help();
		return;
	}

	if ( ! $args['export-sites'] ) {
		$args['user-sites'] = [];
	}
	
	if ( ! $args['skip-content-generation'] ) {
		if ( $args['only-domains'] ) {
			echo "Overriding site domains...\n";
			echo "Exporting from " . $args['only-domains'] . "\n";
			$sites_override = explode( ',', $args['only-domains'] );
		} else {
			$sites_override = [];
		}
		
		$site_ids    = site_ids( $args['user-sites'], $sites_override );
		$table_names = get_table_names_for_dump( $site_ids );

		if ( $args['full-export'] ) {
			echo "Full export enabled.\n";
			$args['exclude-humcore-uploads'] = false;
			$args['exclude-group-documents'] = false;
			$args['exclude-profile-attachments'] = false;
			$args['exclude-bp-attachments'] = false;
			$table_names = [];
			$site_ids = [];
		}
	
		echo "Generating database export...\n";
		echo "Database Export File: " . $args['db-export-file'] . "\n";
		generate_db_export( $table_names, $args['domain'], $args['db-export-file'], $args['randomize-emails'] );
	
		if ( $args['export-uploads'] ) {
			echo "Generating uploads archive...\n";
			echo "Uploads Archive File: " . $args['uploads-export-file'] . "\n";
			generate_uploads_archive( 
				$site_ids,
				$args['uploads-export-file'],
				$args['exclude-humcore-uploads'],
				$args['exclude-group-documents'],
				$args['exclude-profile-attachments'],
				$args['exclude-bp-attachments']
			);
		} else {
			echo "Skipping uploads archive.\n";
		}
	} else {
		echo "Skipping content generation.\n";
	}

	if ( $args['summary-file'] ) {
		echo "Generating summary file...\n";
		echo "Summary File: " . $args['summary-file'] . "\n";
		generate_summary( $args );
	} else {
		echo "Skipping summary file.\n";
	}

	if ( isset( $args['s3-prefix'] ) ) {
		echo "Uploading files to S3...\n";
		upload_to_s3(
			$args['s3-prefix'],
			$args['export-uploads'],
			$args['db-export-file'],
			$args['uploads-export-file'],
			$args['summary-file']
		);
	}

	echo "Done.\n";
}

/**
 * Load necessary environment variables into $_SERVER.
 */
function load_env() {
	$vars = [
		'DB_HOST',
		'DB_USER',
		'DB_PASSWORD',
		'DB_NAME',
		'WP_DOMAIN',
	];
	foreach ( $vars as $var ) {
		if ( ! isset( $_SERVER[ $var ] ) ) {
			$_SERVER[ $var ] = getenv( $var );
		}
	}
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
	if ( $args === null ) {
		return [];
	}

	$parsed_args = [];
	foreach ( $args as $arg ) {
		if ( preg_match( '/^(.*)=(.*)$/', $arg, $matches ) ) {
			if ( strpos( $matches[2], ',' ) !== false ) {
				$parsed_args[ $matches[1] ] = explode( ',', $matches[2] );
			} elseif ( $matches[2] === 'true' ) {
				$parsed_args[ $matches[1] ] = true;
			} elseif ( $matches[2] === 'false' ) {
				$parsed_args[ $matches[1] ] = false;
			} else {
				$parsed_args[ $matches[1] ] = $matches[2];
			}
		} elseif ( preg_match( '/^(.*)$/', $arg, $matches ) ) {
			$parsed_args[ $matches[1] ] = true;
		} else {
			$parsed_args[] = $arg;
		}
	}

	return $parsed_args;
}

function show_help() {
	echo "Usage: ./export-partial-commons.php [arg=<value>]*\n";
	echo "Example: ./export-partial-commons.php export-uploads=false\n\n";
	echo "There should be no spaces between args and values\n\n";
	echo "Boolean args can be set to by passing no value or true (eg. export-uploads). They can be set false by passing arg=false\n\n";
	echo "Args:\n";
	echo "  export-uploads              Export uploads folders.\n";
	echo "                              (Default: true)\n\n";
	echo "  export-sites                Export user sites.\n";
	echo "                              (Default: true)\n\n";
	echo "  full-export                 Export all content.\n";
	echo "							    (Default: false)\n\n";
	echo "  skip-content-generation     Skip generating content and just upload existing files to S3.\n";
	echo "                              (Default: false)\n\n";
	echo "  exclude-humcore-uploads     Exclude HumCORE uploads.\n";
	echo "                              (Default: true)\n\n";
	echo "  exclude-group-documents     Exclude group documents.\n";
	echo "                              (Default: true)\n\n";
	echo "  exclude-profile-attachments Exclude profile attachments. (CVs, etc.)\n";
	echo "                              (Default: false)\n\n";
	echo "  exclude-bp-attachments      Exclude BuddyPress attachments.\n";
	echo "                              (Default: false)\n\n";
	echo "  db-export-file              Database export file.\n";
	echo "                              (Default: db.sql)\n\n";
	echo "  uploads-export-file         Uploads export file.\n";
	echo "                              (Default: uploads.tar.gz)\n\n";
	echo "  summary-file                Summary file.\n";
	echo "                              (Default: summary.txt)\n\n";
	echo "  s3-prefix                   S3 prefix to upload files to.\n";
	echo "                              (Default: None)\n\n";
	echo "  user-sites                  Subdomains of user sites to export. Comma-separated list.\n";
	echo "                              (Eg. schopie1.msu,teaching-learning.hastac,building)\n";
	echo "                              (Default: USER_SITES constant)\n\n";
	echo "  domain                      New domain to replace old domain with.\n";
	echo "                              (Default: commons-wordpress.lndo.site)\n\n";
	echo "  source-domain               Source domain to replace.\n";
	echo "                              (Default: value of WP_DOMAIN ENV variable)\n\n";
	echo "  help                        Show help.\n";
}

/**
 * Gets site ids of all sites to be exported.
 */
function site_ids( $user_sites, $sites_override = [] ) {
	if ( count( $sites_override ) == 0 ) {
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
	} else {
		echo "Overriding site domains...\n";
		$site_domains = $sites_override;
	}

	echo "Exporting " . count( $site_domains ) . " sites...\n";
	
	//Get site ids
	$site_ids = [];
	foreach ( $site_domains as $domain ) {
		$site_ids[] = get_blog_id_from_domain( $domain );
	}

	return $site_ids;
}

/**
 * Replicates the get_networks function from wp-cli without requiring $wpdb.
 */
function get_networks() {
	$mysqli = new mysqli( $_SERVER['DB_HOST'], $_SERVER['DB_USER'], $_SERVER['DB_PASSWORD'], $_SERVER['DB_NAME'] );
	if ( $mysqli->connect_error ) {
		die( 'Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error );
	}
	$result = $mysqli->query( "
		SELECT wp_site.domain, wp_sitemeta.meta_value 
		FROM wp_site 
		LEFT JOIN wp_sitemeta 
		ON wp_site.id = wp_sitemeta.site_id 
		WHERE meta_key = 'site_name';"
	);

	$networks = [];
	while ( $row = $result->fetch_assoc() ) {
		$network = new stdClass();
		$network->domain = $row['domain'];
		$network->site_name = $row['meta_value'];
		$networks[] = $network;
	}

	return $networks;
}

function get_blog_id_from_domain( $domain ) {
	$mysqli = new mysqli( $_SERVER['DB_HOST'], $_SERVER['DB_USER'], $_SERVER['DB_PASSWORD'], $_SERVER['DB_NAME'] );
	if ( $mysqli->connect_error ) {
		die( 'Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error );
	}
	$result = $mysqli->query( "
		SELECT blog_id 
		FROM wp_blogs 
		WHERE domain = '" . $domain . "';"
	);
	$row = $result->fetch_assoc();
	if ( ! $row ) {
		return null;
	}
	return $row['blog_id'];
}

/**
 * Gets names of all tables to be exported.
 */
function get_table_names_for_dump( $site_ids ) {
		$mysqli = new mysqli( $_SERVER['DB_HOST'], $_SERVER['DB_USER'], $_SERVER['DB_PASSWORD'], $_SERVER['DB_NAME'] );
		if ( $mysqli->connect_error ) {
			die( 'Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error );
		}
	
		$sql = "
			SELECT DISTINCT table_name
			FROM information_schema.tables
			WHERE table_schema = 'hcommons'
			AND table_name REGEXP '^[^0-9]*$'
		";

		foreach ( $site_ids as $site_id ) {
			$sql .= " OR table_name LIKE '" . WP_TABLE_PREFIX . $site_id . "\\_%'";
		}
		$sql .= ';';
		$result = $mysqli->query( $sql );
		if ( ! $result ) {
			return [];
		}
		$table_names = [];
		while ( $row = $result->fetch_assoc() ) {
			$table_names[] = $row['table_name'];
		}
		return $table_names;
}

/**
 * Generates mysqldump file.
 */
function generate_db_export( $table_names, $new_domain, $db_export_file, $randomize_emails = false ) {
	if ( file_exists( $db_export_file ) ) {
		echo "Removing existing database export...\n";
		unlink( $db_export_file );
	}
	$table_list = implode( ' ', $table_names );
	$command = "mysqldump -h {$_SERVER['DB_HOST']} -u {$_SERVER['DB_USER']} -p{$_SERVER['DB_PASSWORD']} --lock-tables=false hcommons $table_list > " . $db_export_file;
	//echo $command . "\n";
	exec( $command );
	if ( $new_domain ) {
		echo "Replacing " . $_SERVER['WP_DOMAIN'] . " with $new_domain in database export...\n";
		exec( "mv " . $db_export_file . " old-" . $db_export_file );
		$command = "cat old-" . $db_export_file . " | go-search-replace " . $_SERVER['WP_DOMAIN'] . " $new_domain > " . $db_export_file;
		echo $command . "\n";
		exec( $command );
		unlink( "old-" . $db_export_file );
	}

	if ( $randomize_emails ) {
		echo "Randomizing emails in database export...\n";
		$command = "sed -i 's/[A-Za-z0-9._%+-]*@[A-Za-z0-9.-]*\.[A-Za-z]{2,4}/'$(openssl rand -hex 16)'@example.com/g' " . $db_export_file;
		exec($command);
	}
}

/**
 * Generates uploads archive.
 */
function generate_uploads_archive( 
		$site_ids,
		$uploads_export_file,
		$exclude_humcore_uploads,
		$exclude_group_documents,
		$exclude_profile_attachments,
		$exclude_bp_attachments 
	) {

	if ( file_exists( $uploads_export_file ) ) {
		echo "Removing existing uploads export...\n";
		unlink( $uploads_export_file );
	}

	$tar_without_gz = str_replace( '.tar.gz', '.tar', $uploads_export_file );
	$tar_file_path = getcwd() . '/' . $tar_without_gz;
	$exclude_clauses = "";
	if ( $exclude_humcore_uploads ) {
		$exclude_clauses .= " --exclude='humcore'";
	}
	if ( $exclude_group_documents ) {
		$exclude_clauses .= " --exclude='group-documents'";
	}
	if ( $exclude_profile_attachments ) {
		$exclude_clauses .= " --exclude='bp-attachment-xprofile'";
	}
	if ( $exclude_bp_attachments ) {
		$exclude_clauses .= " --exclude='bp_attachments'";
	}
	if ( is_array( $site_ids ) && count( $site_ids ) > 0 ) {
		$site_exclude_clause = " --exclude='sites'";
	} else {
		$site_ids = [];
		$site_exclude_clause = "";
	}
	$command = "tar $site_exclude_clause $exclude_clauses -cf $tar_file_path -C " . UPLOADS_PARENT_DIRECTORY . " " . UPLOADS_DIRECTORY;
	echo $command . "\n";
	$result = exec( $command );
	foreach ( $site_ids as $site_id ) {
		if ( $site_id === 1 ) {
			continue;
		}
		echo "Adding site $site_id...\n";
		$command = "tar $exclude_clauses -rf $tar_file_path -C " . UPLOADS_PARENT_DIRECTORY . " " . UPLOADS_DIRECTORY . "sites/$site_id ";
		echo $command . "\n";
		$result = exec( $command );
	}
	$command = "gzip " . $tar_file_path;
	echo $command . "\n";
	$result = exec( $command );
}

/**
 * Uploads files to S3.
 */
function upload_to_s3( $prefix, $export_uploads, $db_export_file, $uploads_export_file, $summary_file ) {
	$client = new S3Client( [
		'region' => 'us-east-1',
		'version' => 'latest',
	] );

	echo "Uploading content to S3 bucket " . S3_BUCKET . " with prefix $prefix...\n";

	if ( file_exists( $db_export_file ) ) {
		echo "Uploading database export file " . $db_export_file . " to S3...\n";
		$client->putObject( [
			'Bucket'     => S3_BUCKET,
			'Key'        => "$prefix/$db_export_file",
			'SourceFile' => $db_export_file,
		] );
		echo "Database export file uploaded.\n";
	}

	if ( $export_uploads && file_exists( $uploads_export_file ) ) {
		echo "Uploading uploads archive $uploads_export_file to S3...\n";
		$uploader = new MultipartUploader($client, $uploads_export_file, [
			'bucket' => S3_BUCKET,
			'key'    => "$prefix/$uploads_export_file",
		] );
		
		try {
			$result = $uploader->upload();
			echo "Upload complete: {$result['ObjectURL']}\n";
		} catch (MultipartUploadException $e) {
			echo $e->getMessage() . "\n";
		}

		echo "Uploads archive uploaded.\n";
	}

	if ( $summary_file && file_exists( $summary_file ) ) {
		echo "Uploading summary file $summary_file to S3...\n";
		$client->putObject( [
			'Bucket'     => S3_BUCKET,
			'Key'        => "$prefix/$summary_file",
			'SourceFile' => $summary_file,
		] );
		echo "Summary file uploaded.\n";
	}
}

function generate_summary( $args ) {
	$args = array_map( function( $arg ) {
		if ( is_array( $arg ) ) {
			return implode( ', ', $arg );
		} elseif ( is_bool( $arg ) ) {
			return $arg ? 'yes' : 'no';
		}
		return $arg;
	}, $args );
	
	$summary = "Source Domain: {$args['source-domain']}\n";
	$summary .= "Target Domain: {$args['domain']}\n";
	if ( $args['export-sites'] == 'yes' ) {
		$summary .= "User sites: {$args['user-sites']}\n";
	} else {
		$summary .= "User sites: no\n";
	}
	$summary .= "Exclude humcore uploads: {$args['exclude-humcore-uploads']}\n";
	$summary .= "Exclude group documents: {$args['exclude-group-documents']}\n";
	$summary .= "Exclude profile attachments: {$args['exclude-profile-attachments']}\n";
	$summary .= "Exclude BP attachments: {$args['exclude-bp-attachments']}\n";

	if ( file_exists( $args['summary-file'] ) ) {
		unlink( $args['summary-file'] );
	}
	file_put_contents( $args['summary-file'], $summary );
}

$parsed_args = parse_args( $argv );
main( $parsed_args );
