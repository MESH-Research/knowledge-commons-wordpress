<?php
/**
 * Custom changes to the Invite Anyone plugin.
 *
 * @package Hc_Custom
 */

/**
 * Whether the current user may use invite-anyone in a group.
 *
 * Mirrors the checks in BP_Invite_Anyone::enable_nav_item(), but is safe to
 * call once the current group has actually been resolved.
 *
 * @param int $group_id Group ID. Defaults to the current group.
 * @param int $user_id  User ID. Defaults to the logged-in user.
 * @return bool
 */
function hc_custom_user_can_use_invite_anyone( $group_id = 0, $user_id = 0 ) {
	if ( ! function_exists( 'bp_groups_user_can_send_invites' ) || ! function_exists( 'invite_anyone_group_invite_access_test' ) ) {
		return false;
	}

	if ( ! $group_id && function_exists( 'bp_get_current_group_id' ) ) {
		$group_id = bp_get_current_group_id();
	}

	if ( ! $group_id ) {
		return false;
	}

	if ( ! bp_groups_user_can_send_invites( $group_id, $user_id ) ) {
		return false;
	}

	return 'anyone' === invite_anyone_group_invite_access_test( $group_id, $user_id );
}

/**
 * Restore the invite-anyone "Send Invites" group tab on BuddyPress 12+.
 *
 * This addresses @link https://github.com/MESH-Research/knowledge-commons-wordpress/issues/101
 *
 * Invite Anyone computes its enable_nav_item() value in the constructor of
 * BP_Invite_Anyone, which BuddyPress instantiates at bp_init:11. Since
 * BuddyPress 12 the requested group is not resolved until bp_parse_query, so
 * bp_get_current_group_id() is still 0 at that point, the capability check
 * fails, and the extension registers itself with access and visibility set
 * to 'noone' — the Send Invites tab disappears from every existing group.
 *
 * By bp_actions the current group is known, so constructing a fresh
 * instance here lets the extension evaluate its own access rules correctly.
 * We register it at priority 7, just before BuddyPress registers group
 * extensions at priority 8; re-registering the same slug simply overwrites
 * the earlier, broken registration.
 *
 * @return bool True if the extension was re-registered.
 */
function hc_custom_restore_invite_anyone_group_nav() {
	if ( ! function_exists( 'bp_is_group' ) || ! bp_is_group() ) {
		return false;
	}

	if ( ! class_exists( 'BP_Invite_Anyone' ) ) {
		return false;
	}

	if ( ! hc_custom_user_can_use_invite_anyone() ) {
		return false;
	}

	$extension = new BP_Invite_Anyone();
	$extension->_register();

	return true;
}
add_action( 'bp_actions', 'hc_custom_restore_invite_anyone_group_nav', 7 );

/**
 * Invitations sent to an email address that have not been accepted yet.
 *
 * @param string $email Invited email address.
 * @return WP_Post[] Pending ia_invites posts, oldest first.
 */
function hc_custom_pending_email_invitations( $email ) {
	if ( '' === (string) $email || ! function_exists( 'invite_anyone_get_invitations_by_invited_email' ) ) {
		return array();
	}

	$query   = invite_anyone_get_invitations_by_invited_email( $email );
	$pending = array();

	foreach ( (array) ( $query->posts ?? array() ) as $invite ) {
		if ( empty( $invite->ID ) ) {
			continue;
		}

		if ( '' !== (string) get_post_meta( $invite->ID, 'bp_ia_accepted', true ) ) {
			continue;
		}

		$pending[] = $invite;
	}

	return $pending;
}

/**
 * Accept the email invitations addressed to a user and send the group invites.
 *
 * Replaces Invite Anyone's invite_anyone_activate_user(), which only runs on
 * BuddyPress account activation. Commons accounts are created by the login
 * broker, so that hook never fires here.
 *
 * @param int $user_id User whose email address was invited.
 * @return int[] IDs of the groups the user was invited to.
 */
function hc_custom_process_email_invitations( $user_id ) {
	$user_id = (int) $user_id;
	$pending = hc_custom_pending_email_invitations( bp_core_get_user_email( $user_id ) );

	if ( empty( $pending ) ) {
		return array();
	}

	$taxonomy = invite_anyone_get_invited_groups_tax_name();
	$inviters = array();
	$groups   = array(); // group_id => inviter_id; first invitation wins.

	foreach ( $pending as $invite ) {
		$inviter_id = (int) $invite->post_author;
		$inviters[] = $inviter_id;

		foreach ( (array) wp_get_post_terms( $invite->ID, $taxonomy ) as $term ) {
			$group_id = (int) $term->name;
			if ( $group_id && ! isset( $groups[ $group_id ] ) ) {
				$groups[ $group_id ] = $inviter_id;
			}
		}

		update_post_meta( $invite->ID, 'bp_ia_accepted', gmdate( 'Y-m-d H:i:s' ) );
	}

	foreach ( $groups as $group_id => $inviter_id ) {
		groups_invite_user(
			array(
				'user_id'     => $user_id,
				'group_id'    => $group_id,
				'inviter_id'  => $inviter_id,
				'send_invite' => 1,
			)
		);
	}

	/** This action is documented in invite-anyone/by-email/by-email.php */
	do_action( 'accepted_email_invite', $user_id, array_values( array_unique( $inviters ) ) );

	return array_keys( $groups );
}

