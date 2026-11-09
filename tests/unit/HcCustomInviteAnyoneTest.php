<?php
/**
 * Tests for the hc-custom Invite Anyone integration.
 *
 * Invite Anyone only turns an email invitation into BuddyPress group invites
 * when BuddyPress activates the new account. Commons accounts are created by
 * the login broker, so that never happens. The integration must instead
 * accept the pending invitations addressed to a user, once, on their first
 * authenticated request on the network where the invitations were sent.
 */

use PHPUnit\Framework\TestCase;

class HcCustomInviteAnyoneTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_hc_mock']            = array();
		$GLOBALS['hc_test']             = array();
		$GLOBALS['_mock_user_meta']     = array();
		$GLOBALS['_mock_user_emails']   = array();
		$GLOBALS['_mock_ia_invites']    = array();
		$GLOBALS['_mock_post_meta']     = array();
		$GLOBALS['_mock_post_terms']    = array();
		$GLOBALS['_mock_group_invites'] = array();
		$GLOBALS['_fired_actions']      = array();
		$GLOBALS['_mock_root_blog_id']  = 7;
		unset( $GLOBALS['_mock_is_root_blog'] );
	}

	/**
	 * Register an invitation post addressed to $email.
	 */
	private function invite( $id, $email, $inviter_id, array $group_ids, $accepted = '' ) {
		$GLOBALS['_mock_ia_invites'][ $email ][]                       = (object) array(
			'ID'          => $id,
			'post_author' => $inviter_id,
		);
		$GLOBALS['_mock_post_meta'][ $id ]['bp_ia_accepted']            = $accepted;
		$GLOBALS['_mock_post_terms'][ $id ]['ia_invited_groups']        = $group_ids;
	}

	private function user( $id, $email ) {
		$GLOBALS['_mock_user_emails'][ $id ] = $email;
		return $id;
	}

	private function logIn( $id ) {
		$GLOBALS['hc_test']['loggedin_user']  = $id;
		$GLOBALS['_hc_mock']['current_user_id'] = $id;
	}

	private function invitedGroupIds() {
		return array_map( 'intval', array_column( $GLOBALS['_mock_group_invites'], 'group_id' ) );
	}

	/* ---- Pending invitations ------------------------------------------- */

	public function test_pending_invitations_exclude_accepted_ones() {
		$this->invite( 11, 'new@example.org', 3, array( 100 ) );
		$this->invite( 12, 'new@example.org', 4, array( 101 ), '2026-01-01 00:00:00' );

		$pending = hc_custom_pending_email_invitations( 'new@example.org' );

		$this->assertSame( array( 11 ), array_column( $pending, 'ID' ) );
	}

	public function test_no_invitations_gives_an_empty_list() {
		$this->assertSame( array(), hc_custom_pending_email_invitations( 'nobody@example.org' ) );
	}

	/* ---- Processing ----------------------------------------------------- */

	public function test_processing_invites_the_user_to_every_group_on_the_invitation() {
		$user = $this->user( 50, 'new@example.org' );
		$this->invite( 11, 'new@example.org', 3, array( 100, 101 ) );

		$groups = hc_custom_process_email_invitations( $user );

		$this->assertSame( array( 100, 101 ), $groups );
		$this->assertSame( array( 100, 101 ), $this->invitedGroupIds() );
	}

	public function test_group_invites_are_sent_from_the_inviter_and_delivered() {
		$user = $this->user( 50, 'new@example.org' );
		$this->invite( 11, 'new@example.org', 3, array( 100 ) );

		hc_custom_process_email_invitations( $user );

		$invite = $GLOBALS['_mock_group_invites'][0];
		$this->assertSame( 50, (int) $invite['user_id'] );
		$this->assertSame( 100, (int) $invite['group_id'] );
		$this->assertSame( 3, (int) $invite['inviter_id'] );
		$this->assertSame( 1, (int) $invite['send_invite'] );
	}

	public function test_processing_marks_the_invitations_accepted() {
		$user = $this->user( 50, 'new@example.org' );
		$this->invite( 11, 'new@example.org', 3, array( 100 ) );
		$this->invite( 12, 'new@example.org', 4, array( 101 ) );

		hc_custom_process_email_invitations( $user );

		$this->assertNotEmpty( $GLOBALS['_mock_post_meta'][11]['bp_ia_accepted'] );
		$this->assertNotEmpty( $GLOBALS['_mock_post_meta'][12]['bp_ia_accepted'] );
	}

	public function test_the_same_group_from_two_inviters_yields_one_invite_from_the_first() {
		$user = $this->user( 50, 'new@example.org' );
		$this->invite( 11, 'new@example.org', 3, array( 100 ) );
		$this->invite( 12, 'new@example.org', 4, array( 100 ) );

		$groups = hc_custom_process_email_invitations( $user );

		$this->assertSame( array( 100 ), $groups );
		$this->assertCount( 1, $GLOBALS['_mock_group_invites'] );
		$this->assertSame( 3, (int) $GLOBALS['_mock_group_invites'][0]['inviter_id'] );
	}

	public function test_already_accepted_invitations_are_not_processed_again() {
		$user = $this->user( 50, 'new@example.org' );
		$this->invite( 11, 'new@example.org', 3, array( 100 ), '2026-01-01 00:00:00' );

		$groups = hc_custom_process_email_invitations( $user );

		$this->assertSame( array(), $groups );
		$this->assertSame( array(), $GLOBALS['_mock_group_invites'] );
	}

	public function test_a_user_without_an_email_gets_nothing() {
		$groups = hc_custom_process_email_invitations( 99 );

		$this->assertSame( array(), $groups );
	}

	public function test_accepting_announces_the_inviters_to_other_plugins() {
		$user = $this->user( 50, 'new@example.org' );
		$this->invite( 11, 'new@example.org', 3, array( 100 ) );
		$this->invite( 12, 'new@example.org', 4, array( 101 ) );

		hc_custom_process_email_invitations( $user );

		$this->assertSame( array( array( 50, array( 3, 4 ) ) ), $GLOBALS['_fired_actions']['accepted_email_invite'] );
	}

	/* ---- Once per user per network -------------------------------------- */

	public function test_first_authenticated_request_processes_invitations_and_remembers_it() {
		$user = $this->user( 50, 'new@example.org' );
		$this->invite( 11, 'new@example.org', 3, array( 100 ) );
		$this->logIn( $user );

		$this->assertTrue( hc_custom_maybe_process_email_invitations() );
		$this->assertSame( array( 100 ), $this->invitedGroupIds() );
		$this->assertNotEmpty( $GLOBALS['_mock_user_meta'][50]['hc_ia_invites_checked_7'] );
	}

	public function test_later_requests_on_the_same_network_do_nothing() {
		$user = $this->user( 50, 'new@example.org' );
		$this->invite( 11, 'new@example.org', 3, array( 100 ) );
		$this->logIn( $user );
		hc_custom_maybe_process_email_invitations();

		// A fresh, unaccepted invitation appearing later must not be picked up
		// by the once-only check; the lookup already happened for this network.
		$this->invite( 12, 'new@example.org', 3, array( 101 ) );

		$this->assertFalse( hc_custom_maybe_process_email_invitations() );
		$this->assertSame( array( 100 ), $this->invitedGroupIds() );
	}

	public function test_each_network_is_checked_separately() {
		$user = $this->user( 50, 'new@example.org' );
		$this->invite( 11, 'new@example.org', 3, array( 100 ) );
		$this->logIn( $user );
		$GLOBALS['_mock_user_meta'][50]['hc_ia_invites_checked_1'] = '2026-01-01 00:00:00';

		$this->assertTrue( hc_custom_maybe_process_email_invitations() );
		$this->assertSame( array( 100 ), $this->invitedGroupIds() );
	}

	public function test_the_check_is_remembered_even_when_nothing_was_pending() {
		$user = $this->user( 50, 'new@example.org' );
		$this->logIn( $user );

		$this->assertTrue( hc_custom_maybe_process_email_invitations() );
		$this->assertNotEmpty( $GLOBALS['_mock_user_meta'][50]['hc_ia_invites_checked_7'] );
	}

	public function test_logged_out_visitors_are_ignored() {
		$this->invite( 11, 'new@example.org', 3, array( 100 ) );

		$this->assertFalse( hc_custom_maybe_process_email_invitations() );
		$this->assertSame( array(), $GLOBALS['_mock_group_invites'] );
		$this->assertSame( array(), $GLOBALS['_mock_user_meta'] );
	}

	public function test_non_root_sites_are_ignored() {
		$user = $this->user( 50, 'new@example.org' );
		$this->invite( 11, 'new@example.org', 3, array( 100 ) );
		$this->logIn( $user );
		$GLOBALS['_mock_is_root_blog'] = false;

		$this->assertFalse( hc_custom_maybe_process_email_invitations() );
		$this->assertSame( array(), $GLOBALS['_mock_group_invites'] );
		$this->assertSame( array(), $GLOBALS['_mock_user_meta'] );
	}
}
