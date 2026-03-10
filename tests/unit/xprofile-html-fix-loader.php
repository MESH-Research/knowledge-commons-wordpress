<?php
/**
 * Loads only the hcommons_fix_corrupted_xprofile_html function for unit testing,
 * avoiding the full functions.php which requires WordPress.
 *
 * This must be kept in sync with site/web/app/themes/hcommons-mpe-theme/functions.php
 */

if ( ! function_exists( 'hcommons_fix_corrupted_xprofile_html' ) ) {
	function hcommons_fix_corrupted_xprofile_html( $value ) {
		if ( empty( $value ) || strpos( $value, '<' ) === false ) {
			return $value;
		}

		// Step 1: Normalize curly/smart quotes inside HTML tags only.
		$value = preg_replace_callback( '/<[^>]+>/', function ( $m ) {
			return str_replace(
				[ "\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}" ],
				[ '"', '"', "'", "'" ],
				$m[0]
			);
		}, $value );

		// Step 2: Strip backslash-quotes left by wp_rel_nofollow's wp_slash().
		if ( strpos( $value, '\"' ) !== false ) {
			$value = wp_unslash( $value );
		}

		// Step 3: Reconstruct <a> tags with proper attribute quoting.
		$value = preg_replace_callback( '/<a\s([^>]*)>/i', function ( $m ) {
			$attrs_str = $m[1];
			do {
				$prev      = $attrs_str;
				$attrs_str = str_replace( '""', '"', $attrs_str );
			} while ( $prev !== $attrs_str );

			$atts = wp_kses_hair( $attrs_str, wp_allowed_protocols() );

			if ( empty( $atts ) ) {
				return $m[0];
			}

			$rel_parts = [];
			$html      = '';
			foreach ( $atts as $name => $att ) {
				if ( 'rel' === $name ) {
					$rel_parts = array_merge( $rel_parts, array_map( 'trim', explode( ' ', $att['value'] ) ) );
					continue;
				}
				if ( isset( $att['vless'] ) && 'y' === $att['vless'] ) {
					$html .= $name . ' ';
				} else {
					$val = $att['value'];
					if ( 'href' === $name && str_starts_with( $val, '//' ) ) {
						$val = 'https:' . $val;
					}
					$html .= $name . '="' . esc_attr( $val ) . '" ';
				}
			}

			$rel_parts = array_unique( array_filter( $rel_parts ) );
			if ( ! empty( $rel_parts ) ) {
				$html .= 'rel="' . esc_attr( implode( ' ', $rel_parts ) ) . '" ';
			}

			return '<a ' . trim( $html ) . '>';
		}, $value );

		return $value;
	}
}
