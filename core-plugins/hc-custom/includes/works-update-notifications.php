<?php
/**
 * Functions for calling the Works update notifications API when user or group data is updated.
 * 
 * @see https://github.com/MESH-Research/commons-docs/blob/main/documentation/developer/documentation/kcworks-user%2Bgroup-apis.md
 */

namespace KCommons\HCCustom;

/**
 * Check if the Works environment is configured.
 *
 * @return bool True if the environment is configured, false otherwise.
 */
function works_env_is_configured() : bool {
	if ( ! defined( 'WORKS_URL' ) ) {
		trigger_error( 'WORKS_URL is not defined.', E_USER_WARNING );
		return false;
	}

	if ( ! defined( 'WORKS_API_KEY' ) ) {
		trigger_error( 'WORKS_API_KEY is not defined.', E_USER_WARNING );
		return false;
	}

	if ( ! defined( 'WORKS_KNOWLEDGE_COMMONS_INSTANCE' ) ) {
		trigger_error( 'WORKS_KNOWLEDGE_COMMONS_INSTANCE is not defined.', E_USER_WARNING );
		return false;
	}

	if ( ! WORKS_URL || ! WORKS_API_KEY || ! WORKS_KNOWLEDGE_COMMONS_INSTANCE ) {
		trigger_error( 'One or more Works environment variables are empty.', E_USER_WARNING );
		return false;
	}

	return true;
}

/**
 * Check if the Works update notification endpoint is available.
 *
 * @return bool True if the endpoint is available, false otherwise.
 */
function works_endpoint_is_available() : bool {
	if ( ! works_env_is_configured() ) {
		return false;
	}

	$url = WORKS_URL . '/api/webhooks/user_data_update';
	$args = [
		'headers' => [
			'Authorization' => 'Bearer ' . WORKS_API_KEY,
		],
	];

	$response = wp_remote_get( $url, $args );

	if ( is_wp_error( $response ) ) {
		trigger_error( 'Works update notification endpoint is not available: ' . $response->get_error_message(), E_USER_WARNING );
		return false;
	}

	$response_code = wp_remote_retrieve_response_code( $response );

	if ( $response_code !== 200 ) {
		trigger_error( 'Works update notification endpoint is not available: ' . $response_code, E_USER_WARNING );
		return false;
	}

	return true;
}

/**
 * Send a Works update notification.
 *
 * @param array $message The notification message.
 * @return bool True if the notification was sent, false otherwise.
 */
function send_works_update_notification( array $message ) : bool {
	if ( ! works_env_is_configured() ) {
		return false;
	}

	$url = WORKS_URL . '/api/webhooks/user_data_update';
	$args = [
		'headers' => [
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer ' . WORKS_API_KEY,
		],
		'body' => json_encode( $message ),
	];

	$response = wp_remote_post( $url, $args );

	if ( is_wp_error( $response ) ) {
		trigger_error( 'Works update notification failed: ' . $response->get_error_message(), E_USER_WARNING );
		return false;
	}

	$response_code = wp_remote_retrieve_response_code( $response );

	if ( $response_code !== 202 ) {
		trigger_error( 'Works update notification failed: ' . $response_code, E_USER_WARNING );
		return false;
	}

	return true;
}

/**
 * Send a Works update notification for a user.
 *
 * @param int $user_id The user ID.
 * @return bool True if the notification was sent, false otherwise.
 */
function send_works_update_notification_for_user( int $user_id ) : bool {
	$user = get_user_by( 'id', $user_id );

	if ( ! $user ) {
		trigger_error( 'User ' . $user_login . ' not found.', E_USER_WARNING );
		return false;
	}

	$message = [
		"idp" => WORKS_KNOWLEDGE_COMMONS_INSTANCE,
		"updates" => [
			"users" => [
				[
					"id" => $user->user_login,
					"event" => "updated",
				]
			]
		]
	];

	if ( ! send_works_update_notification( $message ) ) {
		trigger_error( 'Works update notification failed for user ' . $user->user_login, E_USER_WARNING );
		return false;
	} else {
		trigger_error( 'Works update notification sent for user ' . $user->user_login, E_USER_NOTICE );
		return true;
	}
}
add_action( 'profile_update', 'KCommons\HCCustom\send_works_update_notification_for_user', 10, 2 );

/**
 * Send a Works update notification for a group.
 *
 * @param int $group_id The group ID.
 * @return bool True if the notification was sent, false otherwise.
 */
function send_works_update_notification_for_group( $group_id ) : bool {
	$group = groups_get_group( $group_id );

	if ( ! $group ) {
		trigger_error( 'Group ' . $group_id . ' not found.', E_USER_WARNING );
		return false;
	}

	$message = [
		"idp" => WORKS_KNOWLEDGE_COMMONS_INSTANCE,
		"updates" => [
			"groups" => [
				[
					"id" => $group->group_id,
					"event" => "updated",
				]
			]
		]
	];

	if ( ! send_works_update_notification( $message ) ) {
		trigger_error( 'Works update notification failed for group ' . $group->group_id, E_USER_WARNING );
		return false;
	} else {
		trigger_error( 'Works update notification sent for group ' . $group->group_id, E_USER_NOTICE );
		return true;
	}
}
add_action( 'groups_group_details_edited', 'KCommons\HCCustom\send_works_update_notification_for_group', 10, 2 );

/**
 * If user_id is set, send a Works update notification for the user.
 * Otherwise, get all the members of a group and send a Works update notification for each member.
 *
 * @param int $group_id The group ID.
 * @param int $user_id The user ID.
 * @return bool True if the notification was sent, false otherwise.
 */
