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
		case 'read_private_events':
		case 'read_event':
			// we've already parsed this logic in bpeo_map_basic_meta_caps().
			if ( 'exist' === $caps[0] ) {
				return $caps;
			}

			if ( 'private' !== $event->post_status ) {
				// EO uses 'read', which doesn't include non-logged-in users.
				$caps = array( 'exist' );

			} elseif ( array_intersect( $user_groups['groups'], $event_groups ) ) {
				$caps = array( 'read' );
			}

			// @todo group admins / mods permissions
		case 'edit_event':
			break;

		case 'connect_event_to_group':
			$group_id = $args[0];
			$setting  = bpeo_get_group_minimum_member_role_for_connection( $group_id );

			if ( 'admin_mod' === $setting ) {
				$can_connect = groups_is_user_admin( $user_id, $group_id ) || groups_is_user_mod( $user_id, $group_id );
			} else {
				$can_connect = groups_is_user_member( $user_id, $group_id );
			}

			if ( $can_connect ) {
				$caps = array( 'read' );
			}

			break;
	}

	return $caps;
}
add_filter( 'map_meta_cap', 'hc_custom_bpeo_group_event_meta_cap', 20, 5 );

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



