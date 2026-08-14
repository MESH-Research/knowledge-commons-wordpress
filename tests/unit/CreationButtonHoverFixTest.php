<?php
/**
 * Behavioural tests for the creation-button hover contrast fix enqueue logic.
 *
 * @see https://github.com/MESH-Research/knowledge-commons-wordpress/issues/97
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../plugins/hc-styles/includes/creation-button-hover-fix.php';

class CreationButtonHoverFixTest extends TestCase {

	private const HANDLE = 'hc-styles-creation-button-hover-fix';

	protected function setUp(): void {
		$GLOBALS['_mock_bp_is_group_create'] = false;
		$GLOBALS['_mock_bp_is_create_blog']  = false;
		$GLOBALS['_enqueued_styles']         = [];
	}

	protected function tearDown(): void {
		unset(
			$GLOBALS['_mock_bp_is_group_create'],
			$GLOBALS['_mock_bp_is_create_blog'],
			$GLOBALS['_enqueued_styles']
		);
	}

	public function test_group_creation_page_is_a_creation_context() {
		$GLOBALS['_mock_bp_is_group_create'] = true;

		$this->assertTrue( hc_styles_is_creation_context() );
	}

	public function test_site_creation_page_is_a_creation_context() {
		$GLOBALS['_mock_bp_is_create_blog'] = true;

		$this->assertTrue( hc_styles_is_creation_context() );
	}

	public function test_other_pages_are_not_creation_contexts() {
		$this->assertFalse( hc_styles_is_creation_context() );
	}

	public function test_enqueues_stylesheet_on_group_creation_page() {
		$GLOBALS['_mock_bp_is_group_create'] = true;

		$result = hc_styles_enqueue_creation_button_hover_fix();

		$this->assertTrue( $result );
		$this->assertArrayHasKey( self::HANDLE, $GLOBALS['_enqueued_styles'] );
	}

	public function test_enqueues_stylesheet_on_site_creation_page() {
		$GLOBALS['_mock_bp_is_create_blog'] = true;

		$result = hc_styles_enqueue_creation_button_hover_fix();

		$this->assertTrue( $result );
		$this->assertArrayHasKey( self::HANDLE, $GLOBALS['_enqueued_styles'] );
	}

	public function test_does_not_enqueue_stylesheet_elsewhere() {
		$result = hc_styles_enqueue_creation_button_hover_fix();

		$this->assertFalse( $result );
		$this->assertArrayNotHasKey( self::HANDLE, $GLOBALS['_enqueued_styles'] );
	}

	public function test_enqueued_stylesheet_file_exists_in_plugin() {
		$GLOBALS['_mock_bp_is_group_create'] = true;

		hc_styles_enqueue_creation_button_hover_fix();

		$src = $GLOBALS['_enqueued_styles'][ self::HANDLE ] ?? '';
		$this->assertNotSame( '', $src, 'Stylesheet was not enqueued.' );

		// Map the plugins_url()-style URL back onto the repo's plugins dir.
		$path = parse_url( $src, PHP_URL_PATH );
		$this->assertStringContainsString( '/hc-styles/', $path );
		$relative = substr( $path, strpos( $path, '/hc-styles/' ) );
		$file     = __DIR__ . '/../../plugins' . $relative;

		$this->assertFileExists( $file );
	}
}
