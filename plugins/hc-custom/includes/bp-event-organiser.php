<?php
/**
 * Customizations to bp-event-organiser plugin.
 *
 * @package Hc_Custom
 */

/**
 * Callback filter to use BPEO's content for the canonical event page.
 *
 * @param  string $content Current content.
 */
function hc_custom_filter_bp_event_content( $content ) {
	global $page;

	$post_type = get_post_type( get_the_ID() );

	if ( 'event' === $post_type ) {
		$page = 1;
	}

	return $content;
}

add_filter( 'the_content', 'hc_custom_filter_bp_event_content' );

/**
 * Remove buggy function from bp-events-organiser
 */
function hc_custom_remove_bpeo_filter_query_for_bp_group() {
		remove_action( 'pre_get_posts', 'bpeo_filter_query_for_bp_group' );
}
add_action( 'pre_get_posts', 'hc_custom_bpeo_filter_query_for_bp_group' );

/**
 * Modify `WP_Query` requests for the 'bp_group' param.
 *
 * @param object $query Query object, passed by reference.
 */
function hc_custom_bpeo_filter_query_for_bp_group( $query ) {
		// Only modify 'event' queries.
		$post_types = $query->get( 'post_type' );

	if ( ! in_array( 'event', (array) $post_types ) ) {
			return;
	}

	$bp_group = $query->get( 'bp_group', null );

	if ( null === $bp_group ) {
			return;
	}
	if ( ! is_array( $bp_group ) ) {
			$group_ids = array( $bp_group );
	} else {
			$group_ids = $bp_group;
	}
		// Empty array will always return no results.
	if ( empty( $group_ids ) ) {
			$query->set( 'post__in', array( 0 ) );
			return;
	}
		// Make sure private events are displayed.
		$query->set( 'post_status', array( 'publish', 'private' ) );
		// Convert group IDs to a tax query.
		$tq          = array();
		$tq[]        = $query->get( 'tax_query' );
		$group_terms = array();
	foreach ( $group_ids as $group_id ) {
			$group_terms[] = 'group_' . $group_id;
	}
		$tq[] = array(
			'taxonomy' => 'bpeo_event_group',
			'terms'    => $group_terms,
			'field'    => 'name',
			'operator' => 'IN',
		);

		$query->set( 'tax_query', $tq );
}

add_action( 'pre_get_posts', 'hc_custom_remove_bpeo_filter_query_for_bp_group' );

/**
 * Whether a user may connect (create) events for a group.
 *
 * Decided by the group's "minimum member role" setting (groupmeta
 * 'bpeo_connect_member_role': 'member' [default] or 'admin_mod') and the
 * user's role within the group.
 *
 * This deliberately does not rely on WP blog capabilities. The capability
 * chain used to map 'connect_event_to_group' to the 'read' primitive, but on
 * this multisite users frequently hold no role on the society site they are
 * visiting, so 'read' is missing and the check failed for everyone — even
 * group admins — regardless of the group setting.
 *
 * @param int $user_id  ID of the user.
 * @param int $group_id ID of the group.
 * @return bool
 */
function hc_custom_bpeo_user_can_connect_event_to_group( $user_id, $group_id ) {
	$user_id  = (int) $user_id;
	$group_id = (int) $group_id;

	if ( ! $user_id || ! $group_id ) {
		return false;
	}

	if ( is_super_admin( $user_id ) ) {
		return true;
	}

	if ( groups_is_user_banned( $user_id, $group_id ) ) {
		return false;
	}

	if ( function_exists( 'bpeo_get_group_minimum_member_role_for_connection' ) ) {
		$setting = bpeo_get_group_minimum_member_role_for_connection( $group_id );
	} else {
		$setting = groups_get_groupmeta( $group_id, 'bpeo_connect_member_role', true );
	}

	$is_admin_or_mod = groups_is_user_admin( $user_id, $group_id ) || groups_is_user_mod( $user_id, $group_id );

	if ( 'admin_mod' === $setting ) {
		$can_connect = $is_admin_or_mod;
	} else {
		// Default 'member' setting: any (non-banned) group member.
		$can_connect = $is_admin_or_mod || (bool) groups_is_user_member( $user_id, $group_id );
	}

	// Preserve the Humanities Commons new-member vetting, which previously
	// applied to event connection through the 'read' primitive capability.
	if ( $can_connect
		&& $user_id === (int) get_current_user_id()
		&& is_callable( array( 'Humanities_Commons', 'hcommons_vet_user' ) )
		&& ! Humanities_Commons::hcommons_vet_user() ) {
		$can_connect = false;
	}

	return $can_connect;
}

