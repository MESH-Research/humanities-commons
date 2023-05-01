<?php
/**
 * Customizations to BuddyPress Groups.
 *
 * @package Hc_Custom
 */

/**
 * Adds new button labels for joining groups.
 *
 * Society membership is not checked by this function.
 * hcommons_filter_groups_button_lables_for_non_member checks for society
 * membership and replaces the button with an inactive pseudo button for
 * non-society members.
 *
 * @param string $button HTML button for joining a group.
 * @param object $group BuddyPress group object.
 *
 * @return mixed
 */
function hcommons_filter_groups_button_labels( $button, $group ) {
	$status = $group->status;
	$group_auto_accept = groups_get_groupmeta( $group->id, 'auto_accept' );

	if ( empty( BP_Groups_Member::check_is_member( get_current_user_id(), $group->id ) ) ) {
		switch ( $status ) {
			case 'public':
				$button['link_text'] = 'Join Group';
				break;

			case 'private':
				if ( ! empty( $group_auto_accept ) && 'is_true' == $group_auto_accept ) {
					$button['link_text'] = 'Join Group';
					$button['link_href'] = wp_nonce_url( trailingslashit( bp_get_group_permalink( $group ) . 'join' ), 'groups_join_group' );
				} else {
					$button['link_text'] = 'Request Membership';
				}
				break;
		}
	}

	return $button;
}
add_filter( 'bp_get_group_join_button', 'hcommons_filter_groups_button_labels', 10, 2 );

/**
 * Replaces Join Group button with inactive pseudo button for non-society
 * members.
 * 
 * @author Mike Thicke
 *
 * @param string    $contents The HTML of the button.
 * @param Array     $args     Button arguments (unused)
 * @param BP_Button $button   The button object
 *
 * @return string Original contents if not a group button or a society member,
 *                or disabled pseudo button if a non-member.
 */
function hcommons_filter_groups_button_lables_for_non_member( $contents, $args, $button ) {
	global $groups_template;

	// Only filter Join Group buttons
	if ( $button->id !== 'join_group' ) { 
		return $contents;
	}

	// Don't filter for super admins or society members
	if ( is_super_admin() || Humanities_Commons::hcommons_user_in_current_society() ) {
		return $contents;
	}

	// If the group is a committee, there should be no join button.
	if ( hc_custom_get_group_type() !== 'committee' ) {
		if ( 'private' === $groups_template->group->status ) {
			$message = 'Request Membership';
		} else {
			$message = 'Join Group';
		}
		$contents = '<div class="disabled-button">' . $message . '</div>';
	} else {
		$contents = '';
	}

	return $contents;
}
add_filter( 'bp_get_button', 'hcommons_filter_groups_button_lables_for_non_member', 10, 3 );

/**
 * Filters the action for the new group activity update.
 *
 * @param string $activity_action The new group activity update.
 */
function hcommons_filter_groups_activity_new_update_action( $activity_action ) {
	$activity_action = preg_replace( '/(in the group <a href="[^"]*)(">)/', '\1activity\2', $activity_action );
	return $activity_action;
}
add_filter( 'groups_activity_new_update_action', 'hcommons_filter_groups_activity_new_update_action' );

/**
 * Adds disclaimer notice for non society members
 */
function hcommons_add_non_society_member_disclaimer_group() {
	if ( ! is_super_admin() && ! Humanities_Commons::hcommons_user_in_current_society() ) {
		printf(
			'<div class="non-member-disclaimer">Only %1$s members can join these groups.<br><a href="/register">Join %1$s now</a>!</div>',
			strtoupper( Humanities_Commons::$society_id )
		);
	}
}
add_action( 'bp_before_directory_groups_content', 'hcommons_add_non_society_member_disclaimer_group' );

/**
 * On the current group page, reconfigure the group nav when a forum is
 * enabled for the group.
 *
 * What we do here is:
 *  - move the 'Forum' tab to the beginning of the nav
 *  - rename the 'Home' tab to 'Activity'
 */
