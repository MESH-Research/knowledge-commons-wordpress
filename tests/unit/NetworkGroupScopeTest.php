<?php
/**
 * Tests for network scoping of BuddyPress groups.
 *
 * These lock in the invariant that a network's groups directory only ever
 * counts and lists groups belonging to that network: the "All Groups" count,
 * the "My Groups" count, and every directory scope (including 'personal')
 * must be restricted to the current network's group type.
 */

use PHPUnit\Framework\TestCase;

class NetworkGroupScopeTest extends TestCase {

	protected function setUp(): void {
		unset(
			$GLOBALS['_mock_bp_groups_group_get'],
			$GLOBALS['_mock_loggedin_user_id'],
			$GLOBALS['_mock_displayed_user_id'],
			$GLOBALS['_mock_current_user_can'],
			$GLOBALS['_mock_bp_is_user'],
			$GLOBALS['_mock_is_super_admin']
		);
		$_SERVER['REQUEST_URI']          = '/groups/';
		Humanities_Commons::$society_id  = '';
	}

	/* ---- Directory query scoping ---------------------------------------- */

	/**
	 * The 'personal' (My Groups) scope must be restricted to the current
	 * network's group type, not opened up to groups from every network.
	 */
	public function test_personal_scope_is_restricted_to_network_group_type() {
		$args = hcommons_apply_network_group_scope(
			array(
				'scope'      => 'personal',
				'group_type' => '',
			),
			'stemedplus'
		);

		$this->assertSame( 'stemedplus', $args['group_type'] );
	}

	/**
	 * The hc network gets no special treatment: its directory queries are
	 * restricted to hc groups like any other network.
	 */
	public function test_hc_default_scope_is_restricted_to_hc_groups() {
		$args = hcommons_apply_network_group_scope(
			array( 'scope' => '' ),
			'hc'
		);

		$this->assertSame( 'hc', $args['group_type'] );
	}

	/**
	 * The 'all' directory scope on a society network only shows that
	 * society's groups.
	 */
	public function test_all_scope_is_restricted_to_network_group_type() {
		$args = hcommons_apply_network_group_scope(
			array( 'scope' => 'all' ),
			'mla'
		);

		$this->assertSame( 'mla', $args['group_type'] );
	}

	/**
	 * Without a society context (e.g. misconfigured network) the query is
	 * left untouched rather than being restricted to an empty type.
	 */
	public function test_missing_society_leaves_args_untouched() {
		$args = array(
			'scope'      => 'all',
			'group_type' => 'preexisting',
		);

		$this->assertSame( $args, hcommons_apply_network_group_scope( $args, '' ) );
	}

	/**
	 * Regular users on a member's invite-anyone screen only see hc groups
	 * (society group membership is managed externally).
	 */
	public function test_invite_anyone_user_page_shows_only_hc_groups() {
		$GLOBALS['_mock_bp_is_user'] = true;
		$_SERVER['REQUEST_URI']      = '/members/martin/invite-anyone/';

		$args = hcommons_apply_network_group_scope(
			array( 'scope' => '' ),
			'mla'
		);

		$this->assertSame( 'hc', $args['group_type'] );
	}

	/**
	 * Super admins keep the network restriction on invite-anyone screens.
	 */
	public function test_invite_anyone_super_admin_keeps_network_scope() {
		$GLOBALS['_mock_bp_is_user']     = true;
		$GLOBALS['_mock_is_super_admin'] = true;
		$_SERVER['REQUEST_URI']          = '/members/martin/invite-anyone/';

		$args = hcommons_apply_network_group_scope(
			array( 'scope' => '' ),
			'mla'
		);

		$this->assertSame( 'mla', $args['group_type'] );
	}

	/* ---- "All Groups" count --------------------------------------------- */

