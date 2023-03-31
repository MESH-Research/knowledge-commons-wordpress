<?php
/**
 * Updates MailChimp email list with current users.
 *
 * Usage: wp eval-file update-mailchimp.php [weeks] [export]
 */

require_once( __DIR__ . '/vendor/autoload.php');

const API_KEY = '7ec2ee0ed5617b202d903aee9b68a535-us9';
const DC = 'us9';
const LIST_ID = 'f34666534c';

const RECENT_WEEKS = 50;

/**
 * Query the database for users who have logged in or have activity within $weeks weeks.
 */
function get_recent_users( $weeks ) {
	global $wpdb;

	$cutoff_time = time() - ( $weeks * WEEK_IN_SECONDS );

	$bp_activity_table = buddypress()->members->table_name_last_activity;

	// Get all users who have logged in or have activity within $weeks weeks.
	$users = $wpdb->get_results(
		"SELECT u.ID, u.user_email, s.meta_value AS session_tokens, a.date_recorded AS last_activity 
		FROM $wpdb->users AS u
		LEFT JOIN $wpdb->usermeta AS s ON s.user_id = u.ID AND s.meta_key = 'session_tokens'
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
		AND u.deleted = 0
		AND u.spam =0;"
	);

	echo "There were " . count( $users ) . " users found within the last " . $weeks . " weeks. \n";

	return $users;
}

/**
 * Export users to CSV.
 * 
 * @param array $users Array of users.
 */
function export_users_as_csv( $users ) {
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
function update_mailchimp( $recent_users ) {

	$mc = new MailchimpMarketing\ApiClient();
	$mc->setConfig([
		'apiKey' => API_KEY,
		'server' => DC,
	]);

	// Get current list members. MailChimp API only returns 1000 members at a time.
	$current_members = [];
	$offset = 0;
	do {
		$more_members = $mc->lists->getListMembersInfo( 
			LIST_ID,
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

	// Add new users to list.
	foreach ( $recent_users as $user ) {
		if ( 
			! isset( $current_members_by_email[ $user->user_email ] ) 
			|| 'subscribed' !== $current_members_by_email[ $user->user_email ]->status
		) {
			$mc->lists->addListMember( LIST_ID, [
				'email_address' => $user->user_email,
				'status' => 'subscribed',
			] );
			unset( $current_members_by_email[ $user->user_email ] );
		}
	}

	// Remove users who are no longer active.
	foreach ( $current_members_by_email as $email => $member ) {
		if ( 'subscribed' === $member->status ) {
			$mc->lists->deleteListMember( LIST_ID, $email );
		}
	}
}

if ( $args ) {
	$weeks = $args[0];
} else {
	$weeks = RECENT_WEEKS;
}

$recent_users = get_recent_users( $weeks );
// update_mailchimp( $recent_users );

if ( count( $args ) > 1 && 'export' === $args[1] ) {
	export_users_as_csv( $recent_users );
}