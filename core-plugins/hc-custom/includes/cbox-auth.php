<?php
/**
 * Customizations to cbox-auth plugin
 *
 * @package Hc_Custom
 */

/**
 * Get object property or return default value.
 *
 * @param object $obj      Object.
 * @param string $property Property name.
 * @param mixed  $default  Default value.
 * @return mixed Property value or default value.
 */
function hc_custom_get_object_property( $obj, $property, $default ) {
	return ( property_exists( $obj, $property ) ) ? $obj->$property : $default;
}

/**
 * Get group type.
 */
function hc_custom_get_group_type() {
	global $groups_template;

	if ( ! $groups_template || ! property_exists( $groups_template, 'group' ) ) {
		return '';
	}

	$group_type = '';

	$group_type = strtolower( \groups_get_groupmeta( $groups_template->group->id, 'society_group_type', true ) );

	if ( mla_is_group_committee( $groups_template->group->id ) ) {
		$group_type = 'committee';
	}

	if ( ! $group_type ) {
		$group_type = strtolower( $groups_template->group->status );
	}

	return $group_type;
}

/**
 * Hide the join/leave button for committees and forums for which the
 * user is not an admin.
 *
 * @param BP_Groups_Group $group BuddyPress group.
 */
function hc_custom_hide_join_button( $group ) {
	$user_id    = \bp_loggedin_user_id();
	$group_type = hc_custom_get_group_type();

	// Remove the other actions that would create this button.
	$actions = array(
		'bp_group_header_actions'     => 'bp_group_join_button',
		'bp_directory_groups_actions' => 'bp_group_join_button',
	);
	foreach ( $actions as $name => $action ) {
		\remove_action( $name, $action, \has_action( $name, $action ) );
	}

	$is_committee   = ( 'committee' === $group_type );
	$is_forum_admin = ( 'forum' === $group_type && \groups_is_user_admin( $user_id, \bp_get_group_id() ) );

	if ( $is_committee || $is_forum_admin ) {
		return;
	}

	// If a user is a non-society member, then the join button logic is handled
	// by bp-groups.php::hcommons_add_non_society_member_join_group_button
	if ( ! is_super_admin() && ! Humanities_Commons::hcommons_user_in_current_society() ) {
		return;
	}

	if ( class_exists( 'Humanities_Commons' ) && 'mla' !== Humanities_Commons::$society_id ) {
		return \bp_group_join_button( $group );
	}
}

add_action( 'bp_group_header_actions', 'hc_custom_hide_join_button', 1 );
add_action( 'bp_directory_groups_actions', 'hc_custom_hide_join_button', 1 );

/**
 * Hide the request membership tab for committees.
 *
 * @param string $string Unchanged filter string.
 */
function hc_custom_hide_request_membership_tab( $string ) {
	return ( 'committee' === hc_custom_get_group_type() ) ? null : $string;
}

add_filter( 'bp_get_options_nav_request-membership', 'hc_custom_hide_request_membership_tab' );

