<?php
/**
 * Class: DigiPed_App
 * Description: The DigiPed_App class instantiates the DigiPed Application for use with the Wordpress CMS.
 * The class bootstraps the creation of custom post types and taxonomies that are unique to the DigiPed app.
 * In addition the class sets up the enqueue of React based scripts and css to work with the WordPress CMS.
 *
 * Created By: Joseff Betancourt
 * Created: 2019-03-19
 * License: GPLv3
 */

class DigiPed_App
{
    /**
     * Description: the DigiPed_App:init function is the main method called from the theme functions file
     * and sets the ball rolling for the rest of the app.
     */
    public static function init()
    {
        // We check if the classes already exist before actually loading them.
        // If they already exist and we try to load them a PHP Error will occur.
        // Load order is important here as some classes require other classes to already exist.
        self::theme_image_settings();
        /**
         * Custom utility class to negotiate Collection CRUD activities.
         */
        if (!class_exists('DigiPed_Collection')) {
            require_once('class_digiped_collection.php');
        }

        /**
         * Inflection class borrowed from Laravel.
         * Handles conversion of words from singular to plural and vice versa.
         * This is used in the creation of Post, taxonomies and GraphQL connections.
         */
        if (!class_exists('Inflector')) {
            require_once('class_inflector.php');
        }

        /**
         * Class that creates and manages custom post types and their dependencies.
         */
        if (!class_exists('DigiPed_Post_Type')) {
            require_once('class_digiped_post_type.php');

        }


        /**
         * Class that creates and manages custom taxonomies and their dependencies.
         */
        if (!class_exists('DigiPed_Taxonomy')) {
            require_once('class_digiped_taxonomy.php');
        }

        // NOTE: the WP Plugin WPGRAPHQL and WPGRAPHQL-TAX are required
        if (!class_exists('WPGraphQL')) {
            add_action('admin_notices', function () {
                $class = 'notice notice-error';
                $message = __('Dependency Error! WP GraphQL is required.', 'digital-pedagogy');

                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
            });
            return;
        }

        if (!class_exists('WPGraphQL\TaxQuery')) {
            add_action('admin_notices', function () {
                $class = 'notice notice-error';
                $message = __('Dependency Error! WP GraphQL Taxonomy is required.', 'digital-pedagogy');

                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
            });
            return;
        }

        // Here we actually initiate the creation of post types and taxonomies and
        // enqueue our React App and styles.
        DigiPed_Post_Type::init();
        DigiPed_Taxonomy::init();
        add_action('wp_enqueue_scripts', array('DigiPed_App', 'enqueue_react_app'), 1);
        add_action('admin_enqueue_scripts', ['DigiPed_App', 'enqueue_admin_scripts']);
        remove_filter('comment_text', 'wpautop', 30);
        add_filter('comment_flood_filter', '__return_false');
    }

    public
    static function enqueue_admin_scripts($hook)
    {
        //error_log(print_r(get_current_screen(),true));

        if ('edit - dp_author' != get_current_screen()->id && 'artifact' != get_current_screen()->id) {
            return;
        }
        //error_log(print_r('here', true));
        $theme = wp_get_theme();


        wp_register_style('fontawesome.5.8.2', 'https://use.fontawesome.com/releases/v5.8.2/css/all.css', false, $theme->get('Version'));
        wp_register_style('bootstrap.4.4.1', 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.4.1/css/bootstrap.min.css', false, $theme->get('Version'));
        wp_register_style('mdb.4.14.0', 'https://cdnjs.cloudflare.com/ajax/libs/mdbootstrap/4.14.0/css/mdb.min.css', ['fontawesome.5.8.2', 'bootstrap.4.4.1'], $theme->get('Version'));
        wp_enqueue_style('mdb.4.14.0');

        wp_enqueue_script('jquery.3.4.1', 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js', false, $theme->get('Version'));
        wp_enqueue_script('popper.js.1.14.4', 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.4/umd/popper.min.js', false, $theme->get('Version'));
        wp_enqueue_script('bootstrap.4.4.1', 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.4.1/js/bootstrap.min.js', false, $theme->get('Version'));
        wp_register_script('mdb', 'https://cdnjs.cloudflare.com/ajax/libs/mdbootstrap/4.14.0/js/mdb.min.js', ['jquery.3.4.1', 'popper.js.1.14.4', 'bootstrap.4.4.1', 'bootstrap-autocomplete.2.3.2'], $theme->get('Version'));
        wp_enqueue_script('mdb');
        wp_enqueue_style('dp_admin_css', get_template_directory_uri() . '/assets/css/dp_admin.css', ['mdb.4.14.0'], $theme->get('Version'));
        wp_register_script('dp_admin_js', get_template_directory_uri() . '/assets/js/dp_admin.js', ['jquery.3.4.1'], $theme->get('Version'), true);
        wp_enqueue_script('dp_admin_js');

        wp_register_script('editable_table_js', get_template_directory_uri() . '/assets/js/editable_table.js', ['jquery.3.4.1'], $theme->get('Version'), true);
        wp_enqueue_script('editable_table_js');
    }

    /**
     * Enqueues the stylesheet and js for the WPGraphiQL app
     */
    public
    static function enqueue_react_app()
    {
        /**
         * The DigiPed App uses react as it's core language.
         * It uses webpack to compile js and styles into dist packages for staging and production.
         * However for local development we use webpack's web-dev-server for rapid development.
         * As such we need to test the env to see if we are in one of the three env.
         * Below we check if the dist file is created and if it is we use that.
         * Otherwise we are using the web-dev-server so we change the ports and locations accordingly.
         *
         * check against server host and web_dev_server host
         */
        $theme = wp_get_theme();
        if (file_exists(get_template_directory() . '/dist/bundle.js')) {
            wp_enqueue_script('digital_pedagogy', get_template_directory_uri() . '/dist/bundle.js', [], $theme->get('1.0.4'), true);
        } else {
            wp_enqueue_script('digital_pedagogy', "//" . $_SERVER['SERVER_NAME'] . ':8080/bundle.js', [], $theme->get('Version'), true);
        }

        // note that we NEED to add a version to the css for it to work.
        if (file_exists(get_template_directory() . '/dist/style.css')) {
            wp_enqueue_style('digital_pedagogy_style', get_template_directory_uri() . '/dist/style.css', array(), $theme->get('1.0.4'));
        } else {
            wp_enqueue_style('digital_pedagogy_style', "//" . $_SERVER['SERVER_NAME'] . ':8080/style.css', array(), $theme->get('Version'));
        }

        /**
         * Here we localize the Wordpress CMS variables we want to make available for React to use.
         */
        wp_localize_script(
            'digital_pedagogy',
            'dpVars',
            array(
                'nonce' => wp_create_nonce('wp_rest'),
                'graphqlEndpoint' => trailingslashit(get_bloginfo('url')) . \WPGraphQL\Router::$route,
                'userId' => get_current_user_id()
            )
        );

    }

    public
    static function theme_image_settings()
    {
        add_theme_support('post-thumbnails');
        add_image_size('featured-medium', 600, 600, false); // width, height,
        add_image_size('medium-width', 600);
        add_image_size('medium-height', 300);
        add_filter('image_size_names_choose', function ($sizes) {
            return array_merge($sizes, array(
                'medium-width' => __('Medium Width'),
                'medium-height' => __('Medium Height'),
            ));
        });
    }
}
