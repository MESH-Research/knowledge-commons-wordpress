<?php

function parse_settings_file( string $settings_filename ) {
	$settings = yaml_parse_file( $settings_filename );
	return $settings;
}

function get_site_activation_data( string $domain ) {
	
}

$settings = parse_settings_file( 'config/all/wordpress/plugin-theme-activation.yaml' );
print_r( $settings );
