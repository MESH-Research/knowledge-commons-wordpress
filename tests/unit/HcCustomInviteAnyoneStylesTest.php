<?php
/**
 * Tests for the Invite Anyone member screen stylesheet.
 *
 * The sheet restyles Invite Anyone's Send Invites and Sent Invites screens
 * to match the other BuddyPress Nouveau forms. It must load on those screens
 * and nowhere else.
 */

use PHPUnit\Framework\TestCase;

class HcCustomInviteAnyoneStylesTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_enqueued_styles'] = array();
		unset( $GLOBALS['_mock_current_component'] );
	}

	public function test_the_stylesheet_loads_on_the_invite_anyone_member_screens() {
		$GLOBALS['_mock_current_component'] = 'invite-anyone';

		$this->assertTrue( hc_custom_invite_anyone_enqueue_styles() );

		$srcs = array_column( $GLOBALS['_enqueued_styles'], 'src' );
		$this->assertCount( 1, $srcs );
		$this->assertStringEndsWith( 'hc-custom/includes/css/invite-anyone.css', $srcs[0] );
	}

	public function test_the_stylesheet_is_versioned_so_updates_bust_caches() {
		$GLOBALS['_mock_current_component'] = 'invite-anyone';

		hc_custom_invite_anyone_enqueue_styles();

		$style = reset( $GLOBALS['_enqueued_styles'] );
		$this->assertNotEmpty( $style['ver'] );
		$this->assertNotFalse( $style['ver'] );
	}

	public function test_the_stylesheet_stays_off_other_buddypress_screens() {
		$GLOBALS['_mock_current_component'] = 'activity';

		$this->assertFalse( hc_custom_invite_anyone_enqueue_styles() );
		$this->assertSame( array(), $GLOBALS['_enqueued_styles'] );
	}

	public function test_the_stylesheet_stays_off_non_buddypress_pages() {
		$this->assertFalse( hc_custom_invite_anyone_enqueue_styles() );
		$this->assertSame( array(), $GLOBALS['_enqueued_styles'] );
	}

	public function test_the_stylesheet_file_exists() {
		$this->assertFileExists( dirname( __DIR__, 2 ) . '/plugins/hc-custom/includes/css/invite-anyone.css' );
	}
}
