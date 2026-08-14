<?php
/**
 * Unit tests for the hc-custom group navigation fixes (issue #101):
 *
 * 1. The docs tabs on a single doc offer a way back to the owning group's
 *    docs list.
 * 2. The "[group]'s Docs" tab links to the CURRENT group's docs list (not a
 *    relative URL that resolves to another group's doc).
 * 3. The invite-anyone "Send Invites" tab is restored for existing groups on
 *    BuddyPress 12+ when the group allows the current user to send invites.
 */

use PHPUnit\Framework\TestCase;

class GroupNavTest extends TestCase {

	protected function setUp(): void {
		hc_test_reset_state();
	}

	private function make_group( $id, $name, $status = 'public', $docs_url = '' ) {
		$group = (object) array(
			'id'     => $id,
			'name'   => $name,
			'status' => $status,
		);

		$GLOBALS['hc_test']['groups'][ $id ] = $group;

		if ( $docs_url ) {
			$GLOBALS['hc_test']['group_docs_urls'][ $id ] = $docs_url;
		}

		return $group;
	}

	// -------------------------------------------------------------------------
	// Sub-problem 2: group docs link must point at the current group.
	// -------------------------------------------------------------------------

	public function test_group_docs_url_returns_current_group_docs_url() {
		$group = $this->make_group( 5, 'Alpha Group', 'public', 'https://example.org/groups/alpha-group/docs/' );

		$GLOBALS['hc_test']['is_group']      = true;
		$GLOBALS['hc_test']['current_group'] = $group;

		$this->assertSame(
			'https://example.org/groups/alpha-group/docs/',
			hc_custom_get_group_docs_url()
		);
	}

	public function test_group_docs_url_uses_explicit_group_over_current_group() {
		$current = $this->make_group( 5, 'Alpha Group', 'public', 'https://example.org/groups/alpha-group/docs/' );
		$other   = $this->make_group( 9, 'Beta Group', 'public', 'https://example.org/groups/beta-group/docs/' );

		$GLOBALS['hc_test']['is_group']      = true;
		$GLOBALS['hc_test']['current_group'] = $current;

		$this->assertSame(
			'https://example.org/groups/beta-group/docs/',
			hc_custom_get_group_docs_url( $other )
		);
	}

	public function test_group_docs_url_is_empty_when_no_group_available() {
		$this->assertSame( '', hc_custom_get_group_docs_url() );
	}

	public function test_docs_tabs_template_is_overridden_with_hc_custom_copy() {
		$upstream = '/srv/www/plugins/buddypress-docs/includes/templates/docs/tabs-legacy.php';

		$located = hc_custom_bp_docs_tabs_template( $upstream, 'tabs-legacy.php' );

		$this->assertStringEndsWith( 'hc-custom/includes/templates/docs/tabs-legacy.php', $located );
		$this->assertFileExists( $located );
	}

	public function test_docs_tabs_template_override_leaves_other_templates_alone() {
		$upstream = '/srv/www/plugins/buddypress-docs/includes/templates/docs/docs-loop.php';

		$this->assertSame(
			$upstream,
			hc_custom_bp_docs_tabs_template( $upstream, 'docs-loop.php' )
		);
	}

	public function test_docs_tabs_template_override_respects_theme_provided_template() {
		$theme_template = '/srv/www/themes/some-theme/docs/tabs-legacy.php';

		$this->assertSame(
			$theme_template,
			hc_custom_bp_docs_tabs_template( $theme_template, 'tabs-legacy.php' )
		);
	}

	public function test_rendered_tabs_link_group_tab_to_current_group_docs() {
		$group = $this->make_group( 5, 'Alpha Group', 'public', 'https://example.org/groups/alpha-group/docs/' );

		$GLOBALS['hc_test']['is_group']      = true;
		$GLOBALS['hc_test']['current_group'] = $group;

		$html = $this->render_tabs_template();

		$this->assertStringContainsString( 'href="https://example.org/groups/alpha-group/docs/"', $html );
		$this->assertStringContainsString( 'Alpha Group', $html );
		// The upstream bug produced a bare relative href="docs".
		$this->assertStringNotContainsString( 'href="docs"', $html );
	}

	// -------------------------------------------------------------------------
	// Sub-problem 1: single doc view links back to the owning group's docs.
	// -------------------------------------------------------------------------

	public function test_doc_group_docs_tab_returns_link_for_associated_group() {
		$this->make_group( 7, 'Gamma Group', 'public', 'https://example.org/groups/gamma-group/docs/' );
		$GLOBALS['hc_test']['doc_groups'][123] = 7;

		$tab = hc_custom_get_doc_group_docs_tab( 123 );

		$this->assertIsArray( $tab );
		$this->assertSame( 'https://example.org/groups/gamma-group/docs/', $tab['url'] );
		$this->assertStringContainsString( 'Gamma Group', $tab['label'] );
	}

	public function test_doc_group_docs_tab_is_null_when_doc_has_no_group() {
		$GLOBALS['hc_test']['doc_groups'][123] = 0;

		$this->assertNull( hc_custom_get_doc_group_docs_tab( 123 ) );
	}

	public function test_doc_group_docs_tab_is_null_for_hidden_group_non_member() {
		$this->make_group( 7, 'Hidden Group', 'hidden', 'https://example.org/groups/hidden-group/docs/' );
		$GLOBALS['hc_test']['doc_groups'][123]  = 7;
		$GLOBALS['hc_test']['loggedin_user']    = 4;
		$GLOBALS['hc_test']['memberships']      = array();

		$this->assertNull( hc_custom_get_doc_group_docs_tab( 123 ) );
	}

	public function test_doc_group_docs_tab_returned_for_hidden_group_member() {
		$this->make_group( 7, 'Hidden Group', 'hidden', 'https://example.org/groups/hidden-group/docs/' );
		$GLOBALS['hc_test']['doc_groups'][123]      = 7;
		$GLOBALS['hc_test']['loggedin_user']        = 4;
		$GLOBALS['hc_test']['memberships']['4:7']   = true;

		$tab = hc_custom_get_doc_group_docs_tab( 123 );

		$this->assertIsArray( $tab );
		$this->assertSame( 'https://example.org/groups/hidden-group/docs/', $tab['url'] );
	}

	public function test_rendered_tabs_include_back_link_on_single_doc_outside_group() {
		$this->make_group( 7, 'Gamma Group', 'public', 'https://example.org/groups/gamma-group/docs/' );
		$GLOBALS['hc_test']['doc_groups'][123] = 7;
		$GLOBALS['hc_test']['is_group']        = false;
		$GLOBALS['hc_test']['current_doc']     = (object) array( 'ID' => 123 );

		$html = $this->render_tabs_template();

		$this->assertStringContainsString( 'href="https://example.org/groups/gamma-group/docs/"', $html );
		$this->assertStringContainsString( 'Gamma Group', $html );
	}

	public function test_rendered_tabs_have_no_group_link_for_doc_without_group() {
		$GLOBALS['hc_test']['is_group']    = false;
		$GLOBALS['hc_test']['current_doc'] = (object) array( 'ID' => 123 );

		$html = $this->render_tabs_template();

		$this->assertStringNotContainsString( '/groups/', $html );
	}

	// -------------------------------------------------------------------------
	// Helpers.
	// -------------------------------------------------------------------------

	private function render_tabs_template() {
		$template = dirname( __DIR__, 2 ) . '/plugins/hc-custom/includes/templates/docs/tabs-legacy.php';
		$this->assertFileExists( $template );

		$show_create_button = false;

		ob_start();
		include $template;
		return ob_get_clean();
	}
}
