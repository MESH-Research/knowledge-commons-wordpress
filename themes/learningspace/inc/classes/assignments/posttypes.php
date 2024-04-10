<?php

namespace learningspace\inc\classes\assignments;
use learningspace\inc\classes\Inflector;
class posttypes extends \learningspace\inc\classes\posttypes {
	public function __construct() {
		parent::__construct();
	}
	public function get_config () {
		$names     = "Assignments";
		$name      = singularize( $names );
		return array(
			'name' => ucfirst($name),
			'args' => array(
				'label'               => __( $names, $this->plugin_name ),
				'labels'              => array(
					'name'               => __( $names, 'Post Type General Name', $this->plugin_name ),
					'singular_name'      => _x( $names, 'Post Type Singular Name', $this->plugin_name ),
					'menu_name'          => __( $name, $this->plugin_name ),
					'parent_item_colon'  => __( "Parent $name:", $this->plugin_name ),
					'all_items'          => __( "All $names", $this->plugin_name ),
					'view_item'          => __( "View $name", $this->plugin_name ),
					'add_new_item'       => __( "Select $name:", $this->plugin_name ),
					'add_new'            => __( 'Add New', $this->plugin_name ),
					'edit_item'          => __( "Edit $name", $this->plugin_name ),
					'update_item'        => __( "Update $name", $this->plugin_name ),
					'search_items'       => __( "Search $name", $this->plugin_name ),
					'not_found'          => __( 'Not Found', $this->plugin_name ),
					'not_found_in_trash' => __( 'Not found in Trash', $this->plugin_name ),
				),
				'description'         => __( "Learning space's active $names", $this->plugin_name ),
				// Features this CPT supports in Post Editor
				'supports'            => array( 'title',  'thumbnail', 'editor', 'custom-fields', 'comments' ),
				// You can associate this CPT with a taxonomy or custom taxonomy.
				'taxonomies'          => array( 'category', 'keyword', 'post_tag' ),
				'hierarchical'        => false,
				'public'              => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => true,
				'show_in_admin_bar'   => true,
				'menu_position'       => -1,
				'can_export'          => true,
				'has_archive'         => true,
				'exclude_from_search' => false,
				'publicly_queryable'  => true,
			)
		);
	}
}
