<?php
/**
 * Loads plugins/hc-custom/includes/buddypress/bp-groups.php for unit testing.
 *
 * Provides guarded stubs for the WP/BuddyPress functions the group nav
 * settings code calls at runtime. Behaviour is configured per-test through
 * $GLOBALS['_hc_mock'] (see group-permissions-loader.php, loaded from the
 * bootstrap, which supplies _hc_mock(), bp_is_group(), is_super_admin(),
 * get_current_user_id() and groups_is_user_admin()) and through the
 * $GLOBALS['_mock_group_meta'] store from the bootstrap.
 *
 * The BuddyPress nav object is faked with HC_Test_BP_Options_Nav: tests
 * assign an instance to $GLOBALS['bp']->groups->nav and the
 * bp_core_remove_subnav_item() stub mutates it, so removal behaviour can be
 * asserted on resulting nav state rather than on recorded calls.
 */

if ( ! class_exists( 'HC_Test_BP_Options_Nav' ) ) {
	class HC_Test_BP_Options_Nav {
		/** @var object[] */
		public $items = array();

		public function __construct( array $items = array() ) {
			foreach ( $items as $item ) {
				$this->items[] = (object) $item;
			}
		}

		public function get_secondary( $args = array(), $sort = true ) {
			if ( empty( $this->items ) ) {
				// BP_Core_Nav::get_secondary() returns false when nothing matches.
				return false;
			}

			return $this->items;
		}
	}
}

if ( ! function_exists( 'bp_get_current_group_slug' ) ) {
	function bp_get_current_group_slug() {
		return (string) _hc_mock( 'current_group_slug', '' );
	}
}

if ( ! function_exists( 'bp_get_group_id' ) ) {
	function bp_get_group_id() {
		return (int) _hc_mock( 'current_group_id', 0 );
	}
}

if ( ! function_exists( 'bp_current_item' ) ) {
	function bp_current_item() {
		return _hc_mock( 'current_item', false );
	}
}

if ( ! function_exists( 'bp_current_component' ) ) {
	function bp_current_component() {
		return _hc_mock( 'current_component', 'groups' );
	}
}

if ( ! function_exists( 'bp_action_variable' ) ) {
	function bp_action_variable( $position = 0 ) {
		$vars = _hc_mock( 'action_variables', array() );
		return isset( $vars[ $position ] ) ? $vars[ $position ] : false;
	}
}

if ( ! function_exists( 'bp_core_remove_subnav_item' ) ) {
	function bp_core_remove_subnav_item( $parent_slug, $slug, $component = 'members' ) {
		$nav = isset( $GLOBALS['bp']->groups->nav ) ? $GLOBALS['bp']->groups->nav : null;

		if ( $nav instanceof HC_Test_BP_Options_Nav ) {
			$nav->items = array_values(
				array_filter(
					$nav->items,
					function ( $item ) use ( $slug ) {
						return $item->slug !== $slug;
					}
				)
			);
		}

		return true;
	}
}

require_once dirname( __DIR__, 2 ) . '/plugins/hc-custom/includes/buddypress/bp-groups.php';
