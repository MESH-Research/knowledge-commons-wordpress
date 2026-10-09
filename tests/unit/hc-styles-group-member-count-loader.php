<?php
/**
 * Loads the hc-styles group member count helper for unit testing with a
 * minimal _n() stub so no WordPress install is needed.
 */

if ( ! function_exists( '_n' ) ) {
	function _n( $single, $plural, $number, $domain = 'default' ) {
		return ( 1 === (int) $number ) ? $single : $plural;
	}
}

require_once __DIR__ . '/../../plugins/hc-styles/includes/group-member-count.php';
