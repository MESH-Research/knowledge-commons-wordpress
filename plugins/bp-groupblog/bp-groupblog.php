<?php

define ( 'BP_GROUPBLOG_IS_INSTALLED', 1 );
define ( 'BP_GROUPBLOG_VERSION', '1.9.2' );

// Define default roles
if ( !defined( 'BP_GROUPBLOG_DEFAULT_ADMIN_ROLE' ) )
	define ( 'BP_GROUPBLOG_DEFAULT_ADMIN_ROLE', 'administrator' );
if ( !defined( 'BP_GROUPBLOG_DEFAULT_MOD_ROLE' ) )
	define ( 'BP_GROUPBLOG_DEFAULT_MOD_ROLE', 'editor' );
if ( !defined( 'BP_GROUPBLOG_DEFAULT_MEMBER_ROLE' ) )
	define ( 'BP_GROUPBLOG_DEFAULT_MEMBER_ROLE', 'author' );

// Base groupblog component slug
if ( !defined( 'BP_GROUPBLOG_SLUG' ) )
	define ( 'BP_GROUPBLOG_SLUG', 'group-blog' );

// Setup the groupblog theme directory
//register_theme_directory( WP_PLUGIN_DIR . '/bp-groupblog/themes' );

function bp_groupblog_setup() {
	global $wpdb;

	// Set up the array of potential defaults
	$groupblog_blogdefaults = array(
		'theme' => 'bp-default|bp-groupblog',
		'page_template_layout' => 'magazine',
		'delete_blogroll_links' => '1',
		'default_cat_name' => 'Uncategorized',
		'default_link_cat' => 'Links',
		'delete_first_post' => 0,
		'delete_first_comment' => 0,
		'allowdashes'=>0,
		'allowunderscores' => 0,
		'allownumeric' => 0,
		'minlength' => 4,
		'redirectblog' => 0,
		'deep_group_integration' => 0,
		'pagetitle' => 'Blog'
	);
 	// Add a site option so that we'll know set up ran
	add_site_option( 'bp_groupblog_blog_defaults_setup', 1 );
	add_site_option( 'bp_groupblog_blog_defaults_options', $groupblog_blogdefaults);
}

register_activation_hook( __FILE__, 'bp_groupblog_setup' );

/**
 * Require the necessary files. Wait until BP is finished loading, so we have access to everything
 *
 * @package BP Groupblog
 * @since 1.6
 */
function bp_groupblog_includes() {
	require ( WP_PLUGIN_DIR . '/bp-groupblog/bp-groupblog-admin.php' );
	require ( WP_PLUGIN_DIR . '/bp-groupblog/bp-groupblog-cssjs.php' );
	require ( WP_PLUGIN_DIR . '/bp-groupblog/bp-groupblog-classes.php' );
	require ( WP_PLUGIN_DIR . '/bp-groupblog/bp-groupblog-templatetags.php' );
}

// This file is needed earlier in BP 1.2.x, so we load it in the global scope (ugh)
// Require the abstraction file for earlier versions of BP
$bp_version = defined( BP_VERSION ) ? BP_VERSION : '1.2';
if ( version_compare( $bp_version, '1.3', '<' ) ) {
	require_once( WP_PLUGIN_DIR . '/bp-groupblog/1.5-abstraction.php' );
	bp_groupblog_includes();
} else {
	add_action( 'bp_loaded', 'bp_groupblog_includes' );
}

/**
 * Add language support.
 */
