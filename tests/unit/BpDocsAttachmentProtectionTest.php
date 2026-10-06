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

	// --- Group-privacy invariant (mesh_bp_docs_read_allowed) -----------------

	/**
	 * A Doc in a private group must not be readable by a non-member, even when
	 * the Doc's own capability check would allow it because a missing per-Doc
	 * setting resolved to the public "anyone" default. This is the core of the
	 * legacy/imported-Doc exposure.
	 */
	public function test_private_group_nonmember_denied_despite_public_default() {
		$this->assertFalse(
			mesh_bp_docs_read_allowed(
				false, // not a moderator
				true,  // Doc is in a non-public group
				false, // not a member
				false, // not explicitly published to "anyone"
				true   // base cap allows (empty setting resolved to "anyone")
			)
		);
	}

	/**
	 * A Doc in a private group with an explicit "group-members" setting is also
	 * denied to a non-member (base cap already false).
	 */
	public function test_private_group_nonmember_denied_group_members_setting() {
		$this->assertFalse(
			mesh_bp_docs_read_allowed( false, true, false, false, false )
		);
	}

	/**
	 * A member of the private group is allowed (base cap grants it).
	 */
	public function test_private_group_member_allowed() {
		$this->assertTrue(
			mesh_bp_docs_read_allowed( false, true, true, false, true )
		);
	}

	/**
	 * A Doc deliberately published to "anyone" stays public even inside a
	 * private group — the author's explicit choice is respected.
	 */
	public function test_explicitly_public_doc_in_private_group_allowed() {
		$this->assertTrue(
			mesh_bp_docs_read_allowed( false, true, false, true, true )
		);
	}

	/**
	 * For a Doc not in any private group, the Doc's own capability decides:
	 * allowed when the cap grants it, denied when it does not.
	 */
	public function test_no_private_group_defers_to_capability() {
		$this->assertTrue(
			mesh_bp_docs_read_allowed( false, false, false, false, true )
		);
		$this->assertFalse(
			mesh_bp_docs_read_allowed( false, false, false, false, false )
		);
	}

	/**
	 * A site moderator / super admin is always allowed.
	 */
	public function test_moderator_always_allowed() {
		$this->assertTrue(
			mesh_bp_docs_read_allowed( true, true, false, false, false )
		);
	}
}
