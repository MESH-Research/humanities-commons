<?php
/*
Plugin Name: BuddyBlock
Description: A BuddyPress plugin. Allows a member to block another member. Admin controls provided under Settings. 
Version: 1.4
Author: PhiloPress
Author URI: http://philopress.com/
License: GPLv2 
Copyright (C) 2013-2015  shanebp, PhiloPress  
*/

// this version does not use ajax due to non-harressment considerations

if ( !defined( 'ABSPATH' ) ) exit;  

define( 'BUDDYBLOCK_VERSION', '1.4' );


function bp_block_member_include() {
    require( dirname( __FILE__ ) . '/bp-block-member.php' );
	load_plugin_textdomain( 'bp-block-member', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );	
	
	if( ! is_admin() ) 
		require( dirname( __FILE__ ) . '/bp-block-member-profile.php' );

}
add_action( 'bp_include', 'bp_block_member_include' );

function bp_block_member_install() {
	global $wpdb;
	
	$table_name = $wpdb->prefix . "bp_block_member"; 

	if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            id MEDIUMINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
            user_id MEDIUMINT UNSIGNED NOT NULL,
            target_id MEDIUMINT UNSIGNED NOT NULL
            );";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
	
	add_option( 'bp_block_roles', 'administrator,super_admin', '', 'yes' );
	$role = get_role( 'administrator' );
	$role->add_cap( 'unblock_member' );
	
}
register_activation_hook( __FILE__, 'bp_block_member_install' );

