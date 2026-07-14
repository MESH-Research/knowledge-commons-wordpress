<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../mu-plugins/bp-group-documents-mail.php';

/**
 * bp-group-documents hardcodes blog 1 in its upload notification subject.
 * On this multi-network install that is the wrong network name for mail sent
 * from anything but the primary network. These tests describe the corrected
 * behaviour.
 */
class BpGroupDocumentsMailTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_mock_blog_options'] = [
			1 => [ 'blogname' => 'Knowledge Commons' ],
			7 => [ 'blogname' => 'HASTAC' ],
		];
		$GLOBALS['_mock_root_blog_id'] = 1;
		hc_bpgd_end_notification();
	}

	protected function tearDown(): void {
		hc_bpgd_end_notification();
		parent::tearDown();
	}

	/** The subject the upstream plugin produces, always naming blog 1. */
	private function upstreamSubject(): string {
		return '[Knowledge Commons] A document was uploaded to Some Group';
	}

	private function mail( string $subject ): array {
		return [
			'to'          => 'someone@example.org',
			'subject'     => $subject,
			'message'     => 'A document was uploaded.',
			'headers'     => [ 'From: noreply@example.org' ],
			'attachments' => [],
		];
	}

	// --- the prefix rewrite itself -------------------------------------------

	public function test_rewrite_replaces_bracketed_prefix_with_given_network_name(): void {
		$this->assertSame(
			'[HASTAC] A document was uploaded to Some Group',
			hc_bpgd_rewrite_subject_prefix( $this->upstreamSubject(), 'HASTAC' )
		);
	}

	public function test_rewrite_treats_network_name_literally_not_as_a_pattern(): void {
		// A blogname containing regex/backreference-hostile characters must survive intact.
		$this->assertSame(
			'[$1 \\ Commons] A document was uploaded to Some Group',
			hc_bpgd_rewrite_subject_prefix( $this->upstreamSubject(), '$1 \\ Commons' )
		);
	}

	public function test_rewrite_leaves_subject_without_a_bracketed_prefix_untouched(): void {
		$subject = 'A document was uploaded to Some Group';
		$this->assertSame( $subject, hc_bpgd_rewrite_subject_prefix( $subject, 'HASTAC' ) );
	}

	// --- the wp_mail filter ---------------------------------------------------

	public function test_filter_rewrites_subject_to_the_current_networks_name(): void {
		$GLOBALS['_mock_root_blog_id'] = 7; // sending from HASTAC
		hc_bpgd_begin_notification();

		$out = hc_bpgd_filter_mail( $this->mail( $this->upstreamSubject() ) );

		$this->assertSame( '[HASTAC] A document was uploaded to Some Group', $out['subject'] );
	}

	public function test_filter_ignores_mail_sent_outside_a_group_documents_notification(): void {
		$GLOBALS['_mock_root_blog_id'] = 7;
		// No begin_notification() — this is some other plugin's mail.

		$other = $this->mail( '[Knowledge Commons] Your password was reset' );
		$out   = hc_bpgd_filter_mail( $other );

		$this->assertSame( $other, $out );
	}

	public function test_filter_stops_applying_once_the_notification_has_finished(): void {
		$GLOBALS['_mock_root_blog_id'] = 7;
		hc_bpgd_begin_notification();
		hc_bpgd_end_notification();

		$other = $this->mail( '[Knowledge Commons] Your password was reset' );

		$this->assertSame( $other, hc_bpgd_filter_mail( $other ) );
	}

	public function test_filter_leaves_the_rest_of_the_mail_untouched(): void {
		$GLOBALS['_mock_root_blog_id'] = 7;
		hc_bpgd_begin_notification();

		$in  = $this->mail( $this->upstreamSubject() );
		$out = hc_bpgd_filter_mail( $in );

		$this->assertSame( $in['to'], $out['to'] );
		$this->assertSame( $in['message'], $out['message'] );
		$this->assertSame( $in['headers'], $out['headers'] );
		$this->assertSame( $in['attachments'], $out['attachments'] );
	}

	public function test_filter_is_a_no_op_on_the_primary_network(): void {
		$GLOBALS['_mock_root_blog_id'] = 1; // upstream is already correct here
		hc_bpgd_begin_notification();

		$in = $this->mail( $this->upstreamSubject() );

		$this->assertSame( $in['subject'], hc_bpgd_filter_mail( $in )['subject'] );
	}
}
