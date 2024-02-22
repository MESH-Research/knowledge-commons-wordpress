<?php

// Fix incorrect filetype (mimetype) in deposit post meta.
// Caused by deposit front end error.
 
        $query_args = array(
                'include'        => 9554,
                'post_parent'    => 0,
                'post_type'      => 'humcore_deposit',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
		'order'          => 'ASC',
                'order_by'       => 'ID',
        );

	echo "\n\r";
        $deposit_posts = get_posts( $query_args );
	foreach( $deposit_posts as $deposit_post ) {
        	$post_metadata = json_decode( get_post_meta( $deposit_post->ID, '_deposit_file_metadata', true ), true );
		echo $deposit_post->ID, ", ", $deposit_post->post_name, ", ", $deposit_post->post_parent, ", ", $post_metadata['files'][0]['filetype'];
		$post_metadata['files'][0]['filetype'] = 'application/pdf';
		$json_metadata = json_encode( $post_metadata, JSON_HEX_APOS );
		if ( json_last_error() ) {
			echo( '*****Error***** File Post Meta Encoding Error - Post ID: ' . $deposit_post->ID . ' - ' . json_last_error_msg() );
			echo "\n\r";
			continue;
		}
		$post_meta_ID = update_post_meta( $deposit_post->ID, '_deposit_file_metadata', wp_slash( $json_metadata ) );

		echo "\n\r";
	}

	exit();