if ( file_exists( WP_PLUGIN_DIR . '/bp-groupblog/languages/groupblog-' . get_locale() . '.mo' ) )
	load_plugin_textdomain( 'groupblog', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

/**
 * bp_groupblog_setup_globals()
 */
function bp_groupblog_setup_globals() {
	global $bp, $wpdb;

	$bp->groupblog = new stdClass;
	$bp->groupblog->image_base = WP_PLUGIN_DIR . '/bp-groupblog/groupblog/images';
	$bp->groupblog->slug = BP_GROUPBLOG_SLUG;
	$bp->groupblog->default_admin_role = BP_GROUPBLOG_DEFAULT_ADMIN_ROLE;
	$bp->groupblog->default_mod_role = BP_GROUPBLOG_DEFAULT_MOD_ROLE;
	$bp->groupblog->default_member_role = BP_GROUPBLOG_DEFAULT_MEMBER_ROLE;

}
add_action( 'bp_setup_globals', 'bp_groupblog_setup_globals' );

/**
 * bp_groupblog_setup_nav()
 */
function bp_groupblog_setup_nav() {
	global $bp, $current_blog;

	if ( bp_is_group() ) {

		$bp->groups->current_group->is_group_visible_to_member = ( 'public' == $bp->groups->current_group->status || groups_is_user_member( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ? true : false;

		$group_link = bp_get_group_permalink( groups_get_current_group() );

		$checks = get_site_option('bp_groupblog_blog_defaults_options');

		if ( !$checks['deep_group_integration'] ) {

			$parent_slug = bp_get_current_group_slug();

			if (

				// existing groupblog logic
				bp_groupblog_is_blog_enabled( $bp->groups->current_group->id )

				OR

				// mahype's fixes for the non-appearance of the groupblog tab
				// with the addition of a check for the array key to prevent PHP notices.
				(
					isset( $_POST['groupblog-create-new'] ) AND
					$_POST['groupblog-create-new'] == 'yes'
				)

			) {

				// add a filter so plugins can change the name
				$name = __( 'Blog', 'groupblog' );
				$name = apply_filters( 'bp_groupblog_subnav_item_name', $name );

				// add a filter so plugins can change the slug
				$slug = apply_filters( 'bp_groupblog_subnav_item_slug', 'blog' );

				bp_core_new_subnav_item(
					array(
						'name' => $name,
						'slug' => $slug,
						'parent_url' => $group_link,
						'parent_slug' => $parent_slug,
						'screen_function' => 'groupblog_screen_blog',
						'position' => 32,
						'item_css_id' => 'nav-group-blog'
					)
				);
			}
		}
	}
}
add_action( 'bp_setup_nav', 'bp_groupblog_setup_nav' );

/**
 * groupblog_edit_settings()
 *
 * Save the blog-settings accessible only by the group admin or mod.
 *
 * Since version 1.6, this function has been called directly by
 * BP_Groupblog_Extension::edit_screen_save()
 *
 * @package BP Groupblog
 */
function groupblog_edit_settings() {
	global $bp, $groupblog_blog_id, $errors, $filtered_results;

	$group_id = isset( $_POST['groupblog-group-id'] ) ? $_POST['groupblog-group-id'] : bp_get_current_group_id();

	if ( !bp_groupblog_blog_exists( $group_id ) ) {
		if ( isset( $_POST['groupblog-enable-blog'] ) ) {
		    if ( $_POST['groupblog-create-new'] == 'yes' ) {
			//Create a new blog and assign the blog id to the global $groupblog_blog_id
			if ( !bp_groupblog_validate_blog_signup() ) {
				$errors = $filtered_results['errors'];
				bp_core_add_message ( $errors );
				$group_id = '';
			}
		} else if ( $_POST['groupblog-create-new'] == 'no' ) {
		    // They're using an existing blog, so we try to assign that to $groupblog_blog_id
		    if ( !( $groupblog_blog_id = $_POST['groupblog-blogid'] ) ) {
			//They forgot to choose a blog, so send them back and make them do it!
				bp_core_add_message( __( 'Please choose one of your blogs from the drop-down menu.' . $group_id, 'groupblog' ), 'error' );
				if ( bp_is_action_variable( 'step', 0 ) ) {
					bp_core_redirect( trailingslashit( $bp->loggedin_user->domain . $bp->groups->slug . '/create/step/' . $bp->action_variables[1] ) );
				} else {
					bp_core_redirect( trailingslashit( $bp->root_domain . '/' . $bp->current_component . '/' . $bp->current_item . '/admin/group-blog' ) );
				}
		    }
		}
		}
	} else {
	    // They already have a blog associated with the group, we're just saving other settings
		$groupblog_blog_id = groups_get_groupmeta ( $bp->groups->current_group->id, 'groupblog_blog_id' );
	}

	// Get the necessary settings out of the $_POST global so that we can use them to set up
	// the blog
	$settings = array(
		'groupblog-enable-blog' => '',
		'groupblog-silent-add'  => '',
		'default-administrator' => '',
		'default-moderator'     => '',
		'default-member'        => '',
		'page_template_layout'  => ''
	);

	foreach( $settings as $setting => $val ) {
		if ( isset( $_POST[$setting] ) ) {
			$settings[$setting] = $_POST[$setting];
		}
	}

	if ( !groupblog_edit_base_settings( $settings['groupblog-enable-blog'], $settings['groupblog-silent-add'], $settings['default-administrator'], $settings['default-moderator'], $settings['default-member'], $settings['page_template_layout'], $group_id, $groupblog_blog_id ) ) {
		bp_core_add_message( __( 'There was an error creating your group blog, please try again.', 'groupblog' ), 'error' );
	} else {
		bp_core_add_message( __( 'Group details were successfully updated.', 'groupblog' ) );
	}

	do_action( 'groupblog_details_edited', $bp->groups->current_group->id );

	//bp_core_redirect( $bp->root_domain . '/' . $bp->current_component . '/' . $bp->current_item . '/admin/group-blog' );

}

/**
 * groupblog_edit_base_settings()
 *
 * Updates the groupmeta with the blog_id, default roles and if it is enabled or not.
 * Initiating member permissions loop on save - by Boone
 */
function groupblog_edit_base_settings( $groupblog_enable_blog, $groupblog_silent_add = NULL, $groupblog_default_admin_role, $groupblog_default_mod_role, $groupblog_default_member_role, $page_template_layout, $group_id, $groupblog_blog_id = NULL ) {
	global $bp;

	$group_id = (int)$group_id;

	if ( empty( $group_id ) )
		return false;

	$default_role_array = array( 'groupblog_default_admin_role' => $groupblog_default_admin_role, 'groupblog_default_mod_role' => $groupblog_default_mod_role, 'groupblog_default_member_role' => $groupblog_default_member_role );

	$update_users = false;

	foreach ( $default_role_array as $role_name => $role ) {
		$old_default_role = groups_get_groupmeta ( $group_id, $role_name );
		if ( $role != $old_default_role ) {
			$update_users = true;
			break;
		}
	}

	groups_update_groupmeta ( $group_id, 'groupblog_enable_blog', $groupblog_enable_blog );
	groups_update_groupmeta ( $group_id, 'groupblog_blog_id', $groupblog_blog_id );
	wp_cache_delete( $groupblog_blog_id, 'bp_groupblog_blog_group_ids' );

	groups_update_groupmeta ( $group_id, 'groupblog_silent_add', $groupblog_silent_add );

  	groups_update_groupmeta ( $group_id, 'groupblog_default_admin_role', $groupblog_default_admin_role );
	groups_update_groupmeta ( $group_id, 'groupblog_default_mod_role', $groupblog_default_mod_role );
	groups_update_groupmeta ( $group_id, 'groupblog_default_member_role', $groupblog_default_member_role );

	groups_update_groupmeta ( $group_id, 'page_template_layout', $page_template_layout );

	if ( $update_users ) {
		bp_groupblog_member_join( $group_id );
	}

	do_action( 'groupblog_details_updated', $group_id );

	return true;
}

/**
 * bp_groupblog_member_join( $group_id )
 *
 * Runs whenever member permissions are changed and saved - by Boone
 */
function bp_groupblog_member_join( $group_id ) {
	$params = array(
		'exclude_admins_mods'	=> 0,
		'per_page'		=> 10000,
		'group_id'		=> $group_id
	);

	if ( bp_group_has_members( $params ) ) {
		$blog_id = groups_get_groupmeta( $group_id, 'groupblog_blog_id' );
		$group   = groups_get_group( array( 'group_id' => $group_id ) );

		while ( bp_group_members() ) {
			bp_group_the_member();
			$user_id = bp_get_group_member_id();

			if ( $group->creator_id != $user_id )
  				bp_groupblog_upgrade_user( $user_id, $group_id, $blog_id );
		}
	}
}

/**
 * Gets a group's groupblog settings.
 *
 * @since 1.8.13
 *
 * @return array
 */
function bp_groupblog_get_group_settings( $group_id ) {
	$defaults = array(
		'groupblog_silent_add'          => false,
		'groupblog_default_member_role' => BP_GROUPBLOG_DEFAULT_MEMBER_ROLE,
		'groupblog_default_mod_role'    => BP_GROUPBLOG_DEFAULT_MOD_ROLE,
		'groupblog_default_admin_role'  => BP_GROUPBLOG_DEFAULT_ADMIN_ROLE,
		'groupblog_enable_blog'         => false,
		'groupblog_blog_id'             => null,
	);

	$r = array();
	foreach ( $defaults as $key => $default_value ) {
		$saved_value = groups_get_groupmeta( $group_id, $key, true );
		if ( '' === $saved_value ) {
			$r[ $key ] = $default_value;
		} else {
			$r[ $key ] = $saved_value;
		}
	}

	return $r;
}

/**
 * bp_groupblog_upgrade_user( $user_id, $group_id, $blog_id )
 *
 * Subscribes user in question to blog in question
 * This code was initially inspired by Burt Adsit re-interpreted by Boone
 */
function bp_groupblog_upgrade_user( $user_id, $group_id, $blog_id = false ) {
	global $bp;

	if ( !$blog_id )
		$blog_id = groups_get_groupmeta ( $group_id, 'groupblog_blog_id' );

	// If the group has no blog linked, get the heck out of here!
	if ( !$blog_id )
		return;

	$settings = bp_groupblog_get_group_settings( $group_id );
	if ( ! $settings['groupblog_silent_add'] ) {
		return;
	}

	// Set up some variables
	$groupblog_silent_add          = $settings['groupblog_silent_add'];
	$groupblog_default_member_role = $settings['groupblog_default_member_role'];
	$groupblog_default_mod_role    = $settings['groupblog_default_mod_role'];
	$groupblog_default_admin_role  = $settings['groupblog_default_admin_role'];
	$groupblog_creator_role        = 'admin';

	// get user's blog role
	$user_role = bp_groupblog_get_user_role( $user_id, false, $blog_id );

	// Get the current user's group status.
	if ( groups_is_user_admin( $user_id, $group_id ) ) {
		$user_group_status = 'admin';
	} else if ( groups_is_user_mod( $user_id, $group_id ) ) {
		$user_group_status = 'mod';
	} else if ( groups_is_user_member( $user_id, $group_id ) ) {
		$user_group_status = 'member';
	} else {
		return false;
	}

	// change user status based on promotion / demotion
	switch( bp_action_variable( 1 ) ) {
		case 'promote' :
			$user_group_status = bp_action_variable( 2 );
			break;

		case 'demote' :
		case 'unban' :
			$user_group_status = 'member';
			break;

		// we don't remove users from blogs at the moment
		// we give them the minimum role of 'subscriber'
		case 'ban' :
		case 'remove' :
			$user_group_status = 'subscriber';
			break;
	}

	// set the role
	switch ( $user_group_status ) {
		case 'admin' :
			$default_role = $groupblog_default_admin_role;
			break;

		case 'mod' :
			$default_role = $groupblog_default_mod_role;
			break;

		case 'subscriber' :
			$default_role = 'subscriber';
			break;

		case 'member' :
		default :
			$default_role = $groupblog_default_member_role;
			break;
	}

	if ( $user_role == $default_role && $groupblog_silent_add == true ) {
		return false;
	}

	if ( !$groupblog_silent_add ) {
		$default_role = 'subscriber';
	}

	add_user_to_blog( $blog_id, $user_id, $default_role );

	do_action( 'bp_groupblog_upgrade_user', $user_id, $user_role, $default_role );
}

/**
 * bp_groupblog_just_joined_group( $group_id, $user_id )
 *
 * Called when user joins group - by Boone
 */
function bp_groupblog_just_joined_group( $group_id, $user_id ) {
	bp_groupblog_upgrade_user( $user_id, $group_id );
}
add_action( 'groups_join_group', 'bp_groupblog_just_joined_group', 5, 2 );

/*
 * bp_groupblog_changed_status_group( $user_id, $group_id )
 *
 * Called when user changes status in the group
 *
 * Variables ($user_id, $group_id) are switched around for these hooks,
 * therefore we put these in a sepperate function.
 */
function bp_groupblog_changed_status_group( $user_id, $group_id ) {
	bp_groupblog_upgrade_user( $user_id, $group_id );
}
add_action( 'groups_promoted_member',     'bp_groupblog_changed_status_group', 10, 2 );
add_action( 'groups_demoted_member',      'bp_groupblog_changed_status_group', 10, 2 );
add_action( 'groups_unbanned_member',     'bp_groupblog_changed_status_group', 10, 2 );
add_action( 'groups_banned_member',       'bp_groupblog_changed_status_group', 10, 2 );
add_action( 'groups_removed_member',      'bp_groupblog_changed_status_group', 10, 2 );
add_action( 'groups_membership_accepted', 'bp_groupblog_changed_status_group', 10, 2 );
add_action( 'groups_accept_invite',       'bp_groupblog_changed_status_group', 10, 2 );


/**
 * bp_groupblog_remove_user( $group_id, $user_id = false )
 *
 * Called when user leaves.
 */
function bp_groupblog_remove_user( $group_id, $user_id = false ) {
	// Only modify site membership if the plugin is configured to do so.
	$settings = bp_groupblog_get_group_settings( $group_id );
	if ( ! $settings['groupblog_silent_add'] ) {
		return;
	}

	$blog_id = get_groupblog_blog_id( $group_id );

	if ( ! $user_id )
		$user_id = bp_loggedin_user_id();

	// Users with no existing role should not be modified.
	if ( ! is_user_member_of_blog( $user_id, $blog_id ) ) {
		return;
	}

	$user = new WP_User( $user_id );
	$user->for_blog( $blog_id );
	$user->set_role( 'subscriber' );
	wp_cache_delete( $user_id, 'users' );
}
add_action( 'groups_leave_group', 'bp_groupblog_remove_user', 10, 2 );

/**
 * bp_groupblog_get_user_role( $user_id, $user_login, $blog_id )
 *
 * Reworked function to retrieve the users current role - by Boone
 *
 * @param int $user_id The user ID
 * @param bool $user_login Deprecated. Don't use.
 * @param int $blog_id The blog ID
 * @return string The user's blog role
 */
function bp_groupblog_get_user_role( $user_id, $user_login = false, $blog_id ) {
	global $wpdb;

	// determine users role, if any, on this blog
	$roles = get_user_meta( $user_id, $wpdb->get_blog_prefix( $blog_id ) . 'capabilities', true );

	// this seems to be the only way to do this
	if ( isset( $roles['subscriber'] ) ) {
		$user_role = 'subscriber';
	} elseif ( isset( $roles['contributor'] ) ) {
		$user_role = 'contributor';
	} elseif ( isset( $roles['author'] ) ) {
		$user_role = 'author';
	} elseif ( isset( $roles['editor'] ) ) {
		$user_role = 'editor';
	} elseif ( isset( $roles['administrator'] ) ) {
		$user_role = 'administrator';
	} elseif ( is_super_admin( $user_id ) ) {
		$user_role = 'siteadmin';
	} else {
		$user_role = 'norole';
	}

	return $user_role;
}

/**
 * bp_groupblog_create_screen_save()
 *
 * Saves the information from the BP group blog creation step.
 * TO-DO: groupblog-edit-settings is more efficient, rewrite this to be more like that one.
 */

function bp_groupblog_create_screen_save() {
	global $bp;
	global $groupblog_blog_id, $groupblog_create_screen, $filtered_results;

	if ( bp_is_action_variable( 'step', 0 ) ) {
		$groupblog_create_screen = true;
	} else {
		$groupblog_create_screen = false;
	}

	// Set up some default roles
	$groupblog_default_admin_role  = isset( $_POST['default-administrator'] ) ? $_POST['default-administrator'] : BP_GROUPBLOG_DEFAULT_ADMIN_ROLE;
	$groupblog_default_mod_role    = isset( $_POST['default-moderator'] ) ? $_POST['default-moderator'] : BP_GROUPBLOG_DEFAULT_MOD_ROLE;
	$groupblog_default_member_role = isset( $_POST['default-member'] ) ? $_POST['default-member'] : BP_GROUPBLOG_DEFAULT_MEMBER_ROLE;

	// Set up some other values
	$groupblog_group_id	= isset( $_POST['group_id'] ) ? $_POST['group_id'] : bp_get_new_group_id();
	$silent_add 		= isset( $_POST['groupblog-silent-add'] ) ? $_POST['groupblog-silent-add'] : '';
	$page_template_layout	= isset( $_POST['page_template_layout'] ) ? $_POST['page_template_layout'] : '';
	$enable_group_blog	= isset( $_POST['groupblog-enable-blog'] ) ? $_POST['groupblog-enable-blog'] : '';

	if ( $_POST['groupblog-create-new'] == 'yes' ) {
	//Create a new blog and assign the blog id to the global $groupblog_blog_id
		if ( !$groupblog_blog_id = bp_groupblog_validate_blog_signup() ) {
			$errors = $filtered_results['errors'];
			bp_core_add_message ( $errors );
			$group_id = '';
		}
	} else if ( $_POST['groupblog-create-new'] == 'no' ) {
	    // They're using an existing blog, so we try to assign that to $groupblog_blog_id
	    if ( !( $groupblog_blog_id = $_POST['groupblog-blogid'] ) ) {
		//They forgot to choose a blog, so send them back and make them do it!
			bp_core_add_message( __( 'Please choose one of your blogs from the drop-down menu.' . $group_id, 'groupblog' ), 'error' );
			bp_core_redirect( trailingslashit( $bp->loggedin_user->domain . $bp->groups->slug . '/create/step/' . $bp->action_variables[1] ) );
		}
	} else {
	    // They already have a blog associated with the group, we're just saving other settings
		$groupblog_blog_id = groups_get_groupmeta ( $bp->groups->current_group->id, 'groupblog_blog_id' );
	}

	if ( !groupblog_edit_base_settings( $enable_group_blog, $silent_add, $groupblog_default_admin_role, $groupblog_default_mod_role, $groupblog_default_member_role, $page_template_layout, $groupblog_group_id, $groupblog_blog_id ) ) {
		bp_core_add_message( __( 'There was an error creating your group blog, please try again.', 'groupblog' ), 'error' );
		bp_core_redirect( trailingslashit( $bp->loggedin_user->domain . $bp->groups->slug . '/create/step/' . $bp->action_variables[1] ) );
	}
}

/**
 * bp_groupblog_show_blog_form( $blogname = '', $blog_title = '', $errors = '' )
 *
 * Displays the blog signup form and takes the privacy settings from the
 * group privacy settings, where "private & hidden" equal "private".
 */
function bp_groupblog_show_blog_form( $blogname = '', $blog_title = '', $errors = '' ) {
	global $bp, $groupblog_create_screen, $current_site;

	// Get the group id, which is fetched differently depending on whether this is a group
	// Create or Edit screen
	$group_id = bp_is_group_create() ? bp_get_new_group_id() : bp_get_current_group_id();

	$blog_id = get_groupblog_blog_id();

	?>

	<div id="blog-details-fields">

	<?php if ( !$groupblog_create_screen && $blog_id != '' ) : ?>
		<?php /* We're showing the admin form */ ?>
		<?php $blog_details = get_blog_details( get_groupblog_blog_id(), true ); ?>
		<label for="blog_title"><strong><?php _e( 'Blog Title:', 'groupblog' ) ?></strong></label>
		<?php if ( $errmsg = $errors->get_error_message('blog_title') ) { ?>
			<p class="error"><?php echo $errmsg ?></p>
		<?php } ?>
		<p><?php echo $blog_details->blogname; ?></p>
		<input name="blog_title" type="hidden" id="blog_title" value="<?php echo $blog_details->blogname; ?>" />

		<label for="blogname"><strong><?php _e( 'Blog Address:', 'groupblog' ) ?></strong></label>
		<?php if ( $errmsg = $errors->get_error_message('blogname') ) : ?>
			<p class="error"><?php echo $errmsg ?></p>
		<?php endif ?>

		<p><em><?php echo $blog_details->siteurl; ?> </em></p>
		<input name="blogname" type="hidden" id="blogname" value="<?php echo $blog_details->siteurl; ?>" maxlength="50" />

		<div id="uncouple-blog">
			<label for="uncouple"><?php printf( __( 'Uncouple the blog "%1$s" from the group "%2$s":', 'groupblog' ), $blog_details->blogname, $bp->groups->current_group->name ) ?></label>

			<p class="description"><?php printf( __( '<strong>Note:</strong> Uncoupling will remove the blog from your group&#8217;s navigation and prevent future synchronization of group members and blog authors, but it will not remove change blog permissions for any current member. Visit <a href="%1$s">the Users panel</a> if you&#8217;d like to remove users from the blog.', 'groupblog' ), $blog_details->siteurl . '/wp-admin/users.php' ) ?></p>

			<a class="button" href="<?php echo wp_nonce_url( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/group-blog/uncouple', 'groupblog-uncouple' ) ?>"><?php _e( 'Uncouple', 'groupblog' ); ?></a>

		</div>

		<?php $bp->groups->current_group->status == 'public' ? $group_public = '1' : $group_public = '0'; ?>
		<input type="hidden" id="blog_public" name="blog_public" value="<?php echo $group_public ?>" />
		<input type="hidden" id="groupblog_create_screen" name="groupblog_create_screen" value="<?php echo $groupblog_create_screen; ?>" />

	<?php else : ?>
		<?php /* Showing the create screen form */ ?>

		<p><?php _e( 'Choose either one of your existing blogs or create a new one all together with the details displayed below.', 'groupblog' ); ?><br /><?php _e('Take care as you can only choose once.  Later you may still disable or enable the blog, but your choice is set.', 'groupblog' ); ?></p>

		<p>
			<label for="groupblog-create-new-no"><input type="radio" value="no" name="groupblog-create-new" id="groupblog-create-new-no" /><span>&nbsp;<?php _e( 'Use one of your own available blogs:', 'groupblog' ); ?>&nbsp;</span>

			<?php $user_blogs = get_blogs_of_user( get_current_user_id() ) ?>

			<select name="groupblog-blogid" id="groupblog-blogid">
				<option value="0"><?php _e( 'choose a blog', 'groupblog' ) ?></option>
				<?php

				foreach( (array)$user_blogs as $user_blog ) {
					if ( !get_groupblog_group_id( $user_blog->userblog_id ) ) : ?>
						<option value="<?php echo $user_blog->userblog_id; ?>"><?php echo $user_blog->blogname; ?></option>
					<?php
					endif;
				} ?>
			</select>
			</label>
		</p>

		<p>
			<label for="groupblog-create-new-yes"><input type="radio" value="yes" name="groupblog-create-new" id="groupblog-create-new-yes" checked="checked" /><span>&nbsp;<?php _e( 'Or, create a new blog', 'groupblog' ); ?></label></span>
		</p>

		<ul id="groupblog-details">
			<li>
				<label class="groupblog-label" for="blog_title"><strong><?php _e( 'Blog Title:', 'groupblog' ) ?></strong></label>

				<?php if ( $errmsg = $errors->get_error_message('blog_title') ) : ?>
					<span class="error"><?php echo $errmsg ?></span>
				<?php endif ?>

				<?php $blog_title = isset( $_GET['invalid_name'] ) ? urldecode( $_GET['invalid_name'] ) : $bp->groups->current_group->name ?>

				<span class="gbd-value">
					<input name="blog_title" type="text" id="blog_title" value="<?php echo $blog_title ?>" />
				</span>
			</li>

			<li>
				<label class="groupblog-label" for="blogname"><strong><?php _e( 'Blog Address:', 'groupblog' ) ?></strong></label>
				<?php if ( $errmsg = $errors->get_error_message('blogname') ) : ?>
					<span class="error"><?php echo $errmsg ?></span>
				<?php endif ?>

				<?php $blog_address = isset( $_GET['invalid_address'] ) ? urldecode( $_GET['invalid_address'] ) : bp_groupblog_sanitize_blog_name( $bp->groups->current_group->slug ) ?>

				<?php
					// Don't suggest a subdomain if it's really long,
					// since subdomains longer than 63 chars won't work.
					if (strlen($blog_address > 50)) $blog_address = "";
				?>

				<?php if (is_subdomain_install()) : ?>
					<span class="gbd-value"><em>http://</em><input name="blogname" type="text" id="blogname" value="<?php echo $blog_address; ?>" maxlength="50" /><em><?php echo $current_site->domain . $current_site->path ?></em></span>
				<?php else : ?>
					<span class="gbd-value"><em>http://<?php echo $current_site->domain . $current_site->path ?></em><input name="blogname" type="text" id="blogname" value="<?php echo $blog_address; ?>" maxlength="50" /></span>
				<?php endif ?>

			</li>
		</ul>

		<?php $bp->groups->current_group->status == 'public' ? $group_public = '1' : $group_public = '0'; ?>
		<input type="hidden" id="blog_public" name="blog_public" value="<?php echo $group_public ?>" />
		<input type="hidden" id="groupblog_create_screen" name="groupblog_create_screen" value="<?php echo $groupblog_create_screen; ?>" />

	<?php endif ?>

	</div>
	<?php

	do_action( 'signup_blogform', $errors );
}

/**
 * bp_groupblog_validate_blog_form()
 *
 * This function validates that the blog does not exist already, illegal names, etc...
 */
function bp_groupblog_validate_blog_form() {

	$user = '';
	if ( is_user_logged_in() )
		$user = wp_get_current_user();

	$result =  wpmu_validate_blog_signup($_POST['blogname'], $_POST['blog_title'], $user);

	$errors = $result['errors'];

	// we only want to filter if there is an error
	if (!is_object($errors)){
		return $result;
	}


	$checks = get_site_option('bp_groupblog_blog_defaults_options');

	// create a new var to hold errors
	$newerrors = new WP_Error();

	// loop through the errors and look for the one we are concerned with
	foreach ($errors->errors as $key => $value) {
		// if the error is with the blog name, check to see which one
		if ($key == 'blogname'){

			foreach ($value as $subkey => $subvalue) {

				switch ($subvalue){
					case 'Only lowercase letters (a-z) and numbers are allowed.':

						$allowedchars = '';
						if ($checks['allowdashes']== 1) $allowedchars .= '-';
						if ($checks['allowunderscores'] == 1) $allowedchars .= '_';

						$allowed = '/[a-z0-9' . $allowedchars . ']+/';
						preg_match( $allowed, $result['blogname'], $maybe );
						if( $result['blogname'] != $maybe[0] ) {

							//still fails, so add an error to the object
							$newerrors->add('blogname', __("Only lowercase letters and numbers allowed", 'groupblog'));

						}
						break;
					case 'Site name must be at least 4 characters.':
						if( strlen( $result['blogname'] ) < $checks['minlength'] && !is_super_admin() )
						$newerrors->add('blogname',  __("Blog name must be at least " . $checks['minlength'] . " characters", 'groupblog'));
						break;
					case "Sorry, site names may not contain the character &#8220;_&#8221;!":
						if($checks['allowunderscores']!= 1) {
							$newerrors->add('blogname', __("Sorry, blog names may not contain the character '_'!", 'groupblog'));
						}
						break;
					case 'Sorry, site names must have letters too!':
						if($checks['allownumeric'] != 1){
							$newerrors->add('blogname', __("Sorry, blog names must have letters too!", 'groupblog'));
						}
						break;
					default:
						$newerrors->add('blogname', $subvalue);

				}// end switch

		}

		}
		else {
			//Add all other errors into the error object, but they're in sub-arrays, so loop through to get the right stuff.
			foreach ($value as $subkey => $subvalue) {
				$newerrors->add($key, $subvalue);
			}

		}

	}

	//unset the error object from the results & rest it with our new errors
	unset($result['errors']);
	$result['errors'] = $newerrors;

	return $result;

}

/**
 * Sanitizes a group name into a blog address, based on site settings
 *
 * @since 1.7
 * @param str $group_name
 * @return str $blog_address
 */
function bp_groupblog_sanitize_blog_name( $group_name = '' ) {
	$checks = get_site_option('bp_groupblog_blog_defaults_options');

	$baddies = array ();
	if ( $checks['allowdashes'] != '1' )
		$baddies[] = '-';
	if ( $checks['allowunderscores'] != '1' )
		$baddies[] = '_';

	$blog_address = str_replace ( $baddies, '', $group_name );

	return $blog_address;
}

/**
 * Catches and processes a groupblog uncoupling
 *
 * @since 1.7
 */
function bp_groupblog_process_uncouple() {
	if ( bp_is_group() && bp_is_current_action( 'admin' ) && bp_is_action_variable( 'group-blog', 0 ) && bp_is_action_variable( 'uncouple', 1 ) ) {
		check_admin_referer( 'groupblog-uncouple' );

		if ( !bp_group_is_admin() ) {
			bp_core_add_message( __( 'You must be a group admin to perform this action.', 'groupblog' ), 'error' );
			bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) );
		}

		$blog_id = get_groupblog_blog_id();

		// If groupblog is enabled, disable it
		groups_update_groupmeta( bp_get_current_group_id(), 'groupblog_enable_blog', 0 );

		// Unset the groupblog ID
		groups_update_groupmeta( bp_get_current_group_id(), 'groupblog_blog_id', '' );
		wp_cache_delete( $blog_id, 'bp_groupblog_blog_group_ids' );

		bp_core_add_message( __( 'Blog uncoupled.', 'groupblog' ) );

		// Redirect to the groupblog admin
		bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'admin/group-blog' );
	}
}
add_action( 'bp_actions', 'bp_groupblog_process_uncouple', 1 );

