<?php
/**
 * Script to generate an inventory of plugins or themes.
 * 
 * Usage: wp eval-file plugin-theme-inventory.php mode={plugin|theme} out={filename} in={filename} [format={csv|md|composer}]
 *     - mode    whether to generate inventory for plugins or themes
 *     - out     output filename
 *     - in      existing markdown inventory to be merged with new data
 *     - format  whether to generate CSV of data (default), markdown inventory template, or Composer requirements.
 */

define( 'COMMAND_LINE_SYNTAX_ERROR', 1 );

/**
 * Main function for the script.
 *
 * @param Array $args Script arguments as passed by WP CLI runner.
 */
 function main( $args ) {
	$parsed_args = parse_args( $args );
	$mode = $parsed_args['mode'];
	if ( ! $mode ) {
		throw new Exception( "'mode' argument is required.", COMMAND_LINE_SYNTAX_ERROR );
	}

	if ( array_key_exists( 'in', $parsed_args ) ) {
		$input_filename = $parsed_args['in'];
	} else {
		$input_filename = '';
	}

	$format = 'csv';
	if ( array_key_exists( 'format', $parsed_args ) ) {
		if ( $parsed_args['format'] === 'md' ) {
			$format = 'md';
		} elseif ( $parsed_args['format'] === 'composer' ) {
			$format = 'composer';
		}
	} elseif ( substr_compare( $parsed_args['out'], '.md', -3 ) === 0 ) {
		$format = 'md';
	}

	$output_filename = $parsed_args['out'];
	if ( ! $output_filename && $format !== 'composer' ) {
		throw new Exception( "'out' argument is required for CSV or Markdown output.", COMMAND_LINE_SYNTAX_ERROR );
	}

	if ( $mode === 'plugin' ) {
		$inventory_data = generate_plugin_data();
	} elseif ( $mode === 'theme' ) {
		$inventory_data = generate_theme_data();
	} else {
		throw new Exception( "'mode' argument must be either 'plugin' or 'theme'", COMMAND_LINE_SYNTAX_ERROR );
	}


	if ( $format === 'csv' ) {
		write_to_csv( $inventory_data, $output_filename );
	} elseif ( $format === 'md' ) {
		write_to_md( $inventory_data, $mode, $output_filename, $input_filename );
	} elseif ( $format === 'composer' ) {
		echo_composer_requirements( $inventory_data, $mode );
	} 
	else {
		throw new Exception( "Unrecognized output format." );
	}

	print( "Output saved to $output_filename\n\n" );
}

/**
 * Generates data for plugins on the site and returns as array.
 * 
 * @return Array Plugin data indexed by plugin slug.
 * 
 * For each plugin:
 *  - name                : user-readable name of the plugin
 *  - description         : short description of plugin's function
 *  - url                 : url of the plugin's page
 *  - wp-url              : url of plugin's page on wordpress.org
 *  - version             : current version of the plugin
 *  - update-version      : version available for update
 *  - update-version-date : when plugin was last updated
 *  - update-version-wp   : version of wordpress plugin updated tested with
 *  - network-active      : array of network slugs
 *  - site-active         : array of site urls
 *  - base-site-active    : array of base site urls
 *  - must-use            : whether the plugin is a must-use plugin
 *  - dropin              : whether the plugin is a dropin plugin
 */
