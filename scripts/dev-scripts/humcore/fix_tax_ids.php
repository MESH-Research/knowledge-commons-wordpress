<?php

// One time data migration to add group, subject and keyword ID values to deposits post meta.

        $query_args = array(
//                'include'        => 9454,
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
        	$metadata = json_decode( get_post_meta( $deposit_post->ID, '_deposit_metadata', true ), true );

		$metadata['group_ids'] = array();
		if ( ! empty( $metadata['group'] ) ) {
			foreach ( $metadata['group'] as $group_name ) {
				$group_id = humcore_get_id_from_name( $group_name );
				$metadata['group_ids'][] = $group_id;
			}
		}
		$metadata['subject_ids'] = array();
		if ( ! empty( $metadata['subject'] ) ) {
			foreach ( $metadata['subject'] as $subject_name ) {
				$term = get_term_by( 'name', $subject_name, 'humcore_deposit_subject' );
				$metadata['subject_ids'][] = $term->term_taxonomy_id;
			}
		}
		$metadata['keyword_ids'] = array();
		if ( ! empty( $metadata['keyword'] ) ) {
			foreach ( $metadata['keyword'] as $keyword_name ) {
				$term = get_term_by( 'name', $keyword_name, 'humcore_deposit_tag' );
				$metadata['keyword_ids'][] = $term->term_taxonomy_id;
			}
		}

		echo $deposit_post->ID, ", ", $deposit_post->post_name, ", ",
			var_export( $metadata['group'], true ), ", ",
			var_export( $metadata['group_ids'], true ), ", ",
			var_export( $metadata['subject'], true ), ", ",
			var_export( $metadata['subject_ids'], true ), ", ",
			var_export( $metadata['keyword'], true ), ", ",
			var_export( $metadata['keyword_ids'], true );
		$json_metadata = json_encode( $metadata, JSON_HEX_APOS );
		if ( json_last_error() ) {
			echo( '*****Error***** Post Meta Encoding Error - Post ID: ' . $deposit_post->ID . ' - ' . json_last_error_msg() );
			echo "\n\r";
			continue;
		}

		$post_meta_ID = update_post_meta( $deposit_post->ID, '_deposit_metadata', wp_slash( $json_metadata ) );

		echo "\n\r";
	}

	exit();
