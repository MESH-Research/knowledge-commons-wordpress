<?php
/**
 * Bulk forum visibility for BuddyPress activity queries.
 *
 * bbPress 2.6.19 decides which forum activity a visitor may see by running
 * user_can( $user, 'read_forum', $forum ) for every private or hidden forum on
 * every activity query (once in the group extension's exclusion filter, again
 * in BBP_BuddyPress_Activity::activity_get_where_conditions()). Each check runs
 * three capability mappers with their own post, meta and membership lookups,
 * so the cost grows with the number of restricted forums: ~25s per query on
 * hcommons.org (issue #158).
 *
 * This file computes the same answer in bulk: a handful of queries fetch every
 * restricted forum, its descendants, group attachments and moderators, plus the
 * visitor's memberships, and a pure function applies bbPress's rules to all of
 * them at once. The result is memoised per request and used to replace both
 * slow bbPress callbacks with equivalent ones.
 *
 * @package Hc_Custom
 */

/**
 * Apply bbPress's read_forum rules to a set of forums for one user.
 *
 * Mirrors, in order, bbp_map_forum_meta_caps() (inactive users, restricted
 * ancestors, authors, read_private/hidden caps), hc_custom_map_group_forum_meta_caps()
 * (banned members are denied, group members allowed) and
 * BBP_Forums_Group_Extension::map_group_forum_read_meta_caps() (group forums
 * are readable only by non-banned members or moderators).
 *
 * @param array $forums Forum data keyed by forum ID: parent, status, author,
 *                      group_ids, moderator_ids.
 * @param array $user   id, is_inactive, is_super_admin, is_keymaster,
 *                      can_moderate, can_read_private, can_read_hidden,
 *                      member_group_ids, banned_group_ids.
 * @return int[] Sorted IDs of the given forums this user cannot read.
 */
function hc_custom_compute_unreadable_forum_ids( array $forums, array $user ) {
	$restricted_statuses = array( bbp_get_private_status_id(), bbp_get_hidden_status_id() );
	$user_id             = isset( $user['id'] ) ? (int) $user['id'] : 0;
	$members             = array_map( 'intval', isset( $user['member_group_ids'] ) ? (array) $user['member_group_ids'] : array() );
	$banned              = array_map( 'intval', isset( $user['banned_group_ids'] ) ? (array) $user['banned_group_ids'] : array() );
	$moderates_all       = ! empty( $user['is_super_admin'] ) || ! empty( $user['is_keymaster'] ) || ! empty( $user['can_moderate'] );
	$inactive            = ! empty( $user['is_inactive'] );
	$can_read_private    = ! empty( $user['can_read_private'] );
	$can_read_hidden     = ! empty( $user['can_read_hidden'] );
	$memo                = array();

	$is_restricted = function ( $forum ) use ( $restricted_statuses ) {
		return isset( $forum['status'] ) && in_array( $forum['status'], $restricted_statuses, true );
	};

	$is_moderator = function ( $forum ) use ( $moderates_all, $user_id ) {
		if ( $moderates_all ) {
			return true;
		}
		$mods = isset( $forum['moderator_ids'] ) ? array_map( 'intval', (array) $forum['moderator_ids'] ) : array();
		return $user_id > 0 && in_array( $user_id, $mods, true );
	};

	$unreadable = function ( $forum_id ) use ( &$unreadable, &$memo, $forums, $is_restricted, $is_moderator, $inactive, $user_id, $members, $banned, $can_read_private, $can_read_hidden ) {
		if ( isset( $memo[ $forum_id ] ) ) {
			return $memo[ $forum_id ];
		}
		$memo[ $forum_id ] = false; // Guard against parent cycles.
		$forum             = $forums[ $forum_id ];

		// Inactive (spammed/deleted) users are denied read_forum everywhere.
		if ( $inactive ) {
			return $memo[ $forum_id ] = true;
		}

		if ( $is_moderator( $forum ) ) {
			return $memo[ $forum_id ] = false;
		}

		// A restricted ancestor the user cannot read closes every descendant,
		// including public forums in between (bbPress checks the parent with
		// its own ancestors, so the restriction propagates all the way down).
		$parent_id = isset( $forum['parent'] ) ? (int) $forum['parent'] : 0;
		if ( $parent_id && isset( $forums[ $parent_id ] ) && $unreadable( $parent_id ) ) {
			return $memo[ $forum_id ] = true;
		}

		if ( ! $is_restricted( $forum ) ) {
			return $memo[ $forum_id ] = false;
		}

		$group_ids = isset( $forum['group_ids'] ) ? array_map( 'intval', (array) $forum['group_ids'] ) : array();
		if ( ! empty( $group_ids ) ) {
			if ( array_intersect( $group_ids, $banned ) ) {
				return $memo[ $forum_id ] = true;
			}
			return $memo[ $forum_id ] = ! array_intersect( $group_ids, $members );
		}

		if ( $user_id > 0 && isset( $forum['author'] ) && (int) $forum['author'] === $user_id ) {
			return $memo[ $forum_id ] = false;
		}
		if ( bbp_get_private_status_id() === $forum['status'] ) {
			return $memo[ $forum_id ] = ! $can_read_private;
		}
		return $memo[ $forum_id ] = ! $can_read_hidden;
	};

	$result = array();
	foreach ( array_keys( $forums ) as $forum_id ) {
		if ( $unreadable( (int) $forum_id ) ) {
			$result[] = (int) $forum_id;
		}
	}
	sort( $result );
	return $result;
}