/**
 * Modify EO capabilities for group membership. Add capabilities for private events.
 *
 * @param array  $caps    Capability array.
 * @param string $cap     Capability to check.
 * @param int    $user_id ID of the user being checked.
 * @param array  $args    Miscellaneous args.
 * @return array Caps whitelist.
 */
function hc_custom_bpeo_group_event_meta_cap( $caps, $cap, $user_id, $args ) {
	// @todo Need real caching in BP for group memberships.
	if ( false === strpos( $cap, '_event' ) ) {
		return $caps;
	}

	// Some caps do not expect a specific event to be passed to the filter.
	$event        = null;
	$event_groups = array();
	$user_groups  = array( 'groups' => array() );

	$primitive_caps = array( 'read_events', 'read_private_events', 'edit_events', 'edit_others_events', 'publish_events', 'delete_events', 'delete_others_events', 'manage_event_categories', 'connect_event_to_group' );
	if ( ! in_array( $cap, $primitive_caps ) ) {
		$event = get_post( $args[0] );
		if ( 'event' !== $event->post_type ) {
			return $caps;
		}

		$event_groups = bpeo_get_event_groups( $event->ID );
		if ( empty( $event_groups ) ) {
			return $caps;
		}

		$user_groups = groups_get_user_groups( $user_id );
	}

	switch ( $cap ) {
		// 'read_private_events' is deliberately not handled here: it is a
		// primitive cap with no event context, so group membership cannot be
		// evaluated for it. Mapping it to 'exist' (as this once did) handed
		// every visitor the ability to list all private events.
		case 'read_event':
			// we've already parsed this logic in bpeo_map_basic_meta_caps().
			if ( 'exist' === $caps[0] ) {
				return $caps;
			}

			if ( 'private' !== ( is_object( $event ) ? $event->post_status : null ) ) {
				// EO uses 'read', which doesn't include non-logged-in users.
				$caps = array( 'exist' );

			} elseif ( array_intersect( $user_groups['groups'], $event_groups ) ) {
				$caps = array( 'read' );
			}

			break;

		case 'edit_event':
		case 'delete_event':
			// Group admins can edit and delete any event connected to their
			// group; group mods can edit.
			foreach ( $event_groups as $event_group_id ) {
				if ( groups_is_user_admin( $user_id, $event_group_id ) ) {
					$caps = array( 'exist' );
					break;
				}

				if ( 'edit_event' === $cap && groups_is_user_mod( $user_id, $event_group_id ) ) {
					$caps = array( 'exist' );
					break;
				}
			}

			break;

		case 'publish_events':
			// Publishing from a group's New Event screen: when the group's
			// connection setting allows this user to create events there, do
			// not additionally require blog-role primitives such as 'read',
			// which users without a role on the society site do not have.
			$current_group_id = function_exists( 'bp_get_current_group_id' ) ? bp_get_current_group_id() : 0;
			if ( $current_group_id && hc_custom_bpeo_user_can_connect_event_to_group( $user_id, $current_group_id ) ) {
				$caps = array( 'exist' );
			}

			break;

		case 'connect_event_to_group':
			if ( hc_custom_bpeo_user_can_connect_event_to_group( $user_id, $args[0] ) ) {
				// 'exist' rather than 'read': users are not guaranteed a WP
				// role (and thus 'read') on the society site, and the group
				// setting is what governs this permission.
				$caps = array( 'exist' );
			}

			break;
	}

	return $caps;
}
// Priority 30 so this runs after bp-event-organiser's own filter (priority 20)
// and its 'read'-based mapping cannot override the result.
add_filter( 'map_meta_cap', 'hc_custom_bpeo_group_event_meta_cap', 30, 4 );

