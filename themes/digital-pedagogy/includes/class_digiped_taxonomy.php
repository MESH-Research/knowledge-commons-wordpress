<?php

/**
 * Class DigiPed_Taxonomy
 * Define custom taxonomies for the artifact and collection post types.
 * Register graphQL field types relating to custom taxonomy.
 * Customizations to custom taxonomy
 *
 * @package digiped-theme
 */
class DigiPed_Taxonomy
{
    // used in WP registration hooks. Allows you to unregister it in the future using the name.
    private $plugin_name = "mla_digital_pedagogy";

    /**
     * Initiation function called from the class_digiped_app file.
     */
    public static function init()
    {
        $that = new self();
        $that->doTaxonomies();
        $that->actions();
    }

    /**
     * Function to run the wordpress hooks. Most hooks here relate to class methods.
     */
    public function actions()
    {
        // registers the graphQL field 'total' in the WPPageInfo part of the json result.
        // Used on search results for record count.

        if (function_exists('register_graphql_field')) {
            register_graphql_field('WPPageInfo', 'total', [
                'type' => 'Int',
                'description' => __('Total number of items', $this->plugin_name),
            ]);
        }

        /**
         * Creates custom fields for the Author Tax.
         */
        add_action('created_dp_author', array($this, 'saveTaxMeta'), 10, 2);
        add_action('dp_author_add_form_fields', array($this, 'addTaxMeta'), 10, 2);
        add_action('dp_author_edit_form_fields', array($this, 'editTaxMetaFields'), 10, 2);
        add_action('edited_dp_author', array($this, 'saveTaxMeta'), 10, 2);

        // GraphQL hooks provided by WPGraphQL plugin.
        add_filter('graphql_term_object_connection_query_args', array($this, "graphQL_TagSorting"), 10);
        add_filter('graphql_post_object_connection_query_args', function ($query_args) {
            $query_args["no_found_rows"] = false;

            return $query_args;
        }, 10);
        // Fills in the total result count to the queries in pageInfo:total
        // This is a heavy function, and may be removed in the future for performance purposes.
        add_filter('graphql_connection_page_info', function ($page_info, $connection) {
            $query = $connection->get_query();
            if ($query instanceof WP_Query) {
                if (isset($query->found_posts)) {
                    $page_info['total'] = (int)$query->found_posts;
                }
            } elseif ($query instanceof WP_Term_Query && in_array($query->query_vars['taxonomy'][0], array(
                    "dp_genre",
                    "dp_keyword",
                    "post_tag"
                ))) {
                if (!empty($query->query_vars['name'][0])) {
                    $taxQuery = array(
                        array(
                            'taxonomy' => $query->query_vars['taxonomy'][0],
                            'field' => 'name',
                            'terms' => $query->query_vars['name'][0]
                        )
                    );
                    $artifacts = get_posts(array(
                            'post_type' => 'artifact',
                            'numberposts' => -1,
                            'tax_query' => $taxQuery
                        )
                    );
                    $page_info['total'] = !empty($artifacts) ? count($artifacts) : 0;
                } else {
                    $page_info['total'] = !empty($query->terms) ? (int)count($query->terms) : 0;
                }
            }

            return $page_info;

        }, 999, 2);
    }

    /**
     * // Might be a obsolete function.
     *
     * @param $page_info
     * @param $connection
     *
     * @return mixed
     */
    public function graphQL_QueryTotals($page_info, $connection)
    {

        $page_info['total'] = array("total" => 0, "keywords" => 0, "artifacts" => 0);

        if ($connection->query instanceof WP_Query) {
            if (isset($connection->query->found_posts)) {
                $page_info['total'] = (int)$connection->query->found_posts;
            }
        }

        return $page_info;
    }

    /**
     * Sorts the graphQL taxonomy based results by DESC.
     * @param $query_args
     *
     * @return mixed
     */
    public function graphQL_TagSorting($query_args)
    {
        $query_args["no_found_rows"] = false;
        if (
            ($query_args["taxonomy"] === "post_tag" ||
                $query_args["taxonomy"] === "dp_genre" ||
                $query_args["taxonomy"] === "dp_keyword"
            ) &&
            !empty($query_args["orderby"]) &&
            $query_args["orderby"] === "count"
        ) {
            $query_args["order"] = "DESC";
        }

        return $query_args;
    }

    /**
     * Saves the custom fields on author taxonomy.
     * @param $term_id
     * @param $tt_id
     */
    public function saveTaxMeta($term_id, $tt_id)
    {
        update_term_meta($term_id, 'first_name', $_REQUEST['first_name']);
        update_term_meta($term_id, 'last_name', $_REQUEST['last_name']);
        update_term_meta($term_id, 'affiliation', $_REQUEST['affiliation']);
        update_term_meta($term_id, 'website', $_REQUEST['website']);
        update_term_meta($term_id, 'title', $_REQUEST['title']);
        //error_log(print_r($_REQUEST['role'],true));
        if (is_array($_REQUEST['role'])) {
            delete_term_meta($term_id, 'role'); //removes all role meta fields
            foreach ($_REQUEST['role'] as $r) {
                add_term_meta($term_id, 'role', $r['name'] . ":" . $r['artifact']); // add each one.
            }
        } else {
            update_term_meta($term_id, 'role', $_REQUEST['role']);
        }
    }

