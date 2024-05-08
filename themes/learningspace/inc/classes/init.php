<?php

namespace learningspace\inc\classes;

use learningspace\inc\classes\assignments\setup as assignment_setup;
use learningspace\inc\classes\lessons\setup as lesson_setup;
use learningspace\inc\classes\documents\setup as documents_setup;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class init {
	public $plugin_name = 'MLA_Learning_Space';

	public function __construct() {
	}

	public static function sunrise() {
		//util::write_log( 'loaded '.get_called_class());
		get_called_class()::start();
	}

	public static function start() {
		// Setup each object section
		assignment_setup::sunrise();
		lesson_setup::sunrise();
		documents_setup::sunrise();
		menus::install();

		add_action( 'plugins_loaded', [ __NAMESPACE__ . '\init', 'install_calendar' ] );
		$menu_order = function ( $menu_ord ) {
			if ( ! $menu_ord ) {
				return true;
			}

			//util::write_log( $menu_ord );

			return array(
				'index.php', // Dashboard
				'separator1', // First separator
				'edit.php?post_type=assignment',
				'edit.php?post_type=lesson',
				'edit.php?post_type=document',
				'edit.php', // Posts
				'upload.php', // Media
				'link-manager.php', // Links
				'edit-comments.php', // Comments
				'edit.php?post_type=page', // Pages
				'separator2', // Second separator
				'themes.php', // Appearance
				'plugins.php', // Plugins
				'users.php', // Users
				'tools.php', // Tools
				'options-general.php', // Settings
				'separator-last', // Last separator
			);
		};
		add_filter( 'custom_menu_order', $menu_order, 10, 1 );
		add_filter( 'menu_order', $menu_order, 10, 1 );

	}

	public static function is_plugin_active_for_network( $plugin ) {
		if ( ! is_multisite() ) {
			return false;
		}

		$plugins = get_site_option( 'active_sitewide_plugins' );
		if ( isset( $plugins[ $plugin ] ) ) {
			return true;
		}

		return false;
	}

	public static function install_calendar() {
		$err = '';
		//util::run_activate_plugin( 'event-organiser/event-organiser.php' );

		$plugin_dir = WP_PLUGIN_DIR . '/event-organiser/event-organiser.php';
		if ( file_exists( $plugin_dir ) ) {
			$plugin = 'event-organiser/event-organiser.php';
			if ( ! ( in_array( $plugin, (array) get_option( 'active_plugins', array() ) ) || self::is_plugin_active_for_network( $plugin ) ) ) {
				util::run_activate_plugin( 'event-organiser/event-organiser.php' );
			}
		} else {
			$err = 'Event Organizer is required for calendar to be used. Please install it from <a href="http://wp-event-organiser.com">http://wp-event-organiser.com</a> debug: ' . $plugin_dir;
		}

		$name         = 'Calendar';
		$html_content = '';
		$page         = get_page_by_title( $name );
		if ( ! isset( $page ) ) {
			$id = wp_insert_post(
				array(
					'comment_status' => 'close',
					'ping_status'    => 'close',
					'post_title'     => ucwords( $name ),
					'post_name'      => strtolower( str_replace( ' ', '-', trim( $name ) ) ),
					'post_status'    => 'publish',
					'post_content'   => '[eo_fullcalendar]' . $err,
					'post_type'      => 'page',
					'post_parent'    => 0
				)
			);
		}

	}

	public static function install_widgets( $sidebar_config ) {
		$active_sidebars = get_option( 'sidebars_widgets' );
		$c        = [];
		$sidebars = [];
		foreach ( $sidebar_config as $sidebar => $widgets ) {
				//if ( isset( $active_sidebars[$sidebar] ) && empty( $active_sidebars[$sidebar] ) ) { //check if sidebar exists and it is empty

			foreach ( $widgets as $widget_name => $w ) {
				//util::write_log( $widget_name );
				//util::write_log( $w );
				$c[ $widget_name ][] = $w['options'];
				$c[ $widget_name ]['_multiwidget'] = 1;
				//util::write_log( $c[ $widget_name ] );
				$count               = count( $c[ $widget_name ] )-2;
				//util::write_log( $sidebar );
				$sidebars[ $sidebar ][] = $widget_name . "-" . $count; //add a widget to sidebar
				update_option( 'sidebars_widgets', $sidebars ); //update that sidebar
				update_option( "widget_$widget_name", $c[ $widget_name ] );
				}

			//}

		}


	}
}
