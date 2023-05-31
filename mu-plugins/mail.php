<?php

/*
  Override e-mail FROM address globally across network.
  ---
  Use GLOBAL_SMTP_FROM constant if set; otherwise use the multisite admin's
  e-mail address.
*/

add_filter('wp_mail_from', 'override_mail_from_address_globally');

function override_mail_from_address_globally ($old_from_address) {
  return ( defined('GLOBAL_SMTP_FROM') ) ? GLOBAL_SMTP_FROM : get_site_option('admin_email', 'wordpress@localhost', true);
}


// Make WordPress set the MAIL FROM envelope header. This is useful so that the
// the local MTA will use the supplied FROM address when relaying the message.
// http://www.slashslash.de/2013/04/properly-sending-mail-via-eximsendmail-from-wordpress-and-others/

add_action('phpmailer_init', 'mail_add_sender');

function mail_add_sender(&$phpmailer) {
  $phpmailer->Sender = $phpmailer->From;
}


// Disable e-mails sent to users when their e-mail address or password changes.
// This behavior is new in WP 4.3, and seems to be triggered when accounts are
// changed via our custom auth plugin. Disabling since our member info and
// notifications are handled externally.
//
// https://make.wordpress.org/core/2015/07/28/passwords-strong-by-default/

add_filter('send_email_change_email', '__return_false');
add_filter('send_password_change_email', '__return_false');

/**
 * remove urls in comments from comment notification emails so that we don't trigger spam filters
 */
function hcommons_filter_comment_notification_text( $text ) {
	$delimiter = 'You can see all comments on this post here:';
	$exploded_text = explode( $delimiter, $text );

	// http://stackoverflow.com/a/6165666/700113
	$pattern = "/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))/";
	$replace = '<url removed>';
	$exploded_text[0] = preg_replace( $pattern, $replace, $exploded_text[0] );

	$text = $exploded_text[0] . $delimiter . $exploded_text[1];

	return $text;
}
add_filter( 'comment_notification_text', 'hcommons_filter_comment_notification_text' );

?>
