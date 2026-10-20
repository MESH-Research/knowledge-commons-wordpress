<?php
/**
 * Tests for the bulk forum-visibility replacement of bbPress's per-forum
 * capability checks on BuddyPress activity queries (issue #158).
 */

require_once __DIR__ . '/forum-visibility-loader.php';

use PHPUnit\Framework\TestCase;

class ForumVisibilityBulkTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_hc_mock'] = array();
		hc_custom_reset_forum_visibility_cache();
	}

	/** A plain, active, non-moderating registered user with the given memberships. */
	private function user( $id = 5, array $overrides = array() ) {
		return array_merge( array(
			'id'               => $id,
			'is_inactive'      => false,
			'is_super_admin'   => false,
			'is_keymaster'     => false,
			'can_moderate'     => false,
			'can_read_private' => false,
			'can_read_hidden'  => false,
			'member_group_ids' => array(),
			'banned_group_ids' => array(),
		), $overrides );
	}

	private function forum( $parent = 0, $status = 'private', array $group_ids = array(), $author = 1, array $moderator_ids = array() ) {
		return array(
			'parent'        => $parent,
			'status'        => $status,
			'author'        => $author,
			'group_ids'     => $group_ids,
			'moderator_ids' => $moderator_ids,
		);
	}

	// --- hc_custom_compute_unreadable_forum_ids -----------------------------------

	public function test_non_member_cannot_read_private_group_forum() {
		$forums = array( 100 => $this->forum( 1, 'private', array( 10 ) ) );
		$this->assertSame( array( 100 ), hc_custom_compute_unreadable_forum_ids( $forums, $this->user() ) );
	}

	public function test_member_can_read_private_group_forum() {
		$forums = array( 100 => $this->forum( 1, 'private', array( 10 ) ) );
		$user   = $this->user( 5, array( 'member_group_ids' => array( 10 ) ) );
		$this->assertSame( array(), hc_custom_compute_unreadable_forum_ids( $forums, $user ) );
	}

	public function test_member_of_any_attached_group_can_read() {
		$forums = array( 100 => $this->forum( 1, 'hidden', array( 10, 11 ) ) );
		$user   = $this->user( 5, array( 'member_group_ids' => array( 11 ) ) );
		$this->assertSame( array(), hc_custom_compute_unreadable_forum_ids( $forums, $user ) );
	}

	public function test_banned_member_cannot_read_group_forum() {
		$forums = array( 100 => $this->forum( 1, 'private', array( 10 ) ) );
		$user   = $this->user( 5, array( 'member_group_ids' => array( 10 ), 'banned_group_ids' => array( 10 ) ) );
		$this->assertSame( array( 100 ), hc_custom_compute_unreadable_forum_ids( $forums, $user ) );
	}

	public function test_read_private_forums_cap_does_not_open_group_forum_to_non_member() {
		$forums = array( 100 => $this->forum( 1, 'private', array( 10 ) ) );
		$user   = $this->user( 5, array( 'can_read_private' => true ) );
		$this->assertSame( array( 100 ), hc_custom_compute_unreadable_forum_ids( $forums, $user ) );
	}

	public function test_keymaster_reads_everything() {
		$forums = array(
			100 => $this->forum( 1, 'private', array( 10 ) ),
			101 => $this->forum( 1, 'hidden' ),
		);
		$user = $this->user( 5, array( 'is_keymaster' => true ) );
		$this->assertSame( array(), hc_custom_compute_unreadable_forum_ids( $forums, $user ) );
	}

	public function test_super_admin_reads_everything() {
		$forums = array( 100 => $this->forum( 1, 'private', array( 10 ) ) );
		$user   = $this->user( 5, array( 'is_super_admin' => true ) );
		$this->assertSame( array(), hc_custom_compute_unreadable_forum_ids( $forums, $user ) );
	}

	public function test_global_moderator_reads_everything() {
		$forums = array( 100 => $this->forum( 1, 'hidden', array( 10 ) ) );
		$user   = $this->user( 5, array( 'can_moderate' => true ) );
		$this->assertSame( array(), hc_custom_compute_unreadable_forum_ids( $forums, $user ) );
	}

	public function test_per_forum_moderator_reads_that_forum_only() {
		$forums = array(
			100 => $this->forum( 1, 'private', array( 10 ), 1, array( 5 ) ),
			101 => $this->forum( 1, 'private', array( 11 ) ),
		);
		$this->assertSame( array( 101 ), hc_custom_compute_unreadable_forum_ids( $forums, $this->user() ) );
	}

	public function test_inactive_user_cannot_read_restricted_forums_even_as_member() {
		$forums = array( 100 => $this->forum( 1, 'private', array( 10 ) ) );
		$user   = $this->user( 5, array( 'is_inactive' => true, 'member_group_ids' => array( 10 ) ) );
		$this->assertSame( array( 100 ), hc_custom_compute_unreadable_forum_ids( $forums, $user ) );
	}

	public function test_public_descendant_of_unreadable_forum_is_unreadable() {
		$forums = array(
			100 => $this->forum( 1, 'private', array( 10 ) ),
			200 => $this->forum( 100, 'publish' ),
			300 => $this->forum( 200, 'publish' ),
		);
		$this->assertSame( array( 100, 200, 300 ), hc_custom_compute_unreadable_forum_ids( $forums, $this->user() ) );
	}

	public function test_descendant_of_readable_restricted_forum_is_readable() {
		$forums = array(
			100 => $this->forum( 1, 'private', array( 10 ) ),
			200 => $this->forum( 100, 'publish' ),
		);
		$user = $this->user( 5, array( 'member_group_ids' => array( 10 ) ) );
		$this->assertSame( array(), hc_custom_compute_unreadable_forum_ids( $forums, $user ) );
	}

	public function test_moderator_of_child_reads_child_under_unreadable_parent() {
		$forums = array(
			100 => $this->forum( 1, 'private', array( 10 ) ),
			200 => $this->forum( 100, 'private', array( 10 ), 1, array( 5 ) ),
		);
		$this->assertSame( array( 100 ), hc_custom_compute_unreadable_forum_ids( $forums, $this->user() ) );
	}

	public function test_restricted_child_group_forum_under_readable_parent_still_needs_membership() {
		$forums = array(
			100 => $this->forum( 1, 'publish' ),
			200 => $this->forum( 100, 'private', array( 12 ) ),
		);
		$this->assertSame( array( 200 ), hc_custom_compute_unreadable_forum_ids( $forums, $this->user() ) );
	}

	public function test_non_group_private_forum_readable_by_author() {
		$forums = array( 100 => $this->forum( 1, 'private', array(), 5 ) );
		$this->assertSame( array(), hc_custom_compute_unreadable_forum_ids( $forums, $this->user( 5 ) ) );
	}

	public function test_non_group_private_forum_readable_with_read_private_forums_cap() {
		$forums = array( 100 => $this->forum( 1, 'private' ), 101 => $this->forum( 1, 'hidden' ) );
		$user   = $this->user( 5, array( 'can_read_private' => true ) );
		$this->assertSame( array( 101 ), hc_custom_compute_unreadable_forum_ids( $forums, $user ) );
	}

	public function test_non_group_hidden_forum_readable_with_read_hidden_forums_cap() {
		$forums = array( 100 => $this->forum( 1, 'private' ), 101 => $this->forum( 1, 'hidden' ) );
		$user   = $this->user( 5, array( 'can_read_hidden' => true ) );
		$this->assertSame( array( 100 ), hc_custom_compute_unreadable_forum_ids( $forums, $user ) );
	}

	public function test_non_group_restricted_forum_unreadable_by_plain_user() {
		$forums = array( 100 => $this->forum( 1, 'private' ) );
		$this->assertSame( array( 100 ), hc_custom_compute_unreadable_forum_ids( $forums, $this->user() ) );
	}

	public function test_anonymous_visitor_cannot_read_any_restricted_forum() {
		$forums = array(
			100 => $this->forum( 1, 'private', array( 10 ) ),
			101 => $this->forum( 1, 'hidden' ),
			102 => $this->forum( 1, 'publish' ),
		);
		$this->assertSame( array( 100, 101 ), hc_custom_compute_unreadable_forum_ids( $forums, $this->user( 0 ) ) );
	}

	public function test_public_forums_are_never_excluded() {
		$forums = array( 102 => $this->forum( 1, 'publish', array( 10 ) ) );
		$this->assertSame( array(), hc_custom_compute_unreadable_forum_ids( $forums, $this->user() ) );
	}

	public function test_result_is_sorted_unique_ints() {
		$forums = array(
			300 => $this->forum( 1, 'private' ),
			100 => $this->forum( 1, 'hidden' ),
			200 => $this->forum( 300, 'publish' ),
		);
		$this->assertSame( array( 100, 200, 300 ), hc_custom_compute_unreadable_forum_ids( $forums, $this->user() ) );
	}

	// --- query shape helpers --------------------------------------------------------

	public function test_query_without_filter_may_include_forum_activity() {
		$this->assertTrue( hc_custom_activity_query_can_include_forum_activity( array() ) );
	}

	public function test_query_filtered_to_non_forum_actions_is_skipped() {
		$args = array( 'filter' => array( 'action' => 'activity_update,new_group_deposit' ) );
		$this->assertFalse( hc_custom_activity_query_can_include_forum_activity( $args ) );
	}

	public function test_query_filtered_to_topic_action_is_included() {
		$args = array( 'filter' => array( 'action' => array( 'bbp_topic_create' ) ) );
		$this->assertTrue( hc_custom_activity_query_can_include_forum_activity( $args ) );
	}

	public function test_query_filtered_to_blogs_object_is_skipped() {
		$args = array( 'filter' => array( 'object' => 'blogs' ) );
		$this->assertFalse( hc_custom_activity_query_can_include_forum_activity( $args ) );
	}

	public function test_query_filtered_to_groups_object_is_included() {
		$args = array( 'filter' => array( 'object' => 'groups' ) );
		$this->assertTrue( hc_custom_activity_query_can_include_forum_activity( $args ) );
	}

	public function test_single_item_rest_lookup_is_detected() {
		$args = array( 'show_hidden' => true, 'display_comments' => 'stream', 'per_page' => null, 'in' => 42 );
		$this->assertTrue( hc_custom_activity_is_single_lookup( $args ) );
		$this->assertFalse( hc_custom_activity_is_single_lookup( array( 'in' => '42,43', 'show_hidden' => true, 'display_comments' => 'stream' ) ) );
	}

	// --- where-conditions callback ------------------------------------------------

	public function test_no_restricted_posts_leaves_conditions_unchanged() {
		$where = array( 'spam_sql' => 'a.is_spam = 0' );
		$out   = hc_custom_bbp_activity_where_conditions( $where, array(), array(), array( 'forum' => array(), 'topic' => array(), 'reply' => array() ) );
		$this->assertSame( $where, $out );
	}

	public function test_unreadable_forums_add_topic_and_reply_conditions() {
		$out = hc_custom_bbp_activity_where_conditions( array(), array(), array( 100, 200 ), array( 'forum' => array(), 'topic' => array(), 'reply' => array() ) );
		$this->assertArrayHasKey( 'bbpress_topic_visibility', $out );
		$this->assertArrayHasKey( 'bbpress_reply_visibility', $out );
		$this->assertStringContainsString( 'post_parent IN (100, 200)', $out['bbpress_topic_visibility'] );
		$this->assertStringContainsString( 'bbp_reply_topic.post_parent IN (100, 200)', $out['bbpress_reply_visibility'] );
		$this->assertStringContainsString( "a.type = 'bbp_topic_create'", $out['bbpress_topic_visibility'] );
		$this->assertStringContainsString( "a.type = 'bbp_reply_create'", $out['bbpress_reply_visibility'] );
		$this->assertStringContainsString( "a.component = 'groups'", $out['bbpress_topic_visibility'] );
		$this->assertStringContainsString( 'a.secondary_item_id', $out['bbpress_topic_visibility'] );
	}

	public function test_password_protected_topic_and_reply_ids_are_restricted() {
		$password = array( 'forum' => array( 7 ), 'topic' => array( 55 ), 'reply' => array( 66 ) );
		$out      = hc_custom_bbp_activity_where_conditions( array(), array(), array(), $password );
		$this->assertStringContainsString( 'bbp_topic.post_parent IN (7)', $out['bbpress_topic_visibility'] );
		$this->assertStringContainsString( 'bbp_topic.ID IN (55)', $out['bbpress_topic_visibility'] );
		$this->assertStringContainsString( 'bbp_reply.ID IN (66)', $out['bbpress_reply_visibility'] );
	}

	public function test_single_item_lookup_is_not_filtered() {
		$args = array( 'show_hidden' => true, 'display_comments' => 'stream', 'in' => 42 );
		$out  = hc_custom_bbp_activity_where_conditions( array(), $args, array( 100 ), array( 'forum' => array(), 'topic' => array(), 'reply' => array() ) );
		$this->assertSame( array(), $out );
	}

	public function test_non_forum_query_is_not_filtered() {
		$args = array( 'filter' => array( 'action' => 'activity_update' ) );
		$out  = hc_custom_bbp_activity_where_conditions( array(), $args, array( 100 ), array( 'forum' => array(), 'topic' => array(), 'reply' => array() ) );
		$this->assertSame( array(), $out );
	}

	public function test_forum_ids_are_cast_to_integers_in_sql() {
		$out = hc_custom_bbp_activity_where_conditions( array(), array(), array( '100', '2; DROP TABLE x' ), array( 'forum' => array(), 'topic' => array(), 'reply' => array() ) );
		$this->assertStringContainsString( 'IN (100, 2)', $out['bbpress_topic_visibility'] );
		$this->assertStringNotContainsString( 'DROP', $out['bbpress_topic_visibility'] );
	}

	// --- excluded-forum-ids filter --------------------------------------------------

	public function test_exclude_filter_returns_bulk_set_for_given_user() {
		$out = hc_custom_exclude_group_forum_ids( array( 999 ), 5, array( 100, 200 ) );
		$this->assertSame( array( 100, 200 ), $out );
	}

	public function test_exclude_filter_drops_core_ids_the_user_can_read() {
		// Core lists every restricted forum for users without read caps; the bulk
		// set decides which the user can actually read, so core ids not in it go.
		$out = hc_custom_exclude_group_forum_ids( array( 100, 300 ), 5, array( 300 ) );
		$this->assertSame( array( 300 ), $out );
	}
}
