<?php

namespace KC\PTC;

/**
 * WP-CLI commands for managing themes and plugins on Knowledge Commons sites.
 */
class KC_PTC_Command {

	private $yes = false;

    /**
     * Compare actual plugin and theme states with YAML configuration.
     *
     * ## OPTIONS
     *
     * [--all-networks]
     * : Check all networks instead of just the current one.
     *
     * ## EXAMPLES
     *
     *     wp kc ptc compare
     *     wp kc ptc compare --all-networks
     *
     * @when after_wp_load
     */
    public function compare($args, $assoc_args) {
        $all_networks = \WP_CLI\Utils\get_flag_value($assoc_args, 'all-networks', false);

        if ($all_networks) {
            $this->compare_all_networks();
        } else {
            $domain = $this->get_current_domain();
            $this->compare_network($domain);
        }
    }

	/**
	 * Get the status of plugins and themes on a network or all networks.
	 *
	 * ## OPTIONS
	 *
	 * [--all-networks]
	 * : Check all networks instead of just the current one.
	 *
	 * ## EXAMPLES
	 *
	 *     wp kc ptc status --url=mla.hcommons.org
	 *     wp kc ptc status --url=https://mla.hcommons.org
	 *     wp kc ptc status
	 *     wp kc ptc status --all-networks
	 */
	public function status($args, $assoc_args) {
		$all_networks = \WP_CLI\Utils\get_flag_value($assoc_args, 'all-networks', false);

		if ($all_networks) {
			$networks = get_networks();
			foreach ($networks as $network) {
				$status = $this->get_status($network);
				\WP_CLI::line("# Network: {$network->domain}");
				$this->print_sections($status);
				\WP_CLI::line('');
			}
		} else {
			$domain = $this->get_current_domain();
			$network = $this->get_network_by_domain($domain);
			$status = $this->get_status($network);
			$this->print_sections($status);
		}
	}

	/**
	 * Get the plugin and theme requirements for a network or all networks.
	 *
	 * ## OPTIONS
	 *
	 * [--all-networks]
	 * : Check all networks instead of just the current one.
	 *
	 * ## EXAMPLES
	 *
	 *     wp kc ptc requirements --url=mla.hcommons.org
	 *     wp kc ptc requirements --url=https://mla.hcommons.org
	 *     wp kc ptc requirements
	 *     wp kc ptc requirements --all-networks
	 */
	public function requirements($args, $assoc_args) {
		$all_networks = \WP_CLI\Utils\get_flag_value($assoc_args, 'all-networks', false);

		if ($all_networks) {
			$networks = get_networks();
			foreach ($networks as $network) {
				$requirements = $this->calculate_network_requirements($network->domain);
				\WP_CLI::line("# Network: {$network->domain}");
				$this->print_sections($requirements);
				\WP_CLI::line('');
			}
		} else {
			$domain = $this->get_current_domain();
			$requirements = $this->calculate_network_requirements($domain);
			$this->print_sections($requirements);
		}
	}

	/**
	 * Set status of plugins and themes on a network to match requirements. 
	 *
	 * ## OPTIONS
	 *
	 * [--all-networks]
	 * : Sync all networks instead of just the current one.
	 * 
	 * [--yes]
	 * : Skip confirmation prompts.
	 *
	 * ## EXAMPLES
	 *
	 *     wp kc ptc sync
	 *     wp kc ptc sync --all-networks
	 *     wp kc ptc sync --yes
	 */
	public function sync($args, $assoc_args) {
		$this->yes = \WP_CLI\Utils\get_flag_value($assoc_args, 'yes');
		$all_networks = \WP_CLI\Utils\get_flag_value($assoc_args, 'all-networks', false);

		if ($all_networks) {
			$networks = get_networks();
			$network_domains = array_map(function($network) {
				return $network->domain;
			}, $networks);
		} else {
			$network_domains = [$this->get_current_domain()];
		}

		foreach ($network_domains as $network_domain) {
			\WP_CLI::line("# Network: {$network_domain}");
			$this->sync_network($network_domain);
			\WP_CLI::line('');
		}
	}

	/**
	 * Get the domain of the current site.
	 *
	 * @return string The domain of the current site.
	 */
	private function get_current_domain(): string {
		return parse_url(get_site_url(), PHP_URL_HOST);
	}

