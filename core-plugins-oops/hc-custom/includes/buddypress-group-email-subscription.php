<?php
/**
 * Customizations to buddypress-group-email-subscription.
 *
 * @package Hc_Custom
 * @version 1.0.11272018
 */

/**
 * Remove BPGES actions since we use crontab instead of WP cron.
 * Spark doesn't like random emails that are not on it's white list.
 * Welcome emails (on join group uses the first admin as the from email which errors out Spark.
 * We replace this from email with a noreply email.
 */
function hcommons_filter_bp_mail_from( $from, $email_address, $name, $email_type ) {
	if ( $email_type->get( 'type' ) === "bp-ges-welcome" ) {
		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( 'www.' == substr( $sitename, 0, 4 ) ) {
			$sitename = substr( $sitename, 4 );
		}
		$from = new BP_Email_Recipient( "noreply@" . $sitename, $name );
	}

	return $from;
}

;
add_action( 'bp_email_set_from', 'hcommons_filter_bp_mail_from', 10, 4 );

/**
 * Hide the send email to everyone notice
 *
 * @since 1.0.11272018
 */
add_action( 'bp_group_email_subscription_enable_email_notice', function () {
	return false;
} );

/**
 * Hide the change topic email prefix in the group > manage > details screen
 *
 * @since 1.0.11272018
 */
add_filter( 'bp_rbe_new_topic_show_option_on_details_page', false );

/**
 * Remove BPGES actions since we use crontab instead of WP cron.
 */
function hcommons_remove_bpges_actions() {
	remove_action( 'ass_digest_event', 'ass_daily_digest_fire' );
	remove_action( 'ass_digest_event_weekly', 'ass_weekly_digest_fire' );
}

add_action( 'bp_init', 'hcommons_remove_bpges_actions' );

/**
 * Adds a footer to the group welcome email
 *
 * @param $body
 * @param $group_id
 * @param $user
 *
 * @return string
 */

function hcommons_add_welcome_email_footer( $body, $group_id, $user ) {
	add_action( 'bp_before_email_footer', function () use ( $group_id, $user ) {
		$group_link = bp_get_group_link( groups_get_group( $group_id ) );
		switch ( ass_group_default_status( $user->ID ) ) {
			case "supersub":
				$current_email_settings = "All Emails";
				break;
			case "sub":
				$current_email_settings = "No Topic Email";
				break;
			case "sum":
				$current_email_settings = "Weekly Summary Email";
				break;
			case "dig":
				$current_email_settings = "Daily Digest Email";
				break;
			case "no":
			default:
				$current_email_settings = "No Emails";
				break;
		}
		$footer = <<<WELCOME_EMAIL
        ____________________<br/>
        This email is being sent by $group_link<br/>
        Your email setting for this group is: $current_email_settings<br/><br/>
WELCOME_EMAIL;
		echo $footer;
	} );

	return $body;
}

add_action( 'ass_welcome_email', 'hcommons_add_welcome_email_footer', 10, 3 );

/**
 * Add a line break after "Replying to this email will not..."
 * Assumes HTML email, plaintext not supported.
 *
 * @param string $notice Non-RBE notice.
 *
 * @return string
 */
function hcommons_filter_bp_rbe_get_nonrbe_notice( string $notice ) {
	return $notice . '<br>';
}

add_action( 'bp_rbe_get_nonrbe_notice', 'hcommons_filter_bp_rbe_get_nonrbe_notice' );

/**
 * Add nested reply formatting to digests.
 *
 * TODO pass phpcs.
 *
 * @codingStandardsIgnoreStart
 *
 * @param string $group_message
 * @param int    $group_id
 * @param string $type
 * @param array  $activity_ids
 * @param int    $user_id
 *
 * @return string Filtered group message
 */
