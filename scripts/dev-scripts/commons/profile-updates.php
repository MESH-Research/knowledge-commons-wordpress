<?php
/**
 * Get history of profile updates and output CSV.
 * 
 * Usage: wp eval-file profile-updates.php
 */

Namespace MESHResearch\KCScripts;

function main() {
	$users = get_all_users();
	$updates = get_profile_updates();
	output_csv($users, $updates);
}

function get_all_users() {
	global $wpdb;

	$sql = "SELECT ID, user_login, user_registered FROM $wpdb->users WHERE spam = 0 AND deleted = 0";
	$users = $wpdb->get_results($sql);

	return $users;
}

function get_profile_updates() {
	global $wpdb;

	$sql = "SELECT user_id, date_recorded FROM {$wpdb->prefix}bp_activity WHERE type = 'updated_profile'";
	$updates = $wpdb->get_results($sql);

	return $updates;
}

function output_csv($users, $updates) {
	$csv = fopen('profile-updates.csv', 'w');
	fputcsv($csv, ['User ID', 'User Login', 'User Registered', 'Date Recorded']);
	foreach ($updates as $update) {
		$user = array_filter($users, function($user) use ($update) {
			return $user->ID == $update->user_id;
		});
		$user = array_shift($user);
		fputcsv($csv, [$user->ID, $user->user_login, $user->user_registered, $update->date_recorded]);
	}
}

main();