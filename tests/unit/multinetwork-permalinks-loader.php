<?php
/**
 * Stubs for the WP/BP/bbPress functions used by the multinetwork permalink
 * filters in plugins/hc-custom/includes/bbpress.php (the file itself is loaded
 * by group-permissions-loader.php).
 *
 * All stubs are configurable per-test through $GLOBALS['_hc_mock'].
 */

// Society root-blog constants resolved by the filters.
if ( ! defined( 'MOCKSOC_ROOT_BLOG_ID' ) ) {
	define( 'MOCKSOC_ROOT_BLOG_ID', 7 );
}
if ( ! defined( 'MOCKHOME_ROOT_BLOG_ID' ) ) {
	define( 'MOCKHOME_ROOT_BLOG_ID', 1 );
}

if ( ! function_exists( 'bp_get_activity_id' ) ) {
	/**
	 * Mirrors BuddyPress: reads the current item straight off the global
	 * activity template, so it warns exactly like core when there is none.
	 */
	function bp_get_activity_id() {
		global $activities_template;
		return $activities_template->activity->id;
	}
}

if ( ! function_exists( 'get_current_blog_id' ) ) {
	function get_current_blog_id() {
		return (int) ( $GLOBALS['_hc_mock']['current_blog_id'] ?? 1 );
	}
}

if ( ! function_exists( 'switch_to_blog' ) ) {
	function switch_to_blog( $blog_id ) {
		$GLOBALS['_hc_mock']['blog_stack'][]    = get_current_blog_id();
		$GLOBALS['_hc_mock']['current_blog_id'] = (int) $blog_id;
		return true;
	}
}

if ( ! function_exists( 'restore_current_blog' ) ) {
	function restore_current_blog() {
		if ( empty( $GLOBALS['_hc_mock']['blog_stack'] ) ) {
			return false;
		}
		$GLOBALS['_hc_mock']['current_blog_id'] = array_pop( $GLOBALS['_hc_mock']['blog_stack'] );
		return true;
	}
}

if ( ! function_exists( 'bp_activity_get_meta' ) ) {
	function bp_activity_get_meta( $activity_id, $meta_key = '', $single = false ) {
		return $GLOBALS['_hc_mock']['activity_meta'][ $activity_id ][ $meta_key ] ?? '';
	}
}

if ( ! function_exists( 'bbp_get_forum_permalink' ) ) {
	/**
	 * Permalink depends on which blog is current, as get_permalink() would.
	 */
	function bbp_get_forum_permalink( $forum_id = 0 ) {
		$link = $GLOBALS['_hc_mock']['forum_permalinks'][ get_current_blog_id() ][ $forum_id ] ?? '';
		return apply_filters( 'bbp_get_forum_permalink', $link, $forum_id );
	}
}

if ( ! function_exists( 'bbp_get_topic_permalink' ) ) {
	function bbp_get_topic_permalink( $topic_id = 0 ) {
		$link = $GLOBALS['_hc_mock']['topic_permalinks'][ get_current_blog_id() ][ $topic_id ] ?? '';
		return apply_filters( 'bbp_get_topic_permalink', $link, $topic_id );
	}
}
