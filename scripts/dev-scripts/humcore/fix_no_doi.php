<?php

// Fix DOI creation failure for an otherwise complete deposit.
// Can be caused by the ezid server becoming unavailable after the transaction starts.
//
// Create and reserve (mint) the DOI.
// RE-index the solr document.
// Publish the DOI.
// Update the deposit post meta.

	$post_id = 9481;

	if ( empty( $post_id) ) {
		echo 'You need a Post ID!';
		exit();
	}

	global $solr_client;

        $query_args = array(
                'include'        => $post_id,
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

        	$metadata = json_decode( get_post_meta( $deposit_post->ID, '_deposit_metadata', true ), true );

		if ( ! empty( $metadata['deposit_doi'] ) ) {
			echo 'Post has a DOI!', $deposit_post->ID, ", ", $deposit_post->post_name, ", ", $metadata['deposit_doi'], "\n\r";
			exit();
		}

                // Mint a DOI.
                $creators = array();
                foreach ( $metadata['authors'] as $author ) {
                        if ( ( 'author' === $author['role'] ) && ! empty( $author['fullname'] ) ) {
                                $creators[] = $author['fullname'];
                        }
                }
                $creator_list = implode( ',', $creators );

                $deposit_doi = humcore_create_handle(
                                $metadata['title'],
                                $metadata['pid'],
                                $creator_list,
                                $metadata['genre'],
                                $metadata['date_issued'],
                                $metadata['publisher']
                        );
                if ( false === $deposit_doi ) {
                        echo 'There was an EZID API error, the DOI was not sucessfully minted.', "\n\r";
			exit();
                } else {
                        $metadata['handle'] = 'http://dx.doi.org/' . str_replace( 'doi:', '', $deposit_doi );
                        $metadata['deposit_doi'] = $deposit_doi; // Not stored in solr.
                }

		// Reindex solr document.
        	$file_metadata = json_decode( get_post_meta( $post_id, '_deposit_file_metadata', true ), true );
                $resource_filename = $file_metadata['files'][0]['filename'];
                $resource_filesize = $file_metadata['files'][0]['filesize'];
                $resource_fileloc = $file_metadata['files'][0]['fileloc'];
                $check_resource_filetype = wp_check_filetype( $resource_filename, wp_get_mime_types() );

                try {
                        if ( preg_match( '~^audio/|^image/|^video/~', $check_resource_filetype['type'] ) ) {
                                $sResult = $solr_client->create_humcore_document( '', $metadata );
                        } else {
                                $sResult = $solr_client->create_humcore_extract( $resource_fileloc, $metadata );
                        }
                } catch ( Exception $e ) {

                        echo 'An error occurred while reindexing the file!';
                        exit();
                }

		// Publish the DOI.
                $eStatus = humcore_publish_handle( $metadata['deposit_doi'] );
                if ( false === $eStatus ) {
                        echo 'There was an EZID API error, the DOI was not sucessfully published.', "\n\r";
			exit();
                }
                if ( defined( 'CORE_ERROR_LOG' ) && '' != CORE_ERROR_LOG ) {
                        humcore_write_error_log( 'HumCORE deposit DOI published' );
                }

		// Update post meta
		$json_metadata = json_encode( $metadata, JSON_HEX_APOS );
		if ( json_last_error() ) {
			echo( '*****Error***** Post Meta Encoding Error - Post ID: ' . $deposit_post->ID . ' - ' . json_last_error_msg() );
			echo "\n\r";
			continue;
		}

		$post_meta_ID = update_post_meta( $deposit_post->ID, '_deposit_metadata', wp_slash( $json_metadata ) );

		echo $deposit_post->ID, ", ", $deposit_post->post_name, ", ",
			$metadata['pid'], ", ",
			$metadata['handle'], ", ",
			$metadata['deposit_doi'];

		echo "\n\r";
	}

	exit();
