<?php
/**
 * Allow "unsafe URLs" (internal DNS resolution) for domains in dev.
 */

if ( defined( WP_ENV ) && WP_ENV !== 'production' ) {
	add_filter( 'http_request_args', function( $args ) {
		$args['reject_unsafe_urls'] = false;
		return $args;
	} );
}
