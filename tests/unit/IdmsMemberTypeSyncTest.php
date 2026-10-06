<?php
/**
 * Tests for the IDMS WP-CLI member-type sync.
 *
 * These lock in the same contract as the login-time sync in the ci-logon
 * plugin: memberships reported by the profiles system (including STEM Ed+,
 * which arrives under the key 'STEMED+') are reflected in BuddyPress member
 * types — added when held, removed when lapsed, with 'hc' always kept.
 */

use PHPUnit\Framework\TestCase;

class IdmsMemberTypeSyncTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_bp_set_calls']    = array();
		$GLOBALS['_bp_remove_calls'] = array();
		$GLOBALS['_bp_current_types'] = array();
		$GLOBALS['_wp_cli_errors']   = array();

		$user     = new stdClass();
		$user->ID = 42;

		$GLOBALS['_mock_users'] = array( 'martin' => $user );
	}

	protected function tearDown(): void {
		unset(
			$GLOBALS['_bp_set_calls'],
			$GLOBALS['_bp_remove_calls'],
			$GLOBALS['_bp_current_types'],
			$GLOBALS['_wp_cli_errors'],
			$GLOBALS['_mock_users']
		);
	}

	private function setTypes(): array {
		return array_map( static fn( $c ) => $c['type'], $GLOBALS['_bp_set_calls'] );
	}

	private function removedTypes(): array {
		return array_map( static fn( $c ) => $c['type'], $GLOBALS['_bp_remove_calls'] );
	}

	public function test_stemedplus_membership_is_added() {
		IDMS\CLI\kc_sync_bp_member_types_for_username( 'martin', array( 'STEMED+' => 1 ) );

		$this->assertContains( 'stemedplus', $this->setTypes() );
		$this->assertNotContains( 'stemed+', $this->setTypes() );
	}

	public function test_lapsed_stemedplus_membership_is_removed() {
		$GLOBALS['_bp_current_types'] = array( 'stemedplus' );

		IDMS\CLI\kc_sync_bp_member_types_for_username( 'martin', array( 'STEMED+' => '' ) );

		$this->assertContains( 'stemedplus', $this->removedTypes() );
	}

	public function test_stale_type_is_removed_when_membership_lapses() {
		$GLOBALS['_bp_current_types'] = array( 'mla' );

		IDMS\CLI\kc_sync_bp_member_types_for_username( 'martin', array( 'MLA' => '' ) );

		$this->assertContains( 'mla', $this->removedTypes() );
	}

	public function test_hc_is_always_set_and_never_removed() {
		$GLOBALS['_bp_current_types'] = array( 'hc' );

		IDMS\CLI\kc_sync_bp_member_types_for_username( 'martin', array() );

		$this->assertContains( 'hc', $this->setTypes() );
		$this->assertNotContains( 'hc', $this->removedTypes() );
	}

	public function test_missing_user_makes_no_changes() {
		IDMS\CLI\kc_sync_bp_member_types_for_username( 'nobody', array( 'STEMED+' => 1 ) );

		$this->assertSame( array(), $this->setTypes() );
		$this->assertSame( array(), $this->removedTypes() );
	}
}
