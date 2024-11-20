<?php
/**
 * Get avatar directoy details
 *
 * @param int $blog_id Id of blog.
 *
 * @return mixed
 */
function bd_blog_avatar_upload_dir( $blog_id = 0 ) {
	$bp = buddypress();

	if ( ! $blog_id ) {
		$blog_id = get_current_blog_id();
	}

	$path    = bp_core_avatar_upload_path() . '/blog-avatars/' . $blog_id;
	$newbdir = $path;

	if ( ! file_exists( $path ) ) {
		@wp_mkdir_p( $path );
	}

	$newurl    = bp_core_avatar_url() . '/blog-avatars/' . $blog_id;
	$newburl   = $newurl;
	$newsubdir = '/blog-avatars/' . $blog_id;

	$args = array(
		'path'    => $path,
		'url'     => $newurl,
		'subdir'  => $newsubdir,
		'basedir' => $newbdir,
		'baseurl' => $newburl,
		'error'   => false,
	);

	return apply_filters( 'blogs_avatar_upload_dir', $args );
}

/**
 * Filters on bp_get_blog_avatar
 * only provides the avatar if there is one uploaded for the blog
 *
 * @param string $avatar Formatted HTML <img> element, or raw avatar.
 * @param int    $blog_id blog id.
 * @param array  $avatar_data array of arguments.
 *
 * @return string
 */
function bd_filter_blog_avatar( $avatar, $blog_id, $avatar_data ) {
	if ( ! $blog_id ) {
		$blog_id = get_current_blog_id();
	}

	$has_avatar = get_blog_option( $blog_id, 'has_avatar' );

	// if there is no uploaded avatar, do not try to fetch onen, just return the one we got.
	if ( ! $has_avatar ) {
		return $avatar;
	}

	/**
	 * If we are here, there is an an avatar associated with this blog.
	 * let us prep data to fetch that.
	 */

	$avatar_data ['item_id']    = $blog_id; // reset object type to blog.
	$avatar_data ['object']     = 'blog'; // reset object type to blog
	$avatar_data ['avatar_dir'] = 'blog-avatars'; // reset object type to blog
	$avatar_data ['alt']        = ''; // reset alt.

	return bp_core_fetch_avatar( $avatar_data );

}
add_filter( 'bp_get_blog_avatar', 'bd_filter_blog_avatar', 10, 3 );

/**
 * Upto 1.8, BuddyPress has a bug and does not pass the blog id for item deletion for deleting avatar.
 */

/**
 * Filter avatar item id
 *
 * @param int    $item_id avatar item id.
 * @param object $object object.
 *
 * @return int
 */
function bd_filter_avatar_item_id( $item_id, $object ) {

	if ( 'blog' !== $object ) {
		return $item_id;
	}

	if ( ! $item_id ) {
		$item_id = get_current_blog_id();
	}

	return $item_id;
}
add_filter( 'bp_core_avatar_item_id', 'bd_filter_avatar_item_id', 10, 2 );

/**
 * Check whether user can upload avatar
 *
 * @param bool $can check user capability.
 *
 * @return bool
 */
function bd_attachments_current_user_can( $can ) {

	if ( is_super_admin() || current_user_can( 'manage_options' ) ) {
		$can = true;
	}

	return $can;
}

add_filter( 'bp_attachments_current_user_can', 'bd_attachments_current_user_can' );
