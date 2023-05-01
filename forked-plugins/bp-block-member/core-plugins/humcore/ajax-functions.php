<?php
/**
 * Ajax upload functions.
 *
 * @package HumCORE
 * @subpackage Deposits
 */

/**
 * Handle ajax chunked file uploads.
 */
function humcore_upload_handler() {

	global $fedora_api;

	$time    = current_time( 'mysql' );
	$y       = substr( $time, 0, 4 );
	$m       = substr( $time, 5, 2 );
	$yyyy_mm = "$y/$m";

	require_once dirname( __FILE__ ) . '/PluploadHandler.php';

	PluploadHandler::no_cache_headers();
	PluploadHandler::cors_headers();
	$upload_status = PluploadHandler::handle(
		array(
			'target_dir'       => $fedora_api->temp_dir . '/' . $yyyy_mm . '/',
			// 'allow_extensions' => 'cr2,crw,csv,dng,doc,docx,f4v,flv,gif,gz,htm,html,jpeg,jpg,mov,mp3,mp4,nef,odp,ods,odt,ogg,pdf,png,ppt,pptx,pps,psd,rdf,rtf,sxc,sxi,sxw,tar,tiff,txt,tsv,wav,wpd,xls,xlsx,xml,zip',
			//'allow_extensions' => 'csv,doc,docx,f4v,flv,gif,gz,htm,html,jpeg,jpg,mov,mp3,mp4,odp,ods,odt,ogg,pdf,png,ppt,pptx,pps,psd,rdf,rtf,sxc,sxi,sxw,tar,tiff,txt,tsv,wav,wpd,xls,xlsx,xml,zip',
			'allow_extensions' => 'csv,doc,docx,f4v,flv,gif,htm,html,jpeg,jpg,mov,mp3,mp4,odp,ods,odt,ogg,pdf,png,ppt,pptx,pps,psd,rdf,rtf,sxc,sxi,sxw,tiff,txt,tsv,wav,wpd,xls,xlsx,xml',
		)
	);

	if ( ! $upload_status ) {
		die(
			json_encode(
				array(
					'OK'    => 0,
					'error' => array(
						'code'    => PluploadHandler::get_error_code(),
						'message' => PluploadHandler::get_error_message(),
					),
				)
			)
		);
	} else {
		die( json_encode( array( 'OK' => 1 ) ) );
	}
}
add_action( 'wp_ajax_humcore_upload_handler', 'humcore_upload_handler' );
