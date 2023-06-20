<?php
/**
 * Filters altering the frontend appearance of The Commons.
 */

/**
 * Adds a link to an author's profile when displaying their name on the
 * frontend.
 *
 * This is intended to alter the display of an author's name in the post_author
 * block. 
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

	// Yuck. But this seems to be the only way to check that we are in the post author block.
	$calling_functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
	$in_post_author_block = false;
	foreach ( $calling_functions as $caller ) {
		if ( array_key_exists( 'function', $caller ) && $caller['function'] === 'render_block_core_post_author' ) {
			$in_post_author_block = true;
			break;
		}
	}

	// We only want this to apply to the post author block, not other uses of
	// author_display_name. In those cases, should filter on author_link instead.
	if ( ! $in_post_author_block ) {
		return $value;
	}

	$profile_url = bp_core_get_user_domain( $user_id );
	$value = "<a href='$profile_url'>$value</a>";

	return $value;
}
add_filter ( 'get_the_author_display_name', 'hc_add_post_author_profile_link', 10, 3 );

/**
 * Replaces link to author's posts on user sites with link to author's profile page,
 * which also includes their posts. This makes legacy themes match the behavior of
 * the post author block as filtered by hc_add_post_author_profile_link.
 *
 * @see https://github.com/MESH-Research/commons/issues/474
 * @see https://developer.wordpress.org/reference/functions/get_author_posts_url/ 
 * 
 * @param string $link            The current link
 * @param int    $author_id       The WPID of the author
 * @param string $author_nicename The author's display name (unused)
 */
function hc_filter_author_link( $link, $author_id, $author_nicename ) {
	if ( ! $author_id ) {
		return $link;
	}
	
	$profile_url = bp_core_get_user_domain( $author_id );
	if ( $profile_url ) {
		return $profile_url;
	}

	return $link;
}
add_filter( 'author_link', 'hc_filter_author_link', 10, 3 );

/**
 * Add Google Tag to header site-wide.
 * 
 * @see https://github.com/MESH-Research/commons/issues/550
 */
function hc_add_google_analytics_tag() {
	?>
	<!-- Google tag (gtag.js) (site-wide) -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-G8B8HY7PBC"></script>
	<script>
	  window.dataLayer = window.dataLayer || [];
	  function gtag(){dataLayer.push(arguments);}
	  gtag('js', new Date());

	  gtag('config', 'G-G8B8HY7PBC');
	</script>
	<?php
}
add_action( 'wp_head', 'hc_add_google_analytics_tag', 10, 0 );