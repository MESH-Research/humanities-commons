<?php
/**
 * When a user joins a group, tell them about the group site if one exists.
 *
 * @package Hc_Notifications
 */

/**
 * Notification.
 */
class HC_Notification_Join_Group_Site extends HC_Notification {

	/**
	 * Component action.
	 *
	 * @var string
	 */
	public static $action = 'new_group_site_member';

	/**
	 * Set up notification actions.
	 */
	public static function setup_actions() {
		if ( ! is_multisite() ) {
			return;
		}

		$add_notification = function( $group_id, $user_id ) {
			$blog_id = groups_get_groupmeta( $group_id, 'groupblog_blog_id' );

			// Avoid sending this type of notification more than once per user per group by setting a user meta flag.
			$meta_key   = sprintf( '%s_%s_%s_sent', buddypress()->hc_notifications->id, self::$action, $group_id );
			$meta_value = get_user_meta( $user_id, $meta_key, true );

			// Bail if this group has no blog or we've already sent this notification before.
			if ( $meta_value || ! $blog_id ) {
				return;
			}

			$result = bp_notifications_add_notification(
				[
					'user_id'           => $user_id,
					'component_name'    => buddypress()->hc_notifications->id,
					'component_action'  => self::$action,
					'item_id'           => $group_id,
					'secondary_item_id' => $blog_id,
				]
			);

			add_user_meta( $user_id, $meta_key, true, true );
		};

		add_action( 'groups_join_group', $add_notification, 10, 2 );
	}

	/**
	 * Filter link.
	 *
	 * @param string $action            The kind of notification being rendered.
	 * @param int    $item_id           The primary item id.
	 * @param int    $secondary_item_id The secondary item id.
	 * @param int    $total_items       The total number of messaging-related notifications
	 *                                  waiting for the user.
	 * @param string $format            Return value format. 'string' for BuddyBar-compatible
	 *                                  notifications; 'array' for WP Toolbar. Default: 'string'.
	 *
	 * @return string Value of the notification link href attribute.
	 */
	public static function filter_link( $action, $item_id, $secondary_item_id, $total_items, $format ) {
		return get_site_url( $secondary_item_id );
	}

	/**
	 * Filter text.
	 *
	 * @param string $action            The kind of notification being rendered.
	 * @param int    $item_id           The primary item id.
	 * @param int    $secondary_item_id The secondary item id.
	 * @param int    $total_items       The total number of messaging-related notifications
	 *                                  waiting for the user.
	 * @param string $format            Return value format. 'string' for BuddyBar-compatible
	 *                                  notifications; 'array' for WP Toolbar. Default: 'string'.
	 *
	 * @return string Text content of the notification link.
	 */
	public static function filter_text( $action, $item_id, $secondary_item_id, $total_items, $format ) {
		switch_to_blog( $secondary_item_id );
		$blog_name = get_bloginfo( 'name' );
		$caps      = array_keys( get_user_meta( get_current_user_id(), 'wp_capabilities', true ) );
		$role      = $caps[0]; // Just report the first role for now.
		restore_current_blog();

		return sprintf(
			'You\'ve been added to the group site "%s" with the role of %s.',
			$blog_name,
			$role
		);
	}

}