	/**
	 * Sync the plugins and themes on a network to the requirements.
	 *
	 * @param string $network_domain The domain of the network to sync.
	 */
	private function sync_network($network_domain) {
		$network = $this->get_network_by_domain($network_domain);
		$status = $this->get_status($network);
		$config = $this->calculate_network_requirements($network_domain);
		$differences = $this->compare_all_statuses_to_requirements($status, $config);
		foreach ($differences as $section => $section_differences) {
			if ( empty($section_differences) ) {
				continue;
			}
			if ( is_array($section_differences) && empty($section_differences['missing']) && empty($section_differences['extra']) ) {
				continue;
			}
			match($section) {
				'base-site-plugins' => $this->sync_base_site_plugins($section_differences, $network_domain),
				'network-plugins' => $this->sync_network_plugins($section_differences, $network_domain),
				'allowed-user-plugins' => $this->sync_allowed_user_plugins($section_differences, $network_domain),
				'allowed-user-themes' => $this->sync_allowed_user_themes($section_differences, $network_domain),
				'base-theme' => $this->sync_base_theme($section_differences, $network_domain),
				'user-theme' => $this->sync_user_theme($section_differences, $network_domain),
			};
		}
	}
	
	/**
	 * Sync the base site plugins on a network to the requirements.
	 *
	 * @param array $section_differences The differences between the current state and the requirements.
	 * @param string $network_domain The domain of the network to sync.
	 */
	private function sync_base_site_plugins(array $section_differences, string $network_domain) {
		foreach ($section_differences['missing'] as $plugin) {
			$confirm = $this->confirm_action("Activate $plugin on base site?");
			if ( !$confirm ) {
				continue;
			}
			\WP_CLI::runcommand("plugin activate $plugin --url=$network_domain");
		}
		foreach ($section_differences['extra'] as $plugin) {
			$confirm = $this->confirm_action("Deactivate $plugin on base site?");
			if ( !$confirm ) {
				continue;
			}
			\WP_CLI::runcommand("plugin deactivate $plugin --url=$network_domain");
		}
	}

	/**
	 * Sync the network active plugins on a network to the requirements.
	 *
	 * @param array $section_differences The differences between the current state and the requirements.
	 * @param string $network_domain The domain of the network to sync.
	 */
	private function sync_network_plugins(array $section_differences, string $network_domain) {
		foreach ($section_differences['missing'] as $plugin) {
			$confirm = $this->confirm_action("Activate $plugin on network?");
			if ( !$confirm ) {
				continue;
			}
			\WP_CLI::runcommand("plugin activate $plugin --url=$network_domain --network");
		}
		foreach ($section_differences['extra'] as $plugin) {
			$confirm = $this->confirm_action("Deactivate $plugin on network?");
			if ( !$confirm ) {
				continue;
			}
			\WP_CLI::runcommand("plugin deactivate $plugin --url=$network_domain --network");
		}
	}

	/**
	 * Sync the allowed user plugins on a network to the requirements.
	 *
	 * @param array $section_differences The differences between the current state and the requirements.
	 * @param string $network_domain The domain of the network to sync.
	 */
	private function sync_allowed_user_plugins(array $section_differences, string $network_domain) {
		foreach ($section_differences['missing'] as $plugin) {
			$confirm = $this->confirm_action("Allow user sites on $network_domain to activate $plugin?");
			if ( !$confirm ) {
				continue;
			}
			$network = $this->get_network_by_domain($network_domain);
			$user_control_plugins = get_network_option($network->id, 'pm_user_control_list', []);
			$user_control_plugins = $this->simplify_plugin_names($user_control_plugins);
			$user_control_plugins[] = $plugin;
			update_network_option($network->id, 'pm_user_control_list', $user_control_plugins);
			\WP_CLI::success("Allowed user sites on $network_domain to activate $plugin");
		}
		foreach ($section_differences['extra'] as $plugin) {
			$confirm = $this->confirm_action("Disallow user sites on $network_domain to activate $plugin?");
			if ( !$confirm ) {
				continue;
			}
			$network = $this->get_network_by_domain($network_domain);
			$user_control_plugins = get_network_option($network->id, 'pm_user_control_list', []);
			$user_control_plugins = $this->simplify_plugin_names($user_control_plugins);
			$user_control_plugins = array_diff($user_control_plugins, [$plugin]);
			update_network_option($network->id, 'pm_user_control_list', $user_control_plugins);
			\WP_CLI::success("Disallowed user sites on $network_domain from activating $plugin");
		}
	}

