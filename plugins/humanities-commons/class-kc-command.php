<?php

namespace KC;

/**
 * WP-CLI commands for Knowledge Commons.
 */
class KC_Command {

}

if ( class_exists( '\WP_CLI' ) ) {
	\WP_CLI::add_command( 'kc', 'KC\KC_Command' );
}