function generate_plugin_data() {
	$plugin_data = [];

	$plugin_defaults = [
		'name'                => '',
		'description'         => '',
		'url'                 => '',
		'wp-url'              => '',
		'version'             => '',
		'update-version'      => '',
		'update-version-date' => '',
		'update-version-wp'   => '',
		'network-active'      => [],
		'site-active'         => [],
		'base-site-active'    => [],
		'must-use'            => false,
		'dropin'              => false,
	];

	$normal_plugins = get_plugins();
	foreach ( $normal_plugins as $plugin_file => $plugin ) {
		$plugin_slug = get_plugin_slug( $plugin_file );
		$plugin_data[ $plugin_slug ] = $plugin_defaults;
	}
	$mu_plugins = get_mu_plugins();
	foreach ( $mu_plugins as $mup_file => $mup ) {
		$plugin_slug = get_plugin_slug( $mup_file );
		$plugin_data[ $plugin_slug ] = $plugin_defaults;
		$plugin_data[ $plugin_slug ]['must-use'] = true;
	}
	$dropins = get_dropins();
	foreach( $dropins as $dropin_file => $dropin ) {
		$plugin_slug = get_plugin_slug( $dropin_file );
		$plugin_data[ $plugin_slug ] = $plugin_defaults;
		$plugin_data[ $plugin_slug ]['dropin'] = true;
	}

	$plugins = array_merge( $normal_plugins, $mu_plugins, $dropins );

	foreach ( $plugins as $plugin_file => $plugin ) {
		$plugin_slug = get_plugin_slug( $plugin_file );

		$plugin_data[ $plugin_slug ]['name']        = $plugin['Name'];
		$plugin_data[ $plugin_slug ]['url']         = $plugin['PluginURI'];
		$plugin_data[ $plugin_slug ]['version']     = $plugin['Version'];
		$plugin_data[ $plugin_slug ]['description'] = $plugin['Description'];

		try {
			$wordpress_org_url = "https://wordpress.org/plugins/$plugin_slug/";

			$plugin_remote_data = get_plugin_remote_data( $plugin_slug );

			$plugin_data[ $plugin_slug ]['update-version']      = $plugin_remote_data['version'];
			$plugin_data[ $plugin_slug ]['update-version-date'] = $plugin_remote_data['last_updated'];
			$plugin_data[ $plugin_slug ]['update-version-wp']   = $plugin_remote_data['tested'];		
			$plugin_data[ $plugin_slug ]['wp-url']              = $wordpress_org_url;
		}
		catch ( Exception $e ) {
			if ( $e->getCode() !== 404 ) {
				throw $e;
			}
		}
	}

	$base_site_domains = [];
	foreach ( get_networks() as $network ) {
		switch_to_network( $network->id, true );
		$base_site_domains[] = $network->domain;
		$network_active_plugins = wp_get_active_network_plugins();

		print( "Processing plugins for network {$network->domain}.\n" );

		foreach ( $network_active_plugins as $plugin_path ) {
			$plugin_slug = get_plugin_slug( $plugin_path );
			$plugin_data[ $plugin_slug ]['network-active'][] = get_network_slug( $network->domain );
		}

		$sites = get_sites(
			[
				'network_id' => $network->id,
				'number'     => 0,	
			]
		);

		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );
			$plugin_files = wp_get_active_and_valid_plugins();
			foreach ( $plugin_files as $plugin_file ) {
				$plugin_slug = get_plugin_slug( $plugin_file );

				if ( in_array( $plugin_file, $network_active_plugins ) ) {
					continue;
				}

				if ( in_array( $site->domain, $base_site_domains ) ) {
					if( ! in_array( $site->domain, $plugin_data[ $plugin_slug ]['base-site-active'] ) ) {
						$plugin_data[ $plugin_slug ]['base-site-active'][] = $site->domain;
					}
				} else {
					if( ! in_array( $site->domain, $plugin_data[ $plugin_slug ]['site-active'] ) ) {
						$plugin_data[ $plugin_slug ]['site-active'][] = $site->domain;
					}
				}
			}
			restore_current_blog();
		}
		restore_current_network();
	}

	foreach ( $plugin_data as $slug => &$entry_data ) {
		$entry_data['network-active-count']   = count( $entry_data['network-active'] );
		$entry_data['base-site-active-count'] = count( $entry_data['base-site-active'] );
		$entry_data['site-active-count']      = count( $entry_data['site-active'] );
		if ( $entry_data['dropin'] ) {
			$type = 'dropin';
		} elseif ( $entry_data['must-use'] ) {
			$type = 'must-use';
		} else {
			$type = 'regular';
		}
		$entry_data[ 'type' ] = $type;
	}

	return $plugin_data;
}

