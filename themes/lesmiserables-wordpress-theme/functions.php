<?php

// JavaScript Detection. Adds a "js" class to the root <html> element when JavaScript is detected.
function lm_javascript_detection() {
	echo "<script>(function(html){html.className = html.className.replace(/\bno-js\b/,'js')})(document.documentElement);</script>\n";
}

add_action( 'wp_head', 'lm_javascript_detection', 0 );

// Enqueue scripts and styles.
function lm_scripts() {

	// Load our main stylesheet.
	wp_enqueue_style( 'lm-style', get_stylesheet_uri() );

	// Load vendors
	wp_enqueue_script( 'vendors', get_template_directory_uri() . '/js/vendors.min.js');

    // Load our LM module
    wp_enqueue_script( 'lm', get_template_directory_uri() . '/js/lm.min.js', array(), false, true);
}

add_action( 'wp_enqueue_scripts', 'lm_scripts' );

// Hide admin bar for logged in users
add_filter('show_admin_bar', '__return_false');