function hcommons_filter_ass_digest_format_item_group( $group_message, $group_id, $type, $activity_ids, $user_id ) {
	global $bp, $ass_email_css;

	$group = groups_get_group( $group_id );

	$group_permalink = bp_get_group_permalink( $group );
	$group_name_link = '<a class="item-group-group-link" href="' . esc_url( $group_permalink ) . '" name="' . esc_attr( $group->slug ) . '">' . esc_html( $group->name ) . '</a>';

	$userdomain       = ass_digest_get_user_domain( $user_id );
	$unsubscribe_link = "$userdomain?bpass-action=unsubscribe&group=$group_id&access_key=" . md5( "{$group_id}{$user_id}unsubscribe" . wp_salt() );
	// $gnotifications_link = ass_get_login_redirect_url( $group_permalink . 'notifications/' );
	$gnotifications_link = ass_get_login_redirect_url( $userdomain . 'settings/notifications/' );

	// add the group title bar
	if ( 'dig' === $type ) {
		$group_message = "\n---\n\n<div class=\"item-group-title\" {$ass_email_css['group_title']}>" . sprintf( __( 'Group: %s', 'bp-ass' ), $group_name_link ) . "</div>\n\n";
	} elseif ( 'sum' === $type ) {
		$group_message = "\n---\n\n<div class=\"item-group-title\" {$ass_email_css['group_title']}>" . sprintf( __( 'Group: %s weekly summary', 'bp-ass' ), $group_name_link ) . "</div>\n";
	}

	// add change email settings link
	$group_message .= "\n<div class=\"item-group-settings-link\" {$ass_email_css['change_email']}>";
	$group_message .= __( 'To disable these notifications for this group click ', 'bp-ass' ) . " <a href=\"$unsubscribe_link\">" . __( 'unsubscribe', 'bp-ass' ) . '</a> - ';
	$group_message .= __( 'change ', 'bp-ass' ) . '<a href="' . $gnotifications_link . '">' . __( 'email options', 'bp-ass' ) . '</a>';
	$group_message .= "</div>\n\n";

	$group_message = apply_filters( 'ass_digest_group_message_title', $group_message, $group_id, $type );

	// Sort activity items and group by forum topic, where possible.
	$grouped_activity_ids = array(
		'topics' => array(),
		'other'  => array(),
	);

	$topic_activity_map = array();

	foreach ( $activity_ids as $activity_id ) {
		$activity_item = ! empty( $bp->ass->items[ $activity_id ] )
			? $bp->ass->items[ $activity_id ]
			: new BP_Activity_Activity( $activity_id );

		switch ( $activity_item->type ) {
			case 'bbp_topic_create':
				$topic_id                         = $activity_item->secondary_item_id;
				$grouped_activity_ids['topics'][] = $topic_id;

				if ( ! isset( $topic_activity_map[ $topic_id ] ) ) {
					$topic_activity_map[ $topic_id ] = array();
				}

				$topic_activity_map[ $topic_id ][] = $activity_id;
				break;

			case 'bbp_reply_create':
				// Topic may or may not be in this digest queue.
				$topic_id                         = bbp_get_reply_topic_id( $activity_item->secondary_item_id );
				$grouped_activity_ids['topics'][] = $topic_id;

				if ( ! isset( $topic_activity_map[ $topic_id ] ) ) {
					$topic_activity_map[ $topic_id ] = array();
				}

				$topic_activity_map[ $topic_id ][] = $activity_id;
				break;

			default:
				$grouped_activity_ids['other'][] = $activity_id;
				break;
		}

		$grouped_activity_ids['topics'] = array_unique( $grouped_activity_ids['topics'] );
	}

	// Assemble forum topic markup first.
	foreach ( $grouped_activity_ids['topics'] as $topic_id ) {
		$topic = bbp_get_topic( $topic_id );
		if ( ! $topic ) {
			continue;
		}

		// 'Topic' header.
		$item_message = '';
		$item_message .= "<div class=\"digest-item\" {$ass_email_css['item_div']}>";

		$item_message .= '<div class="digest-topic-header">';
		$item_message .= sprintf(
			__( 'Topic: %s', 'bp-ass' ),
			sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $topic_id ) ), esc_html( $topic->post_title ) )
		);
		$item_message .= '</div>'; // .digest-topic-header

		$item_message .= '<div class="digest-topic-items">';
		foreach ( $topic_activity_map[ $topic_id ] as $activity_id ) {
			$activity_item = new BP_Activity_Activity( $activity_id );

			$poster_name     = bp_core_get_user_displayname( $activity_item->user_id );
			$poster_url      = bp_core_get_user_domain( $activity_item->user_id );
			$topic_name      = $topic->post_title;
			$topic_permalink = get_permalink( $topic_id );

			if ( 'bbp_topic_create' === $activity_item->type ) {
				$action_format = '<a href="%s">%s</a> posted on <a href="%s">%s</a>';
			} else {
				$action_format = '<a href="%s">%s</a> started <a href="%s">%s</a>';
			}

			$action = sprintf( $action_format, esc_url( $poster_url ), esc_html( $poster_name ), esc_url( $topic_permalink ), esc_html( $topic_name ) );

			/* Because BuddyPress core set gmt = true, timezone must be added */
			$timestamp = strtotime( $activity_item->date_recorded ) + date( 'Z' );

			$time_posted = date( get_option( 'time_format' ), $timestamp );
			$date_posted = date( get_option( 'date_format' ), $timestamp );

			$item_message .= '<div class="digest-topic-item" style="border-top:1px solid #eee; margin: 15px 0 15px 30px;">';
			$item_message .= "<span class=\"digest-item-action\" {$ass_email_css['item_action']}>" . $action . ': ';
			$item_message .= "<span class=\"digest-item-timestamp\" {$ass_email_css['item_date']}>" . sprintf( __( 'at %1$s, %2$s', 'bp-ass' ), $time_posted, $date_posted ) . '</span>';
			$item_message .= "<br><span class=\"digest-item-content\" {$ass_email_css['item_content']}>" . apply_filters( 'ass_digest_content', $activity_item->content, $activity_item, $type ) . '</span>';
			$item_message .= "</span>\n";
			$item_message .= '</div>'; // .digest-topic-item
		}
		$item_message .= '</div>'; // .digest-topic-items

		$item_message .= '</div>'; // .digest-item

		$group_message .= $item_message;
	}

	// Non-forum-related markup goes at the end.
	foreach ( $grouped_activity_ids['other'] as $activity_id ) {
		// Cache is set earlier in ass_digest_fire()
		$activity_item = ! empty( $bp->ass->items[ $activity_id ] ) ? $bp->ass->items[ $activity_id ] : false;

		if ( ! empty( $activity_item ) ) {

			if ( 'bpeo_create_event' === $activity_item->type ) {
				$event_id = $activity_item->secondary_item_id;

				$occurrences = eo_get_the_occurrences_of( $event_id );

				if ( $occurrences ) {
					$occurence_ids = array_keys( $occurrences );
					$occurence_id  = $occurence_ids[0];
				} else {
					continue;
				}

				$event_date = eo_get_the_start( 'g:i a jS M Y', $event_id, $occurence_id );

				$group_message .= "<div class=\"digest-item\" {$ass_email_css['item_div']}>";
				$group_message .= "<span class=\"digest-item-action\" {$ass_email_css['item_action']}>" . $activity_item->action . ': ';
				$group_message .= "<span class=\"digest-item-timestamp\" {$ass_email_css['item_date']}>" . sprintf( __( 'at %s', 'bp-ass' ), $event_date ) . '</span>';
				$group_message .= "</span>\n";

				// activity content
				if ( ! empty( $activity_item->content ) ) {
					$item_message .= "<br><span class=\"digest-item-content\" {$ass_email_css['item_content']}>" . apply_filters( 'ass_digest_content', $activity_item->content, $activity_item, $type ) . '</span>';
				}

				$view_link = $activity_item->primary_link;

				$group_message .= ' - <a class="digest-item-view-link" href="' . ass_get_login_redirect_url( $view_link ) . '">' . __( 'View', 'bp-ass' ) . '</a>';

				$group_message .= "</div>\n\n";

			} else {
				$group_message .= ass_digest_format_item( $activity_item, $type );
			}
		}
	}


	return $group_message;
}

;
add_filter( 'ass_digest_format_item_group', 'hcommons_filter_ass_digest_format_item_group', 10, 5 );
// @codingStandardsIgnoreEnd

/**
 * Remove activity items that don't belong to the current network from digest emails
 *
 * @uses Humanities_Commons
 *
 * @param array $group_activity_ids List of activities keyed by group ID.
 *
 * @return array Only those activity IDs belonging to the current network
 */
function hcommons_filter_ass_digest_group_activity_ids( $group_activity_ids ) {
	$network_activity_ids = [];

	foreach ( $group_activity_ids as $group_id => $activity_ids ) {
		if ( bp_groups_get_group_type( $group_id ) === Humanities_Commons::$society_id ) {
			$network_activity_ids[ $group_id ] = $activity_ids;
		}

		// Sanity check for activity age - very old items should not be sent.
		// Allow for weekly digests to include one prior week of potentially delayed items.
		// Beyond that, consider the inclusion of this activity a bug and remove it.
		foreach ( $activity_ids as $i => $activity_id ) {
			$activity     = new BP_Activity_Activity( $activity_id );
			$activity_age = time() - strtotime( $activity->date_recorded );

			if ( $activity_age > 2 * WEEK_IN_SECONDS ) {
				unset( $activity_ids[ $i ] );
			}
		}
	}

	return $network_activity_ids;
}

