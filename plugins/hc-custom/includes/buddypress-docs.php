<?php
/**
 * Custom Changes to BuddyPress Docs plugin.
 *
 * @package Hc_Custom
 */

/**
 * Modifies the default sort order. If it isn't set in the admin
 * settings it will default to title.
 *
 * @param str $order_by The order_by item: title, author, created, modified, etc.
 */
function hc_custom_bp_docs_default_sort_order( $order_by ) {

	$bp = buddypress();

	if ( isset( $bp->groups->current_group->id ) ) {
		// Default to the current group first.
		$group_id = $bp->groups->current_group->id;
	} elseif ( isset( $groups_template->group->id ) ) {
		// Then see if we're in the loop.
		$group_id = $groups_template->group->id;
	} else {
		return false;
	}

	$order_by = ! empty( groups_get_groupmeta( $group_id, 'bp_docs_orderby_default' ) ) ? groups_get_groupmeta( $group_id, 'bp_docs_orderby_default' ) : 'title';

	return $order_by;
}

add_filter( 'bp_docs_default_sort_order', 'hc_custom_bp_docs_default_sort_order' );

/**
 * Order attachments for a Doc alphabetically.
 *
 * @param array $atts_args Optional post args for the query.
 * @param int   $doc_id ID of the document.
 */
function hc_custom_bp_docs_get_doc_attachments_args( $atts_args, $doc_id ) {

	$order = array(
		'order'   => 'ASC',
		'orderby' => 'title',
	);

	$merged_array = array_merge( $atts_args, $order );

	return $merged_array;
}

add_filter( 'bp_docs_get_doc_attachments_args', 'hc_custom_bp_docs_get_doc_attachments_args', 10, 2 );

/**
 * Add meta field for numbered titles so that they
 * sort in order.
 *
 * @param int $doc_id ID of the document.
 */
function hc_custom_bp_docs_after_save( $doc_id ) {

	$post_title = get_the_title( $doc_id );

	preg_match_all( '!\d+!', $post_title, $matches );

	$number = implode( ' ', $matches[0] );

	if ( is_numeric( $number ) ) {
		update_post_meta( $doc_id, 'bp_docs_orderby', $number );
	} else {
		update_post_meta( $doc_id, 'bp_docs_orderby', 0 );
	}
}

add_action( 'bp_docs_after_save', 'hc_custom_bp_docs_after_save' );

/**
 * Change the query to include sort order for titles with numbers.
 *
 * @param array  $query_args Array of the args passed wo BP_Docs_Query.
 * @param object $bp_docs_query Object of the current query.
 */
function hc_custom_bp_docs_pre_query_args( $query_args, $bp_docs_query ) {

	$posted_orderby = isset( $_GET['orderby'] ) ? $_GET['orderby'] : '';

	if ( empty( $posted_orderby ) ) {
		$query_args['orderby']  = 'meta_value_num title';
		$query_args['meta_key'] = 'bp_docs_orderby';
	}

	return $query_args;
}

add_filter( 'bp_docs_pre_query_args', 'hc_custom_bp_docs_pre_query_args', 10, 2 );

/**
 * Find out what the groups default orderby is or set the default.
 *
 * @param int $group_id The group id.
 */
function hc_custom_bp_group_get_orderby( $group_id = false ) {
	global $groups_template;

	if ( ! $group_id ) {
		$bp = buddypress();

		if ( isset( $bp->groups->current_group->id ) ) {
			// Default to the current group first.
			$group_id = $bp->groups->current_group->id;
		} elseif ( isset( $groups_template->group->id ) ) {
			// Then see if we're in the loop.
			$group_id = $groups_template->group->id;
		} else {
			return false;
		}
	}

	$orderby_default = groups_get_groupmeta( $group_id, 'bp_docs_orderby_default' );

	// When 'orderby_default' is not set, fall back to a default value.
	if ( ! $orderby_default ) {
		$orderby_default = 'title';
	}

	return $orderby_default;
}

