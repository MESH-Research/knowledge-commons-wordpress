<?php
/**
 * Integration with MailChimp. Adds new users to MailChimp list with the 'new-user' tag.
 * 
 * This script requires MAILCHIMP_LIST_ID, MAILCHIMP_API_KEY, and MAILCHIMP_DC to be defined in .env.
 * 
 * Note: See dev-scripts/mailchimp/update-mailchimp.php for reccurring update script.
 */

 /**
* Add user to MailChimp list on user registration.
*/
function hcommons_add_new_user_to_mailchimp( $user_id, $userdata ) {

	if ( ! defined( 'MAILCHIMP_LIST_ID' ) || ! defined( 'MAILCHIMP_API_KEY' ) || ! defined( 'MAILCHIMP_DC' ) ) {
		trigger_error( 'Mailchimp user creation failed: Mailchimp constants not defined.', E_USER_WARNING );
		return;
	}

	if ( ! $user_id ) {
		trigger_error( 'Mailchimp user creation failed: no user ID provided.', E_USER_WARNING );
		return;
	}

	$user = get_user_by( 'id', $user_id );
	if ( ! $user ) {
		trigger_error( 'Mailchimp user creation failed: no user found for ID ' . $user_id, E_USER_WARNING );
		return;
	}

	// Make sure user has member types set. This function normally triggers on bp_init, but we
	// can't count on that for newly-registered users.
	hcommons_set_user_member_types( $user );
	
	if ( ! isset( $userdata['user_email'] ) ) {
		trigger_error( 'Mailchimp user creation failed: no email address provided.', E_USER_WARNING );
		return;
	}

	$existing_mailchimp_response = hcommons_mailchimp_request(
		'/lists/' . MAILCHIMP_LIST_ID . '/members/' . $userdata['user_email']
	);

	$mailchimp_user_id = '';
	$request_method = 'POST';
	if ( is_array( $existing_mailchimp_response ) && isset( $existing_mailchimp_response['email_address'] ) ) {
		trigger_error( 'Mailchimp user exists for email ' . $userdata['user_email'], E_USER_NOTICE );
		if ( $existing_mailchimp_response['status'] === 'archived') {
			$mailchimp_user_id = $existing_mailchimp_response['id'];
			$request_method = 'PUT';
		} else {
			trigger_error( 'Mailchimp user exists and is not archived for email ' . $userdata['user_email'], E_USER_NOTICE );
			return;
		}
	}

	$member_types = bp_get_member_type( $user_id, false );
	if ( ! is_array( $member_types ) || empty( $member_types ) ) {
		$member_types = [ "hc" ];
	}
	$tags = array_merge( $member_types, [ 'new-user' ] );

	$mailchimp_response = hcommons_mailchimp_request(
		'/lists/' . MAILCHIMP_LIST_ID . '/members/' . $mailchimp_user_id,
		$request_method,
		[
			'email_address' => $userdata['user_email'],
			'status'        => 'subscribed',
			'merge_fields'  => [
				'FNAME'    => $userdata['first_name'],
				'LNAME'    => $userdata['last_name'],
				'DNAME'    => $user->display_name,
				'USERNAME' => $userdata['user_login'],
			],
			'tags'          => $tags,
			'interests'     => [
				MAILCHIMP_NEWSLETTER_GROUP_ID => true, // Newsletter
			],
		]
	);

	if ( is_array( $mailchimp_response ) && isset( $mailchimp_response['id'] ) ) {
		trigger_error( 'Mailchimp user created for email ' . $userdata['user_email'] . ' with status ' . $mailchimp_response['status'], E_USER_NOTICE );
	} else {
		trigger_error( 'Mailchimp user creation failed. Response:' . var_export( $mailchimp_response, true ), E_USER_WARNING );
	}
}
add_action( 'user_register', 'hcommons_add_new_user_to_mailchimp', 10, 2 );

/**
 * Remove user from MailChimp list on user deletion.
 */
function hcommons_remove_user_from_mailchimp( $user_id ) {
	if ( ! defined( 'MAILCHIMP_LIST_ID' ) ) {
		trigger_error( 'Mailchimp user removal failed: Mailchimp constants not defined.', E_USER_WARNING );
		return;
	}
	
	$user = get_user_by( 'id', $user_id );
	
	if ( ! $user ) {
		trigger_error( 'Mailchimp user deletion failed: no user found for ID ' . $user_id, E_USER_WARNING );
		return;
	}
	
	trigger_error( 'Removing user ' . $user->user_login . ' from Mailchimp.', E_USER_NOTICE );

	$existing_mailchimp_response = hcommons_mailchimp_request(
		'/lists/' . MAILCHIMP_LIST_ID . '/members/' . $user->user_email
	);

	if ( is_array( $existing_mailchimp_response ) && isset( $existing_mailchimp_response['email_address'] ) ) {
		$mailchimp_user_id = $existing_mailchimp_response['id'];
		$mailchimp_response = hcommons_mailchimp_request(
			'/lists/' . MAILCHIMP_LIST_ID . '/members/' . $mailchimp_user_id,
			'DELETE',
			[]
		);

		if ( $mailchimp_response !== false ) {
			trigger_error( 'Mailchimp user deleted for email ' . $user->user_email, E_USER_NOTICE );
		} else {
			trigger_error( 'Mailchimp user deletion failed. Response:' . var_export( $mailchimp_response, true ), E_USER_WARNING );
		}
	} else {
		trigger_error( 'Mailchimp deletion failed: user does not exist for email ' . $user->user_email, E_USER_NOTICE );
	}
}
add_action( 'delete_user', 'hcommons_remove_user_from_mailchimp', 10, 1 );
add_action( 'wpmu_delete_user', 'hcommons_remove_user_from_mailchimp', 10, 1 );

/**
 * Make a request to the MailChimp API and return the response body.
 *
 * @param string $endpoint The API endpoint to request. Eg. '/lists/12345/members'
 * @param string $method   The HTTP method to use. Eg. 'GET', 'POST', 'PATCH', 'DELETE'
 * @param array  $params   The request parameters. Eg. [ 'email_address' => ' ... ' ]
 */
function hcommons_mailchimp_request( $endpoint, $method='GET', $params=[] ) {
	if ( ! defined( 'MAILCHIMP_API_KEY' ) || ! defined( 'MAILCHIMP_DC' ) ) {
		trigger_error( 'Mailchimp request failed: Mailchimp constants not defined.', E_USER_WARNING );
		return;
	}
	
	$api_base = "https://" . MAILCHIMP_DC . ".api.mailchimp.com/3.0";
	$url = $api_base . $endpoint;
	if ( $method === 'GET' ) {
		$url .= '?' . http_build_query( $params );
		$body = '';
	} else {
		$body = json_encode( $params );
	}

	$auth_string = 'Basic ' . base64_encode( 'HumanitiesCommons:' . MAILCHIMP_API_KEY );

	try {
		$response = wp_remote_request( 
			$url,
			[
				'url'    => $url,
				'method' => $method,
				'headers' => [
					'Authorization' => $auth_string,
					'Content-Type'  => 'application/json',
				],
				'body'   => $body,
			]
		);
	} catch ( Exception $e ) {
		trigger_error( 'MailChimp request error: ' . $e->getMessage(), E_USER_WARNING );
		return false;
	}

	if ( is_wp_error( $response ) ) {
		trigger_error( 'MailChimp request error: ' . $response->get_error_message(), E_USER_WARNING );
		return false;
	}

	$response_body = json_decode( $response['body'], true );
	return $response_body;
}