add_action( 'ass_digest_group_activity_ids', 'hcommons_filter_ass_digest_group_activity_ids' );

/**
 * Sanity checks for email digests:
 * * Number of items should be reasonably small
 * * Age of items should be reasonably recent
 * * Origin network should be consistent per-digest (no cross-network activities)
 *
 * @uses Humanities_Commons
 *
 * @param string $summary Summary.
 *
 * @return string Summary.
 */
function hcommons_filter_ass_digest_summary_full( string $summary ) {
	// Start with a clean slate, handle below if we need to kill this particular email.
	remove_filter( 'ass_send_email_args', '__return_false' );

	/**
	 * Prevent this digest from being sent to the current user.
	 */
	$skip_current_user_digest = function () use ( $summary ) {
		error_log( 'DIGEST: killed digest with summary: ' . $summary );
		add_filter( 'ass_send_email_args', '__return_false' );
	};

	/**
	 * This is intended to prevent very large digest emails from being sent,
	 * whether due to lots of legitimate activity or erroneous filtering.
	 */
	preg_match_all( '/\((\d+) items\)/', $summary, $matches, PREG_PATTERN_ORDER );
	foreach ( $matches[1] as $num_items ) {
		if ( $num_items > 50 ) {
			$skip_current_user_digest();
		}
	}

	// This should contain the name of at least one group.
	if ( 'Group Summary:' === trim( strip_tags( $summary ) ) ) {
		$skip_current_user_digest();
	}

	return $summary;
}

add_filter( 'ass_digest_summary_full', 'hcommons_filter_ass_digest_summary_full' );

/**
 * Modify the default bbp_reply_create/bbp_topic_create subject
 *
 * @param string $activity_text The subject line of the e-mail.
 *
 * @param object $activity      The BP_Activity_Activity object for this notification.
 *
 * @return string $activity_text Return modified string.
 */
function hcommons_bp_ass_activity_notification_action( $activity_text, $activity ) {
	$topic_id    = bbp_get_reply_topic_id( $activity->secondary_item_id );
	$topic_title = get_post_field( 'post_title', $topic_id, 'raw' );

	$topic_title = wp_trim_words( $topic_title, 7, '...' );

	$forum_id    = bbp_get_topic_forum_id( $topic_id );
	$forum_title = get_post_field( 'post_title', $forum_id, 'raw' );

	$forum_title = wp_trim_words( $forum_title, 4, '...' );

	switch ( $activity->type ) {
		case 'bbp_topic_create':
			// @codingStandardsIgnoreLine
			$activity_text = sprintf( esc_html__( '%1$s (%2$s)', 'bbpress' ), $topic_title, $forum_title );
			break;

		case 'bbp_reply_create':
			// @codingStandardsIgnoreLine
			$activity_text = sprintf( esc_html__( 're: %1$s (%2$s)', 'bbpress' ), $topic_title, $forum_title );
			break;
	}

	return $activity_text;

}

add_filter( 'bp_ass_activity_notification_action', 'hcommons_bp_ass_activity_notification_action', 10, 2 );

/**
 * Adds a section for users to set their default group notifications when joining a new group.
 */
function hc_custom_default_group_forum_subscription_settings() {
	global $bp;
	global $current_user;
	global $groups_template, $bp;
	global $group_obj;

	$user_id   = $bp->displayed_user->id;
	$my_status = get_user_meta( $user_id, 'default_group_notifications', true );
	?>
    <table class="notification-settings" id="groups-notification-settings">
        <thead>
        <tr>
            <th class="icon"></th>
            <th class="title"><?php _e( 'Default For Groups', 'group_forum_subscription' ); ?></th>
            <th class="no-email gas-choice"><?php _e( 'No Email', 'buddypress' ); ?></th>
            <th class="weekly gas-choice"><?php _e( 'Weekly Summary', 'buddypress' ); ?></th>
            <th class="daily gas-choice"><?php _e( 'Daily Digest', 'buddypress' ); ?></th>
            <th class="new-topics gas-choice"><?php _e( 'New Topics', 'buddypress' ); ?></th>
            <th class="all-email gas-choice"><?php _e( 'All Email', 'buddypress' ); ?></th>

        </tr>
        </thead>

        <tbody>

        <tr>
            <td></td>

            <td>
                When you join a group, you’ll be subscribed to
            </td>

            <td class="no-email gas-choice">
                <input type="radio" name="default-group-notifications" value="no"
					<?php if ( 'no' == $my_status || ! $my_status ) { ?>
                        checked="checked"
					<?php } ?>/>
            </td>

            <td class="weekly gas-choice">
                <input type="radio" name="default-group-notifications" value="sum"
					<?php
					if ( 'sum' == $my_status ) {
						?>
                        checked="checked" <?php } ?>/>
            </td>

            <td class="daily gas-choice">
                <input type="radio" name="default-group-notifications" value="dig"
					<?php if ( 'dig' == $my_status ) { ?>
                        checked="checked"
					<?php } ?>/>
            </td>

            <td class="new-topics gas-choice">
                <input type="radio" name="default-group-notifications" value="sub"
					<?php if ( 'sub' == $my_status ) { ?>
                        checked="checked"
					<?php } ?>/>
            </td>

            <td class="weekly gas-choice">
                <input type="radio" name="default-group-notifications" value="supersub"
					<?php if ( 'supersub' == $my_status ) { ?>
                        checked="checked"
					<?php } ?>/>
            </td>
        </tr>

        <thead>
        <tr id="network">
            <th class="section-title" style="border:none;font-size:18px;">YOUR GROUPS</th>
        </tr>
        </thead>
		<?php
		$group_types = bp_groups_get_group_types();

		foreach ( $group_types as $group_type ) {

			$args = array(
				'per_page'       => 100,
				'group_type__in' => $group_type,
				'action'         => '',
				'type'           => '',
				'orderby'        => 'name',
				'order'          => 'ASC',
			);
			if ( bp_has_groups( $args ) ) {

				?>
                <thead>
                <tr id="network">
                    <th class="network-header"><?php echo strtoupper( $group_type ); ?></th>
                </tr>
                </thead>
				<?php
				while ( bp_groups() ) :
					bp_the_group();


					$group_id       = bp_get_group_id();
					$user_id        = $bp->displayed_user->id;
					$subscribers    = ass_get_subscriptions_for_group( $group_id );
					$current_status = $subscribers[ $user_id ];


					?>
                    <tr>
                        <td></td>

                        <td>
                            <a href="<?php bp_group_permalink(); ?>"><?php bp_group_name(); ?></a>
                        </td>

                        <td class="no-email gas-choice">
                            <input type="radio" name="group-notifications[<?php echo $group_id; ?>]" value="no"
								<?php
								if ( 'no' == $current_status || ! $current_status ) {
									?>
                                    checked="checked" <?php } ?>/>
                        </td>

                        <td class="weekly gas-choice">
                            <input type="radio" name="group-notifications[<?php echo $group_id; ?>]" value="sum"
								<?php
								if ( 'sum' == $current_status ) {
									?>
                                    checked="checked" <?php } ?>/>
                        </td>

                        <td class="daily gas-choice">
                            <input type="radio" name="group-notifications[<?php echo $group_id; ?>]" value="dig"
								<?php
								if ( 'dig' == $current_status ) {
									?>
                                    checked="checked" <?php } ?>/>
                        </td>

                        <td class="new-topics gas-choice">
                            <input type="radio" name="group-notifications[<?php echo $group_id; ?>]" value="sub"
								<?php
								if ( 'sub' == $current_status ) {
									?>
                                    checked="checked" <?php } ?>/>
                        </td>

                        <td class="weekly gas-choice">
                            <input type="radio" name="group-notifications[<?php echo $group_id; ?>]" value="supersub"
								<?php
								if ( 'supersub' == $current_status ) {
									?>
                                    checked="checked" <?php } ?>/>
                        </td>
                    </tr>
				<?php
				endwhile;
			}
		}
		?>

        </tbody>
    </table>
	<?php
}

