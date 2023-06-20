<?php
/**
 * Customizations to BuddyPress Members.
 *
 * @package Hc_Custom
 */

/**
 * Disable follow button for non-society-members.
 */
function hcommons_add_non_society_member_follow_button() {
	if ( ! is_super_admin() && ! Humanities_Commons::hcommons_user_in_current_society() ) {
		echo '<div class="disabled-button">Follow</div>';
	}
}
add_action( 'bp_directory_members_actions', 'hcommons_add_non_society_member_follow_button' );

/**
 * Add follow disclaimer for non-society-members.
 */
function hcommons_add_non_society_member_disclaimer_member() {
	if ( ! is_super_admin() && ! Humanities_Commons::hcommons_user_in_current_society() ) {
		printf(
			'<div class="non-member-disclaimer">Only %s members can follow others from here.<br>To follow these members, go to <a href="%s">Humanities Commons</a>.</div>',
			strtoupper( Humanities_Commons::$society_id ),
			get_site_url( getenv( 'HC_ROOT_BLOG_ID' ) )
		);
	}
}
add_action( 'bp_before_directory_members_content', 'hcommons_add_non_society_member_disclaimer_member' );

/**
 * Add info to the members loop
 */
function hcommons_add_info_to_members_loop() {
	$field_content = bp_get_member_profile_data( array( 'field' => 'Institutional or Other Affiliation' ) );
	if( $field_content != false ) {
		echo $field_content;
	}
}
add_action( 'bp_directory_members_item', 'hcommons_add_info_to_members_loop' );
