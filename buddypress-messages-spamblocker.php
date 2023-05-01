<?php

/**
 * Plugin Name: Buddypress Messages Spam Blocker
 * Plugin URI: http://ifs-net.de
 * Description: Fight mass mailings and spam inside buddypress messages
 * Version: 2.5
 * Author: Florian Schiessl
 * Author URI: http://ifs-net.de
 * License: GPL2
 * Text Domain: buddypress-messages-spamblocker
 * Domain Path: /languages/
 */
/* Only load code that needs BuddyPress to run once BP is loaded and initialized. */
function bps_bp_spam_stop_init() {
    require( dirname(__FILE__) . '/plugin.php' );
}

add_action('bp_include', 'bps_bp_spam_stop_init');