<?php
/**
 * Custom Changes to BuddyPress Live Preview plugin.
 *
 * @package Hc_Custom
 */


function hc_custom_live_preview_timeout( $timeout ) {

   $timeout = 300;

   return $timeout;
}

add_filter( 'bbp_live_preview_timeout', 'hc_custom_live_preview_timeout' );


function hc_custom_bbp_kses_allowed_tags( $array ) { 
   $allowed_tags = array_merge( $array, array(
		'pre'=> array(),
		'h1'         => array(
			'align' => true,
		),
		'h2'         => array(
			'align' => true,
		),
		'h3'         => array(
			'align' => true,
		),
		'h4'         => array(
			'align' => true,
		),
		'h5'         => array(
			'align' => true,
		),
		'h6'         => array(
			'align' => true,
		),
	));
	
	return $allowed_tags;
   
}

add_filter( 'bbp_kses_allowed_tags', 'hc_custom_bbp_kses_allowed_tags', 10, 1 ); 
