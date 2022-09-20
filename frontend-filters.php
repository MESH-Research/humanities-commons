<?php
/**
 * Filters altering the frontend appearance of The Commons.
 */

/**
 * Adds a link to an author's profile when displaying their name on the
 * frontend.
 *
 * This is intended to alter the display of an author's name in the post_author
 * block. There doesn't appear to be a way of checking whether we are in that
 * context specifically, so this function tries to check for some cases where we
 * might not want to alter the author's display name.
 * 
 * @see https://github.com/MESH-Research/hastac-migration/issues/92
 *
 * @param string $value    The author's display name
 * @param int    $user_id  The author's user_id
 *
 * @return string Author's display name wrapped in link to their profile.
 */
function hc_add_post_author_profile_link( $value, $user_id, $original_user_id ) {
	global $wp_current_filter;
	
	if ( is_admin() ) {
		return $value;
	}

	// In the context where this is meant to be called,
	// get_the_author_display_name should be the only active filter. If there
	// are other nested filters, we probably don't want to do anything.
	if ( count( $wp_current_filter ) > 1 ) {
		return $value;
	}

	// Avoid conflicts if some other filter is adding links or other markup to 
	// the author's display name.
	if ( $value !== strip_tags( $value ) ) {
		return $value;
	}

	$profile_url = bp_core_get_user_domain( $user_id );
	$value = "<a href='$profile_url'>$value</a>";

	return $value;
}
add_filter ( 'get_the_author_display_name', 'hc_add_post_author_profile_link', 10, 3 );