/**
 * Whether an activity query may return bbPress topic or reply activity.
 *
 * Same logic as BBP_BuddyPress_Activity::query_can_include_forum_activity().
 *
 * @param array $args BuddyPress activity query arguments.
 * @return bool
 */
function hc_custom_activity_query_can_include_forum_activity( array $args ) {
	if ( empty( $args['filter'] ) || ! is_array( $args['filter'] ) ) {
		return true;
	}
	$filter = $args['filter'];

	if ( ! empty( $filter['action'] ) ) {
		$actions = wp_parse_list( $filter['action'] );
		if ( ! array_intersect( $actions, array( 'bbp_topic_create', 'bbp_reply_create' ) ) ) {
			return false;
		}
	}

	if ( ! empty( $filter['object'] ) ) {
		$objects = wp_parse_list( $filter['object'] );
		if ( ! array_intersect( $objects, array( 'bbpress', 'groups' ) ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Whether this is BuddyPress REST's single-item lookup, which checks access itself.
 *
 * Same logic as BBP_BuddyPress_Activity::is_single_activity_lookup().
 *
 * @param array $args BuddyPress activity query arguments.
 * @return bool
 */
function hc_custom_activity_is_single_lookup( array $args ) {
	return ! empty( $args['show_hidden'] )
		&& ! empty( $args['display_comments'] )
		&& empty( $args['per_page'] )
		&& ! empty( $args['in'] )
		&& 1 === count( wp_parse_id_list( $args['in'] ) );
}

/**
 * Build the SQL that hides forum activity the visitor may not read.
 *
 * Produces the same two conditions as BBP_BuddyPress_Activity::activity_get_where_conditions().
 * All IDs are cast to integers so no placeholder escaping is needed.
 *
 * @param array  $where_conditions Existing conditions.
 * @param int[]  $forum_ids        Unreadable forum IDs.
 * @param int[]  $topic_ids        Password-protected topic IDs.
 * @param int[]  $reply_ids        Password-protected reply IDs.
 * @param string $posts_table      Posts table name.
 * @return array
 */
function hc_custom_build_forum_activity_where_conditions( array $where_conditions, array $forum_ids, array $topic_ids, array $reply_ids, $posts_table ) {
	$forum_ids = wp_parse_id_list( $forum_ids );
	$topic_ids = wp_parse_id_list( $topic_ids );
	$reply_ids = wp_parse_id_list( $reply_ids );

	if ( empty( $forum_ids ) && empty( $topic_ids ) && empty( $reply_ids ) ) {
		return $where_conditions;
	}

	$topic_type   = esc_sql( 'bbp_topic_create' );
	$reply_type   = esc_sql( 'bbp_reply_create' );
	$bbp_comp     = esc_sql( 'bbpress' );
	$groups_comp  = esc_sql( 'groups' );
	$in           = function ( array $ids ) {
		return implode( ', ', array_map( 'intval', $ids ) );
	};

	$topic_restrictions = array();
	if ( $forum_ids ) {
		$topic_restrictions[] = 'bbp_topic.post_parent IN (' . $in( $forum_ids ) . ')';
	}
	if ( $topic_ids ) {
		$topic_restrictions[] = 'bbp_topic.ID IN (' . $in( $topic_ids ) . ')';
	}
	if ( $topic_restrictions ) {
		$where_conditions['bbpress_topic_visibility'] = "
			NOT (
				a.type = '{$topic_type}'
				AND (
					a.component = '{$bbp_comp}'
					OR a.component = '{$groups_comp}'
				)
				AND EXISTS (
					SELECT 1
					FROM {$posts_table} AS bbp_topic
					WHERE bbp_topic.ID = CASE
						WHEN a.component = '{$groups_comp}' THEN a.secondary_item_id
						ELSE a.item_id
					END
						AND (" . implode( ' OR ', $topic_restrictions ) . ')
				)
			)';
	}

	$reply_restrictions = array();
	if ( $forum_ids ) {
		$reply_restrictions[] = 'bbp_reply_topic.post_parent IN (' . $in( $forum_ids ) . ')';
	}
	if ( $topic_ids ) {
		$reply_restrictions[] = 'bbp_reply_topic.ID IN (' . $in( $topic_ids ) . ')';
	}
	if ( $reply_ids ) {
		$reply_restrictions[] = 'bbp_reply.ID IN (' . $in( $reply_ids ) . ')';
	}
	if ( $reply_restrictions ) {
		$where_conditions['bbpress_reply_visibility'] = "
			NOT (
				a.type = '{$reply_type}'
				AND (
					a.component = '{$bbp_comp}'
					OR a.component = '{$groups_comp}'
				)
				AND EXISTS (
					SELECT 1
					FROM {$posts_table} AS bbp_reply
					INNER JOIN {$posts_table} AS bbp_reply_topic
						ON bbp_reply_topic.ID = bbp_reply.post_parent
					WHERE bbp_reply.ID = CASE
						WHEN a.component = '{$groups_comp}' THEN a.secondary_item_id
						ELSE a.item_id
					END
						AND (" . implode( ' OR ', $reply_restrictions ) . ')
				)
			)';
	}

	return $where_conditions;
}

/**
 * Replacement for BBP_BuddyPress_Activity::activity_get_where_conditions().
 *
 * @param array      $where_conditions BuddyPress activity query conditions.
 * @param array      $args             BuddyPress activity query arguments.
 * @param int[]|null $forum_ids        Unreadable forum IDs (resolved for the current user when null).
 * @param array|null $password_ids     Password-protected IDs keyed by post type (resolved when null).
 * @return array
 */
function hc_custom_bbp_activity_where_conditions( $where_conditions, $args, $forum_ids = null, $password_ids = null ) {
	$where_conditions = (array) $where_conditions;
	$args             = (array) $args;

	if ( hc_custom_activity_is_single_lookup( $args ) || ! hc_custom_activity_query_can_include_forum_activity( $args ) ) {
		return $where_conditions;
	}

	if ( null === $forum_ids ) {
		$forum_ids = hc_custom_get_unreadable_forum_ids_for_user( bbp_get_current_user_id() );
	}
	if ( null === $password_ids ) {
		$password_ids = hc_custom_get_password_restricted_bbp_post_ids();
	}

	$forum_type = bbp_get_forum_post_type();
	$topic_type = bbp_get_topic_post_type();
	$reply_type = bbp_get_reply_post_type();

	$forum_ids = array_merge( (array) $forum_ids, isset( $password_ids[ $forum_type ] ) ? (array) $password_ids[ $forum_type ] : array() );
	$topic_ids = isset( $password_ids[ $topic_type ] ) ? (array) $password_ids[ $topic_type ] : array();
	$reply_ids = isset( $password_ids[ $reply_type ] ) ? (array) $password_ids[ $reply_type ] : array();

	$posts_table = isset( $GLOBALS['wpdb']->posts ) ? $GLOBALS['wpdb']->posts : 'wp_posts';

	return hc_custom_build_forum_activity_where_conditions( $where_conditions, $forum_ids, $topic_ids, $reply_ids, $posts_table );
}

/**
 * Replacement for BBP_Forums_Group_Extension::exclude_group_forum_ids().
 *
 * bbPress's core list contains every restricted forum for users lacking the
 * read_private/hidden_forums caps and nothing for users holding them; the group
 * extension then adds group forums the user is not a member of and removes
 * those they are. The bulk set already encodes all of those rules, so it is the
 * answer.
 *
 * @param array      $forum_ids      Forum IDs excluded so far.
 * @param int|null   $user_id        User being checked (current bbPress user when null).
 * @param int[]|null $unreadable_ids Precomputed bulk set (resolved when null).
 * @return int[]
 */
function hc_custom_exclude_group_forum_ids( $forum_ids, $user_id = null, $unreadable_ids = null ) {
	if ( null === $user_id ) {
		$user_id = bbp_get_current_user_id();
	}
	if ( null === $unreadable_ids ) {
		$unreadable_ids = hc_custom_get_unreadable_forum_ids_for_user( $user_id );
	}
	return wp_parse_id_list( $unreadable_ids );
}

/**
 * Per-request memo of unreadable forum IDs, keyed by blog and user.
 *
 * @param int $user_id User ID (0 for anonymous).
 * @return int[]
 */
function hc_custom_get_unreadable_forum_ids_for_user( $user_id ) {
	static $memo = array();
	$user_id = (int) $user_id;
	$key     = get_current_blog_id() . ':' . $user_id;

	if ( ! isset( $memo[ $key ] ) ) {
		$data         = hc_custom_collect_forum_visibility_data( $user_id );
		$memo[ $key ] = hc_custom_compute_unreadable_forum_ids( $data['forums'], $data['user'] );
	}
	if ( isset( $GLOBALS['_hc_forum_visibility_reset'] ) ) {
		$memo = array();
		unset( $GLOBALS['_hc_forum_visibility_reset'] );
		return hc_custom_get_unreadable_forum_ids_for_user( $user_id );
	}
	return $memo[ $key ];
}

/**
 * Drop the per-request memo (after membership or forum changes, and in tests).
 */
function hc_custom_reset_forum_visibility_cache() {
	$GLOBALS['_hc_forum_visibility_reset'] = true;
	unset( $GLOBALS['_hc_password_restricted_bbp_ids'] );
}

/**
 * Gather every restricted forum, its descendants, attachments and the user's
 * standing in a handful of queries.
 *
 * @param int $user_id User ID (0 for anonymous).
 * @return array{forums: array, user: array}
 */
function hc_custom_collect_forum_visibility_data( $user_id ) {
	global $wpdb;

	$user_id    = (int) $user_id;
	$restricted = wp_parse_id_list( array_merge( (array) bbp_get_private_forum_ids(), (array) bbp_get_hidden_forum_ids() ) );
	$forums     = array();

	if ( ! empty( $restricted ) ) {
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT ID, post_parent, post_status, post_author FROM {$wpdb->posts} WHERE post_type = %s",
			bbp_get_forum_post_type()
		) );

		$all      = array();
		$children = array();
		foreach ( (array) $rows as $row ) {
			$all[ (int) $row->ID ] = array(
				'parent' => (int) $row->post_parent,
				'status' => (string) $row->post_status,
				'author' => (int) $row->post_author,
			);
			$children[ (int) $row->post_parent ][] = (int) $row->ID;
		}

		// Restricted forums plus every descendant (public ones inherit the restriction).
		$wanted = array();
		$queue  = $restricted;
		while ( $queue ) {
			$id = (int) array_shift( $queue );
			if ( isset( $wanted[ $id ] ) || ! isset( $all[ $id ] ) ) {
				continue;
			}
			$wanted[ $id ] = true;
			if ( isset( $children[ $id ] ) ) {
				$queue = array_merge( $queue, $children[ $id ] );
			}
		}
		// Ancestors are needed so an unreadable restricted parent can close its children.
		foreach ( array_keys( $wanted ) as $id ) {
			$parent = $all[ $id ]['parent'];
			while ( $parent && isset( $all[ $parent ] ) && ! isset( $wanted[ $parent ] ) ) {
				$wanted[ $parent ] = true;
				$parent            = $all[ $parent ]['parent'];
			}
		}

		$ids = array_keys( $wanted );
		update_meta_cache( 'post', $ids );
		$allow_mods = function_exists( 'bbp_allow_forum_mods' ) ? bbp_allow_forum_mods() : true;

		foreach ( $ids as $id ) {
			$forums[ $id ]                  = $all[ $id ];
			$forums[ $id ]['group_ids']     = wp_parse_id_list( get_post_meta( $id, '_bbp_group_ids', true ) );
			$forums[ $id ]['moderator_ids'] = $allow_mods ? wp_parse_id_list( get_post_meta( $id, '_bbp_moderator_id', false ) ) : array();
		}
	}

	$user = array(
		'id'               => $user_id,
		'is_inactive'      => $user_id > 0 && bbp_is_user_inactive( $user_id ),
		'is_super_admin'   => $user_id > 0 && is_super_admin( $user_id ),
		'is_keymaster'     => $user_id > 0 && bbp_is_user_keymaster( $user_id ),
		'can_moderate'     => $user_id > 0 && user_can( $user_id, 'moderate' ),
		'can_read_private' => $user_id > 0 && user_can( $user_id, 'read_private_forums' ),
		'can_read_hidden'  => $user_id > 0 && user_can( $user_id, 'read_hidden_forums' ),
		'member_group_ids' => array(),
		'banned_group_ids' => array(),
	);

	if ( $user_id > 0 && function_exists( 'bp_is_active' ) && bp_is_active( 'groups' ) ) {
		$all_roles = array( 'is_admin' => null, 'is_mod' => null );
		$user['member_group_ids'] = array_map( 'intval', array_keys( (array) bp_get_user_groups( $user_id, $all_roles + array( 'is_confirmed' => true, 'is_banned' => false ) ) ) );
		$user['banned_group_ids'] = array_map( 'intval', array_keys( (array) bp_get_user_groups( $user_id, $all_roles + array( 'is_confirmed' => null, 'is_banned' => true ) ) ) );
	}

	return array( 'forums' => $forums, 'user' => $user );
}

/**
 * Password-protected bbPress post IDs the visitor cannot read, keyed by post
 * type and memoised per request. Same rules as bbPress, including inheritance
 * of a forum password by its descendants.
 *
 * @return array
 */
function hc_custom_get_password_restricted_bbp_post_ids() {
	if ( isset( $GLOBALS['_hc_password_restricted_bbp_ids'] ) ) {
		return $GLOBALS['_hc_password_restricted_bbp_ids'];
	}

	$post_types = array( bbp_get_forum_post_type(), bbp_get_topic_post_type(), bbp_get_reply_post_type() );
	$post_ids   = array_fill_keys( $post_types, array() );
	$protected  = get_posts( array(
		'has_password'           => true,
		'no_found_rows'          => true,
		'numberposts'            => -1,
		'orderby'                => 'none',
		'post_status'            => 'any',
		'post_type'              => $post_types,
		'suppress_filters'       => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	) );

	foreach ( $protected as $post ) {
		if ( post_password_required( $post ) ) {
			$post_ids[ $post->post_type ][] = (int) $post->ID;
		}
	}

	$forum_type = bbp_get_forum_post_type();
	$parents    = $post_ids[ $forum_type ];
	$seen       = array_fill_keys( $parents, true );
	while ( $parents ) {
		$parent_id = array_shift( $parents );
		foreach ( bbp_forum_query_subforum_ids( $parent_id ) as $forum_id ) {
			$forum_id = (int) $forum_id;
			if ( ! isset( $seen[ $forum_id ] ) ) {
				$seen[ $forum_id ]         = true;
				$post_ids[ $forum_type ][] = $forum_id;
				$parents[]                 = $forum_id;
			}
		}
	}

	$GLOBALS['_hc_password_restricted_bbp_ids'] = $post_ids;
	return $post_ids;
}

/**
 * Swap bbPress's per-forum callbacks for the bulk ones.
 *
 * Runs on bp_init after bbPress has built its BuddyPress extensions (priority 7)
 * and BuddyPress has instantiated the registered group extension (priority 10).
 */
function hc_custom_replace_bbpress_forum_visibility_filters() {
	global $wp_filter;

	// Kill switch: define HC_CUSTOM_DISABLE_BULK_FORUM_VISIBILITY to fall back to bbPress's own checks.
	if ( defined( 'HC_CUSTOM_DISABLE_BULK_FORUM_VISIBILITY' ) && HC_CUSTOM_DISABLE_BULK_FORUM_VISIBILITY ) {
		return;
	}
	if ( ! function_exists( 'bbpress' ) || ! class_exists( 'BBP_Forums_Group_Extension' ) ) {
		return;
	}

	if ( isset( $wp_filter['bbp_get_excluded_forum_ids'] ) ) {
		foreach ( $wp_filter['bbp_get_excluded_forum_ids']->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$fn = $callback['function'];
				if ( is_array( $fn ) && isset( $fn[0] ) && $fn[0] instanceof BBP_Forums_Group_Extension && 'exclude_group_forum_ids' === $fn[1] ) {
					remove_filter( 'bbp_get_excluded_forum_ids', $fn, $priority );
				}
			}
		}
	}
	add_filter( 'bbp_get_excluded_forum_ids', 'hc_custom_exclude_group_forum_ids', 20 );

	$bbp = bbpress();
	if ( isset( $bbp->extend->buddypress->activity ) && is_object( $bbp->extend->buddypress->activity ) && method_exists( $bbp->extend->buddypress->activity, 'activity_get_where_conditions' ) ) {
		remove_filter( 'bp_activity_get_where_conditions', array( $bbp->extend->buddypress->activity, 'activity_get_where_conditions' ), 10 );
		add_filter( 'bp_activity_get_where_conditions', 'hc_custom_bbp_activity_where_conditions', 10, 2 );
	}
}
add_action( 'bp_init', 'hc_custom_replace_bbpress_forum_visibility_filters', 50 );

// Membership and forum changes invalidate the per-request memo.
foreach ( array( 'groups_join_group', 'groups_leave_group', 'groups_ban_member', 'groups_unban_member', 'groups_member_after_save', 'groups_member_after_remove', 'bbp_new_forum', 'bbp_edit_forum', 'bbp_trashed_forum', 'bbp_deleted_forum' ) as $hc_fv_hook ) {
	add_action( $hc_fv_hook, 'hc_custom_reset_forum_visibility_cache', 1, 0 );
}
unset( $hc_fv_hook );