/**
 * bp_groupblog_signup_blog($blogname = '', $blog_title = '', $errors = '')
 *
 * This function is called from the template and initiates the blog creation.
 */
function bp_groupblog_signup_blog($blogname = '', $blog_title = '', $errors = '') {
	global $current_user, $current_site, $groupblog_create_screen;
	global $bp, $filtered_results;

	if ( ! is_wp_error($errors) ) {
		$errors = new WP_Error();
	}

	// allow definition of default variables
	$filtered_results = apply_filters('signup_blog_init', array('blogname' => $blogname, 'blog_title' => $blog_title, 'errors' => $errors ));
	$blogname = $filtered_results['blogname'];
	$blog_title = $filtered_results['blog_title'];
	$errors = $filtered_results['errors'];

	if ( !isset ( $groupblog_create_screen ) ) {
		$groupblog_create_screen = false;
	}

	// Get the group id, which is fetched differently depending on whether this is a group
	// Create or Edit screen
	$group_id = bp_is_group_create() ? bp_get_new_group_id() : bp_get_current_group_id();

	?>
	<h2><?php _e( 'Group Blog', 'groupblog' ) ?></h2>
	<?php

	if ( !$groupblog_create_screen ) { ?>
		<input type="hidden" name="stage" value="gimmeanotherblog" />
		<?php do_action( "signup_hidden_fields" ); ?>
	<?php } ?>

		<div class="checkbox">
			<label><input type="checkbox" name="groupblog-enable-blog" id="groupblog-enable-blog" value="1"<?php bp_groupblog_show_enabled( $group_id ) ?>/> <?php _e( 'Enable group blog', 'groupblog' ); ?></label>
		</div>

		<?php bp_groupblog_show_blog_form($blogname, $blog_title, $errors); ?>

		<br />

		<div id="groupblog-member-options">

			<h3><?php _e( 'Member Options', 'groupblog' ) ?></h3>

			<p><?php _e( 'Enable blog posting to allow adding of group members to the blog with the roles set below.', 'groupblog' ); ?><br /><?php _e( 'When disabled, all members will temporarily be set to subscribers, disabling posting.', 'groupblog' ); ?></p>

			<div class="checkbox">
				<label><input type="checkbox" name="groupblog-silent-add" id="groupblog-silent-add" value="1"<?php if ( bp_groupblog_silent_add( $group_id ) ) { ?> checked="checked"<?php } ?>/> <?php _e( 'Enable member blog posting', 'groupblog' ); ?></label>
			</div>

			<?php
			// Assign our default roles to variables.
			// If nothing has been saved in the groupmeta yet, then we assign our own defalt values.
			if ( !( $groupblog_default_admin_role = groups_get_groupmeta ( $bp->groups->current_group->id, 'groupblog_default_admin_role' ) ) ) {
				$groupblog_default_admin_role = $bp->groupblog->default_admin_role;
			}
			if ( !( $groupblog_default_mod_role = groups_get_groupmeta ( $bp->groups->current_group->id, 'groupblog_default_mod_role' ) ) ) {
				$groupblog_default_mod_role = $bp->groupblog->default_mod_role;
			}
			if ( !( $groupblog_default_member_role = groups_get_groupmeta ( $bp->groups->current_group->id, 'groupblog_default_member_role' ) ) ) {
				$groupblog_default_member_role = $bp->groupblog->default_member_role;
			}
			?>

			<label><strong><?php _e( 'Default Administrator Role:', 'groupblog' ); ?></strong></label>
			<input type="radio" <?php checked( $groupblog_default_admin_role, 'administrator' ) ?> value="administrator" name="default-administrator" /><span>&nbsp;<?php _e( 'Administrator', 'groupblog' ); ?>&nbsp;&nbsp;</span>
			<input type="radio" <?php checked( $groupblog_default_admin_role, 'editor' ) ?> value="editor" name="default-administrator" /><span>&nbsp;<?php _e( 'Editor', 'groupblog' ); ?>&nbsp;&nbsp;</span>
			<input type="radio" <?php checked( $groupblog_default_admin_role, 'author' ) ?> value="author" name="default-administrator" /><span>&nbsp;<?php _e( 'Author', 'groupblog' ); ?>&nbsp;&nbsp;</span>
			<input type="radio" <?php checked( $groupblog_default_admin_role, 'contributor' ) ?> value="contributor" name="default-administrator" /><span>&nbsp;<?php _e( 'Contributor', 'groupblog' ); ?>&nbsp;&nbsp;</span>
			<input type="radio" <?php checked( $groupblog_default_admin_role, 'subscriber' ) ?> value="subscriber" name="default-administrator" /><span>&nbsp;<?php _e( 'Subscriber', 'groupblog' ); ?>&nbsp;&nbsp;</span>

			<label><strong><?php _e( 'Default Moderator Role:', 'groupblog' ); ?></strong></label>
			<input type="radio" <?php checked( $groupblog_default_mod_role, 'administrator' ) ?> value="administrator" name="default-moderator" /><span>&nbsp;<?php _e( 'Administrator', 'groupblog' ); ?>&nbsp;&nbsp;</span>
			<input type="radio" <?php checked( $groupblog_default_mod_role, 'editor' ) ?> value="editor" name="default-moderator" /><span>&nbsp;<?php _e( 'Editor', 'groupblog' ); ?>&nbsp;&nbsp;</span>
			<input type="radio" <?php checked( $groupblog_default_mod_role, 'author' ) ?> value="author" name="default-moderator" /><span>&nbsp;<?php _e( 'Author', 'groupblog' ); ?>&nbsp;&nbsp;</span>
			<input type="radio" <?php checked( $groupblog_default_mod_role, 'contributor' ) ?> value="contributor" name="default-moderator" /><span>&nbsp;<?php _e( 'Contributor', 'groupblog' ); ?>&nbsp;&nbsp;</span>
			<input type="radio" <?php checked( $groupblog_default_mod_role, 'subscriber' ) ?> value="subscriber" name="default-moderator" /><span>&nbsp;<?php _e( 'Subscriber', 'groupblog' ); ?>&nbsp;&nbsp;</span>

			<label><strong><?php _e( 'Default Member Role:', 'groupblog' ); ?></strong></label>
			<input type="radio" <?php checked( $groupblog_default_member_role, 'administrator' ) ?> value="administrator" name="default-member" /><span>&nbsp;<?php _e( 'Administrator', 'groupblog' ); ?>&nbsp;&nbsp;</span>
			<input type="radio" <?php checked( $groupblog_default_member_role, 'editor' ) ?> value="editor" name="default-member" /><span>&nbsp;<?php _e( 'Editor', 'groupblog' ); ?>&nbsp;&nbsp;</span>
			<input type="radio" <?php checked( $groupblog_default_member_role, 'author' ) ?> value="author" name="default-member" /><span>&nbsp;<?php _e( 'Author', 'groupblog' ); ?>&nbsp;&nbsp;</span>
			<input type="radio" <?php checked( $groupblog_default_member_role, 'contributor' ) ?> value="contributor" name="default-member" /><span>&nbsp;<?php _e( 'Contributor', 'groupblog' ); ?>&nbsp;&nbsp;</span>
			<input type="radio" <?php checked( $groupblog_default_member_role, 'subscriber' ) ?> value="subscriber" name="default-member" /><span>&nbsp;<?php _e( 'Subscriber', 'groupblog' ); ?>&nbsp;&nbsp;</span>

			<div id="groupblog-member-roles">
				<label><strong><?php _e( 'A bit about WordPress member roles:', 'groupblog' ); ?></strong></label>
				<ul id="groupblog-members">
					<li><?php _e( 'Administrator', 'groupblog' ); ?> - <?php _e( "Somebody who has access to all the administration features.", 'groupblog' ); ?></li>
					<li><?php _e( 'Editor', 'groupblog' ); ?> - <?php _e( "Somebody who can publish posts, manage posts as well as manage other people's posts, etc.", 'groupblog' ); ?></li>
					<li><?php _e( 'Author', 'groupblog' ); ?> - <?php _e( "Somebody who can publish and manage their own posts.", 'groupblog' ); ?></li>
					<li><?php _e( 'Contributor', 'groupblog' ); ?> - <?php _e( "Somebody who can write and manage their posts but not publish posts.", 'groupblog' ); ?></li>
					<li><?php _e( 'Subscriber', 'groupblog' ); ?> - <?php _e( "Somebody who can read comments/comment/receive news letters, etc.", 'groupblog' ); ?></li>
				</ul>
			</div>

		</div>

		<br />

		<?php if ( bp_groupblog_allow_group_admin_layout() )	: ?>

			<?php
				if ( !( $page_template_layout = groups_get_groupmeta ( $bp->groups->current_group->id, 'page_template_layout' ) ) )
					$page_template_layout = groupblog_get_page_template_layout();
			?>

			<div id="groupblog-layout-options">

				<h3><?php _e( 'Select Layout', 'groupblog' ) ?></h3>

				<p class="enabled"><?php _e( 'Please select a Layout which you would like to use for your Group Blog.', 'groupblog' ) ?></p>

				<table class="enabled" id="availablethemes" cellspacing="0" cellpadding="0">
					<tbody>
					<tr>
						<td class="available-theme top left">
							<?php echo '<img src="' . WP_PLUGIN_URL . '/bp-groupblog/inc/i/screenshot-mag.png">';?>
							<br /><br />
							<input<?php if ( !bp_groupblog_is_blog_enabled( $group_id ) ) { ?> disabled="true"<?php } ?> name="page_template_layout" id="page_template_layout"  value="magazine" type="radio" <?php if ($page_template_layout == 'magazine') echo 'checked="checked"'; ?> /><label style="display:inline;"> <?php _e( 'Magazine', 'groupblog' ) ?></label>
							<p class="description"><?php _e( 'Balanced template for groups with diverse postings.', 'groupblog' ) ?></p>
						</td>
						<td class="available-theme top">
							<?php echo '<img src="' . WP_PLUGIN_URL . '/bp-groupblog/inc/i/screenshot-micro.png">';?>
							<br /><br />
							<input<?php if ( !bp_groupblog_is_blog_enabled( $group_id ) ) { ?> disabled="true"<?php } ?> name="page_template_layout" id="page_template_layout"  value="microblog" type="radio" <?php if ($page_template_layout == 'microblog') echo 'checked="checked"'; ?> /><label style="display:inline;"> <?php _e( 'Microblog', 'groupblog' ) ?></label>
							<p class="description"><?php _e( 'Great for simple listing of posts in a chronological order.', 'groupblog' ) ?></p>
						</td>
					</tr>
					</tbody>
				</table>

			</div>

			<br />

		<?php endif; ?>

		<?php if ( !$groupblog_create_screen ) : ?>
			<p>
				<input id="save" type="submit" name="save" class="submit" value="<?php _e('Save Changes &raquo;', 'groupblog') ?>"/>
			</p>
		<?php endif;
}

