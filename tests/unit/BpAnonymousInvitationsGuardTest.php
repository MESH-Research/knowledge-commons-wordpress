<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../mu-plugins/bp-anonymous-invitations-guard.php';

/**
 * BuddyPress drops the user filter from invitation queries when the user ID
 * is 0 (class-bp-invitation.php, get_where_sql: `! empty( $args['user_id'] )`),
 * so a capability check for a logged-out visitor loads every pending group
 * invitation on the network. The guard turns an explicit user_id of 0 into a
 * query that matches nothing, which is the correct answer for a visitor who
 * cannot hold invitations, and leaves every other query untouched.
 */
class BpAnonymousInvitationsGuardTest extends TestCase {

	public function test_explicit_user_id_zero_matches_nothing(): void {
		$args = kc_bp_guard_anonymous_invitation_args( [ 'user_id' => 0, 'type' => 'all' ] );

		$this->assertSame( [ 0 ], $args['id'] );
		$this->assertSame( 'all', $args['type'], 'other arguments are preserved' );
	}

	public function test_string_zero_is_treated_as_zero(): void {
		$args = kc_bp_guard_anonymous_invitation_args( [ 'user_id' => '0' ] );

		$this->assertSame( [ 0 ], $args['id'] );
	}

	public function test_absent_user_id_means_no_user_filter_and_is_untouched(): void {
		$in  = [ 'item_id' => 319, 'type' => 'invite' ];
		$out = kc_bp_guard_anonymous_invitation_args( $in );

		$this->assertSame( $in, $out );
	}

	public function test_false_user_id_is_buddypress_default_and_is_untouched(): void {
		$in  = [ 'user_id' => false, 'item_id' => 319 ];
		$out = kc_bp_guard_anonymous_invitation_args( $in );

		$this->assertSame( $in, $out );
	}

	public function test_real_user_id_is_untouched(): void {
		$in  = [ 'user_id' => 1020063, 'invite_sent' => 'all' ];
		$out = kc_bp_guard_anonymous_invitation_args( $in );

		$this->assertSame( $in, $out );
	}

	public function test_array_of_user_ids_is_untouched(): void {
		$in  = [ 'user_id' => [ 0, 5 ] ];
		$out = kc_bp_guard_anonymous_invitation_args( $in );

		$this->assertSame( $in, $out );
	}

	public function test_user_zero_with_a_group_filter_is_untouched(): void {
		// The REST group-invites endpoint lists a group's invitations with
		// user_id 0 meaning "any user"; that query is scoped by item_id and
		// must keep working for group administrators.
		$in  = [ 'user_id' => 0, 'item_id' => 319, 'type' => 'invite' ];
		$out = kc_bp_guard_anonymous_invitation_args( $in );

		$this->assertSame( $in, $out );
	}

	public function test_user_zero_with_an_inviter_filter_is_untouched(): void {
		$in  = [ 'user_id' => 0, 'inviter_id' => 42 ];
		$out = kc_bp_guard_anonymous_invitation_args( $in );

		$this->assertSame( $in, $out );
	}

	public function test_user_zero_with_explicit_ids_is_untouched(): void {
		$in  = [ 'user_id' => 0, 'id' => [ 7, 8 ] ];
		$out = kc_bp_guard_anonymous_invitation_args( $in );

		$this->assertSame( $in, $out );
	}

	public function test_guard_is_hooked_to_both_invitation_query_filters(): void {
		foreach ( [ 'bp_before_bp_invitations_invitation_get_parse_args', 'bp_before_bp_invitations_invitation_get_total_count_parse_args' ] as $hook ) {
			$this->assertArrayHasKey( $hook, $GLOBALS['_captured_filters'], $hook );
			$this->assertContains( 'kc_bp_guard_anonymous_invitation_args', $GLOBALS['_captured_filters'][ $hook ] );
		}
	}
}
