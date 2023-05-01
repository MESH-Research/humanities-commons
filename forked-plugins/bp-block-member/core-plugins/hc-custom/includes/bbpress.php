<?php
/**
 * Customizations to bbpress
 *
 * @package Hc_Custom
 */

/**
 * Disable akismet for forum posts.
 */
add_filter( 'bbp_is_akismet_active', '__return_false' );

/**
 * Fix multinetwork forum names in multiforum bbp_create_topic activities.
 *
 * @param string               $forum_name The forum name.
 * @param int                  $forum_id   The forum ID.
 * @param BP_Activity_Activity $activity   The bbp_create_topic activity.
 * @return string
 */
function hcommons_fix_multinetwork_forum_name( $forum_name, $forum_id, $activity ) {
	$society_id       = bp_activity_get_meta( $activity->id, 'society_id', true );
	$activity_blog_id = (int) constant( strtoupper( $society_id ) . '_ROOT_BLOG_ID' );

	if ( get_current_blog_id() !== $activity_blog_id ) {
		switch_to_blog( $activity_blog_id );
		$forum_name = get_post_field( 'post_title', $forum_id, 'raw' );
		restore_current_blog();
	}

	return $forum_name;
}
add_filter( 'bpmfp_displayed_forum_name', 'hcommons_fix_multinetwork_forum_name', 10, 3 );
add_filter( 'bpmfp_added_topic_forum_name', 'hcommons_fix_multinetwork_forum_name', 10, 3 );

/**
 * Filter topic permalinks.
 * Switch blogs to get the correct metadata for topics on other networks.
 *
 * @param string $topic_permalink Permalink.
 * @param int    $topic_id ID.
 * @return string
 */
function hcommons_fix_multinetwork_topic_permalinks( $topic_permalink, $topic_id ) {
	// Assume we're already on the correct network if the permalink is set.
	if ( $topic_permalink ) {
		return $topic_permalink;
	}

	// Otherwise look up the activity to get its blog id.
	$results = bp_activity_get(
		[
			'filter' => [
				'action'       => 'bbp_topic_create',
				'secondary_id' => $topic_id,
			],
		]
	);

	// There should be one activity for the creation of any given topic.
	if ( 1 !== count( $results['activities'] ) ) {
		return $topic_permalink;
	}

	$society_id       = bp_activity_get_meta( $results['activities'][0]->id, 'society_id', true );
	$activity_blog_id = (int) constant( strtoupper( $society_id ) . '_ROOT_BLOG_ID' );

	switch_to_blog( $activity_blog_id );

	// Remove this filter so we can run the original again.
	remove_filter( 'bbp_get_topic_permalink', 'hcommons_fix_multinetwork_topic_permalinks', 10, 2 );

	$topic_permalink = bbp_get_topic_permalink( $topic_id );

	restore_current_blog();

	// Restore this filter.
	add_filter( 'bbp_get_topic_permalink', 'hcommons_fix_multinetwork_topic_permalinks', 10, 2 );

	return $topic_permalink;
}
add_filter( 'bbp_get_topic_permalink', 'hcommons_fix_multinetwork_topic_permalinks', 10, 2 );

/**
 * Replace default bbp notification formatter with our own multinetwork-compatible version.
 * Copied from bbp_format_buddypress_notifications().
 * Added switch_to_blog logic for multinetwork compatibility
 *
 * @param string $action                Component action.
 * @param int    $item_id               Notification item ID.
 * @param int    $secondary_item_id     Notification secondary item ID.
 * @param int    $total_items           Number of notifications with the same action.
 * @param string $format                Format of return. Either 'string' or 'object'.
 * @param string $component_action_name Canonical notification action.
 * @param string $component_name        Notification component ID.
 * @param int    $notification_id       Notification ID.
 */
