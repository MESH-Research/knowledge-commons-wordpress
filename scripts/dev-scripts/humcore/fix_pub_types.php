<?php

// Rename publication type in deposit post meta for clarity.
 
        $query_args = array(
//                'include'        => 9554,
                'post_parent'    => 0,
                'post_type'      => 'humcore_deposit',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'order'          => 'ASC',
                'order_by'       => 'ID',
        );

        echo "\n\r";
        $deposit_posts = get_posts( $query_args );
        foreach( $deposit_posts as $deposit_post ) {
		$meta_changed = false;
                $metadata = json_decode( get_post_meta( $deposit_post->ID, '_deposit_metadata', true ), true );
                echo $deposit_post->ID, ", ", $deposit_post->post_name, ", ", $metadata['genre'], ", ", $metadata['publication-type'];
		if ( 'book' === $metadata['publication-type'] ) {
			$metadata['publication-type'] = 'book-chapter';
			$meta_changed = true;
		} else if ( 'conference-proceeding' === $metadata['publication-type'] ) {
			$metadata['publication-type'] = 'proceedings-article';
			$meta_changed = true;
		}
		if ( $meta_changed ) {
                	$json_metadata = json_encode( $metadata, JSON_HEX_APOS );
                	if ( json_last_error() ) {
                        	echo( '*****Error***** Post Meta Encoding Error - Post ID: ' . $deposit_post->ID . ' - ' . json_last_error_msg() );
                        	echo "\n\r";
                        	continue;
                	}
                	//$post_meta_ID = update_post_meta( $deposit_post->ID, '_deposit_metadata', wp_slash( $json_metadata ) );

                	echo " metadata updated";
		}
        	echo "\n\r";
        }

        exit();