/**
 * Output the 'checked' value, if needed, for a given sort order on the group admin screens.
 *
 * @param string      $setting The setting you want to check against ('members',
 *                             'mods', or 'admins').
 * @param object|bool $group   Optional. Group object. Default: current group in loop.
 */
function hc_custom_bp_group_show_orderby_default_setting( $setting, $group = false ) {
	$group_id = isset( $group->id ) ? $group->id : false;

	$orderby_status = hc_custom_bp_group_get_orderby( $group_id );

	if ( $setting == $orderby_status ) {
		echo ' checked="checked"';
	}
}

/**
 * When the Docs sort settings are updated save the custom meta field.
 *
 * @param int $group_id The group id.
 */
function hc_custom_groups_settings_updated( $group_id ) {
	$group_docs_orderby = isset( $_POST['group-docs-orderby'] ) ? $_POST['group-docs-orderby'] : '';
	$group_docs_toggle  = isset( $_POST['group-docs-toggle'] ) ? $_POST['group-docs-toggle'] : '';

	if ( ! empty( $group_docs_orderby ) ) {
		groups_update_groupmeta( $group_id, 'bp_docs_orderby_default', $group_docs_orderby );
	}

	if ( ! empty ( $group_docs_toggle) ) {
		groups_update_groupmeta( $group_id, 'bp_docs_toggle_default', $group_docs_toggle );
	}

}

add_action( 'groups_settings_updated', 'hc_custom_groups_settings_updated' );

add_filter( 'bp_docs_allow_comment_section', '__return_true', 999 );

/**
 * Update post meta for folders.
 *
 * @param int    $post_id The post id.
 * @param object $post The post object.
 */
function hc_custom_buddypress_docs_save_post( $post_id, $post ) {

	if ( 'bp_docs_folder' === $post->post_type ) {

		$post_title = $post->post_title;
		$folder_id  = $post->ID;

		preg_match_all( '!\d+!', $post_title, $matches );

		$number = implode( ' ', $matches[0] );

		if ( is_numeric( $number ) ) {
			update_post_meta( $folder_id, 'bp_docs_orderby', $number );
		} else {
			update_post_meta( $folder_id, 'bp_docs_orderby', 0 );
		}
	}

}

add_action( 'save_post', 'hc_custom_buddypress_docs_save_post', 10, 2 );

/**
 * Sort numbered folder titles correctly.
 *
 * @param object $query The queried object.
 */
function hc_custom_pre_get_posts( $query ) {
	if ( 'bp_docs_folder' === $query->get( 'post_type' ) ) {
		if ( bp_docs_is_bp_docs_page() ) {
			$query->set( 'orderby', 'meta_value_num title' );
			$query->set( 'meta_key', 'bp_docs_orderby' );
		}
	}

	if ( 'attachment' === $query->get( 'post_type' ) ) {
		if ( bp_docs_is_bp_docs_page() ) {
			$query->set( 'posts_per_page', -1 );
		}
	}

	return $query;
}

add_action( 'pre_get_posts', 'hc_custom_pre_get_posts' );

/**
 * Echo the correct class according to the group settings.
 *
 */
function hc_custom_bp_docs_toggleable_open_or_closed_class() {
	global $groups_template;

	$bp = buddypress();

    if ( isset( $bp->groups->current_group->id ) ) {
            // Default to the current group first.
            $group_id = $bp->groups->current_group->id;
    } elseif ( isset( $groups_template->group->id ) ) {
            // Then see if we're in the loop.
            $group_id = $groups_template->group->id;
    } else {
            return false;
    }

    $toggle = ! empty( groups_get_groupmeta( $group_id, 'bp_docs_toggle_default' ) ) ? groups_get_groupmeta( $group_id, 'bp_docs_toggle_default' ) : 'toggle-closed';

	echo $toggle;
}


/**
 * Output the 'checked' value, if needed, for a given html class.
 *
 * @param string      $setting The setting you want to check against ('members',
 *                             'mods', or 'admins').
 * @param object|bool $group   Optional. Group object. Default: current group in loop.
 */