/**
 * Process email invitations once per user per network.
 *
 * @return bool True if invitations were looked up on this request.
 */
function hc_custom_maybe_process_email_invitations() {
	if ( ! is_user_logged_in() || ! bp_is_root_blog() || ! bp_is_active( 'groups' ) ) {
		return false;
	}

	if ( ! function_exists( 'invite_anyone_get_invitations_by_invited_email' ) ) {
		return false;
	}

	$user_id  = get_current_user_id();
	$meta_key = 'hc_ia_invites_checked_' . (int) bp_get_root_blog_id();

	if ( '' !== (string) get_user_meta( $user_id, $meta_key, true ) ) {
		return false;
	}

	hc_custom_process_email_invitations( $user_id );
	update_user_meta( $user_id, $meta_key, gmdate( 'Y-m-d H:i:s' ) );

	return true;
}
add_action( 'bp_init', 'hc_custom_maybe_process_email_invitations', 20 );

/**
 * Interpret an Invite Anyone email link landing on the membership page.
 *
 * The plugin's accept and opt-out links carry ?iaaction=…&email=…. They used
 * to land on the BuddyPress register page; on the Commons they land on the
 * network's /membership/ page, where none of the plugin's handling runs.
 *
 * @param array $get Query arguments (normally $_GET).
 * @return array{action: ?string, email: ?string} Recognised action and a valid
 *                                                 email address, or nulls.
 */
function hc_custom_invite_anyone_request( $get ) {
	$none = array(
		'action' => null,
		'email'  => null,
	);

	if ( ! is_array( $get ) || empty( $get['iaaction'] ) || empty( $get['email'] ) ) {
		return $none;
	}

	$action = urldecode( (string) $get['iaaction'] );
	if ( ! in_array( $action, array( 'accept-invitation', 'opt-out' ), true ) ) {
		return $none;
	}

	// A "+" in the address reaches PHP as a space when the link was not encoded.
	$email = str_replace( ' ', '+', trim( urldecode( (string) $get['email'] ) ) );
	if ( ! is_email( $email ) ) {
		return $none;
	}

	return array(
		'action' => $action,
		'email'  => $email,
	);
}

/**
 * Record an opt-out submitted from the membership page.
 *
 * @param array $post Submitted form data (normally $_POST).
 * @return string|null 'done', 'bad_nonce', 'bad_email', or null when nothing was submitted.
 */
function hc_custom_handle_invite_opt_out( $post ) {
	if ( ! is_array( $post ) || empty( $post['hc_ia_opt_out'] ) ) {
		return null;
	}

	$nonce = isset( $post['_wpnonce'] ) ? (string) $post['_wpnonce'] : '';
	if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'invite_anyone_opt_out' ) ) {
		return 'bad_nonce';
	}

	$email = isset( $post['opt_out_email'] ) ? trim( wp_unslash( (string) $post['opt_out_email'] ) ) : '';
	$email = str_replace( ' ', '+', $email );
	if ( ! is_email( $email ) ) {
		return 'bad_email';
	}

	invite_anyone_mark_as_opt_out( $email );

	return 'done';
}

/**
 * Opt-out confirmation form, or the result of a submission.
 *
 * @param string      $email  Address from the email link.
 * @param string|null $status Result of hc_custom_handle_invite_opt_out().
 * @return string HTML.
 */