function hcommons_bbp_format_buddypress_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string', $component_action_name, $component_name, $notification_id ) {
	$return = $action;

	if ( function_exists( 'bbp_format_buddypress_notifications' ) ) {

		// New reply notifications.
		if ( 'bbp_new_reply' === $action ) {
			$society_id           = bp_notifications_get_meta( $notification_id, 'society_id', true );
			$notification_blog_id = (int) constant( strtoupper( $society_id ) . '_ROOT_BLOG_ID' );
			$switched             = false;
			if ( ! empty( $notification_blog_id ) && get_current_blog_id() !== $notification_blog_id ) {
				switch_to_blog( $notification_blog_id );
				$switched = true;
			}

			$topic_id    = bbp_get_reply_topic_id( $item_id );
			$topic_title = bbp_get_topic_title( $topic_id );
			$topic_link  = wp_nonce_url(
				add_query_arg(
					array(
						'action'   => 'bbp_mark_read',
						'topic_id' => $topic_id,
					), bbp_get_reply_url( $item_id )
				), 'bbp_mark_topic_' . $topic_id
			);
			$title_attr  = __( 'Topic Replies', 'bbpress' );

			if ( (int) $total_items > 1 ) {
				// @codingStandardsIgnoreLine
				$text   = sprintf( __( 'You have %d new replies', 'bbpress' ), (int) $total_items );
				$filter = 'bbp_multiple_new_subscription_notification';
			} else {
				if ( ! empty( $secondary_item_id ) ) {
					// @codingStandardsIgnoreLine
					$text = sprintf( __( 'You have %d new reply to %2$s from %3$s', 'bbpress' ), (int) $total_items, $topic_title, bp_core_get_user_displayname( $secondary_item_id ) );
				} else {
					// @codingStandardsIgnoreLine
					$text = sprintf( __( 'You have %1$d new reply to %2$s', 'bbpress' ), (int) $total_items, $topic_title );
				}
				$filter = 'bbp_single_new_subscription_notification';
			}

			if ( 'string' === $format ) {
				// WordPress Toolbar.
				$return = apply_filters( $filter, '<a href="' . esc_url( $topic_link ) . '" title="' . esc_attr( $title_attr ) . '">' . esc_html( $text ) . '</a>', (int) $total_items, $text, $topic_link );

			} else {
				// Deprecated BuddyBar.
				$return = apply_filters(
					$filter, array(
						'text' => $text,
						'link' => $topic_link,
					), $topic_link, (int) $total_items, $text, $topic_title
				);
			}

			do_action( 'bbp_format_buddypress_notifications', $action, $item_id, $secondary_item_id, $total_items );

			if ( $switched ) {
				restore_current_blog();
			}
		}
	}

	return $return;
}
remove_filter( 'bp_notifications_get_notifications_for_user', 'bbp_format_buddypress_notifications' );
add_filter( 'bp_notifications_get_notifications_for_user', 'hcommons_bbp_format_buddypress_notifications', 999, 8 );

/**
 * Fix multinetwork forum permalinks in multiforum bbp_create_topic activities.
 *
 * @param string $forum_permalink Permalink.
 * @param int    $forum_id        ID.
 * @return string
 */
function hcommons_fix_multinetwork_forum_permalinks( $forum_permalink, $forum_id ) {
	// We depend on bp_get_activity_id() to look up the network ID in activity meta.
	if ( ! bp_get_activity_id() ) {
		return $forum_permalink;
	}

	if ( get_current_blog_id() !== $activity_blog_id ) {
		$society_id       = bp_activity_get_meta( bp_get_activity_id(), 'society_id', true );
		$activity_blog_id = (int) constant( strtoupper( $society_id ) . '_ROOT_BLOG_ID' );

		switch_to_blog( $activity_blog_id );

		// Remove this filter so we can run the original again.
		remove_filter( 'bbp_get_forum_permalink', 'hcommons_fix_multinetwork_forum_permalinks', 15, 2 );

		$forum_permalink = bbp_get_forum_permalink( $forum_id );

		restore_current_blog();

		// Restore this filter.
		add_filter( 'bbp_get_forum_permalink', 'hcommons_fix_multinetwork_forum_permalinks', 15, 2 );
	}

	return $forum_permalink;
}
// Priority 15 to run after CBox_BBP_Autoload->override_the_permalink_with_group_permalink().
add_filter( 'bbp_get_forum_permalink', 'hcommons_fix_multinetwork_forum_permalinks', 15, 2 );

/**
 * Make sure the_permalink() ends in /forum when posting a new topic so that
 * authors see their post and any errors after submission.
 *
 * @param string $url the permalink.
 * @return string filtered permalink ending in '/forum' (if applicable).
 */
function hcommons_fix_group_forum_permalinks( $url ) {
	if (
		bp_is_group() &&
		bp_is_current_action( 'forum' ) &&
		0 === preg_match( '#/forum#i', $url )
	) {
		$url = trailingslashit( $url ) . 'forum';
	}

	return $url;
}
// Priority 20 to run after CBox_BBP_Autoload->override_the_permalink_with_group_permalink().
add_filter( 'the_permalink', 'hcommons_fix_group_forum_permalinks', 20 );

/**
 * Add xprofile query to user query.
 *
 * @param BP_User_Query $q User query.
 */
