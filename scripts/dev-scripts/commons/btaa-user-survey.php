<?php
/**
 * Script to generate spreadsheet of BTAA users showing CORE deposits, groups and sites.
 * 
 * Members are identified by email address.
 * 
 * Members:
 * University of Illinois - illinois.edu
 * Indiana University - iu.edu / indiana.edu / iupui.edu
 * University of Iowa - uiowa.edu
 * University of Maryland - umd.edu
 * University of Michigan - umich.edu
 * Michigan State University - msu.edu
 * University of Minnesota - umn.edu
 * University of Nebraska-Lincoln - unl.edu
 * Northwestern University - northwestern.edu / u.northwestern.edu
 * Ohio State University - osu.edu / buckeyemail.osu.edu
 * Pennsylvania State University - psu.edu
 * Purdue University - purdue.edu
 * Rutgers University-New Brunswick - rutgers.edu / scarletmail.rutgers.edu
 * University of Wisconsin-Madison uwisc.edu
 * 
 * syntax: wp eval-file btaa-user-survey.php
 * 
 * @see https://github.com/MESH-Research/hc-community/issues/15
 */

define( 'ON_DEV', true );

define( 'MAX_USERS', 1000 );

define( 'OUTPUTFILE', 'btaa_survey.csv' );

function main() {
	$email_domains = [
		'illinois.edu'     => 'University of Illinois',
		'iu.edu'           => 'Indiana University',
		'indiana.edu'      => 'Indiana University',
		'iupui.edu'        => 'Indiana University',
		'uiowa.edu'        => 'University of Iowa',
		'umd.edu'          => 'University of Maryland',
		'umich.edu'        => 'University of Michigan',
		'msu.edu'          => 'Michigan State University',
		'umn.edu'          => 'University of Minnesota',
		'unl.edu'          => 'University of Nebraska-Lincoln',
		'northwestern.edu' => 'Northwestern University',
		'osu.edu'          => 'Ohio State University',
		'psu.edu'          => 'Pennsylvania State University',
		'purdue.edu'       => 'Purdue University',
		'rutgers.edu'      => 'Rutgers University-New Brunswick',
		'uwisc.edu'        => 'University of Wisconsin-Madison',
	];

	$csv_file = fopen( OUTPUTFILE, 'w' );

	fputcsv(
		$csv_file,
		[
			'Name',
			'Email',
			'Profile',
			'Affiliation',
			'Group Count',
			'Site Count',
			'Deposit Count',
			'Groups',
			'Sites',
			'Deposits'
		]
	);

	$users = get_users(
		[
			'blog_id' => 0
		]
	);

	print( 'Total users: ' . count( $users) . "\n" );

	$loop_count = 0;
	foreach ( $users as $user ) {
		$loop_count++;
		$user_email = $user->data->user_email;
		$email_parts = explode( '@', $user_email );
		$email_domain = end( $email_parts );

		if ( ON_DEV && strpos( $email_domain, 'sign' ) === 0 ) {
			$email_domain = substr( $email_domain, 4 );
		}


		$affiliation = false;
		foreach ( array_keys( $email_domains ) as $target_domain ) {
			if ( strpos( $email_domain, $target_domain ) !== false ) {
				$affiliation = $email_domains[ $target_domain ];
				break;
			}
		}

		$profile_affiliation = xprofile_get_field_data( 'Institutional or Other Affiliation', $user->ID );

		if ( ! $affiliation ) {
			foreach ( array_values( $email_domains ) as $target_affiliation ) {
				if ( strpos( strtolower( $profile_affiliation ), strtolower( $target_affiliation ) ) !== false ) {
					$affiliation = $target_affiliation;
					break;
				}
			}
		}

		if ( ! $affiliation ) {
			// echo "No match for $email_domain or $profile_affiliation.\n";
			continue;
		}

		// Groups

		$user_groups = [];
		$has_groups = bp_has_groups(
			[
				'user_id'    => $user->ID,
				'action'     => '',
				'type'       => '',
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);

		while ( $has_groups && bp_groups() ) {
			bp_the_group();
			$user_groups[] = bp_get_group_name();
		}
		
		// Sites

		$blogs = get_blogs_of_user( $user->ID );
		$user_blogs = [];
		foreach ( $blogs as $user_blog ) {
			switch_to_blog( $user_blog->userblog_id );
			$userdata = get_userdata( $user->ID );
			$user_roles = $userdata->roles;
			if ( 
				count( 
					array_intersect( 
						[ 'author', 'editor', 'administrator' ],
						$user_roles
					)
				) > 0
			) {
				$user_blogs[] = $user_blog->domain;
			}
			restore_current_blog();
		}

		// CORE Deposits

		$core_deposits = get_posts(
			[
				'post_parent' => 0,
				'author' => $user->ID,
				'post_type'   => 'humcore_deposit'
			]
		);

		$user_deposits = [];
		foreach ( $core_deposits as $user_deposit ) {
			xdebug_break();
			$user_deposits[] = $user_deposit->post_title; 
		}

		// Echo summary data
		$group_count = count( $user_groups );
		$site_count = count( $user_blogs );
		$deposit_count = count( $user_deposits );
		echo "{$user->display_name}({$user->user_login}) - $affiliation - Groups: $group_count Sites: $site_count Core Deposits: $deposit_count\n";

		// Write to csv
		fputcsv(
			$csv_file,
			[
				$user->display_name,
				$user_email,
				"https://hcommons.org/members/{$user->user_login}/",
				$affiliation,
				$group_count,
				$site_count,
				$deposit_count,
				implode( '; ', $user_groups ),
				implode( '; ', $user_blogs ),
				implode( '; ', $user_deposits )
			]
		);

		if ( MAX_USERS != 0 && $loop_count > MAX_USERS ) {
			break;	
		}

	
	}
	fclose( $csv_file );
}


main();