/**
 * Modify the email settings page.
 */
function hc_custom_notifications_page() {
	remove_action( 'bp_notification_settings', 'bp_activity_screen_notification_settings', 1 );
	remove_action( 'bp_notification_settings', 'groups_screen_notification_settings' );
	remove_action( 'bp_notification_settings', 'ass_group_subscription_notification_settings' );
	remove_action( 'bp_notification_settings', 'messages_screen_notification_settings', 2 );
	remove_action( 'bp_notification_settings', 'bp_follow_screen_notification_settings' );

	// Newsletter.
	echo hc_custom_newsletter_settings();

	?>
    <h1>Groups</h1>

	<?php
	// Default For Groups.
	echo hc_custom_default_group_forum_subscription_settings();

	// General Groups Settings.
	echo hc_custom_general_group_settings();

	// Member Activity.
	echo hc_custom_member_activity_settings();

}

add_action( 'bp_notification_settings', 'hc_custom_notifications_page', 0 );

/**
 * Modify the Member Activity section of the settings page.
 */
function hc_custom_member_activity_settings() {

	if ( bp_activity_do_mentions() ) {

		$mention = bp_get_user_meta( bp_displayed_user_id(), 'notification_activity_new_mention', true );

		if ( ! $mention ) {
			$mention = 'yes';
		}
	}

	$reply = bp_get_user_meta( bp_displayed_user_id(), 'notification_activity_new_reply', true );

	if ( ! $reply ) {
		$reply = 'yes';
	}

	$new_messages = bp_get_user_meta( bp_displayed_user_id(), 'notification_messages_new_message', true );

	if ( ! $new_messages ) {
		$new_messages = 'yes';
	}

	$notify = bp_get_user_meta( bp_displayed_user_id(), 'notification_starts_following', true );

	if ( ! $notify ) {
		$notify = 'yes';
	}

	?>

    <table class="notification-settings" id="activity-notification-settings">

        <thead>
        <tr>
            <th class="icon">&nbsp;</th>
            <th class="title"><H1><?php _e( 'Member Activity', 'buddypress' ); ?></H1></th>
            <th class="yes"><?php _e( 'Yes', 'buddypress' ); ?></th>
            <th class="no"><?php _e( 'No', 'buddypress' ); ?></th>
        </tr>
        </thead>

        <tbody>

		<?php if ( bp_activity_do_mentions() ) : ?>

            <tr id="activity-notification-settings-mentions">
                <td>&nbsp;</td>

                <td>Send an e-mail notice when:</td>


            </tr>

            <tr id="activity-notification-settings-mentions">
                <td>&nbsp;</td>


				<?php /* translators: username */ ?>
                <td><?php printf( __( 'A member mentions you in an update using "@%s"', 'buddypress' ), bp_core_get_username( bp_displayed_user_id() ) ); ?></td>
                <td class="yes"><input type="radio" name="notifications[notification_activity_new_mention]"
                                       id="notification-activity-new-mention-yes"
                                       value="yes" <?php checked( $mention, 'yes', true ); ?>/><label
                            for="notification-activity-new-mention-yes" class="bp-screen-reader-text">
						<?php
						/* translators: accessibility text */
						_e( 'Yes, send email', 'buddypress' );
						?>
                    </label></td>
                <td class="no"><input type="radio" name="notifications[notification_activity_new_mention]"
                                      id="notification-activity-new-mention-no"
                                      value="no" <?php checked( $mention, 'no', true ); ?>/><label
                            for="notification-activity-new-mention-no" class="bp-screen-reader-text">
						<?php
						/* translators: accessibility text */
						_e( 'No, do not send email', 'buddypress' );
						?>
                    </label></td>
            </tr>
		<?php endif; ?>

        <tr id="activity-notification-settings-replies">
            <td>&nbsp;</td>
            <td><?php _e( "A member replies to an update or comment you've posted", 'buddypress' ); ?></td>
            <td class="yes"><input type="radio" name="notifications[notification_activity_new_reply]"
                                   id="notification-activity-new-reply-yes"
                                   value="yes" <?php checked( $reply, 'yes', true ); ?>/><label
                        for="notification-activity-new-reply-yes" class="bp-screen-reader-text">
					<?php
					/* translators: accessibility text */
					_e( 'Yes, send email', 'buddypress' );
					?>
                </label></td>
            <td class="no"><input type="radio" name="notifications[notification_activity_new_reply]"
                                  id="notification-activity-new-reply-no"
                                  value="no" <?php checked( $reply, 'no', true ); ?>/><label
                        for="notification-activity-new-reply-no" class="bp-screen-reader-text">
					<?php
					/* translators: accessibility text */
					_e( 'No, do not send email', 'buddypress' );
					?>
                </label></td>
        </tr>

        <tr id="messages-notification-settings-new-message">
            <td></td>
            <td><?php _e( 'A member sends you a new message', 'buddypress' ); ?></td>
            <td class="yes"><input type="radio" name="notifications[notification_messages_new_message]"
                                   id="notification-messages-new-messages-yes"
                                   value="yes" <?php checked( $new_messages, 'yes', true ); ?>/><label
                        for="notification-messages-new-messages-yes" class="bp-screen-reader-text">
					<?php
					/* translators: accessibility text */
					_e( 'Yes, send email', 'buddypress' );
					?>
                </label></td>
            <td class="no"><input type="radio" name="notifications[notification_messages_new_message]"
                                  id="notification-messages-new-messages-no"
                                  value="no" <?php checked( $new_messages, 'no', true ); ?>/><label
                        for="notification-messages-new-messages-no" class="bp-screen-reader-text">
					<?php
					/* translators: accessibility text */
					_e( 'No, do not send email', 'buddypress' );
					?>
                </label></td>
        </tr>
        <tr>
            <td></td>
            <td><?php _e( 'A member starts following your activity', 'bp-follow' ); ?></td>
            <td class="yes"><input type="radio" name="notifications[notification_starts_following]"
                                   value="yes" <?php checked( $notify, 'yes', true ); ?>/></td>
            <td class="no"><input type="radio" name="notifications[notification_starts_following]"
                                  value="no" <?php checked( $notify, 'no', true ); ?>/></td>
        </tr>

        </tbody>
    </table>
	<?php
}

