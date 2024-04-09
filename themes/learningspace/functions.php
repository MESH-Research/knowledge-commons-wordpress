<?php
/**
 * WP Bootstrap Starter functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WP_Bootstrap_Starter
 */

if ( ! function_exists( 'wp_bootstrap_starter_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function wp_bootstrap_starter_setup() {
		/**
		 * Dynamically loads the class attempting to be instantiated elsewhere in the
		 * plugin by looking at the $class_name parameter being passed as an argument.
		 *
		 * The argument should be in the form: learningspace\Namespace. The
		 * function will then break the fully-qualified class name into its pieces and
		 * will then build a file to the path based on the namespace.
		 *
		 * The namespaces in this plugin map to the paths in the directory structure.
		 *
		 * @param string $class_name The fully-qualified name of the class to load.
		 *
		 * @author tutsplus https://code.tutsplus.com/tutorials/using-namespaces-and-autoloading-in-wordpress-plugins-4--cms-27342
		 */
		$template = get_template_directory();
		require get_template_directory() . '/vendor/autoload.php';
		spl_autoload_register( function ( $class_name ) {

			// If the specified $class_name does not include our namespace, duck out.
			if ( false === strpos( $class_name, 'learningspace' ) ) {
				return;
			}

			// Split the class name into an array to read the namespace and class.
			$file_parts = explode( '\\', $class_name );

			// Do a reverse loop through $file_parts to build the path to the file.
			$namespace = '';
			for ( $i = count( $file_parts ) - 1; $i > 0; $i -- ) {

				// Read the current component of the file part.
				$current = strtolower( $file_parts[ $i ] );
				$current = str_ireplace( '_', '-', $current );

				// If we're at the first entry, then we're at the filename.
				if ( count( $file_parts ) - 1 === $i ) {

					/* If 'interface' is contained in the parts of the file name, then
					 * define the $file_name differently so that it's properly loaded.
					 * Otherwise, just set the $file_name equal to that of the class
					 * filename structure.
					 */
					if ( strpos( strtolower( $file_parts[ count( $file_parts ) - 1 ] ), 'interface' ) ) {

						// Grab the name of the interface from its qualified name.
						$interface_name = explode( '_', $file_parts[ count( $file_parts ) - 1 ] );
						$interface_name = $interface_name[0];

						$file_name = "interface-$interface_name.php";

					} else {
						$file_name = "$current.php";
					}
				} else {
					$namespace = '/' . $current . $namespace;
				}
			}

			// Now build a path to the file using mapping to the file location.
			$filepath = trailingslashit( dirname( __FILE__ ) . $namespace );
			$filepath .= $file_name;

			// If the file exists in the specified path, then include it.
			if ( file_exists( $filepath ) ) {
				include_once( $filepath );
			} else {
				wp_die(
					esc_html( "The file attempting to be loaded at $filepath does not exist." )
				);
			}
		} );
		/*
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory.
		 * If you're building a theme based on WP Bootstrap Starter, use a find and replace
		 * to change 'wp-bootstrap-starter' to the name of your theme in all the template files.
		 */
		load_theme_textdomain( 'wp-bootstrap-starter', get_template_directory() . '/languages' );

		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		// This theme uses wp_nav_menu() in one location.
		register_nav_menus( array(
			'primary' => esc_html__( 'Primary', 'wp-bootstrap-starter' ),
		) );

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support( 'html5', array(
			'comment-form',
			'comment-list',
			'caption',
		) );

		// Set up the WordPress core custom background feature.
		add_theme_support( 'custom-background', apply_filters( 'wp_bootstrap_starter_custom_background_args', array(
			'default-color' => 'ffffff',
			'default-image' => '',
		) ) );

		// Add theme support for selective refresh for widgets.
		add_theme_support( 'customize-selective-refresh-widgets' );

		function wp_boostrap_starter_add_editor_styles() {
			add_editor_style( 'custom-editor-style.css' );
		}

		add_action( 'admin_init', 'wp_boostrap_starter_add_editor_styles' );

		//Starts our custom theme logic in the inc/classes folder
		add_action( 'init', function () {
			if ( method_exists( 'learningspace\inc\classes\init', 'start' ) ) {
				learningspace\inc\classes\init::start();
			}

			//setup blade in session
			$template = get_template_directory();
			//\learningspace\inc\classes\util::write_log($template);
			$blade_views = "$template/inc/blade/views";
			//\learningspace\inc\classes\util::write_log($blade_views);
			$srv = '/srv/www/commons/current/web/app/cache';
			if ( file_exists( $srv ) && is_dir( $srv ) ) {
				//echo "server cache!";
				$cache = '/srv/www/commons/current/web/app/cache';
			} else {
				//echo "local mode!";
				$cache = "$template/inc/blade/local_cache";
			}

			if ( ! defined( 'B_VIEWS' ) ) {
				define( 'B_VIEWS', $blade_views );
			}
			if ( ! defined( 'B_CACHE' ) ) {
				define( 'B_CACHE', $cache );
			}
			$blade             = array(
				0       => new Jenssegers\Blade\Blade( B_VIEWS, B_CACHE ),
				'views' => B_VIEWS,
				'cache' => B_CACHE
			);
			$_SESSION['blade'] = $blade;
		} );


	}
endif;
add_action( 'after_setup_theme', 'wp_bootstrap_starter_setup' );

/**
 * On theme activation house cleaning duties
 */


add_action('after_switch_theme', 'learning_space_setup');
function learning_space_setup() {
	$sidebar_config = array(
		'sidebar-1'               => [
			'learning_space'       => [
				'name'    => 'learning_space',
				'options' => [
					'title'    => 'Course Documents',
					'count'    => 5,
					'artifact' => 'document',
					'display'  => 'lists',
					'orderby'  => 'DESC',
					'order'    => 'post_date'
				]
			],
			'eo_event_list_widget' => [
				'name'    => 'eo_event_list_widget',
				'options' => [
					'title'       => 'Upcoming Dates',
					'numberposts' => 7,
				]
			]

		],
		'front-page-sidebar'      => [
			'eo_event_list_widget' => [
				'name'    => 'eo_event_list_widget',
				'options' => [
					'title'       => 'Upcoming Dates',
					'numberposts' => 7,
				]
			]
		],
		'front-page-bottom-left'  => [
			'learning_space' => [
				'name'    => 'learning_space',
				'options' => [
					'title'    => 'Lessons',
					'count'    => 10,
					'artifact' => 'lesson',
					'display'  => 'excerpt_lists',
					'orderby'  => 'DESC',
					'order'    => 'post_date'
				]
			],
		],
		'front-page-bottom-right' => [
			'learning_space' => [
				'name'    => 'learning_space',
				'options' => [
					'title'    => 'Assignments',
					'count'    => 8,
					'artifact' => 'assignment',
					'display'  => 'lists',
					'orderby'  => 'DESC',
					'order'    => 'post_date'
				]
			],
			'learning_space' => [
				'name'    => 'learning_space',
				'options' => [
					'title'    => 'Course Documents',
					'count'    => 8,
					'artifact' => 'document',
					'display'  => 'lists',
					'orderby'  => 'DESC',
					'order'    => 'post_date'
				]
			],
		],
		'assignment'              => [
			'eo_event_list_widget' => [
				'name'    => 'eo_event_list_widget',
				'options' => [
					'title'       => 'Upcoming Dates',
					'numberposts' => 15,
				]
			]
		],
		'lesson'                  => [
			'eo_event_list_widget' => [
				'name'    => 'eo_event_list_widget',
				'options' => [
					'title'       => 'Upcoming Dates',
					'numberposts' => 15,
				]
			]

		],
		'document'                => [

			'learning_space' => [
				'name'    => 'learning_space',
				'options' => [
					'title'    => 'Assignments',
					'count'    => 8,
					'artifact' => 'assignment',
					'display'  => 'lists',
					'orderby'  => 'DESC',
					'order'    => 'post_date'
				]
			],

		],
		'footer-1'                => [],
		'footer-2'                => [],
		'footer-3'                => [],
	);
	\learningspace\inc\classes\init::install_widgets( $sidebar_config );

	//create sample assignments
	if ( empty( get_page_by_title( 'Sample Assignment',OBJECT,'assignment' ) ) ) {
		wp_insert_post( [
			'post_content' => 'Add details of an assignment here. If an assignment has multiple components, you may want to structure the different section using headers. You can also link to relevant <a href="/lesson">lessons</a> and readings.
<br>
If there is a deadline associated with this assignment, you can <a href="/wp-admin/edit.php?post_type=event">add an event</a> on the course calendar and link to this Assignments post.
<br>
When you\'re ready to begin, you can edit this assignment\'s title and permalink, or you can delete it.',
			'post_title'   => 'Sample Assignment',
			'post_status'  => 'publish',
			'post_type'    => 'assignment',
		] );
	}

	//create sample lessons
	if ( empty( get_page_by_title( 'Sample Lesson',OBJECT,'lesson' ) ) ) {
		wp_insert_post( [
			'post_content'   => 'Use lessons to present information relevant to a class meeting or unit. The information you include here might include the content of a lecture, supplemental commentary on a reading, a set of instructions, or a collection of links to other resources.
<br>
If you want to enable discussion around lessons, review your <a href="/wp-admin/options-discussion.php">discussion settings</a> and be sure that "Allow comments" is checked on each lesson post.',
			'post_title'     => 'Sample Lesson',
			'post_status'    => 'publish',
			'post_type'      => 'lesson',
			'comment_status' => 'open',
		] );
	}
	//create sample document
	if ( empty( get_page_by_title( 'Sample Document',OBJECT,'document' ) ) ) {
		wp_insert_post( [
			'post_content' => 'You can add course documents directly in a Document post, or link to a resource hosted elsewhere.',
			'post_title'   => 'Sample Document',
			'post_status'  => 'publish',
			'post_type'    => 'document',
		] );
	}
	if ( empty( get_page_by_title( 'Syllabus' ) ) ) {
		wp_insert_post(
			array(
				'comment_status' => 'close',
				'ping_status'    => 'close',
				'post_title'     => ucwords( 'Syllabus' ),
				'post_name'      => strtolower( str_replace( ' ', '-', trim( 'Syllabus' ) ) ),
				'post_status'    => 'publish',
				'post_content'   => 'Add your course syllabus here. If you like, as you post lessons and assignments on the site, you can update the syllabus page to link to them.
<h2>Section 1</h2>
Headers, like the one above, can be helpful to structure the page.',
				'post_type'      => 'page',
				'post_parent'    => 0
			)
		);
	}

	if ( empty( get_page_by_title( 'Frontpage' ) ) ) {
		$frontpage_id = wp_insert_post(
			array(
				'comment_status' => 'close',
				'ping_status'    => 'close',
				'post_title'     => ucwords( 'Frontpage' ),
				'post_name'      => strtolower( str_replace( ' ', '-', trim( 'Frontpage' ) ) ),
				'post_status'    => 'publish',
				'post_content'   => 'This is the course description. Edit this page to change this description. Please customize the theme from the admin to change your course name/site title, instructor name and/or e-mail address. You can add lessons, assignments, events, and course documents via the dashboard.',
				'post_type'      => 'page',
				'post_parent'    => 0
			)
		);
		update_post_meta( $frontpage_id, '_wp_page_template', 'frontpage' );
		update_option( 'page_on_front', $frontpage_id );
		update_option( 'show_on_front', 'page' );
	}

	if ( empty( get_page_by_title( 'Sample Page' ) ) ) {
		wp_insert_post(
			array(
				'comment_status' => 'close',
				'ping_status'    => 'close',
				'post_title'     => ucwords( 'Sample Page' ),
				'post_name'      => strtolower( str_replace( ' ', '-', trim( 'Sample Page' ) ) ),
				'post_status'    => 'publish',
				'post_content'   => 'This is an example page. It\'s different from a blog post because it will stay in one place and will show up in your site navigation (in most themes). Most people start with an About page that introduces them to potential site visitors. It might say something like this:
<br>
<blockquote>Hi there! I\'m a bike messenger by day, aspiring actor by night, and this is my website. I live in Los Angeles, have a great dog named Jack, and I like pi&#241;a coladas. (And gettin\' caught in the rain.)</blockquote>
<br>
...or something like this:
<br>
<blockquote>The XYZ Doohickey Company was founded in 1971, and has been providing quality doohickeys to the public ever since. Located in Gotham City, XYZ employs over 2,000 people and does all kinds of awesome things for the Gotham community.</blockquote>
<br>
As a new WordPress user, you should go to <a href="https://showme.rumi.mlacommons.org/wp-admin/">your dashboard</a> to delete this page and create new pages for your content. Have fun!',
				'post_type'      => 'page',
				'post_parent'    => 0
			)
		);
	}

} ;


/**
 * Add Welcome message to dashboard
 */
function wp_bootstrap_starter_reminder() {
	$theme_page_url = 'https://afterimagedesigns.com/wp-bootstrap-starter/?dashboard=1';

	if ( ! get_option( 'triggered_welcomet' ) ) {
		$message = sprintf( __( 'Welcome to WP Bootstrap Starter Theme! Before diving in to your new theme, please visit the <a style="color: #fff; font-weight: bold;" href="%1$s" target="_blank">theme\'s</a> page for access to dozens of tips and in-depth tutorials.', 'wp-bootstrap-starter' ),
			esc_url( $theme_page_url )
		);

		printf(
			'<div class="notice is-dismissible" style="background-color: #6C2EB9; color: #fff; border-left: none;">
                        <p>%1$s</p>
                    </div>',
			$message
		);
		add_option( 'triggered_welcomet', '1', '', 'yes' );
	}
	\learningspace\inc\classes\util::write_log('learning space theme setup complete');
}

//add_action( 'admin_notices', 'wp_bootstrap_starter_reminder' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function wp_bootstrap_starter_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'wp_bootstrap_starter_content_width', 1170 );
}

