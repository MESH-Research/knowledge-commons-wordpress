<?php
/**
 * Bootstrap for unit tests that exercise xprofile HTML fix without loading WordPress.
 * Provides stubs for WP functions used by the code under test.
 */

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

require_once __DIR__ . '/xprofile-html-fix-loader.php';