/**
 * bp_groupblog_validate_blog_signup()
 *
 * Final step before the blog gets created it needs to be validated
 */
function bp_groupblog_validate_blog_signup() {
	global $bp, $wpdb, $current_user, $blogname, $blog_title, $errors;
	global $groupblog_blog_id, $filtered_results;

	$group_id = isset( $_COOKIE['bp_new_group_id'] ) ? $_COOKIE['bp_new_group_id'] : bp_get_current_group_id();

	$current_user = wp_get_current_user();
	if( !is_user_logged_in() )
		die();

  // Re-validate user info.
	$result = bp_groupblog_validate_blog_form();
	extract($result);

	$checks = get_site_option( 'bp_groupblog_blog_defaults_options' );

	if ( $errors->get_error_code() ) {
		$message = '';
		$message .= $errors->get_error_message('blogname') . '<br />';
		$message .= __( ' We suggest adjusting the blog address below, in accordance with the following requirements:', 'groupblog' ) . '<br />';
		if ( $checks['allowunderscores'] != '1' || $checks['allowdashes'] != '1' )
			$message .= __( ' &raquo; Only letters and numbers allowed.', 'groupblog' ) . '<br />';
		$message .= sprintf( __( ' &raquo; Must be at least %s characters.', 'groupblog' ), $checks['minlength'] ) . '<br />' ;
		if ( $checks['allownumeric'] != '1' )
			$message .= __( ' &raquo; Has to contain letters as well.', 'groupblog' );
		bp_core_add_message( $message, 'error' );

		$redirect_url = bp_is_current_action( 'create' ) ? trailingslashit( bp_get_groups_directory_permalink() . 'create/step/' . bp_action_variable( 1 ) ) : bp_get_group_permalink( groups_get_current_group() ) . '/admin/group-blog/';

		$error_params = array(
			'create_error'    => '4815162342',
			'invalid_address' => urlencode( $_POST['blogname'] ),
			'invalid_name'    => urlencode( $_POST['blog_title'] )
		);
		$redirect_url = add_query_arg( $error_params, $redirect_url );
		bp_core_redirect( $redirect_url );

	}

	$public = (int) $_POST['blog_public'];

	groups_update_groupmeta( $group_id, 'groupblog_public', $public);
	groups_update_groupmeta( $group_id, 'groupblog_title', $blog_title);
	groups_update_groupmeta( $group_id, 'groupblog_path', $path);
	groups_update_groupmeta( $group_id, 'groupblog_domain', $domain);

	$meta = apply_filters('signup_create_blog_meta', array ('lang_id' => 1, 'public' => $public)); // depreciated
	$meta = apply_filters( "add_signup_meta", $meta );

	$groupblog_blog_id = wpmu_create_blog( $domain, $path, $blog_title, $current_user->ID, $meta, $wpdb->siteid );

	$errors = $filtered_results['errors'];

	return true;

}

