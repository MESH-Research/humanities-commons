<?php
/**
 * Customizations to bp-attachment-xprofile plugin
 *
 * @package Hc_Custom
 */

/**
 * Delete profile field for the user's profile and delete the file from the filesystem.
 *
 */
function hc_custom_delete_file() {
	check_ajax_referer( 'settings_general_nonce', 'nonce' );

	$file_to_delete = !empty( $_POST[ 'file_to_delete'] ) ? $_POST['file_to_delete'] : '';
	$field_id_to_delete = !empty( $_POST[ 'field_id_to_delete'] ) ? $_POST['field_id_to_delete'] : '';

	$root_dir = get_home_path();
	$webroot_dir = $root_dir . '/web';

	if( !empty($file_to_delete ) ) {
		xprofile_delete_field_data( $field_id_to_delete, get_current_user_id() );
		wp_delete_file( dirname(__FILE__, 5) . $file_to_delete );
	}

 	die();
}
add_action( 'wp_ajax_hc_custom_delete_file', 'hc_custom_delete_file' );
add_action( 'wp_ajax_nopriv_hc_custom_delete_file', 'hc_custom_delete_file' );