function generate_theme_data() {
	$theme_data = [];
	$themes = wp_get_themes(
		[
			'errors' => null,
			'allowed' => null,
			'blog_id' => 0,
		]
	);

	foreach ( $themes as $theme_slug => $theme ) {
		// Get the basic data.
		
		$theme_data[ $theme_slug ] = [
			'name'                => $theme->name,
			'url'                 => $theme->get( 'ThemeURI' ),
			'version'             => $theme->version,
			'description'         => $theme->description,
			'author'              => $theme->get( 'Author' ),
			'author-url'          => $theme->get( 'AuthorURI' ),
			'update-version'      => '',
			'update-version-date' => '',
			'update-version-wp'   => '',
			'update-version-php'  => '',
			'wp-url'              => '',
			'network-allowed'     => [],
			'base-site-active'    => [],
			'site-active'         => [],
		];

		// Get data from wp.org API.

		try {
			$remote_data = get_theme_remote_data( $theme_slug );
		}
		catch ( Exception $e ) {
			if ( $e->getCode() !== 404 ) {
				throw $e;
			}
			$remote_data = [];
		}

		if ( $remote_data ) {
			$theme_data[ $theme_slug ]['update-version']      = $remote_data['version'];
			$theme_data[ $theme_slug ]['update-version-date'] = $remote_data['last_updated'];
			$theme_data[ $theme_slug ]['update-version-wp']   = $remote_data['requires'];
			$theme_data[ $theme_slug ]['update-version-php']  = $remote_data['requires_php'];
			if ( strpos( $remote_data['homepage'], 'wordpress.org/themes' ) !== false ) {
				$wordpress_org_url = $remote_data['homepage'];
			} else {
				$wordpress_org_url = "https://wordpress.org/themes/{$theme_slug}/";
			}
			$theme_data[ $theme_slug ]['wp-url']              = $wordpress_org_url;
		}
	}

	// Get data about usage of themes on sites.

	foreach ( get_networks() as $network ) {
		switch_to_network( $network->id, true );

		// Get network allowed themes.
		$allowed_themes = wp_get_themes(
			[
				'allowed' => 'network',
			]
		);
		foreach( array_keys( $allowed_themes ) as $allowed_theme_slug ) {
			$theme_data[ $allowed_theme_slug ]['network-allowed'][] = get_network_slug( $network->domain );
		}

		// Get base site active theme.
		$base_site_id = get_main_site_id( $network->id );
		switch_to_blog( $base_site_id );
		$base_site_theme = wp_get_theme();
		$theme_data[ $base_site_theme->get_stylesheet() ]['base-site-active'][] = get_network_slug( $network->domain );
		restore_current_blog();

		// Get site active themes.
		$sites = get_sites(
			[
				'network_id' => $network->id,
			]
		);
		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );
			if ( $site->blog_id === $base_site_id ) {
				continue;
			}
			$site_theme = wp_get_theme();
			$theme_data[ $site_theme->get_stylesheet() ]['site-active'][] = $site->domain;
			restore_current_blog();
		}
		restore_current_network();
	}

	return $theme_data;
}

/**
 * Connects to WordPress.org plugin API to retrieve plugin data.
 *
 * @param string $plugin_slug Slug for the plugin. Eg. 'ninja-forms' 
 */
function get_plugin_remote_data( $plugin_slug ) {
	print( "Querying WordPress plugin API for $plugin_slug.\n" );
	$info_url = "https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]=$plugin_slug";
	$response = wp_remote_get( $info_url );
	if ( is_wp_error( $response ) ) {
		throw new Exception( "Error retrieving data from WordPress plugin api for $plugin_slug" );
	}
	$response_code = wp_remote_retrieve_response_code( $response );
	if ( $response_code !== 200 ) {
		throw new Exception( "No response from WordPress plugin api for $plugin_slug", $response_code );
	}
	$plugin_data = json_decode( wp_remote_retrieve_body( $response ), true );
	return $plugin_data;
}

/**
 * Connects to WordPress.org theme API to retrieve theme data.
 * 
 * @param string $theme_slug Slug for the theme. Eg. 'twentyseventeen'
 */
