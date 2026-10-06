<?php
/**
 * Loads the real hc-custom BuddyPress activity customizations with BP/bbPress
 * stubs sufficient for unit-testing hc_custom_template_part_filter().
 */

if ( ! function_exists( 'bp_is_group' ) ) {
	function bp_is_group() {
		return ! empty( $GLOBALS['_mock_bp_is_group'] );
	}
}

if ( ! function_exists( 'bp_get_current_group_id' ) ) {
	function bp_get_current_group_id() {
		return $GLOBALS['_mock_current_group_id'] ?? 0;
	}
}

if ( ! function_exists( 'buddypress' ) ) {
	function buddypress() {
		return new stdClass();
	}
}

if ( ! function_exists( 'bbp_get_group_forum_ids' ) ) {
	/**
	 * Mirrors real bbPress behaviour: an empty $group_id falls back to the
	 * current group. Forum IDs are injected via $GLOBALS['_mock_group_forum_ids'],
	 * keyed by group ID.
	 */
	function bbp_get_group_forum_ids( $group_id = 0 ) {
		if ( empty( $group_id ) ) {
			$group_id = bp_get_current_group_id();
		}
		return $GLOBALS['_mock_group_forum_ids'][ $group_id ] ?? array();
	}
}

require_once dirname( __DIR__, 2 ) . '/plugins/hc-custom/includes/buddypress/bp-activity.php';
