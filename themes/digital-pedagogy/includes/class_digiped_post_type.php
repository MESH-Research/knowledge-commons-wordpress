<?php

/**
 * Class DigiPed_Post_Type
 * Handles all definition of custom post types as well as customizations concerning those CPT.
 *
 * @package digiped-theme
 */

class DigiPed_Post_Type
{
    // used in WP registration hooks. Allows you to unregister it in the future using the name.
    private $plugin_name = "mla_digital_pedagogy";

    /**
     * Initiation function called from the class_digiped_app file.
     */
    public static function init()
    {
        $that = new self();
        $that->doPostTypes();
        add_action('graphql_register_types', function () {
            if (function_exists('register_graphql_field')) {
                register_graphql_field('artifact', 'curator', [
                    'type' => 'String',
                    'description' => __('Curator', 'wp-graphql'),
                    'resolve' => function ($post) {
                        $rec = get_post_meta($post->artifactId, 'keyword_curators', true);
                        $curator = !empty($rec) ? implode(", ", $rec) : false;
                        return !empty($curator) ? $curator : '';
                    }
                ]);
                register_graphql_field('artifact', 'sources', [
                    'type' => 'String',
                    'description' => __('Source URL of original artifact on the internet', 'wp-graphql'),
                    'resolve' => function ($post) {
                        $rec = get_post_meta($post->artifactId, 'source_urls', true);
                        $curator = !empty($rec) ? implode(", ", $rec) : false;
                        return !empty($curator) ? $curator : '';
                    }
                ]);
                register_graphql_field('artifact', 'core', [
                    'type' => 'String',
                    'description' => __('Url for CORE artifact entry', 'wp-graphql'),
                    'resolve' => function ($post) {
                        $copy_artifact = get_post_meta($post->artifactId, 'copy_of_artifact', true);
                        $curator = $copy_artifact ? implode(", ", $copy_artifact) : false;
                        return !empty($curator) ? $curator : '';
                    }
                ]);
            }
        });
    }