	/**
	 * Sync the allowed user themes on a network to the requirements.
	 *
	 * @param array $section_differences The differences between the current state and the requirements.
	 * @param string $network_domain The domain of the network to sync.
	 */
	private function sync_allowed_user_themes(array $section_differences, string $network_domain) {
		foreach ($section_differences['missing'] as $theme) {
			$confirm = $this->confirm_action("Allow user sites on $network_domain to use theme $theme?");
			if ( !$confirm ) {
				continue;
			}
			$network = $this->get_network_by_domain($network_domain);
			$allowed_themes = get_network_option($network->id, 'allowedthemes', []);
			$allowed_themes[$theme] = true;
			update_network_option($network->id, 'allowedthemes', $allowed_themes);
			\WP_CLI::success("Allowed user sites on $network_domain to use theme $theme");
		}
		foreach ($section_differences['extra'] as $theme) {
			$confirm = $this->confirm_action("Disallow user sites on $network_domain to use theme $theme?");
			if ( !$confirm ) {
				continue;
			}
			$network = $this->get_network_by_domain($network_domain);
			$allowed_themes = get_network_option($network->id, 'allowedthemes', []);
			unset($allowed_themes[$theme]);
			update_network_option($network->id, 'allowedthemes', $allowed_themes);
			\WP_CLI::success("Disallowed user sites on $network_domain from using theme $theme");
		}
	}

	/**
	 * Sync the base theme on a network to the requirements.
	 *
	 * @param string $section_differences The differences between the current state and the requirements.
	 * @param string $network_domain The domain of the network to sync.
	 */
	private function sync_base_theme(string $section_differences, string $network_domain) {
		[ $old_theme, $new_theme ] = explode(' != ', $section_differences);
		$confirm = $this->confirm_action("Switch base site from $old_theme to $new_theme?");
		if ( !$confirm ) {
			return;
		}
		\WP_CLI::runcommand("theme activate $new_theme --url=$network_domain");
	}

	/**
	 * Sync the default user theme on a network to the requirements.
	 *
	 * @param string $section_differences The differences between the current state and the requirements.
	 * @param string $network_domain The domain of the network to sync.
	 */
	private function sync_user_theme(string $section_differences, string $network_domain) {
		[ $old_theme, $new_theme ] = explode(' != ', $section_differences);
		$confirm = $this->confirm_action("Switch default user theme from $old_theme to $new_theme?");
		if ( !$confirm ) {
			return;
		}
		$network = $this->get_network_by_domain($network_domain);
		update_network_option($network->id, 'default_theme', $new_theme);
		\WP_CLI::success("Switched default user theme on $network_domain from $old_theme to $new_theme");
	}

	/**
	 * Compare the status of plugins and themes on all networks to the requirements.
	 */
	private function compare_all_networks() {
		$networks = get_networks();
		foreach ($networks as $network) {
			\WP_CLI::line("# Network: {$network->domain}");
			\WP_CLI::line('');
			$this->compare_network($network->domain);
			\WP_CLI::line('');
		}
	}

	/**
	 * Compare the status of plugins and themes on a network to the requirements.
	 *
	 * @param string $network_domain The network domain to compare.
	 */
    private function compare_network(string $network_domain) {
        $network = $this->get_network_by_domain($network_domain);
		$status = $this->get_status($network);
		$config = $this->calculate_network_requirements($network_domain);
		$differences = $this->compare_all_statuses_to_requirements($status, $config);
		foreach ($differences as $section => $section_differences) {
			if ( empty($section_differences['missing']) && empty($section_differences['extra']) ) {
				continue;
			}
			\WP_CLI::line("## " . $this->slug_to_name($section) . "\n");
			if ( ! empty($section_differences['missing']) ) {
				\WP_CLI::line("### Missing:");
				$this->print_list($section_differences['missing']);
			}
			if ( ! empty($section_differences['extra']) ) {
				\WP_CLI::line("### Extra:");
				$this->print_list($section_differences['extra']);
			}
		}
    }
    
	/**
	 * Get the plugin and theme requirements from the configuration file.
	 *
	 * @return array The configuration.
	 */
	private function get_config(): array {
        $config_file = getenv('KC_COMMAND_CONFIG_FILE');
        if (!$config_file) {
            \WP_CLI::error('KC_COMMAND_CONFIG_FILE environment variable is not set.');
        }
        if (!file_exists($config_file)) {
            \WP_CLI::error("Config file not found at $config_file");
        }
        
		if ( ! function_exists( 'yaml_parse_file' ) ) {
			\WP_CLI::error( 'YAML parser not found. Please install the PECL YAML extension.' );
		}

		$config = yaml_parse_file( $config_file );
		if ( ! $config ) {
			\WP_CLI::error( 'Failed to parse YAML config file.' );
		}
		return $config;
    }

