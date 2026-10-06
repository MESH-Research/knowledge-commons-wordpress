<?php
/**
 * Stubs for BPGES (buddypress-group-email-subscription) functions, loaded
 * on demand by tests that exercise the "BPGES present" behaviour.
 *
 * Once required, function_exists( 'ass_group_subscription' ) is true for the
 * remainder of the process, so tests that need BPGES absent must run first.
 */

if ( ! function_exists( 'ass_group_subscription' ) ) {
	function ass_group_subscription( $status, $user_id, $group_id ) {
		$GLOBALS['_ass_subscription_calls'][] = array(
			'status'   => $status,
			'user_id'  => $user_id,
			'group_id' => $group_id,
		);
		return true;
	}
}

if ( ! function_exists( 'ass_subscribe_translate' ) ) {
	function ass_subscribe_translate( $status ) {
		return strtoupper( $status );
	}
}

if ( ! function_exists( 'ass_get_forum_type' ) ) {
	function ass_get_forum_type() {
		return $GLOBALS['_mock_ass_forum_type'] ?? 'bbpress';
	}
}
