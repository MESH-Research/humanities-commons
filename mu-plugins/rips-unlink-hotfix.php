<?php
/**
 * Fix for https://blog.ripstech.com/2018/wordpress-file-delete-to-code-execution/
 */

add_filter( 'wp_update_attachment_metadata', 'rips_unlink_tempfix' );

function rips_unlink_tempfix( $data ) {
	if( isset($data['thumb']) ) {
		$data['thumb'] = basename($data['thumb']);
	}

	return $data;
}
