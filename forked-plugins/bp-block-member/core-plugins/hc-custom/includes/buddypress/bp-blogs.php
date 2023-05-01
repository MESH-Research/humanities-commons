<?php
/**
 * Customizations to BuddyPress Blogs.
 *
 * @package Hc_Custom
 */

/**
 * Filter the sites query, exclude root blogs from query.
 *
 * @param array $args Includes the string of blog ids.
 * @return array $args
 */
function hcommons_exclude_root_blogs( $args ) {

	$blog_ids = explode( ',', $args['include_blog_ids'] );

	foreach ( get_networks() as $network ) {
		$blog_id  = get_main_site_id( $network->id );
		$blog_ids = array_diff( $blog_ids, array( $blog_id ) );
	}

	if ( ! empty( $blog_ids ) ) {
		$include_blogs            = implode( ',', $blog_ids );
		$args['include_blog_ids'] = $include_blogs;
	}

	return $args;
}

add_filter( 'bp_before_has_blogs_parse_args', 'hcommons_exclude_root_blogs', 999 );

/**
 * BuddyPress does not consider whether post comments are enabled when users reply to a post activity.
 * Remove the action responsible for posting the comment unless comments are enabled.
 *
 * @param int    $comment_id The activity ID for the posted activity comment.
 * @param array  $r          Parameters for the activity comment.
 * @param object $activity   Parameters of the parent activity item (in this case, the blog post).
 */
function hcommons_constrain_activity_comments( $comment_id, $r, $activity ) {
	switch_to_blog( $activity->item_id );

	// BP filters comments_open to prevent comments on its own post types.
	// Disable it for new_blog_post activities.
	if ( 'new_blog_post' === $activity->type ) {
		remove_filter( 'comments_open', 'bp_comments_open', 10, 2 );
	}

	if ( ! comments_open( $activity->secondary_item_id ) ) {
		remove_action( 'bp_activity_comment_posted', 'bp_blogs_sync_add_from_activity_comment', 10, 3 );
	}

	restore_current_blog();
}
// Priority 5 to run before bp_blogs_sync_add_from_activity_comment().
add_action( 'bp_activity_comment_posted', 'hcommons_constrain_activity_comments', 5, 3 );

/**
 * Ensure that the 'Create a site' button on the sites page is only visible to
 * users who are able to create a site on the network.
 * 
 * This addresses @link https://github.com/MESH-Research/hc-admin-docs-support/issues/237
 *
 * @see
 * buddypress/src/bp-blogs/bp-blogs-template.php::bp_get_blog_create_nav_item()
 * for 'bp_get_blog_create_nav_item' filter.
 * 
 * @param string $output The HTML for the create a site button list item.
 * 
 * @return string New HTML for the button (empty string if user cannot create site)
 */
function hcommons_filter_blog_create_nav_item( $output ) {
	if ( Humanities_Commons::hcommons_user_in_current_society() || is_super_admin() ) {
		return $output;
	}
	return '';
}
add_filter( 'bp_get_blog_create_nav_item', 'hcommons_filter_blog_create_nav_item', 10, 1 );

/**
 * Forces bp_blog_signup_enabled to return true, as caching issues seem to sometimes
 * get it stuck returning false.
 * 
 * This addresses @link https://github.com/MESH-Research/commons/issues/572 and subsequent
 * failures of the 'Create a Site' button to appear on the Sites page.
 * 
 * @see buddypress/bp-blogs/bp-blogs-template.php::bp_blog_create_button() for enabled check.
 * @see buddypress/bp-blogs/bp-blogs-functions.php::bp_blog_signup_enabled() for filter.
 *
 * @param string $active_signup all | none | blog | user
 * @return string 'all' as new value for $active_signup
 */
function hcommons_filter_bp_blog_signup_enabled( $active_signup ) {
	return 'all';
}
add_filter( 'wpmu_active_signup', 'hcommons_filter_bp_blog_signup_enabled', 10, 1 );