add_action( 'after_setup_theme', 'wp_bootstrap_starter_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function wp_bootstrap_starter_widgets_init() {
	register_sidebar( array(
		'name'          => esc_html__( 'Sidebar', 'wp-bootstrap-starter' ),
		'id'            => 'sidebar-1',
		'description'   => esc_html__( 'Add widgets here.', 'wp-bootstrap-starter' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
	register_sidebar( array(
		'name'          => esc_html__( 'Frontpage Sidebar', 'wp-bootstrap-starter' ),
		'id'            => 'front-page-sidebar',
		'description'   => esc_html__( 'Add widgets here.', 'wp-bootstrap-starter' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
	register_sidebar( array(
		'name'          => esc_html__( 'Frontpage Bottom Left', 'wp-bootstrap-starter' ),
		'id'            => 'front-page-bottom-left',
		'description'   => esc_html__( 'Add widgets here.', 'wp-bootstrap-starter' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
	register_sidebar( array(
		'name'          => esc_html__( 'Frontpage Bottom Right', 'wp-bootstrap-starter' ),
		'id'            => 'front-page-bottom-right',
		'description'   => esc_html__( 'Add widgets here.', 'wp-bootstrap-starter' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
	register_sidebar( array(
		'name'          => esc_html__( 'Assignments', 'wp-bootstrap-starter' ),
		'id'            => 'assignment',
		'description'   => esc_html__( 'Add widgets here.', 'wp-bootstrap-starter' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
	register_sidebar( array(
		'name'          => esc_html__( 'Lessons', 'wp-bootstrap-starter' ),
		'id'            => 'lesson',
		'description'   => esc_html__( 'Add widgets here.', 'wp-bootstrap-starter' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
	register_sidebar( array(
		'name'          => esc_html__( 'Documents', 'wp-bootstrap-starter' ),
		'id'            => 'document',
		'description'   => esc_html__( 'Add widgets here.', 'wp-bootstrap-starter' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
	register_sidebar( array(
		'name'          => esc_html__( 'Footer 1', 'wp-bootstrap-starter' ),
		'id'            => 'footer-1',
		'description'   => esc_html__( 'Add widgets here.', 'wp-bootstrap-starter' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
	register_sidebar( array(
		'name'          => esc_html__( 'Footer 2', 'wp-bootstrap-starter' ),
		'id'            => 'footer-2',
		'description'   => esc_html__( 'Add widgets here.', 'wp-bootstrap-starter' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
	register_sidebar( array(
		'name'          => esc_html__( 'Footer 3', 'wp-bootstrap-starter' ),
		'id'            => 'footer-3',
		'description'   => esc_html__( 'Add widgets here.', 'wp-bootstrap-starter' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );

	$classname  = 'learning_space';
	$desc       = 'Learn Space by MLA';
	$components = new \learningspace\inc\classes\components(
		$classname,
		$desc,
		[ 'classname' => $classname, 'description' => $desc ]
	);
	register_widget( $components );
}

add_action( 'widgets_init', 'wp_bootstrap_starter_widgets_init' );


/**
 * Enqueue scripts and styles.
 */
function wp_bootstrap_starter_scripts() {
	// load bootstrap css
	if ( get_theme_mod( 'cdn_assets_setting' ) === 'yes' ) {
		wp_enqueue_style( 'wp-bootstrap-starter-bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css' );
		wp_enqueue_style( 'wp-bootstrap-starter-fontawesome-cdn', 'https://use.fontawesome.com/releases/v5.10.2/css/all.css' );
	} else {
		wp_enqueue_style( 'wp-bootstrap-starter-bootstrap-css', get_template_directory_uri() . '/inc/assets/css/bootstrap.min.css' );
		wp_enqueue_style( 'wp-bootstrap-starter-fontawesome-cdn', get_template_directory_uri() . '/inc/assets/css/fontawesome.min.css' );
	}
	// load bootstrap css
	// load AItheme styles
	// load WP Bootstrap Starter styles
	wp_enqueue_style( 'wp-bootstrap-starter-style', get_stylesheet_uri() );
	if ( get_theme_mod( 'theme_option_setting' ) && get_theme_mod( 'theme_option_setting' ) !== 'default' ) {
		wp_enqueue_style( 'wp-bootstrap-starter-' . get_theme_mod( 'theme_option_setting' ), get_template_directory_uri() . '/inc/assets/css/presets/theme-option/' . get_theme_mod( 'theme_option_setting' ) . '.css', false, '' );
	}
	if ( get_theme_mod( 'preset_style_setting' ) === 'poppins-lora' ) {
		wp_enqueue_style( 'wp-bootstrap-starter-poppins-lora-font', 'https://fonts.googleapis.com/css?family=Lora:400,400i,700,700i|Poppins:300,400,500,600,700' );
	}
	if ( get_theme_mod( 'preset_style_setting' ) === 'montserrat-merriweather' ) {
		wp_enqueue_style( 'wp-bootstrap-starter-montserrat-merriweather-font', 'https://fonts.googleapis.com/css?family=Merriweather:300,400,400i,700,900|Montserrat:300,400,400i,500,700,800' );
	}
	if ( get_theme_mod( 'preset_style_setting' ) === 'poppins-poppins' ) {
		wp_enqueue_style( 'wp-bootstrap-starter-poppins-font', 'https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700' );
	}
	if ( get_theme_mod( 'preset_style_setting' ) === 'roboto-roboto' ) {
		wp_enqueue_style( 'wp-bootstrap-starter-roboto-font', 'https://fonts.googleapis.com/css?family=Roboto:300,300i,400,400i,500,500i,700,700i,900,900i' );
	}
	if ( get_theme_mod( 'preset_style_setting' ) === 'arbutusslab-opensans' ) {
		wp_enqueue_style( 'wp-bootstrap-starter-arbutusslab-opensans-font', 'https://fonts.googleapis.com/css?family=Arbutus+Slab|Open+Sans:300,300i,400,400i,600,600i,700,800' );
	}
	if ( get_theme_mod( 'preset_style_setting' ) === 'oswald-muli' ) {
		wp_enqueue_style( 'wp-bootstrap-starter-oswald-muli-font', 'https://fonts.googleapis.com/css?family=Muli:300,400,600,700,800|Oswald:300,400,500,600,700' );
	}
	if ( get_theme_mod( 'preset_style_setting' ) === 'montserrat-opensans' ) {
		wp_enqueue_style( 'wp-bootstrap-starter-montserrat-opensans-font', 'https://fonts.googleapis.com/css?family=Montserrat|Open+Sans:300,300i,400,400i,600,600i,700,800' );
	}
	if ( get_theme_mod( 'preset_style_setting' ) === 'robotoslab-roboto' ) {
		wp_enqueue_style( 'wp-bootstrap-starter-robotoslab-roboto', 'https://fonts.googleapis.com/css?family=Roboto+Slab:100,300,400,700|Roboto:300,300i,400,400i,500,700,700i' );
	}
	if ( get_theme_mod( 'preset_style_setting' ) && get_theme_mod( 'preset_style_setting' ) !== 'default' ) {
		wp_enqueue_style( 'wp-bootstrap-starter-' . get_theme_mod( 'preset_style_setting' ), get_template_directory_uri() . '/inc/assets/css/presets/typography/' . get_theme_mod( 'preset_style_setting' ) . '.css', false, '' );
	}
	//Color Scheme
	/*if(get_theme_mod( 'preset_color_scheme_setting' ) && get_theme_mod( 'preset_color_scheme_setting' ) !== 'default') {
		wp_enqueue_style( 'wp-bootstrap-starter-'.get_theme_mod( 'preset_color_scheme_setting' ), get_template_directory_uri() . '/inc/assets/css/presets/color-scheme/'.get_theme_mod( 'preset_color_scheme_setting' ).'.css', false, '' );
	}else {
		wp_enqueue_style( 'wp-bootstrap-starter-default', get_template_directory_uri() . '/inc/assets/css/presets/color-scheme/blue.css', false, '' );
	}*/

	wp_enqueue_script( 'jquery' );

	// Internet Explorer HTML5 support
	wp_enqueue_script( 'html5hiv', get_template_directory_uri() . '/inc/assets/js/html5.js', array(), '3.7.0', false );
	wp_script_add_data( 'html5hiv', 'conditional', 'lt IE 9' );

	// load bootstrap js
	if ( get_theme_mod( 'cdn_assets_setting' ) === 'yes' ) {
		wp_enqueue_script( 'wp-bootstrap-starter-popper', 'https://cdn.jsdelivr.net/npm/popper.js@1/dist/umd/popper.min.js', array(), '', true );
		wp_enqueue_script( 'wp-bootstrap-starter-bootstrapjs', 'https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js', array(), '', true );
	} else {
		wp_enqueue_script( 'wp-bootstrap-starter-popper', get_template_directory_uri() . '/inc/assets/js/popper.min.js', array(), '', true );
		wp_enqueue_script( 'wp-bootstrap-starter-bootstrapjs', get_template_directory_uri() . '/inc/assets/js/bootstrap.min.js', array(), '', true );
	}
	wp_enqueue_script( 'wp-bootstrap-starter-themejs', get_template_directory_uri() . '/inc/assets/js/theme-script.min.js', array(), '', true );
	wp_enqueue_script( 'wp-bootstrap-starter-skip-link-focus-fix', get_template_directory_uri() . '/inc/assets/js/skip-link-focus-fix.min.js', array(), '20151215', true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}

add_action( 'wp_enqueue_scripts', 'wp_bootstrap_starter_scripts' );


/**
 * Add Preload for CDN scripts and stylesheet
 */
function wp_bootstrap_starter_preload( $hints, $relation_type ) {
	if ( 'preconnect' === $relation_type && get_theme_mod( 'cdn_assets_setting' ) === 'yes' ) {
		$hints[] = [
			'href'        => 'https://cdn.jsdelivr.net/',
			'crossorigin' => 'anonymous',
		];
		$hints[] = [
			'href'        => 'https://use.fontawesome.com/',
			'crossorigin' => 'anonymous',
		];
	}

	return $hints;
}

add_filter( 'wp_resource_hints', 'wp_bootstrap_starter_preload', 10, 2 );


function wp_bootstrap_starter_password_form() {
	global $post;
	$label = 'pwbox-' . ( empty( $post->ID ) ? rand() : $post->ID );
	$o     = '<form action="' . esc_url( site_url( 'wp-login.php?action=postpass', 'login_post' ) ) . '" method="post">
    <div class="d-block mb-3">' . __( "To view this protected post, enter the password below:", "wp-bootstrap-starter" ) . '</div>
    <div class="form-group form-inline"><label for="' . $label . '" class="mr-2">' . __( "Password:", "wp-bootstrap-starter" ) . ' </label><input name="post_password" id="' . $label . '" type="password" size="20" maxlength="20" class="form-control mr-2" /> <input type="submit" name="Submit" value="' . esc_attr__( "Submit", "wp-bootstrap-starter" ) . '" class="btn btn-primary"/></div>
    </form>';

	return $o;
}

add_filter( 'the_password_form', 'wp_bootstrap_starter_password_form' );


/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Custom functions that act independently of the theme templates.
 */
require get_template_directory() . '/inc/extras.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load plugin compatibility file.
 */
require get_template_directory() . '/inc/plugin-compatibility/plugin-compatibility.php';

/**
 * Load custom WordPress nav walker.
 */
if ( ! class_exists( 'wp_bootstrap_navwalker' ) ) {
	require_once( get_template_directory() . '/inc/wp_bootstrap_navwalker.php' );
}


/* Enqueue custom styles/scripts */
function wpdocs_learningspace_scripts() {
	wp_enqueue_style( 'theme-styles', get_template_directory_uri() . '/inc/assets/css/styles.css' );
	//  wp_enqueue_script( 'script-name', get_template_directory_uri() . '/js/example.js', array(), '1.0.0', true );
}

add_action( 'wp_enqueue_scripts', 'wpdocs_learningspace_scripts' );