    /**
     * - Defines the Collection and the Artifact collection types.
     *   Passes the definition to the createCustomPostType utility function located in the functions file.
     * - Creates/modifies user permissions for these CPT. Also affects general capabilities per project spec.
     * - Modifies the Menu placement of these CPTs. Also affects general menu items per project spec.
     * - Initiates Registration of joined GraphQL connection for the two custom post types.
     *     */
    public function doPostTypes()
    {

        $names = "Artifacts";
        $lc_names = strtolower($names);
        $name = Inflector::singularize($names);
        $artifacts = array(
            'args' => array(
                'label' => __($names, $this->plugin_name),
                'labels' => array(
                    'name' => __($names, 'Post Type General Name', $this->plugin_name),
                    'singular_name' => _x($names, 'Post Type Singular Name', $this->plugin_name),
                    'menu_name' => __($name, $this->plugin_name),
                    'parent_item_colon' => __("Parent $name:", $this->plugin_name),
                    'all_items' => __("All $names", $this->plugin_name),
                    'view_item' => __("View $name", $this->plugin_name),
                    'add_new_item' => __("Select $name:", $this->plugin_name),
                    'add_new' => __('Add New', $this->plugin_name),
                    'edit_item' => __("Edit $name", $this->plugin_name),
                    'update_item' => __("Update $name", $this->plugin_name),
                    'search_items' => __("Search $name", $this->plugin_name),
                    'not_found' => __('Not Found', $this->plugin_name),
                    'not_found_in_trash' => __('Not found in Trash', $this->plugin_name),
                ),
                'description' => __("Learning space's active $names", $this->plugin_name),
                // Features this CPT supports in Post Editor
                'supports' => array('title', 'thumbnail', 'editor', 'custom-fields'),
                // You can associate this CPT with a taxonomy or custom taxonomy.
                'taxonomies' => array('category', 'keyword', 'post_tag'),
                'hierarchical' => false,
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_nav_menus' => false,
                'show_in_admin_bar' => false,
                'menu_position' => 4,
                'can_export' => true,
                'has_archive' => true,
                'exclude_from_search' => false,
                'publicly_queryable' => true,
            )
        );
        createCustomPostType('Artifact', $artifacts['args']);
        //createGraphQLField( 'keyword_curators', 'array', 'A list of related keywords curators', "artifact", "postType" );
        addCustomFieldToPost(
            $name,
            'source_urls',
            'Source URL',
            'serialized_list',
            'normal',
            'high'
        );

        addCustomFieldToPost(
            $name,
            'screenshot',
            'Screenshot',
            'serialized_list',
            'normal',
            'high'
        );

        addCustomFieldToPost(
            $name,
            'permissions',
            'Permissions',
            'serialized_list',
            'normal',
            'high'
        );

        addCustomFieldToPost(
            $name,
            'copy_of_artifact',
            'Copy of Artifact',
            'serialized_list',
            'normal',
            'high'
        );


        $names = "Collections";

        $name = Inflector::singularize($names);
        $lc_names = strtolower($names);
        $lc_name = strtolower($name);
        $collection = array(
            'args' => array(
                'label' => __('Collections', $this->plugin_name),
                'labels' => array(
                    'name' => __($names, 'Post Type General Name', $this->plugin_name),
                    'singular_name' => _x($name, 'Post Type Singular Name', $this->plugin_name),
                    'menu_name' => __($names, $this->plugin_name),
                    'parent_item_colon' => __("Parent $name:", $this->plugin_name),
                    'all_items' => __("All $names", $this->plugin_name),
                    'view_item' => __("View $name", $this->plugin_name),
                    'add_new_item' => __("Select $name:", $this->plugin_name),
                    'add_new' => __('Add New', $this->plugin_name),
                    'edit_item' => __("Edit $name", $this->plugin_name),
                    'update_item' => __("Update $name", $this->plugin_name),
                    'search_items' => __("Search $name", $this->plugin_name),
                    'not_found' => __('Not Found', $this->plugin_name),
                    'not_found_in_trash' => __('Not found in Trash', $this->plugin_name),
                ),
                'description' => __("Learning space's active $names", $this->plugin_name),
                // Features this CPT supports in Post Editor
                'supports' => array('title', 'thumbnail', 'editor', 'comments', 'author'),
                // You can associate this CPT with a taxonomy or custom taxonomy.
                'taxonomies' => array('collection'),
                'hierarchical' => false,
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_nav_menus' => false,
                'show_in_admin_bar' => false,
                'menu_position' => 5,
                'can_export' => true,
                'has_archive' => true,
                'exclude_from_search' => false,
                'publicly_queryable' => true,
                'capability_type' => array($lc_name, $lc_names),
                'map_meta_cap' => true,
            )
        );
        createCustomPostType('Collection', $collection['args']);

        //Capability cleanup and resolution
        add_action('init', function () {

            // Adding the roles you'd like to administer the custom post types
            $roles = array('subscriber', 'editor', 'administrator');
            foreach ($roles as $the_role) {
                $role = get_role($the_role);
                if ('subscriber' === $the_role) {
                    add_filter('register_post_type_args', function ($args, $postType) {
                        //delete all editorial abilities for artifact.
                        if (($postType === 'artifact' || $postType === 'post')) {

                            $args['capabilities'] = [
                                'edit_post' => false,
                                'delete_post' => false,
                                'edit_posts' => false,
                                'edit_others_posts' => false,
                                'delete_posts' => false,
                                'delete_private_posts' => false,
                                'delete_published_posts' => false,
                                'delete_others_posts' => false,
                                'edit_private_posts' => false,
                                'edit_published_posts' => false,
                                'create_posts' => false,
                            ];
                        }
                        return $args;
                    }, 0, 2);


                    $role->remove_cap('edit_post');
                    $role->remove_cap('delete_post');
                    $role->remove_cap('edit_posts');
                    $role->remove_cap('edit_others_posts');
                    $role->remove_cap('delete_posts');
                    $role->remove_cap('delete_private_posts');
                    $role->remove_cap('delete_published_posts');
                    $role->remove_cap('delete_others_posts');
                    $role->remove_cap('edit_private_posts');
                    $role->remove_cap('edit_published_posts');
                    $role->remove_cap('create_posts');
                }

                add_filter('register_post_type_args', function ($args, $postType) {
                    if (($postType === 'collection')) {
                        $args['capabilities'] = [
                            'create_posts' => true,
                            'edit_posts' => true,
                            'edit_post' => true,
                            'delete_post' => true,
                            'read' => true,
                            'delete_others_posts' => true,
                            'edit_published_posts' => true,
                            'edit_collections' => true,
                            'edit_others_collections' => true,
                        ];
                    }
                    return $args;
                }, 0, 2);

                $role->add_cap('create_posts');
                $role->add_cap('edit_posts');
                $role->add_cap('edit_post');
                $role->add_cap('delete_post');
                $role->add_cap('read');
                $role->add_cap('delete_others_posts');
                $role->add_cap('edit_published_posts');
                $role->add_cap('edit_collections');
                $role->add_cap('edit_others_collections');

            }


            //for everyone

        }, 999);

        //remove menu pages if they are not an admin or editor
        if (!(current_user_can('editor') || current_user_can('administrator'))) {
            add_action('admin_menu', function () {
                remove_menu_page('edit.php'); //remove post
                remove_menu_page('edit-comments.php'); //remove comments
                remove_menu_page('edit.php?post_type=artifact'); //remove artifacts
                remove_menu_page('edit.php?post_type=collection'); //collections
            });
            add_action('admin_init', function () {
                remove_meta_box('dashboard_right_now', 'dashboard', 'normal'); //Removes the 'At a Glance' widget
                remove_meta_box('dashboard_activity', 'dashboard', 'normal'); //Removes the 'Activity' widget (since 3.8)
                remove_meta_box('dashboard_quick_press', 'dashboard', 'side'); //Removes the 'Quick Draft' widget
            });
        }

        add_action('graphql_register_types', array($this, 'register_collectionToArtifact_graphql_connection'), 99);

        add_filter('mutate_and_get_payload_prepare_comment_object', function ($args) {
            $args['comment_approve'] = "1";

            return $args;
        });
    }

