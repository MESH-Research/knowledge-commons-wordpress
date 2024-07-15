<?php 

require_once( 'wp-job-manager.php' );

add_action( 'wp_enqueue_scripts', 'aupresses_enqueue_styles', 11 );
function aupresses_enqueue_styles() {
	//remove this var before launch
	$rand = rand( 1, 99999999999 );
	// parent style ( this loads the css from the main folder )
	wp_enqueue_style('parent-style', get_template_directory_uri() .'/style.css');
		
	// child style ( this loads the css from the child folder after parent-style )
	wp_enqueue_style('child-style', get_stylesheet_directory_uri() .'/style.css', array(), $rand );		

	//JS
	wp_enqueue_script( 'scripts', get_stylesheet_directory_uri() . '/js/scripts.js', array('jquery'), $rand, true );
}

//Enabling Elementor on Jobs
add_filter( 'elementor_pro/utils/get_public_post_types', function($post_types) {
	$jobs_cpt = get_post_type_object('job_listing');
	$post_types['job_listing'] = $jobs_cpt->label;
	return $post_types;
} );

//get_template_part( '/inc/elementor_queries'); //Elementor Queries

//Add Display Job Categories Shortcode for a single listing	
function dm_display_wpjm_single_categories () {
	$terms = wp_get_post_terms( get_the_ID(), 'job_listing_category' );
	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
			echo '<div class="job-categories-wrapper"><span>Catgeory:</span><ul>';
			foreach ( $terms as $term ) {
					echo '<li>' . $term->name . '</li>';
			}
			echo '</ul></div>';
	}
}
add_shortcode('list_categories_single', 'dm_display_wpjm_single_categories');
add_theme_support( 'job-manager-templates' );

//Redirect to Job Dashboard after job submission
add_filter( 'job_manager_job_submitted', function() {
	if ( wp_redirect( job_manager_get_permalink( 'job_dashboard' ) ) ) {
		exit;
	}
}, 20 );

//Remove a field from the job submission page
add_filter( 'submit_job_form_fields', 'custom_submit_job_form_fields_dm' );
function custom_submit_job_form_fields_dm( $fields ) {
	unset($fields['company']['company_tagline']);
	unset($fields['company']['company_video']);
	
	$fields['job']['application']['label'] = "URL or email for the 'Apply for job' button";
  $fields['job']['application']['description'] = "Defaults to your email address.";
	
	$fields['job']['job_deadline']['label'] = "Expiration date";

  return $fields;
}