<?php
// Fix changed filename, fileloc and filesize in deposit post meta.
// One step in manually replacing a file.
//
// Upload new file on deposit page - cancel deposit.
// Rename file in the uploads/humcore directory.
// Edit and run this script.
// Update the content aggregator custom post entry for this deposit in wp-admin.
 
	// Edit these for the specific deposit.
	$aggregator_post_id = 10182;
	$resource_pid = 'mla:520';
	$filename = 'jesuiscontingency.pdf';
	$filesize = '223120';
	// You can also change filetype ( mimetype ), if needed.
	//$filetype = 'application/pdf';
	$renamed_file = '/srv/www/commons/current/web/app/uploads/humcore/o_1aa736nca1vim1pm1vvlajt1p447.pdf.jesuiscontingency.pdf';
	$datastream_id = 'CONTENT';

        $query_args = array(
                'include'        => $aggregator_post_id,
                'post_parent'    => 0,
                'post_type'      => 'humcore_deposit',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
		'order'          => 'ASC',
                'order_by'       => 'ID',
        );

	global $fedora_api;

	echo "\n\r";
        $deposit_posts = get_posts( $query_args );
	foreach( $deposit_posts as $deposit_post ) {
        	$post_metadata = json_decode( get_post_meta( $deposit_post->ID, '_deposit_file_metadata', true ), true );
		echo $deposit_post->ID, ", ", $deposit_post->post_name, ", ", $deposit_post->post_parent, ", ", $post_metadata['files'][0]['filename'];
		$post_metadata['files'][0]['filename'] = $filename;
		$post_metadata['files'][0]['fileloc'] = $renamed_file;
		// You can also change filetype ( mimetype ), if needed.
		//$post_metadata['files'][0]['filetype'] = $filetype;
		$post_metadata['files'][0]['filesize'] = $filesize;
		$json_metadata = json_encode( $post_metadata, JSON_HEX_APOS );
		if ( json_last_error() ) {
			echo( '*****Error***** File Post Meta Encoding Error - Post ID: ' . $deposit_post->ID . ' - ' . json_last_error_msg() );
			echo "\n\r";
			continue;
		}
		$post_meta_ID = update_post_meta( $deposit_post->ID, '_deposit_file_metadata', wp_slash( $json_metadata ) );
		echo "\n\r";

		// Fedora updates.

                $uploadUrl = $fedora_api->upload( array( 'file' => $renamed_file, 'filename' => $filename, 'filetype' => $post_metadata['files'][0]['filetype'] ) );
                if ( is_wp_error( $uploadUrl ) ) {
                        printf( '*****Error***** - uploadUrl (1) : %1$s-%2$s',  $uploadUrl->get_error_code(), $uploadUrl->get_error_message() );
			echo "\n\r";
                }

                $rContent = $fedora_api->modify_datastream( array(
                                                'pid' => $resource_pid,
                                                'dsID' => $datastream_id,
                                                'controlGroup' => 'M',
                                                'dsLocation' => $uploadUrl,
                                                'dsLabel' => $filename,
                                                'versionable' => true,
                                                'dsState' => 'A',
                                                'mimeType' => $post_metadata['files'][0]['filetype'],
                                                'content' => false,
                                        ) );
                if ( is_wp_error( $rContent ) ) {
                        printf( '*****Error***** - rContent : %1$s-%2$s',  $rContent->get_error_code(), $rContent->get_error_message() );
			echo "\n\r";
                }

                $oContent = $fedora_api->modify_object( array(
                        'pid' => $resource_pid,
                        'label' => $filename,
                ) );
                if ( is_wp_error( $oContent ) ) {
                        printf( '*****Error***** - oContent : %1$s-%2$s',  $oContent->get_error_code(), $oContent->get_error_message() );
                }

	}
	exit();

