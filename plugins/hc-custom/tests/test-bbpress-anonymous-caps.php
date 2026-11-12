<?php
/**
 * Behaviour tests for the forum capability mapping in includes/bbpress.php.
 *
 * Run with: php tests/test-bbpress-anonymous-caps.php
 *
 * Framework-free. WordPress, BuddyPress and bbPress are replaced by small
 * stubs so the mapping functions are exercised in isolation.
 *
 * Background: bbPress 2.6.19 checks `read_forum` for every private and hidden
 * forum when it builds activity streams and forum queries. On hcommons.org that
 * is ~2,900 capability checks per request, and each one fell through to
 * BuddyPress group membership lookups even for logged-out visitors, who cannot
 * hold a group role. The mapping must therefore answer anonymous checks without
 * touching group membership while still granting members what they had before.
 *
 * @package Hc_Custom
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals, Squiz.Commenting

$failures = 0;

/**
 * Record a check.
 *
 * @param bool   $ok    Whether it passed.
 * @param string $label What was checked.
 * @param string $extra Detail shown on failure.
 */
function check( $ok, $label, $extra = '' ) {
	global $failures;
	if ( $ok ) {
		echo "PASS: {$label}\n";
	} else {
		$failures++;
		echo "FAIL: {$label}" . ( '' !== $extra ? ": {$extra}" : '' ) . "\n";
	}
}

/* ---------- Fixture ---------- */

// Group 100 owns forum 10 (private). Forum 11 is a public, groupless forum.
// Forum 12 is public itself but sits under private forum 10.
// Topic 20 and reply 30 live in forum 10.
$fixture = array(
	'posts'      => array(
		10 => (object) array( 'ID' => 10, 'post_type' => 'forum' ),
		11 => (object) array( 'ID' => 11, 'post_type' => 'forum' ),
		12 => (object) array( 'ID' => 12, 'post_type' => 'forum' ),
		20 => (object) array( 'ID' => 20, 'post_type' => 'topic' ),
		30 => (object) array( 'ID' => 30, 'post_type' => 'reply' ),
	),
	'topic_forum' => array( 20 => 10 ),
	'reply_forum' => array( 30 => 10 ),
	'forum_groups' => array( 10 => array( 100 ), 11 => array(), 12 => array( 100 ) ),
	// Restricted directly, and restricted only once ancestors are considered.
	'restricted'          => array( 10 ),
	'restricted_ancestor' => array( 10, 12 ),
	// user => group => roles.
	'roles'      => array(
		7 => array( 100 => array( 'member' ) ),
		8 => array( 100 => array( 'member', 'admin' ) ),
		9 => array(),
	),
);

$membership_lookups = 0;
$registered_filters = array();

/* ---------- WordPress / BuddyPress / bbPress stubs ---------- */

function add_filter( $tag, $fn, $priority = 10, $accepted_args = 1 ) {
	global $registered_filters;
	$registered_filters[ $tag ][] = array( 'function' => $fn, 'priority' => $priority );
	return true;
}
function add_action( $tag, $fn, $priority = 10, $accepted_args = 1 ) {
	return add_filter( $tag, $fn, $priority, $accepted_args );
}
function remove_filter( $tag, $fn, $priority = 10 ) {
	return true;
}
function get_post( $id ) {
	global $fixture;
	return isset( $fixture['posts'][ $id ] ) ? $fixture['posts'][ $id ] : null;
}
function bbp_get_forum_post_type() {
	return 'forum';
}
function bbp_get_topic_post_type() {
	return 'topic';
}
function bbp_get_reply_post_type() {
	return 'reply';
}
function bbp_get_forum_id( $id = 0 ) {
	return (int) $id;
}
function bbp_get_topic_forum_id( $id ) {
	global $fixture;
	return isset( $fixture['topic_forum'][ $id ] ) ? $fixture['topic_forum'][ $id ] : 0;
}
function bbp_get_reply_forum_id( $id ) {
	global $fixture;
	return isset( $fixture['reply_forum'][ $id ] ) ? $fixture['reply_forum'][ $id ] : 0;
}
function bbp_get_forum_group_ids( $forum_id ) {
	global $fixture;
	return isset( $fixture['forum_groups'][ $forum_id ] ) ? $fixture['forum_groups'][ $forum_id ] : array();
}
function bbp_is_forum_restricted( $forum_id, $check_ancestors = false ) {
	global $fixture;
	$set = $check_ancestors ? $fixture['restricted_ancestor'] : $fixture['restricted'];
	return in_array( (int) $forum_id, $set, true );
}
function bp_is_group() {
	return false;
}
function bp_get_current_group_id() {
	return 0;
}
function _role( $user_id, $group_id, $role ) {
	global $fixture, $membership_lookups;
	$membership_lookups++;
	return ! empty( $fixture['roles'][ $user_id ][ $group_id ] ) && in_array( $role, $fixture['roles'][ $user_id ][ $group_id ], true );
}
function groups_is_user_member( $user_id, $group_id ) {
	return _role( $user_id, $group_id, 'member' );
}
function groups_is_user_mod( $user_id, $group_id ) {
	return _role( $user_id, $group_id, 'mod' );
}
function groups_is_user_admin( $user_id, $group_id ) {
	return _role( $user_id, $group_id, 'admin' );
}
function groups_is_user_banned( $user_id, $group_id ) {
	return _role( $user_id, $group_id, 'banned' );
}

