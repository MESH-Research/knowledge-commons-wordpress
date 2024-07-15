<?php
/**
 * Configuration overrides for WP_ENV === 'staging'
 */

use Roots\WPConfig\Config;

/**
 * You should try to keep staging as close to production as possible. However,
 * should you need to, you can always override production configuration values
 * with `Config::define`.
 *
 * Example: `Config::define('WP_DEBUG', true);`
 * Example: `Config::define('DISALLOW_FILE_MODS', false);`
 */

Config::define('DISALLOW_INDEXING', true);

/**
 * Docker error logging.
 */
ini_set( 'log_errors', 'on' );
ini_set( 'error_log', 'php://stderr' );
ini_set( 'error_reporting', E_ERROR | E_WARNING | E_USER_ERROR | E_USER_WARNING );