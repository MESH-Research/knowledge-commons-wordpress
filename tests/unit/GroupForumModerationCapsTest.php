<?php
/**
 * Tests for hc_custom_map_group_forum_meta_caps(): group admins and mods must
 * regain bbPress moderation capabilities on their group's forum content.
 */

use PHPUnit\Framework\TestCase;

class GroupForumModerationCapsTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_hc_mock'] = array();
	}

	/**
	 * Configure a topic (id 200) living in forum 100 attached to group 10.
	 */
	private function setUpGroupTopic( $topic_id = 200, $forum_id = 100, $group_id = 10 ) {
		$topic            = new stdClass();
		$topic->ID        = $topic_id;
		$topic->post_type = 'topic';

		$GLOBALS['_hc_mock']['posts']        = array( $topic_id => $topic );
		$GLOBALS['_hc_mock']['topic_forums'] = array( $topic_id => $forum_id );
		$GLOBALS['_hc_mock']['forum_groups'] = array( $forum_id => array( $group_id ) );
	}

	public function test_group_admin_can_edit_topic_in_group_forum() {
		$this->setUpGroupTopic( 200, 100, 10 );
		$GLOBALS['_hc_mock']['group_admins'] = array( '5:10' );

		$caps = hc_custom_map_group_forum_meta_caps( array( 'edit_others_topics' ), 'edit_topic', 5, array( 200 ) );

		$this->assertSame( array( 'participate' ), $caps );
	}

	public function test_group_mod_can_moderate_topic_in_group_forum() {
		$this->setUpGroupTopic( 201, 101, 11 );
		$GLOBALS['_hc_mock']['group_mods'] = array( '6:11' );

		$caps = hc_custom_map_group_forum_meta_caps( array( 'moderate' ), 'moderate', 6, array( 201 ) );

		$this->assertSame( array( 'participate' ), $caps );
	}

	public function test_group_admin_can_delete_topic_in_group_forum() {
		$this->setUpGroupTopic( 202, 102, 12 );
		$GLOBALS['_hc_mock']['group_admins'] = array( '7:12' );

		$caps = hc_custom_map_group_forum_meta_caps( array( 'delete_others_topics' ), 'delete_topic', 7, array( 202 ) );

		$this->assertSame( array( 'participate' ), $caps );
	}

	public function test_group_mod_cannot_delete_topic_in_group_forum() {
		$this->setUpGroupTopic( 203, 103, 13 );
		$GLOBALS['_hc_mock']['group_mods'] = array( '8:13' );

		$caps = hc_custom_map_group_forum_meta_caps( array( 'delete_others_topics' ), 'delete_topic', 8, array( 203 ) );

		$this->assertSame( array( 'delete_others_topics' ), $caps );
	}

	public function test_plain_member_does_not_get_moderation_caps() {
		$this->setUpGroupTopic( 204, 104, 14 );
		$GLOBALS['_hc_mock']['group_members'] = array( '9:14' );

		$caps = hc_custom_map_group_forum_meta_caps( array( 'edit_others_topics' ), 'edit_topic', 9, array( 204 ) );

		$this->assertSame( array( 'edit_others_topics' ), $caps );
	}

	public function test_group_member_can_publish_topics() {
		$this->setUpGroupTopic( 205, 105, 15 );
		$GLOBALS['_hc_mock']['group_members'] = array( '20:15' );

		$caps = hc_custom_map_group_forum_meta_caps( array( 'publish_topics' ), 'publish_topics', 20, array( 205 ) );

		$this->assertSame( array( 'participate' ), $caps );
	}

	public function test_banned_member_is_denied_publishing() {
		$this->setUpGroupTopic( 206, 106, 16 );
		$GLOBALS['_hc_mock']['group_members'] = array( '21:16' );
		$GLOBALS['_hc_mock']['group_banned']  = array( '21:16' );

		$caps = hc_custom_map_group_forum_meta_caps( array( 'publish_topics' ), 'publish_topics', 21, array( 206 ) );

		$this->assertSame( array( 'do_not_allow' ), $caps );
	}

	public function test_primitive_cap_without_object_uses_current_group_context() {
		// No object id passed: e.g. current_user_can( 'edit_others_topics' ).
		$GLOBALS['_hc_mock']['is_group']         = true;
		$GLOBALS['_hc_mock']['current_group_id'] = 17;
		$GLOBALS['_hc_mock']['group_admins']     = array( '22:17' );

		$caps = hc_custom_map_group_forum_meta_caps( array( 'edit_others_topics' ), 'edit_others_topics', 22, array() );

		$this->assertSame( array( 'participate' ), $caps );
	}

	public function test_primitive_cap_without_object_outside_group_context_is_unchanged() {
		$GLOBALS['_hc_mock']['is_group']     = false;
		$GLOBALS['_hc_mock']['group_admins'] = array( '23:18' );

		$caps = hc_custom_map_group_forum_meta_caps( array( 'edit_others_topics' ), 'edit_others_topics', 23, array() );

		$this->assertSame( array( 'edit_others_topics' ), $caps );
	}

	public function test_topic_in_non_group_forum_is_unchanged() {
		$topic            = new stdClass();
		$topic->ID        = 207;
		$topic->post_type = 'topic';

		$GLOBALS['_hc_mock']['posts']        = array( 207 => $topic );
		$GLOBALS['_hc_mock']['topic_forums'] = array( 207 => 107 );
		$GLOBALS['_hc_mock']['forum_groups'] = array( 107 => array() );
		$GLOBALS['_hc_mock']['group_admins'] = array( '24:19' );

		$caps = hc_custom_map_group_forum_meta_caps( array( 'edit_others_topics' ), 'edit_topic', 24, array( 207 ) );

		$this->assertSame( array( 'edit_others_topics' ), $caps );
	}

	public function test_unrelated_cap_is_unchanged() {
		$this->setUpGroupTopic( 208, 108, 25 );
		$GLOBALS['_hc_mock']['group_admins'] = array( '26:25' );

		$caps = hc_custom_map_group_forum_meta_caps( array( 'spectate' ), 'spectate', 26, array( 208 ) );

		$this->assertSame( array( 'spectate' ), $caps );
	}

	public function test_group_admin_can_edit_reply_in_group_forum() {
		$reply            = new stdClass();
		$reply->ID        = 300;
		$reply->post_type = 'reply';

		$GLOBALS['_hc_mock']['posts']        = array( 300 => $reply );
		$GLOBALS['_hc_mock']['reply_forums'] = array( 300 => 109 );
		$GLOBALS['_hc_mock']['forum_groups'] = array( 109 => array( 27 ) );
		$GLOBALS['_hc_mock']['group_admins'] = array( '28:27' );

		$caps = hc_custom_map_group_forum_meta_caps( array( 'edit_others_replies' ), 'edit_reply', 28, array( 300 ) );

		$this->assertSame( array( 'participate' ), $caps );
	}
}
