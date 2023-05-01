<?php
/**
 * Plugin Name: HC Styles
 * Plugin URI: https://github.com/mlaa/hc-styles
 * Description: Styles, templates, badges and more for Humanities Commons.
 *
 * Depends on the humanities-commons plugin: https://github.com/mlaa/humanities-commons
 */

namespace Humanities_Commons\Plugin\HC_Styles;

require_once( __DIR__ . '/vendor/autoload.php' );
require_once( __DIR__. '/includes/buddypress-more-privacy-options.php' );

// initialize badges by instantiating
$Badges = new Badges;

// initialize template override functionality by instantiating
$Template = new Template;

$theme = wp_get_theme();

$theme_name = strtolower( $theme->get( 'Name' ) );

if('dispatch' === $theme_name ) {
  wp_register_style( 'hc-styles-dispatch', plugins_url( '/hc-styles/css/dispatch-override.css' ) );
  wp_enqueue_style( 'hc-styles-dispatch' );
}

/* This is not in prod, let's not break anything by deploying this
add_filter( 'comment_form_defaults', function ( $args ) {
	// i.e. different themes may have different form structures.
	// 15 zine uses comment-form which is not using hte hook system so not affected.
	$args['comment_notes_before'] = "<p class=\"comment-notes\"><span id=\"email-notes\">Your e-mail address will not be published.</span> Required fields are marked <span class=\"required\">*</span>.</p>";
	return $args;
} );
*/
