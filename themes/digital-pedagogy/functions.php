<?php
add_theme_support('post-thumbnails');
add_theme_support( 'menus' );

//removes logout warning confirmation message
add_action('check_admin_referer', static function ($action, $result){
    /**
     * Allow logout without confirmation
     */
    if ($action === "log-out" && !isset($_GET['_wpnonce'])) {
        $redirect_to = $_REQUEST['redirect_to'] ?? home_url();
        $location = str_replace('&amp;', '&', wp_logout_url($redirect_to));
        header("Location: $location");
        die;
    }
}, 10,2);

/**
 * @param $post_type_name
 * @param $args
 */
function createCustomPostType($post_type_name, $args)
{
    if (empty($args['labels'])) {
        $args['labels'] = array(
            'name' => _x($post_type_name, "", "post_type_" . $post_type_name),
            'singular_name' => _x(Inflector::singularize($post_type_name), "", "post_type_" . $post_type_name),
        );
    }
    $name = strtolower(str_replace(" ", "_", $post_type_name));
    //required for Gutenberg
    $args['show_in_rest'] = true;

    //required for graphQL
    $args['show_in_graphql'] = true;
    $args['hierarchical'] = true;
    $args['graphql_single_name'] = Inflector::singularize($name);
    $args['graphql_plural_name'] = Inflector::pluralize($name);

    register_post_type(Inflector::singularize($name), $args);
}

/**
 * @param array $fields
 */
//
//array (
//	array(
//		"post_type" => "collection",
//		"meta_name" => "collection_type",
//		"meta_description" => "The collection types"
//	),
//	array(
//		"tax_name" => "author",
//		"meta_name" => "first_name",
//		"meta_description" => "The first name"
//	),
//);
function createGraphQLField($name, $type = "string", $description = "", $obj_name = "author", $obj_type = "taxonomy")
{
    add_action('graphql_register_types', function () use ($name, $type, $description, $obj_name, $obj_type) {
        register_graphql_field($obj_name, $name, array(
            'type' => $type,
            'description' => __($description, 'mla_digital_pedagogy'),
            'resolve' => function ($post) use ($name, $obj_type, $obj_name, $type) {
                if ($obj_type === "postType") {
                    $value = get_post_meta($post->ID, $name, true);
                } else {
                    $term_id = (int)$post->term_id;
                    if ("array" === $type || is_array($type)) {
                        $value = get_metadata('term', $term_id, $name, false);
                    } else {
                        $value = get_metadata('term', $term_id, $name, true);
                    }
                }

                //$value = $data === false ? $value : $data;

                switch ($type) {
                    case "int":
                        return (int)$value;

                    case "bool":
                        return (bool)$value;

                    case "string":
                        return (string)$value;

                    case "array":
                        return (array)$value;

                    default:
                        return $value;
                }

            }
        ));
    });
}

/**
 * @param       $tax_name
 * @param array $post_types
 * @param array $labels
 * @param array $options
 */
