<?php
/**
 * Customizations to wp-to-twitter.
 *
 * @package Hc_Custom
 */

/**
 * Remove the twitter.com platform js.
 *
 * We don't need it and we don't want unnecessary external clientside requests.
 */
function hc_custom_dequeue_twitter_platform() {
	wp_dequeue_script( 'twitter-platform', 'https://platform.twitter.com/widgets.js' );
}
add_action( 'dynamic_sidebar_after', 'hc_custom_dequeue_twitter_platform' );

function hc_custom_society_to_twitter( $society_id ) {
    switch ($society_id) {
        case "up":
 		return "https://twitter.com/aupresses";
		break;
        
        default:
        	return "https://twitter.com/humcommons";
    }
}    
	

add_filter('wpt_cache_expire', function( $value ) {
	return 180;
 }
);