/**
 * Detects a post edit and modifies the BP Groupblog activity entry if found.
 *
 * This is needed for BuddyPress 2.2+.  Older versions of BP continues to use
 * the {@link bp_groupblog_set_group_to_post_activity()} function.
 *
 * @since 1.8.10
 *
 * @param string $new_status New status for the post.
 * @param string $old_status Old status for the post.
 * @param object $post       Post data.
 */
function bp_groupblog_catch_transition_post_type_status( $new_status, $old_status, $post ) {
	// Only needed for >= BP 2.2
	if ( ! function_exists( 'bp_activity_post_type_update' ) ) {
		return;
	}

	// But not needed for BP 2.5. See BP ticket #6834.
	if ( function_exists( 'bp_register_post_types' ) ) {
		return;
	}

	// bail if not a blog post
	if ( 'post' !== $post->post_type ) {
		return;
	}

	// This is an edit.
	if ( $new_status === $old_status ) {
		// An edit of an existing post should update the existing activity item.
		if ( $new_status == 'publish' ) {
			$group_id = get_groupblog_group_id( get_current_blog_id() );

			// Grab existing activity ID
			$id = bp_activity_get_activity_id( array(
				'component'         => 'groups',
				'type'              => 'new_groupblog_post',
				'item_id'           => $group_id,
				'secondary_item_id' => $post->ID
			) );

			if ( empty( $id ) ) {
				return;
			}

			// Grab activity item and modify some properties
			$activity = new BP_Activity_Activity( $id );
			$activity->content = $post->post_content;
			$activity->date_recorded = bp_core_current_time();

			// Pass activity to our edit function
			bp_groupblog_set_group_to_post_activity( $activity, array(
				'group_id' => $group_id,
				'post'     => $post
			) );
		}

		return;
	}
}
add_action( 'transition_post_status', 'bp_groupblog_catch_transition_post_type_status', 10, 3 );

