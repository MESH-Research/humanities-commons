<?php
/**
 * Functions for user administration.
 */

/**
 * Spams additional content when a user is set to spam.
 *
 * Trashes all post types authored by the user, and spams any sites where user
 * was the sole user of that site.
 *
 * @author Mike Thicke
 *
 * @param int $user_id WordPress UID
 */
function hc_spam_user_content( $user_id ) {
	$user_posts = get_posts(
		[
			'numberposts' => -1,
			'author'      => $user_id,
			'post_type'   => 'any',
			'post_status' => 'any',
		]
	);

	foreach ( $user_posts as $spam_post ) {
		wp_trash_post( $spam_post->ID );
	}

	$user_sites = get_blogs_of_user( $user_id );
	foreach ( $user_sites as $spam_site ) {
		$users = get_users(
			[
				'blog_id' => $spam_site->userblog_id,
			]
		);
		if ( count( $users ) === 1 && $users[0]->ID === $user_id ) {
			update_blog_status( $spam_site->userblog_id, 'spam', '1' );
		}
	}
}
add_action( 'make_spam_user', 'hc_spam_user_content', 10, 1 );


/**
 * Recovers content (where possible) of user who is unspammed.
 *
 * @author Mike Thicke
 */
function hc_ham_user_content( $user_id ) {
	$user_posts = get_posts(
		[
			'numberposts' => -1,
			'author'      => $user_id,
			'post_type'   => 'any',
			'post_status' => 'trash',
		]
	);

	foreach ( $user_posts as $spam_post ) {
		wp_untrash_post( $spam_post->ID );
	}

	$user_sites = get_blogs_of_user( $user_id, true );
	foreach ( $user_sites as $spam_site ) {
		$users = get_users(
			[
				'blog_id' => $spam_site->userblog_id,
			]
		);
		if ( count( $users ) === 1 && $users[0]->ID === $user_id ) {
			update_blog_status( $spam_site->userblog_id, 'spam', '0' );
		}
	}
}
add_action( 'make_ham_user', 'hc_ham_user_content', 10, 1 );

/**
 * Makes it so that restored posts are restored to their previous post status
 * when untrashed.
 *
 * This overrides the default behavior, which changed in WP 5.6 to return
 * trashed posts in 'draft' status.
 * @see https://developer.wordpress.org/reference/functions/wp_untrash_post/#changelog
 *
 * @author Mike Thicke
 *
 * @param string $new_status      The new status of the post being restored.
 * @param int    $post_id         The ID of the post being restored.
 * @param string $previous_status The status of the post at the point where it
 *                                was trashed.
 */
function hc_filter_untrash_status( $new_status, $post_id, $previous_status ) {
	return $previous_status;
}
add_filter( 'wp_untrash_post_status', 'hc_filter_untrash_status', 10, 3 );