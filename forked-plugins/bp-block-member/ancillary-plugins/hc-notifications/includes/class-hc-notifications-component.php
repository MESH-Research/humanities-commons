<?php
/**
 * Component class.
 *
 * @package Hc_Notifications
 */

/**
 * Component.
 */
class HC_Notifications_Component extends BP_Component {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::start(
			'hc_notifications',
			'HC Notifications',
			plugin_dir_path( __DIR__ )
		);

		add_filter( 'bp_notifications_get_registered_components', [ $this, 'filter_registered_components' ] );
	}

	/**
	 * Set up globals.
	 *
	 * @param array $args See BP_Component.
	 */
	public function setup_globals( $args = [] ) {
		parent::setup_globals(
			[
				'notification_callback' => [ $this, 'format_notification' ],
			]
		);
	}

	/**
	 * Register all known notification classes & set up actions for each.
	 */
	public function setup_actions() {
		parent::setup_actions();

		// Require base notification class.
		require_once 'class-hc-notification.php';

		// List of classes that extend the base notification class.
		$classes = apply_filters(
			'hc_notifications_classes', [
				'class-hc-notification-join-group-site.php' => 'HC_Notification_Join_Group_Site',
				'class-hc-notification-join-mla-forum.php' => 'HC_Notification_Join_MLA_Forum',
				'class-hc-notification-new-user-email-settings.php' => 'HC_Notification_New_User_Email_Settings',
				'class-hc-notification-newsletter-opt-out.php' => 'HC_Notification_Newsletter_Opt_Out',
			]
		);

		foreach ( $classes as $include => $class ) {
			require_once $include;

			$inst = new $class;
			$inst->setup_actions();

			// These filters are applied by $this->format_notification() when the notification is rendered.
			add_filter( $this->id . '_' . $class::$action . '_link', [ $class, 'filter_link' ], 10, 6 );
			add_filter( $this->id . '_' . $class::$action . '_text', [ $class, 'filter_text' ], 10, 6 );
		}
	}

	/**
	 * Notification formatter callback.
	 *
	 * @param string $action            The kind of notification being rendered.
	 * @param int    $item_id           The primary item id.
	 * @param int    $secondary_item_id The secondary item id.
	 * @param int    $total_items       The total number of messaging-related notifications
	 *                                  waiting for the user.
	 * @param string $format            Return value format. 'string' for BuddyBar-compatible
	 *                                  notifications; 'array' for WP Toolbar. Default: 'string'.
	 * @return string|array Formatted notifications.
	 */
	public function format_notification( $action, $item_id, $secondary_item_id, $total_items, $format ) {
		$link = apply_filters_ref_array( "hc_notifications_${action}_link", func_get_args() );
		$text = apply_filters_ref_array( "hc_notifications_${action}_text", func_get_args() );

		if ( 'string' === $format ) {
			$retval = sprintf( '<a href="%s">%s</a>', $link, $text );
		} else {
			$retval = [
				'text' => $text,
				'link' => $link,
			];
		}

		return $retval;
	}

	/**
	 * Register this component.
	 *
	 * @param array $component_names Array of registered component names.
	 */
	public function filter_registered_components( $component_names ) {
		$component_names[] = buddypress()->hc_notifications->id;
		return $component_names;
	}

}