function send_works_update_notification_for_group_members( int $group_id, int $user_id = 0 ) : bool {
	if ( $user_id ) {
		if ( send_works_update_notification_for_user( $user_id ) ) {
			trigger_error( 'Works membership update notification sent for user ' . $user_id, E_USER_NOTICE );
			return true;
		} else {
			trigger_error( 'Works membership update notification failed for user ' . $user_id, E_USER_WARNING );
			return false;
		}
	}

	// No user_id was provided, so get all the members of the group.

	$group = groups_get_group( $group_id );

	if ( ! $group ) {
		trigger_error( 'Group ' . $group_id . ' not found.', E_USER_WARNING );
		return false;
	}

	$members = groups_get_group_members( 
		[ 
			'group_id' => $group_id,
			'exclude_admins_mods' => false, 
			'exclude_banned' => false,
		] 
	);

	$updates = [];

	if ( ! array_key_exists( 'members', $members ) ) {
		trigger_error( 'Group ' . $group->group_id . ' has no members.', E_USER_WARNING );
		return false;
	}

	foreach ( $members['members'] as $member ) {
		$updates[] = [
			"id" => $member->user_login,
			"event" => "updated",
		];
	}

	$message = [
		"idp" => WORKS_KNOWLEDGE_COMMONS_INSTANCE,
		"updates" => [
			"users" => $updates,
		]
	];

	if ( ! send_works_update_notification( $message ) ) {
		trigger_error( 'Works membership update notification failed for group ' . $group->group_id, E_USER_WARNING );
		return false;
	} else {
		trigger_error( 'Works membership update notification sent for group ' . $group->group_id, E_USER_NOTICE );
		return true;
	}
}
add_action( 'groups_join_group', 'KCommons\HCCustom\send_works_update_notification_for_group_members', 10, 2 );
add_action( 'groups_leave_group', 'KCommons\HCCustom\send_works_update_notification_for_group_members', 10, 2 );
add_action( 'groups_member_invited', 'KCommons\HCCustom\send_works_update_notification_for_group_members', 10, 2 );
add_action( 'groups_invite_accepted', 'KCommons\HCCustom\send_works_update_notification_for_group_members', 10, 2 );
add_action( 'groups_invite_rejected', 'KCommons\HCCustom\send_works_update_notification_for_group_members', 10, 2 );
add_action( 'groups_promote_member', 'KCommons\HCCustom\send_works_update_notification_for_group_members', 10, 2 );
add_action( 'groups_demote_member', 'KCommons\HCCustom\send_works_update_notification_for_group_members', 10, 2 );
add_action( 'groups_ban_member', 'KCommons\HCCustom\send_works_update_notification_for_group_members', 10, 2 );
add_action( 'groups_unban_member', 'KCommons\HCCustom\send_works_update_notification_for_group_members', 10, 2 );

/**
 * WP-CLI commands for Works update notifications.
 */
class KC_Works_Update_Notification_Command {
	/**
	 * Check if the Works environment is configured.
	 *
	 * @param array $args The command arguments.
	 * @param array $assoc_args The command associative arguments.
	 */
	public function is_configured( $args, $assoc_args ) {
		if ( works_env_is_configured() ) {
			\WP_CLI::success( 'Works update notification is configured.' );
		} else {
			\WP_CLI::error( 'Works update notification is not configured.' );
		}
	}

	/**
	 * Check if the Works update notification endpoint is available.
	 *
	 * @param array $args The command arguments.
	 * @param array $assoc_args The command associative arguments.
	 */
	public function is_available( $args, $assoc_args ) {
		if ( works_endpoint_is_available() ) {
			\WP_CLI::success( 'Works update notification endpoint is available.' );
		} else {
			\WP_CLI::error( 'Works update notification endpoint is not available.' );
		}
	}

	/**
	 * Send a Works update notification for a user.
	 *
	 * @param array $args The command arguments.
	 * 	$args[0] is the username.
	 * @param array $assoc_args The command associative arguments.
	 */
	public function user( $args, $assoc_args ) {
		$user = get_user_by( 'login', $args[0] );	
		if ( ! $user ) {
			\WP_CLI::error( 'User ' . $args[0] . ' not found.' );
			return;
		}

		if ( send_works_update_notification_for_user( $user->ID ) ) {
			\WP_CLI::success( 'Works update notification sent for user ' . $args[0] );
		} else {
			\WP_CLI::error( 'Works update notification failed for user ' . $args[0] );
		}
	}

	/**
	 * Send a Works update notification for a group.
	 *
	 * @param array $args The command arguments.
	 * 	$args[0] is the group ID.
	 * @param array $assoc_args The command associative arguments.
	 */
	public function group( $args, $assoc_args ) {
		if ( send_works_update_notification_for_group( $args[0] ) ) {
			\WP_CLI::success( 'Works update notification sent for group ' . $args[0] );
		} else {
			\WP_CLI::error( 'Works update notification failed for group ' . $args[0] );
		}
	}

	/**
	 * Send a Works update notification for a group's members.
	 *
	 * @param array $args The command arguments.
	 * 	$args[0] is the group ID.
	 * @param array $assoc_args The command associative arguments.
	 */
	public function group_members( $args, $assoc_args ) {
		if ( send_works_update_notification_for_group_members( $args[0] ) ) {
			\WP_CLI::success( 'Works update notification sent for group ' . $args[0] );
		} else {
			\WP_CLI::error( 'Works update notification failed for group ' . $args[0] );
		}
	}
}

if ( class_exists('\WP_CLI') ) {
	\WP_CLI::add_command('kc works update-notification', 'KCommons\HCCustom\KC_Works_Update_Notification_Command');
}