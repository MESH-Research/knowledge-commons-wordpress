<?php
/**
 * Bootstrap for unit tests that exercise xprofile HTML fix without loading WordPress.
 * Provides stubs for WP functions used by the code under test.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return stripslashes( $value );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_allowed_protocols' ) ) {
	function wp_allowed_protocols() {
		return [ 'http', 'https', 'mailto', 'ftp', 'ftps', 'tel' ];
	}
}

if ( ! function_exists( 'wp_kses_hair' ) ) {
	/**
	 * Minimal stub of wp_kses_hair — the WP HTML attribute parser.
	 * Handles double-quoted, single-quoted, and unquoted attribute values.
	 * This is a simplified version sufficient for testing; the real WP function
	 * also validates URI protocols via wp_kses_bad_protocol.
	 */
	function wp_kses_hair( $attr, $allowed_protocols ) {
		$attrarr  = [];
		$mode     = 0;
		$attrname = '';

		while ( strlen( $attr ) !== 0 ) {
			$working = 0;

			switch ( $mode ) {
				case 0: // Looking for attribute name.
					if ( preg_match( '/^([_a-zA-Z][-_a-zA-Z0-9:.]*)/', $attr, $match ) ) {
						$attrname = $match[1];
						$working  = 1;
						$mode     = 1;
						$attr     = preg_replace( '/^[_a-zA-Z][-_a-zA-Z0-9:.]*/', '', $attr );
					}
					break;

				case 1: // Looking for = or whitespace.
					if ( preg_match( '/^\s*=\s*/', $attr ) ) {
						$working = 1;
						$mode    = 2;
						$attr    = preg_replace( '/^\s*=\s*/', '', $attr );
						break;
					}
					if ( preg_match( '/^\s+/', $attr ) ) {
						$working = 1;
						$mode    = 0;
						if ( ! array_key_exists( $attrname, $attrarr ) ) {
							$attrarr[ $attrname ] = [
								'name'  => $attrname,
								'value' => '',
								'whole' => $attrname,
								'vless' => 'y',
							];
						}
						$attr = preg_replace( '/^\s+/', '', $attr );
					}
					break;

				case 2: // Looking for attribute value.
					if ( preg_match( '%^"([^"]*)"(\s+|/?$)%', $attr, $match ) ) {
						$thisval = $match[1];
						if ( ! array_key_exists( $attrname, $attrarr ) ) {
							$attrarr[ $attrname ] = [
								'name'  => $attrname,
								'value' => $thisval,
								'whole' => "$attrname=\"$thisval\"",
								'vless' => 'n',
							];
						}
						$working = 1;
						$mode    = 0;
						$attr    = preg_replace( '/^"[^"]*"(\s+|$)/', '', $attr );
						break;
					}

					if ( preg_match( "%^'([^']*)'(\s+|/?$)%", $attr, $match ) ) {
						$thisval = $match[1];
						if ( ! array_key_exists( $attrname, $attrarr ) ) {
							$attrarr[ $attrname ] = [
								'name'  => $attrname,
								'value' => $thisval,
								'whole' => "$attrname='$thisval'",
								'vless' => 'n',
							];
						}
						$working = 1;
						$mode    = 0;
						$attr    = preg_replace( "/^'[^']*'(\s+|$)/", '', $attr );
						break;
					}

					if ( preg_match( "%^([^\s\"']+)(\s+|/?$)%", $attr, $match ) ) {
						$thisval = $match[1];
						if ( ! array_key_exists( $attrname, $attrarr ) ) {
							$attrarr[ $attrname ] = [
								'name'  => $attrname,
								'value' => $thisval,
								'whole' => "$attrname=\"$thisval\"",
								'vless' => 'n',
							];
						}
						$working = 1;
						$mode    = 0;
						$attr    = preg_replace( "%^[^\s\"']+(\s+|$)%", '', $attr );
					}
					break;
			}

			if ( 0 === $working ) {
				// Not well-formed, skip past the bad character(s).
				$attr = preg_replace( '/^("[^"]*("|$)|\'[^\']*(\'|$)|\S)*\s*/', '', $attr );
				$mode = 0;
			}
		}

		if ( 1 === $mode && ! array_key_exists( $attrname, $attrarr ) ) {
			$attrarr[ $attrname ] = [
				'name'  => $attrname,
				'value' => '',
				'whole' => $attrname,
				'vless' => 'y',
			];
		}

		return $attrarr;
	}
}

if ( ! function_exists( 'str_starts_with' ) ) {
	function str_starts_with( $haystack, $needle ) {
		return strncmp( $haystack, $needle, strlen( $needle ) ) === 0;
	}
}

// --- Stubs for HTTP / transient / REST APIs used by ecs-service-info ----------
// Each of these is overridable per-test via a $GLOBALS['_mock_*'] callback so
// behaviour can be injected without rewriting the function.

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		// no-op for unit tests
	}
}

if ( ! function_exists( 'register_rest_route' ) ) {
	function register_rest_route( $namespace, $route, $args = [], $override = false ) {
		$GLOBALS['_registered_rest_routes'][] = compact( 'namespace', 'route', 'args' );
		return true;
	}
}

