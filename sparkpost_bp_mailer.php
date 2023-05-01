<?php
/**
 * Sparkpost BP PHPMailer override
 *
 * @package Mail
 * @subpackage MLA
 */

/**
 * Plugin Name: Sparkpost BP PHPMailer override
 * Description: Allow Buddypress to send HTML emails using Sparkpost.
 * Version: 1.0
 * Author: MLA
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

function sp_bp_phpmailer_class_override() {
	require_once dirname( __FILE__ ) . '/classes/class-sp-bp-mailer.php';
error_log('*******************************mailer**********************');
	return 'SP_BP_PHPMailer';
}

add_filter( 'bp_send_email_delivery_class', 'sp_bp_phpmailer_class_override');
