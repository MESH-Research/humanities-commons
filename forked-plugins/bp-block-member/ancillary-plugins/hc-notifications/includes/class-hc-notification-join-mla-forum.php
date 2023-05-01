<?php
/**
 * Tell users how to manage their MLA forum memberships.
 *
 * @package Hc_Notifications
 */

/**
 * Notification.
 */
class HC_Notification_Join_MLA_Forum extends HC_Notification {

	/**
	 * Component action.
	 *
	 * @var string
	 */
	public static $action = 'join_mla_forum';

	/**
	 * Set up notification actions.
	 */
	public static function setup_actions() {
		$add_notification = function( $group_id, $user_id ) {
			$mla_oid = groups_get_groupmeta( $group_id, 'mla_oid' );

			// Avoid sending this type of notification more than once per user per group by setting a user meta flag.
			$meta_key   = sprintf( '%s_%s_%s_sent', buddypress()->hc_notifications->id, self::$action, $group_id );
			$meta_value = get_user_meta( $user_id, $meta_key, true );

			if (
				$meta_value ||
				! $mla_oid ||
				'M' !== substr( groups_get_groupmeta( $group_id, 'mla_oid' ), 0, 1 )
			) {
				return;
			}

			$result = bp_notifications_add_notification(
				[
					'user_id'          => $user_id,
					'component_name'   => buddypress()->hc_notifications->id,
					'component_action' => self::$action,
					'item_id'          => $group_id,
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
		return bp_get_group_permalink( groups_get_group( $item_id ) );
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
                $group = groups_get_group( $item_id );
		
		$group_society_id = strtoupper( bp_groups_get_group_type( $item_id ) );
                
                $text  = sprintf(
                        'You\'ve been added to "%s" based on your %s membership record',
                        $group->name, 
                        $group_society_id
                );

                if ( groups_is_user_admin( get_current_user_id(), $item_id ) ) {
                        $text .= ' You are an admin of this group.';
                } 

                return $text;
	}
}