/**
 * bp_groupblog_set_group_to_post_activity ( $activity )
 *
 * Record the blog activity for the group - by Luiz Armesto
 *
 * @since 1.8.10 Added $args parameter.
 * @todo Move this functionality into bp_groupblog_catch_transition_post_type_status().
 *
 * @param BP_Activity_Activity $activity
 * @param array $args {
 *     Optional. Handy if you've already parsed the blog post and group ID.
 *     @type WP_Post $post     The WP post object.
 *     @type int     $group_id The group ID.
 * }
 */
function bp_groupblog_set_group_to_post_activity( $activity, $args = array() ) {

	// sanity check!
	if ( ! bp_is_active( 'groups' ) ) {
		return;
	}

	// If we've using this function outside the regular BP activity save process,
	// set some variables
	if ( ! empty( $args['post'] ) ) {
		$post     = $args['post'];
		$group_id = $args['group_id'];
		$id       = $activity->id;

	// Regular BP save routine
	} else {
		// stop if this activity item is not a blog post!
		if ( $activity->type != 'new_blog_post' ) {
			return;
		}

		$blog_id  = $activity->item_id;
		$post_id  = $activity->secondary_item_id;
		$group_id = get_groupblog_group_id( $blog_id );

		// no group is attached to this blog, so stop now!
		if ( ! $group_id ) {
			return;
		}

		$post = get_post( $post_id );

		// Try to see if we are editing an existing groupblog post
		$id = bp_activity_get_activity_id( array(
			'type'              => 'new_groupblog_post',
			'item_id'           => $group_id,
			'secondary_item_id' => $post_id
		) );
	}

	// fetch group data
	$group = groups_get_group( array( 'group_id' => $group_id ) );

	// Only allow certain HTML tags in post titles.
	if ( ! empty( $post->post_title ) ) {
		$allowed_tags = array(
			'em' => array(),
			'strong' => array(),
		);
		$post->post_title = wp_kses( $post->post_title, $allowed_tags );
	}

	// This is an existing blog post
	if ( ! empty( $id ) ) {

		if ( apply_filters( 'groupblog_skip_edit_activity', false ) ) {
			return;
		}

		$activity->id      = $id;
		$activity->user_id = $post->post_author;

		$activity->action = sprintf( __( '%s edited the blog post %s in the group %s:', 'groupblog'), bp_core_get_userlink( $post->post_author ), '<a href="' . get_permalink( $post->ID ) .'">' . $post->post_title . '</a>', '<a href="' . bp_get_group_permalink( $group ) . '">' . esc_attr( $group->name ) . '</a>' );

	// This is a new blog post!
	} else {
		$activity->action = sprintf( __( '%s wrote a new blog post %s in the group %s:', 'groupblog'), bp_core_get_userlink( $post->post_author ), '<a href="' . get_permalink( $post->ID ) .'">' . $post->post_title . '</a>', '<a href="' . bp_get_group_permalink( $group ) . '">' . esc_attr( $group->name ) . '</a>' );
	}

	$activity->primary_link  = get_permalink( $post->ID );

	// Replace the necessary values to display in group activity stream
	$activity->item_id       = (int)$group_id;
	$activity->component     = 'groups';

	// use group's privacy settings for activity privacy
	$activity->hide_sitewide = $group->status == 'public' ? 0 : 1;

	// need to set type as new_groupblog_post (see bp_groupblog_posts() below) or filters won't work
	$activity->type = 'new_groupblog_post';

	remove_action( 'bp_activity_before_save', 'bp_groupblog_set_group_to_post_activity');

	// Using this function outside BP's save routine requires us to manually save
	if ( ! empty( $args['post'] ) ) {
		$activity->save();
	}

	// Update the last_active flag for the group.
	groups_update_last_activity( $group_id );
}
add_action( 'bp_activity_before_save', 'bp_groupblog_set_group_to_post_activity');

/**
 * When a blog post is deleted, delete the activity item
 *
 * @since 1.8.5
 *
 * @param int $post_id
 * @param int $blog_id
 * @param int $user_id
 */
function bp_groupblog_remove_post( $post_id, $blog_id = 0, $user_id = 0 ) {
	// Bail if the activity or blogs components are not enabled
	if ( ! bp_is_active( 'blogs' ) || ! bp_is_active( 'activity' ) ) {
		return;
	}

	global $wpdb, $bp;

	$post_id = (int) $post_id;

	if ( !$blog_id )
		$blog_id = get_current_blog_id();

	if ( !$user_id )
		$user_id = bp_loggedin_user_id();

	$group_id = get_groupblog_group_id( $blog_id );

	if ( ! $group_id ) {
		return false;
	}

	do_action( 'bp_groupblog_before_remove_post', $blog_id, $post_id, $user_id, $group_id );

	// Delete activity stream item
	bp_blogs_delete_activity( array(
		'item_id' => $group_id,
		'secondary_item_id' => $post_id,
		'type' => 'new_groupblog_post',
		'component' => $bp->groups->id
	) );

	do_action( 'bp_groupblog_remove_post', $blog_id, $post_id, $user_id, $group_id );
}
add_action( 'wp_trash_post', 'bp_groupblog_remove_post', 5 );
add_action( 'delete_post', 'bp_groupblog_remove_post', 5 );