/**
 * General group settings.
 */
function hc_custom_general_group_settings() {
	// Get forum type.
	$forums = ass_get_forum_type();

	// No forums installed? stop now!
	if ( ! $forums ) {
		return;
	}

	$group_invite = bp_get_user_meta( bp_displayed_user_id(), 'notification_groups_invite', true );

	if ( ! $group_invite ) {
		$group_invite = 'yes';
	}

	$group_update = bp_get_user_meta( bp_displayed_user_id(), 'notification_groups_group_updated', true );

	if ( ! $group_update ) {
		$group_update = 'yes';
	}

	$group_promo = bp_get_user_meta( bp_displayed_user_id(), 'notification_groups_admin_promotion', true );

	if ( ! $group_promo ) {
		$group_promo = 'yes';
	}

	$group_request = bp_get_user_meta( bp_displayed_user_id(), 'notification_groups_membership_request', true );

	if ( ! $group_request ) {
		$group_request = 'yes';
	}

	$group_request_completed = bp_get_user_meta( bp_displayed_user_id(), 'notification_membership_request_completed', true );

	if ( ! $group_request_completed ) {
		$group_request_completed = 'yes';
	}

	$notification_group_documents_upload_member = bp_get_user_meta( bp_displayed_user_id(), 'notification_group_documents_upload_member', true );

	if ( ! $notification_group_documents_upload_member ) {
		$notification_group_documents_upload_member = 'yes';
	}

	$notification_group_documents_upload_mod = bp_get_user_meta( bp_displayed_user_id(), 'notification_group_documents_upload_mod', true );

	if ( ! $notification_group_documents_upload_mod ) {
		$notification_group_documents_upload_mod = 'yes';
	}
	?>
    <table class="notification-settings zebra" id="groups-subscription-notification-settings">
        <thead>
        <tr>
            <th class="icon"></th>
            <th class="title"><?php _e( 'General Groups Settings', 'bp-ass' ); ?></th>
            <th class="yes"><?php _e( 'Yes', 'bp-ass' ); ?></th>
            <th class="no"><?php _e( 'No', 'bp-ass' ); ?></th>
        </tr>
        </thead>
        <tbody>

		<?php
		// only add the following options if BP's bundled forums are installed...
		// @todo add back these options for bbPress if possible.
		?>

		<?php
		if ( 'buddypress' == $forums ) :

			$replies_to_topic = bp_get_user_meta( bp_displayed_user_id(), 'ass_replies_to_my_topic', true );

			if ( ! $replies_to_topic ) {
				$replies_to_topic = 'yes';
			}

			$replies_after_me = bp_get_user_meta( bp_displayed_user_id(), 'ass_replies_after_me_topic', true );

			if ( ! $replies_after_me ) {
				$replies_after_me = 'yes';
			}
			?>


            <tr>
                <td></td>
                <td><?php _e( 'A member replies in a forum topic you\'ve started', 'bp-ass' ); ?></td>
                <td class="yes"><input type="radio" name="notifications[ass_replies_to_my_topic]"
                                       value="yes" <?php checked( $replies_to_topic, 'yes', true ); ?>/></td>
                <td class="no"><input type="radio" name="notifications[ass_replies_to_my_topic]"
                                      value="no" <?php checked( $replies_to_topic, 'no', true ); ?>/></td>
            </tr>

            <tr>
                <td></td>
                <td><?php _e( 'A member replies after you in a forum topic', 'bp-ass' ); ?></td>
                <td class="yes"><input type="radio" name="notifications[ass_replies_after_me_topic]"
                                       value="yes" <?php checked( $replies_after_me, 'yes', true ); ?>/></td>
                <td class="no"><input type="radio" name="notifications[ass_replies_after_me_topic]"
                                      value="no" <?php checked( $replies_after_me, 'no', true ); ?>/></td>
            </tr>

		<?php endif; ?>

        <tr>
            <td></td>
            <td>Send an e-mail notice when:</td>

        </tr>


        <tr id="groups-notification-settings-invitation">
            <td></td>
            <td><?php _ex( 'A member invites you to join a group', 'group settings on notification settings page', 'buddypress' ); ?></td>
            <td class="yes"><input type="radio" name="notifications[notification_groups_invite]"
                                   id="notification-groups-invite-yes"
                                   value="yes" <?php checked( $group_invite, 'yes', true ); ?>/><label
                        for="notification-groups-invite-yes" class="bp-screen-reader-text">
					<?php
					/* translators: accessibility text */
					_e( 'Yes, send email', 'buddypress' );
					?>
                </label></td>
            <td class="no"><input type="radio" name="notifications[notification_groups_invite]"
                                  id="notification-groups-invite-no"
                                  value="no" <?php checked( $group_invite, 'no', true ); ?>/><label
                        for="notification-groups-invite-no" class="bp-screen-reader-text">
					<?php
					/* translators: accessibility text */
					_e( 'No, do not send email', 'buddypress' );
					?>
                </label></td>
        </tr>
        <tr id="groups-notification-settings-info-updated">
            <td></td>
            <td><?php _ex( 'Group information is updated', 'group settings on notification settings page', 'buddypress' ); ?></td>
            <td class="yes"><input type="radio" name="notifications[notification_groups_group_updated]"
                                   id="notification-groups-group-updated-yes"
                                   value="yes" <?php checked( $group_update, 'yes', true ); ?>/><label
                        for="notification-groups-group-updated-yes" class="bp-screen-reader-text">
					<?php
					/* translators: accessibility text */
					_e( 'Yes, send email', 'buddypress' );
					?>
                </label></td>
            <td class="no"><input type="radio" name="notifications[notification_groups_group_updated]"
                                  id="notification-groups-group-updated-no"
                                  value="no" <?php checked( $group_update, 'no', true ); ?>/><label
                        for="notification-groups-group-updated-no" class="bp-screen-reader-text">
					<?php
					/* translators: accessibility text */
					_e( 'No, do not send email', 'buddypress' );
					?>
                </label></td>
        </tr>
        <tr id="groups-notification-settings-promoted">
            <td></td>
            <td><?php _ex( 'You are promoted to a group administrator or moderator', 'group settings on notification settings page', 'buddypress' ); ?></td>
            <td class="yes"><input type="radio" name="notifications[notification_groups_admin_promotion]"
                                   id="notification-groups-admin-promotion-yes"
                                   value="yes" <?php checked( $group_promo, 'yes', true ); ?>/><label
                        for="notification-groups-admin-promotion-yes" class="bp-screen-reader-text">
					<?php
					/* translators: accessibility text */
					_e( 'Yes, send email', 'buddypress' );
					?>
                </label></td>
            <td class="no"><input type="radio" name="notifications[notification_groups_admin_promotion]"
                                  id="notification-groups-admin-promotion-no"
                                  value="no" <?php checked( $group_promo, 'no', true ); ?>/><label
                        for="notification-groups-admin-promotion-no" class="bp-screen-reader-text">
					<?php
					/* translators: accessibility text */
					_e( 'No, do not send email', 'buddypress' );
					?>
                </label></td>
        </tr>
        <tr id="groups-notification-settings-request">
            <td></td>
            <td><?php _ex( 'A member requests to join a private group for which you are an admin', 'group settings on notification settings page', 'buddypress' ); ?></td>
            <td class="yes"><input type="radio" name="notifications[notification_groups_membership_request]"
                                   id="notification-groups-membership-request-yes"
                                   value="yes" <?php checked( $group_request, 'yes', true ); ?>/><label
                        for="notification-groups-membership-request-yes" class="bp-screen-reader-text">
					<?php
					/* translators: accessibility text */
					_e( 'Yes, send email', 'buddypress' );
					?>
                </label></td>
            <td class="no"><input type="radio" name="notifications[notification_groups_membership_request]"
                                  id="notification-groups-membership-request-no"
                                  value="no" <?php checked( $group_request, 'no', true ); ?>/><label
                        for="notification-groups-membership-request-no" class="bp-screen-reader-text">
					<?php
					/* translators: accessibility text */
					_e( 'No, do not send email', 'buddypress' );
					?>
                </label></td>
        </tr>
        <tr id="groups-notification-settings-request-completed">
            <td></td>
            <td><?php _ex( 'Your request to join a group has been approved or denied', 'group settings on notification settings page', 'buddypress' ); ?></td>
            <td class="yes"><input type="radio" name="notifications[notification_membership_request_completed]"
                                   id="notification-groups-membership-request-completed-yes"
                                   value="yes" <?php checked( $group_request_completed, 'yes', true ); ?>/><label
                        for="notification-groups-membership-request-completed-yes" class="bp-screen-reader-text">
					<?php
					/* translators: accessibility text */
					_e( 'Yes, send email', 'buddypress' );
					?>
                </label></td>
            <td class="no"><input type="radio" name="notifications[notification_membership_request_completed]"
                                  id="notification-groups-membership-request-completed-no"
                                  value="no" <?php checked( $group_request_completed, 'no', true ); ?>/><label
                        for="notification-groups-membership-request-completed-no" class="bp-screen-reader-text">
					<?php
					/* translators: accessibility text */
					_e( 'No, do not send email', 'buddypress' );
					?>
                </label></td>
        </tr>
        <tr id="groups-notification-settings-user-upload-file">
            <td></td>
            <td><?php _e( 'A member uploads a file to a group you belong to', 'bp-group-documents' ); ?></td>
            <td class="yes"><input type="radio" name="notifications[notification_group_documents_upload_member]"
                                   value="yes" <?php checked( $notification_group_documents_upload_member, 'yes', true ); ?>/>
            </td>
            <td class="no"><input type="radio" name="notifications[notification_group_documents_upload_member]"
                                  value="no" <?php checked( $notification_group_documents_upload_member, 'no', true ); ?>/>
            </td>
        </tr>
        <tr>
            <td></td>
            <td><?php _e( 'A member uploads a file to a group for which you are an moderator/admin', 'bp-group-documents' ); ?></td>
            <td class="yes"><input type="radio" name="notifications[notification_group_documents_upload_mod]"
                                   value="yes" <?php checked( $notification_group_documents_upload_mod, 'yes', true ); ?>/>
            </td>
            <td class="no"><input type="radio" name="notifications[notification_group_documents_upload_mod]"
                                  value="no" <?php checked( $notification_group_documents_upload_mod, 'no', true ); ?>/>
            </td>
        </tr>
        <tr>
            <td></td>
            <td><?php _e( 'Receive notifications of your own posts?', 'bp-ass' ); ?></td>
            <td class="yes"><input type="radio" name="notifications[ass_self_post_notification]" value="yes"
					<?php
					if ( ass_self_post_notification( bp_displayed_user_id() ) ) {
						?>
                        checked="checked" <?php } ?>/></td>
            <td class="no"><input type="radio" name="notifications[ass_self_post_notification]" value="no"
					<?php
					if ( ! ass_self_post_notification( bp_displayed_user_id() ) ) {
						?>
                        checked="checked" <?php } ?>/></td>
        </tr>
        </tbody>
    </table>


	<?php
}