if ( ! function_exists( 'wp_remote_get' ) ) {
	function wp_remote_get( $url, $args = [] ) {
		if ( isset( $GLOBALS['_mock_wp_remote_get_callback'] ) ) {
			return call_user_func( $GLOBALS['_mock_wp_remote_get_callback'], $url, $args );
		}
		return new WP_Error( 'http_request_failed', 'no mock configured' );
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	function wp_remote_retrieve_response_code( $response ) {
		if ( is_wp_error( $response ) ) {
			return 0;
		}
		return $response['response']['code'] ?? 0;
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) {
		if ( is_wp_error( $response ) ) {
			return '';
		}
		return $response['body'] ?? '';
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $key ) {
		return $GLOBALS['_mock_transients'][ $key ] ?? false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $key, $value, $ttl = 0 ) {
		$GLOBALS['_mock_transients'][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'wp_script_is' ) ) {
	function wp_script_is( $handle, $status = 'enqueued' ) {
		if ( isset( $GLOBALS['_mock_wp_script_is_callback'] ) ) {
			return (bool) call_user_func( $GLOBALS['_mock_wp_script_is_callback'], $handle, $status );
		}
		return false;
	}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script( $handle, $src = '', $deps = [], $ver = false, $in_footer = false ) {
		$GLOBALS['_enqueued_scripts'][ $handle ] = compact( 'handle', 'src', 'deps', 'ver', 'in_footer' );
	}
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style( $handle, $src = '', $deps = [], $ver = false, $media = 'all' ) {
		$GLOBALS['_enqueued_styles'][ $handle ] = compact( 'handle', 'src', 'deps', 'ver', 'media' );
	}
}

if ( ! function_exists( 'bpeo_is_component' ) ) {
	/**
	 * Stub of bp-event-organiser's component check. Controlled per-test via
	 * $GLOBALS['_mock_bpeo_is_component'].
	 */
	function bpeo_is_component() {
		return ! empty( $GLOBALS['_mock_bpeo_is_component'] );
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $code;
		public $message;
		public $data;
		public function __construct( $code = '', $message = '', $data = [] ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}
		public function get_error_code() {
			return $this->code;
		}
		public function get_error_message() {
			return $this->message;
		}
	}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {
		private $params = [];
		public function get_query_params() {
			return $this->params;
		}
		public function set_query_params( array $params ) {
			$this->params = $params;
		}
	}
}

// --- Stubs for multisite / BuddyPress functions used by bp-group-documents-mail ---
// Blog options are injected per-test via $GLOBALS['_mock_blog_options'][ $blog_id ].

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		// Recorded so tests can resolve captured hooks; otherwise a no-op.
		$GLOBALS['_captured_filters'][ $hook ][] = $callback;
		return true;
	}
}

if ( ! function_exists( 'get_blog_option' ) ) {
	function get_blog_option( $blog_id, $option, $default = false ) {
		return $GLOBALS['_mock_blog_options'][ $blog_id ][ $option ] ?? $default;
	}
}

if ( ! function_exists( 'bp_get_root_blog_id' ) ) {
	function bp_get_root_blog_id() {
		return $GLOBALS['_mock_root_blog_id'] ?? 1;
	}
}

// --- Stubs for object cache / group meta / BP functions used by works-group-extension ---
// Backing stores are $GLOBALS['_mock_wp_cache'] and $GLOBALS['_mock_group_meta'];
// reset them in each test's setUp.

if ( ! function_exists( 'wp_cache_get' ) ) {
	function wp_cache_get( $key, $group = '' ) {
		return $GLOBALS['_mock_wp_cache'][ $group ][ $key ] ?? false;
	}
}

if ( ! function_exists( 'wp_cache_add' ) ) {
	function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
		if ( isset( $GLOBALS['_mock_wp_cache'][ $group ][ $key ] ) ) {
			return false;
		}
		$GLOBALS['_mock_wp_cache'][ $group ][ $key ] = $data;
		return true;
	}
}

if ( ! function_exists( 'wp_cache_set' ) ) {
	function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
		$GLOBALS['_mock_wp_cache'][ $group ][ $key ] = $data;
		return true;
	}
}

if ( ! function_exists( 'groups_get_groupmeta' ) ) {
	function groups_get_groupmeta( $group_id, $meta_key = '', $single = true ) {
		return $GLOBALS['_mock_group_meta'][ $group_id ][ $meta_key ] ?? '';
	}
}

if ( ! function_exists( 'groups_update_groupmeta' ) ) {
	function groups_update_groupmeta( $group_id, $meta_key, $meta_value, $prev_value = '' ) {
		$GLOBALS['_mock_group_meta'][ $group_id ][ $meta_key ] = $meta_value;
		return true;
	}
}

if ( ! function_exists( 'bp_get_current_group_id' ) ) {
	function bp_get_current_group_id() {
		// Shared across suites: falls back to the _hc_mock store used by the
		// group-permissions loader when the direct mock is not set.
		return (int) ( $GLOBALS['_mock_current_group_id'] ?? $GLOBALS['_hc_mock']['current_group_id'] ?? 0 );
	}
}

if ( ! function_exists( 'check_admin_referer' ) ) {
	function check_admin_referer( $action = -1, $query_arg = '_wpnonce' ) {
		return 1;
	}
}

if ( ! function_exists( 'wp_remote_post' ) ) {
	function wp_remote_post( $url, $args = [] ) {
		if ( isset( $GLOBALS['_mock_wp_remote_post_callback'] ) ) {
			return call_user_func( $GLOBALS['_mock_wp_remote_post_callback'], $url, $args );
		}
		return new WP_Error( 'http_request_failed', 'no mock configured' );
	}
}

if ( ! function_exists( 'wp_remote_request' ) ) {
	function wp_remote_request( $url, $args = [] ) {
		if ( isset( $GLOBALS['_mock_wp_remote_request_callback'] ) ) {
			return call_user_func( $GLOBALS['_mock_wp_remote_request_callback'], $url, $args );
		}
		return new WP_Error( 'http_request_failed', 'no mock configured' );
	}
}

require_once __DIR__ . '/xprofile-html-fix-loader.php';
require_once __DIR__ . '/group-permissions-loader.php';
require_once __DIR__ . '/group-nav-loader.php';
