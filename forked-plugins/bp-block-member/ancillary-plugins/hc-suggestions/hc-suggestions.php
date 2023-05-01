<?php
/**
 * Plugin Name:     HC Suggestions
 * Plugin URI:      https://github.com/mlaa/hc-suggestions
 * Description:     Widget to suggest content to members based on selected terms.
 * Author:          MLA
 * Author URI:      https://github.com/mlaa
 * Text Domain:     hc-suggestions
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         HC_Suggestions
 */

/**
 * Main file
 */

/**
 * Register widget
 */
add_action(
	'widgets_init', function() {
		require_once trailingslashit( __DIR__ ) . 'classes/class-hc-suggestions-widget.php';
		register_widget( 'HC_Suggestions_Widget' );
	}
);

/**
 * Register REST controller
 */
add_action(
	'rest_api_init', function() {
		require_once trailingslashit( __DIR__ ) . 'classes/class-hc-suggestions-rest-controller.php';
		$controller = new HC_Suggestions_REST_Controller;
		$controller->register_routes();
	}
);

/**
 * Register template stack
 */
add_action(
	'bp_loaded', function () {
		bp_register_template_stack(
			function() {
				return trailingslashit( __DIR__ ) . 'templates/';
			}
		);
	}
);

/**
 * Enqueue scripts
 */
add_action(
	'wp_enqueue_scripts', function () {
		wp_enqueue_script( 'hc-suggestions', trailingslashit( plugins_url() ) . 'hc-suggestions/public/js/hc-suggestions.js' );
		wp_localize_script( 'hc-suggestions', 'wpApiSettings', [ 'nonce' => wp_create_nonce( 'wp_rest' ) ] );

		wp_enqueue_style( 'hc-suggestions', trailingslashit( plugins_url() ) . 'hc-suggestions/public/css/hc-suggestions.css' );
	}
);
