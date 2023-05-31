<?php

/**
 * Comments are not nested correctly if caching is on, so disable it for commentpress.
 */
function hcommons_selectively_disable_object_cache() {
	$theme = wp_get_theme();
	if ( false !== strpos( strtolower( $theme->get( 'Name' ) ), 'commentpress' ) ) {
		wp_cache_add_non_persistent_groups( array( 'comment' ) );
	}
}
add_action( 'plugins_loaded', 'hcommons_selectively_disable_object_cache' );
