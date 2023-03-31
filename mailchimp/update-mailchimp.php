<?php
/**
 * Updates MailChimp email list with current users.
 *
 * Usage: wp eval-file /home/ubuntu/dev-scripts/mailchimp/update-mailchimp.php
 */

require_once( __DIR__ . '/vendor/autoload.php');

const API_KEY = '7ec2ee0ed5617b202d903aee9b68a535-us9';
const DC = 'us9';
const LIST_ID = 'f34666534c';

const RECENT_WEEKS = 26;

/**
 * Query the database for users who have logged in or have activity within RECENT_WEEKS weeks.
 */
function get_recent_users() {
	global $wpdb;

	$cutoff_time = time() - ( RECENT_WEEKS * WEEK_IN_SECONDS );

	// Get all users who have logged in or have activity within RECENT_WEEKS weeks.
	$users = $wpdb->get_results(
		"SELECT ID, user_email, s.meta_value AS session_tokens, a.meta_value AS last_activity 
		FROM $wpdb->users
		LEFT JOIN $wpdb->usermeta AS s ON s.user_id = $wpdb->users.ID AND s.meta_key = 'session_tokens'
		LEFT JOIN $wpdb->usermeta AS a ON a.user_id = $wpdb->users.ID AND a.meta_key = 'last_activity'
		WHERE UNIX_TIMESTAMP( STR_TO_DATE( a.meta_value, '%Y-%m-%d %H:%i:%s' ) ) > $cutoff_time
		OR CAST( 
			REGEXP_SUBSTR(
				REGEXP_SUBSTR( s.meta_value, '\"login\";i:[0-9]+;}}' ),
				'[0-9]+'
			)
		AS UNSIGNED ) > $cutoff_time;"
	);

	echo "There were " . count( $users ) . " users found within the last " . RECENT_WEEKS . " weeks. \n";

	return $users;
}

/**
 * Update MailChimp email list with current users.
 */
function update_mailchimp() {
	$recent_users = get_recent_users();

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

get_recent_users();
//update_mailchimp();