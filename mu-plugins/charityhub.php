<?php

/**
 * charityhub saves its custom options in a file inside the theme directory.
 * when that happens, filter get_template_dir() to return a writeable dir instead.
 * see charityhub/include/gdlr-admin-option.php gdlr_generate_style_custom()
 */
function hcommons_filter_charityhub_template_directory( $dir ) {
	foreach ( debug_backtrace() as $bt ) {
		if ( isset( $bt['function'] ) && 'gdlr_generate_style_custom' === $bt['function'] ) {
			$dir = wp_get_upload_dir()['basedir'];
			// actual css files are inside a hardcoded dir, make sure it exists
			mkdir( trailingslashit( $dir ) . 'stylesheet' );
			break;
		}
	}
	return $dir;
}
add_filter( 'template_directory', 'hcommons_filter_charityhub_template_directory' );

/**
 * other half of hcommons_filter_charityhub_template_directory():
 * use the filtered stylesheet path when enqueueing.
 */
function hcommons_filter_charityhub_enqueue_scripts( $scripts ) {
	$path = 'stylesheet/style-custom' . get_current_blog_id() . '.css';
	foreach ( $scripts['style'] as &$url ) {
		if ( strpos( $url, $path ) !== false ) {
			$url = trailingslashit(  wp_get_upload_dir()['baseurl'] ) . $path;
		}
	}
	return $scripts;
}
add_filter( 'gdlr_enqueue_scripts', 'hcommons_filter_charityhub_enqueue_scripts', 20 );
