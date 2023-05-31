<?php
/**
 * Disable large_network features (like removing pagination) on network users admin.
 */
function hcommons_wp_is_large_network( $is_large_network ) {
	if ( function_exists( 'get_current_screen' ) ) {
		$screen = get_current_screen();

		if ( 'users-network' === $screen->id ) {
			$is_large_network = false;
		}
	}

	return $is_large_network;
}
add_filter( 'wp_is_large_network', 'hcommons_wp_is_large_network' );

/**
 * Disable forcing of network admin domain checking.
 */
if ( 0 !== strcasecmp( $current_blog->domain, $current_site->domain ) ) {
            add_filter('redirect_network_admin_request', '__return_false' );
}

/**
 * Disable forcing of network admin email checking.
 */
add_filter( 'admin_email_check_interval', '__return_false' );