/**
 * Save group notification email settings.
 **/
function hc_custom_update_group_subscribe_settings() {
	global $bp;

	if ( ! bp_is_settings_component() && ! bp_is_current_action( 'notifications' ) ) {
		return false;
	}


	// If the edit form has been submitted, save the edited details.
	if ( isset( $_POST['group-notifications'] ) ) {

		$user_id = bp_loggedin_user_id();

		foreach ( $_POST['group-notifications'] as $group_id => $value ) {
			// Save the setting.
			ass_group_subscription( $value, $user_id, $group_id );
		}
	}

	if ( isset( $_POST['default-group-notifications'] ) ) {
		$user_id = bp_loggedin_user_id();
		$value   = $_POST['default-group-notifications'];

		update_user_meta( $user_id, 'default_group_notifications', $value );
	}

}

add_action( 'bp_actions', 'hc_custom_update_group_subscribe_settings' );

/**
 * Give the user a notice if they are default subscribed to this group (does not work for invites or requests).
 *
 * @param int $group_id ID of the group the member has joined.
 * @param int $user_id  ID of the user who joined the group.
 **/
function hc_custom_join_group_message( $group_id, $user_id ) {

	remove_action( 'groups_join_group', 'ass_join_group_message' );

	if ( bp_loggedin_user_id() != $user_id ) {
		return;
	}

	$status = get_user_meta( $user_id, 'default_group_notifications', true );

	if ( empty( $status ) ) {
		$status = 'no';
		update_user_meta( $user_id, 'default_group_notifications', 'no' );
	}

	ass_group_subscription( $status, $user_id, $group_id );

	bp_core_add_message( __( 'You successfully joined the group. Your group email status is: ', 'bp-ass' ) . ass_subscribe_translate( $status ) );

}

