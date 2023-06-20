<?php
/**
 * Hide Plugin Notices.
 *
 * @package Hc_Custom
 */

/**
 * Removes Mashsb plugin notices from the admin panel.
 **/
function hc_custom_skip_mashsb_notices() {

	update_option( 'mashsb_tracking_notice', '1' );
	remove_action( 'admin_notices', 'mashsb_admin_messages' );
}

add_action( 'init', 'hc_custom_skip_mashsb_notices' );

