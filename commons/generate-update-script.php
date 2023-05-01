<?php
/**
 * Generate update bash script based on available updates for WordPress core, plugins, and themes.
 * 
 * The resulting file will be a bash script that will perform the updates.
 * 
 * Usage wp eval-file generate-update-script.php > november-2022-updates.sh
 */

// List of plugin slugs to skip
define( 'SKIP_PLUGINS',
	[
		'buddypress-group-email-subscription', // Still don't have multinetwork compatibility fix for bpges
		'wordpress-sparkpost',                 // Forked
		'tainacan_old',
		'wp-graphql',                          // Used on just one MLA site and has breaking changes
    'bp-group-documents',                  // Forked
	]
);

// List of theme slugs to skip
define( 'SKIP_THEMES',
	[

	]
);

define( 'PHP_MAX_VERSION', '7.4' );

function main() {
	$commands = [];
	
	wp_version_check( [], true );
	$core_version_data = get_site_transient( 'update_core' );
	if ( is_array( $core_version_data->updates ) && count( $core_version_data->updates ) > 0 ) {
		$new_version = $core_version_data->updates[0]->version;
		$commands[] = "# WordPress core to $new_version";
		$commands[] = "wp core update --version=$new_version";
	}
	
	wp_update_plugins();
	$update_plugins = get_site_transient( 'update_plugins' );
	$commands[] = "\n\n# Plugin Updates";
	foreach( $update_plugins->response as $plugin_slug => $plugin ) {
		if ( ! $plugin->slug || ! $plugin->new_version ) {
			continue;
		}
		
		if ( in_array( $plugin->slug, SKIP_PLUGINS ) ) {
			$commands[] = "# Skipping {$plugin->slug} - in SKIP_PLUGINS";
			continue;
		}
		
		if ( version_compare( $plugin->requires_php, PHP_MAX_VERSION ) === 1 ) {
			$commands[] = "# Skipping {$plugin->slug} - requires PHP {$plugin->requires_php}";
			continue;
		}

		$commands[] = "wp plugin update {$plugin->slug} --version={$plugin->new_version}";
	}

	wp_update_themes();
	$update_themes = get_site_transient( 'update_themes' );
	$commands[] = "\n# Theme Updates";
	foreach ( $update_themes->response as $theme_slug => $theme ) {
		if ( ! $theme['theme'] || ! $theme['new_version'] ) {
			continue;
		}
		
		if ( in_array( $theme['theme'], SKIP_THEMES ) ) {
			$commands[] = "# Skipping {$theme['theme']} - in SKIP_THEMES";
			continue;
		}
		
		if ( version_compare( $theme['requires_php'], PHP_MAX_VERSION ) === 1 ) {
			$commands[] = "# Skipping {$theme['theme']} - requires PHP {$theme['requires_php']}";
			continue;
		}
		
		$commands[] = "wp theme update {$theme['theme']} --version={$theme['new_version']}";
	}


	$commands[] = "\n# Reminder to update from our repos";
	$commands[] = "~/dev-scripts/commons/repo-status.py";

	foreach ( $commands as $command ) {
		echo "$command\n";
	}
}

main();
