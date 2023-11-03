<?php
/**
 * Reset all local WordPress passwords to 'password'.
 * 
 * **WARNING**: This will reset all local WordPress passwords to 'password'. 
 * It should only be run on a local development environment!
 * 
 * Usage: lando wp eval-file reset-local-wp-passwords.php
 */

namespace MESHResearch\KCScripts;

require_once( __DIR__ . '/lib/command-line.php' );

function main( array $args = [] ) : void {
	global $wpdb;
	
	$args = parse_wp_cli_args( $args );
	
	$hashed_password = wp_hash_password( 'password' );

	$sql = "UPDATE $wpdb->users SET user_pass = '$hashed_password'";
	$result = $wpdb->query( $sql );

	if ( $result ) {
		echo "All ($result) passwords reset to 'password'.\n";
	} else {
		echo "Failed to reset passwords.\n";
	}
}

main( $args );
