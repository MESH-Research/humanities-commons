<?php
/**
 * Customizations to BuddyPress Groups.
 *
 * @package Hc_Custom
 */

/**
 * Enqueue jQuery UI dialog and convert template notices to modal dialogs.
 */
function hc_custom_convert_template_notices_to_dialogs() {
	$bp         = buddypress();
	$js_handle  = 'hc-custom-template-notice-dialogs';
	$js_path    = 'includes/js/template-notice-dialogs.js';
	$js_version = filemtime( trailingslashit( plugin_dir_path( dirname( __DIR__ ) ) ) . $js_path );
	$js_vars    = [
		'template_message_type' => ucfirst( $bp->template_message_type ),
	];
	wp_enqueue_script( $js_handle, plugins_url( $js_path, dirname( __DIR__ ) ), [ 'jquery-ui-dialog' ], $js_version, true );
	wp_localize_script( $js_handle, 'hc_custom_template_notice_dialogs_vars', $js_vars );
	wp_enqueue_style( 'wp-jquery-ui-dialog' );
}
add_action( 'wp_enqueue_scripts', 'hc_custom_convert_template_notices_to_dialogs' );

/**
 * Enlarge cover images to better fit large displays.
 *
 * @param array $wh An associative array containing the width and height values.
 */
function hc_custom_enlarge_cover_images( $wh ) {
	$wh['width']  = 1250;
	$wh['height'] = 320;
	return $wh;
}
add_filter( 'bp_attachments_get_cover_image_dimensions', 'hc_custom_enlarge_cover_images' );

/**
 * Inject BP_Email into wp_mail.
 *
 * @param array $args Mail args.
 * @return array
 */
function hcommons_filter_wp_mail( $args ) {
	// TODO extract extract()
	// @codingStandardsIgnoreLine
	extract( $args );

	// Replace default footer to remove "unsubscribe" since that isn't handled for non-bp-email types.
	add_action( 'bp_before_email_footer', 'ob_start', 999, 0 );
	add_action( 'bp_after_email_footer', 'ob_get_clean', -999, 0 );
	add_action( 'bp_after_email_footer', 'hcommons_email_footer' );

	// Load template markup.
	ob_start();
	add_filter( 'bp_locate_template_and_load', '__return_true' );
	bp_locate_template( 'assets/emails/single-bp-email.php', true, false );
	remove_filter( 'bp_locate_template_and_load', '__return_true' );
	$template = ob_get_contents();
	ob_end_clean();

	$args['message'] = bp_core_replace_tokens_in_text(
		$template, [
			'content'        => make_clickable( nl2br( $message ) ),
			'recipient.name' => 'there', // Since we don't know the user's actual name.
		]
	);

	// Wp core sets headers to a string value joined by newlines for e.g. comment notifications.
	// Most plugins use/keep the array set by apply_filter( 'wp_mail' ).
	// Cast to array.
	if ( is_string( $args['headers'] ) ) {
		$args['headers'] = explode( "\n", $args['headers'] );
	}
	// Remove existing content-type header if present.
	$args['headers'] = array_filter(
		$args['headers'], function( $v ) {
			return strpos( strtolower( $v ), 'content-type' ) === false;
		}
	);
	// Set html content-type.
	$args['headers'][] = 'Content-Type: text/html';

	// Clean up.
	remove_action( 'bp_before_email_footer', 'ob_start', 999, 0 );
	remove_action( 'bp_after_email_footer', 'ob_get_clean', -999, 0 );
	remove_action( 'bp_after_email_footer', 'hcommons_email_footer' );

	return $args;
}
add_filter( 'wp_mail', 'hcommons_filter_wp_mail' );

/**
 * Used in hcommons_filter_wp_mail().
 */
function hcommons_email_footer() {
	$settings = bp_email_get_appearance_settings();
	echo $settings['footer_text'];
}

/**
 * Sometimes we don't want to use our html filter (e.g. bbpress has its own),
 * but there's no way to tell inside wp_mail when that's the case - this is a workaround.
 */
function hcommons_unfilter_wp_mail() {
	remove_filter( 'wp_mail', 'hcommons_filter_wp_mail' );
}
add_action( 'bbp_pre_notify_subscribers', 'hcommons_unfilter_wp_mail' );
add_action( 'bbp_pre_notify_forum_subscribers', 'hcommons_unfilter_wp_mail' );
// No action available for this one, so abuse a filter instead.
add_filter(
	'newsletters_execute_mail_message', function( $message ) {
		hcommons_unfilter_wp_mail();
		return $message;
	}
);