function get_theme_remote_data( $theme_slug ) {
	print( "Querying WordPress theme API for $theme_slug.\n" );
	$info_url = "https://api.wordpress.org/themes/info/1.2/?action=theme_information&request[slug]=$theme_slug";
	$response = wp_remote_get( $info_url );
	if ( is_wp_error( $response ) ) {
		throw new Exception( "Error retrieving data from WordPress theme api for $theme_slug" );
	}
	$response_code = wp_remote_retrieve_response_code( $response );
	if ( $response_code !== 200 ) {
		throw new Exception( "No response from WordPress theme api for $theme_slug", $response_code );
	}
	$theme_data = json_decode( wp_remote_retrieve_body( $response ), true );
	return $theme_data;
}

/**
 * Gets slug for a plugin.
 *
 * @param string $plugin_path Path of the plugin, either relative to plugins directory or absolute.
 */
function get_plugin_slug( $plugin_path ) {
	$path_parts = explode( '/', $plugin_path );
	if ( count( $path_parts ) == 1 ) {
		$plugin_filename = explode( '.', $path_parts[0] );
		$plugin_slug = $plugin_filename[0];
	} else {
		$plugin_slug = $path_parts[ count( $path_parts ) - 2 ];
	}
	return $plugin_slug;
}

/**
 * Gets slug for a network.
 *
 * @param string $domain Domain of the network.
 */
function get_network_slug( $domain ) {
	$domain_parts = explode( '.', $domain );
	$network_slug = $domain_parts[0];
	if ( strpos( $domain_parts[0], 'hcommons' ) === 0 ) {
		$network_slug = 'hc';
	}
	return $network_slug;
}

/**
 * Outputs plugin or theme data to CSV.
 *
 * @param Array $data Associative array of data to be written, where keys are column names.
 * @param string $output_filename Filename to write CSV output. Will be overwritten if exists.
 */
function write_to_csv( $data, $output_filename ) {
	$sheet = fopen( $output_filename, 'w' );
	$columns = array_merge( ['slug'], array_keys( array_values( $data )[0] ) );
	fputcsv( $sheet, $columns );
	foreach ( $data as $slug => &$row ) {
		foreach ( $row as $col_key => &$col_val ) {
			$col_val = value_to_string( $col_val );
		}
		fputcsv( $sheet, array_merge( [ $slug ], $row ) );
	}
	fclose( $sheet );
}

/**
 * Echoes plugin or theme data to stdout in Composer require format.
 *
 * @param Array $data Associative array of data to be written, where keys are column names.
 */
function echo_composer_requirements( $data, $type = 'plugin' ) {
	foreach ( $data as $slug => &$row ) {
		// Skip plugins that don't have a WP.org URL.
		if ( ! $row['wp-url'] ) {
			continue;
		}
		$requirement = "\"wpackagist-$type/$slug\"";
		if ( $row['version'] ) {
			$requirement .= ": \"^{$row['version']}\"";
		} else {
			$requirement .= ": \"*\"";
		}
		if ( $slug !== array_key_last( $data) ) {
			$requirement .= ',';
		}
		echo "$requirement\n";
	}
}

/**
 * Ensures that values are strings for writing to CSV or MD.
 *
 * @param mixed $value A value that might be an array.
 */
function value_to_string( $value, $separator = ' ; ' ) {
	if ( ! is_array( $value ) ) {
		return $value;
	}
	$converted = array_map( 'convert_domain_to_url', $value );
	$converted = join( $separator, $converted );
	return $converted; 
}

/**
 * Outputs plugin or theme data to markdown.
 *
 * @param Array $data Associative array of data to be written, where keys are column names.
 * @param string $output_filename Filename to write CSV output. Will be overwritten if exists.
 */