function hcommons_override_config_group_nav() {
	$group_slug = bp_current_item();
	$group_id = bp_get_current_group_id();

	$has_frontpage = ! empty( groups_get_groupmeta( $group_id, 'group_has_frontpage' ) ) ? groups_get_groupmeta( $group_id, 'group_has_frontpage' )  : false;

	// BP 2.6+.
	if ( function_exists( 'bp_rest_api_init' ) ) {
			buddypress()->groups->nav->edit_nav( array( 'position' => 1 ), 'forum', $group_slug );
			buddypress()->groups->nav->edit_nav( array( 'position' => 0 ), 'home', $group_slug );

		      if ( $has_frontpage ) {
                           buddypress()->groups->nav->edit_nav( array( 'name' => __( 'Home', 'buddypress' ) ), 'home', $group_slug );
		      } else {
			   buddypress()->groups->nav->edit_nav( array( 'name' => __( 'Activity', 'buddypress' ) ), 'home', $group_slug );
		      }
		// Older versions of BP.
	} else {
			buddypress()->bp_options_nav[ $group_slug ]['home']['position']  = 0;
			buddypress()->bp_options_nav[ $group_slug ]['forum']['position'] = 1;
			buddypress()->bp_options_nav[ $group_slug ]['home']['name']      = __( 'Activity', 'buddypress' );
	}
}

/**
 * Set the group default tab to 'forum' if the current group has a forum
 * attached to it.
 *
 * @param string $retval Navigation slug.
 */
function hcommons_override_cbox_set_group_default_tab( $retval ) {
	$group_id = bp_get_current_group_id();

	// If there is a landing page set, use it instead.
	$retval = ! empty( groups_get_groupmeta( $group_id, 'group_landing_page' ) ) ? groups_get_groupmeta( $group_id, 'group_landing_page' ) : $retval;

	// Check if bbPress or legacy forums are active and configured properly.
	if ( ( function_exists( 'bbp_is_group_forums_active' ) && bbp_is_group_forums_active() ) ||
				( function_exists( 'bp_forums_is_installed_correctly' ) && bp_forums_is_installed_correctly() ) ) {

		// If current group does not have a forum attached, stop now!
		if ( ! bp_group_is_forum_enabled( groups_get_current_group() ) ) {
					return $retval;
		}

			// Allow non-logged-in users to view a private group's homepage.
		if ( false === is_user_logged_in() && groups_get_current_group() && 'private' === bp_get_new_group_status() ) {
				return $retval;
		}

			// Reconfigure the group's nav.
			add_action( 'bp_actions', 'hcommons_override_config_group_nav', 99 );

			// Finally, use 'forum' as the default group tab.
			return ! empty( groups_get_groupmeta( $group_id, 'group_landing_page' ) ) ? groups_get_groupmeta( $group_id, 'group_landing_page' ) : 'home';
	}

		return $retval;
}
add_filter( 'bp_groups_default_extension', 'hcommons_override_cbox_set_group_default_tab', 100 );

/**
 * BuddyPress Groups Forbidden Group Slugs
 * Used for groups that have been redirected or slugs that we want to reserve.
 *
 * @param array $forbidden_names List of forbidden group slugs.
 */
function mla_bp_groups_forbidden_names( $forbidden_names ) {

	$mla_forbidden_group_slugs = array(
		'style',
	);

	return array_merge( $forbidden_names, $mla_forbidden_group_slugs );

}
add_filter( 'groups_forbidden_names', 'mla_bp_groups_forbidden_names', 10, 1 );

/**
 * Set forums' status to match the privacy status of the associated group.
 *
 * Fired whenever a group is saved.
 *
 * @param BP_Groups_Group $group Group object.
 */
function update_group_forum_visibility( BP_Groups_Group $group ) {
	global $wpdb;

	$bp = buddypress();

	// Get group forum IDs.
	$forum_ids = bbp_get_group_forum_ids( $group->id );

	// Bail if no forum IDs available.
	if ( empty( $forum_ids ) ) {
			return;
	}

	// Loop through forum IDs.
	foreach ( $forum_ids as $forum_id ) {

		// Get forum from ID.
		$forum = bbp_get_forum( $forum_id );

		// Check for change.
		if ( $group->status !== $forum->post_status ) {
			switch ( $group->status ) {

				// Changed to hidden.
				case 'hidden':
						bbp_hide_forum( $forum_id, $forum->post_status );
					break;

				// Changed to private.
				case 'private':
						bbp_privatize_forum( $forum_id, $forum->post_status );
					break;

				// Changed to public.
				case 'public':
				default:
						bbp_publicize_forum( $forum_id, $forum->post_status );
					break;
			}
		}
	}

	// Update activity table.
	switch ( $group->status ) {
		// Changed to hidden.
		case 'hidden':
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE %s SET hide_sitewide = 1 WHERE item_id = %d AND component = 'groups'",
					$bp->activity->table_name,
					$group->id
				)
			);
			break;

		// Changed to private.
		case 'private':
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE %s SET hide_sitewide = 1 WHERE item_id = %d AND component = 'groups'",
					$bp->activity->table_name,
					$group->id
				)
			);
			break;

		// Changed to public.
		case 'public':
		default:
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE %s SET hide_sitewide = 0 WHERE item_id = %d AND component = 'groups'",
					$bp->activity->table_name,
					$group->id
				)
			);
			break;
	}
}
add_action( 'groups_group_after_save', 'update_group_forum_visibility' );

