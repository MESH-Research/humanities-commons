<?php
/**
 * Plugin Name:     BP Attachment XProfile Field Type
 * Plugin URI:      https://github.com/mlaa/bp-attachment-xprofile-field-type
 * Description:     Custom XProfile field type for BuddyPress Attachments.
 * Author:          MLA
 * Author URI:      https://github.com/mlaa
 * Text Domain:     bp-attachment-xprofile-field-type
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Bp_Attachment_Xprofile_Field_Type
 */

define( 'BPAXFT_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'BPAXFT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Register the BP Attachment XProfile field type.
 *
 * @param array $field_types Key/value pairs (field type => class name).
 * @return array $field_types
 */
function bpaxft_filter_xprofile_get_field_types( array $field_types ) {
	require_once BPAXFT_PLUGIN_PATH . 'classes/class-bp-attachment-xprofile.php';
	require_once BPAXFT_PLUGIN_PATH . 'classes/class-bp-attachment-xprofile-field-type.php';

	return array_merge(
		$field_types,
		[
			'bp_attachment' => 'BP_Attachment_XProfile_Field_Type',
		]
	);
}
add_filter( 'bp_xprofile_get_field_types', 'bpaxft_filter_xprofile_get_field_types' );

/**
 * Handle file uploads.
 */
function bpaxft_handle_uploaded_file() {
	if ( isset( $_POST['action'] ) && BP_Attachment_XProfile::ACTION === $_POST['action'] ) {
		if ( ! empty( $_FILES[ BP_Attachment_XProfile::FILE_INPUT ]['name'] ) ) {
			$attachment = new BP_Attachment_XProfile();
			$file       = $attachment->upload( $_FILES );

			if ( ! empty( $file['error'] ) ) {
				bp_core_add_message( $file['error'], 'error' );
			} else {
				$existing_file_url = BP_XProfile_ProfileData::get_value_byid(
					$_POST['bpaxft_field_id'],
					get_current_user_id()
				);
			
				// TODO This assumes the profile being edited belongs to the current user.
				// If e.g. an admin is editing another user's profile, this won't work.
				$result = xprofile_set_field_data(
					$_POST['bpaxft_field_id'],
					get_current_user_id(),
					$file['url']
				);

				if ( $result && ! empty( $existing_file_url ) ) {
					$upload_dir_parts = explode( '/', $file['file'] );
					array_pop( $upload_dir_parts );
					$upload_dir = trailingslashit( join( '/', $upload_dir_parts ) );
					$existing_filename = array_pop( explode( '/', $existing_file_url ) );
					$existing_file_path = $upload_dir . $existing_filename;
					wp_delete_file( $existing_file_path );
				}

				// TODO If xprofile_set_field_data() failed, we should handle that here.
			}
		}
		
		if ( isset( $_POST['field_ids'] ) && isset( $_POST['bpaxft_field_id'] ) ) {
			$field_id = $_POST['bpaxft_field_id'];

			// In case visibility changed, handle that first since we're bypassing xprofile_screen_edit_profile().
			xprofile_set_field_visibility_level(
				$field_id,
				bp_displayed_user_id(),
				! empty( $_POST[ 'field_' . $field_id . '_visibility' ] ) ? $_POST[ 'field_' . $field_id . '_visibility' ] : 'public'
			);

			// Prevent xprofile_screen_edit_profile() from deleting this field, since the file input will be empty.
			$_POST['field_ids'] = array_diff( explode( ',', $_POST['field_ids'] ), [ $field_id ] );
		}
	}
}
add_action( 'bp_actions', 'bpaxft_handle_uploaded_file' );
