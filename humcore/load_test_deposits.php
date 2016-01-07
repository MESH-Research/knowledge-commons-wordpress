<?php

// Add deposits from a commons production copy to a solr/fedora test system.
// The pids in the test system start at 3000 to avoid collision with production pids. We'll need to increase that periodically.
// Currently the katana/sorjuana test system is only partially loaded due to a recurring memory error in solr.

        $query_args = array(
                'post_parent'    => 0,
                'post_type'      => 'humcore_deposit',
                'post_status'    => 'publish',
                'posts_per_page' => 45,
		'order'          => 'ASC',
                'order_by'       => 'ID',
        );

	global $fedora_api, $solr_client;

	echo "\n\r";
        $deposit_posts = get_posts( $query_args );
	foreach( $deposit_posts as $deposit_post ) {
        	$metadata = json_decode( get_post_meta( $deposit_post->ID, '_deposit_metadata', true ), true );
//		echo var_export( $metadata, true );

		// Get the production pids.
		$nextPids = array();
                $nextPids[] = $metadata['pid'];
        	$post_metadata = json_decode( get_post_meta( $deposit_post->ID, '_deposit_file_metadata', true ), true );
		$nextPids[] = $post_metadata['files'][0]['pid'];
		echo var_export( $nextPids, true );

		// Check to see if this deposit has alreasdy been created.
		$fStatus = $fedora_api->validate( array(
			'pid' => $nextPids[0],
		) );
		if ( ! is_wp_error( $fStatus ) ) {
			continue;
		} else if ( 404 !== $fStatus->get_error_code() ) {
			echo 'Error validating ' . $nextPids[0] . ' - ' . $fStatus->get_error_message();
			continue;
		}

		// Get the file data.
		$datastream_id = $post_metadata['files'][0]['datastream_id'];
		$filename = $post_metadata['files'][0]['filename'];
		$filetype = $post_metadata['files'][0]['filetype'];
		$filesize = $post_metadata['files'][0]['filesize'];
		$renamed_file = $post_metadata['files'][0]['fileloc'];
		$fileloc = str_replace( $filename, '', $renamed_file ); // Recover original temp file name.
                $MODS_file = $fileloc . '.MODS.' . $filename . '.xml';
		$thumb_datastream_id = $post_metadata['files'][0]['thumb_datastream_id'];
		$generated_thumb_name = $post_metadata['files'][0]['thumb_filename'];

		// For some reason, we don't have the deposited file - move on.
		if ( ! file_exists( $renamed_file ) ) {
			echo 'Missing file, Pid - ' . $nextPids[0] . ', File name - ' . $renamed_file;
			continue;
		}

                $check_filetype = wp_check_filetype( $filename, wp_get_mime_types() );
                if ( preg_match( '~^image/~', $check_filetype['type'] ) ) {
                        $thumb_image = wp_get_image_editor( $renamed_file );
                        if ( ! is_wp_error( $thumb_image ) ) {
                                $current_size = $thumb_image->get_size();
                                $thumb_image->resize( 150, 150, false );
                                $thumb_filename = $thumb_image->generate_filename( 'thumb', $fedora_api->tempDir . '/', 'jpg' );
                                $generated_thumb = $thumb_image->save( $thumb_filename, 'image/jpeg' );
                                $generated_thumb_path = $generated_thumb['path'];
                                $generated_thumb_name = str_replace( $tempname . '.', '', $generated_thumb['file'] );
                                $generated_thumb_mime = $generated_thumb['mime-type'];
                        } else {
                                echo 'Error - thumb_image : ' . esc_html( $thumb_image->get_error_code() ) . '-' . esc_html( $thumb_image->get_error_message() );
                        }
                }

		// Change the creator in test system.
		// The DOI must be changed, a test DOI only lasts for 2 weeks, so let's not use DOIs for the prodction deposit copies.
		$metadata['creator'] = 'tester';
		$metadata['deposit_doi'] = '';
		$metadata['handle'] = sprintf( bp_get_root_domain() . '/deposits/item/%s/', $nextPids[0] );

		// Prepare the fedora xml and rdf.
                $aggregatorXml = create_aggregator_xml( array(
                                                                'pid' => $nextPids[0],
                                                                'creator' => $metadata['creator'],
                                                 ) );

                $aggregatorRdf = create_aggregator_rdf( array(
                                                                'pid' => $nextPids[0],
                                                                'collectionPid' => $fedora_api->collectionPid,
                                                 ) );

                $aggregatorFoxml = create_foxml( array(
                                                                'pid' => $nextPids[0],
                                                                'label' => '',
                                                                'xmlContent' => $aggregatorXml,
                                                                'state' => 'Active',
                                                                'rdfContent' => $aggregatorRdf,
                                                   ) );

                $metadataMODS = create_mods_xml( $metadata );

		if ( ! file_exists( $MODS_file ) ) {
                	$file_write_status = file_put_contents( $MODS_file, $metadataMODS );
		}

                $resourceXml = create_resource_xml( $metadata, $filetype );

                $resourceRdf = create_resource_rdf( array(
                                                                'aggregatorPid' => $nextPids[0],
                                                                'resourcePid' => $nextPids[1],
                                                ) );

                $resourceFoxml = create_foxml( array(
                                                        'pid' => $nextPids[1],
                                                        'label' => $filename,
                                                        'xmlContent' => $resourceXml,
                                                        'state' => 'Active',
                                                        'rdfContent' => $resourceRdf,
                                                 ) );

                /**
                 * Add solr first, if Tika errors out we'll move on to the next.
                 *
                 * Index the deposit content and metadata in Solr.
                 */
                try {
                        if ( preg_match( '~^audio/|^image/|^video/~', $check_filetype['type'] ) ) {
                                $sResult = $solr_client->create_humcore_document( '', $metadata );
                        } else {
                                $sResult = $solr_client->create_humcore_extract( $renamed_file, $metadata );
                        }
                } catch ( Exception $e ) {
                        if ( '500' == $e->getCode() && strpos( $e->getMessage(), 'TikaException' ) ) {
                                try {
                                        $sResult = $solr_client->create_humcore_document( '', $metadata );
                                        echo __( 'A Tika error occurred while depositing your file!', 'humcore_domain' );
                                } catch ( Exception $e ) {
                                        echo __( 'An error occurred while depositing your file!', 'humcore_domain' );
                                        continue;
                                }
                        } else {
                                echo __( 'An error occurred while depositing your file!', 'humcore_domain' );
                                continue;
                        }
                }

                /**
                 * Create the aggregator Fedora object along with the DC and RELS-EXT datastreams.
                 */
                $aIngest = $fedora_api->ingest( array( 'xmlContent' => $aggregatorFoxml ) );
                if ( is_wp_error( $aIngest ) ) {
                        echo 'Error - aIngest : ' . esc_html( $aIngest->get_error_message() );
                        continue;
                }

                /**
                 * Upload the MODS file to the Fedora server temp file storage.
                 */
                $uploadMODS = $fedora_api->upload( array( 'file' => $MODS_file ) );
                if ( is_wp_error( $uploadMODS ) ) {
                        echo 'Error - uploadMODS : ' . esc_html( $uploadMODS->get_error_message() );
                }

                /**
                 * Create the descMetadata datastream for the aggregator object.
                 */
                $mContent = $fedora_api->add_datastream( array(
                                                'pid' => $nextPids[0],
                                                'dsID' => 'descMetadata',
                                                'controlGroup' => 'M',
                                                'dsLocation' => $uploadMODS,
                                                'dsLabel' => $metadata['title'],
                                                'versionable' => true,
                                                'dsState' => 'A',
                                                'mimeType' => 'text/xml',
                                                'content' => false,
                                        ) );
                if ( is_wp_error( $mContent ) ) {
                        echo esc_html( 'Error - mContent : ' . $mContent->get_error_message() );
                }

                $rIngest = $fedora_api->ingest( array( 'xmlContent' => $resourceFoxml ) );
                if ( is_wp_error( $rIngest ) ) {
                        echo esc_html( 'Error - rIngest : ' . $rIngest->get_error_message() );
                }

                /**
                 * Upload the deposit to the Fedora server temp file storage.
                 */
                $uploadUrl = $fedora_api->upload( array( 'file' => $renamed_file, 'filename' => $filename, 'filetype' => $filetype ) );
                if ( is_wp_error( $uploadUrl ) ) {
                        echo 'Error - uploadUrl : ' . esc_html( $uploadUrl->get_error_message() );
                }

                /**
                 * Create the CONTENT datastream for the resource object.
                 */
                $rContent = $fedora_api->add_datastream( array(
                                                'pid' => $nextPids[1],
                                                'dsID' => $datastream_id,
                                                'controlGroup' => 'M',
                                                'dsLocation' => $uploadUrl,
                                                'dsLabel' => $filename,
                                                'versionable' => true,
                                                'dsState' => 'A',
                                                'mimeType' => $filetype,
                                                'content' => false,
                                        ) );
                if ( is_wp_error( $rContent ) ) {
                        echo 'Error - rContent : ' . esc_html( $rContent->get_error_message() );
                }

                /**
                 * Upload the thumb to the Fedora server temp file storage if necessary.
                 */
                if ( preg_match( '~^image/~', $check_filetype['type'] ) ) {
                        $uploadUrl = $fedora_api->upload( array( 'file' => $generated_thumb_path, 'filename' => $generated_thumb_name, 'filetype' => $generated_thumb_mime ) );
                        if ( is_wp_error( $uploadUrl ) ) {
                                echo 'Error - uploadUrl : ' . esc_html( $uploadUrl->get_error_message() );
                        }

                        /**
                         * Create the THUMB datastream for the resource object if necessary.
                         */
                        $tContent = $fedora_api->add_datastream( array(
                                                        'pid' => $nextPids[1],
                                                        'dsID' => $thumb_datastream_id,
                                                        'controlGroup' => 'M',
                                                        'dsLocation' => $uploadUrl,
                                                        'dsLabel' => $generated_thumb_name,
                                                        'versionable' => true,
                                                        'dsState' => 'A',
                                                        'mimeType' => $generated_thumb_mime,
                                                        'content' => false,
                                                ) );
                        if ( is_wp_error( $tContent ) ) {
                                echo 'Error - tContent : ' . esc_html( $tContent->get_error_message() );
                        }
                }

		$json_metadata = json_encode( $metadata, JSON_HEX_APOS );
		if ( json_last_error() ) {
			echo '*****HumCORE Deposit Error***** Post Meta Encoding Error - Post ID: ' . $deposit_post->ID . ' - ' . json_last_error_msg();
		}
		$post_meta_ID = update_post_meta( $deposit_post->ID, '_deposit_metadata', wp_slash( $json_metadata ) );

		echo 'Test deposit fedora/solr/wp writes complete.';
		echo "\n\r";
sleep (30); // Trying to keep katana from throwing up.
	}

	exit();
