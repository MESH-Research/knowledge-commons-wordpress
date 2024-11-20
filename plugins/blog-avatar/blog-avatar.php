<?php
/**
 * Plugin Name: Blog Avatar
 * Plugin URI: https://buddydev.com/plugins/blog-avatar/
 * Version: 1.1.0
 * Author: BuddyDev Team
 * Author URI: https://buddydev.com/
 * Description: Allow Site Admins to upload avatar for a blog
 *
 * @package blog-avatar
 **/

// exit if file access directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BD_Blog_Avatar
 */
class BD_Blog_Avatar {
	/**
	 * Holds class object
	 *
	 * @var static $instance holds class object,
	 */
	private static $instance = null;
	/**
	 * BD_Blog_Avatar constructor.
	 */
	private function __construct() {
		add_action( 'bp_loaded', array( $this, 'load' ) );
	}
	/**
	 * Function load neccesary plugins file.
	 */
	function load() {

		$path = plugin_dir_path( __FILE__ );

		if ( ! bp_is_active( 'blogs' ) ) {
			return;
		}

		$files = array(
			'hooks.php',
			'functions.php',
		);
		if ( is_admin() ) {
			$files[] = 'admin/admin.php';
		}
		foreach ( $files as $file ) {
			require_once $path . $file;
		}
	}
	/**
	 * Return Instance of class
	 *
	 * @return BD_Blog_Avatar
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}

BD_Blog_Avatar::get_instance();// Have a fun .....

