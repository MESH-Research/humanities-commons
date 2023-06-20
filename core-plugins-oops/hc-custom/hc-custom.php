<?php
/**
 * Plugin Name:     HC Custom
 * Plugin URI:      https://github.com/mlaa/hc-custom
 * Description:     Miscellaneous actions & filters for Humanities Commons.
 * Author:          MLA
 * Author URI:      https://github.com/mlaa
 * Text Domain:     hc-custom
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Hc_Custom
 */

/**
 * BuddyPress actions & filters.
 */
require_once trailingslashit( __DIR__ ) . 'includes/buddypress/bp-core.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress/bp-blogs.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress/bp-groups.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress/bp-members.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress/bp-activity.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress/bp-xprofile.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress/buddypress-functions.php';


/**
 * Plugin actions & filters.
 */
require_once trailingslashit( __DIR__ ) . 'includes/avatar-privacy.php';
require_once trailingslashit( __DIR__ ) . 'includes/bbpress.php';
require_once trailingslashit( __DIR__ ) . 'includes/bbp-live-preview.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress-docs.php';
require_once trailingslashit( __DIR__ ) . 'includes/bp-groupblog.php';
require_once trailingslashit( __DIR__ ) . 'includes/bp-group-documents.php';
require_once trailingslashit( __DIR__ ) . 'includes/bp-event-organiser.php';
require_once trailingslashit( __DIR__ ) . 'includes/bp-attachment-xprofile.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress-followers.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress-group-email-subscription.php';
require_once trailingslashit( __DIR__ ) . 'includes/buddypress-more-privacy-options.php';
require_once trailingslashit( __DIR__ ) . 'includes/cbox-auth.php';
require_once trailingslashit( __DIR__ ) . 'includes/elasticpress-buddypress.php';
require_once trailingslashit( __DIR__ ) . 'includes/humcore.php';
require_once trailingslashit( __DIR__ ) . 'includes/mashsharer.php';
require_once trailingslashit( __DIR__ ) . 'includes/siteorigin-panels.php';
require_once trailingslashit( __DIR__ ) . 'includes/wp-to-twitter.php';


/**
 * Miscellaneous actions & filters.
 */
require_once trailingslashit( __DIR__ ) . 'includes/mla-groups.php';
require_once trailingslashit( __DIR__ ) . 'includes/user-functions.php';
