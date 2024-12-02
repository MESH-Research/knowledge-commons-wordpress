<?php

/**
 * Get blog avatar
 * To get blog avatar outside the blogs loop
 *
 * @param string $args list of arguments.
 */
function bd_blog_avatar( $args = '' ) {
	echo bd_get_blog_avatar( $args );
}

/**
 * Get Blog avatar
 *
 * @param string $args list of arguments.
 *
 * @return mixed
 */
function bd_get_blog_avatar( $args = '' ) {

	$defaults = array(
		'type'    => 'full',
		'width'   => false,
		'height'  => false,
		'class'   => 'avatar',
		'id'      => false,
		'alt'     => '',
		'blog_id' => get_current_blog_id(),
		'no_grav' => true,
	);

	$r = wp_parse_args( $args, $defaults );

	$blog = bd_get_blog_details( $r['blog_id'] );

	$r['item_id'] = $blog->admin_user_id;
	$r['email'] = $blog->admin_user_email;

	$avatar = apply_filters( 'bp_get_blog_avatar_' . $r['blog_id'], bp_core_fetch_avatar( $r ) );

	return apply_filters( 'bp_get_blog_avatar', $avatar, $blog->blog_id, $r );
}

/**
 * Get blog details
 *
 * @param int $blog_id Id of blog.
 *
 * @return array|null|object|void
 */
function bd_get_blog_details( $blog_id ) {

	global $wpdb;

	$bp = buddypress();

	$blog = $wpdb->get_row( $wpdb->prepare( "SELECT b.blog_id, b.user_id as admin_user_id, u.user_email as admin_user_email FROM {$bp->blogs->table_name} b, {$wpdb->users} u WHERE b.user_id = u.ID and b.blog_id = %d", $blog_id ) );

	return $blog;
}

