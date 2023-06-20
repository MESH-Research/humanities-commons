<?php
/**
 * Deposit transaction.
 *
 * @package HumCORE
 * @subpackage Deposits
 */

/**
 * Process the uploaded file:
 * Check for duplicate entries.
 * Make a usable unique filename.
 * Generate a thumb if necessary.
 * For this uploaded file create 2 objects in Fedora, 1 document in Solr and 2 posts.
 * Get the next 2 object id values for Fedora.
 * Prepare the metadata sent to Fedora and Solr.
 * Mint and reserve a DOI.
 * Determine post date, status and necessary activity.
 * Create XML needed for the fedora objects.
 * Create the aggregator post so that we can reference the ID in the Solr document.
 * Set object terms for subjects.
 * Add any new keywords and set object terms for tags.
 * Add to metadata and store in post meta.
 * Prepare an array of post data for the resource post.
 * Insert the resource post.
 * Extract text first if small. If Tika errors out we'll index without full text.
 * Index the deposit content and metadata in Solr.
 * Create the aggregator Fedora object along with the DC and RELS-EXT datastreams.
 * Upload the MODS file to the Fedora server temp file storage.
 * Create the descMetadata datastream for the aggregator object.
 * Upload the deposited file to the Fedora server temp file storage.
 * Create the CONTENT datastream for the resource object.
 * Upload the thumb to the Fedora server temp file storage if necessary.
 * Create the THUMB datastream for the resource object if necessary.
 * Add the activity entry for the author.
 * Publish the reserved DOI.
 * Notify provisional deposit review group for HC member deposits.
 * Re-index larger text based deposits in the background.
 */
