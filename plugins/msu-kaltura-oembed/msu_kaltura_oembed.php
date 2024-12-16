<?php
/*
	Plugin Name: MSU Kaltura oEmbed
	Plugin URI:
	Description: Enable MSU Kaltura Mediaspace oEmbed within your site.
	Version: 1.0
	Author: HC
	License: GPL2
*/

function msu_kaltura_add_oembed_handlers() {
    wp_oembed_add_provider( 'https://mediaspace.msu.edu/id/*', 'https://mediaspace.msu.edu/oembed/', false );
}
add_action( 'init', 'msu_kaltura_add_oembed_handlers' );