function hc_custom_invite_opt_out_markup( $email, $status = null ) {
	$site = get_bloginfo( 'name' );

	if ( 'done' === $status ) {
		return '<div class="hc-invite-opt-out bp-feedback success"><p>' . sprintf(
			/* translators: 1: email address, 2: site name */
			esc_html__( '%1$s will not receive any further invitations from %2$s.', 'hc-custom' ),
			'<strong>' . esc_html( $email ) . '</strong>',
			esc_html( $site )
		) . '</p></div>';
	}

	$html = '<div class="hc-invite-opt-out">';

	$html .= '<p>' . sprintf(
		/* translators: %s: site name */
		esc_html__( 'To opt out of future invitations to %s, confirm your email address below.', 'hc-custom' ),
		esc_html( $site )
	) . '</p>';

	if ( 'bad_nonce' === $status ) {
		$html .= '<p class="bp-feedback error">' . esc_html__( 'That request could not be verified. Please try again.', 'hc-custom' ) . '</p>';
	} elseif ( 'bad_email' === $status ) {
		$html .= '<p class="bp-feedback error">' . esc_html__( 'Please enter a valid email address.', 'hc-custom' ) . '</p>';
	}

	$html .= '<form method="post" class="standard-form">';
	$html .= '<label for="opt_out_email">' . esc_html__( 'Email address', 'hc-custom' ) . '</label>';
	$html .= '<input type="email" name="opt_out_email" id="opt_out_email" value="' . esc_attr( $email ) . '" required />';
	$html .= '<p class="submit"><input type="submit" name="hc_ia_opt_out" value="' . esc_attr__( 'Opt Out', 'hc-custom' ) . '" /></p>';
	$html .= wp_nonce_field( 'invite_anyone_opt_out', '_wpnonce', false, false );
	$html .= '</form></div>';

	return $html;
}

/**
 * Notice telling an invitee who invited them and how to accept.
 *
 * @param string $email   Invited address.
 * @param array  $invites Pending invitation posts for that address.
 * @return string HTML, empty when there is nothing to accept.
 */
function hc_custom_invite_accept_markup( $email, array $invites ) {
	if ( empty( $invites ) ) {
		return '';
	}

	$names = array();
	foreach ( $invites as $invite ) {
		$name = bp_core_get_user_displayname( (int) $invite->post_author );
		if ( '' !== (string) $name ) {
			$names[ $name ] = true;
		}
	}
	$names = array_keys( $names );

	$site = get_bloginfo( 'name' );

	$html  = '<div class="hc-invite-accept bp-feedback info"><p>';
	$html .= sprintf(
		/* translators: 1: inviter name(s), 2: site name */
		esc_html__( '%1$s invited you to join %2$s.', 'hc-custom' ),
		'<strong>' . esc_html( implode( ', ', $names ) ) . '</strong>',
		esc_html( $site )
	);
	$html .= ' ' . sprintf(
		/* translators: %s: email address */
		esc_html__( 'Register below using %s; once your account is set up you will receive invitations to the groups you were invited to.', 'hc-custom' ),
		'<strong>' . esc_html( $email ) . '</strong>'
	);
	$html .= '</p></div>';

	return $html;
}

/**
 * Membership page content with the invitation notice or opt-out form prepended.
 *
 * @param string      $content Page content.
 * @param array       $request Parsed request, see hc_custom_invite_anyone_request().
 * @param string|null $status  Opt-out submission status.
 * @return string
 */
function hc_custom_invite_anyone_membership_content( $content, array $request, $status = null ) {
	if ( empty( $request['action'] ) || empty( $request['email'] ) ) {
		return $content;
	}

	if ( 'opt-out' === $request['action'] ) {
		return hc_custom_invite_opt_out_markup( $request['email'], $status ) . $content;
	}

	$notice = hc_custom_invite_accept_markup(
		$request['email'],
		hc_custom_pending_email_invitations( $request['email'] )
	);

	return $notice . $content;
}

/**
 * Remember the opt-out status between template_redirect and the_content.
 *
 * @param string|null $status Status to store, or null to read.
 * @return string|null
 */
function hc_custom_invite_anyone_opt_out_status( $status = null ) {
	static $stored = null;

	if ( null !== $status ) {
		$stored = $status;
	}

	return $stored;
}

/**
 * Whether the current request is for a network's membership page.
 *
 * @return bool
 */
function hc_custom_is_membership_page() {
	return is_page_template( 'membership-template.php' ) || is_page( 'membership' );
}

/**
 * template_redirect: process an opt-out submission on the membership page.
 */
function hc_custom_invite_anyone_handle_opt_out_request() {
	if ( ! hc_custom_is_membership_page() || ! function_exists( 'invite_anyone_mark_as_opt_out' ) ) {
		return;
	}

	$request = hc_custom_invite_anyone_request( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( 'opt-out' !== $request['action'] ) {
		return;
	}

	$status = hc_custom_handle_invite_opt_out( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( null !== $status ) {
		hc_custom_invite_anyone_opt_out_status( $status );
	}
}
add_action( 'template_redirect', 'hc_custom_invite_anyone_handle_opt_out_request' );

/**
 * the_content: show the invitation notice or opt-out form on the membership page.
 *
 * @param string $content Page content.
 * @return string
 */
function hc_custom_invite_anyone_filter_content( $content ) {
	if ( ! hc_custom_is_membership_page() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	return hc_custom_invite_anyone_membership_content(
		$content,
		hc_custom_invite_anyone_request( $_GET ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		hc_custom_invite_anyone_opt_out_status()
	);
}
add_filter( 'the_content', 'hc_custom_invite_anyone_filter_content' );