function humcore_deposit_file() {

	if ( empty( $_POST ) ) {
		return false;
	}

	global $fedora_api, $solr_client, $datacite_api;
	//$tika_client = \Vaites\ApacheTika\Client::make('localhost', 9998);
	$tika_client = \Vaites\ApacheTika\Client::make( '/srv/www/commons/current/vendor/tika/tika-app-1.16.jar' );     // app mode

	$upload_error_message = '';
	if ( empty( $_POST['selected_file_name'] ) ) {
		// Do something!
		$upload_error_message = __( 'No file was uploaded! Please press "Select File" and upload a file first.', 'humcore_domain' );
	} elseif ( 0 == $_POST['selected_file_size'] ) {
		$upload_error_message = sprintf( __( '%1$s appears to be empty, please choose another file.', 'humcore_domain' ), sanitize_file_name( $_POST['selected_file_name'] ) );
	}
	if ( ! empty( $upload_error_message ) ) {
		echo '<div id="message" class="info"><p>' . $upload_error_message . '</p></div>'; // XSS OK.
		return false;
	}

	/**
	 * Check for duplicate entries.
	 */
	$title_check = wp_strip_all_tags( stripslashes( $_POST['deposit-title-unchanged'] ) );
	$genre       = sanitize_text_field( $_POST['deposit-genre'] );
	if ( 'yes' === $_POST['deposit-on-behalf-flag'] ) {
		$group_id = intval( $_POST['deposit-committee'] );
	} else {
		$group_id = '';
	}
	$user        = get_user_by( 'login', sanitize_text_field( $_POST['deposit-author-uni'] ) );
	$title_match = humcore_get_deposit_by_title_genre_and_author( $title_check, $genre, $group_id, $user );
	if ( ! empty( $title_match ) ) {
		echo '<div id="message" class="info">';
		if ( ! empty( $group_id ) ) {
			$group            = groups_get_group( array( 'group_id' => $group_id ) );
			$sentence_subject = sprintf( '[ %s ]', $group->name );
		} else {
			$sentence_subject = 'You';
		}
		echo sprintf(
			'Wait a minute! %1$s deposited another %2$s entitled <a onclick="target=%3$s" href="%4$s/deposits/item/%5$s">%6$s</a> %7$s ago.<br />Perhaps this is a duplicate deposit? If not, please change the title and click <b>Deposit</b> again.',
			$sentence_subject,
			strtolower( $genre ),
			"'blank'",
			HC_SITE_URL,
			$title_match->id,
			$title_match->title_unchanged,
			human_time_diff( strtotime( $title_match->record_creation_date ) )
		);
		echo '</div>';
		return false;
	}

	// Single file uploads at this point.
	$tempname             = sanitize_file_name( $_POST['selected_temp_name'] );
	$time                 = current_time( 'mysql' );
	$y                    = substr( $time, 0, 4 );
	$m                    = substr( $time, 5, 2 );
	$yyyy_mm              = "$y/$m";
	$fileloc              = $fedora_api->temp_dir . '/' . $yyyy_mm . '/' . $tempname;
	$filename             = strtolower( sanitize_file_name( $_POST['selected_file_name'] ) );
	$filesize             = sanitize_text_field( $_POST['selected_file_size'] );
	$renamed_file         = $fileloc . '.' . $filename;
	$mods_file            = $fileloc . '.MODS.' . $filename . '.xml';
	$filename_dir         = pathinfo( $renamed_file, PATHINFO_DIRNAME );
	$datastream_id        = 'CONTENT';
	$thumb_datastream_id  = 'THUMB';
	$generated_thumb_name = '';

	/**
	 * Make a usable unique filename.
	 */
	if ( file_exists( $fileloc ) ) {
		$file_rename_status = rename( $fileloc, $renamed_file );
	}
	// TODO handle file error.
	$check_filetype = wp_check_filetype( $filename, wp_get_mime_types() );
	$filetype       = $check_filetype['type'];

	//TODO fix thumbs if ( preg_match( '~^image/|/pdf$~', $check_filetype['type'] ) ) {
	if ( preg_match( '~^image/$~', $check_filetype['type'] ) ) {
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
			echo 'Error - thumb_image : ' . esc_html( $thumb_image->get_error_code() ) . '-' . esc_html( $thumb_image->get_error_message() );
			humcore_write_error_log( 'error', sprintf( '*****HumCORE Deposit Error***** - thumb_image : %1$s-%2$s', $thumb_image->get_error_code(), $thumb_image->get_error_message() ) );
		}
	}

	humcore_write_error_log( 'info', 'HumCORE deposit started' );
	humcore_write_error_log( 'info', 'HumCORE deposit - check_filetype ' . var_export( $check_filetype, true ) );
	if ( ! empty( $thumb_image ) ) {
		humcore_write_error_log( 'info', 'HumCORE deposit - thumb_image ' . var_export( $thumb_image, true ) );
	}

	/**
	 * For this uploaded file create 2 objects in Fedora, 1 document in Solr and 2 posts.
	 * Get the next 2 object id values for Fedora.
	 */
	$next_pids = $fedora_api->get_next_pid(
		array(
			'numPIDs'   => '2',
			'namespace' => $fedora_api->namespace,
		)
	);
	if ( is_wp_error( $next_pids ) ) {
		echo 'Error - next_pids : ' . esc_html( $next_pids->get_error_code() ) . '-' . esc_html( $next_pids->get_error_message() );
		humcore_write_error_log( 'error', sprintf( '*****HumCORE Deposit Error***** - next_pids : %1$s-%2$s', $next_pids->get_error_code(), $next_pids->get_error_message() ) );
		return false;
	}

	/**
	 * Prepare the metadata to send to Fedore and Solr.
	 */
	$curr_val                          = $_POST;
	$metadata                          = prepare_user_entered_metadata( $user, $curr_val );
	$metadata['id']                    = $next_pids[0];
	$metadata['pid']                   = $next_pids[0];
	$metadata['creator']               = 'HumCORE';
	$metadata['submitter']             = $user->ID;
	$metadata['society_id']            = humcore_get_current_user_societies( $user->ID );
	$metadata['member_of']             = $fedora_api->collection_pid;
	$metadata['record_content_source'] = 'HumCORE';
	$metadata['record_creation_date']  = gmdate( 'Y-m-d\TH:i:s\Z' );
	$metadata['record_change_date']    = gmdate( 'Y-m-d\TH:i:s\Z' );

	/**
	 * Determine post date, status and necessary activity.
	 */
	$deposit_activity_needed = true;
	$deposit_review_needed   = false;
	$deposit_post_date       = ( new DateTime() )->format( 'Y-m-d H:i:s' );
	$deposit_post_status     = 'draft';
	if ( 'yes' === $metadata['embargoed'] ) {
		$deposit_post_status = 'future';
		$deposit_post_date   = date( 'Y-m-d H:i:s', strtotime( '+' . sanitize_text_field( $_POST['deposit-embargo-length'] ) ) );
	}
	if ( 'hcadmin' === $user->user_login ) {
		$deposit_activity_needed     = false;
		$deposit_post_date           = '';
				$deposit_post_status = 'publish';
	}

	//if in HC lookup user
	//if HC only user send to provisional deposit review group
	if ( 'hc' === humcore_get_current_society_id() && 'hcadmin' !== $user->user_login ) {
		$query_args = array(
			'post_parent' => 0,
			'post_type'   => 'humcore_deposit',
			'post_status' => array( 'draft', 'publish' ),
			'author'      => $user->ID,
		);

		$deposit_posts    = get_posts( $query_args );
			$member_types = bp_get_member_type( $user->ID, false );
		if ( empty( $deposit_posts ) && 1 === count( $member_types ) && 'hc' === $member_types[0] ) {
			$deposit_review_needed = true;
			$deposit_post_status   = 'pending';
		}
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
				humcore_write_error_log( 'error', '*****HumCORE Deposit Error - bad tag*****' . var_export( $term_key, true ) );
			}
		}
		if ( ! empty( $term_ids ) ) {
			$term_object_id          = str_replace( $fedora_api->namespace . ':', '', $next_pids[0] );
			$term_taxonomy_ids       = wpmn_set_object_terms( $term_object_id, $term_ids, 'humcore_deposit_tag' );
			$metadata['keyword_ids'] = $term_taxonomy_ids;
		}
	}

	/**
	 * Create a draft DOI.
	 */
	$deposit_doi = humcore_create_handle(
		$metadata
	);
	if ( ! $deposit_doi ) {
		$metadata['handle']      = sprintf( HC_SITE_URL . '/deposits/item/%s/', $next_pids[0] );
		$metadata['deposit_doi'] = ''; // Not stored in solr.
		humcore_write_error_log( 'info', 'HumCORE deposit DOI creation error' );
	} else {
		$metadata['handle']      = $datacite_api->datacite_proxy . str_replace( 'doi:', '', $deposit_doi );
		$metadata['deposit_doi'] = $deposit_doi; // Not stored in solr.
		humcore_write_error_log( 'info', 'HumCORE deposit DOI created' );
	}

	/**
	 * Publish the reserved DOI.
	 */
	if ( ! empty( $metadata['deposit_doi'] ) ) {
		$e_status = humcore_publish_handle( $metadata );
		if ( false === $e_status ) {
			$metadata['handle']      = sprintf( HC_SITE_URL . '/deposits/item/%s/', $next_pids[0] );
		} else {
			humcore_write_error_log( 'info', 'HumCORE deposit DOI published' );
		}
	}

	/**
	 * Create XML needed for the fedora objects.
	 */
	$aggregator_xml = create_aggregator_xml(
		array(
			'pid'     => $next_pids[0],
			'creator' => $metadata['creator'],
		)
	);

	$aggregator_rdf = create_aggregator_rdf(
		array(
			'pid'           => $next_pids[0],
			'collectionPid' => $fedora_api->collection_pid,
		)
	);

	$aggregator_foxml = create_foxml(
		array(
			'pid'        => $next_pids[0],
			'label'      => '',
			'xmlContent' => $aggregator_xml,
			'state'      => 'Active',
			'rdfContent' => $aggregator_rdf,
		)
	);

	$metadata_mods = create_mods_xml( $metadata );

	$resource_xml = create_resource_xml( $metadata, $filetype );

	$resource_rdf = create_resource_rdf(
		array(
			'aggregatorPid' => $next_pids[0],
			'resourcePid'   => $next_pids[1],
		)
	);

	$resource_foxml = create_foxml(
		array(
			'pid'        => $next_pids[1],
			'label'      => $filename,
			'xmlContent' => $resource_xml,
			'state'      => 'Active',
			'rdfContent' => $resource_rdf,
		)
	);
	// TODO handle file write error.
	$file_write_status = file_put_contents( $mods_file, $metadata_mods );

	humcore_write_error_log( 'info', 'HumCORE deposit metadata complete' );

	/**
	 * Create the aggregator post now so that we can reference the ID in the Solr document.
	 */
	$deposit_post_data = array(
		'post_title'   => $metadata['title'],
		'post_excerpt' => $metadata['abstract'],
		'post_status'  => $deposit_post_status,
		'post_date'    => $deposit_post_date,
		'post_type'    => 'humcore_deposit',
		'post_name'    => str_replace( ':', '', $next_pids[0] ),
		'post_author'  => $user->ID,
	);

	$deposit_post_id               = wp_insert_post( $deposit_post_data );
	$metadata['record_identifier'] = get_current_blog_id() . '-' . $deposit_post_id;

	$json_metadata = json_encode( $metadata, JSON_HEX_APOS );
	if ( json_last_error() ) {
		humcore_write_error_log( 'error', '*****HumCORE Deposit Error***** Post Meta Encoding Error - Post ID: ' . $deposit_post_id . ' - ' . json_last_error_msg() );
	}
	$post_meta_id = update_post_meta( $deposit_post_id, '_deposit_metadata', wp_slash( $json_metadata ) );
	humcore_write_error_log( 'info', 'HumCORE deposit - postmeta (1)', json_decode( $json_metadata, true ) );

	/**
	 * Add to metadata and store in post meta.
	 */
	$post_metadata['files'][] = array(
		'pid'                 => $next_pids[1],
		'datastream_id'       => $datastream_id,
		'filename'            => $filename,
		'filetype'            => $filetype,
		'filesize'            => $filesize,
		'fileloc'             => $renamed_file,
		'thumb_datastream_id' => ( ! empty( $generated_thumb_name ) ) ? $thumb_datastream_id : '',
		'thumb_filename'      => ( ! empty( $generated_thumb_name ) ) ? $generated_thumb_name : '',
	);

	$json_metadata = json_encode( $post_metadata, JSON_HEX_APOS );
	if ( json_last_error() ) {
		humcore_write_error_log( 'error', '*****HumCORE Deposit Error***** File Post Meta Encoding Error - Post ID: ' . $deposit_post_id . ' - ' . json_last_error_msg() );
	}
	$post_meta_id = update_post_meta( $deposit_post_id, '_deposit_file_metadata', wp_slash( $json_metadata ) );
	humcore_write_error_log( 'info', 'HumCORE deposit - postmeta (2)', json_decode( $json_metadata, true ) );

	/**
	 * Prepare an array of post data for the resource post.
	 */
	$resource_post_data = array(
		'post_title'  => $filename,
		'post_status' => 'publish',
		'post_type'   => 'humcore_deposit',
		'post_name'   => $next_pids[1],
		'post_author' => $user->ID,
		'post_parent' => $deposit_post_id,
	);

	/**
	 * Insert the resource post.
	 */
	$resource_post_id = wp_insert_post( $resource_post_data );

	/**
	 * Add POST variables needed for async tika extraction.
	 */
	$_POST['aggregator-post-id'] = $deposit_post_id;

	/**
	 * Extract text first if small. If Tika errors out we'll index without full text.
	 */
	if ( ! preg_match( '~^audio/|^image/|^video/~', $check_resource_filetype['type'] ) && (int) $filesize < 1000000 ) {

		try {
			$tika_text = $tika_client->getText( $renamed_file );
			$content   = $tika_text;
		} catch ( Exception $e ) {
			humcore_write_error_log( 'error', sprintf( '*****HumCORE Deposit Error***** - A Tika error occurred extracting text from the uploaded file. This deposit, %1$s, will be indexed using only the web form metadata.', $next_pids[0] ) );
			humcore_write_error_log( 'error', sprintf( '*****HumCORE Deposit Error***** - Tika error message: ' . $e->getMessage(), var_export( $e, true ) ) );
			$content = '';
		}
	}

	/**
	 * Index the deposit content and metadata in Solr.
	 */
	try {
		if ( preg_match( '~^audio/|^image/|^video/~', $check_filetype['type'] ) ) {
			$s_result = $solr_client->create_humcore_document( '', $metadata );
		} else {
			//$s_result = $solr_client->create_humcore_extract( $renamed_file, $metadata ); //no longer using tika on server
			$s_result = $solr_client->create_humcore_document( $content, $metadata );
		}
	} catch ( Exception $e ) {
		if ( '500' == $e->getCode() && strpos( $e->getMessage(), 'TikaException' ) ) { // Only happens if tika is on the solr server.
			try {
				$s_result = $solr_client->create_humcore_document( '', $metadata );
				humcore_write_error_log( 'error', sprintf( '*****HumCORE Deposit Error***** - A Tika error occurred extracting text from the uploaded file. This deposit, %1$s, will be indexed using only the web form metadata.', $next_pids[0] ) );
			} catch ( Exception $e ) {
				echo '<h3>', __( 'An error occurred while depositing your file!', 'humcore_domain' ), '</h3>';
				humcore_write_error_log( 'error', sprintf( '*****HumCORE Deposit Error***** - solr : %1$s-%2$s', $e->getCode(), $e->getMessage() ) );
				wp_delete_post( $deposit_post_id );
				return false;
			}
		} else {
			echo '<h3>', __( 'An error occurred while depositing your file!', 'humcore_domain' ), '</h3>';
			humcore_write_error_log( 'error', sprintf( '*****HumCORE Deposit Error***** - solr : %1$s-%2$s', $e->getCode(), $e->getMessage() ) );
			wp_delete_post( $deposit_post_id );
			wp_delete_post( $resource_post_id );
			return false;
		}
	}

	/**
	 * Create the aggregator Fedora object along with the DC and RELS-EXT datastreams.
	 */
	$a_ingest = $fedora_api->ingest( array( 'xmlContent' => $aggregator_foxml ) );
	if ( is_wp_error( $a_ingest ) ) {
		echo 'Error - a_ingest : ' . esc_html( $a_ingest->get_error_message() );
		humcore_write_error_log( 'error', sprintf( '*****HumCORE Deposit Error***** - a_ingest : %1$s-%2$s', $a_ingest->get_error_code(), $a_ingest->get_error_message() ) );
		return false;
	}

	/**
	 * Upload the MODS file to the Fedora server temp file storage.
	 */
	$upload_mods = $fedora_api->upload( array( 'file' => $mods_file ) );
	if ( is_wp_error( $upload_mods ) ) {
		echo 'Error - upload_mods : ' . esc_html( $upload_mods->get_error_message() );
		humcore_write_error_log( 'error', sprintf( '*****HumCORE Deposit Error***** - upload_mods : %1$s-%2$s', $upload_mods->get_error_code(), $upload_mods->get_error_message() ) );
	}

	/**
	 * Create the descMetadata datastream for the aggregator object.
	 */
	$m_content = $fedora_api->add_datastream(
		array(
			'pid'          => $next_pids[0],
			'dsID'         => 'descMetadata',
			'controlGroup' => 'M',
			'dsLocation'   => $upload_mods,
			'dsLabel'      => $metadata['title'],
			'versionable'  => true,
			'dsState'      => 'A',
			'mimeType'     => 'text/xml',
			'content'      => false,
		)
	);
	if ( is_wp_error( $m_content ) ) {
		echo esc_html( 'Error - m_content : ' . $m_content->get_error_message() );
		humcore_write_error_log( 'error', sprintf( '*****HumCORE Deposit Error***** - m_content : %1$s-%2$s', $m_content->get_error_code(), $m_content->get_error_message() ) );
	}

	$r_ingest = $fedora_api->ingest( array( 'xmlContent' => $resource_foxml ) );
	if ( is_wp_error( $r_ingest ) ) {
		echo esc_html( 'Error - r_ingest : ' . $r_ingest->get_error_message() );
		humcore_write_error_log( 'error', sprintf( '*****HumCORE Deposit Error***** - r_ingest : %1$s-%2$s', $r_ingest->get_error_code(), $r_ingest->get_error_message() ) );
	}

	/**
	 * Upload the deposited file to the Fedora server temp file storage.
	 */
	$upload_url = $fedora_api->upload(
		array(
			'file'     => $renamed_file,
			'filename' => $filename,
			'filetype' => $filetype,
		)
	);
	if ( is_wp_error( $upload_url ) ) {
		echo 'Error - upload_url : ' . esc_html( $upload_url->get_error_message() );
		humcore_write_error_log( 'error', sprintf( '*****HumCORE Deposit Error***** - upload_url (1) : %1$s-%2$s', $upload_url->get_error_code(), $upload_url->get_error_message() ) );
	}

	/**
	 * Create the CONTENT datastream for the resource object.
	 */
	$r_content = $fedora_api->add_datastream(
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
		echo 'Error - r_content : ' . esc_html( $r_content->get_error_message() );
		humcore_write_error_log( 'error', sprintf( '*****HumCORE Deposit Error***** - r_content : %1$s-%2$s', $r_content->get_error_code(), $r_content->get_error_message() ) );
	}

	/**
	 * Upload the thumb to the Fedora server temp file storage if necessary.
	 */
	//TODO fix thumbs if ( preg_match( '~^image/|/pdf$~', $check_filetype['type'] ) && ! empty( $generated_thumb_path ) ) {
	if ( preg_match( '~^image/$~', $check_filetype['type'] ) && ! empty( $generated_thumb_path ) ) {

		$upload_url = $fedora_api->upload(
			array(
				'file'     => $generated_thumb_path,
				'filename' => $generated_thumb_name,
				'filetype' => $generated_thumb_mime,
			)
		);
		if ( is_wp_error( $upload_url ) ) {
			echo 'Error - upload_url : ' . esc_html( $upload_url->get_error_message() );
			humcore_write_error_log( 'error', sprintf( '*****HumCORE Deposit Error***** - upload_url (2) : %1$s-%2$s', $upload_url->get_error_code(), $upload_url->get_error_message() ) );
		}

		/**
		 * Create the THUMB datastream for the resource object if necessary.
		 */
		$t_content = $fedora_api->add_datastream(
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
			humcore_write_error_log( 'error', sprintf( '*****HumCORE Deposit Error***** - t_content : %1$s-%2$s', $t_content->get_error_code(), $t_content->get_error_message() ) );
		}
	}

	humcore_write_error_log( 'info', 'HumCORE deposit fedora/solr writes complete' );

	//DOI's are taking too long to resolve, put the permalink in the activity records.
	$local_link = sprintf( HC_SITE_URL . '/deposits/item/%s/', $next_pids[0] );

	/**
	 * Add the activity entry for the author.
	 */
	if ( $deposit_activity_needed ) {
		$activity_id = humcore_new_deposit_activity( $deposit_post_id, $metadata['abstract'], $local_link, $user->ID );
	}

	/**
	 * Notify provisional deposit review group for HC member deposits.
	 */
	if ( $deposit_review_needed ) {
		$bp                          = buddypress();
					$review_group_id = BP_Groups_Group::get_id_from_slug( 'provisional-deposit-review' );
		$group_args                  = array(
			'group_id'            => $review_group_id,
			'exclude_admins_mods' => false,
		);
		$provisional_reviewers       = groups_get_group_members( $group_args );
		humcore_write_error_log( 'info', 'Provisional Review Required ' . var_export( $provisional_reviewers, true ) );
		foreach ( $provisional_reviewers['members'] as $group_member ) {
			bp_notifications_add_notification(
				array(
					'user_id'           => $group_member->ID,
					'item_id'           => $deposit_post_id,
					'secondary_item_id' => $user->ID,
					'component_name'    => $bp->humcore_deposits->id,
					'component_action'  => 'deposit_review',
					'date_notified'     => bp_core_current_time(),
					'is_new'            => 1,
				)
			);
		}
		//$review_group = groups_get_group( array( 'group_id' => $review_group_id ) );
		//$group_activity_ids[] = humcore_new_group_deposit_activity( $metadata['record_identifier'], $review_group_id, $metadata['abstract'], $local_link );
		//$metadata['group'][] = $review_group->name;
		//$metadata['group_ids'][] = $review_group_id;
	}

	/**
	 * Re-index larger text based deposits in the background.
	 */
	if ( ! preg_match( '~^audio/|^image/|^video/~', $check_resource_filetype['type'] ) && (int) $filesize >= 1000000 ) {
		do_action( 'humcore_tika_text_extraction' );
	}

			$new_author_unis = array_map(
				function( $element ) {
						return urlencode( $element['uni'] );
				}, $metadata['authors']
			);
			$author_uni_keys = array_filter( $new_author_unis );
			humcore_delete_cache_keys( 'author_uni', $author_uni_keys );

			humcore_write_error_log( 'info', 'HumCORE deposit transaction complete' );
	echo '<h3>', __( 'Deposit complete!', 'humcore_domain' ), '</h3><br />';
	return $next_pids[0];

}