/**
 * Set forums' status to match the privacy status of the associated group.
 *
 * @param string $parent_slug The group slug.
 */
function hc_custom_get_options_nav( $parent_slug = '' ) {
	global $bp;

	?>
	<h4><?php _e( 'Show or Hide Menu Items for Members', 'group_forum_menu' ); ?></h4>
	<table class="group-nav-settings" >
		<thead>
			<tr>
				<th class="icon"></th>
				<th class="title"></th>
				<th class="show gas-choice"><?php _e( 'Show', 'buddypress' ); ?></th>
				<th class="hide gas-choice"><?php _e( 'Hide', 'buddypress' ); ?></th>
			</tr>
	</thead>
	<tbody>

	<?php

	$group_id              = bp_get_group_id();
	$current_item          = bp_current_item();
	$single_item_component = bp_current_component();

	// Adjust the selected nav item for the current single item if needed.
	if ( ! empty( $parent_slug ) ) {
		$current_item  = $parent_slug;
		$selected_item = bp_action_variable( 0 );
	}

	// If the nav is not defined by the parent component, look in the Members nav.
	if ( ! isset( $bp->{$single_item_component}->nav ) ) {
		$secondary_nav_items = $bp->members->nav->get_secondary( array( 'parent_slug' => $current_item ) );
	} else {
		$secondary_nav_items = $bp->{$single_item_component}->nav->get_secondary( array( 'parent_slug' => $current_item ) );
	}

	if ( ! $secondary_nav_items ) {
		return false;
	}

	foreach ( $secondary_nav_items as $subnav_item ) :
		// List type depends on our current component.
		$list_type = bp_is_group() ? 'groups' : 'personal';
		

		if ( 'groups_screen_group_admin' === $subnav_item->screen_function || 'members' === $subnav_item->slug || 'invite-anyone' == $subnav_item->slug || 'notifications' === $subnav_item->slug ) {
                        continue;

		}

		$current_status = ! empty( groups_get_groupmeta( $group_id, $subnav_item->slug ) ) ? groups_get_groupmeta( $group_id, $subnav_item->slug ) : '';
		?>

		<tr>
			<td></td>
			<td>
				<a href="<?php echo esc_url( $subnav_item->link ); ?>"> <?php echo $subnav_item->name; ?> </a>
			</td>

			<td class="show gas-choice">
				<input type="radio" data-slug="<?php echo $subnav_item->slug; ?>" id="hide-or-show-menu" name="group-nav-settings[<?php echo $subnav_item->slug; ?>]" value="show"
					<?php
					if ( 'show' == $current_status || ! $current_status ) {
						?>
						checked="checked" <?php } ?>/>
			</td>

			<td class="hide gas-choice">
				<input type="radio" data-slug="<?php echo $subnav_item->slug; ?>" id="hide-or-show-menu" name="group-nav-settings[<?php echo $subnav_item->slug; ?>]" value="hide"
					<?php
					if ( 'hide' == $current_status ) {
						?>
						checked="checked" <?php } ?>/>
			</td>
		</tr>
		<?php endforeach; ?>

		</tbody>
	</table>

	<?php
}

/**
 * Save group nav settings.
 *
 * @param int $group_id The group id.
 */