	/**
	 * The sitewide group count is replaced by the count of groups belonging
	 * to the current network.
	 */
	public function test_total_group_count_is_scoped_to_current_network() {
		Humanities_Commons::$society_id = 'soc-total-a';

		$GLOBALS['_mock_bp_groups_group_get'] = function ( $args ) {
			$total = ( 'soc-total-a' === ( $args['group_type'] ?? '' ) ) ? 42 : 1982;
			return array(
				'groups' => array(),
				'total'  => $total,
			);
		};

		$this->assertSame( 42, hcommons_network_total_group_count( 1982 ) );
	}

	/**
	 * Without a society context the install-wide count passes through.
	 */
	public function test_total_group_count_unfiltered_without_society() {
		Humanities_Commons::$society_id = '';

		$this->assertSame( 1982, hcommons_network_total_group_count( 1982 ) );
	}

	/* ---- "My Groups" count ---------------------------------------------- */

	/**
	 * A user's group count only includes their groups on the current network.
	 */
	public function test_user_group_count_is_scoped_to_current_network() {
		Humanities_Commons::$society_id     = 'soc-user-a';
		$GLOBALS['_mock_loggedin_user_id']  = 5;

		$GLOBALS['_mock_bp_groups_group_get'] = function ( $args ) {
			$scoped = ( 'soc-user-a' === ( $args['group_type'] ?? '' ) )
				&& ( 5 === (int) ( $args['user_id'] ?? 0 ) );
			return array(
				'groups' => array(),
				'total'  => $scoped ? 7 : 11,
			);
		};

		$this->assertSame( 7, hcommons_network_total_group_count_for_user( 11, 5 ) );
	}

	/**
	 * When no user id is passed, the count falls back to the displayed user,
	 * then the logged-in user, matching BuddyPress's own behaviour.
	 */
	public function test_user_group_count_defaults_to_displayed_user() {
		Humanities_Commons::$society_id     = 'soc-user-b';
		$GLOBALS['_mock_displayed_user_id'] = 9;
		$GLOBALS['_mock_loggedin_user_id']  = 5;

		$GLOBALS['_mock_bp_groups_group_get'] = function ( $args ) {
			return array(
				'groups' => array(),
				'total'  => ( 9 === (int) ( $args['user_id'] ?? 0 ) ) ? 3 : 99,
			);
		};

		$this->assertSame( 3, hcommons_network_total_group_count_for_user( 11, 0 ) );
	}

	/**
	 * With no resolvable user the original count passes through untouched.
	 */
	public function test_user_group_count_without_user_returns_original() {
		Humanities_Commons::$society_id = 'soc-user-c';

		$this->assertSame( 11, hcommons_network_total_group_count_for_user( 11, 0 ) );
	}

	/**
	 * Counting your own groups includes hidden groups you belong to, so the
	 * count matches what your My Groups list shows you.
	 */
	public function test_user_group_count_includes_own_hidden_groups() {
		Humanities_Commons::$society_id    = 'soc-user-d';
		$GLOBALS['_mock_loggedin_user_id'] = 5;

		$GLOBALS['_mock_bp_groups_group_get'] = function ( $args ) {
			return array(
				'groups' => array(),
				'total'  => empty( $args['show_hidden'] ) ? 5 : 7,
			);
		};

		$this->assertSame( 7, hcommons_network_total_group_count_for_user( 11, 5 ) );
	}

	/**
	 * Counting someone else's groups excludes their hidden groups, matching
	 * what a visitor is allowed to see.
	 */
	public function test_user_group_count_excludes_others_hidden_groups() {
		Humanities_Commons::$society_id    = 'soc-user-e';
		$GLOBALS['_mock_loggedin_user_id'] = 6;

		$GLOBALS['_mock_bp_groups_group_get'] = function ( $args ) {
			return array(
				'groups' => array(),
				'total'  => empty( $args['show_hidden'] ) ? 5 : 7,
			);
		};

		$this->assertSame( 5, hcommons_network_total_group_count_for_user( 11, 5 ) );
	}
}