/**
 * Register the events subnav (Calendar / Upcoming / Manage / New Event) for the
 * current group.
 *
 * BP Event Organiser registers this subnav from its group extension
 * constructor, which runs on 'bp_init'. Since BuddyPress 12 the request is
 * parsed on 'bp_parse_query', which runs later, so bp_is_group() is false at
 * construction time and the subnav is never registered — leaving group members
 * without the "New Event" button. This re-registers the subnav once the group
 * context is available.
 */
function hc_custom_bpeo_register_group_events_subnav() {
	if ( ! function_exists( 'bpeo_get_group_permalink' ) || ! bp_is_group() ) {
		return;
	}

	$group = groups_get_current_group();
	if ( empty( $group->slug ) ) {
		return;
	}

	$user_id        = bp_loggedin_user_id();
	$is_group_admin = (bool) groups_is_user_admin( $user_id, $group->id );

	// Respect the group's "show or hide menu items" setting for the Events
	// tab (see hc_custom_remove_group_manager_subnav_tabs()): when the tab is
	// hidden, do not register its subnav either. Site admins and group admins
	// always see all tabs, matching the removal logic.
	if ( ! is_super_admin() && ! $is_group_admin
		&& 'hide' === groups_get_groupmeta( $group->id, bpeo_get_events_slug() ) ) {
		return;
	}

	$parent_slug = $group->slug . '_events';

	// Bail if the subnav has already been registered (e.g. fixed upstream).
	$existing = buddypress()->groups->nav->get_secondary( array( 'parent_slug' => $parent_slug ), false );
	if ( ! empty( $existing ) ) {
		return;
	}

	// Routing is handled by the Events group extension screen, so the subnav
	// items are links only and never dispatch their own screen function.
	$default_params = array(
		'parent_url'        => bpeo_get_group_permalink(),
		'parent_slug'       => $parent_slug,
		'screen_function'   => '__return_false',
		'show_in_admin_bar' => true,
	);

	$sub_nav = array();

	$sub_nav[] = array_merge(
		array(
			'name'            => __( 'Calendar', 'bp-event-organiser' ),
			'slug'            => 'calendar',
			'user_has_access' => ! empty( $group->is_user_member ),
			'position'        => 0,
			'link'            => bpeo_get_group_permalink(),
		),
		$default_params
	);

	$sub_nav[] = array_merge(
		array(
			'name'            => __( 'Upcoming', 'bp-event-organiser' ),
			'slug'            => 'upcoming',
			'user_has_access' => ! empty( $group->is_user_member ),
			'position'        => 0,
			'link'            => bpeo_get_group_permalink() . 'upcoming/',
		),
		$default_params
	);

	$sub_nav[] = array_merge(
		array(
			'name'            => __( 'Manage', 'bp-event-organiser' ),
			'slug'            => 'manage',
			'user_has_access' => $is_group_admin,
			'position'        => 0,
			'link'            => trailingslashit( bp_get_group_permalink( $group ) . 'admin/' . bpeo_get_events_slug() ),
		),
		$default_params
	);

	// Gate the New Event button on the group's connection setting directly
	// rather than on current_user_can(), whose 'read' primitive mapping fails
	// for users without a WP role on the society site.
	if ( hc_custom_bpeo_user_can_connect_event_to_group( $user_id, $group->id ) ) {
		$sub_nav[] = array_merge(
			array(
				'name'            => __( 'New Event', 'bp-event-organiser' ),
				'slug'            => bpeo_get_events_new_slug(),
				'user_has_access' => ! empty( $group->is_user_member ),
				'position'        => 99,
			),
			$default_params
		);
	}

	foreach ( $sub_nav as $nav ) {
		bp_core_new_subnav_item( $nav, 'groups' );
	}
}
add_action( 'bp_actions', 'hc_custom_bpeo_register_group_events_subnav', 7 );

