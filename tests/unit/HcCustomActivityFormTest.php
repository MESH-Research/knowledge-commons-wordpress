<?php
/**
 * Regression tests for hc_custom_template_part_filter().
 *
 * The activity post form should be hidden in groups that have a bbPress forum
 * and shown everywhere else — without raising PHP warnings while deciding.
 */

use PHPUnit\Framework\TestCase;

class HcCustomActivityFormTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_mock_bp_is_group']       = false;
		$GLOBALS['_mock_current_group_id']  = 0;
		$GLOBALS['_mock_group_forum_ids']   = array();
	}

	public function test_other_template_slugs_pass_through() {
		$templates = array( 'activity/entry.php' );

		$this->assertSame(
			$templates,
			hc_custom_template_part_filter( $templates, 'activity/entry', '' )
		);
	}

	public function test_post_form_is_shown_outside_groups() {
		$templates = array( 'activity/post-form.php' );

		$this->assertSame(
			$templates,
			hc_custom_template_part_filter( $templates, 'activity/post-form', '' )
		);
	}

	public function test_post_form_is_hidden_when_group_has_a_forum() {
		$GLOBALS['_mock_bp_is_group']      = true;
		$GLOBALS['_mock_current_group_id'] = 42;
		$GLOBALS['_mock_group_forum_ids']  = array( 42 => array( 7 ) );

		$this->assertFalse(
			hc_custom_template_part_filter( array( 'activity/post-form.php' ), 'activity/post-form', '' )
		);
	}

	public function test_post_form_is_shown_when_group_has_no_forum() {
		$GLOBALS['_mock_bp_is_group']      = true;
		$GLOBALS['_mock_current_group_id'] = 42;

		$templates = array( 'activity/post-form.php' );

		$this->assertSame(
			$templates,
			hc_custom_template_part_filter( $templates, 'activity/post-form', '' )
		);
	}
}