function hc_custom_bp_group_docs_toggle_default_setting( $setting, $group = false ) {
        $group_id = isset( $group->id ) ? $group->id : false;

        $toggle_status = hc_custom_bp_group_get_toggle( $group_id );

        if ( $setting == $toggle_status ) {
                echo ' checked="checked"';
        }
}

/**
 * Find out what the groups default toggle is or set the default.
 *
 * @param int $group_id The group id.
 */
function hc_custom_bp_group_get_toggle( $group_id = false ) {
        global $groups_template;

        if ( ! $group_id ) {
                $bp = buddypress();

                if ( isset( $bp->groups->current_group->id ) ) {
                        // Default to the current group first.
                        $group_id = $bp->groups->current_group->id;
                } elseif ( isset( $groups_template->group->id ) ) {
                        // Then see if we're in the loop.
                        $group_id = $groups_template->group->id;
                } else {
                        return false;
                }
        }

        $toggle_default = groups_get_groupmeta( $group_id, 'bp_docs_toggle_default' );

        // When 'orderby_default' is not set, fall back to a default value.
        if ( ! $toggle_default ) {
                $toggle_default = 'off';
        }

        return $toggle_default;
}


 add_action( 'bp_docs_before_tags_meta_box', 'hc_custom_remove_bp_docs_folders_meta_box' , 0 );


/**
 * Remove buddypress-docs version of the folder metabox.
 *
 */
function hc_custom_remove_bp_docs_folders_meta_box() {
		remove_action( 'bp_docs_before_tags_meta_box', 'bp_docs_folders_meta_box' );
}

	add_action( 'bp_docs_before_tags_meta_box', 'hc_custom_bp_docs_folders_meta_box' );

/**
 * Add the meta box to the edit page.
 *
 */
function hc_custom_bp_docs_folders_meta_box() {

        $doc_id = get_the_ID();
        $associated_group_id = bp_is_active( 'groups' ) ? bp_docs_get_associated_group_id( $doc_id ) : 0;

        if ( ! $associated_group_id && isset( $_GET['group'] ) ) {
                $group_id = BP_Groups_Group::get_id_from_slug( urldecode( $_GET['group'] ) );
                if ( current_user_can( 'bp_docs_associate_with_group', $group_id ) ) {
                        $associated_group_id = $group_id;
                }
        }

        // On the Create screen, respect the 'folder' $_GET param
		if ( bp_docs_is_doc_create() ) {
			$folder_id = bp_docs_get_current_folder_id();
		} else {
		$folder_id = bp_docs_get_doc_folder( $doc_id );
		}

	?>

	<div id="doc-folders" class="doc-meta-box">
		<div class="toggleable <?php hc_custom_bp_docs_toggleable_open_or_closed_class() ?>">
			<p id="folders-toggle-edit" class="toggle-switch">
				<span class="hide-if-js toggle-link-no-js"><?php _e( 'Folders', 'bp-docs' ) ?></span>
				<a class="hide-if-no-js toggle-link" id="folders-toggle-link" href="#"><span class="show-pane plus-or-minus"></span><span class="toggle-title"><?php _e( 'Folders', 'bp-docs' ) ?></span></a>
			</p>

			<div class="toggle-content">
				<table class="toggle-table" id="toggle-table-folders">
					<tr>
						<td class="desc-column">
							<label for="bp_docs_tag"><?php _e( 'Select a folder for this Doc.', 'bp-docs' ) ?></label>
						</td>

						<td>
							<div class="existing-or-new-selector">
								<input type="radio" name="existing-or-new-folder" id="use-existing-folder" value="existing" checked="checked" />
								<label for="use-existing-folder" class="radio-label"><?php _e( 'Use an existing folder', 'bp-docs' ) ?></label><br />
								<div class="selector-content">
									<?php bp_docs_folder_selector( array(
										'name'     => 'bp-docs-folder',
										'id'       => 'bp-docs-folder',
										'group_id' => $associated_group_id,
										'selected' => $folder_id,
									) ) ?>
								</div>
							</div>

							<div class="existing-or-new-selector" id="new-folder-block">
								<input type="radio" name="existing-or-new-folder" id="create-new-folder" value="new" />
								<label for="create-new-folder" class="radio-label"><?php _e( 'Create a new folder', 'bp-docs' ) ?></label>
								<div class="selector-content">

									<?php bp_docs_create_new_folder_markup( array(
										'group_id' => $associated_group_id,
										'selected' => $associated_group_id,
									) ) ?>
								</div><!-- .selector-content -->
							</div>
						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>

	<?php
}

