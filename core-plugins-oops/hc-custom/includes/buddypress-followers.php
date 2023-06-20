<?php
/**
 * Customizations to buddypress followers
 *
 * @package Hc_Custom
 */

/**
 * Disables "followers" menu item on the top right
 *
 * @uses $wp_admin_bar
 */
function hcommons_admin_bar_remove_followers() {
	global $wp_admin_bar;

	$wp_admin_bar->remove_node( 'my-account-follow-followers' );
}

add_action( 'wp_before_admin_bar_render', 'hcommons_admin_bar_remove_followers' );

/**
 * Prevent users from seeing one another's followers (can only see their own).
 * Unfortunately there's no filter to prevent running the query, but we can at least empty the result before rendering.
 *
 * @param array $followers Followers.
 * @return array
 */
function hcommons_filter_get_followers( $followers ) {
	if ( bp_displayed_user_id() !== get_current_user_id() ) {
		$followers = [];
	}
	return $followers;
}
add_filter( 'bp_follow_get_followers', 'hcommons_filter_get_followers' );
