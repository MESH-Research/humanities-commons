<?php
/**
 * Customizations to BuddyPress Activity.
 *
 * @package Hc_Custom
 */

/**
 * Removes the activity form if there is a discussion board.
 *
 * @param array  $templates Array of templates located.
 * @param string $slug      Template part slug requested.
 * @param string $name      Template part name requested.
 */
function hc_custom_template_part_filter( $templates, $slug, $name ) {

	if ( 'activity/post-form' !== $slug ) {
		return $templates;
	}

	if ( bp_is_group() ) {
		$bp = buddypress();

		// Get group forum IDs.
		$forum_ids = bbp_get_group_forum_ids( $group->id );

		// Bail if no forum IDs available.
		if ( empty( $forum_ids ) ) {
			return $templates;
		} else {
			return false;
		}
	}

	return $templates;
}
add_filter( 'bp_get_template_part', 'hc_custom_template_part_filter', 10, 3 );

/**
 * Mark messages as spam that Akismet might have missed but we know are spam.
 *
 * Note: this is a temporary fix, and we probably want a more robust way of doing this.
 *
 * @author Mike Thicke
 *
 * @param BP_Activity_Activity $activity The activity item to check.
 */
function hc_custom_check_activity( $activity ) {
	if ( empty( $activity->content ) ) {
		return;
	}

	$blocked_patterns = [
		'/mend testing our new project -.*http:\/\//i',
	];

	foreach ( $blocked_patterns as $pattern ) {
		if ( preg_match( $pattern, $activity->content ) ) {
			bp_activity_mark_as_spam( $activity );
			break;
		}
	}
}
add_action( 'bp_activity_before_save', 'hc_custom_check_activity', 10, 1 );
