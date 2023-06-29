#!/usr/bin/env php
<?php
/**
 * Interact with AWS S3 kcommons-dev-content bucket.
 * 
 * Usage: ./s3-pull.php [prefix] [options]
 * 	--import-prefix=<prefix>		Import db and uploads from <prefix>.
 *  [prefix]						Alias for --import-prefix.
 * 	--get-prefix=<prefix>			Download all files from <prefix>.
 * 	--list-prefixes					List all prefixes in bucket.
 * 	--destination-dir=<dir>			Download files to <dir>. Applies only to --get-prefix.
 *  --just-db						Only import the database. Applies only to --import-prefix.
 *  --just-uploads					Only import the uploads. Applies only to --import-prefix.
 *  --backup-uploads				Move uploads folder to uploads-old before extracting new uploads dir.
 * 									Applies only to --import-prefix.
 *
 * If not options are passed, --list-prefixes is assumed.
 */

namespace MESHResearch\KCScripts;

require_once __DIR__ . '/lib/composer-autoload.php';
require_once __DIR__ . '/lib/command-line.php';
require_once __DIR__ . '/lib/filesystem.php';
require_once __DIR__ . '/lib/aws.php';
require_once __DIR__ . '/lib/git.php';

use Aws\S3\S3Client;

const BUCKET = 'kcommons-dev-content';
 
function main() : void {
	$args = parse_command_line_args();
	if ( isset( $args[0] ) ) {
		$args['import-prefix'] = $args[0];
	}
	
	$client = new S3Client( [
		'region' => 'us-east-1',
		'version' => 'latest',
	] );

	if ( count( $args ) === 0 || isset( $args['list-prefixes'] ) ) {
		list_prefixes( $client );
	} elseif ( isset( $args['get-prefix'] ) ) {
		if ( isset( $args['destination-dir'] ) ) {
			$destination = $args['destination-dir'];
		} else {
			$destination = getcwd();
		}
		get_all_from_prefix( $client, $args['get-prefix'], $destination );
	} elseif ( isset( $args['import-prefix'] ) ) {
		import_content_from_prefix( 
			client:         $client, 
			prefix:         $args['import-prefix'],
			just_db:        isset( $args['just-db'] ),
			just_uploads:   isset( $args['just-uploads'] ),
			backup_uploads: isset( $args['backup-uploads'] ) 
		);
	}
}

function list_prefixes( S3Client $client ) {
	$result = $client->listObjects( [
		'Bucket' => BUCKET,
		'Delimiter' => '/',
	] );

	if ( ! isset( $result['CommonPrefixes'] ) ) {
		throw new \Exception( 'CommonPrefixes not found in S3.' );
	}
	
	foreach( $result['CommonPrefixes'] as $prefix ) {
		echo $prefix['Prefix'] . "\n";
		
		$prefix_contents = list_from_prefix( $client, $prefix['Prefix'] );

		$format_string = "\t%-40s %10s %s\n";

		foreach( $prefix_contents as $content ) {
			printf( 
				$format_string, 
				$content['Key'], 
				bytes_to_human_readable( $content['Size'] ), 
				$content['LastModified'] 
			);
		}
	}
}

function import_content_from_prefix( 
		S3Client $client, 
		string $prefix,
		bool $just_db = false,
		bool $just_uploads = false,
		bool $backup_uploads = false
	) {
	echo "Importing content from $prefix...\n";
	$objects = list_from_prefix( $client, $prefix );
	
	$sql_file = '';
	$uploads_archive_file = '';

	foreach ( $objects as $object ) {
		if ( 'sql' === pathinfo( $object['Key'], PATHINFO_EXTENSION ) ) {
			$sql_file = $object['Key'];
		} elseif ( 
			'gz' === pathinfo( $object['Key'], PATHINFO_EXTENSION ) ||
			'tgz' === pathinfo( $object['Key'], PATHINFO_EXTENSION )
		) {
			$uploads_archive_file = $object['Key'];
		}
	}

	if ( ! $sql_file && ! $uploads_archive_file ) {
		echo "No files found. Exiting.\n";
		return;
	}

	$temp_directory = create_temp_directory( true );
	echo "Using temp directory $temp_directory\n";

	if ( ! $just_uploads && $sql_file ) {
		echo "Found SQL file: $sql_file\n";
		db_import( $client, $temp_directory, $sql_file );
	} else {
		echo "No SQL file found.\n";
	}

	if ( ! $just_db && $uploads_archive_file ) {
		echo "Found uploads archive file: $uploads_archive_file\n";
		uploads_import( $client, $temp_directory, $uploads_archive_file, $backup_uploads );
	} else {
		echo "No uploads archive file found.\n";
	}

	echo "Deleting temp directory $temp_directory\n";
	`rm -rf $temp_directory`;
}

function db_import( S3Client $client, string $temp_directory, string $key ) : void {
	$destination_path = trailingslashit( $temp_directory ) . filename_from_key( $key );
	echo "Downloading $key...\n";
	$client->getObject( [
		'Bucket' => BUCKET,
		'Key' => $key,
		'SaveAs' => $destination_path,
	] );
	echo "Importing $key...\n";
	$import_path = 
		trailingslashit( container_path_from_host_path( $temp_directory ) ) . filename_from_key( $key );
	$output = `lando db-import $import_path 2>&1`;
	echo $output;
	echo "Finished importing $key.\n";
}

function uploads_import( 
		S3Client $client, 
		string $temp_directory, 
		string $key, 
		bool $backup_uploads = false
	) : void {
	$destination_path = trailingslashit( $temp_directory ) . filename_from_key( $key );
	echo "Downloading $key...\n";
	$client->getObject( [
		'Bucket' => BUCKET,
		'Key' => $key,
		'SaveAs' => $destination_path,
	] );
	$uploads_dir_parent = trailingslashit( get_project_root() ) . 'site/web/app';
	if ( $backup_uploads ) {
		echo "Moving $uploads_dir_parent/uploads to $uploads_dir_parent/uploads-old\n";
		`mv $uploads_dir_parent/uploads $uploads_dir_parent/uploads-old`;
	} else {
		echo "Deleting $uploads_dir_parent/uploads\n";
		`rm -rf $uploads_dir_parent/uploads`;
	}
	echo "Extracting $key to $uploads_dir_parent...\n";
	$output = `tar -xzf $destination_path -C $uploads_dir_parent`;
	echo $output;
	echo "Finished extracting $key.\n";
}

function list_from_prefix( S3Client $client, string $prefix ) {
	$result = $client->listObjectsV2( [
		'Bucket' => BUCKET,
		'Prefix' => $prefix,
	] );

	if ( ! isset( $result['Contents'] ) ) {
		return [];
	}

	array_shift( $result['Contents'] ); // Remove the prefix itself (first element).

	$objects = array_map( function( $object ) {
		return [
			'Key' => $object['Key'],
			'Size' => $object['Size'],
			'LastModified' => $object['LastModified'],
		];
	}, $result['Contents'] );

	return $objects;
}

function get_all_from_prefix( S3Client $client, string $prefix, string $destination = '' ) {
	$objects = list_from_prefix( $client, $prefix );
	foreach ( $objects as $object ) {
		$destination_path = trailingslashit( $destination ) . filename_from_key( $object['Key'] );
		$result = $client->getObject( [
			'Bucket' => BUCKET,
			'Key' => $object['Key'],
			'SaveAs' => $destination_path,
		] )['Body'];
	}
}

main();