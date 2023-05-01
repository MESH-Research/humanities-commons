<?php

if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
    exit();
	
global $wpdb;
$sql = "DROP TABLE {$wpdb->prefix}bp_block_member";	
$e = $wpdb->query($sql); //die(var_dump($e));

/*
$current_allowed_roles = explode(",", get_option( 'bp_block_roles' ));
				
foreach( $current_allowed_roles as $key => $value ){
	if( 'administrator' != $value ) { 
		$role = get_role( $value );
		$role->remove_cap( 'unblock_member' );
	}
}
*/
delete_option( 'bp_block_roles' );

?>