<?php

namespace learningspace\inc\classes;
class util {
	public function __construct() {
	}

	/**
	 * @param $post_type_name
	 * @param $args
	 */
	public static function create_custom_post_type( $post_type_name, $args ) {
		if ( empty( $args['labels'] ) ) {
			$args['labels'] = array(
				'name'          => _x( $post_type_name, "", "post_type_" . $post_type_name ),
				'singular_name' => _x( Inflector::singularize( $post_type_name ), "", "post_type_" . $post_type_name ),
			);
		}
		$name = strtolower( str_replace( " ", "_", $post_type_name ) );
		//required for Gutenberg
		$args['show_in_rest'] = true;

		//required for graphQL
		$args['show_in_graphql']     = true;
		$args['hierarchical']        = true;
		$args['graphql_single_name'] = Inflector::singularize( $name );
		$args['graphql_plural_name'] = Inflector::pluralize( $name );

		register_post_type( Inflector::singularize( $name ), $args );
	}

	/**
	 * @param       $tax_name
	 * @param array $post_types
	 * @param array $labels
	 * @param array $options
	 */
	public static function create_custom_taxonomy( $tax_name, $post_types = array( "post" ), $labels = array(), $options = array() ) {
		if ( is_array( $tax_name ) ) {
			$array    = $tax_name;
			$tax_name = $array['human'];
		}

		$tax_machine_name = is_array( $array ) && ! empty( $array['machine'] ) ?
			$array['machine'] :
			strtolower( str_replace( " ", "_", $tax_name ) );
		$tax_slug         = is_array( $array ) && ! empty( $array['slug'] ) ?
			$array['slug'] :
			strtolower( str_replace( "_", "-", $tax_machine_name ) );

		$tax_name_singular = Inflector::singularize( $tax_name );

		$labels = array_merge( array(
			'name'              => __( $tax_name, 'tax_' . $tax_name ),
			'singular_name'     => __( $tax_name_singular, 'tax_' . $tax_name ),
			'search_items'      => __( 'Search ' . $tax_name ),
			'all_items'         => __( 'All ' . $tax_name ),
			'edit_item'         => __( 'Edit ' . $tax_name_singular ),
			'update_item'       => __( 'Update ' . $tax_name_singular ),
			'add_new_item'      => __( 'Add New ' . $tax_name_singular ),
			'new_item_name'     => __( "new $tax_name_singular Name" ),
			'parent_item'       => __( 'Parent Topic' ),
			'parent_item_colon' => __( 'Parent Topic:' ),
		), $labels );

		$options = array_merge( array(
			'hierarchical'        => true,
			'labels'              => $labels,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menu'    => false,
			'show_in_admin_bar'   => false,
			'show_admin_column'   => false,
			'public'              => true,
			'has_archive'         => true,
			'query_var'           => true,
			'show_in_graphql'     => true, //needed for wpgraphql plugin\
			'graphql_single_name' => Inflector::singularize( $tax_machine_name ),
			'graphql_plural_name' => Inflector::pluralize( $tax_machine_name ),
			'show_in_rest'        => true, //required for Gutenberg
			'rewrite'             => array(
				'slug'       => $tax_slug,
				'with_front' => false,
			)
		), $options );

		if ( ! empty( $options['show_as_admin_filter'] ) && $options['show_as_admin_filter'] == true ) {
			error_log( 'inside condition' );
			foreach ( $post_types as $type ) {
				add_action( 'restrict_manage_posts', function () use ( $type, $tax_name, $tax_machine_name ) {
					if ( isset( $_GET['post_type'] ) ) {
						$pt = $_GET['post_type'];
					} else {
						$pt = 'post';
					}

					//only add filter to post type you want
					if ( $type == $pt ) {
						//change this to the list of values you want to show
						//in 'label' => 'value' format
						$terms = get_terms( array(
							'taxonomy'   => $tax_machine_name,
							'hide_empty' => true,
						) );
						//error_log(print_r($terms, true));
						$values = [];
						foreach ( $terms as $t ) {
							$values[ $t->name ] = $t->slug;
						}
						$hookName = strtoupper( $tax_name ) . "_FIELD_VALUE";
						?>
                        <select name="<?= $hookName ?>">
                            <option value=""
                                    selected="selected"><?php _e( 'All ' . ucwords( $tax_name ), 'dp_admin' ); ?></option>
							<?php
							$current_v = isset( $_GET[ $hookName ] ) ? $_GET[ $hookName ] : '';
							foreach ( $values as $label => $value ) {
								printf
								(
									'<option value="%s"%s>%s</option>',
									$value,
									$value == $current_v ? ' selected="selected"' : '',
									$label
								);
							}
							?>
                        </select>
						<?php
					}
				} );
				add_filter( 'parse_query', function ( $query ) use ( $type, $tax_name, $tax_machine_name ) {
					global $pagenow;
					if ( isset( $_GET['post_type'] ) ) {
						$pt = $_GET['post_type'];
					} else {
						$pt = 'post';
					}
					$hookName = strtoupper( $tax_name ) . "_FIELD_VALUE";
					if ( $type == $pt && is_admin() && $pagenow == 'edit.php' && isset( $_GET[ $hookName ] ) && $_GET[ $hookName ] != '' ) {
						//error_log(print_r($query, true));
						$query->query_vars['tax_query'] = array(
							array(
								'taxonomy' => $tax_machine_name,
								'field'    => 'slug',
								'terms'    => $_GET[ $hookName ]
							)
						);
					}
				} );
			}
		}

		register_taxonomy( $tax_machine_name, $post_types, $options );
	}

