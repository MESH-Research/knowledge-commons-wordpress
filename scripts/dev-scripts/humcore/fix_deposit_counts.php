<?php

// One time data migration to rename and merge deposit download counters.

        $query_args = array(
//                'include'        => 8465,
                'post_parent'    => 0,
                'post_type'      => 'humcore_deposit',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
		'order'          => 'ASC',
                'order_by'       => 'ID',
        );

	global $wpdb;
	define( 'DIEONDBERROR', true );
	$wpdb->show_errors();

	echo "\n\rFix Deposit Counts\n\r";
	$post_total_count = 0;
        $deposit_posts = get_posts( $query_args );
	foreach( $deposit_posts as $deposit_post ) {
        	$metadata = json_decode( get_post_meta( $deposit_post->ID, '_deposit_metadata', true ), true );
        	$file_metadata = json_decode( get_post_meta( $deposit_post->ID, '_deposit_file_metadata', true ), true );
		$aggregator_pid = $metadata['pid'];
		$resource_pid = $file_metadata['files'][0]['pid'];
		$post_total_count++;

		$update_aggregator_view_postmeta = 
			$wpdb->prepare( 
				"
				UPDATE $wpdb->postmeta
				SET meta_key = '%s'
				WHERE post_id = %d
				AND meta_key = '_total_views'
				",
				'_total_views_' . $aggregator_pid,
				$deposit_post->ID
			);
		$uavp_stats = $wpdb->query( $update_aggregator_view_postmeta );

		$select_resource_view_content_count = 
			$wpdb->prepare( 
				"
				SELECT sum( meta_value )
				FROM $wpdb->postmeta
				WHERE post_id = %d
				AND ( meta_key LIKE '_total_views_CONTENT_%%'
				OR meta_key = '_total_views__' )
				",
				$deposit_post->ID
			);
		$srvcc = $wpdb->get_col( $select_resource_view_content_count );

		if ( ! is_null( $srvcc[0] ) ) {
			$update_resource_content_view_postmeta = 
				$wpdb->prepare( 
					"
					UPDATE $wpdb->postmeta
					SET meta_key = '%s',
					meta_value = %d
					WHERE post_id = %d
					AND meta_key LIKE '_total_views_CONTENT_%%'
					",
					'_total_views_CONTENT_' . $resource_pid,
					$srvcc[0],
					$deposit_post->ID
				);
			$urcvp_stats = $wpdb->query( $update_resource_content_view_postmeta );
		}

		$select_resource_view_content_id = 
			$wpdb->prepare( 
				"
				SELECT min( meta_id )
				FROM $wpdb->postmeta
				WHERE post_id = %d
				AND meta_key = '%s'
				",
				$deposit_post->ID,
				'_total_views_CONTENT_' . $resource_pid
			);
		$srvci = $wpdb->get_col( $select_resource_view_content_id );

		if ( ! is_null( $srvci[0] ) ) {
			$delete_resource_content_view_postmeta = 
				$wpdb->prepare( 
					"
					DELETE FROM $wpdb->postmeta
					WHERE post_id = %d
					AND meta_key = '%s'
					AND meta_id != %d
					",
					$deposit_post->ID,
					'_total_views_CONTENT_' . $resource_pid,
					$srvci[0]
				);
			$drcvp_stats = $wpdb->query( $delete_resource_content_view_postmeta );
		}

		$select_resource_view_thumb_count = 
			$wpdb->prepare( 
				"
				SELECT sum( meta_value )
				FROM $wpdb->postmeta
				WHERE post_id = %d
				AND meta_key LIKE '_total_views_THUMB_%%'
				",
				$deposit_post->ID
			);
		$srvtc = $wpdb->get_col( $select_resource_view_thumb_count );

		if ( ! is_null( $srvtc[0] ) ) {
			$update_resource_thumb_view_postmeta = 
				$wpdb->prepare( 
					"
					UPDATE $wpdb->postmeta
					SET meta_key = '%s',
					meta_value = %d
					WHERE post_id = %d
					AND meta_key LIKE '_total_views_THUMB_%%'
					",
					'_total_views_THUMB_' . $resource_pid,
					$srvtc[0],
					$deposit_post->ID
				);
			$urtvp_stats = $wpdb->query( $update_resource_thumb_view_postmeta );
		}

		$select_resource_view_thumb_id = 
			$wpdb->prepare( 
				"
				SELECT min( meta_id )
				FROM $wpdb->postmeta
				WHERE post_id = %d
				AND meta_key = '%s'
				",
				$deposit_post->ID,
				'_total_views_THUMB_' . $resource_pid
			);
		$srvti = $wpdb->get_col( $select_resource_view_thumb_id );

		if ( ! is_null( $srvti[0] ) ) {
			$delete_resource_view_thumb_postmeta = 
				$wpdb->prepare( 
					"
					DELETE FROM $wpdb->postmeta
					WHERE post_id = %d
					AND meta_key = '%s'
					AND meta_id != %d
					",
					$deposit_post->ID,
					'_total_views_THUMB_' . $resource_pid,
					$srvti[0]
				);
			$drvtp_stats = $wpdb->query( $delete_resource_view_thumb_postmeta );
		}

		$select_resource_download_content_count = 
			$wpdb->prepare( 
				"
				SELECT sum( meta_value )
				FROM $wpdb->postmeta
				WHERE post_id = %d
				AND ( meta_key LIKE '_total_downloads_CONTENT_%%'
				OR meta_key = '_total_downloads__' )
				",
				$deposit_post->ID
			);
		$srdcc = $wpdb->get_col( $select_resource_download_content_count );

		if ( ! is_null( $srdcc[0] ) ) {
			$update_resource_content_download_postmeta = 
				$wpdb->prepare( 
					"
					UPDATE $wpdb->postmeta
					SET meta_key = '%s',
					meta_value = %d
					WHERE post_id = %d
					AND meta_key LIKE '_total_downloads_CONTENT_%%'
					",
					'_total_downloads_CONTENT_' . $resource_pid,
					$srdcc[0],
					$deposit_post->ID
				);
			$urcdp_stats = $wpdb->query( $update_resource_content_download_postmeta );
		}

		$select_resource_download_content_id = 
			$wpdb->prepare( 
				"
				SELECT min( meta_id )
				FROM $wpdb->postmeta
				WHERE post_id = %d
				AND meta_key = '%s'
				",
				$deposit_post->ID,
				'_total_downloads_CONTENT_' . $resource_pid
			);
		$srdci = $wpdb->get_col( $select_resource_download_content_id );

		if ( ! is_null( $srdci[0] ) ) {
			$delete_resource_content_download_postmeta = 
				$wpdb->prepare( 
					"
					DELETE FROM $wpdb->postmeta
					WHERE post_id = %d
					AND meta_key = '%s'
					AND meta_id != %d
					",
					$deposit_post->ID,
					'_total_downloads_CONTENT_' . $resource_pid,
					$srdci[0]
				);
			$drcdp_stats = $wpdb->query( $delete_resource_content_download_postmeta );
		}

		$delete_view_postmeta = 
			$wpdb->prepare( 
				"
				DELETE FROM $wpdb->postmeta
				WHERE post_id = %d
				AND meta_key = '_total_views__';
				",
				$deposit_post->ID
			);
		$dv_stats = $wpdb->query( $delete_view_postmeta );

		$delete_download_postmeta = 
			$wpdb->prepare( 
				"
				DELETE FROM $wpdb->postmeta
				WHERE post_id = %d
				AND meta_key = '_total_downloads__';
				",
				$deposit_post->ID
			);
		$dd_stats = $wpdb->query( $delete_download_postmeta );

		echo "\n\r",$deposit_post->ID, ", ", $deposit_post->post_name, ", ",
			$aggregator_pid, ", ",
			$resource_pid, ", ",
			"uavp-", $uavp_stats, ", ",
			"urcvp-", $urcvp_stats, ", ",
			"drcvp-", $drcvp_stats, ", ",
			"urtvp-", $urtvp_stats, ", ",
			"drvtp-", $drvtp_stats, ", ",
			"urcdp-", $urcdp_stats, ", ",
			"drcdp-", $drcdp_stats, ", ",
			"dv-", $dv_stats, ", ",
			"dd-", $dd_stats, ", ",
			"\n\r";
	}

	echo "Total Posts ",$post_total_count;
	echo "\n\r";

	$delete_orphan_postmeta = 
		$wpdb->prepare( 
			"
			DELETE FROM $wpdb->postmeta
			WHERE meta_key = '%s'
			AND post_id NOT IN
 				( SELECT ID FROM $wpdb->posts
				WHERE post_type = 'humcore_deposit'
				AND post_parent = 0)
			",
			'_total_views'
		);
	$do_stats = $wpdb->query( $delete_orphan_postmeta );
	echo "do-", $do_stats;
	echo "\n\r";

	exit();

