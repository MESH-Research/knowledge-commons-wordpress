<?php
add_action( 'wp_enqueue_scripts', 'enqueue_faculty_child' );
function enqueue_faculty_child() {
	wp_enqueue_style('faculty-css', get_template_directory_uri().'/style.css' );
	wp_enqueue_style('faculty-child-css', get_template_directory_uri() .'/style.css');
	wp_enqueue_script('faculty-child-js', get_template_directory_uri() .'/js/script.js', array( 'jquery' ), '1.0', true );
}


// if some third party has loaded the font-awesome we don't need it
// since v2.3
add_action('wp_enqueue_scripts', 'fac_check_font_awesome_child', 999999);

function fac_check_font_awesome_child() {
  global $wp_styles;
  $srcs = array_map('basename', (array) wp_list_pluck($wp_styles->registered, 'src') );
  if ( in_array('font-awesome.css', $srcs) || in_array('font-awesome.min.css', $srcs)  ) {
    /* echo 'font-awesome.css registered'; */

    wp_dequeue_style('font-awesome');

    wp_enqueue_style('font-awesome-css', get_stylesheet_directory_uri() . '/css/font-awesome.min.css' );

  } else {
    wp_enqueue_style('font-awesome-css', get_stylesheet_directory_uri() . '/css/font-awesome.min.css' );

  }
}