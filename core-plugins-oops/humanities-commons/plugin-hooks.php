<?php
/**
 * Functions to alter the behavior of Commons plugins site-wide.
 */

/**
 * Removes the 'SimpleMag theme is deactivated, please also deactivate the SimpleMag Addons plugin.'
 * alert from the dashboard.
 * 
 * We keep the plugin activated network-wide so that users of the SimpleMag theme don't have to activate
 * it themselves.
 *
 * @see plugins/simplemag-addons/init.php
 * @see https://github.com/MESH-Research/hc-admin-docs-support/issues/114
 */
function hc_suppress_simplemag_alert() {
	remove_action( 'admin_notices', 'simplemag_deactivated_admin_notice', 999 );
}
add_action( 'admin_notices', 'hc_suppress_simplemag_alert', 10, 0 );