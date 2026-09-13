<?php
/**
 * Loads the real hc-custom Group Email Subscription integration file with
 * stubs sufficient to unit-test its behaviour when the BPGES plugin is
 * absent (its ass_*() functions undefined) and when it is present.
 *
 * IMPORTANT: no ass_*() function may be defined at load time — the "GES
 * absent" tests rely on that. Tests that need GES present define the stubs
 * themselves (and therefore must run after the absence tests).
 */

if ( ! function_exists( 'remove_action' ) ) {
	function remove_action( $hook, $callback, $priority = 10 ) {
		return true;
	}
}

if ( ! function_exists( 'get_user_meta' ) ) {
	function get_user_meta( $user_id, $key = '', $single = false ) {
		return $GLOBALS['_mock_user_meta'][ $user_id ][ $key ] ?? '';
	}
}

if ( ! function_exists( 'update_user_meta' ) ) {
	function update_user_meta( $user_id, $key, $value, $prev = '' ) {
		$GLOBALS['_mock_user_meta'][ $user_id ][ $key ] = $value;
		$GLOBALS['_user_meta_updates'][] = array(
			'user_id' => $user_id,
			'key'     => $key,
			'value'   => $value,
		);
		return true;
	}
}

if ( ! function_exists( 'bp_core_add_message' ) ) {
	function bp_core_add_message( $message, $type = 'success' ) {
		$GLOBALS['_bp_messages'][] = array(
			'message' => $message,
			'type'    => $type,
		);
	}
}

if ( ! function_exists( 'bp_is_settings_component' ) ) {
	function bp_is_settings_component() {
		return ! empty( $GLOBALS['_hc_mock']['is_settings_component'] );
	}
}

if ( ! function_exists( 'bp_displayed_user_id' ) ) {
	function bp_displayed_user_id() {
		return (int) ( $GLOBALS['_hc_mock']['displayed_user_id'] ?? 0 );
	}
}

if ( ! function_exists( 'bp_get_user_meta' ) ) {
	function bp_get_user_meta( $user_id, $key = '', $single = false ) {
		return $GLOBALS['_mock_user_meta'][ $user_id ][ $key ] ?? '';
	}
}

if ( ! function_exists( 'checked' ) ) {
	function checked( $checked, $current = true, $display = true ) {
		$result = (string) $checked === (string) $current ? " checked='checked'" : '';
		if ( $display ) {
			echo $result;
		}
		return $result;
	}
}

if ( ! function_exists( '_e' ) ) {
	function _e( $text, $domain = 'default' ) {
		echo $text;
	}
}

if ( ! function_exists( '_ex' ) ) {
	function _ex( $text, $context, $domain = 'default' ) {
		echo $text;
	}
}

require_once dirname( __DIR__, 2 ) . '/plugins/hc-custom/includes/buddypress-group-email-subscription.php';
