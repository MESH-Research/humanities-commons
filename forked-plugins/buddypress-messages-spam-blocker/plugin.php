<?php

// recreate pot file? excute this in the plugin's directory  
// xgettext --language=PHP --from-code=utf-8 --keyword=__ *.php -o languages/buddypress-messages-spamblocker.pot
// Load translations and text domain
add_action('init', 'bps_load_textdomain');

// We will do the check before compose form is shown
add_filter('messages_screen_compose', 'bps_bp_spam_stop');

function bps_load_textdomain() {
    load_plugin_textdomain('buddypress-messages-spamblocker', false, dirname(plugin_basename(__FILE__)) . "/languages/");
}

/**
 * Checks for bp message spam
 * @global type $wpdb
 */
function bps_bp_spam_stop() {
    $current_user = wp_get_current_user();
    $member_types = (array)bp_get_member_type( $current_user->ID, false );
    if ( empty( $member_types ) || ( 1 == count( $member_types ) && in_array( 'hc', $member_types ) ) ) {
        $society_member = false;
    } else {
        $society_member = true;
    }
    if ( ! user_can( $current_user, 'edit_users' ) && ! $society_member ) {
        $abort = false;
        $timeDiff = time() - strtotime($current_user->user_registered);
        $hours = apply_filters('buddypress_messages_spamblocker_newMembersWaitingPeriod', 24);

        if ($timeDiff < (60 * 60 * $hours)) {
            //bp_core_add_message(sprintf(__('We want to protect other users from spam. New members are only allowed to send messages to other users when their registration is not older than %d hours. Please wait until this time is over and then feel free to write messages to other members!', 'buddypress-messages-spamblocker'), $hours), 'error');
            bp_core_add_message(sprintf(__('Because we want to protect our community from spam, new members have to wait %d hours before sending messages. Please try again tomorrow.', 'buddypress-messages-spamblocker'), $hours), 'error');
            $abort = true;
        } else {
            // exclude friends from spam mechanism
            if (function_exists(friends_get_friend_user_ids)) {
                $friendsArray = friends_get_friend_user_ids($current_user->ID);
            } else {
                $friendsArray = array();
            }

            // last 5 Minutes max 6 messages
            if (!bps_bp_spam_stop_helper_check(5, apply_filters('buddypress_messages_spamblocker_5m', 6), $friendsArray)) {
                $abort = true;
            }
            // last 10 Minutes max 10 messages
            else if (!bps_bp_spam_stop_helper_check(10, apply_filters('buddypress_messages_spamblocker_10m', 10), $friendsArray)) {
                $abort = true;
            }
            // last 30 Minutes max 20 messages
            else if (!bps_bp_spam_stop_helper_check(30, apply_filters('buddypress_messages_spamblocker_30m', 20), $friendsArray)) {
                $abort = true;
            }
            // last 60 Minutes max 30 messages
            else if (!bps_bp_spam_stop_helper_check(60, apply_filters('buddypress_messages_spamblocker_60m', 30), $friendsArray)) {
                $abort = true;
            }
            // last 12h Minutes max 35 messages
            else if (!bps_bp_spam_stop_helper_check((60 * 12), apply_filters('buddypress_messages_spamblocker_12h', 35), $friendsArray)) {
                $abort = true;
            }
            // last 24h Minutes max 40 messages
            else if (!bps_bp_spam_stop_helper_check((60 * 24), apply_filters('buddypress_messages_spamblocker_24d', 40), $friendsArray)) {
                $abort = true;
            }
            // last 48h Minutes max 50 messages
            else if (!bps_bp_spam_stop_helper_check((60 * 48), apply_filters('buddypress_messages_spamblocker_48d', 50), $friendsArray)) {
                $abort = true;
            }

            if ($abort) {
                //bp_core_add_message(__('We want to avoid SPAM. You are only allowed to start a limited number of new conversations in a given period. You can add your recipients as friends. There are no restrictions for new conversations you start with your friends!', 'buddypress-messages-spamblocker'), 'error');
                bp_core_add_message(__('Because we want to protect our community from spam, we only let members start a limited number of new conversations in a given period. Please try again later.', 'buddypress-messages-spamblocker'), 'error');
            }
        }
        // Check results
        if ($abort) {
            global $bp;
            $url = bp_loggedin_user_domain() . bp_get_messages_slug();
            $url = apply_filters('buddypress_messages_spamblocker_blockedURL', $url);
            wp_redirect($url);
            exit;
        }
    }
}

/**
 * Returns true if everything is ok.
 * @global type $wpdb
 * @param type $minutes
 * @param type $max
 * @param type $friendsList
 * @return boolean
 */
function bps_bp_spam_stop_helper_check($minutes, $max, $friendsList) {
    global $wpdb;
    global $bp;
    $current_user = wp_get_current_user();
    $friendsArray = $friendsList;
    // exclude own user, too
    $friendsArray[] = $current_user->ID;
    $sql_query = ' 
        SELECT 
            COUNT(*) as Count
        FROM 
            ' . $bp->messages->table_name_messages . ' as m,
            ' . $bp->messages->table_name_recipients . ' as r
        WHERE
            r.thread_id = m.thread_id
        AND m.sender_id = ' . $current_user->ID . '
            
        AND m.date_sent > "' . date("Y-m-d H:i:s", (time() - ($minutes * 60))) . '"
        AND r.user_id NOT in (' . implode(", ", $friendsArray) . ')';


    $result = $wpdb->get_results($sql_query);
    if ($result[0]->Count >= $max) {
        return false;
    } else {
        return true;
    }
}