function filter_bp_xprofile_add_xprofile_query_to_user_query( BP_User_Query $q ) {

	if ( bp_is_group_members() ) {
		$members_search = ! empty( $_REQUEST['members_search'] ) ? sanitize_text_field( $_REQUEST['members_search'] ) : sanitize_text_field( $_REQUEST['search_terms'] );

		if ( isset( $members_search ) && ! empty( $members_search ) ) {

			$args = array(
				'xprofile_query' => array(
					'relation' => 'AND',
					array(
						'field'   => 'Name',
						'value'   => $members_search,
						'compare' => 'LIKE',
					),
				),
			);

			$xprofile_query = new BP_XProfile_Query( $args );
			$sql            = $xprofile_query->get_sql( 'u', $q->uid_name );

			if ( ! empty( $sql['join'] ) ) {
				$q->uid_clauses['select'] .= $sql['join'];
				$q->uid_clauses['where']  .= $sql['where'];
			}
		}
	}
}

add_action( 'bp_pre_user_query', 'filter_bp_xprofile_add_xprofile_query_to_user_query' );

/**
 * Disables the forum subscription link.
 */
add_filter( 'bbp_get_forum_subscribe_link', '__return_false' );

/**
 * Disables bbPress image button.
 *
 * @param array $buttons the permalink.
 */
function hcommons_tinymce_buttons( $buttons ) {

	if ( bp_is_group() ) {
		// Remove image button.
		$remove  = array( 'image' );
		$buttons = array_diff( $buttons, $remove );
	}

	return $buttons;
}

add_filter( 'mce_buttons', 'hcommons_tinymce_buttons', 21 );

/**
 * Filter who can edit forum topics.
 *
 * @uses mla_is_group_committee()
 * @param  array $array Array of the links to modify.
 * @return array Modified array of items.
 */
function hcommons_topic_admin_links( $array ) {
	// Super admins can edit any post.
	if ( is_super_admin() ) {
		return $array;
	}

	// Committee admins can edit any post in their group.
	if ( mla_is_group_committee( bp_get_current_group_id() ) && groups_is_user_admin( get_current_user_id(), bp_get_current_group_id() ) ) {
		return $array;
	}

	// All other users can only edit their own posts.
	if (
		isset( $array['edit'] ) &&
		get_current_user_id() !== bbp_get_topic_author_id( bbp_get_topic_id() )
	) {
		unset( $array['edit'] );
	}

	return $array;
}
add_filter( 'bbp_topic_admin_links', 'hcommons_topic_admin_links' );

/**
 * Filter who can edit forum replies.
 *
 * @uses mla_is_group_committee()
 * @param  array $array Array of the links to modify.
 * @return array Modified array of items.
 */
function hcommons_reply_admin_links( $array ) {
	// Super admins can edit any post.
	if ( is_super_admin() ) {
		return $array;
	}

	// Committee admins can edit any post in their group.
	if ( mla_is_group_committee( bp_get_current_group_id() ) && groups_is_user_admin( get_current_user_id(), bp_get_current_group_id() ) ) {
		return $array;
	}

	// All other users can only edit their own posts.
	if (
		isset( $array['edit'] ) &&
		get_current_user_id() !== bbp_get_reply_author_id( bbp_get_reply_id() )
	) {
		unset( $array['edit'] );
	}

	return $array;
}
add_filter( 'bbp_reply_admin_links', 'hcommons_reply_admin_links' );


/**
 * Filter who can edit forum replies.
 *
 * @param array $statuses Array of statuses.
 */
function hc_custom_bbp_get_topic_statuses( $statuses ) {
	// Remove Pending status by key.
	unset( $statuses[ bbp_get_pending_status_id() ] );

	// Add private to status.
	$statuses = array_merge(
		$statuses, array(
			bbp_get_private_status_id() => _x( 'Admin Only', 'Viewable by Admins Only', 'bbpress' ),
		)
	);

	return $statuses;
}

add_filter( 'bbp_get_topic_statuses', 'hc_custom_bbp_get_topic_statuses' );

/**
 *
 * Filter on the current_user_can() function.
 * This function is used to explicitly allow authors to edit contributors and other
 * authors posts if they are published or pending.
 *
 * @param array $allcaps All the capabilities of the user.
 * @param array $cap     [0] Required capability.
 * @param array $args    [0] Requested capability.
 *                       [1] User ID.
 *                       [2] Associated object ID.
 */