/**
 * Add "new_groupblog_post" activity type to "Posts" dropdown filter option.
 *
 * When the "Posts" option is selected in the activity dropdown filter, it
 * only filters activity items by blog posts and not groupblog posts.  This
 * function allows both types of blog posts to be filtered in activity loops.
 *
 * @since 1.8.9
 *
 * @param string $qs The querystring for the BP loop
 * @param string $object The current object for the querystring
 * @return string Modified querystring
 */
function bp_groupblog_override_new_blog_post_activity_filter( $qs, $object ) {
	// not on the blogs object? stop now!
	if ( $object != 'activity' ) {
		return $qs;
	}

	// parse querystring into an array
	$r = wp_parse_args( $qs );

	if ( empty( $r['type'] ) || 'new_blog_post' !== $r['type'] ) {
		return $qs;
	}

	// add the 'new_groupblog_post' type if it doesn't exist
	if ( false === strpos( $r['action'], 'new_groupblog_post' ) ) {
		// 'action' filters activity items by the 'type' column
		$r['action'] .= ',new_groupblog_post';
	}

	// 'type' isn't used anywhere internally
	unset( $r['type'] );

	// return a querystring
	return build_query( $r );
}
add_filter( 'bp_ajax_querystring', 'bp_groupblog_override_new_blog_post_activity_filter', 20, 2 );

/**
 * See if users are able to comment to the activity entry of the groupblog post.
 *
 * @since 1.8.4
 */
function bp_groupblog_activity_can_comment( $retval ) {
	if ( 'new_groupblog_post' !== bp_get_activity_action_name() && 'new_groupblog_comment' !== bp_get_activity_action_name() ) {
		return $retval;
	}

	// Explicitly disable activity commenting on groupblog items.
	return false;
}
add_filter( 'bp_activity_can_comment', 'bp_groupblog_activity_can_comment' );

/**
 * Register 'new_groupblog_comment' action with the activity component.
 *
 * @since 1.9.0
 */
add_action( 'bp_register_activity_actions', function() {
	bp_activity_set_action(
		'groups',
		'new_groupblog_comment',
		__( 'New groupblog comment', 'bp-groupblog' ),
		'bp_groupblog_format_activity_action_new_groupblog_comment',
		bp_is_user() ? __( 'Groupblog Comments', 'bp-groupblog' ) : __( 'Blog Comments', 'bp-groupblog' ),
		array( 'activity', 'member', 'group' ),
		0
	);
} );

/**
 * Action format Callback for our 'new_groupblog_comment' activity type.
 *
 * @since 1.9.0
 */
function bp_groupblog_format_activity_action_new_groupblog_comment( $action, $activity ) {
	$blog_id = get_groupblog_blog_id( $activity->item_id );

	$blog_url  = bp_blogs_get_blogmeta( $blog_id, 'url' );
	$blog_name = bp_blogs_get_blogmeta( $blog_id, 'name' );

	if ( empty( $blog_url ) || empty( $blog_name ) ) {
		$blog_url  = get_home_url( $blog_id );
		$blog_name = get_blog_option( $blog_id, 'blogname' );

		bp_blogs_update_blogmeta( $blog_id, 'url', $blog_url );
		bp_blogs_update_blogmeta( $blog_id, 'name', $blog_name );
	}

	$post_url   = bp_activity_get_meta( $activity->id, 'post_url' );
	$post_title = bp_activity_get_meta( $activity->id, 'post_title' );

	if ( empty( $activity->user_id ) ) {
		$anonymous = bp_activity_get_meta( $activity->id, 'anonymous_comment_author' );
	}

	// Should only be empty at the time of post creation.
	if ( empty( $post_url ) || empty( $post_title ) ) {
		switch_to_blog( $blog_id );

		$comment = get_comment( $activity->secondary_item_id );

		if ( ! empty( $comment->comment_post_ID ) ) {
			$post_url = add_query_arg( 'p', $comment->comment_post_ID, trailingslashit( $blog_url ) );
			bp_activity_update_meta( $activity->id, 'post_url', $post_url );

			$post = get_post( $comment->comment_post_ID );

			if ( is_a( $post, 'WP_Post' ) ) {
				$post_title = $post->post_title;
				bp_activity_update_meta( $activity->id, 'post_title', $post_title );
			}
		}

		if ( empty( $activity->user_id ) ) {
			$anonymous = $comment->comment_author;
			bp_activity_update_meta( $activity->id, 'anonymous_comment_author', $anonymous );
		}

		restore_current_blog();
	}

	$post_link = '<a href="' . esc_url( $post_url ) . '">' . $post_title . '</a>';
	$user_link = bp_core_get_userlink( $activity->user_id );

	if ( empty( $activity->user_id ) && ! empty( $anonymous ) ) {
		$user_link = esc_attr( $anonymous );
	} elseif ( empty( $activity->user_id ) ) {
		$user_link = esc_html__( 'Anonymous user', 'bp-groupblog' );
	}

	// Build the complete activity action string.
	$action = sprintf( __( '%1$s commented on the post, %2$s, on the groupblog %3$s', 'buddypress' ), $user_link, $post_link, '<a href="' . esc_url( $blog_url ) . '">' . esc_html( $blog_name ) . '</a>' );

	return $action;
}

/**
 * Hook to switch 'new_blog_comment' activity type to 'new_groupblog_comment'.
 *
 * We're going to be piggybacking off of BuddyPress' existing blog comment
 * recording, but we're going to switch the activity type for groupblog
 * comments to our custom 'new_groupblog_comment' so groups can view these
 * items in their activity stream.
 *
 * @since 1.9.0
 */
add_action( 'bp_activity_before_save', function( $activity ) {
	// We handle groupblog activities differently.
	if ( 'new_blog_comment' !== $activity->type ) {
		return;
	}

	/*
	 * See if the blog is connected to a group.
	 *
	 * If so, switch the activity properties around.
	 */
	$group_id = get_groupblog_group_id( $activity->item_id );
	if ( ! empty( $group_id ) ) {
		$activity->component = 'groups';
		$activity->type      = 'new_groupblog_comment';
		$activity->item_id   = $group_id;

		if ( ! $activity->hide_sitewide ) {
			$group = groups_get_group( $group_id );
			if ( 'public' !== $group->status ) {
				$activity->hide_sitewide = true;
			}
		}
	}
} );

/**
 * Groupblog comment status transition listener.
 *
 * @since 1.9.0
 *
 * @param string $new_status New comment status.
 * @param string $old_status Old comment status.
 * @param object $comment    Comment object.
 */
function bp_groupblog_transition_comment_status( $new_status, $old_status, $comment ) {
	$group_id = get_groupblog_group_id( get_current_blog_id() );
	if ( empty( $group_id ) ) {
		return;
	}

	buddypress()->activity->groupblog_temp_id = $group_id;

	$post_type = get_post_type( $comment->comment_post_ID );
	if ( 'post' !== $post_type ) {
		return;
	}

	if ( in_array( $new_status, array( 'delete', 'hold' ) ) ) {
		bp_activity_delete_by_item_id( array(
			'item_id'           => $group_id,
			'secondary_item_id' => $comment->comment_ID,
			'component'         => 'groups',
			'type'              => 'new_groupblog_comment',
			'user_id'           => false,
		) );

		remove_action( 'transition_comment_status', 'bp_activity_transition_post_type_comment_status', 10 );
		return;
	}

	add_filter( 'bp_activity_get_activity_id', '_bp_groupblog_set_activity_id_for_groupblog_comment', 10, 2 );
	add_filter( 'bp_disable_blogforum_comments', '__return_true' );
}
add_action( 'transition_comment_status', 'bp_groupblog_transition_comment_status', 0, 3 );

/**
 * Set activity filters when posting groupblog comments.
 *
 * We need to hook into {@link bp_activity_post_type_comment()} to manipulate
 * how BuddyPress records blog post comments into the activity stream.  This
 * is mainly to handle existing activity items and to generate separate
 * activity entries and not nested, activity comments.
 *
 * @since 1.9.0
 */
add_filter( 'bp_activity_post_pre_comment', function( $retval, $blog_id ) {
	$group_id = get_groupblog_group_id( $blog_id );
	if ( empty( $group_id ) ) {
		return $retval;
	}

	buddypress()->activity->groupblog_temp_id = $group_id;

	add_filter( 'bp_activity_get_activity_id', '_bp_groupblog_set_activity_id_for_groupblog_comment', 10, 2 );
	add_filter( 'bp_disable_blogforum_comments', '__return_true' );

	return $retval;
}, 10, 2 );