function hc_custom_groups_nav_settings( $group_id ) {

	$group_nav_settings = isset( $_POST['group-nav-settings'] ) ? $_POST['group-nav-settings'] : '';
	$group_landing_page = isset( $_POST['group-landing-page-select'] ) ? $_POST['group-landing-page-select'] : '';
	$group              = groups_get_group( array( 'group_id' => $group_id ) );
	$group_slug         = $group->slug;

	if ( ! empty( $group_nav_settings ) ) {

		foreach ( $_POST['group-nav-settings'] as $menu_item => $value ) {
			groups_update_groupmeta( $group_id, $menu_item, $value );
		}
	}

	if ( ! empty( $group_landing_page ) ) {
		groups_update_groupmeta( $group_id, 'group_landing_page', $group_landing_page );
	}

}
add_action( 'groups_settings_updated', 'hc_custom_groups_nav_settings' );

/**
 * Remove tabs based on group settings.
 */
function hc_custom_remove_group_manager_subnav_tabs() {
	global $bp;

	// Site admins will see all tabs.
	if ( ! bp_is_group() || is_super_admin() ) {
		return;
	}

	$group_id = bp_get_current_group_id();

	// Group admins will see all tabs.
	if ( ! $group_id || groups_is_user_admin( get_current_user_id(), $group_id ) ) {
		return;
	}

	$parent_nav_slug     = bp_get_current_group_slug();
	$secondary_nav_items = $bp->groups->nav->get_secondary( array( 'parent_slug' => $parent_nav_slug ) );

	$selected_item = null;

	// Remove the nav items. Not stored, just unsets it.
	foreach ( $secondary_nav_items as $subnav_item ) {
		if ( 'hide' === groups_get_groupmeta( $group_id, $subnav_item->slug ) ) {

			bp_core_remove_subnav_item( $parent_nav_slug, $subnav_item->slug, 'groups' );
		}
	}
}
add_action( 'bp_actions', 'hc_custom_remove_group_manager_subnav_tabs' );


/**
 * Allow group admin to change the default landing page.
 */
function hc_custom_choose_landing_page() {
	global $bp;

	$group_id = ! empty( $_POST['group_id'] ) && isset( $_POST['group_id'] ) ? $_POST['group_id'] : '';

	if ( empty( $group_id ) ) {
		die( '-1' );
	}

	$parent_nav_slug = ! empty( $_POST['group_slug'] ) && isset( $_POST['group_slug'] ) ? $_POST['group_slug'] : '';

	$selected            = groups_get_groupmeta( $group_id, 'group_landing_page' );
	$secondary_nav_items = $bp->groups->nav->get_secondary( array( 'parent_slug' => $parent_nav_slug ) );
	
	$has_frontpage = ! empty( groups_get_groupmeta( $group_id, 'group_has_frontpage' ) ) ? groups_get_groupmeta( $group_id, 'group_has_frontpage' )  : false;
	
	$html = '';

	if ( isset( $_POST['menu_option_value'] ) && ! empty( $_POST['menu_option_value'] ) ) {

		if ( isset( $_POST['menu_option_slug'] ) && ! empty( $_POST['menu_option_slug'] ) ) {
			$menu_item = $_POST['menu_option_slug'];
			$value     = $_POST['menu_option_value'];

			groups_update_groupmeta( $group_id, $menu_item, $value );
		}
	}

	foreach ( $secondary_nav_items as $subnav_item ) {

		$name = preg_replace( '/\d/', '', $subnav_item->name );

		if ( 'Home' === $name ) {
			if( !$has_frontpage ) {
			    $name = 'Activity';
			}
		}

		if ( 'hide' === groups_get_groupmeta( $group_id, $subnav_item->slug ) ) {
			continue;
		}

		if ( 'invite-anyone' == $subnav_item->slug ) {
			continue;
		}

		if ( 'groups_screen_group_admin' === $subnav_item->screen_function ) {
			continue;
		}

		if ( isset( $_POST['menu_option_value'] ) && ! empty( $_POST['menu_option_value'] ) ) {

			if ( isset( $_POST['menu_option_slug'] ) && ! empty( $_POST['menu_option_slug'] ) ) {

				if ( $subnav_item->slug === $_POST['menu_option_slug'] && 'hide' === $_POST['menu_option_value'] ) {
					continue;
				}
			}
		}

		$html .= '<option value="' . esc_attr( $subnav_item->slug ) . '"' . selected( $subnav_item->slug, $selected ) . '>' . $name . '</option>';

	}
	echo $html;

	die();

}

