<?php
/**
 * Loader for group navigation unit tests.
 *
 * Stubs the WordPress/BuddyPress functions used by the hc-custom group
 * navigation code, then loads the real plugin files under test. Stub
 * behaviour is driven by $GLOBALS['hc_test'] state so each test can set up
 * its own scenario without any live WordPress installation.
 */

if ( ! isset( $GLOBALS['hc_test'] ) ) {
	$GLOBALS['hc_test'] = array();
}

/**
 * Reset the shared stub state to a known baseline.
 */
function hc_test_reset_state() {
	$GLOBALS['hc_test'] = array(
		'is_group'         => false,
		'current_group'    => null,
		'group_docs_urls'  => array(),
		'doc_groups'       => array(),
		'groups'           => array(),
		'memberships'      => array(),
		'loggedin_user'    => 1,
		'can_moderate'     => false,
		'can_send_invites' => array(),
		'invite_access'    => array(),
	);

	// The shared bootstrap's bp_get_current_group_id() stub reads this.
	$GLOBALS['_mock_current_group_id'] = 0;

	// Clear the group-permissions suite's mock store so its bridged stubs
	// fall through to the hc_test state set up above.
	$GLOBALS['_hc_mock'] = array();

	BP_Invite_Anyone::$instances = array();
}

/**
 * Set the current group for both the hc_test state and the shared
 * bootstrap's bp_get_current_group_id() stub.
 *
 * @param object|null $group Group object, or null to clear.
 */
function hc_test_set_current_group( $group ) {
	$GLOBALS['hc_test']['current_group'] = $group;
	$GLOBALS['_mock_current_group_id']   = empty( $group->id ) ? 0 : (int) $group->id;
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		// no-op for unit tests.
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $value ) {
		return rtrim( $value, '/\\' ) . '/';
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( '_e' ) ) {
	function _e( $text, $domain = 'default' ) {
		echo $text;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return (string) $url;
	}
}

if ( ! defined( 'BP_DOCS_SLUG' ) ) {
	define( 'BP_DOCS_SLUG', 'docs' );
}

// --- BuddyPress / BuddyPress Docs stubs --------------------------------------

if ( ! function_exists( 'bp_is_group' ) ) {
	function bp_is_group() {
		return ! empty( $GLOBALS['hc_test']['is_group'] );
	}
}

if ( ! function_exists( 'groups_get_current_group' ) ) {
	function groups_get_current_group() {
		return $GLOBALS['hc_test']['current_group'] ?? null;
	}
}

if ( ! function_exists( 'bp_get_current_group_id' ) ) {
	function bp_get_current_group_id() {
		$group = $GLOBALS['hc_test']['current_group'] ?? null;
		return empty( $group->id ) ? 0 : (int) $group->id;
	}
}

if ( ! function_exists( 'bp_get_current_group_name' ) ) {
	function bp_get_current_group_name() {
		$group = $GLOBALS['hc_test']['current_group'] ?? null;
		return empty( $group->name ) ? '' : $group->name;
	}
}

if ( ! function_exists( 'bp_docs_get_group_docs_url' ) ) {
	/**
	 * Returns the configured docs URL for a group, resolving the group the
	 * same way the real function does (explicit object/ID, else current).
	 */
	function bp_docs_get_group_docs_url( $group = null ) {
		if ( ! $group ) {
			$group = groups_get_current_group();
		}
		$group_id = is_object( $group ) ? ( $group->id ?? 0 ) : (int) $group;
		if ( ! $group_id ) {
			return '';
		}
		return $GLOBALS['hc_test']['group_docs_urls'][ $group_id ] ?? '';
	}
}

if ( ! function_exists( 'bp_docs_get_associated_group_id' ) ) {
	function bp_docs_get_associated_group_id( $doc_id ) {
		return $GLOBALS['hc_test']['doc_groups'][ $doc_id ] ?? 0;
	}
}

if ( ! function_exists( 'groups_get_group' ) ) {
	function groups_get_group( $args ) {
		$group_id = is_array( $args ) ? ( $args['group_id'] ?? 0 ) : (int) $args;
		return $GLOBALS['hc_test']['groups'][ $group_id ] ?? (object) array();
	}
}