/**
 * Delete corresponding activity item when groupblog comment is deleted.
 *
 * @since 1.9.0
 *
 * @param int $comment_id Blog comment ID.
 */
function bp_groupblog_delete_activity_on_delete_blog_comment( $comment_id ) {
	$group_id = get_groupblog_group_id( get_current_blog_id() );
	if ( empty( $group_id ) ) {
		return;
	}

	$comment = get_comment( $comment_id );
	$post_type = get_post_type( $comment->comment_post_ID );
	if ( 'post' !== $post_type ) {
		return;
	}

	bp_activity_delete_by_item_id( array(
		'item_id'           => $group_id,
		'secondary_item_id' => $comment_id,
		'component'         => 'groups',
		'type'              => 'new_groupblog_comment',
		'user_id'           => false,
	) );

	remove_action( 'delete_comment', 'bp_activity_post_type_remove_comment', 10 );
}
add_action( 'delete_comment', 'bp_groupblog_delete_activity_on_delete_blog_comment', 0 );

/**
 * Delete corresponding post comments when groupblog activity item is deleted.
 *
 * @since 1.9.0
 */
add_action( 'bp_activity_after_delete', function( $activities ) {
	$switched = false;
	foreach ( $activities as $activity ) {
		if ( 'groups' === $activity->component && 'new_groupblog_comment' === $activity->type ) {
			$blog_id = get_groupblog_blog_id( $activity->item_id );

			if ( ! $switched ) {
				remove_action( 'transition_comment_status', 'bp_groupblog_transition_comment_status', 0 );
				remove_action( 'transition_comment_status', 'bp_activity_transition_post_type_comment_status', 10 );
				remove_action( 'delete_comment', 'bp_groupblog_delete_activity_on_delete_blog_comment', 0 );
				remove_action( 'delete_comment', 'bp_activity_post_type_remove_comment', 10 );

				if ( ! empty( $blog_id ) ) {
					switch_to_blog( $blog_id );
					$switched = true;
				}
			}

			if ( ! empty( $blog_id ) ) {
				wp_delete_comment( $activity->secondary_item_id, true );
			}
		}
	}

	if ( $switched ) {
		restore_current_blog();

		add_action( 'transition_comment_status', 'bp_groupblog_transition_comment_status', 0, 3 );
		add_action( 'transition_comment_status', 'bp_activity_transition_post_type_comment_status', 10, 3 );
		add_action( 'delete_comment', 'bp_groupblog_delete_activity_on_delete_blog_comment', 0 );
		add_action( 'delete_comment', 'bp_activity_post_type_remove_comment', 10 );
	}
} );

/**
 * Helper function to fetch the activity ID for a groupblog comment.
 *
 * @since 1.9.0
 *
 * @param  int   $retval Activity ID.
 * @param  array $r      Activity arguments used to fetch the activity ID.
 * @return int
 */
function _bp_groupblog_set_activity_id_for_groupblog_comment( $retval, $r ) {
	$groupblog_temp_id = isset( buddypress()->activity->groupblog_temp_id ) ? buddypress()->activity->groupblog_temp_id : null;
	if ( ! $groupblog_temp_id ) {
		return $retval;
	}

	$r['component'] = 'groups';
	$r['type'] = 'new_groupblog_comment';
	$r['item_id'] = buddypress()->activity->groupblog_temp_id;

	return BP_Activity_Activity::get_id(
		$r['user_id'],
		$r['component'],
		$r['type'],
		$r['item_id'],
		$r['secondary_item_id'],
		$r['action'],
		$r['content'],
		$r['date_recorded']
	);
}

/**
 * Set the activity permalink for groupblog posts to the post permalink.
 *
 * @since 1.8.4
 */
function bp_groupblog_activity_permalink( $retval, $activity ) {
	// not a groupblog post? stop now!
	if ( $activity->type !== 'new_groupblog_post' && $activity->type !== 'new_groupblog_comment' ) {
		return $retval;
	}

	return $activity->primary_link;
}
add_filter( 'bp_activity_get_permalink', 'bp_groupblog_activity_permalink', 10, 2 );

/**
 * bp_groupblog_posts()
 *
 * Add a filter option to the filter select box on group activity pages.
 */
function bp_groupblog_posts() { ?>

	<option value="new_groupblog_post"><?php _e( 'Blog Posts', 'groupblog' ) ?></option><?php

}
add_action( 'bp_group_activity_filter_options', 'bp_groupblog_posts' );

/**
 * groupblog_screen_blog()
 *
 * This screen gets called when the 'group blog' link is clicked.
 */
function groupblog_screen_blog() {

	if ( bp_is_groups_component() && bp_is_current_action( apply_filters( 'bp_groupblog_subnav_item_slug', 'blog' ) ) ) {

		$checks = get_site_option('bp_groupblog_blog_defaults_options');
		$blog_id = get_groupblog_blog_id();

		$home_url = ! empty( $blog_id ) ? get_home_url( get_groupblog_blog_id() ) : false;

		if ( isset( $checks['redirectblog'] ) && $checks['redirectblog'] == 1 && ! empty( $home_url ) ) {
			wp_redirect( $home_url );
			die();

		} else if ( isset( $checks['redirectblog'] ) && $checks['redirectblog'] == 2 && ! empty( $home_url ) ) {
			wp_redirect( $home_url . '/' . $checks['pageslug'] . '/' );
			die();

		} else {
			if ( file_exists( locate_template( array( 'groupblog/blog.php' ) ) ) ) {
				bp_core_load_template( apply_filters( 'groupblog_screen_blog', 'groupblog/blog' ) );
				add_action( 'bp_screens', 'groupblog_screen_blog' );
			}
			else if ( 0 != groups_get_current_group() ) {
			 	add_action( 'bp_template_content', 'groupblog_screen_blog_content' );
				bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'groups/single/plugins' ) );
			}
		}
	}
}

/**
 * groupblog_screen_blog_content()
 *
 * Depending on the groupblog admin setup we load the correct template.
 */
function groupblog_screen_blog_content() {
	global $bp, $wp;

	load_template( WP_PLUGIN_DIR . '/bp-groupblog/bp-groupblog-blog.php' );
}

/**
 * groupblog_redirect_group_home()
 *
 * Redirect Group Home page to Blog Home page if set in admin settings.
 */
function groupblog_redirect_group_home() {
	global $bp;

	if ( bp_is_group_home() ) {

		$checks = get_site_option('bp_groupblog_blog_defaults_options');

		$blog_id = get_groupblog_blog_id();

		if ( $checks['deep_group_integration'] && ! empty( $blog_id ) ) {
			$home_url = get_home_url( $blog_id );
			bp_core_redirect( $home_url );
		}
	}
}
add_action( 'bp_init', 'groupblog_redirect_group_home' );

/**
 * bp_groupblog_delete_meta( $blog_id, $drop = false )
 *
 * Clean up groupmeta after a blog gets deleted.
 */
function bp_groupblog_delete_meta( $blog_id, $drop = false ) {

	$group_id = get_groupblog_group_id( $blog_id );

	groups_update_groupmeta ( $group_id, 'groupblog_enable_blog', '' );
	groups_update_groupmeta ( $group_id, 'groupblog_blog_id', '' );
	wp_cache_delete( $blog_id, 'bp_groupblog_blog_group_ids' );

	groups_update_groupmeta ( $group_id, 'groupblog_silent_add', '' );

  	groups_update_groupmeta ( $group_id, 'groupblog_default_admin_role', '' );
	groups_update_groupmeta ( $group_id, 'groupblog_default_mod_role', '' );
	groups_update_groupmeta ( $group_id, 'groupblog_default_member_role', '' );

}
add_action('delete_blog', 'bp_groupblog_delete_meta', 10, 1);

/**
 * Use the group avatar on the Site Directory page for groupblogs.
 *
 * If a site in the site loop is a groupblog, use the group logo only if
 * the site doesn't already have a customized site icon.
 *
 * @since 1.9.2
 *
 * @param  string $retval  Current site avatar
 * @param  int    $blog_id Site ID in loop
 * @param  array  $r       Avatar arguments
 * @return string
 */
function bp_groupblog_use_group_avatar_in_site_loop( $retval, $blog_id, $r ) {
	// Not a groupblog? Bail.
	$group_id = get_groupblog_group_id( $blog_id );
	if ( empty( $group_id ) ) {
		return $retval;
	}

	// Already using a site icon, so bail.
	$site_icon = bp_blogs_get_blogmeta( $blog_id, "site_icon_url_{$r['type']}" );
	if ( ! empty( $site_icon ) ) {
		return $retval;
	}

	// Site is using the site admin's avatar, so switch to group logo.
	return bp_core_fetch_avatar( array(
		'item_id'    => $group_id,
		'avatar_dir' => 'group-avatars',
		'object'     => 'group',
		'type'       => $r['type'],
		'alt'        => 'Group logo',
		'width'      => $r['width'],
		'height'     => $r['height'],
	) );
}
add_filter( 'bp_get_blog_avatar', 'bp_groupblog_use_group_avatar_in_site_loop', 10, 3 );
