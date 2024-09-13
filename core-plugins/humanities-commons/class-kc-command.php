<?php

class KC_Command {

    /**
     * Compare actual plugin and theme states with YAML configuration.
     *
     * ## OPTIONS
     *
     * [<network>]
     * : The network domain to check. If not provided, checks all networks.
     *
     * ## EXAMPLES
     *
     *     wp kc compare
     *     wp kc compare mla.hcommons.org
     *
     * @when after_wp_load
     */
    public function compare($args, $assoc_args) {
        $network = isset($args[0]) ? $args[0] : null;

        if ($network) {
            $this->compare_network($network);
        } else {
            $this->compare_all_networks();
        }
    }

	public function status($args, $assoc_args) {
		$network_domain = isset($args[0]) ? $args[0] : '';
		$network = $this->get_network_by_domain($network_domain);
		$status = $this->get_status($network);
		$this->print_sections($status);
	}

	public function requirements($args, $assoc_args) {
		$network_domain = isset($args[0]) ? $args[0] : '';
		$requirements = $this->calculate_network_requirements($network_domain);
		$this->print_sections($requirements);
	}

	public function sync($args, $assoc_args) {
		$network_domain = isset($args[0]) ? $args[0] : '';
		$network = $this->get_network_by_domain($network_domain);
		$status = $this->get_status($network);
		$config = $this->calculate_network_requirements($network_domain);
		$differences = $this->compare_all_statuses_to_requirements($status, $config);
		foreach ($differences as $section => $section_differences) {
			if ( empty($section_differences['missing']) && empty($section_differences['extra']) ) {
				continue;
			}
			match($section) {
				'base-site-plugins' => $this->sync_base_site_plugins($section_differences),
				'network-plugins' => $this->sync_network_plugins($section_differences),
				'allowed-user-plugins' => $this->sync_allowed_user_plugins($section_differences),
				'allowed-user-themes' => $this->sync_allowed_user_themes($section_differences),
				'base-theme' => $this->sync_base_theme($section_differences),
				'user-theme' => $this->sync_user_theme($section_differences),
			};
		}
	}

    private function compare_network(string $network_domain) {
        $network = $this->get_network_by_domain($network_domain);
		$status = $this->get_status($network);
		$config = $this->calculate_network_requirements($network_domain);
		$differences = $this->compare_all_statuses_to_requirements($status, $config);
		foreach ($differences as $section => $section_differences) {
			if ( empty($section_differences['missing']) && empty($section_differences['extra']) ) {
				continue;
			}
			WP_CLI::line("## " . $this->slug_to_name($section) . "\n");
			if ( ! empty($section_differences['missing']) ) {
				WP_CLI::line("### Missing:");
				$this->print_list($section_differences['missing']);
			}
			if ( ! empty($section_differences['extra']) ) {
				WP_CLI::line("### Extra:");
				$this->print_list($section_differences['extra']);
			}
		}
    }

    private function compare_all_networks() {
        $networks = get_networks();
        foreach ($networks as $network) {
            WP_CLI::line("# Network: {$network->domain}");
			WP_CLI::line('');
            $this->compare_network($network->domain);
            WP_CLI::line('');
        }
    }

    private function get_config(): array {
        $config_file = getenv('KC_COMMAND_CONFIG_FILE');
        if (!$config_file) {
            WP_CLI::error('KC_COMMAND_CONFIG_FILE environment variable is not set.');
        }
        if (!file_exists($config_file)) {
            WP_CLI::error("Config file not found at $config_file");
        }
        
		if ( ! function_exists( 'yaml_parse_file' ) ) {
			WP_CLI::error( 'YAML parser not found. Please install the PECL YAML extension.' );
		}

		$config = yaml_parse_file( $config_file );
		if ( ! $config ) {
			WP_CLI::error( 'Failed to parse YAML config file.' );
		}
		return $config;
    }

    private function get_status(\WP_Network $network) : array {
        $base_site = get_sites(['network_id' => $network->id, 'path' => '/'])[0];

		$plugin_conversion_fn = function($plugin) {
			$parts = explode('/', $plugin);
			return $parts[0];
		};
		
		$base_site_plugins = [];
        switch_to_blog($base_site->blog_id);
        $base_site_plugins = get_option('active_plugins', []);
        $base_site_plugins = array_map($plugin_conversion_fn, $base_site_plugins);
        restore_current_blog();

        $network_plugins = get_network_option($network->id, 'active_sitewide_plugins', []);
		$network_plugins = array_keys($network_plugins);
		$network_plugins = array_map($plugin_conversion_fn, $network_plugins);

		$available_themes = wp_get_themes();
		$allowed_themes = get_network_option(get_current_network_id(), 'allowedthemes');
		$network_themes = array_intersect(
			array_keys($available_themes), 
			array_keys($allowed_themes)
		);

		$base_site_theme = get_option('stylesheet');
		$default_theme = get_network_option($network->id, 'default_theme');

        $user_control_plugins = get_network_option($network->id, 'pm_user_control_list', []);
		$user_control_plugins = array_map($plugin_conversion_fn, $user_control_plugins);
		
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

			$result[$section] = $this->compare_status_to_requirements($statuses[$section], $settings);
		}
		return $result;
	}

	private function compare_status_to_requirements(array $status, array $config) : array {
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
		

	private function convert_network_key(string $key) : string {
		$root_domain = getenv('WP_DOMAIN');
		if ( ! $root_domain ) {
			$root_domain = parse_url(get_site_url(), PHP_URL_HOST);
		}
		$network_key = str_replace($root_domain, 'hcommons.org', $key);
		return $network_key;
	}

	private function get_network_by_domain(string $domain) : \WP_Network {
		if ( $domain === '' ) {
			$domain = getenv('WP_DOMAIN');
			if ( ! $domain ) {
				$domain = parse_url(get_site_url(), PHP_URL_HOST);
			}
		}
		$base_site = get_sites( [ 'domain' => $domain ] )[0] ?? null;
		if ( !$base_site ) {
			WP_CLI::error( "Network site not found for domain: $domain" );
		}
		return get_network( $base_site->site_id );
    }

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

	private function print_sections(array $sections) {
		foreach ($sections as $slug => $settings) {
			if (is_array($settings)) {
				WP_CLI::line("## " . $this->slug_to_name($slug));
				$this->print_list($settings);
			}
			else {
				WP_CLI::line("$slug: $settings");
			}
		}
	}

	private function print_list(array $list) {
		foreach ($list as $item) {
			WP_CLI::line("    $item");
		}
		WP_CLI::line('');
	}

	private function confirm_action(string $message, array $assoc_args = []) : bool {
		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'yes' ) ) {
			return true;
		}
		fwrite( STDOUT, "$message [y/n]: " );
		$response = fgets( STDIN );
		if ( strtolower( trim( $response ) ) === 'y' ) {
			return true;
		}
		return false;
	}
}

if ( class_exists('WP_CLI') ) {
	WP_CLI::add_command('kc ptc', 'KC_Command');
}