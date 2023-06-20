<?php

function hc_styles_add_admin_mpo_js( $hook ) {

    if('options-reading.php' === $hook) {
		$jtime = filemtime( dirname(__FILE__) . '/js/admin-mpo.js'  );

		wp_enqueue_script('hc_styles_mpo_admin_script', plugins_url('js/admin-mpo.js', __FILE__), array('jquery'), $jtime );
    }
}

add_action('admin_enqueue_scripts', 'hc_styles_add_admin_mpo_js');

function hc_styles_fetch_society() {

	echo Humanities_Commons::$society_id;

 	die();
}
add_action( 'wp_ajax_hc_styles_fetch_society', 'hc_styles_fetch_society' );
add_action( 'wp_ajax_nopriv_hc_styles_fetch_society', 'hc_styles_fetch_society' );