    public function roleField($roles = [])
    {
        $head = "<!-- Editable table -->
                    <div id='table' class='mt-3 table-editable'>
                       <table id='role-table' class='table table-bordered table-responsive-md table-striped text-center'>
                        <thead>
                          <tr>
                            <th class='text-center'>Role Name</th>
                            <th class='text-center'>Artifact</th>
                            <th class='text-center'>Remove</th>
                          </tr>
                        </thead>
                        <tbody>";
        $rTemplate = "   <tr>
                          <td class='pt-3-half' contenteditable='false'>
                            <input type='search' name='role[%{count}%][name]' %{name-value}% autocomplete='off' class='form-control autocomplete role-name'>
                                <button class='autocomplete-clear'>
                                    <svg fill='#000000' height='24' viewBox='0 0 24 24' width='24' xmlns='https://www.w3.org/2000/svg'>
                                      <path d='M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z' />
                                      <path d='M0 0h24v24H0z' fill='none' />
                                    </svg>
                                </button>
                          </td>
                          <td class='pt-3-half' contenteditable='false'>
                            <input type='search'  id='artifact-autocomplete' name='role[%{count}%][artifact]' %{artifact-value}% autocomplete='off' class='form-control mdb-autocomplete role-artifact'/>
                                <button class='autocomplete-clear'>
                                    <svg fill='#000000' height='24' viewBox='0 0 24 24' width='24' xmlns='https://www.w3.org/2000/svg'>
                                      <path d='M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z' />
                                      <path d='M0 0h24v24H0z' fill='none' />
                                    </svg>
                                </button>
                          </td>
                          <td>
                            <span class='table-remove'><button type='button' class='btn btn-danger btn-rounded btn-sm my-0'>Remove</button></span>
                          </td>
                         </tr>";
        $foot = "    </tbody>
                    </table>
                   </div>
                   <span class='table-add float-right mt-3 mb-3 mr-2'><a href='#!' class='text-success add-role-link'>Add Another Role</a></span>  
                <!-- Editable table -->";
        $rows = '';
        if(!empty($roles)) {
            $count = 0;
            foreach($roles as $k => $r) {
                //error_log(print_r($r, true));
                $ar = explode(":", $r);
                //error_log(print_r($ar, true));
                $rows .= str_replace(['%{count}%','%{name-value}%', '%{artifact-value}%'], [$count, "value='$ar[0]'", "value='$ar[1]'"], $rTemplate);
                $count++;
            }
        } else {
            $rows .= str_replace(['%{count}%','%{name-value}%', '%{artifact-value}%'], [0,'',''], $rTemplate);
        }

        return $head.$rows.$foot;
    }

    /**
     * Add custom taxonomy fields to the Author Taxonomy admin page.
     */
    public function addTaxMeta()
    {
        ?>

        <!-- Grid row -->
        <div class='row'>

            <!-- Grid column -->
            <div class='col-6'>
                <label for="first_name">

                    <?php _e('First Name', $this->plugin_name); ?></label>
                <p>
                    <input type="text" class="col-12 author-first-name" id="first_name" name="first_name"
                           value=""/>
                </p>
            </div>
            <div class='col-6'>
                <label for="last_name">
                    <?php _e('Last Name', $this->plugin_name); ?></label>
                <p>
                    <input type="text" class="col-12 author-last-name" id="last_name" name="last_name" value=""/>
                </p>
            </div>
        </div>


        <div class='row'>
            <!-- Grid column -->
            <div class='col-12'>
                <label for="title">
                    <?php _e('Title', $this->plugin_name); ?></label>
                <p>
                    <input type="text" class="col-12 author-title" id="title" name="title"
                           value=""/>
                </p>
            </div>
        </div>


        <!-- Grid row -->
        <div class='row'>
            <!-- Grid column -->
            <div class='col-12'>
                <label for="affiliation">
                    <?php _e('Affiliation', $this->plugin_name); ?></label>
                <p>
                    <input type="text" class="col-12 author-affiliation" id="affiliation" name="affiliation"
                           value=""/>
                </p>
            </div>
        </div>


        <div class='row'>
            <!-- Grid column -->
            <div class='col-12'>
                <label for="website">
                    <?php _e('Web site', $this->plugin_name); ?></label>
                <p>
                    <input type="text" class="col-12 author-website" id="website" name="website"
                           value=""/>
                </p>
            </div>
        </div>


        <!--            <label for="role">-->
        <!--                --><?php //_e('Role', $this->plugin_name);
        ?><!--</label>-->
        <!--            <p>-->
        <!--                <input type="text" class="author-role" id="role" name="role"-->
        <!--                       value=""/>-->
        <!--            </p>-->
        <div class='row roles-container'>
            <!-- Grid column -->
            <div class='col-12'>
                <?= $this->roleField() ?>
            </div>
        </div>
        <?php
    }

