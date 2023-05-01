<?php
/**
 * Plugin Name: HC Member Profiles
 * Plugin URI:  https://github.com/mlaa/hc-member-profiles
 * Description: Enhanced BuddyPress XProfile functionality for Humanities Commons
 * Author:      MLA
 * Author URI:  https://github.com/mlaa
 * Text Domain: hc-member-profiles
 * Domain Path: /languages
 *
 * @package Hc_Member_Profiles
 */

require_once trailingslashit( dirname( __FILE__ ) ) . '/includes/functions.php';

/**
 * Bootstrap the component.
 */
function hcmp_init() {
	require_once trailingslashit( dirname( __FILE__ ) ) . 'includes/class-hc-member-profiles-component.php';
	buddypress()->hc_member_profiles = new HC_Member_Profiles_Component();
}
add_action( 'bp_init', 'hcmp_init', 10, 0 );

// Register custom field types.
add_filter( 'bp_xprofile_get_field_types', 'hcmp_register_xprofile_field_types' );