require_once __DIR__ . '/../includes/bbpress.php';

/* ---------- hc_custom_map_group_forum_meta_caps ---------- */

$base = array( 'read_private_forums' );

$membership_lookups = 0;
$out = hc_custom_map_group_forum_meta_caps( $base, 'read_forum', 0, array( 10 ) );
check( $out === $base, 'anonymous read_forum on a group forum leaves caps unchanged', var_export( $out, true ) );
check( 0 === $membership_lookups, 'anonymous read_forum consults no group membership', "{$membership_lookups} lookups" );

$membership_lookups = 0;
$out = hc_custom_map_group_forum_meta_caps( array( 'moderate' ), 'moderate', 0, array( 10 ) );
check( $out === array( 'moderate' ), 'anonymous moderate on a group forum leaves caps unchanged', var_export( $out, true ) );
check( 0 === $membership_lookups, 'anonymous moderate consults no group membership', "{$membership_lookups} lookups" );

$out = hc_custom_map_group_forum_meta_caps( $base, 'read_forum', 7, array( 10 ) );
check( $out === array( 'participate' ), 'group member may read the group forum', var_export( $out, true ) );

$out = hc_custom_map_group_forum_meta_caps( array( 'do_not_allow' ), 'delete_topic', 8, array( 20 ) );
check( $out === array( 'participate' ), 'group admin may delete a topic in the group forum', var_export( $out, true ) );

$out = hc_custom_map_group_forum_meta_caps( array( 'do_not_allow' ), 'delete_topic', 7, array( 20 ) );
check( $out === array( 'do_not_allow' ), 'plain member may not delete a topic', var_export( $out, true ) );

$out = hc_custom_map_group_forum_meta_caps( $base, 'read_forum', 9, array( 10 ) );
check( $out === $base, 'non-member keeps bbPress default caps', var_export( $out, true ) );

$membership_lookups = 0;
$out = hc_custom_map_group_forum_meta_caps( array( 'edit_posts' ), 'edit_posts', 7, array( 10 ) );
check( $out === array( 'edit_posts' ) && 0 === $membership_lookups, 'unrelated capability is untouched without lookups', var_export( $out, true ) );

/* ---------- hc_custom_deny_anonymous_restricted_forum_caps ---------- */

$out = hc_custom_deny_anonymous_restricted_forum_caps( $base, 'read_forum', 0, array( 10 ) );
check( $out === array( 'do_not_allow' ), 'anonymous is denied read_forum on a private forum', var_export( $out, true ) );

$out = hc_custom_deny_anonymous_restricted_forum_caps( $base, 'read_forum', 0, array( 12 ) );
check( $out === array( 'do_not_allow' ), 'anonymous is denied read_forum on a public forum under a private ancestor', var_export( $out, true ) );

$out = hc_custom_deny_anonymous_restricted_forum_caps( $base, 'read_forum', 0, array( 11 ) );
check( $out === $base, 'anonymous read_forum on a public forum is left to bbPress', var_export( $out, true ) );

$out = hc_custom_deny_anonymous_restricted_forum_caps( $base, 'read_forum', 7, array( 10 ) );
check( $out === $base, 'logged-in read_forum on a private forum is left to bbPress and the group extension', var_export( $out, true ) );

$out = hc_custom_deny_anonymous_restricted_forum_caps( $base, 'read_forum', 0, array() );
check( $out === $base, 'anonymous read_forum without a forum is left alone', var_export( $out, true ) );

$out = hc_custom_deny_anonymous_restricted_forum_caps( $base, 'read_topic', 0, array( 20 ) );
check( $out === $base, 'other capabilities are not touched', var_export( $out, true ) );

/* ---------- Hook placement ---------- */

$deny_priority = null;
foreach ( isset( $registered_filters['bbp_map_meta_caps'] ) ? $registered_filters['bbp_map_meta_caps'] : array() as $hook ) {
	if ( 'hc_custom_deny_anonymous_restricted_forum_caps' === $hook['function'] ) {
		$deny_priority = $hook['priority'];
	}
}
check( null !== $deny_priority, 'denial is hooked to bbp_map_meta_caps' );
// bbPress's own mappers run at 10 and overwrite caps; the group extension runs
// at 20 and preserves an earlier do_not_allow. The denial must sit between them.
check( null !== $deny_priority && $deny_priority > 10 && $deny_priority < 20, 'denial runs after bbPress mappers and before the group extension', var_export( $deny_priority, true ) );

echo $failures ? "\n{$failures} failure(s)\n" : "\nAll checks passed\n";
exit( $failures ? 1 : 0 );
