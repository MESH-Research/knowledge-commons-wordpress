<?php
/**
 * Update URLs of MSU Commons sites to new domain.
 * 
 * Syntax: wp eval-file ./rewrite-msu-site-domains.php
 */

global $wpdb;

// Are we really doing this?
$dry_run = true;

$old_domain = 'msu.msucommons-dev.org';
$new_domain = 'commons-dev.meshresearch.net';
$network_id = 7; // Just for MSU Network sites

$network = get_network( $network_id );
echo "Network: {$network->site_name}\n";

$sites = get_sites( [ 'network_id' => $network_id, 'number' => 9999 ] );
foreach ( $sites as $site ) {
	echo "Updating {$site->blog_id}: {$site->domain} \n";
	$current_site_domain = $site->domain;
	$domain_parts = explode('.', $current_site_domain );
	$current_base_domain = join( '.', array_slice( $domain_parts, 1 ) );
	$site_slug = $domain_parts[0];
	// Make sure we're updating the right domains
	if ( $current_base_domain !== $old_domain ) {
		echo "-- Site domain mismatch. Skipping \n";
		continue;
	}
	if ( $site_slug == 'msu' ) {
		echo "-- Skipping base blog \n";
		continue;
	}
	$query = "SELECT blog_id, site_id, domain FROM wp_blogs WHERE blog_id={$site->blog_id}";
	$results = $wpdb->get_results( $query );
	if ( $results ) {
		$blog_table_domain = $results[0]->domain;
		$blog_base_domain = join( '.', array_slice( explode( '.', $blog_table_domain ) , 1 ) );
		$new_site_domain = "$site_slug.$new_domain";

		// Update site options
		switch_to_blog( $site->blog_id );
		echo "-- Updating $current_site_domain to $new_site_domain \n";
		$old_siteurl = get_option( 'siteurl', false );
		$old_home = get_option( 'home', false );
		$new_siteurl = "https://$new_site_domain/";
		$new_home = "https://$new_site_domain/";
		echo "-- siteurl: $old_siteurl -> $new_siteurl | $old_home -> $new_home \n";
		if ( ! $dry_run ) {
			update_option( 'siteurl', $new_siteurl );
			update_option( 'home', $new_home );
			echo "-- updated options \n";
		}
		restore_current_blog();

		// Update wp_blogs table
		if ( ! $dry_run ) {
			$query = "UPDATE wp_blogs SET domain='$new_site_domain' WHERE blog_id={$site->blog_id}";
			$success = $wpdb->query( $query );
			if ( $success === false ) {
				echo "-- error updating domain \n";
				echo "---- $query \n";
			} else {
				echo "-- updated domain \n";
			}
		}
	}	
}