	/**
	 * @param $post_id
	 * @param $field_name
	 * @param $field_value
	 * @param bool $unique
	 * @param string $old_value
	 *
	 * @return bool|int
	 */
	public static function write_custom_field( $post_id, $field_name, $field_value, $unique = true, $old_value = '' ) {
		if ( $retVal = add_post_meta( $post_id, $field_name, $field_value, $unique ) === false ) {
			$retVal = update_post_meta( $post_id, $field_name, $field_value, $old_value );
		}

		return $retVal;
	}

	/**
	 * @param $post_id
	 * @param $field_name
	 * @param bool $return_array
	 *
	 * @return mixed
	 */
	public static function read_custom_field( $post_id, $field_name, $return_array = false ) {
		return get_post_meta( $post_id, $field_name, ! $return_array );
	}

	/**
	 * @return void
	 */
	public static function debug_admin_menus() {
		if ( ! is_admin() ) {
			return;
		}
		global $submenu, $menu, $pagenow;
		if ( current_user_can( 'manage_options' ) ) { // ONLY DO THIS FOR ADMIN
			if ( $pagenow == 'index.php' ) {  // PRINTS ON DASHBOARD
				echo '<pre>';
				print_r( $menu );
				echo '</pre>'; // TOP LEVEL MENUS
				echo '<pre>';
				print_r( $submenu );
				echo '</pre>'; // SUBMENUS
			}
		}
	}

	/**
	 * @return bool
	 */
	public static function is_wp_admin_page() {
		global $pagenow;

		return ( 'post.php' === $pagenow || 'post-new.php' === $pagenow );
	}

	/**
	 * @param bool $post_type
	 *
	 * @return bool
	 */
	public static function is_admin_page( $post_type = false ) {
		global $pagenow;
		$action = false;
		if ( $pagenow == "post-new.php" ) {
			$action = 'new';
		}

		if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) {
			$action = 'edit';
		}

		$retVal = self::is_wp_admin_page() && ( ( in_array( $post_type, array(
						'courses',
						'course_asset'
					) ) && $action === 'new' ) || $action === 'edit' );

		return $retVal;
	}

	/**
	 * writes debugging info to log
	 * @param $log
	 * @param bool $objArr
	 * @param bool $backtrace
	 */
	public static function write_log( $log, $objArr = false, $backtrace = true ) {
		if ( $backtrace ) {
			$backtrace = debug_backtrace();
			$bk_msg    = $backtrace[0]['file'] . "/" . $backtrace[0]['line'] . ": \n";
		} else {
			$backtrace = array(
				array( "file" => "", "line" => "" )
			);
			$bk_msg    = "";
		}

		if ( is_array( $log ) || is_object( $log ) ) {
			error_log( $bk_msg . print_r( $log, true ) );
		} else {
			error_log( $bk_msg . $log );
			if ( $objArr ) {
				self::write_log( $objArr, false, false );
			}
		}
	}

	/**
	 * Returns the last SQL WP ran
	 * @param bool $msg
	 */
	public static function last_SQL( $msg = false ) {
		global $wpdb;
		if ( $msg ) {
			self::write_log( $msg );
		}
		self::write_log( $wpdb->last_query );
	}

	/**
	 * Adds the WP Gutenberg class and comment tags for paragraph, image and video blocks.
	 *
	 * @param $string
	 *
	 * @return string
	 */
	public static function add_gutenberg_asset_tags( $string ) {
		$string = preg_replace( '#<p(.*?)>(.*?)</p>#is', '<!-- wp:paragraph -->$2<!-- /wp:paragraph -->', $string );
		$string = preg_replace( '/(<img[^>]+>(?:<\/img>)?)/i', '<!-- wp:image --><figure class="wp-block-image">$1</figure><!-- /wp:image -->', $string );
		$string = preg_replace( '/(<video[^>]+>(?:<\/video>)?)/i', '<!-- wp:video --><figure class="wp-block-video">$1</figure><!-- /wp:video -->', $string );

		return $string;
	}

	public static function run_activate_plugin( $plugin ) {
		$current = get_option( 'active_plugins' );
		$plugin = plugin_basename( trim( $plugin ) );

		if ( !in_array( $plugin, $current ) ) {
			$current[] = $plugin;
			sort( $current );
			do_action( 'activate_plugin', trim( $plugin ) );
			update_option( 'active_plugins', $current );
			do_action( 'activate_' . trim( $plugin ) );
			do_action( 'activated_plugin', trim( $plugin) );
		}

		return null;
	}

}
