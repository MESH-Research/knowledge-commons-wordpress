<?php
/**
 * Updates MailChimp email list with current users.
 *
 * Usage: 	wp eval-file update-mailchimp.php weeks=<weeks> [csv=filename.csv] [dry-run=true] [mail=email@address.com,another@address.com]
 * 
 * 			Parameters must be passed as key=value (no spaces).
 * 				weeks: Number of weeks to look back for recent users.
 * 				csv: Optional. Filename to save CSV of recent users.
 * 				dry-run: Optional. If true, no changes will be made to MailChimp.
 * 				mail: Optional. If set, an update email will be sent to the specified addresses.
 */

require_once( __DIR__ . '/vendor/autoload.php');

const LOGFILE = '/srv/www/commons/logs/update-mailchimp.log';

const API_KEY = '';
const DC = 'us9';
const LIST_ID = 'ab124b16b0'; //commons-active

const HC_ROOT_BLOG_ID = 1000360;

// Facilitate debugging MailChimp API responses.
ini_set( 'log_errors_max_len', 0 );

/**
 * Main function.
 */
function main( $args ) {
	log_entry( 'Beginning MailChimp update.' );

	try {
		$args = parse_args( $args );
		validate_args( $args );
	} catch ( Exception $e ) {
		log_entry( $e->getMessage() );
		exit;
	}

	$recent_users = get_recent_users( $args['weeks'] );

	log_entry( 'Found ' . count( $recent_users ) . ' recent users.' );

	$dry_run = array_key_exists( 'dry-run', $args ) && $args['dry-run'];
	if ( ! $dry_run ) {
		$updated_users = update_mailchimp( $recent_users );
	}
	
	if ( $args['csv'] ) {
		export_users_as_csv( $recent_users, $args['csv'] );
	}

	if ( $args['mail'] ) {
		if ( $args['csv'] ) {
			send_update_email( $args['mail'], $updated_users, $args['csv'] );
		} else {
			send_update_email( $args['mail'], $updated_users );
		}
	}

	log_entry( 'Finished MailChimp update.' );
}

/**
 * Parse arguments into associative array.
 *
 * Arguments should be passed to script as key=value (no spaces).
 *
 * @param Array $args Array of arguments as passed to script by WP CLI eval-file.
 */
function parse_args( $args ) {
	$parsed_args = [];
	foreach ( $args as $arg ) {
		$split_arg = explode( '=', $arg );
		if ( count( $split_arg ) !== 2 ) {
			throw new Exception( "Misformatted argument: '$arg'" );
		}
		if ( is_numeric( $split_arg[1] ) ) {
			$split_arg[1] = intval( $split_arg[1] );
		} elseif ( 'true' === $split_arg[1] ) {
			$split_arg[1] = true;
		} elseif ( 'false' === $split_arg[1] ) {
			$split_arg[1] = false;
		} elseif ( strpos( $split_arg[1], ',' ) !== false ) {
			$split_arg[1] = explode( ',', $split_arg[1] );
		}
		$parsed_args[ $split_arg[0] ] = $split_arg[1];
	}
	return $parsed_args;
}

/**
 * Validate arguments.
 */
function validate_args( $args ) {
	if ( ! array_key_exists( 'weeks', $args ) ) {
		throw new Exception( 'Missing required argument: weeks' );
	}
	if ( ! is_numeric( $args['weeks'] ) ) {
		throw new Exception( 'Invalid argument: weeks must be numeric' );
	}
	if ( array_key_exists( 'csv', $args ) && ! is_string( $args['csv'] ) ) {
		throw new Exception( 'Invalid argument: csv must be string' );
	}
	if ( array_key_exists( 'dry-run', $args ) && ! is_bool( $args['dry-run'] ) ) {
		throw new Exception( 'Invalid argument: dry-run must be boolean' );
	}
}

/**
 * Query the database for users who have logged in or have activity within $weeks weeks.
 * 
 * @param int $weeks Number of weeks to look back.
 * @param int $max_users Maximum number of users to return or 0 for no limit.
 */
