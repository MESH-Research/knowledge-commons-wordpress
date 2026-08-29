<?php
/**
 * Tests for the BuddyPress Docs access-hardening decision policy.
 *
 * These lock in the access-control invariant: a request for a restricted Doc
 * (its read view or one of its files) must only be served to a user who holds
 * the Doc's "read" permission, while legitimate readers and unrelated requests
 * are left untouched.
 */

use PHPUnit\Framework\TestCase;

class BpDocsAttachmentProtectionTest extends TestCase {

	/**
	 * A request that is not a Doc read view / attachment download must always
	 * pass through untouched, regardless of the other facts.
	 */
	public function test_non_docs_request_is_always_allowed() {
		$this->assertSame(
			'allow',
			mesh_bp_docs_access_decision( false, 0, false, false )
		);
		$this->assertSame(
			'allow',
			mesh_bp_docs_access_decision( false, 123, false, true )
		);
	}

	/**
	 * A Docs request that cannot be tied to a real Doc (doc_id 0) must not be
	 * interfered with — we only assert authority over identifiable Docs.
	 */
	public function test_docs_request_without_doc_id_is_allowed() {
		$this->assertSame(
			'allow',
			mesh_bp_docs_access_decision( true, 0, false, false )
		);
	}

	/**
	 * A reader who satisfies the Doc's "read" permission is allowed through.
	 */
	public function test_permitted_reader_is_allowed() {
		$this->assertSame(
			'allow',
			mesh_bp_docs_access_decision( true, 42, true, true )
		);
	}

	/**
	 * An anonymous visitor who cannot read the Doc is sent to authenticate,
	 * never served the content. This is the core of the reported leak.
	 */
	public function test_anonymous_unauthorized_is_sent_to_login() {
		$this->assertSame(
			'login',
			mesh_bp_docs_access_decision( true, 42, false, false )
		);
	}

	/**
	 * A logged-in user who is not permitted (e.g. not a member of the private
	 * group) is forbidden outright rather than bounced to a login screen.
	 */
	public function test_authenticated_unauthorized_is_forbidden() {
		$this->assertSame(
			'forbid',
			mesh_bp_docs_access_decision( true, 42, false, true )
		);
	}

	/**
	 * A permitted reader is allowed whether or not they happen to be
	 * authenticated (covers public Docs readable by anyone).
	 */
	public function test_permitted_anonymous_reader_is_allowed() {
		$this->assertSame(
			'allow',
			mesh_bp_docs_access_decision( true, 42, true, false )
		);
	}
}