/**
 * Create activity on event save.
 *
 * The 'save_post' hook fires both on insert and update, so we use this function as a router.
 *
 * Run late to ensure that group connections have been set.
 *
 * @param int $event_id ID of the event.
 */
function hc_custom_bpeo_create_activity_for_event( $event_id, $event = null, $update = null ) {
	remove_action( 'save_post', 'bpeo_create_activity_for_event' );

	if ( is_null( $event ) ) {
		$event = get_post( $event_id );
	}

	// Skip auto-drafts and other post types.
	if ( 'event' !== $event->post_type ) {
		return;
	}

	// Skip post statuses other than 'publish' and 'private' (the latter is for non-public groups).
	if ( ! in_array( $event->post_status, array( 'publish', 'private' ), true ) ) {
		return;
	}

	// Hack: distinguish 'create' from 'edit' by comparing post_date and post_modified.
	if ( 'before_delete_post' === current_action() ) {
		$type = 'bpeo_delete_event';
	} elseif ( $event->post_date === $event->post_modified ) {
		$type = 'bpeo_create_event';
	} else {
		$type = 'bpeo_edit_event';
	}

	$content = '';
	if ( 'bpeo_create_event' === $type ) {
		$content_parts = array();

		$content_parts['title'] = sprintf( __( 'Title: %s', 'bp-event-organiser' ), $event->post_title );

		$content_parts['description'] = sprintf( __( 'Description: %s', 'bp-event-organiser' ), $event->post_content );

		$date = eo_get_next_occurrence( eo_get_event_datetime_format( $event_id ), $event_id );
		if ( $date ) {
			$dateTime = new DateTime();
			$dateTime->setTimeZone( new DateTimeZone( eo_get_blog_timezone()->getName() ) );

			$event_timezone = $dateTime->format('T');
			$content_parts['date'] = sprintf( __( 'Date: %s %s', 'bp-event-organiser' ), esc_html( $date ), esc_html( $event_timezone ) );
		}

		$venue_id = eo_get_venue( $event_id );
		if ( $venue_id ) {
			$venue = eo_get_venue_name( $venue_id );
			$content_parts['location'] = sprintf( __( 'Location: %s', 'bp-event-organiser' ), esc_html( $venue ) );
		}

		$content_parts[] = "\r";

		$content = implode( "\n\r", $content_parts );
	}

	// Existing activity items for this event.
	$activities = bpeo_get_activity_by_event_id( $event_id );

	// There should never be more than one top-level create item.
	if ( 'bpeo_create_event' === $type ) {
		$create_items = array();
		foreach ( $activities as $activity ) {
			if ( 'bpeo_create_event' === $activity->type && 'events' === $activity->component ) {
				return;
			}
		}
	}

	// Prevent edit floods.
	if ( 'bpeo_edit_event' === $type ) {

		if ( $activities ) {

			// Just in case.
			$activities = bp_sort_by_key( $activities, 'date_recorded' );
			$last_activity = end( $activities );

			/**
			 * Filters the number of seconds in the event edit throttle.
			 *
			 * This prevents activity stream flooding by multiple edits of the same event.
			 *
			 * @param int $throttle_period Defaults to 6 hours.
			 */
			$throttle_period = apply_filters( 'bpeo_event_edit_throttle_period', 6 * HOUR_IN_SECONDS );
			if ( ( time() - strtotime( $last_activity->date_recorded ) ) < $throttle_period ) {
				return;
			}
		}
	}

	switch ( $type ) {
		case 'bpeo_create_event' :
			$recorded_time = $event->post_date_gmt;
			break;
		case 'bpeo_edit_event' :
			$recorded_time = $event->post_modified_gmt;
			break;
		default :
			$recorded_time = bp_core_current_time();
			break;
	}

	$hide_sitewide = 'publish' !== $event->post_status;

	$activity_args = array(
		'component' => 'events',
		'type' => $type,
		'content' => $content,
		'user_id' => $event->post_author, // @todo Event edited by non-author?
		'primary_link' => get_permalink( $event ),
		'secondary_item_id' => $event_id, // Leave 'item_id' blank for groups.
		'recorded_time' => $recorded_time,
		'hide_sitewide' => $hide_sitewide,
	);

	bp_activity_add( $activity_args );

	do_action( 'bpeo_create_event_activity', $activity_args, $event );
}
add_action( 'save_post', 'hc_custom_bpeo_create_activity_for_event', 20, 3 );

