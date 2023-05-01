<?php
/**
 * Deposit Edit transaction.
 *
 * @package HumCORE
 * @subpackage Deposits
 */

/**
 * Check for matching pid and post.
 * Determine if file changed.
 * Make a usable unique filename.
 * Generate a thumb if necessary.
 * Change 2 objects in Fedora, 1 document in Solr and 2 posts.
 * Prepare the metadata sent to Fedora and Solr.
 * Determine post date, status and necessary activity.
 * Create XML needed for the fedora objects.
 * Set object terms for subjects.
 * Add any new keywords and set object terms for tags.
 * Extract text first if small. If Tika errors out we'll index without full text.
 * Index the deposit content and metadata in Solr.
 * Update the aggregator post.
 * Add to metadata and store in post meta.
 * Prepare an array of post data for the resource post.
 * Update the resource post.
 * Upload the MODS file to the Fedora server temp file storage.
 * Update the descMetadata datastream for the aggregator object.
 * Upload the deposited file to the Fedora server temp file storage.
 * Update the CONTENT datastream for the resource object.
 * Modify the resource object metadata datastream.
 * Upload the thumb to the Fedora server temp file storage if necessary.
 * Update the THUMB datastream for the resource object if necessary.
 * Handle doi metadata changes.
 * Re-index larger text based deposits in the background.
 */
