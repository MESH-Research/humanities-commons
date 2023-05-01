<?php
/**
 * Abstract class for all notifications in this plugin.
 *
 * @package Hc_Notifications
 */

/**
 * Extend this class to add a new notification.
 *
 * Be sure to register your new notification class in HC_Notifications_Component->setup_actions().
 */
class HC_Notification {

	/**
	 * Component action sent to bp_notifications_add_notification().
	 *
	 * @var string
	 */
	public static $action;

	/**
	 * Set up notification actions.
	 *
	 * This should call add_action() with a callback that calls bp_notifications_add_notification().
	 */
	public static function setup_actions() {}

	/**
	 * Filter notification text.
	 *
	 * This is automatically hooked to the relevant action by HC_Notifications_Component.
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
		// TODO This function should never be called on this class directly,
		// so we should probably throw a doing_it_wrong() here.
		return '';
	}

	/**
	 * Filter notification link.
	 *
	 * This is automatically hooked to the relevant action by HC_Notifications_Component.
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
		// TODO This function should never be called on this class directly,
		// so we should probably throw a doing_it_wrong() here.
		return '';
	}

}
