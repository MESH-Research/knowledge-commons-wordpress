<?php
/**
 * Script to list all sites from a network.
 * 
 * Usage: wp eval-file export-network-sites.php --url=<base network url>
 * 
 * Note: run in an empty directory, as an export file will be generated for each site on the network.
 */

function main() {
	// switch_to_network( ID );
	$domain = parse_url( get_site_url(), PHP_URL_HOST );
	$networks = get_networks( [ 'domain' => $domain ] );
	$sites = get_sites( ['network_id' => $networks[0]->id] );
	foreach ( $sites as $site ) {
		echo "{$site->domain}\n";
	}

}

main();