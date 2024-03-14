<?php

/**
 * Replaces media URLs with the S3 bucket / Cloudfront URL.
 */
function hc_replace_media_url( $url ) {
	if ( ! defined( 'S3_UPLOADS_BUCKET_URL' ) ) {
		return $url;
	}
	
	if ( ! defined( 'HC_UPLOADS_BASE_URL' ) ) {
		return $url;
	}

	// Check if the linked attachment exists in the uploads directory and return it if so.
	$uploads_dir = wp_upload_dir();
	$attachment_path = str_replace( $uploads_dir['baseurl'], $uploads_dir['basedir'], $url );
	if ( file_exists( $attachment_path ) ) {
		return $url;
	}

	if ( strpos( $url, HC_UPLOADS_BASE_URL ) !== false ) {
		$url = str_replace( HC_UPLOADS_BASE_URL, S3_UPLOADS_BUCKET_URL, $url );
	}
	
	return $url;
}
//add_filter( 'wp_get_attachment_url', 'hc_replace_media_url', 10, 1 );

function hc_replace_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
	foreach ( $sources as $key => $source ) {
		$sources[ $key ]['url'] = hc_replace_media_url( $source['url'] );
	}
	return $sources;
}
//add_filter( 'wp_calculate_image_srcset', 'hc_replace_srcset', 10, 5 );