add_action( 'wp_ajax_nopriv_generate_menu_options_dropdown', 'hc_custom_choose_landing_page' );
add_action( 'wp_ajax_generate_menu_options_dropdown', 'hc_custom_choose_landing_page' );


/**
 * Grey out group navs if they are hidden.
 *
 * @param string $string Unchanged filter string.
 * @param array  $subnav_item Array of nav item.
 * @param string $selected_item Currently selected nav item.
 */
function hc_custom_modify_nav( $string, $subnav_item, $selected_item ) {
	global $bp;

	// Site admins will see all tabs.
	if ( ! bp_is_group() ) {
		return $string;
	}

	$group_id = bp_get_current_group_id();

	// Group admins will see all tabs.
	if ( ! $group_id && ( ! groups_is_user_admin( get_current_user_id(), $group_id ) || ! is_super_admin() ) ) {

		return $string;
	}

	if ( 'hide' === groups_get_groupmeta( $group_id, $subnav_item->slug ) ) {
		$string = '<li class="disabled-group-nav" id="' . esc_attr( $subnav_item->css_id . '-groups-li' ) . '" ' . $selected_item . '><span class="disabled-nav"> ' . $subnav_item->name . '</span></li>';
	}

	return $string;
}

/**
 * Fiter for home nav.
 *
 * @param string $string Unchanged filter string.
 * @param array  $subnav_item Array of nav item.
 * @param string $selected_item Currently selected nav item.
 */
function hc_custom_modify_home_nav( $string, $subnav_item, $selected_item ) {
	return hc_custom_modify_nav( $string, $subnav_item, $selected_item );
}

add_filter( 'bp_get_options_nav_home', 'hc_custom_modify_home_nav', 10, 3 );

/**
 * Fiter for home nav.
 *
 * @param string $string Unchanged filter string.
 * @param array  $subnav_item Array of nav item.
 * @param string $selected_item Currently selected nav item.
 */
function hc_custom_modify_forum_nav( $string, $subnav_item, $selected_item ) {
	return hc_custom_modify_nav( $string, $subnav_item, $selected_item );
}

add_filter( 'bp_get_options_nav_nav-forum', 'hc_custom_modify_forum_nav', 10, 3 );

/**
 * Fiter for home nav.
 *
 * @param string $string Unchanged filter string.
 * @param array  $subnav_item Array of nav item.
 * @param string $selected_item Currently selected nav item.
 */
function hc_custom_modify_events_nav( $string, $subnav_item, $selected_item ) {
	return hc_custom_modify_nav( $string, $subnav_item, $selected_item );
}

add_filter( 'bp_get_options_nav_nav-events', 'hc_custom_modify_events_nav', 10, 3 );

/**
 * Fiter for Core deposits nav.
 *
 * @param string $string Unchanged filter string.
 * @param array  $subnav_item Array of nav item.
 * @param string $selected_item Currently selected nav item.
 */
function hc_custom_modify_deposits_nav( $string, $subnav_item, $selected_item ) {
	return hc_custom_modify_nav( $string, $subnav_item, $selected_item );
}

add_filter( 'bp_get_options_nav_deposits', 'hc_custom_modify_deposits_nav', 10, 3 );

/**
 * Fiter for docs nav.
 *
 * @param string $string Unchanged filter string.
 * @param array  $subnav_item Array of nav item.
 * @param string $selected_item Currently selected nav item.
 */
function hc_custom_modify_docs_nav( $string, $subnav_item, $selected_item ) {
	return hc_custom_modify_nav( $string, $subnav_item, $selected_item );
}

add_filter( 'bp_get_options_nav_nav-docs', 'hc_custom_modify_docs_nav', 10, 3 );

/**
 * Fiter for documents nav.
 *
 * @param string $string Unchanged filter string.
 * @param array  $subnav_item Array of nav item.
 * @param string $selected_item Currently selected nav item.
 */
function hc_custom_modify_documents_nav( $string, $subnav_item, $selected_item ) {
	return hc_custom_modify_nav( $string, $subnav_item, $selected_item );
}

add_filter( 'bp_get_options_nav_nav-documents', 'hc_custom_modify_documents_nav', 10, 3 );

/**
 * Fiter for blog nav.
 *
 * @param string $string Unchanged filter string.
 * @param array  $subnav_item Array of nav item.
 * @param string $selected_item Currently selected nav item.
 */
