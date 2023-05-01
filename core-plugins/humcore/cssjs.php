<?php
/**
 * Plugin css and js support.
 *
 * @package HumCORE
 * @subpackage Deposits
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the plugin css and js files.
 */
function humcore_deposits_front_cssjs() {

	wp_register_style( 'humcore_deposits_css', plugins_url( 'css/deposits.css', __FILE__ ), '', '011818' );
	wp_enqueue_style( 'humcore_deposits_css' );

	if ( humcore_is_deposit_new_page() || humcore_is_deposit_item_edit() ) {
		wp_enqueue_script( 'plupload', array( 'jquery' ) );

		wp_register_script( 'humcore_retrieve_doi_js', plugins_url( 'js/retrieve-doi.js', __FILE__ ), array( 'jquery' ), '091317', true );
		wp_enqueue_script( 'humcore_retrieve_doi_js' );

		wp_register_script( 'humcore_deposits_js', plugins_url( 'js/deposits.js', __FILE__ ), array( 'jquery' ), '010218', true );
		wp_enqueue_script( 'humcore_deposits_js' );

		wp_register_script( 'humcore_deposit_select2_js', plugins_url( 'js/deposit-select2.js', __FILE__ ), array( 'jquery' ), '010218', true );
		wp_enqueue_script( 'humcore_deposit_select2_js' );

		/*
		wp_register_script( 'humcore_deposits_select2_js', plugins_url( 'select2/dist/js/select2.min.js', __FILE__ ), array( 'jquery' ), '022316', true );
		wp_enqueue_script( 'humcore_deposits_select2_js' );
		wp_register_style( 'humcore_deposits_select2_css', plugins_url( 'select2/dist/css/select2.min.css', __FILE__ ), '', '022316' );
		wp_enqueue_style( 'humcore_deposits_select2_css' );
		*/

		wp_register_script( 'select2_js', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/select2.min.js', array( 'jquery' ), '022416', true );
		wp_enqueue_script( 'select2_js' );
		wp_register_style( 'select2_css', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/css/select2.min.css', '', '022416' );
		wp_enqueue_style( 'select2_css' );

		wp_enqueue_script( 'jquery-ui-dialog', array( 'jquery' ) );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );

	} else {
		wp_register_script( 'humcore_search_js', plugins_url( 'js/search.js', __FILE__ ), array( 'jquery' ), '011718', true );
		wp_enqueue_script( 'humcore_search_js' );
	}

}
add_action( 'wp_enqueue_scripts', 'humcore_deposits_front_cssjs' );
