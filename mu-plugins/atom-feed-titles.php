<?php

/**
 * Provide full HTML in Atom feed post <title>s.
 */

function better_atom_titles( $title ) {
	remove_filter( 'the_title_rss', 'strip_tags' );
	remove_filter( 'the_title_rss', 'esc_html' );
}
add_filter( 'atom_head', 'better_atom_titles' );