function hc_custom_modify_blog_nav( $string, $subnav_item, $selected_item ) {
	return hc_custom_modify_nav( $string, $subnav_item, $selected_item );
}

add_filter( 'bp_get_options_nav_nav-group-blog', 'hc_custom_modify_blog_nav', 10, 3 );

/**
 * Meta box html for private group options
 */
function hc_add_auto_accept_option_to_group_settings_page() {
	$group_id          = filter_var( $_GET['gid'], FILTER_VALIDATE_INT );
	$group_auto_accept = groups_get_groupmeta( $group_id, 'auto_accept' ) ?: 'is_false';

	?>
		<label>
			<input type="radio" name="group-auto-accept" value="is_true" <?php checked( $group_auto_accept, 'is_true', true ); ?>/>
			<strong>Auto accept all membership request.</strong>
		</label>
		<br/>
		<label>
			<input type="radio" name="group-auto-accept" value="is_false" <?php checked( $group_auto_accept, 'is_false', true ); ?>/>
			<strong>Manually review all membership request.</strong>
		</label>

	<?php
}

/**
 * Add admin page option for auto accept on a private group
 */
function hc_add_auto_accept_option_to_group_settings_page_meta_box() {
	if ( 'bp-groups' == is_admin() && $_GET['page'] ) {

		$group_id = filter_var( $_GET['gid'], FILTER_VALIDATE_INT );
		$group    = groups_get_group( $group_id );

		if ( 'private' === $group->status ) {
			// Only show this meta box on private groups.
			add_meta_box(
				'hc_auto_accept_option',
				_x( 'Private Group Options', 'Private Group Options', 'hc_private_group_options' ),
				'hc_add_auto_accept_option_to_group_settings_page',
				get_current_screen()->id,
				'side',
				'core'
			);
		}
	}
}
add_action( 'bp_groups_admin_meta_boxes', 'hc_add_auto_accept_option_to_group_settings_page_meta_box' );

/**
 * Save the auto-accept metadata.
 *
 * @param string $action Current $_GET action being performed in admin screen.
 */
function hc_save_auto_accept_settings( $action ) {
	// displays what action we are in.
	$bp       = buddypress();
	$group_id = filter_var( $_GET['gid'], FILTER_VALIDATE_INT );

	// lets check if the request method and action are on post and save.
	if ( 'save' == $action ) {
		$group_auto_accept = ! empty( $_POST['group-auto-accept'] ) ? $_POST['group-auto-accept'] : 'is_false';

		groups_update_groupmeta( $group_id, 'auto_accept', $group_auto_accept );
	}
}

add_action( 'bp_groups_admin_load', 'hc_save_auto_accept_settings' );

/**
 * Join or leave a group when clicking the "join/leave" button via a POST request.
 *
 * @return string HTML
 */