function hc_custom_admin_cap_filter( $allcaps, $cap, $args ) {
	// Bail out if we're not asking about topics.
	if ( 'read_private_topics' == $args[0] || 'read_private_replies' == $args[0] ) {
		// Load the post data.
		$post = get_post( $args[2] );

		$group_id = bp_get_current_group_id();

		$new_caps = array(
			$cap[0] => true,
		);

		if ( groups_is_user_admin( $args[1], $group_id ) ) {
			$allcaps = array_merge( $allcaps, $new_caps );
		}
	}

	return $allcaps;
}

add_filter( 'user_has_cap', 'hc_custom_admin_cap_filter', 10, 3 );

/**
 * Allow admins to see private topics on the view page.
 *
 * @param object $query Instance (passed by reference).
 */
function hc_custom_show_private_posts_for_admins( $query ) {

	if ( function_exists( 'bbp_is_single_forum' ) ) {

		if ( is_admin() || ! bbp_is_single_forum() ) {
			return;
		}

		$user_id = get_current_user_id();

		$group_id = bp_get_current_group_id();

		if ( groups_is_user_admin( $user_id, $group_id ) ) {
			$query->set( 'post_status', array( 'private', 'publish', 'closed', 'hidden' ) );
		}
	}
}

add_action( 'pre_get_posts', 'hc_custom_show_private_posts_for_admins' );

/**
 * If current user can and is vewing all topics/replies
 *
 * @uses current_user_can() To check if the current user can moderate
 * @uses apply_filters() Calls 'bbp_get_view_all' with the link and original link
 * @param string $cap The requested capability.
 * @return bool Whether current user can and is viewing all
 */
function hc_custom_get_view_all( $cap ) {

	if ( function_exists( 'bbp_is_single_topic' ) ) {

		$user_id = get_current_user_id();

		$group_id = bp_get_current_group_id();

		if ( groups_is_user_admin( $user_id, $group_id ) && bbp_is_single_topic() ) {
			return true;
		}
	}
}

add_action( 'bbp_get_view_all', 'hc_custom_get_view_all' );

/**
 * Change the private topic title prepend.
 *
 * @param string $prepend The current prepend string.
 * @param object $post The post object.
 */
function hc_custom_private_title_format( $prepend, $post ) {
	/* translators: %s: topic title */
	if( 'topic' === $post->post_type ) {
	    $prepend = "<span class='badge-admin-only'>Admin Only </span>  " . __( ' %s' );
	
	return $prepend;
	} else {
	  return $post->post_title;
	}
}
add_filter( 'private_title_format', 'hc_custom_private_title_format', 10, 2 );

/**
 * Don't show the reply form on private topics.
 *
 * @param bool $access The current prepend string.
 */
function filter_bbp_current_user_can_access_create_reply_form( $access ) {

	if ( 'private' === get_post_status( bbp_get_topic_id() ) ) {
		$access = false;
	}
	return $access;
}

add_filter( 'bbp_current_user_can_access_create_reply_form', 'filter_bbp_current_user_can_access_create_reply_form', 999 );

/**
 * Return default for buddypress messages spamblocker.
 * @param int $max The maximum number of emails.
 */
function hc_custom_buddypress_messages_spamblocker( $max ) {
        $max = 10;
        return $max;
}

/**
 * Reduce the spam blokcer limit to 10 emails.
 */
add_filter( 'buddypress_messages_spamblocker_10m', 'hc_custom_buddypress_messages_spamblocker' );
add_filter( 'buddypress_messages_spamblocker_30m', 'hc_custom_buddypress_messages_spamblocker' );
add_filter( 'buddypress_messages_spamblocker_60m', 'hc_custom_buddypress_messages_spamblocker' );
add_filter( 'buddypress_messages_spamblocker_12h', 'hc_custom_buddypress_messages_spamblocker' );
add_filter( 'buddypress_messages_spamblocker_24d', 'hc_custom_buddypress_messages_spamblocker' );

/**
 * Bypass the bbPress comment moderation feature.
 *
 * There is not a good interface for comment moderation, so it is better to
 * bypass it unless spam starts to become a problem.
 * 
 * @see https://github.com/MESH-Research/boss-child/issues/113
 *
 * @see bbpress/includes/common/functions.php::bbp_check_for_moderation()
 */
function hc_custom_bypass_moderation( $false, $anonymous_data, $author_id, $title, $content, $strict ) {
	return True;
}
add_filter( 'bbp_bypass_check_for_moderation', 'hc_custom_bypass_moderation', 10, 6 );

