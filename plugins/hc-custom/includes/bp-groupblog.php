<?php
/**
 * Customizations to bp-groupblog
 *
 * @package Hc_Custom
 */

/**
 * Remove users from group blog upon leaving group.
 *
 * @param int $group_id Group.
 * @param int $user_id User.
 */
function hcommons_remove_user_from_group_site( $group_id, $user_id ) {
	$blog_id = get_groupblog_blog_id( $group_id );
	remove_user_from_blog( $user_id, $blog_id );
}
add_action( 'groups_leave_group', 'hcommons_remove_user_from_group_site', 10, 2 );

/**
 * Hook into and modify site meta fields on creation.
 *
 * @param array $blog_meta_defaults blog meta fields.
 */

function hcommons_signup_create_blog_meta( $blog_meta_defaults ) { 

    if ( '1' == $_POST['is_classsite'] ) {
	
	$blog_meta_defaults['template'] = 'learningspace';
	$blog_meta_defaults['stylesheet'] = 'learningspace';
    }
  
    return $blog_meta_defaults; 
}
//add_filter( 'signup_create_blog_meta', 'hcommons_signup_create_blog_meta', 10, 1 ); 


add_action( 'wp_insert_site', 'hcommons_wp_insert_site');

/**
 * Set wp_blog, siteurl, and homeurl of the new site domain if it is a class site.
 *
 * @param object $new_site WP_Site object.
 */

function hcommons_wp_insert_site( $new_site ){
    global $wpdb;
      
     if ( '1' == $_POST['is_classsite'] ) {

	$user = wp_get_current_user();

        $domain_parts = explode('.', $new_site->domain);

        $partial_domain = array_slice($domain_parts, 1);

        $append_domain = array($user->user_login.'-'.$domain_parts[0]);

        $corrected_domain = array_merge($append_domain, $partial_domain);

        $completed_domain = implode('.', $corrected_domain);
    
        $new_site->domain = $completed_domain;
	$rows_affected = $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->blogs}  SET domain = %s WHERE blog_id = %d", $completed_domain, $new_site->blog_id
        ) // $wpdb->prepare
        ); // $wpdb->query
	

	}

}

function wporg_wpmu_new_blog_example( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
 global $wpdb;

   if ( '1' == $_POST['is_classsite'] ) {
       switch_to_blog( $blog_id );
	switch_theme('learningspace');
	restore_current_blog();

    }
}
add_action( 'wpmu_new_blog', 'wporg_wpmu_new_blog_example', 10, 6 );

/**
 * Get the URL the group "Site" tab should take a visitor to.
 *
 * Mirrors bp-groupblog's network "redirectblog" setting: 1 sends visitors to
 * the site's home page, 2 to a configured page on the site. Any other value
 * means bp-groupblog renders the blog inside the group, so no URL is returned.
 *
 * @param int $group_id Group ID.
 * @return string Site URL, or '' when the group has no site or redirects are off.
 */
function hcommons_get_groupblog_site_url( $group_id ) {
	$blog_id = (int) groups_get_groupmeta( $group_id, 'groupblog_blog_id' );
	if ( ! $blog_id ) {
		return '';
	}

	$home_url = get_home_url( $blog_id );
	if ( empty( $home_url ) ) {
		return '';
	}

	$checks = get_site_option( 'bp_groupblog_blog_defaults_options' );
	$mode   = ( is_array( $checks ) && isset( $checks['redirectblog'] ) ) ? (int) $checks['redirectblog'] : 0;

	if ( 1 === $mode ) {
		return $home_url;
	}

	if ( 2 === $mode ) {
		$pageslug = isset( $checks['pageslug'] ) ? trim( (string) $checks['pageslug'], '/' ) : '';
		return trailingslashit( $home_url ) . ( '' !== $pageslug ? $pageslug . '/' : '' );
	}

	return '';
}

/**
 * Get the hostname of a group's site.
 *
 * @param int $group_id Group ID.
 * @return string Lower-cased hostname, or '' when the group has no site.
 */
function hcommons_get_groupblog_site_host( $group_id ) {
	$blog_id = (int) groups_get_groupmeta( $group_id, 'groupblog_blog_id' );
	if ( ! $blog_id ) {
		return '';
	}

	$host = wp_parse_url( get_home_url( $blog_id ), PHP_URL_HOST );

	return is_string( $host ) ? strtolower( $host ) : '';
}

/**
 * Allow wp_safe_redirect() to send visitors from a group to that group's site.
 *
 * bp-groupblog 1.9.4 redirects the group "Site" tab with wp_safe_redirect().
 * Group sites live on their own subdomain, which is not an allowed redirect
 * host for the main network site, so WordPress discards the URL and falls back
 * to wp-admin. Adding the current group's site host fixes that.
 *
 * @param string[] $hosts Allowed redirect hosts.
 * @return string[]
 */
function hcommons_allow_groupblog_redirect_host( $hosts ) {
	if ( ! function_exists( 'bp_is_group' ) || ! bp_is_group() ) {
		return $hosts;
	}

	$host = hcommons_get_groupblog_site_host( bp_get_current_group_id() );
	if ( '' === $host ) {
		return $hosts;
	}

	$hosts = (array) $hosts;
	if ( ! in_array( $host, $hosts, true ) ) {
		$hosts[] = $host;
	}

	return $hosts;
}
add_filter( 'allowed_redirect_hosts', 'hcommons_allow_groupblog_redirect_host' );

/**
 * Point the group "Site" tab straight at the group's site.
 *
 * Runs after bp-groupblog has registered the tab so the link goes directly to
 * the site rather than via the /groups/<slug>/blog/ redirect.
 */
function hcommons_groupblog_nav_link_to_site() {
	if ( ! bp_is_group() ) {
		return;
	}

	$url = hcommons_get_groupblog_site_url( bp_get_current_group_id() );
	if ( '' === $url ) {
		return;
	}

	$slug = apply_filters( 'bp_groupblog_subnav_item_slug', 'blog' );

	buddypress()->groups->nav->edit_nav( array( 'link' => $url ), $slug, bp_get_current_group_slug() );
}
add_action( 'bp_setup_nav', 'hcommons_groupblog_nav_link_to_site', 20 );