    /**
     * Registers a GraphQL connection that joins the Collection record with Artifact records it contains via the collection comment record as lookup.
     * @param       $post_type
     * @param array $args
     */
    public static function register_collectionToArtifact_graphql_connection($post_type, $args = [])
    {
        $config = [
            'fromType' => 'Collection',
            'toType' => 'Artifact',
            'fromFieldName' => 'Artifacts',
            'resolve' => function ($id, $args, $context, $info) {
                $resolver = new \WPGraphQL\Data\Connection\PostObjectConnectionResolver($id, $args, $context, $info, 'artifact');
                $artifacts = array_reverse(DigiPed_Collection::get_artifacts($id->ID)); // Collection CRUD utility class. Returns array of id's.
                //error_log(print_r($artifacts,true));
                if ($artifacts) {
                    $resolver->setQueryArg('post__in', $artifacts);
                    $resolver->setQueryArg('orderby', 'post__in');
                } else {
                    //an empty post__in will result in all artifacts. So we give an absurdly long id to never match on.
                    $resolver->setQueryArg('post__in', [9999999999]);
                }
                $resolver->setQueryArg('post_parent', 0);
                $connection = $resolver->get_connection();

                return $connection;
            },

            'resolveNode' => function ($id, $args, $context, $info) {
                return \WPGraphQL\Data\DataSource::resolve_post_object($id, $context);
            }
        ];
        if (function_exists('register_graphql_connection')) {
            register_graphql_connection($config);
        }
    }

}