	/**
	 * Get the status of plugins and themes on a network.
	 *
	 * @param \WP_Network $network The network to get the status for.
	 * @return array The status of plugins and themes.
	 */
    private function get_status(\WP_Network $network) : array {
        $base_site = get_sites(['network_id' => $network->id, 'path' => '/'])[0];

		$base_site_plugins = [];
        switch_to_blog($base_site->blog_id);
        $base_site_plugins = get_option('active_plugins', []);
        $base_site_plugins = $this->simplify_plugin_names($base_site_plugins);
		$base_site_plugins = array_intersect($base_site_plugins, $this->get_installed_plugins());
        restore_current_blog();

        $network_plugins = get_network_option($network->id, 'active_sitewide_plugins', []);
		$network_plugins = array_keys($network_plugins);
		$network_plugins = $this->simplify_plugin_names($network_plugins);
		$network_plugins = array_intersect($network_plugins, $this->get_installed_plugins());

		$available_themes = wp_get_themes();
		$allowed_themes = get_network_option(get_current_network_id(), 'allowedthemes');
		$network_themes = array_intersect(
			array_keys($available_themes), 
			array_keys($allowed_themes)
		);

		$base_site_theme = get_option('stylesheet');
		$default_theme = get_network_option($network->id, 'default_theme');

        $user_control_plugins = get_network_option($network->id, 'pm_user_control_list', []);
		$user_control_plugins = $this->simplify_plugin_names($user_control_plugins);
		$user_control_plugins = array_intersect($user_control_plugins, $this->get_installed_plugins());
		
		$status = [
			'base-site-plugins' => $base_site_plugins,
			'network-plugins' => $network_plugins,
			'allowed-user-plugins' => $user_control_plugins,
			'allowed-user-themes' => $network_themes,
			'base-theme' => $base_site_theme,
			'user-theme' => $default_theme
		];

		return $status;
    }

	/**
	 * Simplify the plugin names to match format in configuration file.
	 *
	 * @param array $plugins The plugin names.
	 * @return array The simplified plugin names.
	 */
	private function simplify_plugin_names(array $plugins) : array {
		return array_map(function($plugin) {
			$parts = explode('/', $plugin);
			return $parts[0];
		}, $plugins);
	}
	
	/**
	 * Calculate the plugin and theme requirements for a network.
	 *
	 * @param string $network_domain The network domain to calculate requirements for.
	 * @return array The requirements.
	 */
	private function calculate_network_requirements(string $network_domain) : array {
		$config = $this->get_config();
		$result_requirements = $config['default'];
		$network_key = $this->convert_network_key($network_domain);
		$network_requirements = $config[$network_key] ?? [];
		if ( ! is_array($network_requirements) ) {
			return $result_requirements;
		}
		foreach ($network_requirements as $key => $value) {
			if (is_array($value)) {
				foreach ($value as $setting) {
					if ( str_starts_with($setting, '-') ) {
						$setting = substr($setting, 1);
						$del_key = array_search($setting, $result_requirements[$key]);
						if ( $del_key !== false ) {
							unset($result_requirements[$key][$del_key]);
						}
					}
					else if ( ! in_array($setting, $result_requirements[$key]) ) {
						$result_requirements[$key][] = $setting;
					}
				}
			} else if ( $value ) {
				$result_requirements[$key] = $value;
			}
		}
		return $result_requirements;
	}

	/**
	 * Compare the status of plugins and themes on a network to the requirements.
	 *
	 * @param array $statuses The status of plugins and themes.
	 * @param array $config The requirements.
	 * @return array The differences.
	 */
	private function compare_all_statuses_to_requirements(array $statuses, array $config) : array {
		$result = [];
		foreach ( $config as $section => $settings ) {
			if ( ! isset($statuses[$section]) ) {
				continue;
			}
			if ( ! is_array($statuses[$section]) && ! is_array( $settings ) ) {
				if ( $statuses[$section] !== $settings ) {
					$this_status = empty($statuses[$section]) ? 'default' : $statuses[$section];
					$result[$section] = "$this_status != $settings";
				}
				continue;
			}

			$result[$section] = $this->compare_status_section_to_requirements($statuses[$section], $settings);
		}
		return $result;
	}

