<?php

/**
 * google-analytics-async tries to handle form submissions even when the form has nothing to do with that plugin's settings.
 * stop it, check if the form needs handling, call that ourselves if so. otherwise allow user to proceed without interruption
 */
function hcommons_prevent_gaa_submit_hijack() {
	global $google_analytics_async;

	remove_action( 'admin_init', array( $google_analytics_async, 'handle_page_requests' ) );

	if ( isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( $_SERVER['REQUEST_URI'], 'page=google-analytics' ) ) {

		// code inside this block adapted from Google_Analytics_Async::handle_page_requests(),
		if ( isset( $_POST['submit'] ) ) {
			if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'submit_settings_network' ) ) {
				//save network settings
				$google_analytics_async->save_options( array('track_settings' => $_POST), 'network' );

				wp_redirect( add_query_arg( array( 'page' => 'google-analytics', 'dmsg' => urlencode( __( 'Changes were saved!', $google_analytics_async->text_domain ) ) ), 'settings.php' ) );
				exit;
			} elseif ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'submit_settings' ) ) {
				//save settings
				$google_analytics_async->save_options( array('track_settings' => $_POST) );

				wp_redirect( add_query_arg( array( 'page' => 'google-analytics', 'dmsg' => urlencode( __( 'Changes were saved!', $google_analytics_async->text_domain ) ) ), 'options-general.php' ) );
				exit;
			}
		}

		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			$privacy_text = __( "This website uses Google Analytics to track website traffic. Collected data is processed in such a way that visitors cannot be identified.", $google_analytics_async->text_domain );
			wp_add_privacy_policy_content('Google Analytics +', $privacy_text);
		}

	}
}
add_action( 'admin_init', 'hcommons_prevent_gaa_submit_hijack', 5 ); // before the original action has run, so we can cancel it

function modify_sitewide_plugins($value) {
    global $current_blog;

    if( '1000642' === $current_blog->blog_id ) {
        unset($value['google-analytics-async/google-analytics-async.php']);
    }

    return $value;
}
add_filter('site_option_active_sitewide_plugins', 'modify_sitewide_plugins');
