<?php
/**
 * Convert PDFjs and Google Docs Embedder shortcodes to links.
 *
 * This is a one-time script to replace PDFjs and Google Docs Embedder
 * shortcodes with links in order to remove those plugins.
 * 
 */

// Run mla separately
$all_network_slugs      = [ 'hc', 'ajs', 'arlisna', 'aseees', 'caa', 'msu', 'up', 'mla' ];
$included_network_slugs = [ 'hc', 'ajs', 'arlisna', 'aseees', 'caa', 'msu', 'up' ];
# $included_network_slugs = [ 'mla' ];

$replace_docs  = True;
$replace_pdfjs = False;
$dry_run       = False;

$pdfjs_pattern = '/\[pdfjs-viewer.*url="(\S*)".*\]/';
$docs_pattern  = '/\[gview.*file="(\S*)".*?\]/';

foreach ( get_networks() as $network ) {
	$network_slug = explode( '.', $network->domain )[0];
	if ( ! in_array( $network_slug, $all_network_slugs ) ) {
		$network_slug = 'hc';
	}
	if ( ! in_array( $network_slug, $included_network_slugs ) ) {
		continue;
	}
	$args = [ 
		'network_id' => $network->id,
		'number'     => 0, 
	];
	$sites = get_sites( $args );
	foreach ( $sites as $site ) {
		switch_to_blog( $site->blog_id );
		$args = [
			'numberposts' => -1,
			'post_type' => 'any',
			'post_status' => 'any',
		];
		foreach ( get_posts( $args ) as $post ) {
			$content = $post->post_content;
			if ( ! $content ) {
				continue;
			}

			$changes = false;

			if ( $replace_pdfjs ) {
				preg_match_all( $pdfjs_pattern, $content, $pdfjs_matches );
				if ( count( $pdfjs_matches ) === 2 && count( $pdfjs_matches[0] ) ) {
					echo urldecode( get_permalink( $post ) ) . "\n";
					for ( $i = 0; $i < count( $pdfjs_matches[0] ); $i++ ) {
						$shortcode         = $pdfjs_matches[0][$i];
						$decoded_shortcode = urldecode( $shortcode );
						$url               = urldecode( $pdfjs_matches[1][$i] );
						$link              = "<a href='$url'>View PDF</a>";
						echo "$decoded_shortcode => $link\n";
						if ( ! $dry_run ) {
							$content = str_replace( $shortcode, $link, $content );
							$changes = true;
						}
					}
				}
			}

			if ( $replace_docs ) {
				preg_match_all( $docs_pattern, $content, $docs_matches );
				if ( count( $docs_matches ) === 2 && count( $docs_matches[0] ) ) {
					echo urldecode( get_permalink( $post ) ) . "\n";
					for ( $i = 0; $i < count( $docs_matches[0] ); $i++ ) {
						$shortcode         = $docs_matches[0][$i];
						$decoded_shortcode = urldecode( $shortcode );
						$url               = urldecode( $docs_matches[1][$i] );
						if ( str_ends_with( $url, 'pdf' ) || str_ends_with( $url, 'PDF') ) {
							$link_text = 'View PDF';
						} else {
							$link_text = 'View Doc';
						}
						$link = "<a href='$url'>$link_text</a>";
						echo "$decoded_shortcode => $link\n";
						if ( ! $dry_run ) {
							$content = str_replace( $shortcode, $link, $content );
							$changes = true;
						}
					}
				}
			}
			if ( $changes && ! $dry_run ) {
				$post_arr = [
					'ID' => $post->ID,
					'post_content' => $content
				];
				remove_all_actions( 'save_post' );
				wp_update_post( $post_arr, false, false );
			}
		}
		restore_current_blog();
	}
}