/**
 * Prevent duplicate and delayed buddypress notifications from showing.
 *
 * This addresses @link https://github.com/MESH-Research/commons/issues/77
 *
 * This function is not called directly. It is called through the
 * 'bp_core_render_message' action, which occurs immediately after a buddypress
 * notification has been displayed.
 * 
 * @see buddypress/bp-core/bp-core-functions.php bp_core_render_message()
 *
 * @author Mike Thicke
 *
 * @global $bp The BuddyPress object.
 */
function hcommons_prevent_bp_message_duplicates() {
	global $bp;
	$bp->template_message = null; //Prevent message from being shown twice.

	// Prevent message from being shown on next page load.
	@setcookie( 'bp-message', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
	@setcookie( 'bp-message-type', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
}
add_action( 'bp_core_render_message', 'hcommons_prevent_bp_message_duplicates', 10 );

/**
 * Ensure that when a doc is edited or created through a POST request, it is returned as
 * the current doc, or if it is an autosave / not in a group, return null.
 *
 * This addresses @link
 * https://github.com/MESH-Research/hc-admin-docs-support/issues/202
 *
 * There seems to be a caching issue with buddypress-docs returning the wrong
 * doc in the functions.php::bp_docs_get_current_doc() function. This results in
 * the user not having permission to save or create the doc becuase the returned
 * doc might be from a group in which they are not a member.
 *
 * @see functions.php::bp_docs_get_current_doc() for the 'bp_docs_get_current_doc' filter.
 * @see component.php::catch_page_load() for the failed permission check that results.
 *
 * @author Mike Thicke
 *
 * @param  $current_doc The doc returned by the bp_docs_get_current_doc() function.
 */
function hcommons_correct_bp_docs_get_current_doc( $current_doc ) {
	if ( empty( $_POST['doc-edit-submit'] ) && empty( $_POST['doc-edit-submit-continue'] ) ) {
		return $current_doc;
	}

	if ( empty( $_POST['doc_id'] ) ) {
		return $current_doc;
	}

	$correct_current_doc = get_post( intval( $_POST['doc_id'] ) );
	if ( bp_docs_get_post_type_name() === $correct_current_doc->post_type ) {
		$group_id = bp_docs_get_associated_group_id( $correct_current_doc->ID, $correct_current_doc );
		if ( ! $group_id ) {
			return null;
		}
		return $correct_current_doc;
	}

	return $current_doc;
}
add_filter( 'bp_docs_get_current_doc', 'hcommons_correct_bp_docs_get_current_doc', 10, 1 );

/**
 * Ensure that when a doc is created or edited through a POST request, the
 * associated group is set as the associated group in the POST request.
 *
 * This addresses @link
 * https://github.com/MESH-Research/hc-admin-docs-support/issues/202
 *
 * When a doc is created through a POST request, bp_get_current_group_id()
 * sometimes (?) returns 0 rather than the correct group.
 *
 * @see buddypress/bp-groups/bp-groups-template.php::bp_get_current_group_id()
 * for the 'bp_get_current_group_id' filter.
 * 
 * @see integration-groups.php::bp_docs_groups_map_meta_caps() for the failed
 * permission check that results.
 *
 * @param int    $current_group_id ID of the current group
 * @param object $current_group    Instance holding the current group.
 */
function hcommons_correct_bp_get_current_group_id( $current_group_id, $current_group ) {
	if ( empty( $_POST['doc-edit-submit'] ) && empty( $_POST['doc-edit-submit-continue'] ) ) {
		return $current_group_id;
	}
	
	if ( empty( $_POST['associated_group_id'] ) ) {
		return $current_group_id;
	}

	return intval( $_POST['associated_group_id'] );
}
add_filter( 'bp_get_current_group_id', 'hcommons_correct_bp_get_current_group_id', 10, 2 );

/**
 * Resolve to no current doc when a brand-new doc is being saved.
 *
 * This addresses @link
 * https://github.com/MESH-Research/knowledge-commons-wordpress/issues/118
 *
 * On /docs/create the main query is the bp_doc post-type archive, so the
 * global $post holds the first doc of the archive listing. Since
 * buddypress-docs 2.2.5, BP_Docs_Component::catch_page_load() treats
 * whatever bp_docs_get_current_doc() returns at save time as the doc being
 * edited and demands the matching 'bp_docs_edit_{ID}' nonce — which the
 * create form never contained, because at render time BuddyPress theme
 * compatibility has already reset the globals to a dummy post with ID 0. The
 * result is that every create save dies in wp_nonce_ays() with "The link you
 * followed has expired."
 *
 * A create save (no posted doc ID) has no current doc by definition, so
 * return null and let the archive's first post stop masquerading as one.
 * Renders and genuine edit saves are left untouched.
 *
 * @param WP_Post|null $current_doc The doc detected by bp_docs_get_current_doc().
 * @return WP_Post|null Null on a create save, the detected doc otherwise.
 */
function hc_custom_bp_docs_create_save_current_doc( $current_doc ) {
	// Only act on doc save requests.
	if ( empty( $_POST['doc-edit-submit'] ) && empty( $_POST['doc-edit-submit-continue'] ) ) {
		return $current_doc;
	}

	// A posted doc ID means this is an edit save; leave it alone.
	if ( ! empty( $_POST['doc_id'] ) || ! empty( $_POST['doc-id'] ) ) {
		return $current_doc;
	}

	// A create save has no current doc.
	return null;
}
add_filter( 'bp_docs_get_current_doc', 'hc_custom_bp_docs_create_save_current_doc', 20, 1 );

function hcommons_restricted_comment_terms_doc_fallback( $terms, $term_query ) {
	if (
		isset( $term_query->query_vars['taxonomy'] ) && 
		is_array( $term_query->query_vars['taxonomy'] ) &&
		count( $term_query->query_vars['taxonomy'] ) > 0 &&
		$term_query->query_vars['taxonomy'][0] === 'bp_docs_comment_access' 
	) {
		if ( array_key_exists( 'slug', $term_query->query_vars ) ) {
			if ( in_array( 'default-term-query-in-progress', $term_query->query_vars['slug'] ) ) {
				return;
			} else {
				$term_query_copy = clone( $term_query );
				if ( ! is_array( $term_query->query_vars['slug'] ) ) {
					$term_query->query_vars['slug'] = [];
				}
				$term_query->query_vars['slug'][] = 'default-term-query-in-progress';
				$term_query->get_terms();
				if ( true ) {
					$slugs = $term_query_copy->query_vars['slug'];
					foreach ( $slugs as $slug ) {
						$doc_slug = str_replace( 'comment_', '', $slug );
						if ( $doc_slug != $slug ) {
							$term_query_copy->query_vars['slug'][] = $doc_slug;
						}
					}
					$term_query_copy->query_vars['taxonomy'][0] = 'bp_docs_access';
					$term_query_copy->get_terms();
					unset( $term_query_copy->query_vars['slug']['default-term-query-in-progress'] );
					return $term_query_copy->terms;
				}
			}
		}
	}
	return null;
}
add_action( 'terms_pre_query', 'hcommons_restricted_comment_terms_doc_fallback', 10, 2 );

/**
 * Get the URL of a group's Docs list.
 *
 * This addresses @link https://github.com/MESH-Research/knowledge-commons-wordpress/issues/101
 *
 * The upstream tabs-legacy.php template fails to echo the return value of
 * esc_url( bp_get_group_url( ... ) ), leaving a relative href="docs" that
 * resolves to /groups/{slug}/docs/docs and is then treated as a doc-slug
 * lookup, which can land on another group's doc. This helper always returns
 * an absolute URL for the requested group.
 *
 * @param object|int|null $group Group object or ID. Defaults to the current group.
 * @return string The group's Docs URL, or an empty string if unavailable.
 */
function hc_custom_get_group_docs_url( $group = null ) {
	if ( ! function_exists( 'bp_docs_get_group_docs_url' ) ) {
		return '';
	}

	if ( ! $group && function_exists( 'groups_get_current_group' ) ) {
		$group = groups_get_current_group();
	}

	if ( ! $group ) {
		return '';
	}

	$url = bp_docs_get_group_docs_url( $group );

	return $url ? $url : '';
}

/**
 * Build a "back to the group's Docs" tab for a single doc.
 *
 * Single docs are viewed at the global /docs/{slug} URL, outside the group
 * context, so the docs tabs offer no way back to the owning group's docs
 * list. This helper resolves the doc's associated group so the tabs template
 * can render a link back to it. Hidden groups are not revealed to
 * non-members.
 *
 * @param int $doc_id ID of the doc.
 * @return array|null Array with 'url' and 'label' keys, or null when the doc
 *                    has no (visible) associated group.
 */
function hc_custom_get_doc_group_docs_tab( $doc_id ) {
	if ( ! $doc_id || ! function_exists( 'bp_docs_get_associated_group_id' ) ) {
		return null;
	}

	$group_id = (int) bp_docs_get_associated_group_id( $doc_id );

	if ( ! $group_id ) {
		return null;
	}

	$group = groups_get_group( array( 'group_id' => $group_id ) );

	if ( empty( $group->id ) || empty( $group->name ) ) {
		return null;
	}

	// Do not reveal hidden groups to non-members.
	if ( isset( $group->status ) && 'hidden' === $group->status ) {
		$can_moderate = function_exists( 'bp_current_user_can' ) && bp_current_user_can( 'bp_moderate' );

		if ( ! $can_moderate && ! groups_is_user_member( bp_loggedin_user_id(), $group_id ) ) {
			return null;
		}
	}

	$url = hc_custom_get_group_docs_url( $group );

	if ( ! $url ) {
		return null;
	}

	return array(
		'url'   => $url,
		/* translators: %s: group name */
		'label' => sprintf( __( "%s's Docs", 'hc-custom' ), $group->name ),
	);
}

/**
 * Replace the buddypress-docs legacy tabs template with a corrected copy.
 *
 * The hc-custom copy fixes the un-echoed group docs URL and adds a link back
 * to the owning group's docs list when viewing a single doc. A template
 * provided by the theme (anything outside buddypress-docs) is left alone.
 *
 * @param string $template_path Located template path.
 * @param string $template      Requested template file name.
 * @return string Template path to load.
 */
function hc_custom_bp_docs_tabs_template( $template_path, $template ) {
	if ( 'tabs-legacy.php' !== $template ) {
		return $template_path;
	}

	// Respect overrides located outside the buddypress-docs plugin.
	if ( false === strpos( (string) $template_path, 'buddypress-docs' ) ) {
		return $template_path;
	}

	$override = trailingslashit( __DIR__ ) . 'templates/docs/tabs-legacy.php';

	if ( file_exists( $override ) ) {
		return $override;
	}

	return $template_path;
}
add_filter( 'bp_docs_locate_template', 'hc_custom_bp_docs_tabs_template', 10, 2 );

/**
 * Enqueue buddypress-docs js
 */
function enqueue_buddypress_docs_js() {
	wp_enqueue_script( 'hc-buddypress-docs-js', trailingslashit( plugins_url() ) . 'hc-custom/includes/js/buddypress-docs.js', array( 'jquery' ) );
}
add_action( 'wp_enqueue_scripts', 'enqueue_buddypress_docs_js', 10, 0 );