function createCustomTaxonomy($tax_name, $post_types = array("post"), $labels = array(), $options = array())
{
    if (is_array($tax_name)) {
        $array = $tax_name;
        $tax_name = $array['human'];
    }

    $tax_machine_name = is_array($array) && !empty($array['machine']) ?
        $array['machine'] :
        strtolower(str_replace(" ", "_", $tax_name));
    $tax_slug = is_array($array) && !empty($array['slug']) ?
        $array['slug'] :
        strtolower(str_replace("_", "-", $tax_machine_name));

    $tax_name_singular = Inflector::singularize($tax_name);

    $labels = array_merge(array(
        'name' => __($tax_name, 'tax_' . $tax_name),
        'singular_name' => __($tax_name_singular, 'tax_' . $tax_name),
        'search_items' => __('Search ' . $tax_name),
        'all_items' => __('All ' . $tax_name),
        'edit_item' => __('Edit ' . $tax_name_singular),
        'update_item' => __('Update ' . $tax_name_singular),
        'add_new_item' => __('Add New ' . $tax_name_singular),
        'new_item_name' => __("new $tax_name_singular Name"),
        'parent_item' => __('Parent Topic'),
        'parent_item_colon' => __('Parent Topic:'),
    ), $labels);

    $options = array_merge(array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menu' => false,
        'show_in_admin_bar' => false,
        'show_admin_column' => false,
        'public' => true,
        'has_archive' => true,
        'query_var' => true,
        'show_in_graphql' => true, //needed for wpgraphql plugin\
        'graphql_single_name' => Inflector::singularize($tax_machine_name),
        'graphql_plural_name' => Inflector::pluralize($tax_machine_name),
        'show_in_rest' => true, //required for Gutenberg
        'rewrite' => array(
            'slug' => $tax_slug,
            'with_front' => false,
        )
    ), $options);

    if (!empty($options['show_as_admin_filter']) && $options['show_as_admin_filter'] == true) {
        //error_log('inside condition');
        foreach ($post_types as $type) {
            add_action('restrict_manage_posts', function () use ($type, $tax_name, $tax_machine_name) {
                if (isset($_GET['post_type'])) {
                    $pt = $_GET['post_type'];
                } else {
                    $pt = 'post';
                }

                //only add filter to post type you want
                if ($type == $pt) {
                    //change this to the list of values you want to show
                    //in 'label' => 'value' format
                    $terms = get_terms(array(
                        'taxonomy' => $tax_machine_name,
                        'hide_empty' => true,
                    ));
                    //error_log(print_r($terms, true));
                    $values = [];
                    foreach ($terms as $t) {
                        $values[$t->name] = $t->slug;
                    }
                    $hookName = strtoupper($tax_name) . "_VALUE";
                    ?>
                    <select name="<?= $hookName ?>">
                        <option value=""
                                selected="selected"><?php _e('All ' . ucwords($tax_name), 'dp_admin'); ?></option>
                        <?php
                        $current_v = isset($_GET[$hookName]) ? $_GET[$hookName] : '';
                        foreach ($values as $label => $value) {
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
            });
            add_filter('parse_query', function ($query) use ($type, $tax_name, $tax_machine_name) {
                global $pagenow;
                if (isset($_GET['post_type'])) {
                    $pt = $_GET['post_type'];
                } else {
                    $pt = 'post';
                }
                $hookName = strtoupper($tax_name) . "_VALUE";
                if ($type == $pt && is_admin() && $pagenow == 'edit.php' && isset($_GET[$hookName]) && $_GET[$hookName] != '') {
                    //error_log(print_r($query, true));
                    $query->query_vars['tax_query'] = array(
                        array(
                            'taxonomy' => $tax_machine_name,
                            'field' => 'slug',
                            'terms' => $_GET[$hookName]
                        )
                    );
                }
            });
        }
    }

    register_taxonomy($tax_machine_name, $post_types, $options);
}

function addCustomFieldToPost($pt, $id, $label, $type, $context = 'normal', $priority = 'high', array $options = array())
{
    add_action('save_post', function ($post_id) use ($id) {
        // Bail if we're doing an auto save

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        // if our nonce isn't there, or we can't verify it, bail
        if (empty($_POST['meta_box_nonce']) || !wp_verify_nonce($_POST['meta_box_nonce'], 'my_meta_box_nonce')) return;
        // if our current user can't edit this post, bail
        if (!current_user_can('edit_post')) return;
        //no data field, bail
        if (empty($_POST[$id])) return;
//        error_log(print_r(wp_debug_backtrace_summary(), true));
//        error_log(print_r($id, true));
//        error_log(print_r($_POST[$id], true));


        // Probably a good idea to make sure your data is set
        $save_value = $_POST[$id];
        if (!empty($_POST["serialize_data_$id"])) {
            if (is_array($_POST[$id])) {
                $ar = [];
                if (!is_serialized($_POST[$id][0])) {
                    foreach ($_POST[$id] as $value) {
                        $ar[] = $value['name'];
                    }
                }
                $save_value = $ar;
//                error_log(print_r($ar, true));
//                error_log(print_r('srl 1', true));
            } else {
//                error_log(print_r('srl 2', true));
                if (!is_serialized($_POST[$id]['name'])) {
                    $save_value = serialize($_POST[$id]['name']);
                } else {
                    $save_value = $_POST[$id]['name'];
                }

            }
            delete_post_meta($post_id, $id);
            update_post_meta($post_id, $id, $save_value);
            return;
        }

        if (is_array($save_value)) {
            delete_post_meta($post_id, $id);
            foreach ($_POST[$id] as $v) {
                add_post_meta($post_id, $id, $v['name']);
            }
            return;
        } else {
            update_post_meta($post_id, $id, $save_value);
            return;
        }

    }, 1000);
    add_action('add_meta_boxes', function () use ($context, $priority, $pt, $id, $label, $type, $options) {
        add_meta_box(
            'meta_box_' . $id,
            $label,
            function ($post) use ($id, $label, $type, $options) {
                $class = !empty($options['classes']) ? is_array($options['classes']) ? implode(" ", $options['classes']) : $options['classes'] : '';
                $values = get_post_custom($post->ID);
                $selected = !empty($values[$id]) ? $values[$id] : '';
                wp_nonce_field('my_meta_box_nonce', 'meta_box_nonce');
                $html = '';
                switch ($type) {
                    case 'serialized_list':
                        $head = "<!-- Editable table -->
                        <div class='mt-3 table-editable'>
                        <span class='table-row-add'><a href='#!' class='float-right text-success add-td-link'>Add Another $label</a>
                       <table id='table_$id' class='table table-bordered table-responsive-md table-striped text-center editable-table'>
                        <thead>
                          <tr>
                            <th class='text-center'>$label</th>
                            <th class='text-center'>Remove</th>
                          </tr>
                        </thead>
                        <tbody>";
                        $rTemplate = "   <tr>
                          <td class='pt-3-half' contenteditable='false'>
                            <input type='search' name='" . $id . "[][name]' %{name-value}% autocomplete='off' class='form-control autocomplete elem-name'>
                                <button class='autocomplete-clear'>
                                    <svg fill='#000000' height='24' viewBox='0 0 24 24' width='24' xmlns='https://www.w3.org/2000/svg'>
                                      <path d='M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z' />
                                      <path d='M0 0h24v24H0z' fill='none' />
                                    </svg>
                                </button>
                          </td>
                          <td>
                            <span class='table-elem-remove'><button type='button' class='btn btn-danger btn-rounded btn-sm my-0'>Remove</button></span>
                          </td>
                         </tr>";
                        $foot = "<input type='hidden' name='serialize_data_$id' value='true'/></tbody>
                                            </table>   </span>  
                                           </div>
                                        
                <!-- Editable table -->";
                        $rows = '';
                        //error_log(print_r($selected, true));

                        if (!empty($selected) && is_array($selected)) {
                            $count = 0;
                            foreach ($selected as $k) {
                                //error_log(print_r($k, true));
                                if (is_serialized($k)) {
                                    $k = unserialize($k);
                                    //error_log(is_array($k));
                                    if (is_array($k)) {
                                        foreach ($k as $kk) {
                                            $rows .= str_replace(['%{count}%', '%{name-value}%'], [$count, "value='$kk'"], $rTemplate);
                                            $count++;
                                        }
                                    } else {
                                        $rows .= str_replace(['%{count}%', '%{name-value}%'], [0, "value='$k'"], $rTemplate);
                                    }
                                } else {
                                    $rows .= str_replace(['%{count}%', '%{name-value}%'], [$count, "value='$k'"], $rTemplate);
                                    $count++;
                                }
                            }
                        } else {
                            //error_log('string');
                            $rows .= str_replace(['%{count}%', '%{name-value}%'], [0, "value='$selected'"], $rTemplate);
                        }
                        $html = $head . $rows . $foot;
                        break;
                    case 'select':
                    case 'list':
                        $is_list = $type === 'list' ? 'size="5"' : '';
                        $html = "<select class='$class select $type' id='$id' name='$id' $is_list>";
                        if (!empty($options['values']) && is_array($options['values'])) {
                            foreach ($options['values'] as $k => $v) {
                                $s = selected($selected, $v, false);
                                $html .= "<option class='$class option-$type' value='$v' $s>$v</option>.";
                            }
                        }
                        $html .= '</select>';
                        break;
                    case 'radio':
                    case 'checkbox':
                        $html = "";
                        if (!empty($options['values']) && is_array($options['values'])) {
                            foreach ($options['values'] as $k => $v) {
                                $s = selected($selected, $v, false);
                                $sClass = $s ? 'selected' : '';
                                $html .= "<input class='$class radio-check $type $sClass' type='$type'  id='$id' name='$id'> value='$v' $s>
                                           <label class='$class label radio-check-label $type-label' for='$id'>$k</label><br>";
                            }
                        }
                        break;
                    case 'button':
                    case 'reset':
                    case 'submit':
                        $html = "<button class='$class button'  type='$type'>$selected</button>";
                        break;

                    case 'date':
                    case 'datetime-local':
                    case 'text':
                    case 'email':
                    case 'tel':
                    case 'url':
                    case 'password':
                    case 'time':
                    case 'hidden':
                    case 'color':
                    case 'file':
                    case 'image':
                    case 'range':
                    case 'search':
                    case 'week':
                    default:
                        $html = "<label class='$class label input-label $type' for='$id'>$label</label><br/>
                                       <input class='$class input $type-label'  id='$id' type='$type' name='$id' value='$selected'/><br/>";
                        break;
                }
                echo $html;
            },
            $pt,
            $context,
            $priority);
    });


}

add_action('init', 'app_init');
function app_init()
{
    if (!class_exists('DigiPed_App')) {
        require_once('includes/class_digiped_app.php');
        DigiPed_App::init();
    }
}

register_activation_hook(__FILE__, 'plugin_activate');
function plugin_activate()
{
    // register taxonomies/post types here
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'plugin_deactivate');
function plugin_deactivate()
{
    flush_rewrite_rules();
}