function write_to_md( $data, $mode, $output_filename, $input_filename = '' ) {
	$plugin_label_map = [
		'slug'                   => 'Plugin slug',
		'url'                    => 'URL',
		'description'            => 'Description',
		'type'                   => 'Plugin type', // 'regular', 'must-use', or 'dropin'
		'wp-url'                 => 'WordPress.org URL',
		'version'                => 'Installed version',
		'update-version'         => 'Most recent version',
		'update-version-date'    => 'Most recent update',
		'update-version-wp'      => 'Updated tested with WP',
		'network-active-count'   => 'Network Active Count',
		'network-active'         => 'Network Active',
		'site-active-count'      => 'Site Active Count',
		'site-active'            => 'Sites',
		'base-site-active-count' => 'Base Site Active Count',
		'base-site-active'       => 'Base Sites',
		'function'               => 'Function on the Commons',
		'removal-status'         => 'Removal Status (Remove / Test / Investigate / Keep)',
		'notes'                  => 'Notes',
		'last-reviewed'          => 'Last Reviewed (Date / Initials)',
	];

	$theme_label_map = [];
	
	$markdown = '';

	if ( $mode == 'plugin' ) {
		if ( $input_filename ) {
			$data = merge_manual_markdown_fields( $input_filename, $data, $plugin_label_map );
		}
		$markdown .= plugin_summary_data( $data );
	}
	
	foreach( $data as $slug => $single_entry ) {
		if ( $slug == 'general-notes' ) {
			continue;
		}
		if ( $mode == 'plugin' ) {
			$markdown .= plugin_entry_to_md( array_merge( $single_entry, [ 'slug' => $slug ] ), $plugin_label_map );
		} else {
			$markdown .= theme_entry_to_md( array_merge( [ 'slug' => $slug ], $single_entry ), $theme_label_map );
		}
	}

	$markdown_file = fopen( $output_filename, 'w' );
	if ( $markdown_file ) {
		fwrite( $markdown_file, $markdown );
		fclose( $markdown_file );
	} else {
		throw new Exception( "Failed to open $output_filename for write. Does user have permissions?" );
	}
}

/**
 * Generates summary data about installed plugins.
 * 
 * - network-active    List of network active plugins
 * - base-site-active  List of base site active plugins
 * - site-active       List of site active plugins
 * - mu                List of mu-plugins
 * - dropin            List of dropin plugins
 * - nowhere           List of plugins that are active nowhere
 * - few               List of plugins that are active on fewer than 5 sites
 * - updatable         List of plugins that are updatable
 * - non-wordpress     List of plugins without a wordpress.org page
 */
function plugin_summary_data( $data ) {
	$summary_data['network-active']   = [];
	$summary_data['base-site-active'] = [];
	$summary_data['site-active']      = [];
	$summary_data['mu']               = [];
	$summary_data['dropin']           = [];
	$summary_data['nowhere']          = [];
	$summary_data['few']              = [];
	$summary_data['updatable']        = [];
	$summary_data['non-wordpress']    = [];

	foreach ( $data as $slug => $plugin_data ) {
		$somewhere = false;
		if ( ! is_array( $plugin_data ) ) {
			continue;
		}
		if ( count( $plugin_data['network-active'] ) > 0 ) {
			$somewhere = true;
			$summary_data['network-active'][] = markdown_item( $plugin_data );
		}
		if ( count( $plugin_data['base-site-active'] ) > 0 ) {
			$somewhere = true;
			$summary_data['base-site-active'][] = markdown_item( $plugin_data );
		}
		if ( count( $plugin_data['site-active'] ) > 0 ) {
			$somewhere = true;
			$summary_data['site-active'][] = markdown_item( $plugin_data );
		}
		if ( $plugin_data['must-use'] ) {
			$somewhere = true;
			$summary_data['mu'][] = markdown_item( $plugin_data );
		}
		if ( $plugin_data['dropin'] ) {
			$somewhere = true;
			$summary_data['dropin'][] = markdown_item( $plugin_data );
		}
		if ( ! $somewhere ) {
			$summary_data['nowhere'][] = markdown_item( $plugin_data );
		}
		if ( 
			count( $plugin_data['site-active'] ) > 0 && 
			count( $plugin_data['site-active'] ) < 5 &&
			count( $plugin_data['network-active'] ) === 0 &&
			count( $plugin_data['base-site-active'] ) === 0 &&
			! $plugin_data['must-use'] &&
			! $plugin_data['dropin']
		) {
			$summary_data['few'][] = markdown_item( $plugin_data );
		}
		if ( 
			$plugin_data['update-version'] &&
			$plugin_data['version'] !== $plugin_data['update-version']
		) {
			$summary_data['updatable'][] = markdown_item( $plugin_data );
		}
		if ( ! $plugin_data['wp-url'] ) {
			$summary_data['non-wordpress'][] = markdown_item( $plugin_data );
		}
	}

	$summary_data['count-network-active']   = count( $summary_data['network-active'] );
	$summary_data['count-base-site-active'] = count( $summary_data['base-site-active'] );
	$summary_data['count-site-active']      = count( $summary_data['site-active'] );
	$summary_data['count-mu']               = count( $summary_data['mu'] );
	$summary_data['count-dropin']           = count( $summary_data['dropin'] );
	$summary_data['count-nowhere']          = count( $summary_data['nowhere'] );
	$summary_data['count-few']              = count( $summary_data['few'] );
	$summary_data['count-updatable']        = count( $summary_data['updatable'] );
	$summary_data['count-non-wordpress']    = count( $summary_data['non-wordpress'] );

	foreach ( $summary_data as $field => $item_data ) {
		if ( is_array( $item_data ) ) {
			$summary_text[ $field ] = join( "\n", $item_data );
		}
	}

	$total_plugins = count( $data );

	$general_notes = trim( $data['general-notes'] );

	$markdown = <<<"EOD"
	## Notes
	
	$general_notes

	## Summary Data

	Total plugins: $total_plugins

	### Network active plugins ( {$summary_data['count-network-active']} )

	{$summary_text['network-active']}

	### Base site active plugins ( {$summary_data['count-base-site-active']} )

	{$summary_text['base-site-active']}

	### Site active plugins ( {$summary_data['count-site-active']} )

	{$summary_text['site-active']}

	### Must-Use plugins ( {$summary_data['count-mu']} )

	{$summary_text['mu']}

	### Dropin plugins ( {$summary_data['count-dropin']} )

	{$summary_text['dropin']}

	### Unused plugins ( {$summary_data['count-nowhere']} )

	{$summary_text['nowhere']}

	### Plugins with fewer than 5 activations ( {$summary_data['count-few']} )

	{$summary_text['few']}

	### Plugins with updates available from wordpress.org ( {$summary_data['count-updatable']} )

	{$summary_text['updatable']}

	### Plugins not on wordpress.org ( {$summary_data['count-non-wordpress']} )

	{$summary_text['non-wordpress']}


	EOD;

	return $markdown;
}