function get_recent_users( $weeks ) {
	global $wpdb;

	$cutoff_time = time() - ( $weeks * WEEK_IN_SECONDS );
	
	// Only include HASTAC users who have been active since 9/22/2022 (HASTAC import date)
	$hastac_cutoff_time = mktime( 0, 0, 0, 9, 23, 2022 );

	if ( $cutoff_time > $hastac_cutoff_time ) {
		$hastac_cutoff_time = $cutoff_time;
	}

	$bp_activity_table = buddypress()->members->table_name_last_activity;

	$sql = "SELECT 	u.ID, 
					u.user_email, 
					u.display_name, 
					u.user_login,
					FROM_UNIXTIME( 
						CAST( 
							REGEXP_SUBSTR(
								REGEXP_SUBSTR( s.meta_value, '\"login\";i:[0-9]+;}}' ),
								'[0-9]+'
							) 
							AS UNSIGNED 
						)
					) AS last_session,
					a.date_recorded AS last_activity, 
					n.meta_value AS newsletter_optin,
					m.name AS member_type
			FROM $wpdb->users AS u
			LEFT JOIN $wpdb->usermeta AS s ON s.user_id = u.ID AND s.meta_key = 'session_tokens'
			LEFT JOIN $bp_activity_table AS a ON a.user_id = u.ID AND a.type = 'last_activity'
			LEFT JOIN wp_1000360_term_relationships as r ON u.ID = r.object_id
			LEFT JOIN wp_1000360_term_taxonomy as t ON r.term_taxonomy_id = t.term_taxonomy_id AND t.taxonomy = 'bp_member_type'
			LEFT JOIN wp_1000360_terms as m ON t.term_id = m.term_id
			LEFT JOIN $wpdb->usermeta AS n ON n.user_id = u.ID AND n.meta_key = 'newsletter_optin'
			WHERE (
				UNIX_TIMESTAMP( STR_TO_DATE( a.date_recorded, '%Y-%m-%d %H:%i:%s' ) ) > $cutoff_time
				OR CAST( 
					REGEXP_SUBSTR(
						REGEXP_SUBSTR( s.meta_value, '\"login\";i:[0-9]+;}}' ),
						'[0-9]+'
					)
				AS UNSIGNED ) > $cutoff_time	
			)
			AND ( n.meta_value IS NULL OR n.meta_value = 'yes' )
			AND u.deleted = 0
			AND u.spam = 0
			AND u.user_email IS NOT NULL
			AND u.user_email <> ''";
	
	$users = $wpdb->get_results( $sql );

	// The above query returns duplicate users if they have multiple member types or multiple last_activities.
	// Combine these into a single user object.
	$unique_users = [];
	foreach ( $users as $user ) {
		if ( array_key_exists( $user->ID, $unique_users ) ) {
			if ( $user->last_login > $unique_users[ $user->ID ]->last_login ) {
				$unique_users[ $user->ID ]->last_login = $user->last_login;
			}
			if ( $user->last_activity > $unique_users[ $user->ID ]->last_activity ) {
				$unique_users[ $user->ID ]->last_activity = $user->last_activity;
			}
			if ( 
					! in_array( $user->member_type, $unique_users[ $user->ID ]->member_type ) 
					&& $user->member_type
			) {
				$unique_users[ $user->ID ]->member_type[] = $user->member_type;
			}
		} else {
			if ( $user->member_type ) {
				$user->member_type = [ $user->member_type ];
			} else {
				$user->member_type = [];
			}
			$unique_users[ $user->ID ] = $user;
		}
	}

	// I can't figure out how to do this in the SQL query, so HASTAC users who
	// have not been active since 9/22/2022 are filtered out here.
	$hastac_filtered_users = [];
	foreach ( $unique_users as $user ) {
		if ( 
			is_array( $user->member_type ) && 
			in_array( 'hastac', $user->member_type ) &&
			in_array( 'hc', $user->member_type ) &&
			count( $user->member_type ) === 2 &&
			strtotime( $user->last_activity ) < $hastac_cutoff_time &&
			strtotime( $user->last_login ) < $hastac_cutoff_time
		) {
			continue;
		}
		$hastac_filtered_users[] = $user;
	}

	return $hastac_filtered_users;
}

/**
 * csv users to CSV.
 * 
 * @param array $users Array of users.
 */
function export_users_as_csv( $users, $csv_file = 'recent-users.csv' ) {
	log_entry( 'Exporting users to CSV.' );

	$csv = fopen( $csv_file, 'w' );

	fputcsv( $csv, array_keys( get_object_vars( reset( $users ) ) ) );

	foreach ( $users as $user ) {
		if ( is_array( $user->member_type ) ) {
			$user->member_type = implode( '; ', $user->member_type );
		}
		fputcsv( $csv, get_object_vars( $user ) );
	}
	
	fclose( $csv );
}

function send_update_email( $to, $updated_users, $csv_file = null ) {
	if ( ! is_array( $to ) ) {
		$to = [ $to ];
	}
	
	log_entry( "Sending CSV by email. To: " . implode( ', ', $to ) );

	$email_body = "MailChimp update complete.\n\n";

	$email_body .= "Added users (". count($updated_users['added']) . "):\n";

	foreach ( $updated_users['added'] as $user ) {
		$email_body .= "{$user->user_login} : {$user->user_email} \n";
	}

	$email_body .= "\nRemoved users (" . count( $updated_users['removed'] ) . "):\n";

	foreach ( $updated_users['removed'] as $user ) {
		$email_body .= "{$user->user_login} : {$user->user_email} \n";
	}

	if ( $csv_file ) {
		$attachments = [ $csv_file ];
	} else {
		$attachments = [];
	}

	$result = wp_mail(
		$to,
		'MailChimp update complete',
		$email_body,
		[],
		$attachments
	);

	log_entry( 'Email sent: ' . ( $result ? 'true' : 'false' ) );
}