/**
 * Conditionally sets up the PHPMailer callback for adding the .ics attachment to BPGES emails.
 */
function hc_custom_bpeo_maybe_hook_ics_attachments( $args, $email_type ) {
	if ( 'bp-ges-single' !== $email_type ) {
		return $args;
	}

	if ( empty( $args['activity'] ) ) {
		return $args;
	}

	if ( 'bpeo_create_event' !== $args['activity']->type && 'bpeo_edit_event' !== $args['activity']->type ) {
		return $args;
	}

	$ical_link = bpeo_get_the_ical_link( $args['activity']->secondary_item_id );

	$request = wp_remote_get(
		$ical_link,
		array(
			'cookies' => $_COOKIE,
		)
	);

	if ( 200 !== wp_remote_retrieve_response_code( $request ) ) {
		return $args;
	}

	$GLOBALS['bpeo_event_ical'] = wp_remote_retrieve_body( $request );

	add_action( 'phpmailer_init', 'hc_custom_bpeo_attach_ical_to_bpges_notification' );

	return $args;
}
add_action( 'ass_send_email_args', 'hc_custom_bpeo_maybe_hook_ics_attachments', 10, 2 );

/**
 * Sets up ical attachment to outgoing emails.
 *
 * @param PHPMailer $phpmailer
 */
function hc_custom_bpeo_attach_ical_to_bpges_notification( $phpmailer ) {
	global $bpeo_event_ical;

	if ( empty( $bpeo_event_ical ) ) {
		return;
	}

	$date = date('m-d-Y', time());

	$ics_file_name = 'event-organiser_'.$date.'.ics';

	$phpmailer->addStringAttachment( $bpeo_event_ical, $ics_file_name );
}

/**
 * Format activity items related to groups.
 *
 * @param string $action
 * @param object $activity
 * @return string
 */
function hc_custom_bpeo_activity_action_format_for_groups( $action, $activity ) {
    $modified_action = rtrim($action, '.');

	return $modified_action;
}
add_filter( 'bpeo_activity_action', 'hc_custom_bpeo_activity_action_format_for_groups', 999, 2 );




/** Group calendar scoping *****************************************************/

/**
 * Resolve the group a calendar request is being built for.
 *
 * @param array|null $request Request vars; defaults to $_GET.
 * @return int Group ID, or 0 when the request is not for a group.
 */