/**
 * Generates MarkDown-formatted list item for summary data.
 *
 * @param Array $entry Data for a single plugin.
 */
function markdown_item( $entry ) {
	$link = $entry['name'];
	$link = preg_replace("/[^A-Za-z0-9\- ]/", '', $link );
	$link = strtolower( $link );
	$link = preg_replace( '/ +/', '-', $link );
	return "- [{$entry['name']}](#$link)";
}

/**
 * Generates markdown-formatted text for single plugin.
 */
function plugin_entry_to_md( $data, $fields_label_map ) {
	$markdown = '';
	
	$markdown .= "## {$data['name']}\n\n";

	foreach ( $fields_label_map as $field => $label ) {
		$markdown .= "**$label**: ";
		if ( array_key_exists( $field, $data ) ) {
			$flattened_data = value_to_string( $data[ $field ], '; ' );
			$flattened_data = htmlentities( $flattened_data );
			$markdown .= $flattened_data;
		}
		$markdown .= "\n\n";
	}

	$markdown .= "\n";
	return $markdown;
}

/**
 * Merges existing MarkDown document with newly-generated data, preserving
 * manual-entry fields.
 * 
 * @param string $input_filename  Filename of existing MarkDown document
 * @param Array $data             Newly-generated plugin data
 * @param Array $fields_label_map Map from field headings to field slugs
 */
function merge_manual_markdown_fields( $input_filename, $data, $fields_label_map ) {
	$manual_fields = [ 'function', 'notes', 'removal-status', 'last-reviewed' ];
	$parsed_data = parse_markdown_plugin_inventory( $input_filename, $fields_label_map );
	foreach ( $parsed_data as $slug => $entry_data ) {
		if ( $slug === 'general-notes' ) {
			continue;
		}
		foreach ( $manual_fields as $field_slug ) {
			if ( array_key_exists( $field_slug, $entry_data ) ) {
				$data[ $slug ][ $field_slug ] = $entry_data[ $field_slug ];
			}
		}
	}
	if ( array_key_exists( 'general-notes', $parsed_data ) ) {
		$data['general-notes'] = $parsed_data['general-notes'];
	}
	return $data;
}