function humcore_deposit_edit_file() {

	if ( empty( $_POST ) ) {
		return false;
	}

	global $fedora_api, $solr_client;
	$tika_client = \Vaites\ApacheTika\Client::make( '/srv/www/commons/current/vendor/tika/tika-app-1.16.jar' ); // app mode

	$curr_val = $_POST;
	//TODO post must exist and pid in post meta must match
	$deposit_post_id    = sanitize_text_field( $curr_val['deposit_post_id'] );
	$deposit_post       = get_post( $deposit_post_id );
	$resource_post_args = array(
		'post_parent'    => $deposit_post_id,
		'post_type'      => 'humcore_deposit',
		'posts_per_page' => 1,
	);
	$resource_post      = get_posts( $resource_post_args );

	$deposit_post_metadata = json_decode( get_post_meta( $deposit_post_id, '_deposit_metadata', true ), true );
	$deposit_file_metadata = json_decode( get_post_meta( $deposit_post_id, '_deposit_file_metadata', true ), true );
	$file_metadata         = $deposit_file_metadata;
	$fileloc               = $deposit_file_metadata['files'][0]['fileloc'];
	$filetype              = $deposit_file_metadata['files'][0]['filetype'];
	$filename              = $deposit_file_metadata['files'][0]['filename'];
	$filesize              = $deposit_file_metadata['files'][0]['filesize'];
	$prev_pathname         = pathinfo( $deposit_file_metadata['files'][0]['fileloc'], PATHINFO_DIRNAME );
	$full_prev_tempname    = pathinfo( $deposit_file_metadata['files'][0]['fileloc'], PATHINFO_BASENAME );
	$prev_tempname         = str_replace( '.' . $deposit_file_metadata['files'][0]['filename'], '', $full_prev_tempname );
	$mods_file             = $prev_pathname . '/' . $prev_tempname . '.MODS.' . $filename . '.xml';
	$upload_error_message  = '';
	if ( empty( $curr_val['selected_file_name'] ) ) {
		// Do something!
		$upload_error_message = __( 'No file was uploaded! Please press "Select File" and upload a file first.', 'humcore_domain' );
	} elseif ( 0 == $curr_val['selected_file_size'] ) {
		$upload_error_message = sprintf(
			__( '%1$s appears to be empty, please choose another file.', 'humcore_domain' ),
			sanitize_file_name( $curr_val['selected_file_name'] )
		);
	}
	if ( ! empty( $upload_error_message ) ) {
		echo '<div id="message" class="info"><p>' . $upload_error_message . '</p></div>'; // XSS OK.
		return false;
	}
	$user       = get_user_by( 'ID', sanitize_text_field( $deposit_post_metadata['submitter'] ) );
	$society_id = $deposit_post_metadata['society_id'];

	// Single file uploads at this point.
	$tempname     = sanitize_file_name( $curr_val['selected_temp_name'] );
	$file_changed = false;
	if ( $prev_tempname !== $tempname ) {
		$file_changed         = true;
		$time                 = current_time( 'mysql' );
		$y                    = substr( $time, 0, 4 );
		$m                    = substr( $time, 5, 2 );
		$yyyy_mm              = "$y/$m";
		$fileloc              = $fedora_api->temp_dir . '/' . $yyyy_mm . '/' . $tempname;
		$filename             = strtolower( sanitize_file_name( $curr_val['selected_file_name'] ) );
		$filesize             = sanitize_text_field( $curr_val['selected_file_size'] );
		$renamed_file         = pathinfo( $deposit_file_metadata['files'][0]['fileloc'], PATHINFO_BASENAME );
		$mods_file            = $fileloc . '.MODS.' . $filename . '.xml';
		$filename_dir         = pathinfo( $renamed_file, PATHINFO_DIRNAME );
		$datastream_id        = 'CONTENT';
		$thumb_datastream_id  = 'THUMB';
		$generated_thumb_name = '';
		$renamed_file         = $fileloc . '.' . $filename;
	}
	if ( $file_changed ) {
		// Make a usable unique filename.
		if ( file_exists( $fileloc ) ) {
			$file_rename_status = rename( $fileloc, $renamed_file );
		}
		// TODO handle file error.
		$check_filetype = wp_check_filetype( $filename, wp_get_mime_types() );
		$filetype       = $check_filetype['type'];

		//TODO fix thumbs if ( preg_match( '~^image/|/pdf$~', $check_filetype['type'] ) ) {
		if ( preg_match( '~^image/$~', $filetype ) ) {
			$thumb_image = wp_get_image_editor( $renamed_file );
			if ( ! is_wp_error( $thumb_image ) ) {
				$current_size = $thumb_image->get_size();
				$thumb_image->resize( 150, 150, false );
				$thumb_image->set_quality( 95 );
				$thumb_filename       = $thumb_image->generate_filename( 'thumb', $filename_dir . '/' . $yyyy_mm . '/', 'jpg' );
				$generated_thumb      = $thumb_image->save( $thumb_filename, 'image/jpeg' );
				$generated_thumb_path = $generated_thumb['path'];
				$generated_thumb_name = str_replace( $tempname . '.', '', $generated_thumb['file'] );
				$generated_thumb_mime = $generated_thumb['mime-type'];
			} else {
				echo 'Error - thumb_image : ' . esc_html( $thumb_image->get_error_code() ) . '-' .
						esc_html( $thumb_image->get_error_message() );
				humcore_write_error_log(
					'error', sprintf(
						'*****HumCORE Deposit Edit Error***** - thumb_image : %1$s-%2$s',
						$thumb_image->get_error_code(), $thumb_image->get_error_message()
					)
				);
			}
		}
	}

	humcore_write_error_log( 'info', 'HumCORE Deposit Edit started' );
	if ( $file_changed ) {
		humcore_write_error_log( 'info', 'HumCORE Deposit Edit - check_filetype ' . var_export( $check_filetype, true ) );
		if ( ! empty( $thumb_image ) ) {
			humcore_write_error_log( 'info', 'HumCORE Deposit Edit - thumb_image ' . var_export( $thumb_image, true ) );
		}
	}

	$next_pids   = array();
	$next_pids[] = $deposit_post_metadata['pid'];
	$next_pids[] = $deposit_file_metadata['files'][0]['pid'];

	$metadata                          = prepare_user_entered_metadata( $user, $curr_val );
	$metadata['id']                    = $next_pids[0];
	$metadata['pid']                   = $next_pids[0];
	$metadata['creator']               = $deposit_post_metadata['creator'];
	$metadata['submitter']             = $deposit_post_metadata['submitter'];
	$metadata['society_id']            = $society_id;
	$metadata['handle']                = $deposit_post_metadata['handle'];
	$metadata['deposit_doi']           = $deposit_post_metadata['deposit_doi'];
	$metadata['member_of']             = $deposit_post_metadata['member_of'];
	$metadata['record_identifier']     = $deposit_post_metadata['record_identifier'];
	$metadata['record_content_source'] = $deposit_post_metadata['record_content_source'];
	$metadata['record_creation_date']  = $deposit_post_metadata['record_creation_date'];
	$metadata['record_change_date']    = gmdate( 'Y-m-d\TH:i:s\Z' );
	$current_authors                   = $deposit_post_metadata['authors'];
	$current_embargo_flag              = $deposit_post_metadata['embargoed'];
	$current_post_date                 = $deposit_post->post_date;
	$current_post_status               = $deposit_post->post_status;

	//TODO set these to handle hcadmin and embargo?
	//$deposit_activity_needed = true;
	//$deposit_review_needed = false;
	$deposit_post_date   = $deposit_post->post_date;
	$deposit_post_status = $deposit_post->post_status;

	if ( 'yes' === $metadata['embargoed'] ) {
		//recalc embargo end date using original date
		$metadata['embargo_end_date'] = date(
			'm/d/Y', strtotime(
				$deposit_post_metadata['record_creation_date'] . '+' .
					sanitize_text_field( $curr_val['deposit-embargo-length'] )
			)
		);
		$deposit_post_date            = date( 'Y-m-d', strtotime( $metadata['embargo_end_date'] ) );
		$deposit_post_status          = 'future';
	} elseif ( 'future' === $current_post_status ) {
		$metadata['embargo_end_date'] = '';
		$deposit_post_date            = date( 'Y-m-d', strtotime( $deposit_post_metadata['record_creation_date'] ) );
		$deposit_post_status          = 'draft';
	}

	/**
	 * Add any new subjects and set object terms for subjects.
	 */
	if ( ! empty( $metadata['subject'] ) ) {
		$term_ids = array();
		foreach ( $metadata['subject'] as $subject ) {
			$term_key = wpmn_term_exists( $subject, 'humcore_deposit_subject' );
			if ( empty( $term_key ) ) {
				$term_key = wpmn_insert_term( sanitize_text_field( $subject ), 'humcore_deposit_subject' );
			}
			if ( ! is_wp_error( $term_key ) ) {
				$term_ids[] = intval( $term_key['term_id'] );
			} else {
				humcore_write_error_log(
					'error', '*****HumCORE Deposit Edit Error - bad subject*****' .
					var_export( $term_key, true )
				);
			}
		}
		if ( ! empty( $term_ids ) ) {
			$term_object_id          = str_replace( $fedora_api->namespace . ':', '', $next_pids[0] );
			$term_taxonomy_ids       = wpmn_set_object_terms( $term_object_id, $term_ids, 'humcore_deposit_subject' );
			$metadata['subject_ids'] = $term_taxonomy_ids;
		}
	}

	/**
	 * Add any new keywords and set object terms for tags.
	 */
	if ( ! empty( $metadata['keyword'] ) ) {
		$term_ids = array();
		foreach ( $metadata['keyword'] as $keyword ) {
			$term_key = wpmn_term_exists( $keyword, 'humcore_deposit_tag' );
			if ( empty( $term_key ) ) {
				$term_key = wpmn_insert_term( sanitize_text_field( $keyword ), 'humcore_deposit_tag' );
			}
			if ( ! is_wp_error( $term_key ) ) {
				$term_ids[] = intval( $term_key['term_id'] );
			} else {
				humcore_write_error_log(
					'error', '*****HumCORE Deposit Edit Error - bad tag*****' .
					var_export( $term_key, true )
				);
			}
		}
		if ( ! empty( $term_ids ) ) {
			$term_object_id          = str_replace( $fedora_api->namespace . ':', '', $next_pids[0] );
			$term_taxonomy_ids       = wpmn_set_object_terms( $term_object_id, $term_ids, 'humcore_deposit_tag' );
			$metadata['keyword_ids'] = $term_taxonomy_ids;
		}
	}

	$metadata_mods = create_mods_xml( $metadata );

	$resource_xml = create_resource_xml( $metadata, $filetype );

	// TODO handle file write error.
	$file_write_status = file_put_contents( $mods_file, $metadata_mods );

	humcore_write_error_log( 'info', 'HumCORE Deposit Edit metadata complete' );

	/**
	 * Extract text first if small. If Tika errors out we'll index without full text.
	 */
	if ( ! preg_match( '~^audio/|^image/|^video/~', $filetype ) && (int) $filesize < 1000000 ) {
		try {
			$tika_text = $tika_client->getText( $renamed_file );
			$content   = $tika_text;
		} catch ( Exception $e ) {
			humcore_write_error_log(
				'error', sprintf(
					'*****HumCORE Deposit Edit Error***** - ' .
						'A Tika error occurred extracting text from the uploaded file. This deposit, %1$s, ' .
						'will be indexed using only the web form metadata.', $next_pids[0]
				)
			);
			humcore_write_error_log(
				'error', sprintf(
					'*****HumCORE Deposit Edit Error***** - Tika error message: ' .
						$e->getMessage(), var_export( $e, true )
				)
			);
			$content = '';
		}
	}

	/**
	 * Index the deposit content and metadata in Solr.
	 */
	try {
		if ( preg_match( '~^audio/|^image/|^video/~', $filetype ) ) {
			$s_result = $solr_client->create_humcore_document( '', $metadata );
		} else {
			//$s_result = $solr_client->create_humcore_extract( $renamed_file, $metadata ); //no longer using tika on server
			$s_result = $solr_client->create_humcore_document( $content, $metadata );
		}
	} catch ( Exception $e ) {
		if ( '500' == $e->getCode() && strpos( $e->getMessage(), 'TikaException' ) ) { // Only happens if tika is on the solr server.
			try {
				$s_result = $solr_client->create_humcore_document( '', $metadata );
				humcore_write_error_log(
					'error', sprintf(
						'*****HumCORE Deposit Edit Error***** - ' .
							'A Tika error occurred extracting text from the uploaded file. This deposit, ' .
							'%1$s, will be indexed using only the web form metadata.', $next_pids[0]
					)
				);
			} catch ( Exception $e ) {
				echo '<h3>', __( 'An error occurred while editing your deposit!', 'humcore_domain' ), '</h3>';
				humcore_write_error_log(
					'error', sprintf(
						'*****HumCORE Deposit Edit Error***** - solr : %1$s-%2$s',
						$e->getCode(), $e->getMessage()
					)
				);
				return false;
			}
		} else {
			echo '<h3>', __( 'An error occurred while editing your deposit!', 'humcore_domain' ), '</h3>';
			humcore_write_error_log(
				'error', sprintf(
					'*****HumCORE Deposit Edit Error***** - solr : %1$s-%2$s',
					$e->getCode(), $e->getMessage()
				)
			);
			return false;
		}
	}

	/**
	 * Update the aggregator post
	 */
	$deposit_post_data = array(
		'ID'           => $deposit_post_id,
		'post_title'   => $metadata['title'],
		'post_excerpt' => $metadata['abstract'],
		'post_status'  => $deposit_post_status,
		'post_date'    => $deposit_post_date,
	);
	humcore_write_error_log( 'info', 'HumCORE Deposit Edit post data ' . var_export( $deposit_post_data, true ) );
	$deposit_post_update_status = wp_update_post( $deposit_post_data, true );

	$json_metadata = json_encode( $metadata, JSON_HEX_APOS );
	if ( json_last_error() ) {
		humcore_write_error_log(
			'error', '*****HumCORE Deposit Edit Error***** Post Meta Encoding Error - Post ID: ' .
			$deposit_post_id . ' - ' . json_last_error_msg()
		);
	}
	$post_meta_update_status = update_post_meta( $deposit_post_id, '_deposit_metadata', wp_slash( $json_metadata ) );
	humcore_write_error_log( 'info', 'HumCORE Deposit Edit - postmeta (1)', json_decode( $json_metadata, true ) );

	/**
	 * Update the resource post.
	 */
	$resource_post_data          = array(
		'ID'         => $resource_post[0]->ID,
		'post_title' => $filename,
	);
	$resource_post_update_status = wp_update_post( $resource_post_data );

	/**
	 * Update metadata and store in post meta.
	 */
	if ( $file_changed ) {
		$file_metadata['files'][0]['filename']            = $filename;
		$file_metadata['files'][0]['filetype']            = $filetype;
		$file_metadata['files'][0]['filesize']            = $filesize;
		$file_metadata['files'][0]['fileloc']             = $renamed_file;
		$file_metadata['files'][0]['thumb_datastream_id'] = ( ! empty( $generated_thumb_name ) ) ? $thumb_datastream_id : '';
		$file_metadata['files'][0]['thumb_filename']      = ( ! empty( $generated_thumb_name ) ) ? $generated_thumb_name : '';

		$json_metadata = json_encode( $file_metadata, JSON_HEX_APOS );
		if ( json_last_error() ) {
			humcore_write_error_log(
				'error', '*****HumCORE Deposit Edit Error***** File Post Meta Encoding Error - Post ID: ' .
						$deposit_post_id . ' - ' . json_last_error_msg()
			);
		}
		$file_meta_update_status = update_post_meta( $deposit_post_id, '_deposit_file_metadata', wp_slash( $json_metadata ) );
		humcore_write_error_log( 'info', 'HumCORE Deposit Edit - postmeta (2)', json_decode( $json_metadata, true ) );
	}

	/**
	 * Bust cache for this deposit.
	 */
	$old_author_unis = array_map(
		function( $element ) {
				return urlencode( $element['uni'] );
		}, $current_authors
	);
	$new_author_unis = array_map(
		function( $element ) {
				return urlencode( $element['uni'] );
		}, $metadata['authors']
	);
	$author_uni_keys = array_filter( array_unique( array_merge( $old_author_unis, $new_author_unis ) ) );
	humcore_delete_cache_keys( 'item', urlencode( $next_pids[0] ) );
	humcore_delete_cache_keys( 'author_uni', $author_uni_keys );

	/**
	 * Upload the MODS file to the Fedora server temp file storage.
	 */
	$upload_mods = $fedora_api->upload( array( 'file' => $mods_file ) );
	if ( is_wp_error( $upload_mods ) ) {
		echo 'Error - upload_mods : ' . esc_html( $upload_mods->get_error_message() );
		humcore_write_error_log(
			'error', sprintf(
				'*****HumCORE Deposit Edit Error***** - upload_mods : %1$s-%2$s',
				$upload_mods->get_error_code(), $upload_mods->get_error_message()
			)
		);
	}

	/**
	 * Update the descMetadata datastream for the aggregator object.
	 */
	$m_content = $fedora_api->modify_datastream(
		array(
			'pid'        => $next_pids[0],
			'dsID'       => 'descMetadata',
			'dsLocation' => $upload_mods,
			'dsLabel'    => $metadata['title'],
			'mimeType'   => 'text/xml',
			'content'    => false,
		)
	);
	if ( is_wp_error( $m_content ) ) {
		echo esc_html( 'Error - m_content : ' . $m_content->get_error_message() );
		humcore_write_error_log(
			'error', sprintf(
				'*****WP HumCORE Deposit Edit Error***** - m_content : %1$s-%2$s',
				$m_content->get_error_code(), $m_content->get_error_message()
			)
		);
	}

	/**
	 * Upload the deposit to the Fedora server temp file storage.
	 */
	if ( $file_changed ) {
		$upload_url = $fedora_api->upload(
			array(
				'file'     => $renamed_file,
				'filename' => $filename,
				'filetype' => $filetype,
			)
		);
		if ( is_wp_error( $upload_url ) ) {
			echo 'Error - upload_url : ' . esc_html( $upload_url->get_error_message() );
			humcore_write_error_log(
				'error', sprintf(
					'*****HumCORE Deposit Edit Error***** - upload_url (1) : %1$s-%2$s',
					$upload_url->get_error_code(), $upload_url->get_error_message()
				)
			);
		}
	}

	/**
	 * Update the CONTENT datastream for the resource object.
	 */
	if ( $file_changed ) {
		$r_content = $fedora_api->modify_datastream(
			array(
				'pid'          => $next_pids[1],
				'dsID'         => $datastream_id,
				'controlGroup' => 'M',
				'dsLocation'   => $upload_url,
				'dsLabel'      => $filename,
				'versionable'  => true,
				'dsState'      => 'A',
				'mimeType'     => $filetype,
				'content'      => false,
			)
		);
		if ( is_wp_error( $r_content ) ) {
			printf( '*****Error***** - r_content : %1$s-%2$s', $r_content->get_error_code(), $r_content->get_error_message() );
			echo "\n\r";
		}
	}

	if ( $file_changed ) {
		$o_content = $fedora_api->modify_object(
			array(
				'pid'   => $next_pids[1],
				'label' => $filename,
			)
		);
		if ( is_wp_error( $o_content ) ) {
			printf( '*****Error***** - o_content : %1$s-%2$s', $o_content->get_error_code(), $o_content->get_error_message() );
		}
	}

	/**
	 * Modify the resource object metadata datastream.
	 */
	$r_dc_content = $fedora_api->modify_datastream(
		array(
			'pid'      => $next_pids[1],
			'dsID'     => 'DC',
			'mimeType' => 'text/xml',
			'content'  => $resource_xml,
		)
	);
	if ( is_wp_error( $r_dc_content ) ) {
		echo 'Error - r_dc_content : ' . esc_html( $r_dc_content->get_error_message() );
		humcore_write_error_log(
			'error', sprintf(
				'*****WP HumCORE Deposit Edit Error***** - r_dc_content : %1$s-%2$s',
				$r_dc_content->get_error_code(), $r_dc_content->get_error_message()
			)
		);
	}

	/**
	 * Upload the thumb to the Fedora server temp file storage if necessary.
	 */
	if ( $file_changed ) {
		//TODO fix thumbs if ( preg_match( '~^image/|/pdf$~', $filetype ) && ! empty( $generated_thumb_path ) ) {
		if ( preg_match( '~^image/$~', $filetype ) && ! empty( $generated_thumb_path ) ) {

			$upload_url = $fedora_api->upload(
				array(
					'file'     => $generated_thumb_path,
					'filename' => $generated_thumb_name,
					'filetype' => $generated_thumb_mime,
				)
			);
			if ( is_wp_error( $upload_url ) ) {
				echo 'Error - upload_url : ' . esc_html( $upload_url->get_error_message() );
				humcore_write_error_log(
					'error', sprintf(
						'*****HumCORE Deposit Edit Error***** - upload_url (2) : %1$s-%2$s',
						$upload_url->get_error_code(), $upload_url->get_error_message()
					)
				);
			}

			/**
			 * Update the THUMB datastream for the resource object if necessary.
			 */
			$t_content = $fedora_api->modify_datastream(
				array(
					'pid'          => $next_pids[1],
					'dsID'         => $thumb_datastream_id,
					'controlGroup' => 'M',
					'dsLocation'   => $upload_url,
					'dsLabel'      => $generated_thumb_name,
					'versionable'  => true,
					'dsState'      => 'A',
					'mimeType'     => $generated_thumb_mime,
					'content'      => false,
				)
			);
			if ( is_wp_error( $t_content ) ) {
				echo 'Error - t_content : ' . esc_html( $t_content->get_error_message() );
				humcore_write_error_log(
					'error', sprintf(
						'*****HumCORE Deposit Edit Error***** - t_content : %1$s-%2$s',
						$t_content->get_error_code(), $t_content->get_error_message()
					)
				);
			}
		}
	}

	humcore_write_error_log( 'info', 'HumCORE Deposit Edit fedora/solr writes complete' );

	/**
	 * Add the activity entry for the author.
	 */
	/* not needed for update
	if ( $deposit_activity_needed ) {
		$activity_ID = humcore_new_deposit_activity( $deposit_post_id, $metadata['abstract'], $local_link, $user->ID );
	}
	*/

	/**
	 * Handle doi metadata changes.
	 */
	if ( ! empty( $metadata['deposit_doi'] ) ) {
		$e_status = humcore_modify_handle( $metadata );
		if ( false === $e_status ) {
			humcore_write_error_log( 'error', 'There was an EZID API error, the DOI was not sucessfully modified.' );
		}
	}

	/**
	 * Add POST variables needed for async tika extraction
	 */
	$_POST['aggregator-post-id'] = $deposit_post_id;

	/**
	 * Re-index larger text based deposits in the background.
	 */
	if ( ! preg_match( '~^audio/|^image/|^video/~', $filetype ) && (int) $filesize >= 1000000 ) {
		do_action( 'humcore_tika_text_extraction' );
	}

	humcore_write_error_log( 'info', 'HumCORE Deposit Edit transaction complete' );
	echo '<h3>', __( 'Deposit edit complete!', 'humcore_domain' ), '</h3><br />';
	return $next_pids[0];

}
