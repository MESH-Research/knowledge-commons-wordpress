<?php

namespace learningspace\inc\classes;
class menus extends \learningspace\inc\classes\init {
	public function __construct() {
		parent::__construct();
	}
	public static function install() {
		/*                            Top Menu                            */

		$top_menu_name = 'Learning Space Top Menu';
		$top_menu_exists = wp_get_nav_menu_object( $top_menu_name );

		if( !$top_menu_exists){
			$top_menu_id = wp_create_nav_menu($top_menu_name);

			// Set up default menu items
			wp_update_nav_menu_item($top_menu_id, 0, array(
				'menu-item-title' =>  __('Assignments'),
				'menu-item-url' => home_url( '/assignment' ),
				'menu-item-status' => 'publish'));

			wp_update_nav_menu_item($top_menu_id, 0, array(
				'menu-item-title' =>  __('Syllabus'),
				'menu-item-url' => home_url( '/syllabus' ),
				'menu-item-status' => 'publish'));

			wp_update_nav_menu_item($top_menu_id, 0, array(
				'menu-item-title' =>  __('Lessons'),
				'menu-item-url' => home_url( '/lesson' ),
				'menu-item-status' => 'publish'));

			wp_update_nav_menu_item($top_menu_id, 0, array(
				'menu-item-title' =>  __('Calendar'),
				'menu-item-url' => home_url( '/calendar' ),
				'menu-item-status' => 'publish'));

			$locations = get_theme_mod('nav_menu_locations');
			$locations['primary'] = $top_menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
		}

		/*                         Footer Menu                           */

		$footer_menu_name = 'Learning Space Bottom Menu';
		$footer_menu_exists = wp_get_nav_menu_object( $footer_menu_name );

		if( !$footer_menu_exists){
			$footer_menu_id = wp_create_nav_menu($footer_menu_name);

			// Set up default menu items
			wp_update_nav_menu_item($footer_menu_id, 0, array(
				'menu-item-title' =>  __('Assignments'),
				'menu-item-url' => home_url( '/assignment' ),
				'menu-item-status' => 'publish'));

			wp_update_nav_menu_item($footer_menu_id, 0, array(
				'menu-item-title' =>  __('Syllabus'),
				'menu-item-url' => home_url( '/syllabus' ),
				'menu-item-status' => 'publish'));

			wp_update_nav_menu_item($footer_menu_id, 0, array(
				'menu-item-title' =>  __('Lessons'),
				'menu-item-url' => home_url( '/lesson' ),
				'menu-item-status' => 'publish'));

			wp_update_nav_menu_item($footer_menu_id, 0, array(
				'menu-item-title' =>  __('Calendar'),
				'menu-item-url' => home_url( '/calendar' ),
				'menu-item-status' => 'publish'));

			wp_update_nav_menu_item($footer_menu_id, 0, array(
				'menu-item-title' =>  __('Course Documents'),
				'menu-item-url' => home_url( '/document' ),
				'menu-item-status' => 'publish'));
		}
	}
}
