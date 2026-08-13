<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../mu-plugins/hcommons-mail-multi-network-subject-modifier.php';

/**
 * Several plugins stamp the primary network's name (blog 1 / BP_ROOT_BLOG) into
 * notification email subjects. On this multi-network install that is wrong for
 * mail sent from any secondary network. These tests describe the corrected
 * behaviour of the centralised fixer.
 *
 * Mock networks: blog 1 = "Knowledge Commons" (primary), blog 7 = "HASTAC".
 */
class HcommonsMailMultiNetworkSubjectModifierTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_mock_blog_options'] = [
			1 => [ 'blogname' => 'Knowledge Commons' ],
			7 => [ 'blogname' => 'HASTAC' ],
		];
		$GLOBALS['_mock_root_blog_id'] = 1;
		hc_mnsm_bpgd_end_notification();
	}

	protected function tearDown(): void {
		hc_mnsm_bpgd_end_notification();
		parent::tearDown();
	}

	// --- shared: current network blogname ------------------------------------

	public function test_current_network_blogname_is_the_root_blog_of_the_active_network(): void {
		$GLOBALS['_mock_root_blog_id'] = 7;
		$this->assertSame( 'HASTAC', hc_mnsm_current_network_blogname() );
	}

	// --- shared: leading-prefix rewrite (bp-group-documents style) ------------

	public function test_leading_prefix_is_replaced_with_the_given_network_name(): void {
		$this->assertSame(
			'[HASTAC] A document was uploaded to Some Group',
			hc_mnsm_rewrite_leading_prefix( '[Knowledge Commons] A document was uploaded to Some Group', 'HASTAC' )
		);
	}

	public function test_leading_prefix_rewrite_treats_the_name_literally_not_as_a_pattern(): void {
		$this->assertSame(
			'[$1 \\ Commons] A document was uploaded to Some Group',
			hc_mnsm_rewrite_leading_prefix( '[Knowledge Commons] A document was uploaded to Some Group', '$1 \\ Commons' )
		);
	}

	public function test_leading_prefix_rewrite_leaves_a_subject_without_a_prefix_untouched(): void {
		$subject = 'A document was uploaded to Some Group';
		$this->assertSame( $subject, hc_mnsm_rewrite_leading_prefix( $subject, 'HASTAC' ) );
	}

	// --- shared: token swap (GES style) --------------------------------------

	public function test_token_swap_replaces_the_wrong_bracketed_name(): void {
		$this->assertSame(
			'Bob posted an update [HASTAC]',
			hc_mnsm_swap_token( 'Bob posted an update [Knowledge Commons]', '[Knowledge Commons]', '[HASTAC]' )
		);
	}

	public function test_token_swap_is_a_no_op_when_the_names_match(): void {
		$subject = 'Bob posted an update [Knowledge Commons]';
		$this->assertSame( $subject, hc_mnsm_swap_token( $subject, '[Knowledge Commons]', '[Knowledge Commons]' ) );
	}

	public function test_token_swap_treats_the_name_literally_not_as_a_pattern(): void {
		$this->assertSame(
			'Digest [$1 \\ Commons]',
			hc_mnsm_swap_token( 'Digest [Knowledge Commons]', '[Knowledge Commons]', '[$1 \\ Commons]' )
		);
	}

	// --- bp-group-documents: wp_mail window ----------------------------------

	private function bpgdMail( string $subject ): array {
		return [
			'to'          => 'someone@example.org',
			'subject'     => $subject,
			'message'     => 'A document was uploaded.',
			'headers'     => [ 'From: noreply@example.org' ],
			'attachments' => [],
		];
	}

	public function test_bpgd_filter_rewrites_subject_to_the_current_networks_name(): void {
		$GLOBALS['_mock_root_blog_id'] = 7; // sending from HASTAC
		hc_mnsm_bpgd_begin_notification();

		$out = hc_mnsm_bpgd_filter_mail( $this->bpgdMail( '[Knowledge Commons] A document was uploaded to Some Group' ) );

		$this->assertSame( '[HASTAC] A document was uploaded to Some Group', $out['subject'] );
	}

	public function test_bpgd_filter_ignores_mail_sent_outside_a_group_documents_notification(): void {
		$GLOBALS['_mock_root_blog_id'] = 7;
		// No begin_notification() — this is some other plugin's mail.
		$other = $this->bpgdMail( '[Knowledge Commons] Your password was reset' );
		$this->assertSame( $other, hc_mnsm_bpgd_filter_mail( $other ) );
	}

	public function test_bpgd_filter_stops_applying_once_the_notification_has_finished(): void {
		$GLOBALS['_mock_root_blog_id'] = 7;
		hc_mnsm_bpgd_begin_notification();
		hc_mnsm_bpgd_end_notification();

		$other = $this->bpgdMail( '[Knowledge Commons] Your password was reset' );
		$this->assertSame( $other, hc_mnsm_bpgd_filter_mail( $other ) );
	}

	public function test_bpgd_filter_leaves_the_rest_of_the_mail_untouched(): void {
		$GLOBALS['_mock_root_blog_id'] = 7;
		hc_mnsm_bpgd_begin_notification();

		$in  = $this->bpgdMail( '[Knowledge Commons] A document was uploaded to Some Group' );
		$out = hc_mnsm_bpgd_filter_mail( $in );

		$this->assertSame( $in['to'], $out['to'] );
		$this->assertSame( $in['message'], $out['message'] );
		$this->assertSame( $in['headers'], $out['headers'] );
		$this->assertSame( $in['attachments'], $out['attachments'] );
	}

	public function test_bpgd_filter_is_a_no_op_on_the_primary_network(): void {
		$GLOBALS['_mock_root_blog_id'] = 1; // upstream is already correct here
		hc_mnsm_bpgd_begin_notification();

		$in = $this->bpgdMail( '[Knowledge Commons] A document was uploaded to Some Group' );
		$this->assertSame( $in['subject'], hc_mnsm_bpgd_filter_mail( $in )['subject'] );
	}

	// --- GES: single-activity notification subject ---------------------------
	// GES builds the subject as "$action [get_blog_option( BP_ROOT_BLOG, ... )]"
	// and passes the bracketed blogname to the filter.

	public function test_ges_single_rewrites_blogname_to_the_current_network(): void {
		$GLOBALS['_mock_root_blog_id'] = 7; // sending from HASTAC
		$out = hc_mnsm_ges_fix_single_subject(
			'Bob posted an update [Knowledge Commons]',
			'Bob posted an update',
			'[Knowledge Commons]'
		);
		$this->assertSame( 'Bob posted an update [HASTAC]', $out );
	}

	public function test_ges_single_is_a_no_op_on_the_primary_network(): void {
		$GLOBALS['_mock_root_blog_id'] = 1;
		$subject = 'Bob posted an update [Knowledge Commons]';
		$this->assertSame(
			$subject,
			hc_mnsm_ges_fix_single_subject( $subject, 'Bob posted an update', '[Knowledge Commons]' )
		);
	}

	// --- GES: digest / summary subject ---------------------------------------
	// GES builds the subject as "$title [$blogname]" and passes the raw blogname.

	public function test_ges_digest_rewrites_blogname_to_the_current_network(): void {
		$GLOBALS['_mock_root_blog_id'] = 7;
		$out = hc_mnsm_ges_fix_digest_subject(
			'Group Digest, Weekly [Knowledge Commons]',
			'Knowledge Commons',
			'Group Digest, Weekly',
			'sum'
		);
		$this->assertSame( 'Group Digest, Weekly [HASTAC]', $out );
	}

	public function test_ges_digest_is_a_no_op_on_the_primary_network(): void {
		$GLOBALS['_mock_root_blog_id'] = 1;
		$subject = 'Group Digest, Weekly [Knowledge Commons]';
		$this->assertSame(
			$subject,
			hc_mnsm_ges_fix_digest_subject( $subject, 'Knowledge Commons', 'Group Digest, Weekly', 'dig' )
		);
	}
}
