<?php
/**
 * Tests for hc-custom's Group Email Subscription integration when the BPGES
 * plugin is unavailable.
 *
 * The integration file is loaded unconditionally by hc-custom, but several
 * of its callbacks run on core BuddyPress hooks (joining a group, accepting
 * an invitation, saving notification settings) and call ass_*() functions
 * that only exist while BPGES is active. Without guards these calls fatal
 * mid-request — the "critical error on join" from issue #101.
 *
 * NOTE: test order matters. The "BPGES absent" tests must run before any
 * test defines the ass_*() stubs, because PHP functions cannot be undefined.
 */

use PHPUnit\Framework\TestCase;

class GesGuardTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_mock_user_meta']         = array();
		$GLOBALS['_user_meta_updates']      = array();
		$GLOBALS['_bp_messages']            = array();
		$GLOBALS['_hc_mock']                = array( 'current_user_id' => 2 );
		$GLOBALS['_ass_subscription_calls'] = array();
	}

	protected function tearDown(): void {
		unset(
			$GLOBALS['_mock_user_meta'],
			$GLOBALS['_user_meta_updates'],
			$GLOBALS['_bp_messages'],
			$GLOBALS['_hc_mock'],
			$GLOBALS['_ass_subscription_calls']
		);
	}

	/* ---- BPGES absent -------------------------------------------------- */

	public function test_join_message_without_ges_does_not_fatal() {
		$this->assertFalse( function_exists( 'ass_group_subscription' ), 'precondition: BPGES absent' );

		hc_custom_join_group_message( 1, 2 );

		$this->assertTrue( true, 'joining a group must not fatal when BPGES is absent' );
	}

	public function test_accept_invite_without_ges_does_not_fatal() {
		$this->assertFalse( function_exists( 'ass_group_subscription' ), 'precondition: BPGES absent' );

		hc_custom_set_notifications_on_accept_invite_or_request( 2, 1 );

		$this->assertTrue( true, 'accepting an invite must not fatal when BPGES is absent' );
	}

	public function test_settings_save_without_ges_does_not_fatal() {
		$this->assertFalse( function_exists( 'ass_group_subscription' ), 'precondition: BPGES absent' );

		$_POST['group-notifications'] = array( 1 => 'sub' );
		$GLOBALS['_hc_mock']['is_settings_component'] = true;

		try {
			hc_custom_update_group_subscribe_settings();
			$this->assertTrue( true );
		} finally {
			unset( $_POST['group-notifications'], $GLOBALS['_hc_mock']['is_settings_component'] );
		}
	}

	public function test_settings_screen_sections_without_ges_do_not_fatal() {
		$this->assertFalse( function_exists( 'ass_get_forum_type' ), 'precondition: BPGES absent' );

		ob_start();
		try {
			hc_custom_default_group_forum_subscription_settings();
			hc_custom_general_group_settings();
		} finally {
			ob_end_clean();
		}

		$this->assertTrue( true, 'settings screen must not fatal when BPGES is absent' );
	}

	/* ---- BPGES present -------------------------------------------------
	 * These run last: they define the ass_*() stubs, after which the
	 * "absent" preconditions above can no longer hold in this process.
	 */

	public function test_join_message_with_ges_subscribes_user_with_their_default() {
		require_once __DIR__ . '/ges-function-stubs.php';

		$GLOBALS['_mock_user_meta'][2]['default_group_notifications'] = 'sum';

		hc_custom_join_group_message( 1, 2 );

		$this->assertSame(
			array( array( 'status' => 'sum', 'user_id' => 2, 'group_id' => 1 ) ),
			$GLOBALS['_ass_subscription_calls'],
			'with BPGES active the member must be subscribed with their default status'
		);
	}

	public function test_accept_invite_with_ges_subscribes_user() {
		require_once __DIR__ . '/ges-function-stubs.php';

		hc_custom_set_notifications_on_accept_invite_or_request( 2, 1 );

		$this->assertSame(
			array( array( 'status' => 'no', 'user_id' => 2, 'group_id' => 1 ) ),
			$GLOBALS['_ass_subscription_calls'],
			'with BPGES active an accepted invite must subscribe the member'
		);
	}
}