function hc_custom_bp_legacy_theme_ajax_joinleave_group() {
	// Bail if not a POST action.
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
		error_log( 'Request Method failed' );
		return;
	}

	// Cast gid as integer.
	$group_id = (int) $_POST['gid'];

	if ( groups_is_user_banned( bp_loggedin_user_id(), $group_id ) ) {
		return;
	}

	$group = groups_get_group( $group_id );

	if ( ! $group ) {
		return;
	}

	$user              = bp_loggedin_user_id();
	$group_auto_accept = groups_get_groupmeta( $group->id, 'auto_accept' );

	$society_id  = bp_groups_get_group_type( $group->id, true );
	$member_types = bp_get_member_type( $user, false );

	// If the user isn't a member of the society, they can't join a group in that society.
	if ( ! Humanities_Commons::hcommons_user_in_current_society() ) {
		wp_die();
	}

	if ( ! groups_is_user_member( bp_loggedin_user_id(), $group->id ) ) {
		if ( 'public' == $group->status ) {
			check_ajax_referer( 'groups_join_group' );

			if ( ! groups_join_group( $group->id ) ) {
				_e( 'Error joining group', 'buddypress' );
			} else {
				echo '<a id="group-' . esc_attr( $group->id ) . '" class="group-button leave-group" rel="leave" href="' . wp_nonce_url( bp_get_group_permalink( $group ) . 'leave-group', 'groups_leave_group' ) . '">' . __( 'Leave Group', 'buddypress' ) . '</a>';
			}
		} elseif ( 'private' == $group->status ) {

			if ( ! empty( $group_auto_accept ) && 'is_true' == $group_auto_accept && in_array( $society_id, $member_types ) ) {
				if ( ! groups_join_group( $group->id ) ) {
					_e( 'Error joining group', 'buddypress' );
				} else {
					echo '<a id="group-' . esc_attr( $group->id ) . '" class="group-button leave-group" rel="leave" href="' . wp_nonce_url( bp_get_group_permalink( $group ) . 'leave-group', 'groups_leave_group' ) . '">' . __( 'Leave Group', 'buddypress' ) . '</a>';
				}

				exit;
			}

			// If the user has already been invited, then this is
			// an Accept Invitation button.
			if ( groups_check_user_has_invite( bp_loggedin_user_id(), $group->id ) ) {
				check_ajax_referer( 'groups_accept_invite' );

				if ( ! groups_accept_invite( bp_loggedin_user_id(), $group->id ) ) {
					_e( 'Error requesting membership', 'buddypress' );
				} else {
					echo '<a id="group-' . esc_attr( $group->id ) . '" class="group-button leave-group" rel="leave" href="' . wp_nonce_url( bp_get_group_permalink( $group ) . 'leave-group', 'groups_leave_group' ) . '">' . __( 'Leave Group', 'buddypress' ) . '</a>';
				}

				// Otherwise, it's a Request Membership button.
			} else {
				check_ajax_referer( 'groups_request_membership' );

				if ( ! groups_send_membership_request( bp_loggedin_user_id(), $group->id ) ) {
					_e( 'Error requesting membership', 'buddypress' );
				} else {
					echo '<a id="group-' . esc_attr( $group->id ) . '" class="group-button disabled pending membership-requested" rel="membership-requested" href="' . bp_get_group_permalink( $group ) . '">' . __( 'Request Sent', 'buddypress' ) . '</a>';
				}
			}
		}
	} else {
		check_ajax_referer( 'groups_leave_group' );

		if ( ! groups_leave_group( $group->id ) ) {
			_e( 'Error leaving group', 'buddypress' );
		} elseif ( 'public' == $group->status || ( ! empty( $group_auto_accept ) && 'is_true' == $group_auto_accept && in_array( $society_id, $member_types ) ) ) {
			echo '<a id="group-' . esc_attr( $group->id ) . '" class="group-button join-group" rel="join" href="' . wp_nonce_url( bp_get_group_permalink( $group ) . 'join', 'groups_join_group' ) . '">' . __( 'Join Group', 'buddypress' ) . '</a>';
		} elseif ( 'private' == $group->status ) {
			echo '<a id="group-' . esc_attr( $group->id ) . '" class="group-button request-membership" rel="join" href="' . wp_nonce_url( bp_get_group_permalink( $group ) . 'request-membership', 'groups_request_membership' ) . '">' . __( 'Request Membership', 'buddypress' ) . '</a>';
		}
	}

	exit;
}

add_action( 'wp_ajax_joinleave_group', 'hc_custom_bp_legacy_theme_ajax_joinleave_group', 0 );

/**
 * Prints a message if the group is not visible to the current user (it is a
 * private group, but the user can join).
 *
 * @global BP_Groups_Template $groups_template Groups template object.
 *
 * @param string      $message The message to be displayed to the user.
 * @param object|null $group Group to get status message for. Optional; defaults to current group.
 */
function hc_custom_bp_group_status_message( $message, $group ) {
	$user              = bp_loggedin_user_id();
	$group_auto_accept = groups_get_groupmeta( $group->id, 'auto_accept' );

	$society_id  = bp_groups_get_group_type( $group->id, true );
	$member_types = bp_get_member_type( $user, false );

	if ( ! empty( $group_auto_accept ) && 'is_true' == $group_auto_accept && in_array( $society_id, $member_types ) ) {
		$message = sprintf( "This is a private group that automatically accepts %s members. Click the 'Join Group' button to join.", strtoupper( $society_id ) );
	}

	return $message;
}

add_filter( 'bp_group_status_message', 'hc_custom_bp_group_status_message', 10, 2 );

/**
 * Remove the request-membership tab if the user can join automatically.
 */
