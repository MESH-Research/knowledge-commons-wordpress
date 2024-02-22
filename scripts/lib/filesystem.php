<?php
/**
 * Filesystem utility functions.
 */

namespace MESHResearch\KCScripts;

function bytes_to_human_readable( int $bytes ) : string {
	$units = [ 'B', 'KB', 'MB', 'GB', 'TB', 'PB' ];
	$bytes = max( $bytes, 0 );
	$pow = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
	$pow = min( $pow, count( $units ) - 1 );
	$bytes /= pow( 1024, $pow );
	return round( $bytes, 2 ) . ' ' . $units[ $pow ];
}

function untrailingslashit( string $value ) : string {
	return rtrim( $value, '/\\' );
}

function trailingslashit( string $value ) : string {
	return untrailingslashit( $value ) . '/';
}

function create_temp_directory( bool $in_project = false ) : string {
	if ( $in_project ) {
		$base_directory = get_project_root();
	} else {
		$base_directory = sys_get_temp_dir();
	}
	
	$temp_directory = trailingslashit( $base_directory ) . uniqid( 'kc-scripts-' );
	mkdir( $temp_directory );
	return $temp_directory;
}

function container_path_from_host_path( string $host_path ) : string {
	$host_path = untrailingslashit( $host_path );
	$project_root = untrailingslashit( get_project_root() );
	$container_path = '/app' . str_replace( $project_root, '', $host_path );
	return $container_path;
}

function file_put_contents_new_directory( string $path, string $contents ) : int {
	$directory = dirname( $path );
	if ( ! file_exists( $directory ) ) {
		mkdir( $directory, 0777, true );
	}
	return file_put_contents( $path, $contents );
}