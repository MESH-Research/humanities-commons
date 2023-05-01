<?php
/**
 * Ask users to opt-in to the HC newsletter.
 *
 * @package Hc_Notifications
 */

/**
 * Notification.
 */
class HC_Notification_Newsletter_Opt_Out extends HC_Notification {

	/**
	 * Component action.
	 *
	 * @var string
	 */
	public static $action = 'newsletter_opt_out';

	/**
	 * Set up notification actions.
	 */
	public static function setup_actions() {
		$add_notification = function() {
			$optin_meta_value = get_user_meta( get_current_user_id(), 'newsletter_optin', true );

			// Avoid sending this type of notification more than once per user per group by setting a user meta flag.
			$sent_meta_key   = sprintf( '%s_%s_sent', buddypress()->hc_notifications->id, self::$action );
			$sent_meta_value = get_user_meta( get_current_user_id(), $sent_meta_key, true );

			// Only add notifications for users who have not opted in and haven't already been notified.
			if ( 'no' !== $optin_meta_value || $sent_meta_value ) {
				return;
			}

			$result = bp_notifications_add_notification(
				[
					'user_id'          => get_current_user_id(),
					'component_name'   => buddypress()->hc_notifications->id,
					'component_action' => self::$action,
				]
			);

			add_user_meta( get_current_user_id(), $sent_meta_key, true, true );
		};

		add_action( 'wp_login', $add_notification );
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
		return trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() ) . 'notifications';
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
		return 'You have not opted in to receive the Humanities Commons newsletter. Visit your email settings to opt in.';
	}

}