// @codingStandardsIgnoreLine
add_action( 'groups_join_group', 'hc_custom_join_group_message', 2, 2 );

/**
 * Overwrite unsubscribe link in e-mails.
 *
 * @param string   $formatted_tokens Associative pairing of token names (key) and replacement values (value).
 *
 * @param string   $tokens           Associative pairing of unformatted token names (key) and replacement values
 *                                   (value).
 *
 * @param BP_Email $instance         Current instance of the email type class.
 */
function hc_custom_bp_email_set_tokens( $formatted_tokens, $tokens, $instance ) {
	$formatted_tokens['unsubscribe'] = bp_displayed_user_domain() . bp_get_settings_slug() . '/notifications';

	return $formatted_tokens;
}

add_filter( 'bp_email_set_tokens', 'hc_custom_bp_email_set_tokens', 1, 3 );

/**
 * Change group digest unsubscribe link in e-mails.
 *
 * @param string $unsubscribe_message       The unsubscribe message.
 *
 * @param string $userdomain_bp_groups_slug The url containing the userdomain and the groups slug.
 **/
function hc_custom_ass_digest_disable_notifications( $unsubscribe_message, $userdomain_bp_groups_slug ) {
	$userdomain = explode( '/', $userdomain_bp_groups_slug );

	if ( ! isset( $userdomain[4] ) ) {
		return $unsubscribe_message;
	}

	$settings_page = bp_get_settings_slug() . '/notifications';

	// @codingStandardsIgnoreLine
	$unsubscribe_message = '\n\n' . sprintf( __( 'To disable these notifications per group please login and go to: %s where you can change your email settings for each group.', 'bp-ass' ), '<a href="https://{$userdomain[2]}/{$userdomain[3]}/{$userdomain[4]}/{$settings_page}/">' . __( 'My Groups', 'bp-ass' ) . '</a>' );

	return $unsubscribe_message;
}

add_filter( 'ass_digest_disable_notifications', 'hc_custom_ass_digest_disable_notifications', 10, 2 );

/**
 * Add custom BP email footer for HTML emails.
 *
 * We want to override the default {{unsubscribe}} token with something else.
 **/
function hc_custom_ass_bp_email_footer_html_unsubscribe_links() {
	$tokens = buddypress()->ges_tokens;

	if ( ! isset( $tokens['subscription_type'] ) ) {
		return;
	}

	remove_action( 'bp_after_email_footer', 'ass_bp_email_footer_html_unsubscribe_links' );

	if ( isset( $tokens['ges.settings-link'] ) ) {
		$settings_page = $tokens['ges.settings-link'];
	} else {
		$userdomain    = strtok( $tokens['ges.unsubscribe'], '?' );
		$settings_page = $userdomain . '/settings/notifications/';
	}

	$link_format  = '<a href="%1$s" title="%2$s" style="text-decoration: underline;">%3$s</a>';
	$footer_links = array();

	switch ( $tokens['subscription_type'] ) {
		// Self-notifications.
		case 'self_notify':
			$footer_links[] = sprintf(
				$link_format,
				$tokens['ges.settings-link'],
				esc_attr__( 'Once you are logged in, uncheck "Receive notifications of your own posts?".', 'bp-ass' ),
				esc_html__( 'Change email settings', 'bp-ass' )
			);

			break;

		// Everything else.
		case 'sub':
		case 'supersub':
		case 'dig':
		case 'sum':
			$footer_links[] = sprintf(
				$link_format,
				$settings_page,
				esc_attr__( 'Once you are logged in, change your email settings for each group.', 'bp-ass' ),
				esc_html__( 'Change email settings', 'bp-ass' )
			);

			break;
	}

	if ( ! empty( $footer_links ) ) {
		echo implode( ' &middot; ', $footer_links );
	}

	unset( buddypress()->ges_tokens );
}

add_action( 'bp_after_email_footer', 'hc_custom_ass_bp_email_footer_html_unsubscribe_links' );

/**
 * Disable the default subscription settings during group creation.
 */
function hc_custom_disable_subscription_settings_form() {

	if ( ! mla_is_group_committee( bp_get_current_group_id() ) ) {
		remove_action( 'bp_after_group_settings_admin', 'ass_default_subscription_settings_form' );
		remove_action( 'bp_after_group_settings_creation_step', 'ass_default_subscription_settings_form' );
	}
}

add_action( 'bp_after_group_settings_admin', 'hc_custom_disable_subscription_settings_form', 0 );
add_action( 'bp_after_group_settings_creation_step', 'hc_custom_disable_subscription_settings_form', 0 );


/**
 * Set default notification for user on accept or invite.
 *
 * @param int $user_id  ID of the user who joined the group.
 * @param int $group_id ID of the group the member has joined.
 */
function hc_custom_set_notifications_on_accept_invite_or_request( $user_id, $group_id ) {

	$status = get_user_meta( $user_id, 'default_group_notifications', true );

	if ( empty( $status ) ) {
		$status = 'no';
		update_user_meta( $user_id, 'default_group_notifications', 'no' );
	}

	ass_group_subscription( $status, $user_id, $group_id );
}

add_action( 'groups_accept_invite', 'hc_custom_set_notifications_on_accept_invite_or_request', 20, 2 );
add_action( 'groups_membership_accepted', 'hc_custom_set_notifications_on_accept_invite_or_request', 20, 2 );

