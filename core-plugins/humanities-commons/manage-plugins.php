<?php
/**
 * Control which plugins users can activate themselves on KC network sites.
 */

namespace KC\PTC;

/**
 * Filter the list of plugins shown in the admin area.
 *
 * @param array $plugins An array of plugin data.
 * @return array Modified array of plugin data.
 */
function filter_visible_plugins($plugins) {
    $allowed_plugins = get_network_option(get_current_network_id(), 'pm_user_control_list', []);

	$filtered_plugins = [];
	foreach ($plugins as $plugin_path => $plugin) {
		$plugin_slug = explode('/', $plugin_path)[0];
		if ( 
			in_array($plugin_slug, $allowed_plugins) || 
			in_array($plugin_path, $allowed_plugins) ||
			( is_plugin_active($plugin_path) && ! is_plugin_active_for_network($plugin_path) )
		) {
			$filtered_plugins[] = $plugin;
		}
	}

    return $filtered_plugins;
}
add_filter('all_plugins', 'KC\PTC\filter_visible_plugins');
