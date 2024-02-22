<?php
/**
 * Generates a CSV of sites using a particular plugin or theme in order to email
 * them.
 * 
 * Syntax: wp eval-file users_of_plugin_or_theme.php <plugin|theme> <slug> <outputfile.csv> [exclude=<network slugs>]
 * Example: wp eval-file users_of_plugin_or_theme.php plugin google-document-embedder ~/outputfile.csv exclude=mla
 * 
 * Note: arguments must be passed in exact order.
 */

function show_help() {
	echo "\nGenerates a CSV of sites using a particular plugin or theme in order to email them.\n\n";
	echo "Syntax: wp eval-file users_of_plugin_or_theme.php <plugin|theme> <slug> <outputfile.csv> [exclude=<network slugs>]\n";
	echo "Example: wp eval-file users_of_plugin_or_theme.php plugin google-document-embedder ~/outputfile.csv exclude=mla\n\n";
	echo "Note: arguments must be passed in exact order.\n";
	exit;
}

if ( count( $args ) < 3 ) {
	echo "Not enough arguments.\n";
	show_help();
}

$excluded_network_slugs = [];
if ( count( $args ) === 4 ) {
	$split_args = explode( '=', $args[3] );
	if ( $split_args[0] !== 'exclude' ) {
		echo "Incorrect syntax for exclude networks.\n";
		show_help();
	}
	$excluded_network_slugs = explode( ',', $split_args[1] );
}

if ( $args[0] === 'plugin' ) {
	$plugin = true;
} elseif ( $args[0] === 'theme' ) {
	$plugin = false;
} else {
	show_help();
}

$slug = $args[1];
$output_filename = $args[2];

$output_fields = [
	'email',
	'name',
	'return_path',
	'metadata',
	'substitution_data',
	'tags'
];

$networks = get_networks();

$outputfile = fopen( $output_filename, 'w' );
if ( ! $outputfile ) {
	echo "Error: Unable to open $output_filename for writing.\n";
	show_help();
}
fputcsv( $outputfile, $output_fields );

$recipient_count = 0;
foreach ( $networks as $network ) {
	$network_slug = explode( '.', $network->domain )[0];
	if ( in_array( $network_slug, $excluded_network_slugs ) ) {
		continue;
	}
	switch_to_network( $network->id, true );
	// Network active plugins. All sites in network will be added to recipient list.
	if ( $plugin ) {
		$network_plugins = get_site_option( 'active_sitewide_plugins' );
		$network_plugin_slugs = array_map(
			function ( $key ) {
				return explode( '/', $key )[0];
			},
			array_keys( $network_plugins )
		);
		if ( in_array( $slug, $network_plugin_slugs ) ) {
			$network_active = true;
		} else {
			$network_active = false;
		}
	}

	$sites = get_sites(
		[
			'network_id' => $network->id,
			'number'     => 0,
			'deleted'    => 0,
			'spam'       => 0
		]
	);

	foreach ( $sites as $site ) {
		$add_to_recipient_list = false;
		if ( $plugin ) {
			if ( $network_active ) {
				$add_to_recipient_list = true;
			} else {
				$active_plugins = get_blog_option( $site->blog_id, 'active_plugins' );
				$active_plugin_slugs = array_map(
					function ( $value ) {
						return explode( '/', $value )[0];
					},
					$active_plugins
				);
				if ( in_array( $slug, $active_plugin_slugs ) ) {
					$add_to_recipient_list = true;
				}
			}	
		} else {
			$active_theme = get_blog_option( $site->blog_id, 'stylesheet' );
			if ( $slug === explode( '/', $active_theme )[0] ) {
				$add_to_recipient_list = true;
			}
		}

		$email = get_blog_option( $site->blog_id, 'admin_email' );

		if ( $add_to_recipient_list && $email ) {
			$recipient_count += 1;
			$row = [
				get_blog_option( $site->blog_id, 'admin_email' ),
				get_blog_option( $site->blog_id, 'blogname' ),
				'hc@hcommons.org',
				'',
				"{\"site\": \"{$site->domain}\"}",
				''
			];
			fputcsv( $outputfile, $row );
		}
	}
}

echo "Generated $recipient_count recipients.\n";
