<?php
/**
 * Loads the real BuddyPress Docs access-hardening mu-plugin with stubs
 * sufficient to unit-test its permission decision function in isolation.
 *
 * The mu-plugin registers a template_redirect action at load time; the unit
 * bootstrap provides a no-op add_action(), so requiring the file is safe.
 */

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

require_once dirname( __DIR__, 2 ) . '/mu-plugins/bp-docs-attachment-protection.php';