if ( ! function_exists( 'groups_is_user_member' ) ) {
	function groups_is_user_member( $user_id, $group_id ) {
		return ! empty( $GLOBALS['hc_test']['memberships'][ $user_id . ':' . $group_id ] );
	}
}

if ( ! function_exists( 'bp_loggedin_user_id' ) ) {
	function bp_loggedin_user_id() {
		return $GLOBALS['hc_test']['loggedin_user'] ?? 0;
	}
}

if ( ! function_exists( 'bp_current_user_can' ) ) {
	function bp_current_user_can( $capability ) {
		if ( 'bp_moderate' === $capability ) {
			return ! empty( $GLOBALS['hc_test']['can_moderate'] );
		}
		return false;
	}
}

if ( ! function_exists( 'bp_groups_user_can_send_invites' ) ) {
	function bp_groups_user_can_send_invites( $group_id = 0, $user_id = 0 ) {
		return ! empty( $GLOBALS['hc_test']['can_send_invites'][ $group_id ] );
	}
}

if ( ! function_exists( 'invite_anyone_group_invite_access_test' ) ) {
	function invite_anyone_group_invite_access_test( $group_id = 0, $user_id = 0 ) {
		return $GLOBALS['hc_test']['invite_access'][ $group_id ] ?? 'noone';
	}
}

// Template tags used by the overridden docs tabs template.

if ( ! function_exists( 'bp_docs_is_global_directory' ) ) {
	function bp_docs_is_global_directory() {
		return false;
	}
}

if ( ! function_exists( 'bp_docs_archive_link' ) ) {
	function bp_docs_archive_link() {
		echo 'https://example.org/docs/';
	}
}

if ( ! function_exists( 'is_user_logged_in' ) ) {
	function is_user_logged_in() {
		return ! empty( $GLOBALS['hc_test']['loggedin_user'] );
	}
}

if ( ! function_exists( 'bp_is_current_action' ) ) {
	function bp_is_current_action( $action ) {
		return false;
	}
}

if ( ! function_exists( 'bp_docs_mydocs_started_link' ) ) {
	function bp_docs_mydocs_started_link() {
		echo 'https://example.org/docs/started/';
	}
}

if ( ! function_exists( 'bp_docs_mydocs_edited_link' ) ) {
	function bp_docs_mydocs_edited_link() {
		echo 'https://example.org/docs/edited/';
	}
}

if ( ! function_exists( 'bp_is_active' ) ) {
	function bp_is_active( $component ) {
		return true;
	}
}

if ( ! function_exists( 'bp_docs_is_mygroups_docs' ) ) {
	function bp_docs_is_mygroups_docs() {
		return false;
	}
}

if ( ! function_exists( 'bp_docs_mygroups_link' ) ) {
	function bp_docs_mygroups_link() {
		echo 'https://example.org/docs/mygroups/';
	}
}

if ( ! function_exists( 'bp_docs_create_button' ) ) {
	function bp_docs_create_button() {
		// no-op for unit tests.
	}
}

if ( ! function_exists( 'bp_docs_is_existing_doc' ) ) {
	function bp_docs_is_existing_doc() {
		return ! empty( $GLOBALS['hc_test']['current_doc'] );
	}
}

if ( ! function_exists( 'bp_docs_get_current_doc' ) ) {
	function bp_docs_get_current_doc() {
		return $GLOBALS['hc_test']['current_doc'] ?? null;
	}
}

/**
 * Test double for the invite-anyone group extension. Records instantiation
 * and registration so tests can assert whether the nav item was restored.
 */
if ( ! class_exists( 'BP_Invite_Anyone' ) ) {
	class BP_Invite_Anyone {
		public static $instances = array();
		public $registered        = false;

		public function __construct() {
			self::$instances[] = $this;
		}

		public function _register() {
			$this->registered = true;
		}
	}
}

// Load the real hc-custom code under test.
require_once __DIR__ . '/../../plugins/hc-custom/includes/buddypress-docs.php';
require_once __DIR__ . '/../../plugins/hc-custom/includes/invite-anyone.php';