function hc_custom_bpeo_get_calendar_group_id( $request = null ) {
	// On the group page itself BuddyPress knows the group.
	if ( function_exists( 'bp_is_group' ) && bp_is_group() ) {
		return (int) bp_get_current_group_id();
	}

	// Inside the eventorganiser-fullcal admin-ajax request BuddyPress has no
	// group context (it skips URI parsing for AJAX), so the calendar passes
	// the group along explicitly.
	if ( null === $request ) {
		$request = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	if ( ! isset( $request['bp_group'] ) || ! is_scalar( $request['bp_group'] ) ) {
		return 0;
	}

	$bp_group = (string) $request['bp_group'];
	if ( '' === $bp_group || ! ctype_digit( $bp_group ) ) {
		return 0;
	}

	return (int) $bp_group;
}

/**
 * Restrict the Event Organiser calendar query to the current group's events.
 *
 * @param array $query Query vars as set up by EO.
 * @return array
 */
function hc_custom_bpeo_filter_calendar_query_for_group( $query ) {
	$group_id = hc_custom_bpeo_get_calendar_group_id();

	if ( ! $group_id ) {
		return $query;
	}

	// The admin-ajax endpoint has none of the group page's access control, so
	// only honour the scope for groups the caller may see. A denied request
	// gets an empty scope, which bpeo's pre_get_posts handler turns into
	// post__in => array( 0 ), i.e. no events, rather than the unscoped calendar.
	if ( ! hc_custom_bpeo_user_can_view_group_calendar( get_current_user_id(), $group_id ) ) {
		$query['bp_group'] = array();
		return $query;
	}

	// Consumed by bpeo's pre_get_posts handling of 'bp_group', which turns it
	// into a bpeo_event_group tax query (and admits private events, which is
	// how events in non-public groups are stored).
	$query['bp_group'] = $group_id;

	// Access has been checked against the group itself, so do not let EO's
	// capability-based 'readable' restriction hide the group's private events
	// from its members on top of that.
	$query['perm'] = '';

	return $query;
}

/**
 * Whether a user may view a group's calendar.
 *
 * Mirrors the visibility rule bp-event-organiser applies when listing an
 * event's connected groups: public groups are open to all; private and hidden
 * groups to their members and to community moderators.
 *
 * @param int $user_id  User ID (0 for anonymous).
 * @param int $group_id Group ID.
 * @return bool
 */
function hc_custom_bpeo_user_can_view_group_calendar( $user_id, $group_id ) {
	$group = groups_get_group( array( 'group_id' => (int) $group_id ) );

	if ( empty( $group->id ) ) {
		return false;
	}

	if ( 'public' === $group->status ) {
		return true;
	}

	if ( current_user_can( 'bp_moderate' ) ) {
		return true;
	}

	return (bool) groups_is_user_member( (int) $user_id, (int) $group_id );
}
// After bp-event-organiser's own filter (priority 10), which only works when
// bp_is_group() is true and so is a no-op inside admin-ajax.
add_filter( 'eventorganiser_fullcalendar_query', 'hc_custom_bpeo_filter_calendar_query_for_group', 20 );

/**
 * Load the script that sends the calendar's group along with its AJAX requests.
 *
 * Event Organiser enqueues 'eo_front' from wp_footer only when a calendar is on
 * the page, so this runs just before footer scripts print and piggybacks on it.
 * Hooked to wp_print_footer_scripts rather than wp_footer because the embedded
 * group calendar (?embedded=true) strips wp_footer actions.
 */
function hc_custom_bpeo_enqueue_group_calendar_script() {
	if ( ! wp_script_is( 'eo_front', 'enqueued' ) ) {
		return;
	}

	$js_path = 'includes/js/bpeo-group-calendar.js';

	wp_enqueue_script(
		'hc-custom-bpeo-group-calendar',
		plugins_url( $js_path, __DIR__ ),
		array( 'eo_front' ),
		filemtime( trailingslashit( plugin_dir_path( __DIR__ ) ) . $js_path ),
		true
	);
}
add_action( 'wp_print_footer_scripts', 'hc_custom_bpeo_enqueue_group_calendar_script', 5 );

/**
 * Point calendar event links at the group's rendering of the event.
 *
 * @param string $link          Current event permalink.
 * @param int    $event_id      Event post ID.
 * @param int    $occurrence_id Occurrence ID.
 * @return string
 */
function hc_custom_bpeo_filter_calendar_event_link_for_group( $link, $event_id = 0, $occurrence_id = 0 ) {
	$group_id = hc_custom_bpeo_get_calendar_group_id();
	$event_id = (int) $event_id;

	if ( ! $group_id || ! $event_id ) {
		return $link;
	}

	// Only events actually connected to this group have a rendering under it.
	$event_groups = array_map( 'intval', (array) bpeo_get_event_groups( $event_id ) );
	if ( ! in_array( $group_id, $event_groups, true ) ) {
		return $link;
	}

	$event = get_post( $event_id );
	if ( ! $event || empty( $event->post_name ) ) {
		return $link;
	}

	return trailingslashit( bpeo_get_group_permalink( $group_id ) . $event->post_name );
}
// After bp-event-organiser's own filter (priority 10), which only works when
// bp_is_group() is true and so is a no-op inside admin-ajax.
add_filter( 'eventorganiser_calendar_event_link', 'hc_custom_bpeo_filter_calendar_event_link_for_group', 20, 3 );

/** Sitewide calendar: members' private group events ***************************/

/**
 * IDs of private events connected to any of a user's groups.
 *
 * @param int $user_id User ID.
 * @return int[]
 */
function hc_custom_bpeo_get_member_private_event_ids( $user_id ) {
	$user_id = (int) $user_id;
	if ( ! $user_id ) {
		return array();
	}

	$user_groups = groups_get_user_groups( $user_id );
	$group_ids   = ! empty( $user_groups['groups'] ) ? array_map( 'intval', (array) $user_groups['groups'] ) : array();
	if ( empty( $group_ids ) ) {
		return array();
	}

	$group_terms = array();
	foreach ( $group_ids as $group_id ) {
		$group_terms[] = 'group_' . $group_id;
	}

	// Same shape of query bp-event-organiser uses for a group's events.
	$q = new WP_Query( array(
		'post_type'      => 'event',
		'post_status'    => 'private',
		'fields'         => 'ids',
		'posts_per_page' => -1,
		'showpastevents' => true,
		'tax_query'      => array(
			array(
				'taxonomy' => 'bpeo_event_group',
				'field'    => 'name',
				'terms'    => $group_terms,
				'operator' => 'IN',
			),
		),
	) );

	return array_values( array_filter( array_map( 'intval', (array) $q->posts ) ) );
}

/**
 * Let a logged-in member see their private groups' events on the unscoped
 * (sitewide) calendar.
 *
 * @param array $query Query vars as set up by EO.
 * @return array
 */
function hc_custom_bpeo_filter_calendar_query_for_member_groups( $query ) {
	// Group-scoped (including denied) and member calendars manage their own
	// visibility.
	if ( isset( $query['bp_group'] ) || isset( $query['bp_displayed_user_id'] ) ) {
		return $query;
	}

	// Callers whose role grants read_private_events already see every private
	// event through EO's 'readable' query; the membership-based restriction
	// below would only take events away from them.
	if ( current_user_can( 'read_private_events' ) ) {
		return $query;
	}

	$event_ids = hc_custom_bpeo_get_member_private_event_ids( get_current_user_id() );
	if ( empty( $event_ids ) ) {
		return $query;
	}

	// EO's 'readable' perm would limit private posts to the caller's own,
	// which, now that read_private_events is no longer handed to everyone,
	// would hide their groups' events. Admit private posts and let the
	// posts_where restriction confine them to the visible IDs.
	$query['hc_bpeo_private_event_ids'] = $event_ids;
	$query['perm']                      = '';
	$query['post_status']               = array_values( array_unique( array_merge( (array) ( $query['post_status'] ?? array( 'publish' ) ), array( 'private' ) ) ) );

	return $query;
}
add_filter( 'eventorganiser_fullcalendar_query', 'hc_custom_bpeo_filter_calendar_query_for_member_groups', 30 );

/**
 * Keep private events other than the caller's visible ones out of the query.
 *
 * @param string   $where    SQL WHERE clause.
 * @param WP_Query $wp_query The query.
 * @return string
 */
function hc_custom_bpeo_restrict_private_events_where( $where, $wp_query ) {
	$event_ids = $wp_query->get( 'hc_bpeo_private_event_ids' );
	if ( empty( $event_ids ) ) {
		return $where;
	}

	$event_ids = array_values( array_filter( array_map( 'intval', (array) $event_ids ) ) );
	if ( empty( $event_ids ) ) {
		return $where;
	}

	global $wpdb;
	$posts   = $wpdb->posts;
	$user_id = (int) get_current_user_id();
	$id_list = implode( ',', $event_ids );

	$where .= " AND ( {$posts}.post_status <> 'private' OR {$posts}.post_author = {$user_id} OR {$posts}.ID IN ({$id_list}) )";

	return $where;
}
add_filter( 'posts_where', 'hc_custom_bpeo_restrict_private_events_where', 20, 2 );
