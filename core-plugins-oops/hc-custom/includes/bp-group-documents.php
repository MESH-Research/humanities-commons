<?php
/**
 * Customizations to bp-group-documents plugin.
 *
 * @package Hc_Custom
 * @version 1.0.11272018
 */

/**
 * Remove ability for group admins to change member default notification settings.
 **/
function hc_custom_bp_group_documents_email_notification() {
	remove_action( 'bp_group_documents_add_success', 'bp_group_documents_email_notification' );
}

add_action( 'bp_group_documents_add_success' , 'hc_custom_bp_group_documents_email_notification' , 0 ) ;

/**
 * Prevent notifications for deleting and editing group documents.
 */
function hc_custom_bp_group_documents_stop_doc_emails() {
	remove_action( 'bp_group_documents_edit_success', 'bp_group_documents_record_edit', 15, 1 );
	remove_action( 'bp_group_documents_delete_success', 'bp_group_documents_record_delete', 15, 1 );
}
add_action( 'bp_group_documents_template_do_url_logic', 'hc_custom_bp_group_documents_stop_doc_emails', 10, 0 );
add_action( 'bp_group_documents_template_do_post_action', 'hc_custom_bp_group_documents_stop_doc_emails', 10, 0 );