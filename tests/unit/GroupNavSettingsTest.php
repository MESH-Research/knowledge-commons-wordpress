<?php
/**
 * Behavioural tests for the group nav show/hide settings (hc-custom).
 *
 * Covers the two regressions from the group Manage > Settings screen:
 *  - the "Select Default Landing Page for Group" dropdown must be buildable
 *    from a persisted nav snapshot, because on BP 12+ the group nav is never
 *    registered during admin-ajax requests;
 *  - menu items set to "hide" must not render in the group nav (no orphaned
 *    labels), for regular members and group admins alike, while remaining
 *    listed on the settings screen so they can be toggled back on.
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/group-nav-settings-loader.php';

class GroupNavSettingsTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_hc_mock']        = array();
		$GLOBALS['_mock_group_meta'] = array();
		unset( $GLOBALS['_mock_current_group_id'] );
		$GLOBALS['bp'] = $this->makeBp( array() );
	}

	private function makeBp( array $items ) {
		$bp              = new stdClass();
		$bp->groups      = new stdClass();
		$bp->groups->nav = new HC_Test_BP_Options_Nav( $items );

		return $bp;
	}

	private function defaultNavItems() {
		return array(
			array(
				'slug'            => 'home',
				'name'            => 'Home',
				'link'            => 'https://example.org/groups/test-group/home/',
				'screen_function' => 'groups_screen_group_home',
			),
			array(
				'slug'            => 'forum',
				'name'            => 'Forum',
				'link'            => 'https://example.org/groups/test-group/forum/',
				'screen_function' => 'groups_screen_group_forum',
			),
			array(
				'slug'            => 'events',
				'name'            => 'Events',
				'link'            => 'https://example.org/groups/test-group/events/',
				'screen_function' => 'bpeo_screen_group_events',
			),
			array(
				'slug'            => 'invite-anyone',
				'name'            => 'Send Invites',
				'link'            => 'https://example.org/groups/test-group/invite-anyone/',
				'screen_function' => 'invite_anyone_catch_group_invites',
			),
			array(
				'slug'            => 'admin',
				'name'            => 'Manage',
				'link'            => 'https://example.org/groups/test-group/admin/',
				'screen_function' => 'groups_screen_group_admin',
			),
		);
	}

	/**
	 * Put the mocks into "real group page request" state: BP has resolved the
	 * group and registered its secondary nav.
	 */
	private function enterGroupPage( $group_id, $slug, array $items ) {
		$GLOBALS['_hc_mock']['is_group']           = true;
		$GLOBALS['_hc_mock']['current_group_id']   = $group_id;
		$GLOBALS['_hc_mock']['current_group_slug'] = $slug;
		$GLOBALS['_hc_mock']['current_item']       = $slug;
		$GLOBALS['_hc_mock']['current_component']  = 'groups';
		$GLOBALS['bp']                             = $this->makeBp( $items );
	}

	/**
	 * Put the mocks into "admin-ajax request" state: no group context, no
	 * group nav registered.
	 */
	private function enterAdminAjax() {
		$GLOBALS['_hc_mock']['is_group'] = false;
		unset(
			$GLOBALS['_hc_mock']['current_group_id'],
			$GLOBALS['_hc_mock']['current_group_slug'],
			$GLOBALS['_hc_mock']['current_item']
		);
		$GLOBALS['bp'] = $this->makeBp( array() );
	}

	private function navSlugs() {
		$items = $GLOBALS['bp']->groups->nav->get_secondary( array( 'parent_slug' => 'test-group' ) );

		if ( empty( $items ) ) {
			return array();
		}

		return array_map(
			function ( $item ) {
				return $item->slug;
			},
			$items
		);
	}

	// -----------------------------------------------------------------------
	// Bug (a): landing page dropdown empty in admin-ajax.
	// -----------------------------------------------------------------------

	public function test_dropdown_options_available_in_admin_ajax_after_group_page_snapshot() {
		$this->enterGroupPage( 7, 'test-group', $this->defaultNavItems() );
		hc_custom_snapshot_group_nav();

		$this->enterAdminAjax();
		$html = hc_custom_get_landing_page_options_html( 7, 'test-group' );

		$this->assertStringContainsString( 'value="home"', $html );
		$this->assertStringContainsString( 'value="forum"', $html );
		$this->assertStringContainsString( 'value="events"', $html );
	}

	public function test_dropdown_excludes_admin_screens_and_invite_anyone() {
		$this->enterGroupPage( 7, 'test-group', $this->defaultNavItems() );
		hc_custom_snapshot_group_nav();

		$this->enterAdminAjax();
		$html = hc_custom_get_landing_page_options_html( 7, 'test-group' );

		$this->assertStringNotContainsString( 'value="admin"', $html );
		$this->assertStringNotContainsString( 'value="invite-anyone"', $html );
	}

	public function test_dropdown_excludes_items_hidden_via_group_settings() {
		$this->enterGroupPage( 7, 'test-group', $this->defaultNavItems() );
		hc_custom_snapshot_group_nav();
		$GLOBALS['_mock_group_meta'][7]['events'] = 'hide';

		$this->enterAdminAjax();
		$html = hc_custom_get_landing_page_options_html( 7, 'test-group' );

		$this->assertStringNotContainsString( 'value="events"', $html );
		$this->assertStringContainsString( 'value="forum"', $html );
	}

	public function test_dropdown_renames_home_to_activity_when_group_has_no_frontpage() {
		$this->enterGroupPage( 7, 'test-group', $this->defaultNavItems() );
		hc_custom_snapshot_group_nav();

		$this->enterAdminAjax();
		$html = hc_custom_get_landing_page_options_html( 7, 'test-group' );

		$this->assertStringContainsString( '>Activity<', $html );
		$this->assertStringNotContainsString( '>Home<', $html );
	}

	public function test_dropdown_keeps_home_label_when_group_has_frontpage() {
		$this->enterGroupPage( 7, 'test-group', $this->defaultNavItems() );
		hc_custom_snapshot_group_nav();
		$GLOBALS['_mock_group_meta'][7]['group_has_frontpage'] = '1';

		$this->enterAdminAjax();
		$html = hc_custom_get_landing_page_options_html( 7, 'test-group' );

		$this->assertStringContainsString( '>Home<', $html );
	}

	public function test_dropdown_marks_saved_landing_page_as_selected() {
		$this->enterGroupPage( 7, 'test-group', $this->defaultNavItems() );
		hc_custom_snapshot_group_nav();
		$GLOBALS['_mock_group_meta'][7]['group_landing_page'] = 'forum';

		$this->enterAdminAjax();
		$html = hc_custom_get_landing_page_options_html( 7, 'test-group' );

		$this->assertMatchesRegularExpression( '/value="forum"[^>]*selected/', $html );
		$this->assertDoesNotMatchRegularExpression( '/value="events"[^>]*selected/', $html );
	}

	public function test_dropdown_uses_live_nav_when_group_nav_is_registered() {
		// No snapshot has ever been taken, but the nav is available (a real
		// group page request): options must still be produced.
		$this->enterGroupPage( 7, 'test-group', $this->defaultNavItems() );

		$html = hc_custom_get_landing_page_options_html( 7, 'test-group' );

		$this->assertStringContainsString( 'value="forum"', $html );
		$this->assertStringContainsString( 'value="events"', $html );
	}

	public function test_dropdown_is_empty_only_when_group_never_visited() {
		$this->enterAdminAjax();

		$this->assertSame( '', hc_custom_get_landing_page_options_html( 99, 'never-visited' ) );
	}

	public function test_snapshot_refreshes_when_group_nav_changes() {
		$this->enterGroupPage( 7, 'test-group', $this->defaultNavItems() );
		hc_custom_snapshot_group_nav();

		// A new tab (Docs) appears on a later page load.
		$items   = $this->defaultNavItems();
		$items[] = array(
			'slug'            => 'docs',
			'name'            => 'Docs',
			'link'            => 'https://example.org/groups/test-group/docs/',
			'screen_function' => 'bp_docs_screen',
		);
		$this->enterGroupPage( 7, 'test-group', $items );
		hc_custom_snapshot_group_nav();

		$this->enterAdminAjax();
		$html = hc_custom_get_landing_page_options_html( 7, 'test-group' );

		$this->assertStringContainsString( 'value="docs"', $html );
	}

	// -----------------------------------------------------------------------
	// Bug (b): hidden menu items must not render in the group nav.
	// -----------------------------------------------------------------------

	public function test_modify_nav_outputs_nothing_for_hidden_items() {
		$this->enterGroupPage( 7, 'test-group', $this->defaultNavItems() );
		$GLOBALS['_mock_group_meta'][7]['events'] = 'hide';

		$item = (object) array(
			'slug'   => 'events',
			'name'   => 'Events',
			'css_id' => 'nav-events',
		);

		$rendered = '<li id="nav-events-groups-li"><a href="https://example.org/groups/test-group/events/">Events</a></li>';

		$this->assertSame( '', hc_custom_modify_nav( $rendered, $item, '' ) );
	}

	public function test_modify_nav_outputs_nothing_for_hidden_items_when_viewer_is_group_admin() {
		$this->enterGroupPage( 7, 'test-group', $this->defaultNavItems() );
		$GLOBALS['_mock_group_meta'][7]['events'] = 'hide';
		$GLOBALS['_hc_mock']['current_user_id']   = 5;
		$GLOBALS['_hc_mock']['group_admins']      = array( '5:7' );

		$item = (object) array(
			'slug'   => 'events',
			'name'   => 'Events',
			'css_id' => 'nav-events',
		);

		$rendered = '<li id="nav-events-groups-li"><a href="#">Events</a></li>';

		$this->assertSame( '', hc_custom_modify_nav( $rendered, $item, '' ) );
	}

	public function test_modify_nav_leaves_visible_items_untouched() {
		$this->enterGroupPage( 7, 'test-group', $this->defaultNavItems() );

		$item = (object) array(
			'slug'   => 'forum',
			'name'   => 'Forum',
			'css_id' => 'nav-forum',
		);

		$rendered = '<li id="nav-forum-groups-li"><a href="#">Forum</a></li>';

		$this->assertSame( $rendered, hc_custom_modify_nav( $rendered, $item, '' ) );
	}

	public function test_modify_nav_keeps_hidden_items_for_super_admins() {
		$this->enterGroupPage( 7, 'test-group', $this->defaultNavItems() );
		$GLOBALS['_mock_group_meta'][7]['events'] = 'hide';
		$GLOBALS['_hc_mock']['is_super_admin']    = true;

		$item = (object) array(
			'slug'   => 'events',
			'name'   => 'Events',
			'css_id' => 'nav-events',
		);

		$rendered = '<li id="nav-events-groups-li"><a href="#">Events</a></li>';

		$this->assertSame( $rendered, hc_custom_modify_nav( $rendered, $item, '' ) );
	}

	public function test_modify_nav_ignores_non_group_context() {
		$GLOBALS['_hc_mock']['is_group'] = false;

		$item = (object) array(
			'slug'   => 'events',
			'name'   => 'Events',
			'css_id' => 'nav-events',
		);

		$rendered = '<li id="nav-events-groups-li"><a href="#">Events</a></li>';

		$this->assertSame( $rendered, hc_custom_modify_nav( $rendered, $item, '' ) );
	}

	public function test_hidden_subnav_items_removed_for_regular_members() {
		$this->enterGroupPage( 7, 'test-group', $this->defaultNavItems() );
		$GLOBALS['_mock_group_meta'][7]['events'] = 'hide';
		$GLOBALS['_hc_mock']['current_user_id']   = 9;

		hc_custom_remove_group_manager_subnav_tabs();

		$this->assertNotContains( 'events', $this->navSlugs() );
		$this->assertContains( 'forum', $this->navSlugs() );
	}

	public function test_hidden_subnav_items_removed_for_group_admins() {
		$this->enterGroupPage( 7, 'test-group', $this->defaultNavItems() );
		$GLOBALS['_mock_group_meta'][7]['events'] = 'hide';
		$GLOBALS['_hc_mock']['current_user_id']   = 5;
		$GLOBALS['_hc_mock']['group_admins']      = array( '5:7' );

		hc_custom_remove_group_manager_subnav_tabs();

		$this->assertNotContains( 'events', $this->navSlugs() );
		$this->assertContains( 'forum', $this->navSlugs() );
	}

	public function test_hidden_subnav_items_kept_for_super_admins() {
		$this->enterGroupPage( 7, 'test-group', $this->defaultNavItems() );
		$GLOBALS['_mock_group_meta'][7]['events'] = 'hide';
		$GLOBALS['_hc_mock']['is_super_admin']    = true;

		hc_custom_remove_group_manager_subnav_tabs();

		$this->assertContains( 'events', $this->navSlugs() );
	}

	// -----------------------------------------------------------------------
	// Settings screen: hidden items must stay listed so they can be re-shown.
	// -----------------------------------------------------------------------

	public function test_settings_table_still_lists_items_removed_from_live_nav() {
		// Snapshot is taken early in the request, while the nav is complete.
		$this->enterGroupPage( 7, 'test-group', $this->defaultNavItems() );
		hc_custom_snapshot_group_nav();
		$GLOBALS['_mock_group_meta'][7]['events'] = 'hide';

		// By render time the hidden item has been removed from the live nav.
		$items = array_values(
			array_filter(
				$this->defaultNavItems(),
				function ( $item ) {
					return 'events' !== $item['slug'];
				}
			)
		);
		$GLOBALS['bp'] = $this->makeBp( $items );

		ob_start();
		hc_custom_get_options_nav();
		$table = ob_get_clean();

		$this->assertStringContainsString( 'group-nav-settings[events]', $table );
		$this->assertStringContainsString( 'group-nav-settings[forum]', $table );
	}

	public function test_settings_table_never_offers_admin_screens() {
		$this->enterGroupPage( 7, 'test-group', $this->defaultNavItems() );
		hc_custom_snapshot_group_nav();

		ob_start();
		hc_custom_get_options_nav();
		$table = ob_get_clean();

		$this->assertStringNotContainsString( 'group-nav-settings[admin]', $table );
		$this->assertStringNotContainsString( 'group-nav-settings[invite-anyone]', $table );
	}

	public function test_settings_table_checks_hide_radio_for_hidden_items() {
		$this->enterGroupPage( 7, 'test-group', $this->defaultNavItems() );
		hc_custom_snapshot_group_nav();
		$GLOBALS['_mock_group_meta'][7]['events'] = 'hide';

		ob_start();
		hc_custom_get_options_nav();
		$table = ob_get_clean();

		$this->assertMatchesRegularExpression(
			'/name="group-nav-settings\[events\]" value="hide"\s+checked="checked"/s',
			$table
		);
	}
}