    /**
     * Update on save the custom fields on the Author Taxonomy admin page.
     * @param $term
     * @param $taxonomy
     */
    public function editTaxMetaFields($term, $taxonomy)
    {
        $first_name = get_term_meta($term->term_id, 'first_name', true);
        $last_name = get_term_meta($term->term_id, 'last_name', true);
        $affiliation = get_term_meta($term->term_id, 'affiliation', true);
        $website = get_term_meta($term->term_id, 'website', true);
        $title = get_term_meta($term->term_id, 'title', true);
        $role = get_term_meta($term->term_id, 'role');
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="first_name">
                    <?php _e('First Name', $this->plugin_name); ?></label>
            </th>
            <td>
                <p>
                    <input type="text" class="author-first-name" id="first_name" name="first_name"
                           value="<?= $first_name; ?>"/>
                </p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="last_name">
                    <?php _e('Last Name', $this->plugin_name); ?></label>
            </th>
            <td>
                <p>
                    <input type="text" class="author-last-name" id="last_name" name="last_name"
                           value="<?= $last_name; ?>"/>
                </p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="affiliation">
                    <?php _e('Affiliation', $this->plugin_name); ?></label>
            </th>
            <td>
                <p>
                    <input type="text" class="author-affiliation" id="affiliation" name="affiliation"
                           value="<?= $affiliation; ?>"/>
                </p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="website">
                    <?php _e('Web site', $this->plugin_name); ?></label>
            </th>
            <td>
                <p>
                    <input type="text" class="author-website" id="website" name="website"
                           value="<?= $website; ?>"/>
                </p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="website">
                    <?php _e('Title', $this->plugin_name); ?></label>
            </th>
            <td>
                <p>
                    <input type="text" class="author-title" id="title" name="title"
                           value="<?= $title; ?>"/>
                </p>
            </td>
        </tr>
        <tr class="form-field"><th scope="row">
                <label for="website">
                    <?php _e('Artifact Roles', $this->plugin_name); ?></label>
            </th>
            <td>
        <?= $this->roleField($role) ?>
            </td></tr>
        <?php
    }

    /**
     * Define and call into creation the custom taxonomies via utility function createCustomTaxonomy located in the functions.php
     * Create corresponding GraphQL definitions for each custom field.
     */
    public function doTaxonomies()
    {
        // Artifact based taxonomies
        createCustomTaxonomy([
            'human' => 'Keyword',
            'machine' => 'dp_keyword',
            'slug' => 'keyword'
        ], ["artifact"], [], [
            'show_in_admin_bar' => true,
            'show_admin_column' => true,
            'show_as_admin_filter' => true]);
        createCustomTaxonomy([
            'human' => 'Genre',
            'machine' => 'dp_genre',
            'slug' => 'genre'
        ], ["artifact"]);
        createCustomTaxonomy([
            'human' => 'Author',
            'machine' => 'dp_author',
            'slug' => 'author'
        ], ["artifact"], [], ['hierarchical' => false]);
        createCustomTaxonomy([
            'human' => 'Citation',
            'machine' => 'dp_citation',
            'slug' => 'citation'
        ], ["artifact"], [], ['hierarchical' => false]);
        createCustomTaxonomy([
            'human' => 'Related Work',
            'machine' => 'dp_related_work',
            'slug' => 'related-work'
        ], ["artifact"], [], ['hierarchical' => false]);
        createCustomTaxonomy([
            'human' => 'Related Keywords',
            'machine' => 'dp_related_keyword',
            'slug' => 'related-keyword'
        ], ["artifact"], [], ['hierarchical' => false]);

        // Collection based taxonomies
        createCustomTaxonomy([
            'human' => 'Collection Type',
            'machine' => 'dp_collection',
            'slug' => 'collection'
        ], ["collection"]);

        // Add the custom meta tags for the Author Tax
        createGraphQLField('first_name', 'string', 'Author First Name', "dp_author", "taxonomy");
        createGraphQLField('last_name', 'string', 'Author Last Name', "dp_author", "taxonomy");
        createGraphQLField('affiliation', 'string', 'Author Affiliation', "dp_author", "taxonomy");
        createGraphQLField('website', 'string', 'Author Website', "dp_author", "taxonomy");
        createGraphQLField('title', 'string', 'Author Title', "dp_author", "taxonomy");
        createGraphQLField('role', ["list_of" => "String"], 'Author Role', "dp_author", "taxonomy");
    }
}