function hc_custom_remove_group_request_membership() {
	global $bp;

	$group_id = bp_get_current_group_id();

	// Group admins will see all tabs.
	if ( ! $group_id ) {
		return;
	}

	$user              = bp_loggedin_user_id();
	$group_auto_accept = groups_get_groupmeta( $group_id, 'auto_accept' );

	$parent_nav_slug     = bp_get_current_group_slug();
	$secondary_nav_items = $bp->groups->nav->get_secondary( array( 'parent_slug' => $parent_nav_slug ) );

	$selected_item = null;

	// Remove the nav items. Not stored, just unsets it.
	foreach ( $secondary_nav_items as $subnav_item ) {
		if ( ! empty( $group_auto_accept ) && 'is_true' == $group_auto_accept ) {

			bp_core_remove_subnav_item( $parent_nav_slug, 'request-membership', 'groups' );
		}
	}
}
add_action( 'bp_actions', 'hc_custom_remove_group_request_membership' );

/**
 * Catch and process "Join Group" button clicks.
 *
 * @since 1.0.0
 *
 * @return bool
 */
function hc_custom_groups_action_join_group() {

	if ( ! bp_is_single_item() || ! bp_is_groups_component() || ! bp_is_current_action( 'join' ) ) {
		return false;
	}

	// If a user is not a member of a society, they cannot join a group in that society.
	if ( ! Humanities_Commons::hcommons_user_in_current_society() ) {
		bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );
	}

	// Nonce check.
	if ( ! check_admin_referer( 'groups_join_group' ) ) {
		return false;
	}

	$bp = buddypress();

	$user              = bp_loggedin_user_id();
	$group_auto_accept = groups_get_groupmeta( $bp->groups->current_group->id, 'auto_accept' );

	// Skip if banned or already a member.
	if ( ! groups_is_user_member( bp_loggedin_user_id(), $bp->groups->current_group->id ) && ! groups_is_user_banned( bp_loggedin_user_id(), $bp->groups->current_group->id ) ) {

		// User wants to join a group that is not public.
		if ( 'public' != $bp->groups->current_group->status ) {
			$society_id  = bp_groups_get_group_type( $bp->groups->current_group->id, true );
			$member_types = bp_get_member_type( $user, false );

			if ( ! empty( $group_auto_accept ) && 'is_true' == $group_auto_accept && in_array( $society_id, $member_types ) ) {

				if ( ! groups_join_group( $bp->groups->current_group->id ) ) {
					bp_core_add_message( __( 'There was an error joining the group.', 'buddypress' ), 'error' );
				} else {
					bp_core_add_message( __( 'You joined the group!', 'buddypress' ) );
				}

				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );
			}

			if ( ! groups_check_user_has_invite( bp_loggedin_user_id(), $bp->groups->current_group->id ) ) {
				bp_core_add_message( __( 'There was an error joining the group.', 'buddypress' ), 'error' );
				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );
			}
		}

		// User wants to join any group.
		if ( ! groups_join_group( $bp->groups->current_group->id ) ) {
			bp_core_add_message( __( 'There was an error joining the group.', 'buddypress' ), 'error' );
		} else {
			bp_core_add_message( __( 'You joined the group!', 'buddypress' ) );
		}

		bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) );
	}

	/**
	 * Filters the template to load for the single group screen.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Path to the single group template to load.
	 */
	bp_core_load_template( apply_filters( 'groups_template_group_home', 'groups/single/home' ) );
}

add_action( 'bp_actions', 'hc_custom_groups_action_join_group', 0 );

/**
 * Ensures that only society members can create society groups.
 *
 * This is a filter called upon group creation by Buddypress.
 * @see bp-groups/bp-groups-template.php
 *
 * @author Mike Thicke
 *
 * @param bool can_create Whether the user can create groups.
 * @param bool restricted Whether group creation is restricted.
 * 
 * @return bool Whether user can create a society group.
 */
function hc_custom_group_creation_only_for_society_members( $can_create, $restricted ) {
	if ( ! $can_create ) {
		return false;
	}

	if ( ! Humanities_Commons::hcommons_user_in_current_society() && ! is_super_admin() ) {
		return false;
	}

	return true;
}
add_filter( 'bp_user_can_create_groups', 'hc_custom_group_creation_only_for_society_members', 10, 2 );
