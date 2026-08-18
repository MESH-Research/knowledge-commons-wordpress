<?php
/**
 * Stubs for WP/BP/bbPress functions used by the group permissions code under
 * test, then loads the real plugin files:
 *   - plugins/hc-custom/includes/bbpress.php
 *   - plugins/hc-custom/includes/bp-event-organiser.php
 *
 * All stubs are configurable per-test through $GLOBALS['_hc_mock'] so tests can
 * exercise behaviour (return values) without any live WordPress environment.
 */

// ---------------------------------------------------------------------------
// Generic WP plugin API stubs.
// ---------------------------------------------------------------------------

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['_hc_registered_filters'][ $hook ][] = array(
			'callback' => $callback,
			'priority' => $priority,
		);
		return true;
	}
}

if ( ! function_exists( 'remove_filter' ) ) {
	function remove_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		return true;
	}
}

if ( ! function_exists( 'remove_action' ) ) {
	function remove_action( $hook, $callback, $priority = 10 ) {
		return true;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value ) {
		return $value;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( '_x' ) ) {
	function _x( $text, $context, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $value ) {
		return rtrim( $value, '/' ) . '/';
	}
}

// ---------------------------------------------------------------------------
// Mock helper.
// ---------------------------------------------------------------------------

/**
 * Read a value from the per-test mock configuration.
 */
function _hc_mock( $key, $default = null ) {
	return isset( $GLOBALS['_hc_mock'][ $key ] ) ? $GLOBALS['_hc_mock'][ $key ] : $default;
}

// ---------------------------------------------------------------------------
// WordPress core stubs.
// ---------------------------------------------------------------------------

if ( ! function_exists( 'get_post' ) ) {
	function get_post( $post_id = null ) {
		$posts = _hc_mock( 'posts', array() );
		return isset( $posts[ $post_id ] ) ? $posts[ $post_id ] : null;
	}
}

if ( ! function_exists( 'is_super_admin' ) ) {
	function is_super_admin( $user_id = false ) {
		return (bool) _hc_mock( 'is_super_admin', false );
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return (int) _hc_mock( 'current_user_id', 0 );
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $cap, ...$args ) {
		$can = _hc_mock( 'user_can', array() );
		return ! empty( $can[ $cap ] );
	}
}

// ---------------------------------------------------------------------------
// BuddyPress stubs.
// ---------------------------------------------------------------------------

if ( ! function_exists( 'bp_is_group' ) ) {
	function bp_is_group() {
		// Shared across suites: falls back to the hc_test store used by the
		// group-nav loader when no _hc_mock value is configured.
		return (bool) _hc_mock( 'is_group', ! empty( $GLOBALS['hc_test']['is_group'] ) );
	}
}

if ( ! function_exists( 'bp_get_current_group_id' ) ) {
	function bp_get_current_group_id() {
		return (int) _hc_mock( 'current_group_id', 0 );
	}
}

if ( ! function_exists( 'bp_loggedin_user_id' ) ) {
	function bp_loggedin_user_id() {
		// Shared across suites: falls back to the hc_test store used by the
		// group-nav loader when no _hc_mock value is configured.
		return (int) _hc_mock( 'current_user_id', $GLOBALS['hc_test']['loggedin_user'] ?? 0 );
	}
}

if ( ! function_exists( 'groups_is_user_admin' ) ) {
	function groups_is_user_admin( $user_id, $group_id ) {
		return in_array( "$user_id:$group_id", _hc_mock( 'group_admins', array() ), true );
	}
}

if ( ! function_exists( 'groups_is_user_mod' ) ) {
	function groups_is_user_mod( $user_id, $group_id ) {
		return in_array( "$user_id:$group_id", _hc_mock( 'group_mods', array() ), true );
	}
}

if ( ! function_exists( 'groups_is_user_member' ) ) {
	function groups_is_user_member( $user_id, $group_id ) {
		// Shared across suites: falls back to the hc_test store used by the
		// group-nav loader when no _hc_mock value is configured.
		if ( isset( $GLOBALS['_hc_mock']['group_members'] ) ) {
			return in_array( "$user_id:$group_id", $GLOBALS['_hc_mock']['group_members'], true );
		}
		return ! empty( $GLOBALS['hc_test']['memberships'][ "$user_id:$group_id" ] );
	}
}

if ( ! function_exists( 'groups_is_user_banned' ) ) {
	function groups_is_user_banned( $user_id, $group_id ) {
		return in_array( "$user_id:$group_id", _hc_mock( 'group_banned', array() ), true );
	}
}

if ( ! function_exists( 'groups_get_user_groups' ) ) {
	function groups_get_user_groups( $user_id ) {
		$user_groups = _hc_mock( 'user_groups', array() );
		return array(
			'groups' => isset( $user_groups[ $user_id ] ) ? $user_groups[ $user_id ] : array(),
		);
	}
}

if ( ! function_exists( 'groups_get_current_group' ) ) {
	function groups_get_current_group() {
		// Shared across suites: falls back to the hc_test store used by the
		// group-nav loader when no _hc_mock value is configured.
		return _hc_mock( 'current_group', $GLOBALS['hc_test']['current_group'] ?? null );
	}
}

if ( ! function_exists( 'bp_get_group_permalink' ) ) {
	function bp_get_group_permalink( $group = null ) {
		return _hc_mock( 'group_permalink', 'https://example.org/groups/test-group/' );
	}
}

if ( ! function_exists( 'bp_core_new_subnav_item' ) ) {
	function bp_core_new_subnav_item( $args, $component = null ) {
		$GLOBALS['_hc_registered_subnav'][] = array(
			'args'      => $args,
			'component' => $component,
		);
		return true;
	}
}

if ( ! class_exists( 'HC_Test_Group_Nav' ) ) {
	class HC_Test_Group_Nav {
		public function get_secondary( $args = array(), $sort = true ) {
			return _hc_mock( 'existing_secondary_nav', array() );
		}
	}
}

if ( ! function_exists( 'buddypress' ) ) {
	function buddypress() {
		$bp                           = new stdClass();
		$bp->groups                   = new stdClass();
		$bp->groups->nav              = new HC_Test_Group_Nav();
		$bp->groups->current_group    = _hc_mock( 'current_group', null );
		return $bp;
	}
}

// ---------------------------------------------------------------------------
// bbPress stubs.
// ---------------------------------------------------------------------------

if ( ! function_exists( 'bbp_get_forum_post_type' ) ) {
	function bbp_get_forum_post_type() {
		return 'forum';
	}
}

if ( ! function_exists( 'bbp_get_topic_post_type' ) ) {
	function bbp_get_topic_post_type() {
		return 'topic';
	}
}

if ( ! function_exists( 'bbp_get_reply_post_type' ) ) {
	function bbp_get_reply_post_type() {
		return 'reply';
	}
}

if ( ! function_exists( 'bbp_get_topic_forum_id' ) ) {
	function bbp_get_topic_forum_id( $topic_id ) {
		$map = _hc_mock( 'topic_forums', array() );
		return isset( $map[ $topic_id ] ) ? $map[ $topic_id ] : 0;
	}
}

if ( ! function_exists( 'bbp_get_reply_forum_id' ) ) {
	function bbp_get_reply_forum_id( $reply_id ) {
		$map = _hc_mock( 'reply_forums', array() );
		return isset( $map[ $reply_id ] ) ? $map[ $reply_id ] : 0;
	}
}

if ( ! function_exists( 'bbp_get_forum_group_ids' ) ) {
	function bbp_get_forum_group_ids( $forum_id ) {
		$map = _hc_mock( 'forum_groups', array() );
		return isset( $map[ $forum_id ] ) ? $map[ $forum_id ] : array();
	}
}

if ( ! function_exists( 'bbp_get_topic_id' ) ) {
	function bbp_get_topic_id( $topic_id = 0 ) {
		return (int) _hc_mock( 'topic_id', 0 );
	}
}

if ( ! function_exists( 'bbp_get_topic_author_id' ) ) {
	function bbp_get_topic_author_id( $topic_id = 0 ) {
		return (int) _hc_mock( 'topic_author_id', 0 );
	}
}

if ( ! function_exists( 'bbp_get_reply_id' ) ) {
	function bbp_get_reply_id( $reply_id = 0 ) {
		return (int) _hc_mock( 'reply_id', 0 );
	}
}

if ( ! function_exists( 'bbp_get_reply_author_id' ) ) {
	function bbp_get_reply_author_id( $reply_id = 0 ) {
		return (int) _hc_mock( 'reply_author_id', 0 );
	}
}

// ---------------------------------------------------------------------------
// hc-custom / MLA stubs.
// ---------------------------------------------------------------------------

if ( ! function_exists( 'mla_is_group_committee' ) ) {
	function mla_is_group_committee( $group_id ) {
		return in_array( $group_id, _hc_mock( 'committees', array() ), true );
	}
}

// ---------------------------------------------------------------------------
// BP Event Organiser stubs.
// ---------------------------------------------------------------------------

if ( ! function_exists( 'bpeo_get_event_groups' ) ) {
	function bpeo_get_event_groups( $event_id ) {
		$map = _hc_mock( 'event_groups', array() );
		return isset( $map[ $event_id ] ) ? $map[ $event_id ] : array();
	}
}

if ( ! function_exists( 'bpeo_get_group_minimum_member_role_for_connection' ) ) {
	function bpeo_get_group_minimum_member_role_for_connection( $group_id ) {
		$map = _hc_mock( 'group_min_role', array() );
		return isset( $map[ $group_id ] ) ? $map[ $group_id ] : 'member';
	}
}

if ( ! function_exists( 'bpeo_get_group_permalink' ) ) {
	function bpeo_get_group_permalink( $group = null ) {
		return _hc_mock( 'events_group_permalink', 'https://example.org/groups/test-group/events/' );
	}
}

if ( ! function_exists( 'bpeo_get_events_slug' ) ) {
	function bpeo_get_events_slug() {
		return 'events';
	}
}

if ( ! function_exists( 'bpeo_get_events_new_slug' ) ) {
	function bpeo_get_events_new_slug() {
		return 'new';
	}
}

// ---------------------------------------------------------------------------
// Load the real code under test.
// ---------------------------------------------------------------------------

require_once dirname( __DIR__, 2 ) . '/plugins/hc-custom/includes/bbpress.php';
require_once dirname( __DIR__, 2 ) . '/plugins/hc-custom/includes/bp-event-organiser.php';
