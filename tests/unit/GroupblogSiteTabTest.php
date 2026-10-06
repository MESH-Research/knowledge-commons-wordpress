<?php
/**
 * Unit tests for the group "Site" tab fix (issue #129).
 *
 * bp-groupblog 1.9.4 redirects the Site tab with wp_safe_redirect(), which
 * rejects the group site's subdomain and falls back to wp-admin. hc-custom
 * must (a) allow the group site's host as a redirect target and (b) link the
 * Site tab straight to the group site.
 */

use PHPUnit\Framework\TestCase;

class GroupblogSiteTabTest extends TestCase {

	const SITE_URL = 'https://bputg.hcommons-dev.org';

	protected function setUp(): void {
		$GLOBALS['_hc_mock']               = array();
		$GLOBALS['_mock_group_meta']       = array();
		$GLOBALS['_mock_home_urls']        = array();
		$GLOBALS['_mock_site_options']     = array();
		$GLOBALS['_mock_current_group_id'] = 0;
	}

	private function give_group_a_site( $group_id, $blog_id, $url = self::SITE_URL ) {
		$GLOBALS['_mock_group_meta'][ $group_id ]['groupblog_blog_id'] = (string) $blog_id;
		$GLOBALS['_mock_home_urls'][ $blog_id ]                         = $url;
	}

	private function set_redirect_mode( $mode, $pageslug = '' ) {
		$GLOBALS['_mock_site_options']['bp_groupblog_blog_defaults_options'] = array(
			'redirectblog' => $mode,
			'pageslug'     => $pageslug,
		);
	}

	private function visit_group( $group_id, $slug ) {
		$GLOBALS['_hc_mock']['is_group']           = true;
		$GLOBALS['_hc_mock']['current_group_slug'] = $slug;
		$GLOBALS['_mock_current_group_id']         = $group_id;
	}

	private function set_nav( array $items ) {
		$GLOBALS['_hc_mock']['existing_secondary_nav'] = array_map(
			function ( $item ) {
				return (object) $item;
			},
			$items
		);
	}

	private function nav_link( $slug ) {
		foreach ( buddypress()->groups->nav->get_secondary() as $item ) {
			if ( $item->slug === $slug ) {
				return $item->link;
			}
		}
		return null;
	}

	// -------------------------------------------------------------------------
	// Site URL resolution.
	// -------------------------------------------------------------------------

	public function test_site_url_is_blog_home_when_redirecting_to_home() {
		$this->give_group_a_site( 7, 42 );
		$this->set_redirect_mode( 1 );

		$this->assertSame( self::SITE_URL, hcommons_get_groupblog_site_url( 7 ) );
	}

	public function test_site_url_targets_page_when_redirecting_to_page() {
		$this->give_group_a_site( 7, 42 );
		$this->set_redirect_mode( 2, 'about' );

		$this->assertSame( self::SITE_URL . '/about/', hcommons_get_groupblog_site_url( 7 ) );
	}

	public function test_site_url_handles_string_option_values() {
		$this->give_group_a_site( 7, 42 );
		$this->set_redirect_mode( '1' );

		$this->assertSame( self::SITE_URL, hcommons_get_groupblog_site_url( 7 ) );
	}

	public function test_site_url_is_empty_when_redirects_are_off() {
		$this->give_group_a_site( 7, 42 );
		$this->set_redirect_mode( '' );

		$this->assertSame( '', hcommons_get_groupblog_site_url( 7 ) );
	}

	public function test_site_url_is_empty_when_option_missing() {
		$this->give_group_a_site( 7, 42 );

		$this->assertSame( '', hcommons_get_groupblog_site_url( 7 ) );
	}

	public function test_site_url_is_empty_when_group_has_no_site() {
		$this->set_redirect_mode( 1 );

		$this->assertSame( '', hcommons_get_groupblog_site_url( 7 ) );
	}

	// -------------------------------------------------------------------------
	// Site host resolution.
	// -------------------------------------------------------------------------

	public function test_site_host_is_blog_hostname() {
		$this->give_group_a_site( 7, 42 );

		$this->assertSame( 'bputg.hcommons-dev.org', hcommons_get_groupblog_site_host( 7 ) );
	}

	public function test_site_host_is_lowercased() {
		$this->give_group_a_site( 7, 42, 'https://BPUTG.hcommons-dev.org/' );

		$this->assertSame( 'bputg.hcommons-dev.org', hcommons_get_groupblog_site_host( 7 ) );
	}

	public function test_site_host_is_empty_when_group_has_no_site() {
		$this->assertSame( '', hcommons_get_groupblog_site_host( 7 ) );
	}

	// -------------------------------------------------------------------------
	// allowed_redirect_hosts filter.
	// -------------------------------------------------------------------------

