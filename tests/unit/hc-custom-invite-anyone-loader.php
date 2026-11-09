<?php
/**
 * Loads the hc-custom Invite Anyone integration with stubs for the plugin's
 * data layer and the BuddyPress functions it calls. Relies on the shared
 * stores from the other loaders: _hc_mock() for the current user and posts,
 * $GLOBALS['_mock_user_meta'] for user meta, $GLOBALS['hc_test']['loggedin_user']
 * for the login state.
 *
 * Invitations are injected per test through $GLOBALS['_mock_ia_invites'],
 * keyed by invited email, each a list of post objects (ID, post_author).
 * Their meta lives in $GLOBALS['_mock_post_meta'] and their group terms in
 * $GLOBALS['_mock_post_terms']. Group invitations made are recorded in
 * $GLOBALS['_mock_group_invites'].
 */

if ( ! function_exists( 'bp_is_root_blog' ) ) {
	function bp_is_root_blog( $blog_id = 0 ) {
		return ! isset( $GLOBALS['_mock_is_root_blog'] ) || ! empty( $GLOBALS['_mock_is_root_blog'] );
	}
}

if ( ! function_exists( 'bp_core_get_user_email' ) ) {
	function bp_core_get_user_email( $user_id ) {
		return $GLOBALS['_mock_user_emails'][ $user_id ] ?? '';
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( $post_id, $key = '', $single = false ) {
		return $GLOBALS['_mock_post_meta'][ $post_id ][ $key ] ?? '';
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	function update_post_meta( $post_id, $key, $value, $prev = '' ) {
		$GLOBALS['_mock_post_meta'][ $post_id ][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'wp_get_post_terms' ) ) {
	function wp_get_post_terms( $post_id, $taxonomy = 'post_tag', $args = array() ) {
		$names = $GLOBALS['_mock_post_terms'][ $post_id ][ $taxonomy ] ?? array();
		return array_map(
			function ( $name ) {
				return (object) array( 'name' => (string) $name );
			},
			$names
		);
	}
}

if ( ! function_exists( 'groups_invite_user' ) ) {
	function groups_invite_user( $args = array() ) {
		$GLOBALS['_mock_group_invites'][] = $args;
		return true;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( $hook, ...$args ) {
		$GLOBALS['_fired_actions'][ $hook ][] = $args;
	}
}

if ( ! function_exists( 'invite_anyone_get_invited_groups_tax_name' ) ) {
	function invite_anyone_get_invited_groups_tax_name() {
		return 'ia_invited_groups';
	}
}

if ( ! function_exists( 'invite_anyone_get_invitations_by_invited_email' ) ) {
	/**
	 * Stand-in for the plugin's lookup: a WP_Query-like object whose ->posts
	 * holds the invitations addressed to $email.
	 */
	function invite_anyone_get_invitations_by_invited_email( $email ) {
		return (object) array( 'posts' => $GLOBALS['_mock_ia_invites'][ $email ] ?? array() );
	}
}

if ( ! function_exists( 'bp_is_current_component' ) ) {
	function bp_is_current_component( $component ) {
		return ( $GLOBALS['_mock_current_component'] ?? '' ) === $component;
	}
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return trailingslashit( dirname( $file ) );
	}
}

if ( ! function_exists( 'plugins_url' ) ) {
	function plugins_url( $path = '', $plugin = '' ) {
		$base = 'http://example.test/app/plugins';
		// Mirrors WordPress: the folder of the plugin file/dir given, so a
		// call with __DIR__ from hc-custom/includes resolves to hc-custom/.
		if ( '' !== $plugin ) {
			$base .= '/' . basename( dirname( $plugin ) );
		}
		return $base . '/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'esc_attr__' ) ) {
	function esc_attr__( $text, $domain = 'default' ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'is_email' ) ) {
	function is_email( $email ) {
		return false !== filter_var( $email, FILTER_VALIDATE_EMAIL ) ? $email : false;
	}
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( $nonce, $action = -1 ) {
		return in_array( $nonce, (array) ( $GLOBALS['_mock_valid_nonces'][ $action ] ?? array() ), true ) ? 1 : false;
	}
}

if ( ! function_exists( 'wp_nonce_field' ) ) {
	function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $display = true ) {
		$field = '<input type="hidden" name="' . $name . '" value="nonce-for-' . $action . '" />';
		if ( $display ) {
			echo $field;
		}
		return $field;
	}
}

if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( $show = '' ) {
		return $GLOBALS['_mock_bloginfo'][ $show ] ?? 'Test Commons';
	}
}

if ( ! function_exists( 'bp_core_get_user_displayname' ) ) {
	function bp_core_get_user_displayname( $user_id ) {
		return $GLOBALS['_mock_user_names'][ $user_id ] ?? '';
	}
}

if ( ! function_exists( 'is_page_template' ) ) {
	function is_page_template( $template = '' ) {
		return ! empty( $GLOBALS['_mock_is_membership_page'] );
	}
}

if ( ! function_exists( 'is_page' ) ) {
	function is_page( $page = '' ) {
		return ! empty( $GLOBALS['_mock_is_membership_page'] );
	}
}

if ( ! function_exists( 'in_the_loop' ) ) {
	function in_the_loop() {
		return true;
	}
}

if ( ! function_exists( 'is_main_query' ) ) {
	function is_main_query() {
		return true;
	}
}

if ( ! function_exists( 'invite_anyone_mark_as_opt_out' ) ) {
	function invite_anyone_mark_as_opt_out( $email ) {
		$GLOBALS['_mock_opted_out'][] = $email;
		return true;
	}
}

require_once dirname( __DIR__, 2 ) . '/plugins/hc-custom/includes/invite-anyone.php';
