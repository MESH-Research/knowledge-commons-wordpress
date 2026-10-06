<?php
/**
 * Tests for hcommons_topic_admin_links() and hcommons_reply_admin_links():
 * group admins and mods must keep the full set of moderation links, not just
 * MLA committee admins.
 */

use PHPUnit\Framework\TestCase;

class ForumAdminLinksTest extends TestCase {

	private $links;

	protected function setUp(): void {
		$GLOBALS['_hc_mock'] = array();

		$this->links = array(
			'edit'  => '<a href="#">Edit</a>',
			'close' => '<a href="#">Close</a>',
			'stick' => '<a href="#">Stick</a>',
			'trash' => '<a href="#">Trash</a>',
			'reply' => '<a href="#">Reply</a>',
		);
	}

	public function test_super_admin_keeps_all_topic_links() {
		$GLOBALS['_hc_mock']['is_super_admin'] = true;

		$this->assertSame( $this->links, hcommons_topic_admin_links( $this->links ) );
	}

	public function test_group_admin_keeps_all_topic_links() {
		$GLOBALS['_hc_mock']['current_user_id']  = 5;
		$GLOBALS['_hc_mock']['current_group_id'] = 10;
		$GLOBALS['_hc_mock']['group_admins']     = array( '5:10' );
		$GLOBALS['_hc_mock']['topic_id']         = 200;
		$GLOBALS['_hc_mock']['topic_author_id']  = 99;

		$this->assertSame( $this->links, hcommons_topic_admin_links( $this->links ) );
	}

	public function test_group_mod_keeps_all_topic_links() {
		$GLOBALS['_hc_mock']['current_user_id']  = 6;
		$GLOBALS['_hc_mock']['current_group_id'] = 11;
		$GLOBALS['_hc_mock']['group_mods']       = array( '6:11' );
		$GLOBALS['_hc_mock']['topic_id']         = 201;
		$GLOBALS['_hc_mock']['topic_author_id']  = 99;

		$this->assertSame( $this->links, hcommons_topic_admin_links( $this->links ) );
	}

	public function test_plain_member_loses_edit_link_on_others_topic() {
		$GLOBALS['_hc_mock']['current_user_id']  = 7;
		$GLOBALS['_hc_mock']['current_group_id'] = 12;
		$GLOBALS['_hc_mock']['topic_id']         = 202;
		$GLOBALS['_hc_mock']['topic_author_id']  = 99;

		$expected = $this->links;
		unset( $expected['edit'] );

		$this->assertSame( $expected, hcommons_topic_admin_links( $this->links ) );
	}

	public function test_author_keeps_edit_link_on_own_topic() {
		$GLOBALS['_hc_mock']['current_user_id']  = 8;
		$GLOBALS['_hc_mock']['current_group_id'] = 13;
		$GLOBALS['_hc_mock']['topic_id']         = 203;
		$GLOBALS['_hc_mock']['topic_author_id']  = 8;

		$this->assertSame( $this->links, hcommons_topic_admin_links( $this->links ) );
	}

	public function test_group_admin_keeps_all_reply_links() {
		$GLOBALS['_hc_mock']['current_user_id']  = 9;
		$GLOBALS['_hc_mock']['current_group_id'] = 14;
		$GLOBALS['_hc_mock']['group_admins']     = array( '9:14' );
		$GLOBALS['_hc_mock']['reply_id']         = 300;
		$GLOBALS['_hc_mock']['reply_author_id']  = 99;

		$this->assertSame( $this->links, hcommons_reply_admin_links( $this->links ) );
	}

	public function test_plain_member_loses_edit_link_on_others_reply() {
		$GLOBALS['_hc_mock']['current_user_id']  = 15;
		$GLOBALS['_hc_mock']['current_group_id'] = 16;
		$GLOBALS['_hc_mock']['reply_id']         = 301;
		$GLOBALS['_hc_mock']['reply_author_id']  = 99;

		$expected = $this->links;
		unset( $expected['edit'] );

		$this->assertSame( $expected, hcommons_reply_admin_links( $this->links ) );
	}

	public function test_author_keeps_edit_link_on_own_reply() {
		$GLOBALS['_hc_mock']['current_user_id']  = 17;
		$GLOBALS['_hc_mock']['current_group_id'] = 18;
		$GLOBALS['_hc_mock']['reply_id']         = 302;
		$GLOBALS['_hc_mock']['reply_author_id']  = 17;

		$this->assertSame( $this->links, hcommons_reply_admin_links( $this->links ) );
	}
}
