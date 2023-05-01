<?php
/*
 * Let's have it both ways.
 */

class Humcore_Theme_Compatibility {

	/**
	 * We'll need to call get_header unless we are using a certain theme.
	 *
	 */
	public static function get_header( $name = null ) {

		// Get theme object
		$theme = wp_get_theme();
		if ( 'levitin' === get_stylesheet() ) {
			return;
		} else {
			get_header( $name );
			return;
		}

	}

	/**
	 * We'll need to call get_sidebar unless we are using a certain theme.
	 *
	 */
	public static function get_sidebar( $name = null ) {

		// Get theme object
		$theme = wp_get_theme();
		if ( 'levitin' === get_stylesheet() ) {
			return;
		} else {
			get_sidebar( $name );
			return;
		}

	}

	/**
	 * We'll need to call get_footer unless we are using a certain theme.
	 *
	 */
	public static function get_footer( $name = null ) {

		// Get theme object
		$theme = wp_get_theme();
		if ( 'levitin' === get_stylesheet() ) {
			return;
		} else {
			get_footer( $name );
			return;
		}

	}

}