	public function test_group_site_host_is_allowed_on_group_page() {
		$this->give_group_a_site( 7, 42 );
		$this->visit_group( 7, 'bputg-group' );

		$hosts = hcommons_allow_groupblog_redirect_host( array( 'hcommons-dev.org' ) );

		$this->assertContains( 'bputg.hcommons-dev.org', $hosts );
		$this->assertContains( 'hcommons-dev.org', $hosts );
	}

	public function test_hosts_unchanged_outside_a_group() {
		$this->give_group_a_site( 7, 42 );

		$this->assertSame(
			array( 'hcommons-dev.org' ),
			hcommons_allow_groupblog_redirect_host( array( 'hcommons-dev.org' ) )
		);
	}

	public function test_hosts_unchanged_when_group_has_no_site() {
		$this->visit_group( 7, 'bputg-group' );

		$this->assertSame(
			array( 'hcommons-dev.org' ),
			hcommons_allow_groupblog_redirect_host( array( 'hcommons-dev.org' ) )
		);
	}

	public function test_host_is_not_duplicated() {
		$this->give_group_a_site( 7, 42 );
		$this->visit_group( 7, 'bputg-group' );

		$hosts = hcommons_allow_groupblog_redirect_host( array( 'hcommons-dev.org', 'bputg.hcommons-dev.org' ) );

		$this->assertSame( array( 'hcommons-dev.org', 'bputg.hcommons-dev.org' ), $hosts );
	}

	// -------------------------------------------------------------------------
	// Site tab link.
	// -------------------------------------------------------------------------

	public function test_site_tab_links_directly_to_group_site() {
		$this->give_group_a_site( 7, 42 );
		$this->set_redirect_mode( 1 );
		$this->visit_group( 7, 'bputg-group' );
		$this->set_nav( array(
			array( 'slug' => 'home', 'parent_slug' => 'bputg-group', 'link' => 'https://hcommons-dev.org/groups/bputg-group/' ),
			array( 'slug' => 'blog', 'parent_slug' => 'bputg-group', 'link' => 'https://hcommons-dev.org/groups/bputg-group/blog/' ),
		) );

		hcommons_groupblog_nav_link_to_site();

		$this->assertSame( self::SITE_URL, $this->nav_link( 'blog' ) );
		$this->assertSame( 'https://hcommons-dev.org/groups/bputg-group/', $this->nav_link( 'home' ) );
	}

	public function test_site_tab_targets_page_when_redirecting_to_page() {
		$this->give_group_a_site( 7, 42 );
		$this->set_redirect_mode( 2, 'about' );
		$this->visit_group( 7, 'bputg-group' );
		$this->set_nav( array(
			array( 'slug' => 'blog', 'parent_slug' => 'bputg-group', 'link' => 'https://hcommons-dev.org/groups/bputg-group/blog/' ),
		) );

		hcommons_groupblog_nav_link_to_site();

		$this->assertSame( self::SITE_URL . '/about/', $this->nav_link( 'blog' ) );
	}

	public function test_site_tab_left_alone_when_redirects_are_off() {
		$this->give_group_a_site( 7, 42 );
		$this->set_redirect_mode( '' );
		$this->visit_group( 7, 'bputg-group' );
		$this->set_nav( array(
			array( 'slug' => 'blog', 'parent_slug' => 'bputg-group', 'link' => 'https://hcommons-dev.org/groups/bputg-group/blog/' ),
		) );

		hcommons_groupblog_nav_link_to_site();

		$this->assertSame( 'https://hcommons-dev.org/groups/bputg-group/blog/', $this->nav_link( 'blog' ) );
	}

	public function test_site_tab_left_alone_outside_a_group() {
		$this->give_group_a_site( 7, 42 );
		$this->set_redirect_mode( 1 );
		$this->set_nav( array(
			array( 'slug' => 'blog', 'parent_slug' => 'bputg-group', 'link' => 'https://hcommons-dev.org/groups/bputg-group/blog/' ),
		) );

		hcommons_groupblog_nav_link_to_site();

		$this->assertSame( 'https://hcommons-dev.org/groups/bputg-group/blog/', $this->nav_link( 'blog' ) );
	}

	public function test_site_tab_absent_is_not_an_error() {
		$this->give_group_a_site( 7, 42 );
		$this->set_redirect_mode( 1 );
		$this->visit_group( 7, 'bputg-group' );
		$this->set_nav( array(
			array( 'slug' => 'home', 'parent_slug' => 'bputg-group', 'link' => 'https://hcommons-dev.org/groups/bputg-group/' ),
		) );

		hcommons_groupblog_nav_link_to_site();

		$this->assertNull( $this->nav_link( 'blog' ) );
	}
}
