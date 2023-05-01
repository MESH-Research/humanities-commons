<?php
/**
 * MLA Allowed Tags
 *
 * Allow iframe, embed and script tags in post content for editor and administrator. Allow class attribute on strong tag in title.
 *
 */

/**
 * Plugin Name: MLA Allowed Tags
 * Description: Allow iframe, embed and script tags in post content for editor and administrator. Allow class attribute on strong tag in title.
 * Version: 1.0
 * Author: MLA
 */

/**
 * Allow iframe, embed and script tags in post content for editor and administrator. Allow class attribute on strong tag in title.
 *
 * @param array $allowed_tags
 * @param string $context
 * @return array Conditionally modified tags array.
 */
function mla_allow_multisite_extra_tags( $allowed_tags, $context ) {

	if ( function_exists( 'is_multisite' ) && is_multisite() && is_super_admin() ) {
		return $allowed_tags;
	} else if ( ! current_user_can( 'editor' ) && ! current_user_can( 'administrator' ) ) {
		return $allowed_tags;
	}

	$multisite_extra_tags = array();

	if ( 'post' === $context ) {

		$multisite_extra_tags['iframe'] = array(
			'src' => true,
			'width' => true,
			'height' => true,
			'align' => true,
			'class' => true,
			'name' => true,
			'id' => true,
			'frameborder' => true,
			'seamless' => true,
			'srcdoc' => true,
			'sandbox' => true,
			'allowfullscreen' => true
		);
		$multisite_extra_tags['embed'] = array(
			'src' => true,
			'type' => true,
			'allowfullscreen' => true,
			'allowscriptaccess' => true,
			'height' => true,
			'width' => true
		);
		$multisite_extra_tags['script'] = array(
			'language' => true,
			'type' => true
		);
		$multisite_extra_tags['a'] = array(
			'class' => true,
			'href' => true,
			'rel' => true,
			'target' => true,
			'id' => true
		);

	} else if ( 'title_save_pre' === $context ) {

		$multisite_extra_tags['strong'] = array(
			'class' => true
		);

	}

    return array_merge( $allowed_tags, $multisite_extra_tags );

}
add_filter( 'wp_kses_allowed_html', 'mla_allow_multisite_extra_tags', 10, 2 );

/**
 * Strip cdata within scripts that may have been added by entering the visual editor.
 *
 * @param string $string Post content.
 * @param array $allowed_html
 * @param array $allowed_portocols
 * @return string Conditionally modified post content.
 */
function mla_strip_cdata( $string, $allowed_html, $allowed_protocols ) {

        if ( function_exists( 'is_multisite' ) && is_multisite() && is_super_admin() ) {
                return $string;
        } else if ( ! current_user_can( 'editor' ) && ! current_user_can( 'administrator' ) ) {
                return $string;
        }

	$clean_string = preg_replace(
		'~(<script\s*[^>]*>\s*)(//\s*<!\[CDATA\[|)\s*(.*?)\s*(//\s*]]>|)(\s*<\/script>)~imsx',
		'$1$3$5',
		$string
	);
	if ( empty( $clean_string ) ) {
		return $string;
	} else {
		return $clean_string;
	}

}
add_filter( 'pre_kses', 'mla_strip_cdata', 10, 3 );