/**
 * Update MailChimp email list with current users.
 */
function update_mailchimp( $recent_users, $list_id = LIST_ID ) {

	$mc = mailchimp_connect();

	$current_members = get_current_list_members( $mc, $list_id );

	$added_users = [];

	foreach ( $recent_users as $user ) {
		if ( isset( $current_members[ $user->user_email ] ) ) {
			$current_member = $current_members[ $user->user_email ];
		} else {
			$current_member = null;
		}
		if ( 
			is_null( $current_member ) 
			|| 'subscribed' !== $current_members[ $user->user_email ]->status
		) {
			[ $first_name, $last_name ] = get_first_and_last_name( $user->display_name );
			if ( ! $user->member_type ) {
				$user->member_type = [ 'hc' ];
			}
			$request_parameters = [
				'email_address' => $user->user_email,
				'status' => 'subscribed',
				'merge_fields' => [
					'FNAME' => $first_name,
					'LNAME' => $last_name,
				],
				'tags' => $user->member_type,
				'interests' => [
					'ab124b16b0' => true, // Newsletter
				],
			];

			try {
				if ( $current_member ) {
					$response = $mc->lists->updateListMember( 
						$list_id,
						md5( $user->user_email ),
						$request_parameters
					);
				} else {
					$response = $mc->lists->setListMember( 
						$list_id,
						md5( $user->user_email ),
						$request_parameters
					);
				}
			} catch ( Exception $e ) {
				log_entry( 'Error adding ' . $user->user_email . ' to list.' );
				log_entry( $e->getMessage() );
				continue;
			}
			if ( 'subscribed' !== $response->status ) {
				log_entry( 'Error adding ' . $user->user_email . ' to list.' );
				log_entry( print_r( $response, true ) );
			} else {
				$added_users[] = $user;
				log_entry( 'Added ' . $user->user_email . ' to list.' );
			}
		}
		if ( isset( $current_members[ $user->user_email ] ) ) {
			unset( $current_members[ $user->user_email ] );
		}
	}

	$removed_users = [];

	// Remove users who are no longer active.
	// This is "archiving" the member, not permanently deleting them.
	foreach ( $current_members as $email => $member ) {
		if ( 'subscribed' === $member->status ) {
			try {
				$response = $mc->lists->deleteListMember( $list_id, $email );
			} catch ( Exception $e ) {
				log_entry( 'Error removing ' . $email . ' from list.' );
				log_entry( $e->getMessage() );
				continue;
			}
			if ( $response->status ) {
				log_entry( 'Error removing ' . $email . ' from list.' );
				log_entry( print_r( $response, true ) );
			} else {
				$removed_users[] = $user;
				log_entry( 'Removed ' . $email . ' from list.' );
			}
		}
	}

	return [
		'added' => $added_users,
		'removed' => $removed_users,
	];
}

/**
 * Get Mailchimp API client.
 * 
 * @return MailchimpMarketing\ApiClient Mailchimp API client.
 */
function mailchimp_connect() {
	$mc = new MailchimpMarketing\ApiClient();
	$mc->setConfig([
		'apiKey' => API_KEY,
		'server' => DC,
	]);

	return $mc;
}

/**
 * Get current list members.
 * 
 * @param MailchimpMarketing\ApiClient $mc Mailchimp API client.
 * @param string $list_id Mailchimp list ID.
 * 
 * @return array Array of Mailchimp members.
 */
function get_current_list_members( $mc, $list_id ) {
	// MailChimp API only returns 1000 members at a time.
	$current_members = [];
	$offset = 0;
	do {
		$more_members = $mc->lists->getListMembersInfo( 
			$list_id,
			[
				'members.email_address',
				'members.status',
			],
			null,
			1000,
			$offset
		)->members;
		if ( $more_members ) {
			$current_members = array_merge( $current_members, $more_members);
		} else {
			break;
		}
		$offset += 1000;
	} while ( true );

	$current_members_by_email = [];
	foreach ( $current_members as $member ) {
		$current_members_by_email[ $member->email_address ] = $member;
	}
	return $current_members_by_email;
}

/**
 * Convert display_name into first and last name. Assume that the last name is
 * the last word and first name is everything else.
 * 
 * @param string $display_name Display name.
 * @return array Array of first and last name.
 */
function get_first_and_last_name( $display_name ) {
	$names = explode( ' ', $display_name );
	$last_name = array_pop( $names );
	$first_name = implode( ' ', $names );
	return [ $first_name, $last_name ];
}

/**
 * Write entry to logfile.
 */
function log_entry( $message ) {
	error_log( date( 'Y-m-d H:i:s' ) . " $message\n", 3, LOGFILE );
}

main( $args );