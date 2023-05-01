<?php
/**
 * Hooks for BuddyPress
 */

/**
 * Make sure group invitations filter is turned on.
 * 
 * Since BuddyPress 10.x, the Invitations submenu item in a member's groups page only appears
 * if this feature is on, and there's no obvious way to control this from the WordPress admin.
 */
function hc_group_invites_feature( $retval ) {
	return true;
}
add_filter( 'bp_is_groups_invitations_active', 'hc_group_invites_feature', 10, 1 );