/**
 * Parses existing MarkDown document into Array.
 *
 * @param string $input_filename Existing MarkDown-formatted document.
 * @param Array $fields_label_map Map from fields to headings.
 */
function parse_markdown_plugin_inventory( $input_filename, $fields_label_map ) {
	$parsed_data = [];
	$parsed_data['general-notes'] = '';
	$input_text = file_get_contents( $input_filename );
	$label_fields_map = array_flip( $fields_label_map );

	$lines = explode( "\n", $input_text );
	$section = '';
	$field = '';
	$entry_data = [];
	$is_plugin_entry = false;
	foreach ( $lines as $line ) {
		if ( strpos( $line, '## ' ) === 0 ) {
			if ( $is_plugin_entry ) {
				$parsed_data[ $entry_data['slug'] ] = $entry_data;
				$entry_data = [];
				$is_plugin_entry = false;
			} 
			$section = trim( $line );
			continue;
		}
		if ( $section === '## Notes' ) {
			$parsed_data['general-notes'] .= "$line\n";
			continue;
		}
		if ( $section === '## Summary Data' ) {
			continue;
		}
		// Should be parsing a plugin entry if we get to this point
		$new_field = preg_match( '/\*\*(.+?)\*\*\: (.*)/', $line, $matches );
		if ( $new_field === 1 ) {
			$field = $label_fields_map[ $matches[1] ];
			$data = trim( $matches[2] );
			if ( strpos( $data, ';' ) !== false ) {
				$data = explode( '; ', $data );
			}
			if ( $field === 'slug' ) {
				$is_plugin_entry = true;
			}
			$entry_data[ $field ] = $data;
		} elseif ( $is_plugin_entry && $field !== '' ) {
			$data = trim( $line );
			if ( $data ) {
				$entry_data[ $field ] .= "\n$data";
			}
		}
	}
	// For the final plugin
	if ( array_key_exists( 'slug', $entry_data ) && $entry_data['slug'] ) {
		$parsed_data[ $entry_data['slug'] ] = $entry_data;
	}

	return $parsed_data;
}

/**
 * Generates MarkDown for theme entry.
 */
function theme_entry_to_md( $data ) {
	return '';
}

/**
 * If the domain is formatted as a domain (eg. supoort.hcommons.org), then
 * return as a URL. Otherwise return as-is.
 */
function convert_domain_to_url( $domain ) {
	if ( strpos( $domain, '.' ) !== false ) {
		return "https://$domain/";
	}
	return $domain;
}

/**
 * Give user appropriate feedback on fatal error before exiting.
 */
function handle_fatal_error( $e ) {
	if ( $e->getCode() === COMMAND_LINE_SYNTAX_ERROR ) {
		show_help();
	}
	print( "Error: {$e->getMessage()} ({$e->getCode()}).\n\n" );
	exit();
}

/**
 * Print help text.
 */
function show_help() {
	$help = <<<EOD
	Usage: wp eval-file plugin-theme-inventory.php mode={plugin|theme} out={filename} in={filename} [format={csv|md}]
		- mode    whether to generate inventory for plugins or themes
		- out     output filename
		- in      existing markdown inventory to be merged with new data
		- format  whether to generate CSV of data (default), mardown inventory template, or Composer requirements
	EOD;
	print( $help );
}

/**
 * Parse arguments into associative array.
 *
 * Arguments should be passed to script as key=value (no spaces).
 *
 * @param Array $args Array of arguments as passed to script by WP CLI eval-file.
 */
function parse_args( $args ) {
	$parsed_args = [];
	foreach ( $args as $arg ) {
		$split_arg = explode( '=', $arg );
		if ( count( $split_arg ) !== 2 ) {
			throw new Exception( "Misformatted argument: '$arg'" );
		} 
		$parsed_args[ $split_arg[0] ] = $split_arg[1];
	}
	return $parsed_args;
}

try {
	main( $args );
}
catch ( Exception $e ) {
	handle_fatal_error( $e );
}
