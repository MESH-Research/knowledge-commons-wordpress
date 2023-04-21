<?php
/**
 * Updates MailChimp email list with current users.
 *
 * Usage: wp eval-file update-mailchimp.php [weeks] [export]
 */

require_once( __DIR__ . '/vendor/autoload.php');

const LOGFILE = '/srv/www/commons/logs/update-mailchimp.log';

const API_KEY = '7ec2ee0ed5617b202d903aee9b68a535-us9';
const DC = 'us9';
const LIST_ID = 'ab124b16b0'; //commons-active

const RECENT_WEEKS = 50;
const MAX_USERS = 20;

/**
 * Main function.
 */
function main() {
	global $args;

	log_entry( 'Beginning MailChimp update.' );

	if ( $args ) {
		$weeks = $args[0];
	} else {
		$weeks = RECENT_WEEKS;
	}
	
	log_entry( "Recent weeks: $weeks Max users: " . MAX_USERS );

	$recent_users = get_recent_users( $weeks, MAX_USERS );

	log_entry( 'Found ' . count( $recent_users ) . ' recent users.' );
	
	update_mailchimp( $recent_users );

	if ( count( $args ) > 1 && 'export' === $args[1] ) {
		export_users_as_csv( $recent_users );
	}

	log_entry( 'Finished MailChimp update.' );
}

/**
 * Query the database for users who have logged in or have activity within $weeks weeks.
 * 
 * @param int $weeks Number of weeks to look back.
 * @param int $max_users Maximum number of users to return or 0 for no limit.
 */
function get_recent_users( $weeks, $max_users = 0 ) {
	global $wpdb;

	$cutoff_time = time() - ( $weeks * WEEK_IN_SECONDS );

	$bp_activity_table = buddypress()->members->table_name_last_activity;

	// Get all users who have logged in or have activity within $weeks weeks and have not opted-out of the newsletter.
	$users = $wpdb->get_results(
		"SELECT u.ID, u.user_email, u.display_name, s.meta_value AS session_tokens, a.date_recorded AS last_activity 
		FROM $wpdb->users AS u
		LEFT JOIN $wpdb->usermeta AS s ON s.user_id = u.ID AND s.meta_key = 'session_tokens'
		LEFT JOIN $wpdb->usermeta AS n ON n.user_id = u.ID AND n.meta_key = 'newsletter_optin'
		LEFT JOIN $bp_activity_table AS a ON a.user_id = u.ID AND a.type = 'last_activity'
		WHERE (
			UNIX_TIMESTAMP( STR_TO_DATE( a.date_recorded, '%Y-%m-%d %H:%i:%s' ) ) > $cutoff_time
			OR CAST( 
				REGEXP_SUBSTR(
					REGEXP_SUBSTR( s.meta_value, '\"login\";i:[0-9]+;}}' ),
					'[0-9]+'
				)
			AS UNSIGNED ) > $cutoff_time
		)
		AND n.meta_value <> 'no'
		AND u.deleted = 0
		AND u.spam = 0;"
	);

	if ( $max_users ) {
		$users = array_slice( $users, 0, $max_users );
	}

	return $users;
}

/**
 * Export users to CSV.
 * 
 * @param array $users Array of users.
 */
function export_users_as_csv( $users ) {
	log_entry( 'Exporting users to CSV.' );

	$csv = fopen( 'recent-users.csv', 'w' );
	fputcsv( $csv, [ 'ID', 'Email', 'Session Tokens', 'Last Activity' ] );
	foreach ( $users as $user ) {
		fputcsv( $csv, [ $user->ID, $user->user_email, $user->session_tokens, $user->last_activity ] );
	}
	fclose( $csv );
}

/**
 * Update MailChimp email list with current users.
 */
function update_mailchimp( $recent_users, $list_id = LIST_ID ) {

	$mc = mailchimp_connect();

	$current_members = get_current_list_members( $mc, $list_id );

	// Add new users to list.
	foreach ( $recent_users as $user ) {
		if ( 
			! isset( $current_members[ $user->user_email ] ) 
			|| 'subscribed' !== $current_members[ $user->user_email ]->status
		) {
			[ $first_name, $last_name ] = get_first_and_last_name( $user->display_name );
			$response = $mc->lists->setListMember( 
				$list_id,
				md5( $user->user_email ),
				[
					'email_address' => $user->user_email,
					'status' => 'subscribed',
					'merge_fields' => [
						'FNAME' => $first_name,
						'LNAME' => $last_name,
					],
				]
			);
			if ( 'subscribed' !== $response->status ) {
				log_entry( 'Error adding ' . $user->user_email . ' to list.' );
				log_entry( print_r( $response, true ) );
			} else {
				log_entry( 'Added ' . $user->user_email . ' to list.' );
			}
		}
		if ( isset( $current_members[ $user->user_email ] ) ) {
			unset( $current_members[ $user->user_email ] );
		}
	}

	// Remove users who are no longer active.
	foreach ( $current_members as $email => $member ) {
		if ( 'subscribed' === $member->status ) {
			$response = $mc->lists->deleteListMember( $list_id, $email );
			if ( $response->status ) {
				log_entry( 'Error removing ' . $email . ' from list.' );
				log_entry( print_r( $response, true ) );
			} else {
				log_entry( 'Removed ' . $email . ' from list.' );
			}
		}
	}
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
	// Get current list members. MailChimp API only returns 1000 members at a time.
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

/**
 * Test get_current_list_members().
 */
function test_get_current_list_members() {
	$mc = mailchimp_connect();

	$mc->lists->setListMember( 
		LIST_ID,
		md5( 'thickemi@msu.edu' ),
		[
			'email_address' => 'thickemi@msu.edu',
			'status' => 'subscribed',
		] 
	);

	$members = get_current_list_members( $mc, LIST_ID );
	echo count( $members ) . " members found. \n";
}

/**
 * Test get_recent_users().
 */
function test_get_recent_users() {
	$users = get_recent_users( 50, 50 );
	echo count( $users ) . " users found. \n";
}

//test_get_current_list_members();
//test_get_recent_users();
main();