	/**
	 * Compare the status of plugins and themes on a network to the requirements.
	 *
	 * @param array $status The status of plugins and themes.
	 * @param array $config The requirements.
	 * @return array The differences.
	 */
	private function compare_status_section_to_requirements(array $status, array $config) : array {
		$result = [
			'missing' => [],
			'extra' => []
		];

		foreach ($config as $value) {
			if ( ! in_array($value, $status) ) {
				$result['missing'][] = $value;
			}
		}

		foreach ($status as $value) {
			if ( ! in_array($value, $config) ) {
				$result['extra'][] = $value;
			}
		}

		return $result;
	}
		
	/**
	 * Convert a network key to the corresponding network key for the Humanities Commons.
	 * 
	 * @param string $key The network key as used in the configuration file.
	 * @return string The Humanities Commons network key.
	 */
	private function convert_network_key(string $key) : string {
		$root_domain = getenv('WP_DOMAIN');
		if ( ! $root_domain ) {
			$root_domain = parse_url(get_site_url(), PHP_URL_HOST);
		}
		$network_key = str_replace($root_domain, 'hcommons.org', $key);
		return $network_key;
	}

	/**
	 * Get a network by its domain.
	 *
	 * @param string $domain The domain of the network.
	 * @return \WP_Network The network.
	 */
	private function get_network_by_domain(string $domain) : \WP_Network {
		if ( $domain === '' ) {
			$domain = getenv('WP_DOMAIN');
			if ( ! $domain ) {
				$domain = parse_url(get_site_url(), PHP_URL_HOST);
			}
		}
		$base_site = get_sites( [ 'domain' => $domain ] )[0] ?? null;
		if ( !$base_site ) {
			\WP_CLI::error( "Network site not found for domain: $domain" );
		}
		return get_network( $base_site->site_id );
    }

	/**
	 * Convert a requirement section slug to a name.
	 *
	 * @param string $slug The slug.
	 * @return string The name.
	 */
	private function slug_to_name(string $slug) : string {
		$name = match($slug) {
			'base-site-plugins' => 'Base Site Plugins',
			'network-plugins' => 'Network Plugins',
			'allowed-user-plugins' => 'Allowed User Plugins',
			'allowed-user-themes' => 'Allowed User Themes',
			'base-theme' => 'Base Theme',
			'user-theme' => 'User Theme',
			default => $slug
		};
		return $name;
	}

	/**
	 * Print the sections.
	 *
	 * @param array $sections The sections.
	 */
	private function print_sections(array $sections) {
		foreach ($sections as $slug => $settings) {
			if (is_array($settings)) {
				\WP_CLI::line("## " . $this->slug_to_name($slug) . " (" . count($settings) . ")");
				$this->print_list($settings);
			}
			else {
				\WP_CLI::line("$slug: $settings");
			}
		}
	}

	/**
	 * Print a list.
	 *
	 * @param array $list The list.
	 */
	private function print_list(array $list) {
		foreach ($list as $item) {
			\WP_CLI::line("    $item");
		}
		\WP_CLI::line('');
	}

	/**
	 * Confirm with user that an action should be taken.
	 *
	 * @param string $message Message describing the action.
	 * @param array $assoc_args unused.
	 * @return bool The result.
	 */
	private function confirm_action(string $message, array $assoc_args = []) : bool {
		if ( $this->yes ) {
			return true;
		}
		fwrite( STDOUT, "$message [y/n]: " );
		system('stty -icanon');
        $response = fgetc(STDIN);
        system('stty icanon');
        fwrite( STDOUT, "\n" );
		if ( strtolower( trim( $response ) ) === 'y' ) {
			return true;
		}
		return false;
	}

	/**
	 * Get list of installed plugins.
	 *
	 * @return array The list of installed plugins.
	 */
	private function get_installed_plugins() : array {
		$plugins = get_plugins();
		return $this->simplify_plugin_names(array_keys($plugins));
	}

	/**
	 * Get list of installed themes.
	 *
	 * @return array The list of installed themes.
	 */
	private function get_installed_themes() : array {
		$themes = wp_get_themes();
		return array_keys($themes);
	}
}

if ( class_exists('\WP_CLI') ) {
	\WP_CLI::add_command('kc ptc', 'KC\PTC\KC_PTC_Command');
}