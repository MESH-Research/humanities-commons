<?php
/**
 * Plugin Name:     HC Notifications
 * Plugin URI:      https://github.com/mlaa/hc-notifications.git
 * Description:     HC Notifications
 * Author:          MLA
 * Author URI:      https://github.com/mlaa
 * Text Domain:     hc-notifications
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Hc_Notifications
 */

/**
 * Bootstrap the component.
 */
function hc_notifications_init_component() {
	require_once 'includes/class-hc-notifications-component.php';
	buddypress()->hc_notifications = new HC_Notifications_Component();
}
add_action( 'bp_loaded', 'hc_notifications_init_component' );