/**
 * Avoid iterating through every group to update latest topics and replies when
 * making a discussion post.
 *
 * The bbpress functions bbp_update_forum_last_topic_id,
 * bbp_update_forum_last_reply_id, and bbp_update_forum_last_active_id are
 * called whenever a discussion post is added. These functions are intended to
 * update the post id of the latest discussion post, for both the current group
 * and its parent. When the parent is updated, the default behavior is not to
 * compare the post being added with the previous latest post, but to iterate
 * through every child form and find the latest post. When there are a large
 * number of groups, this takes a long time.
 *
 * This function short circuits this behavior and only compares the discussion
 * post being added with the previous latest post stored by the parent forum.
 * This is much more efficient and seems safe, though there is possibly some
 * race condition that the default behavior would catch and this would not.
 *
 * This function operates by setting a value for topic_id, reply_id, and
 * active_id when arguments are being parsed, taking advantage of the fact that
 * child forums are only iterated through if the topic_id or reply_id are not
 * set.
 * 
 * @see https://github.com/MESH-Research/commons/issues/242
 * @see https://github.com/MESH-Research/commons/issues/434
 *
 * @see bbpress/includes/forums/functions.php::bbp_update_forum_last_topic_id
 * @see bbpress/includes/forums/functions.php::bbp_update_forum_last_reply_id
 * @see bbpress/includes/forums/functions.php::bbp_update_forum_last_active_id
 * @see bbpress/includes/common/functions.php::bbp_parse_args
 * 
 * @author Mike Thicke
 *
 * @param Array $r        The current arguments
 * @param Array $args     The initial arguments before filtering
 * @param Array $defualts The default arguments
 *
 * @return Array Filtered arguments with topic_id, reply_id, active_id set if not already.
 */
function hc_custom_calc_parent_latest_topic( $r, $args, $defaults ) {
	$forum_id = $r['forum_id'];

	$latest_topic_id = wp_cache_get( 'hc_custom_calc_parent_latest_topic_id' );
	if ( $latest_topic_id === false ) {
		$latest_topic_id = (int) get_post_meta( $forum_id, '_bbp_last_topic_id', true );
	}

	$latest_reply_id = wp_cache_get( 'hc_custom_calc_parent_latest_reply_id' );
	if ( $latest_reply_id === false ) {
		$latest_reply_id = (int) get_post_meta( $forum_id, '_bbp_last_reply_id', true );
	}

	// If this forum has topics...
	$topic_ids = bbp_forum_query_topic_ids( $forum_id );
	if ( ! empty( $topic_ids ) ) {

		// ...get the most recent reply from those topics...
		$reply_id = bbp_forum_query_last_reply_id( $forum_id, $topic_ids );

		// ...and compare it to the most recent topic id...
		$reply_id = ( $reply_id > max( $topic_ids ) )
			? $reply_id
			: max( $topic_ids );
	}

	if ( 
		array_key_exists( 'last_topic_id', $r ) && 
		$r['last_topic_id'] !== 0 && 
		$r['last_topic_id'] > $latest_topic_id 
	) {
		wp_cache_set( 'hc_custom_calc_parent_latest_topic_id', $r['last_topic_id'] );
		if ( $reply_id > $latest_reply_id ) {
			wp_cache_set( 'hc_custom_calc_parent_latest_reply_id', $reply_id );
		}
	} else {
		$latest_post_meta = (int) get_post_meta( $forum_id, '_bbp_last_topic_id', true );
		if ( $latest_topic_id > $latest_post_meta ) {
			$r['last_topic_id'] = $latest_topic_id;
		} else {
			$r['last_topic_id'] = $latest_post_meta;
		}

		/*
		 * The logic used to calculate last_reply_id and last_active_id is
		 * exactly the same, so when one is set the other can be without
		 * checking.
		 *
		 * @see bbp_update_forum_last_reply_id() and
		 * bbp_update_forum_last_active_id() in
		 * plugins/bbpress/includes/forums/functions.php
		 */
		if ( $reply_id < $latest_reply_id ) {
			$r['last_reply_id'] = $latest_reply_id;
			$r['last_active_id'] = $latest_reply_id;
		}
	}

	return $r;
}
add_filter( 'bbp_after_update_forum_parse_args', 'hc_custom_calc_parent_latest_topic', 10, 3 );

function hc_bbp_get_post_types( $r, $args, $defaults ) {
	if ( empty( $r['post_type'] ) ) {
		$r['post_type'] = [
			bbp_get_reply_post_type(),
			bbp_get_topic_post_type(),
			bbp_get_forum_post_type(),
		];
	}

	return $r;
}
add_filter( 'bbp_after_has_search_results_parse_args', 'hc_bbp_get_post_types', 10, 3 );
