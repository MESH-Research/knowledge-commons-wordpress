<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/group-cover-image-loader.php';

/**
 * Group cover image uploads must be disabled site-wide: BuddyPress removes
 * both the create-a-group "cover image" step and the Manage > Cover Image
 * settings screen when the bp_disable_group_cover_image_uploads filter
 * resolves to true.
 */
class GroupCoverImageTest extends TestCase {

	/**
	 * The filter callback reports uploads as disabled even when the
	 * stored BuddyPress option would allow them.
	 */
	public function testCallbackDisablesUploadsWhenOptionAllowsThem(): void {
		$this->assertTrue( hcommons_disable_group_cover_image_uploads( false ) );
	}

	/**
	 * The filter callback keeps uploads disabled when the option already
	 * disables them.
	 */
	public function testCallbackKeepsUploadsDisabledWhenOptionDisablesThem(): void {
		$this->assertTrue( hcommons_disable_group_cover_image_uploads( true ) );
	}

	/**
	 * The callback returns a strict boolean, matching what BuddyPress
	 * expects from the filter.
	 */
	public function testCallbackReturnsBoolean(): void {
		$this->assertIsBool( hcommons_disable_group_cover_image_uploads() );
	}

	/**
	 * Loading the plugin file wires the disable filter: resolving the
	 * bp_disable_group_cover_image_uploads hook over an enabled (false)
	 * option value must yield true, which is what removes the cover image
	 * step from group creation and the Manage settings screen.
	 */
	public function testLoadingFileRegistersDisableFilter(): void {
		$this->assertTrue(
			_test_apply_captured_filters( 'bp_disable_group_cover_image_uploads', false )
		);
	}
}
