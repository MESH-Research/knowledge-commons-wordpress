<?php
/**
 * Tests for Invite Anyone's accept and opt-out links landing on /membership/.
 *
 * The plugin's own screens only run on the BuddyPress register page, which
 * the Commons does not use. The membership page must therefore explain an
 * invitation to the invitee and let an address opt out of further
 * invitations.
 */

use PHPUnit\Framework\TestCase;

class HcCustomInviteAnyoneMembershipTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_mock_ia_invites']        = array();
		$GLOBALS['_mock_post_meta']         = array();
		$GLOBALS['_mock_user_names']        = array();
		$GLOBALS['_mock_valid_nonces']      = array();
		$GLOBALS['_mock_opted_out']         = array();
		$GLOBALS['_mock_bloginfo']          = array( 'name' => 'STEM Ed+ Commons' );
		$GLOBALS['_mock_is_membership_page'] = false;
		$_GET                               = array();
		$_POST                              = array();
	}

	private function invite( $id, $email, $inviter_id, $inviter_name ) {
		$GLOBALS['_mock_ia_invites'][ $email ][]            = (object) array(
			'ID'          => $id,
			'post_author' => $inviter_id,
		);
		$GLOBALS['_mock_post_meta'][ $id ]['bp_ia_accepted'] = '';
		$GLOBALS['_mock_user_names'][ $inviter_id ]          = $inviter_name;
	}

	private function validOptOutPost( $email ) {
		$GLOBALS['_mock_valid_nonces']['invite_anyone_opt_out'] = array( 'good-nonce' );
		return array(
			'hc_ia_opt_out' => 'Opt Out',
			'opt_out_email' => $email,
			'_wpnonce'      => 'good-nonce',
		);
	}

	/* ---- Request parsing ------------------------------------------------ */

	public function test_an_accept_link_is_recognised() {
		$request = hc_custom_invite_anyone_request( array( 'iaaction' => 'accept-invitation', 'email' => 'new@example.org' ) );

		$this->assertSame( array( 'action' => 'accept-invitation', 'email' => 'new@example.org' ), $request );
	}

	public function test_an_opt_out_link_is_recognised() {
		$request = hc_custom_invite_anyone_request( array( 'iaaction' => 'opt-out', 'email' => 'new@example.org' ) );

		$this->assertSame( 'opt-out', $request['action'] );
	}

	public function test_a_plus_sign_lost_to_url_decoding_is_restored() {
		$request = hc_custom_invite_anyone_request( array( 'iaaction' => 'opt-out', 'email' => 'new tag@example.org' ) );

		$this->assertSame( 'new+tag@example.org', $request['email'] );
	}

	public function test_an_invalid_email_is_rejected() {
		$request = hc_custom_invite_anyone_request( array( 'iaaction' => 'opt-out', 'email' => 'not-an-email' ) );

		$this->assertSame( array( 'action' => null, 'email' => null ), $request );
	}

	public function test_an_unknown_action_is_rejected() {
		$request = hc_custom_invite_anyone_request( array( 'iaaction' => 'delete-everything', 'email' => 'new@example.org' ) );

		$this->assertSame( array( 'action' => null, 'email' => null ), $request );
	}

	public function test_a_request_without_parameters_is_rejected() {
		$this->assertSame( array( 'action' => null, 'email' => null ), hc_custom_invite_anyone_request( array() ) );
	}

	/* ---- Opt-out submission --------------------------------------------- */

	public function test_nothing_submitted_means_no_status() {
		$this->assertNull( hc_custom_handle_invite_opt_out( array() ) );
		$this->assertSame( array(), $GLOBALS['_mock_opted_out'] );
	}

	public function test_a_valid_submission_opts_the_address_out() {
		$status = hc_custom_handle_invite_opt_out( $this->validOptOutPost( 'new@example.org' ) );

		$this->assertSame( 'done', $status );
		$this->assertSame( array( 'new@example.org' ), $GLOBALS['_mock_opted_out'] );
	}

	public function test_a_submission_with_a_bad_nonce_changes_nothing() {
		$post             = $this->validOptOutPost( 'new@example.org' );
		$post['_wpnonce'] = 'forged';

		$this->assertSame( 'bad_nonce', hc_custom_handle_invite_opt_out( $post ) );
		$this->assertSame( array(), $GLOBALS['_mock_opted_out'] );
	}

	public function test_a_submission_with_a_bad_email_changes_nothing() {
		$this->assertSame( 'bad_email', hc_custom_handle_invite_opt_out( $this->validOptOutPost( 'nope' ) ) );
		$this->assertSame( array(), $GLOBALS['_mock_opted_out'] );
	}

	/* ---- Markup --------------------------------------------------------- */

	public function test_the_opt_out_form_is_prefilled_and_protected() {
		$html = hc_custom_invite_opt_out_markup( 'new+tag@example.org' );

		$this->assertStringContainsString( '<form', $html );
		$this->assertStringContainsString( 'name="opt_out_email"', $html );
		$this->assertStringContainsString( 'value="new+tag@example.org"', $html );
		$this->assertStringContainsString( 'name="hc_ia_opt_out"', $html );
		$this->assertStringContainsString( 'nonce-for-invite_anyone_opt_out', $html );
	}

	public function test_the_opt_out_form_escapes_the_address() {
		$html = hc_custom_invite_opt_out_markup( '"><script>alert(1)</script>' );

		$this->assertStringNotContainsString( '<script>', $html );
	}

	public function test_a_completed_opt_out_shows_a_confirmation_instead_of_the_form() {
		$html = hc_custom_invite_opt_out_markup( 'new@example.org', 'done' );

		$this->assertStringNotContainsString( '<form', $html );
		$this->assertStringContainsString( 'new@example.org', $html );
		$this->assertStringContainsString( 'STEM Ed+ Commons', $html );
	}

	public function test_a_failed_opt_out_shows_the_form_again_with_an_error() {
		$html = hc_custom_invite_opt_out_markup( 'new@example.org', 'bad_nonce' );

		$this->assertStringContainsString( '<form', $html );
		$this->assertMatchesRegularExpression( '/class="[^"]*error[^"]*"/', $html );
	}

	public function test_the_accept_notice_names_the_inviters_and_the_address_to_register_with() {
		$this->invite( 11, 'new@example.org', 3, 'Bonnie Russell' );
		$this->invite( 12, 'new@example.org', 4, 'Ada Lovelace' );

		$html = hc_custom_invite_accept_markup( 'new@example.org', hc_custom_pending_email_invitations( 'new@example.org' ) );

		$this->assertStringContainsString( 'Bonnie Russell', $html );
		$this->assertStringContainsString( 'Ada Lovelace', $html );
		$this->assertStringContainsString( 'new@example.org', $html );
		$this->assertStringContainsString( 'STEM Ed+ Commons', $html );
	}

	public function test_the_accept_notice_is_empty_without_a_matching_invitation() {
		$this->assertSame( '', hc_custom_invite_accept_markup( 'new@example.org', array() ) );
	}

	/* ---- Membership page content ---------------------------------------- */

	public function test_an_opt_out_link_puts_the_form_before_the_page_content() {
		$request = array( 'action' => 'opt-out', 'email' => 'new@example.org' );

		$html = hc_custom_invite_anyone_membership_content( '<p>Join us.</p>', $request );

		$this->assertStringEndsWith( '<p>Join us.</p>', $html );
		$this->assertStringContainsString( 'name="opt_out_email"', $html );
	}

	public function test_an_accept_link_puts_the_invitation_notice_before_the_page_content() {
		$this->invite( 11, 'new@example.org', 3, 'Bonnie Russell' );
		$request = array( 'action' => 'accept-invitation', 'email' => 'new@example.org' );

		$html = hc_custom_invite_anyone_membership_content( '<p>Join us.</p>', $request );

		$this->assertStringEndsWith( '<p>Join us.</p>', $html );
		$this->assertStringContainsString( 'Bonnie Russell', $html );
	}

	public function test_an_accept_link_without_a_matching_invitation_leaves_the_page_alone() {
		$request = array( 'action' => 'accept-invitation', 'email' => 'new@example.org' );

		$this->assertSame( '<p>Join us.</p>', hc_custom_invite_anyone_membership_content( '<p>Join us.</p>', $request ) );
	}

	public function test_a_plain_visit_leaves_the_page_alone() {
		$request = array( 'action' => null, 'email' => null );

		$this->assertSame( '<p>Join us.</p>', hc_custom_invite_anyone_membership_content( '<p>Join us.</p>', $request ) );
	}

	public function test_the_content_filter_only_touches_the_membership_page() {
		$_GET = array( 'iaaction' => 'opt-out', 'email' => 'new@example.org' );

		$GLOBALS['_mock_is_membership_page'] = false;
		$this->assertSame( '<p>Elsewhere.</p>', hc_custom_invite_anyone_filter_content( '<p>Elsewhere.</p>' ) );

		$GLOBALS['_mock_is_membership_page'] = true;
		$this->assertStringContainsString( 'name="opt_out_email"', hc_custom_invite_anyone_filter_content( '<p>Join us.</p>' ) );
	}
}