/**
 * Adds a section for users to set their newsletter settings
 */
function hc_custom_newsletter_settings() {
	global $bp;

	$user_id          = $bp->displayed_user->id;
	$newsletter_optin = get_user_meta( $user_id, 'newsletter_optin', true );
	?>

    <table class="notification-settings" id="groups-notification-settings">
        <thead>
        <tr>
            <th class="icon"></th>
            <th class="title"><h1><?php _e( 'Newsletter', 'group_forum_subscription' ); ?></h1></th>
            <th class="no-email gas-choice"><?php _e( 'Yes', 'buddypress' ); ?></th>
            <th class="weekly gas-choice"><?php _e( 'No', 'buddypress' ); ?></th>
        </tr>
        </thead>

        <tbody>

        <tr>
            <td></td>

            <td>
                Can we send you periodic updates about the Commons?
            </td>

            <td class="no-newsletter gas-choice">
                <input type="radio" name="newsletter-optin" value="yes"
					<?php if ( 'yes' === $newsletter_optin || ! $newsletter_optin ) : ?>
                        checked="checked"
					<?php endif; ?>/>
            </td>

            <td class="yes-newsletter gas-choice">
                <input type="radio" name="newsletter-optin" value="no"
					<?php if ( 'no' === $newsletter_optin ) : ?>
                        checked="checked"
					<?php endif; ?>/>
            </td>

        </tr>
        </tbody>
    </table>
	<?php
}

/**
 * Save group notification email settings.
 **/
function hc_custom_update_newsletter_settings() {
	global $bp;

	if ( ! bp_is_settings_component() && ! bp_is_current_action( 'notifications' ) ) {
		return false;
	}

	if ( isset( $_POST['newsletter-optin'] ) ) {
		$user_id = bp_loggedin_user_id();
		$value   = $_POST['newsletter-optin'];

		update_user_meta( $user_id, 'newsletter_optin', $value );
	}
}

add_action( 'bp_actions', 'hc_custom_update_newsletter_settings' );

/**
 * Add a template notice to warn users when they have no email setting for the current group.
 */
function hc_custom_bpges_add_settings_warning() {
	// Ensure we're on a group homepage.
	if ( ! bp_is_group_home() ) {
		return;
	}

	// Ensure this user is a member of the group.
	if ( ! groups_is_user_member( get_current_user_id(), bp_get_current_group_id() ) ) {
		return;
	}

	// Check for an existing subscription setting.
	$subs = ass_get_subscriptions_for_group( bp_get_current_group_id() );
	foreach ( $subs as $user_id => $type ) {
		if ( get_current_user_id() === $user_id && 'no' !== $type ) {
			return;
		}
	}

	// Check whether this user has disabled this warning.
	$meta_key  = 'hc_custom_bpges_setting_warning_group_ids';
	$group_ids = get_user_meta( get_current_user_id(), $meta_key, true );
	if (
		! empty( $group_ids ) &&
		( [ 0 ] === $group_ids || in_array( bp_get_current_group_id(), $group_ids ) )
	) {
		return;
	}

	// At this point we know we're going to show the warning, so enqueue the script.
	$js_handle  = 'hc-bpges-disable-warning';
	$js_path    = 'includes/js/bpges-disable-warning.js';
	$js_version = filemtime( trailingslashit( plugin_dir_path( __DIR__ ) ) . $js_path );
	wp_enqueue_script( $js_handle, plugins_url( $js_path, __DIR__ ), [], $js_version, true );
	wp_localize_script( $js_handle, 'hc_custom_bpges_setting_warning_group_ids', [ bp_get_current_group_id() ] );

	// Remove filters to preserve markup.
	remove_filter( 'bp_core_render_message_content', 'wp_kses_data', 5 );
	remove_filter( 'bp_core_render_message_content', 'wpautop' );

	// Add notice.
	$settings_href = trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() ) . 'notifications';
	$message       = 'You are not receiving e-mail notifications for this group. To change your default setting or this group\'s e-mail setting, visit your e-mail settings page.<br><br>';
	$links         = [
		sprintf(
			'<a class="button" href="%s">Visit e-mail settings</a><br>',
			$settings_href
		),
		sprintf(
			'<a id="hc-bpges-warning-disable-this" href="%s">Disable this warning for this group only</a>',
			$settings_href
		),
		sprintf(
			'<a id="hc-bpges-warning-disable-all" href="%s">Disable this warning for <strong>all</strong> groups</a>',
			$settings_href
		),
	];

	/**
	 * Instead of bp_core_add_message(), which sends Set-Cookie headers,
	 * just set the template globals to be sure this message only appears on group home.
	 */
	$bp                        = buddypress();
	$bp->template_message      = $message . implode( '<br><br>', $links );
	$bp->template_message_type = 'warning';
}

add_action( 'bp_init', 'hc_custom_bpges_add_settings_warning' );

/**
 * Handle POST requests to admin-ajax.php to disable the BPGES warning.
 */
function hc_custom_bpges_handle_settings_warning_post() {
	$meta_key       = 'hc_custom_bpges_setting_warning_group_ids';
	$old_meta_value = get_user_meta( get_current_user_id(), $meta_key, true );

	if ( isset( $_POST[ $meta_key ] ) && is_array( $_POST[ $meta_key ] ) ) {
		// Disable warning for this group.
		$new_meta_value = array_merge( (array) $_POST[ $meta_key ], (array) $old_meta_value );
	} else {
		// Disable warning for all groups.
		$new_meta_value = [ 0 ];
	}

	update_user_meta( get_current_user_id(), $meta_key, $new_meta_value, $old_meta_value );
}

add_action( 'wp_ajax_hc_custom_bpges_settings_warning', 'hc_custom_bpges_handle_settings_warning_post' );

/**
 * Enqueue jQuery AreYouSure, monitors html forms and alerts users to unsaved changes.
 */
function hc_custom_jquery_are_you_sure() {
	wp_enqueue_script( 'jquery-are-you-sure', trailingslashit( plugins_url() ) . 'hc-custom/includes/js/jquery.are-you-sure.js', array( 'jquery' ) );
}

add_action( 'wp_enqueue_scripts', 'hc_custom_jquery_are_you_sure' );

/**
 * Remove ability for group admins to change member default notification settings.
 **/
function hc_custom_ass_change_all_email_sub() {
	remove_action( 'bp_after_group_manage_members_admin', 'ass_change_all_email_sub' );
}

add_action( 'bp_after_group_manage_members_admin', 'hc_custom_ass_change_all_email_sub', 0 );
