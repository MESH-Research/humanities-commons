<?php
/**
 * Plugin admin screen functions.
 *
 * @package HumCORE
 * @subpackage Deposits
 */

/**
 * Enqueue a script in the WordPress admin on edit.php.
 *
 * @param int $hook Hook suffix for the current admin page.
 */
 function deposit_load_admin_scripts() {
	hcommons_write_error_log( 'info', 'humcore deposit load admin scripts' );

	wp_register_style( 'humcore_deposits_css', plugins_url( 'css/deposits.css', __FILE__ ), '', '011818' );
	wp_enqueue_style( 'humcore_deposits_css' );

	wp_enqueue_script( 'plupload', array( 'jquery' ) );

	wp_register_script( 'humcore_deposits_js',   plugins_url( 'js/deposits.js',        __FILE__ ), array( 'jquery' ), '010218', true );
	wp_enqueue_script( 'humcore_deposits_js' );

	wp_register_script('deposit-select2-script', plugins_url( 'js/deposit-select2.js', __FILE__ ), array('jquery'), '1.0.0', true);
	wp_enqueue_script('deposit-select2-script'); 
	
	wp_register_script( 'admin_select2_js', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/select2.min.js', array( 'jquery' ), '022416', true );
	wp_enqueue_script( 'admin_select2_js' );
	wp_register_style( 'admin_select2_css', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/css/select2.min.css', '', '022416' );
	wp_enqueue_style( 'admin_select2_css' );
}
add_action( 'admin_enqueue_scripts', 'deposit_load_admin_scripts' );

/**
 * Add a meta box to the humcore_deposit custom post screen.
 */
function humcore_add_post_type_metabox() {
	// Add the meta box.
	add_meta_box( 'deposit_metabox', 'Deposit Meta', 'humcore_deposit_metabox', 'humcore_deposit', 'normal', 'high' );
}

/**
 * Display the humcore_deposit custom post metadata.
 */
function humcore_deposit_metabox( $post ) {

	// Noncename needed to verify where the data originated.
	echo '<input type="hidden" name="deposit_metabox_noncename" id="deposit_metabox_noncename" value="' . esc_attr( wp_create_nonce( 'humcore_deposit_metabox' ) ) . '" />';

	// Get the metadata.
	$aggregator_metadata = json_decode( get_post_meta( $post->ID, '_deposit_metadata', true ), true );

	if ( ! empty( $aggregator_metadata ) ) {

		if ( empty( $aggregator_metadata['title_unchanged'] ) ) {
			$aggregator_metadata['title_unchanged'] = $aggregator_metadata['title'];
		}
		if ( empty( $aggregator_metadata['abstract_unchanged'] ) ) {
			$aggregator_metadata['abstract_unchanged'] = $aggregator_metadata['abstract'];
		}
		if ( empty( $aggregator_metadata['notes_unchanged'] ) ) {
			$aggregator_metadata['notes_unchanged'] = $aggregator_metadata['notes'];
		}
		// Echo out the array.
	?>
	<div class="width_full p_box">
		<!-- <?php print_r( $aggregator_metadata ); ?> -->

		<p>
			<label>ID<br>
				<input type="hidden" name="aggregator_id" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['id'] ); ?>">
				<input type="text" name="aggregator_id_display" class="widefat" disabled="disabled" value="<?php echo esc_attr( $aggregator_metadata['id'] ); ?>">
			</label>
		</p>
		<p>
			<label>PID<br>
				<input type="hidden" name="aggregator_pid" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['pid'] ); ?>">
				<input type="text" name="aggregator_pid_display" class="widefat" disabled="disabled" value="<?php echo esc_attr( $aggregator_metadata['pid'] ); ?>">
			</label>
		</p>
		<p>
			<label>Creator<br>
				<input type="hidden" name="aggregator_creator" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['creator'] ); ?>">
				<input type="text" name="aggregator_creator_display" class="widefat" disabled="disabled" value="<?php echo esc_attr( $aggregator_metadata['creator'] ); ?>">
			</label>
		</p>
		<p>
			<label>Published?<br>
				<input type="text" name="aggregator_published" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['published'] ); ?>">
			</label>
		</p>
		<p>
			<label>Title<br>
				<input type="text" name="aggregator_title_unchanged" class="widefat" maxlength="255" value="<?php echo esc_attr( $aggregator_metadata['title_unchanged'] ); ?>">
			</label>
		</p>
		<p>
			<label>Abstract<br>
				<textarea name="aggregator_abstract_unchanged" class="widefat"><?php echo esc_attr( $aggregator_metadata['abstract_unchanged'] ); ?></textarea>
			</label>
		</p>
		<p>
			<label>Genre<br>
			<select name="aggregator_genre">
			<option class="level-0" selected value=""></option>
<?php
	$genre_list   = humcore_deposits_genre_list();
	$posted_genre = '';
if ( ! empty( $aggregator_metadata['genre'] ) ) {
	$posted_genre = esc_attr( $aggregator_metadata['genre'] );
}
foreach ( $genre_list as $genre_key => $genre_value ) {
	printf(
		'			<option class="level-0" %1$s value="%2$s">%3$s</option>' . "\n",
		( $genre_key == $posted_genre ) ? 'selected="selected"' : '',
		$genre_key,
		$genre_value
	);
}
?>
			</select>
			</label>
		</p>
		<p>
			<label>Organization<br>
				<input type="text" name="aggregator_organization" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['organization'] ); ?>">
			</label>
		</p>
		<p>
			<label>Institution<br>
				<input type="text" name="aggregator_institution" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['institution'] ); ?>">
			</label>
		</p>
		<p>
			<label>Conference Title<br>
				<input type="text" name="aggregator_conference_title" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['conference_title'] ); ?>">
			</label>
		</p>
		<p>
			<label>Conference Organization<br>
				<input type="text" name="aggregator_conference_organization" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['conference_organization'] ); ?>">
			</label>
		</p>
		<p>
			<label>Conference Location<br>
				<input type="text" name="aggregator_conference_location" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['conference_location'] ); ?>">
			</label>
		</p>
		<p>
			<label>Conference Date<br>
				<input type="text" name="aggregator_conference_date" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['conference_date'] ); ?>">
			</label>
		</p>
		<p>
			<label>Meeting Title<br>
				<input type="text" name="aggregator_meeting_title" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['meeting_title'] ); ?>">
			</label>
		</p>
		<p>
			<label>Meeting Organization<br>
				<input type="text" name="aggregator_meeting_organization" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['meeting_organization'] ); ?>">
			</label>
		</p>
		<p>
			<label>Meeting Location<br>
				<input type="text" name="aggregator_meeting_location" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['meeting_location'] ); ?>">
			</label>
		</p>
		<p>
			<label>Meeting Date<br>
				<input type="text" name="aggregator_meeting_date" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['meeting_date'] ); ?>">
			</label>
		</p>
		<p>
			<label>Deposit for Others<br>
				<input type="hidden" name="aggregator_deposit_for_others" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['deposit_for_others'] ); ?>">
				<input type="text" name="aggregator_deposit_for_others_display" class="widefat" disabled="disabled" value="<?php echo esc_attr( $aggregator_metadata['deposit_for_others'] ); ?>">
			</label>
		</p>
		<p>
			<label>Committee Deposit<br>
				<input type="hidden" name="aggregator_committee_deposit" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['committee_deposit'] ); ?>">
				<input type="text" name="aggregator_committee_deposit_display" class="widefat" disabled="disabled" value="<?php echo esc_attr( $aggregator_metadata['committee_deposit'] ); ?>">
			</label>
		</p>
		<p>
			<label>Committee ID<br>
				<input type="hidden" name="aggregator_committee_id" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['committee_id'] ); ?>">
				<input type="text" name="aggregator_committee_id_display" class="widefat" disabled="disabled" value="<?php echo esc_attr( $aggregator_metadata['committee_id'] ); ?>">
			</label>
		</p>
		<p>
			<label>Submitter<br>
				<input type="text" name="aggregator_submitter" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['submitter'] ); ?>">
			</label>
		</p>
		<p>
			<label>Author(s)<br>
			<?php foreach ( $aggregator_metadata['authors'] as $author ) { ?>
				<label>Given: 
				<input type="text" name="aggregator_author_given[]" class="widefat" value="<?php echo esc_attr( $author['given'] ); ?>">
				</label>
				<label>Family: 
				<input type="text" name="aggregator_author_family[]" class="widefat" value="<?php echo esc_attr( $author['family'] ); ?>">
				</label>
				<label>Full Name: 
				<input type="text" name="aggregator_author_fullname[]" class="widefat" value="<?php echo esc_attr( $author['fullname'] ); ?>">
				</label>
				<label>UNI: 
				<input type="text" name="aggregator_author_uni[]" class="widefat" value="<?php echo esc_attr( $author['uni'] ); ?>">
				</label>
				<label>Role: 
				<input type="text" name="aggregator_author_role[]" class="widefat" value="<?php echo esc_attr( $author['role'] ); ?>">
				</label>
				<label>Affiliation: 
				<input type="text" name="aggregator_author_affiliation[]" class="widefat" value="<?php echo esc_attr( $author['affiliation'] ); ?>">
				</label>
			<?php } ?>
				<label>Given: 
				<input type="text" name="aggregator_author_given[]" class="widefat" value="">
				</label>
				<label>Family: 
				<input type="text" name="aggregator_author_family[]" class="widefat" value="">
				</label>
				<label>Full Name: 
				<input type="text" name="aggregator_author_fullname[]" class="widefat" value="">
				</label>
				<label>UNI: 
				<input type="text" name="aggregator_author_uni[]" class="widefat" value="">
				</label>
				<label>Role: 
				<input type="text" name="aggregator_author_role[]" class="widefat" value="">
				</label>
				<label>Affiliation: 
				<input type="text" name="aggregator_author_affiliation[]" class="widefat" value="">
				</label>
			</label>
		</p>
		<p>
			<label>Author Info<br>
				<input type="hidden" name="aggregator_author_info" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['author_info'] ); ?>">
				<input type="text" name="aggregator_author_info_display" class="widefat" disabled="disabled" value="<?php echo esc_attr( $aggregator_metadata['author_info'] ); ?>">
			</label>
		</p>
		<p>
			<label>Group(s)<br>
			<select name="aggregator_group[]" multiple size="10">
			<option class="level-0" value="">(No groups)</option>
<?php
	$group_list        = humcore_deposits_group_list( $aggregator_metadata['submitter'] );
	$posted_group_list = array();
if ( ! empty( $aggregator_metadata['group'] ) ) {
	foreach ( $aggregator_metadata['group_ids'] as $group_id ) {
		if ( ! empty( $group_id ) ) {
			$posted_group_list[] = $group_id;
		}
	}
}
foreach ( $group_list as $group_key => $group_value ) {
	printf(
		'			<option class="level-0" %1$s value="%2$s">%3$s</option>' . "\n",
		( in_array( $group_key, $posted_group_list ) ) ? 'selected="selected"' : '',
		$group_key,
		$group_value
	);
}
?>
			</select>
			</label>
		</p>
		<p>
			<label>Subject(s) (Remove a subject by clicking on the 'x' in the subject's label)<br>
			<select 
				name="aggregator_subject[]" 
				id="aggregator_subject[]" 
				class="js-basic-multiple-fast-subjects-admin"
				data-placeholder="Pick a FAST subject heading"
				multiple="multiple"				
				data-allow-clear="false"
				data-width="75%"
				data-theme="default"
				data-dir="ltr"
				data-minimum-input-length="2"
				data-maximum-selection-length="10"
				data-close-on-select="true"
				data-disabled="false"
				data-debug="false"
				data-delay="250"
			>
			<option class="level-0" value="">(No subjects)</option>
	<?php
  $posted_subject_list = array();
	if ( ! empty( $aggregator_metadata['subject'] ) ) {
    $posted_subject_list = array_map( 'sanitize_text_field', $aggregator_metadata['subject'] );
  }
  foreach ( $posted_subject_list as $subject ) {
      printf(
      '			<option class="level-1" %1$s value="%2$s">%3$s</option>' . "\n",
      'selected="selected"',
      $subject,
      $subject
    );
  }
	//}
	?>
			</select>
			<?php
			$screen = get_current_screen(); 
			echo("<p><b>");
			//print_r($screen);
			echo("</b></p>");
			?>
			</label>
		</p>
		<p>
		<label>Tag(s)<br>
		<select 
			name="aggregator_keyword[]" 
			id="aggregator_keyword[]" 
			class="js-basic-multiple-keywords" 
			multiple="multiple" 
			data-placeholder="Enter tags">
			<option class="level-0" value="">(No keywords)</option>
<?php
	$keyword_list        = humcore_deposits_keyword_list();
	$posted_keyword_list = array();
if ( ! empty( $aggregator_metadata['keyword'] ) ) {
	foreach ( $aggregator_metadata['keyword_ids'] as $keyword_id ) {
		$term                      = wpmn_get_term_by( 'term_taxonomy_id', $keyword_id, 'humcore_deposit_tag' );
			$posted_keyword_list[] = sanitize_text_field( stripslashes( $term->name ) );
	}
}
foreach ( $keyword_list as $keyword_key => $keyword_value ) {
	if (in_array( $keyword_key, $posted_keyword_list )) {
		printf(
			'			<option class="level-0" %1$s value="%2$s">%3$s</option>' . "\n",
			'selected="selected"',
			$keyword_key,
			$keyword_value
		);
	}
}
?>
			</select>
			</label>
		</p>
		<p>
			<label>Type of Resource<br>
			<select name="aggregator_type_of_resource">
			<option class="level-0" selected value=""></option>
<?php
	$resource_type_list   = humcore_deposits_resource_type_list();
	$posted_resource_type = '';
if ( ! empty( $aggregator_metadata['type_of_resource'] ) ) {
	$posted_resource_type = esc_attr( $aggregator_metadata['type_of_resource'] );
}
foreach ( $resource_type_list as $resource_key => $resource_value ) {
	printf(
		'			<option class="level-0" %1$s value="%2$s">%3$s</option>' . "\n",
		( $resource_key == $posted_resource_type ) ? 'selected="selected"' : '',
		$resource_key,
		$resource_value
	);
}
?>
			</select>
			</label>
		</p>
		<p>
			<label>Language<br>
			<select name="aggregator_language">
			<option class="level-0" selected value=""></option>
<?php
	$language_list   = humcore_deposits_language_list();
	$posted_language = '';
if ( ! empty( $aggregator_metadata['language'] ) ) {
	$posted_language = esc_attr( $aggregator_metadata['language'] );
}
foreach ( $language_list as $language_key => $language_value ) {
	printf(
		'			<option class="level-0" %1$s value="%2$s">%3$s</option>' . "\n",
		( $language_key == $posted_language ) ? 'selected="selected"' : '',
		$language_key,
		$language_value
	);
}
?>
			</select>
			</label>
		</p>
		<p>
			<label>Notes<br>
				<textarea name="aggregator_notes_unchanged" class="widefat"><?php echo esc_attr( $aggregator_metadata['notes_unchanged'] ); ?></textarea>
			</label>
		</p>
		<p>
			<label>Record Content Source<br>
				<input type="hidden" name="aggregator_record_content_source" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['record_content_source'] ); ?>">
				<input type="text" name="aggregator_record_content_source_display" class="widefat" disabled="disabled" value="<?php echo esc_attr( $aggregator_metadata['record_content_source'] ); ?>">
			</label>
		</p>
		<p>
			<label>Record Creation Date<br>
				<input type="hidden" name="aggregator_record_creation_date" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['record_creation_date'] ); ?>">
				<input type="text" name="aggregator_record_creation_date_display" class="widefat" disabled="disabled" value="<?php echo esc_attr( $aggregator_metadata['record_creation_date'] ); ?>">
			</label>
		</p>
		<p>
			<label>Record Change Date<br>
				<input type="hidden" name="aggregator_record_change_date" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['record_change_date'] ); ?>">
				<input type="text" name="aggregator_record_change_date_display" class="widefat" disabled="disabled" value="<?php echo esc_attr( $aggregator_metadata['record_change_date'] ); ?>">
			</label>
		</p>
		<p>
			<label>Member Of<br>
				<input type="hidden" name="aggregator_member_of" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['member_of'] ); ?>">
				<input type="text" name="aggregator_member_of_display" class="widefat" disabled="disabled" value="<?php echo esc_attr( $aggregator_metadata['member_of'] ); ?>">
			</label>
		</p>
		<p>
			<label>Publication Type<br>
			<input type="radio" name="aggregator_publication-type" class="widefat" value="book" 
			<?php
			if ( ! empty( $aggregator_metadata['publication-type'] ) ) {
				checked( sanitize_text_field( $aggregator_metadata['publication-type'] ), 'book' ); }
?>
>Book &nbsp;
			<input type="radio" name="aggregator_publication-type" class="widefat" value="book-chapter" 
			<?php
			if ( ! empty( $aggregator_metadata['publication-type'] ) ) {
				checked( sanitize_text_field( $aggregator_metadata['publication-type'] ), 'book-chapter' ); }
?>
>Book chapter &nbsp;
			<input type="radio" name="aggregator_publication-type" class="widefat" value="book-review" 
			<?php
			if ( ! empty( $aggregator_metadata['publication-type'] ) ) {
				checked( sanitize_text_field( $aggregator_metadata['publication-type'] ), 'book-review' ); }
?>
>Book review &nbsp;
			<input type="radio" name="aggregator_publication-type" class="widefat" value="book-section" 
			<?php
			if ( ! empty( $aggregator_metadata['publication-type'] ) ) {
				checked( sanitize_text_field( $aggregator_metadata['publication-type'] ), 'book-section' ); }
?>
>Book section &nbsp;
			<input type="radio" name="aggregator_publication-type" class="widefat" value="journal-article" 
			<?php
			if ( ! empty( $aggregator_metadata['publication-type'] ) ) {
				checked( sanitize_text_field( $aggregator_metadata['publication-type'] ), 'journal-article' ); }
?>
>Journal article &nbsp;
			<input type="radio" name="aggregator_publication-type" class="widefat" value="magazine-section" 
			<?php
			if ( ! empty( $aggregator_metadata['publication-type'] ) ) {
				checked( sanitize_text_field( $aggregator_metadata['publication-type'] ), 'magazine-section' ); }
?>
>Magazine section &nbsp;
			<input type="radio" name="aggregator_publication-type" class="widefat" value="monograph" 
			<?php
			if ( ! empty( $aggregator_metadata['publication-type'] ) ) {
				checked( sanitize_text_field( $aggregator_metadata['publication-type'] ), 'monograph' ); }
?>
>Monograph &nbsp;
			<input type="radio" name="aggregator_publication-type" class="widefat" value="newspaper-article" 
			<?php
			if ( ! empty( $aggregator_metadata['publication-type'] ) ) {
				checked( sanitize_text_field( $aggregator_metadata['publication-type'] ), 'newspaper-article' ); }
?>
>Newspaper article &nbsp;
			<input type="radio" name="aggregator_publication-type" class="widefat" value="online-publication" 
			<?php
			if ( ! empty( $aggregator_metadata['publication-type'] ) ) {
				checked( sanitize_text_field( $aggregator_metadata['publication-type'] ), 'online-publication' ); }
?>
>Online publication &nbsp;
			<input type="radio" name="aggregator_publication-type" class="widefat" value="podcast" 
			<?php
			if ( ! empty( $aggregator_metadata['publication-type'] ) ) {
				checked( sanitize_text_field( $aggregator_metadata['publication-type'] ), 'podcast' ); }
?>
>Podcast &nbsp;
			<input type="radio" name="aggregator_publication-type" class="widefat" value="proceedings-article" 
			<?php
			if ( ! empty( $aggregator_metadata['publication-type'] ) ) {
				checked( sanitize_text_field( $aggregator_metadata['publication-type'] ), 'proceedings-article' ); }
?>
>Conference proceeding &nbsp;
			<input type="radio" name="aggregator_publication-type" class="widefat" value="none" 
			<?php
			if ( ! empty( $aggregator_metadata['publication-type'] ) ) {
				checked( sanitize_text_field( $aggregator_metadata['publication-type'] ), 'none' ); }
?>
>Not published &nbsp;
			</label>
		</p>
		<p>
			<label>Publisher<br>
				<input type="text" name="aggregator_publisher" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['publisher'] ); ?>">
			</label>
		</p>
		<p>
			<label>Date<br>
				<input type="text" name="aggregator_date" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['date'] ); ?>">
			</label>
		</p>
		<p>
			<label>Date Issued<br>
				<input type="text" name="aggregator_date_issued" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['date_issued'] ); ?>">
			</label>
		</p>
		<p>
			<label>Publisher DOI<br>
				<input type="text" name="aggregator_doi" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['doi'] ); ?>">
			</label>
		</p>
		<p>
			<label>Publisher URL<br>
				<input type="text" name="aggregator_url" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['url'] ); ?>">
			</label>
		</p>
		<p>
			<label>Book, Journal or Proceeding Title / Newspaper / Magazine / Web site<br>
				<input type="text" name="aggregator_book_journal_title" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['book_journal_title'] ); ?>">
			</label>
		</p>
		<p>
			<label>Author or Editor<br>
				<input type="text" name="aggregator_book_author" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['book_author'] ); ?>">
			</label>
		</p>
		<p>
			<label>Chapter<br>
				<input type="text" name="aggregator_chapter" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['chapter'] ); ?>">
			</label>
		</p>
		<p>
			<label>Edition / Version<br>
				<input type="text" name="aggregator_edition" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['edition'] ); ?>">
			</label>
		</p>
		<p>
			<label>Volume / Section / Episode<br>
				<input type="text" name="aggregator_volume" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['volume'] ); ?>">
			</label>
		</p>
		<p>
			<label>Issue<br>
				<input type="text" name="aggregator_issue" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['issue'] ); ?>">
			</label>
		</p>
		<p>
			<label>Start Page<br>
				<input type="text" name="aggregator_start_page" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['start_page'] ); ?>">
			</label>
		</p>
		<p>
			<label>End Page<br>
				<input type="text" name="aggregator_end_page" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['end_page'] ); ?>">
			</label>
		</p>
		<p>
			<label>ISBN<br>
				<input type="text" name="aggregator_isbn" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['isbn'] ); ?>">
			</label>
		</p>
		<p>
			<label>ISSN<br>
				<input type="text" name="aggregator_issn" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['issn'] ); ?>">
			</label>
		</p>
		<p>
			<label>Handle<br>
				<input type="text" name="aggregator_handle" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['handle'] ); ?>">
			</label>
		</p>
		<p>
			<label>Deposit DOI<br>
				<input type="text" name="aggregator_deposit_doi" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['deposit_doi'] ); ?>">
			</label>
		</p>
		<p>
			<label>Record Identifier<br>
				<input type="hidden" name="aggregator_record_identifier" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['record_identifier'] ); ?>">
				<input type="text" name="aggregator_record_identifier_display" class="widefat" disabled="disabled" value="<?php echo esc_attr( $aggregator_metadata['record_identifier'] ); ?>">
			</label>
		</p>
		<p>
			<label>Society ID<br>
				<input type="hidden" name="aggregator_society_id" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['society_id'] ); ?>">
	<?php
	if ( ! empty( $aggregator_metadata['society_id'] ) && is_array( $aggregator_metadata['society_id'] ) ) {
		$society_list = implode( ', ', $aggregator_metadata['society_id'] );
	} else {
		$society_list = $aggregator_metadata['society_id'];
	}
	?>
				<input type="text" name="aggregator_society_id_display" class="widefat" disabled="disabled" value="<?php echo esc_attr( $society_list ); ?>">
			</label>
		</p>
		<p>
			<label>Creative Commons License<br>
			<select name="aggregator_type_of_license">
<?php
	$license_type_list   = humcore_deposits_license_type_list();
	$posted_license_type = '';
if ( ! empty( $aggregator_metadata['type_of_license'] ) ) {
	$posted_license_type = esc_attr( $aggregator_metadata['type_of_license'] );
}
foreach ( $license_type_list as $license_key => $license_value ) {
	printf(
		'			<option class="level-0" %1$s value="%2$s">%3$s</option>' . "\n",
		( $license_key == $posted_license_type ) ? 'selected="selected"' : '',
		$license_key,
		$license_value
	);
}
?>
			</select>
			</label>
		</p>
		<p>
			<label>Embargoed?<br>
				<input type="text" name="aggregator_embargoed" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['embargoed'] ); ?>">
			</label>
		</p>
		<p>
			<label>Embargo End Date<br>
				<input type="text" name="aggregator_embargo_end_date" class="widefat" value="<?php echo esc_attr( $aggregator_metadata['embargo_end_date'] ); ?>">
			</label>
		</p>

	</div>
<?php
	}

	// Get the file metadata.
	$resource_file_metadata = json_decode( get_post_meta( $post->ID, '_deposit_file_metadata', true ), true );

	if ( ! empty( $resource_file_metadata ) ) {
		$resource_pid           = $resource_file_metadata['files'][0]['pid'];
		$resource_datastream_id = $resource_file_metadata['files'][0]['datastream_id'];
		$resource_filename      = $resource_file_metadata['files'][0]['filename'];
		$resource_filetype      = $resource_file_metadata['files'][0]['filetype'];
		$resource_filesize      = $resource_file_metadata['files'][0]['filesize'];
		$resource_fileloc       = $resource_file_metadata['files'][0]['fileloc'];

		// Echo out the fields.
	?>
	<p />
	<div class="width_full p_box">
		<p>
			<label><strong>Resource Metadata Fields</strong><br>
				<hr />
			</label>
		</p>
		<p>
			<label>Resource PID<br>
				<input type="text" name="resource_pid" class="widefat" disabled="disabled" value="<?php echo esc_attr( $resource_pid ); ?>">
			</label>
		</p>
		<p>
			<label>Resource Datastream ID<br>
				<input type="text" name="resource_datastream_id" class="widefat" disabled="disabled" value="<?php echo esc_attr( $resource_datastream_id ); ?>">
			</label>
		</p>
		<p>
			<label>File Name<br>
				<input type="text" name="resource_filename" class="widefat" disabled="disabled" value="<?php echo esc_attr( $resource_filename ); ?>">
			</label>
		</p>
		<p><label>Mime Type<br>
				<input type="text" name="resource_filetype" class="widefat" disabled="disabled" value="<?php echo esc_attr( $resource_filetype ); ?>">
			</label>
		</p>
		<p><label>File Size<br>
				<input type="text" name="resource_filesize" class="widefat" disabled="disabled" value="<?php echo esc_attr( $resource_filesize ); ?>">
			</label>
		</p>
		<p><label>File Location<br>
				<input type="text" name="resource_fileloc" class="widefat" disabled="disabled" value="<?php echo esc_attr( $resource_fileloc ); ?>">
			</label>
		</p>
	</div>
<?php
	}

}

/**
 * Save any changes to custom metadata
 *
 * @param int $post_id The ID of the post being saved.
 */
function humcore_deposit_metabox_save( $post_id ) {
	/*
	 * We need to verify this came from our screen and with proper authorization,
	 * because the save_post action can be triggered at other times.
	 */

	// Check if our nonce is set.
	if ( ! isset( $_POST['deposit_metabox_noncename'] ) ) {
		return $post_id;
	}

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce( $_POST['deposit_metabox_noncename'], 'humcore_deposit_metabox' ) ) {
		return $post_id;
	}

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}

	// Check the user's permissions.
	if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return $post_id;
		}
	} else {

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}
	}

	// Make sure that at least the ID is set.
	if ( ! isset( $_POST['aggregator_id'] ) ) {
		return $post_id;
	}

	global $fedora_api, $solr_client;
	//$tika_client = \Vaites\ApacheTika\Client::make('localhost', 9998);
	$tika_client = \Vaites\ApacheTika\Client::make( '/srv/www/commons/current/vendor/tika/tika-app-1.16.jar' );     // app mode

	$aggregator_metadata = json_decode( get_post_meta( $post_id, '_deposit_metadata', true ), true );
	$current_authors     = $aggregator_metadata['authors'];
	$current_groups      = $aggregator_metadata['group'];
	$current_group_ids   = $aggregator_metadata['group_ids'];
	$current_subjects    = $aggregator_metadata['subject'];
	$current_subject_ids = $aggregator_metadata['subject_ids'];
	$current_keywords    = $aggregator_metadata['keyword'];
	$current_keyword_ids = $aggregator_metadata['keyword_ids'];

	// No changes allowed.
	// $aggregator_metadata['id'] = sanitize_text_field( $_POST['aggregator_id'] );
	// $aggregator_metadata['pid'] = sanitize_text_field( $_POST['aggregator_pid'] );
	// $aggregator_metadata['creator'] = sanitize_text_field( $_POST['aggregator_creator'] );

	// Sanitize user input.
	$aggregator_metadata['published']               = sanitize_text_field( stripslashes( $_POST['aggregator_published'] ) );
	$aggregator_metadata['title']                   = sanitize_text_field( stripslashes( $_POST['aggregator_title_unchanged'] ) );
		$aggregator_metadata['title_unchanged']     = wp_kses(
			stripslashes( $_POST['aggregator_title_unchanged'] ),
			array(
				'b'      => array(),
				'em'     => array(),
				'strong' => array(),
			)
		);
	$aggregator_metadata['abstract']                = sanitize_text_field( stripslashes( $_POST['aggregator_abstract_unchanged'] ) );
		$aggregator_metadata['abstract_unchanged']  = wp_kses(
			stripslashes( $_POST['aggregator_abstract_unchanged'] ),
			array(
				'b'      => array(),
				'em'     => array(),
				'strong' => array(),
			)
		);
	$aggregator_metadata['genre']                   = sanitize_text_field( $_POST['aggregator_genre'] );
	$aggregator_metadata['organization']            = sanitize_text_field( stripslashes( $_POST['aggregator_organization'] ) );
	$aggregator_metadata['institution']             = sanitize_text_field( stripslashes( $_POST['aggregator_institution'] ) );
	$aggregator_metadata['conference_title']        = sanitize_text_field( stripslashes( $_POST['aggregator_conference_title'] ) );
	$aggregator_metadata['conference_organization'] = sanitize_text_field( stripslashes( $_POST['aggregator_conference_organization'] ) );
	$aggregator_metadata['conference_location']     = sanitize_text_field( stripslashes( $_POST['aggregator_conference_location'] ) );
	$aggregator_metadata['conference_date']         = sanitize_text_field( stripslashes( $_POST['aggregator_conference_date'] ) );
	$aggregator_metadata['meeting_title']           = sanitize_text_field( stripslashes( $_POST['aggregator_meeting_title'] ) );
	$aggregator_metadata['meeting_organization']    = sanitize_text_field( stripslashes( $_POST['aggregator_meeting_organization'] ) );
	$aggregator_metadata['meeting_location']        = sanitize_text_field( stripslashes( $_POST['aggregator_meeting_location'] ) );
	$aggregator_metadata['meeting_date']            = sanitize_text_field( stripslashes( $_POST['aggregator_meeting_date'] ) );

	// No changes allowed.
	//$aggregator_metadata['deposit_for_others'] = sanitize_text_field( $_POST['aggregator_deposit_for_others'] );
	//$aggregator_metadata['committee_deposit'] = sanitize_text_field( $_POST['aggregator_committee_deposit'] );
	//$aggregator_metadata['committee_id'] = sanitize_text_field( $_POST['aggregator_committee_id'] );
	$aggregator_metadata['submitter'] = sanitize_text_field( $_POST['aggregator_submitter'] );

	$aggregator_metadata['authors'] = array();
	$authors                        = array();
	$authors                        = array_map(
		function ( $given, $family, $fullname, $uni, $role, $affiliation ) {
				return array(
					'given'       => sanitize_text_field( stripslashes( $given ) ),
					'family'      => sanitize_text_field( stripslashes( $family ) ),
					'fullname'    => sanitize_text_field( stripslashes( $fullname ) ),
					'uni'         => sanitize_text_field( stripslashes( $uni ) ),
					'role'        => sanitize_text_field( stripslashes( $role ) ),
					'affiliation' => sanitize_text_field( stripslashes( $affiliation ) ),
				); },
		$_POST['aggregator_author_given'],
		$_POST['aggregator_author_family'],
		$_POST['aggregator_author_fullname'],
		$_POST['aggregator_author_uni'],
		$_POST['aggregator_author_role'],
		$_POST['aggregator_author_affiliation']
	);

	foreach ( $authors as $author ) {
		if ( ! empty( $author['given'] ) || ! empty( $author['family'] ) || ! empty( $author['fullname'] ) ) {
			$aggregator_metadata['authors'][] = $author;
		}
	}
	$aggregator_metadata['author_info'] = humcore_deposits_format_author_info( $aggregator_metadata['authors'] );

	$aggregator_metadata['group']     = array();
	$aggregator_metadata['group_ids'] = array();
	if ( ! empty( $_POST['aggregator_group'] ) ) {
		foreach ( $_POST['aggregator_group'] as $group_id ) {
			$group = groups_get_group( array( 'group_id' => sanitize_text_field( $group_id ) ) );
			if ( ! empty( $group ) ) {
				$aggregator_metadata['group'][]     = $group->name;
				$aggregator_metadata['group_ids'][] = $group_id;
			}
		}
	}

	$aggregator_metadata['subject'] = array();
	if ( ! empty( $_POST['aggregator_subject'] ) ) {
		foreach ( $_POST['aggregator_subject'] as $subject ) {
				$aggregator_metadata['subject'][] = sanitize_text_field( stripslashes( $subject ) );
		}
		if ( $aggregator_metadata['subject'] != $current_subjects ) {
			$term_ids                           = array();
			$aggregator_metadata['subject_ids'] = array();
			foreach ( $_POST['aggregator_subject'] as $subject ) {
				$term_key = wpmn_term_exists( $subject, 'humcore_deposit_subject' );
				if ( empty( $term_key ) ) {
					$term_key = wpmn_insert_term( sanitize_text_field( $subject ), 'humcore_deposit_subject' );
				}
				if ( ! is_wp_error( $term_key ) ) {
					$term_ids[] = intval( $term_key['term_id'] );
				} else {
					humcore_write_error_log( 'error', '*****WP Admin HumCORE Deposit Meta Update Subject Error*****' . var_export( $term_key, true ) );
				}
			}
			// Support add and remove.
						$term_object_id         = str_replace( $fedora_api->namespace . ':', '', $aggregator_metadata['pid'] );
			$term_taxonomy_ids                  = wpmn_set_object_terms( $term_object_id, $term_ids, 'humcore_deposit_subject' );
			$aggregator_metadata['subject_ids'] = $term_taxonomy_ids;
		}
	}

	$aggregator_metadata['keyword'] = array();
	if ( ! empty( $_POST['aggregator_keyword'] ) ) {
		foreach ( $_POST['aggregator_keyword'] as $keyword ) {
				$aggregator_metadata['keyword'][] = sanitize_text_field( stripslashes( $keyword ) );
		}
		if ( $aggregator_metadata['keyword'] != $current_keywords ) {
			$term_ids                           = array();
			$aggregator_metadata['keyword_ids'] = array();
			foreach ( $_POST['aggregator_keyword'] as $keyword ) {
				$term_key = wpmn_term_exists( $keyword, 'humcore_deposit_tag' );
				if ( empty( $term_key ) ) {
					$term_key = wpmn_insert_term( sanitize_text_field( $keyword ), 'humcore_deposit_tag' );
				}
				if ( ! is_wp_error( $term_key ) ) {
					$term_ids[] = intval( $term_key['term_id'] );
				} else {
					humcore_write_error_log( 'error', '*****WP Admin HumCORE Deposit Meta Update Keyword Error*****' . var_export( $term_key, true ) );
				}
			}
			// Support add and remove.
						$term_object_id         = str_replace( $fedora_api->namespace . ':', '', $aggregator_metadata['pid'] );
			$term_taxonomy_ids                  = wpmn_set_object_terms( $term_object_id, $term_ids, 'humcore_deposit_tag' );
			$aggregator_metadata['keyword_ids'] = $term_taxonomy_ids;
		}
	}

	$aggregator_metadata['type_of_resource']    = sanitize_text_field( $_POST['aggregator_type_of_resource'] );
	$aggregator_metadata['language']            = sanitize_text_field( $_POST['aggregator_language'] );
	$aggregator_metadata['notes']               = sanitize_text_field( stripslashes( $_POST['aggregator_notes'] ) );
	$aggregator_metadata['notes_unchanged']     = sanitize_text_field( stripslashes( $_POST['aggregator_notes_unchanged'] ) );
		$aggregator_metadata['notes_unchanged'] = wp_kses(
			stripslashes( $_POST['aggregator_notes_unchanged'] ),
			array(
				'b'      => array(),
				'em'     => array(),
				'strong' => array(),
			)
		);

	// No changes allowed.
	// $aggregator_metadata['record_content_source'] = sanitize_text_field( $_POST['aggregator_record_content_source'] );
	// $aggregator_metadata['record_creation_date'] = sanitize_text_field( $_POST['aggregator_record_creation_date'] );
	// $aggregator_metadata['member_of'] = sanitize_text_field( $_POST['aggregator_member_of'] );

	$aggregator_metadata['record_change_date'] = gmdate( 'Y-m-d\TH:i:s\Z' );
	$aggregator_metadata['publication-type']   = sanitize_text_field( $_POST['aggregator_publication-type'] );
	$aggregator_metadata['publisher']          = sanitize_text_field( stripslashes( $_POST['aggregator_publisher'] ) );
	$aggregator_metadata['date']               = sanitize_text_field( $_POST['aggregator_date'] );
	$aggregator_metadata['date_issued']        = sanitize_text_field( $_POST['aggregator_date_issued'] );
	$aggregator_metadata['doi']                = sanitize_text_field( $_POST['aggregator_doi'] );
	$aggregator_metadata['url']                = sanitize_text_field( $_POST['aggregator_url'] );
	$aggregator_metadata['book_journal_title'] = sanitize_text_field( stripslashes( $_POST['aggregator_book_journal_title'] ) );
	$aggregator_metadata['book_author']        = sanitize_text_field( stripslashes( $_POST['aggregator_book_author'] ) );
	$aggregator_metadata['chapter']            = sanitize_text_field( $_POST['aggregator_chapter'] );
	$aggregator_metadata['edition']            = sanitize_text_field( $_POST['aggregator_edition'] );
	$aggregator_metadata['volume']             = sanitize_text_field( $_POST['aggregator_volume'] );
	$aggregator_metadata['issue']              = sanitize_text_field( $_POST['aggregator_issue'] );
	$aggregator_metadata['start_page']         = sanitize_text_field( $_POST['aggregator_start_page'] );
	$aggregator_metadata['end_page']           = sanitize_text_field( $_POST['aggregator_end_page'] );
	$aggregator_metadata['isbn']               = sanitize_text_field( $_POST['aggregator_isbn'] );
	$aggregator_metadata['issn']               = sanitize_text_field( $_POST['aggregator_issn'] );
	$aggregator_metadata['handle']             = sanitize_text_field( $_POST['aggregator_handle'] );
	$aggregator_metadata['deposit_doi']        = sanitize_text_field( $_POST['aggregator_deposit_doi'] );

	// No changes allowed.
	// $aggregator_metadata['record_identifier'] = sanitize_text_field( $_POST['aggregator_record_identifier'] );
	// $aggregator_metadata['society_id'] = sanitize_text_field( $_POST['aggregator_society_id'] );

	$aggregator_metadata['type_of_license']  = sanitize_text_field( $_POST['aggregator_type_of_license'] );
	$aggregator_metadata['embargoed']        = sanitize_text_field( stripslashes( $_POST['aggregator_embargoed'] ) );
	$aggregator_metadata['embargo_end_date'] = sanitize_text_field( stripslashes( $_POST['aggregator_embargo_end_date'] ) );

	$json_aggregator_metadata = json_encode( $aggregator_metadata, JSON_HEX_APOS );

	// Update the meta field in the database.
	$post_meta_id = update_post_meta( $post_id, '_deposit_metadata', wp_slash( $json_aggregator_metadata ) );
	if ( defined( 'CORE_ERROR_LOG' ) && '' != CORE_ERROR_LOG ) {
		humcore_write_error_log( 'info', 'WP Admin HumCORE Deposit Meta Update', json_decode( $json_aggregator_metadata, true ) );
	}

	// Reindex solr doc.
	$resource_file_metadata = json_decode( get_post_meta( $post_id, '_deposit_file_metadata', true ), true );
	if ( ! empty( $resource_file_metadata ) ) {
		$resource_pid            = $resource_file_metadata['files'][0]['pid'];
		$resource_datastream_id  = $resource_file_metadata['files'][0]['datastream_id'];
		$resource_filename       = $resource_file_metadata['files'][0]['filename'];
		$resource_filetype       = $resource_file_metadata['files'][0]['filetype'];
		$resource_filesize       = $resource_file_metadata['files'][0]['filesize'];
		$resource_fileloc        = $resource_file_metadata['files'][0]['fileloc'];
		$check_resource_filetype = wp_check_filetype( $resource_filename, wp_get_mime_types() );
		$resource_file_prefix    = str_replace( $resource_filename, '', $resource_fileloc );
		$resource_mods_file      = $resource_file_prefix . 'MODS.' . $resource_filename . '.xml';
		$these_pids              = array( $aggregator_metadata['pid'], $resource_pid );

		$metadata_mods     = create_mods_xml( $aggregator_metadata );
		$file_write_status = file_put_contents( $resource_mods_file, $metadata_mods );
		$upload_mods       = $fedora_api->upload( array( 'file' => $resource_mods_file ) );
		if ( is_wp_error( $upload_mods ) ) {
			echo 'Error - uploadMODS : ' . esc_html( $upload_mods->get_error_message() );
			humcore_write_error_log(
				'error', sprintf(
					'*****WP Admin HumCORE Deposit Error***** - uploadMODS : %1$s-%2$s',
					$upload_mods->get_error_code(), $upload_mods->get_error_message()
				)
			);
		}

		$m_content = $fedora_api->modify_datastream(
			array(
				'pid'        => $these_pids[0],
				'dsID'       => 'descMetadata',
				'dsLocation' => $upload_mods,
				'dsLabel'    => $aggregator_metadata['title'],
				'mimeType'   => 'text/xml',
				'content'    => false,
			)
		);
		if ( is_wp_error( $m_content ) ) {
			echo esc_html( 'Error - m_content : ' . $m_content->get_error_message() );
			humcore_write_error_log(
				'error', sprintf(
					'*****WP Admin HumCORE Deposit Error***** - m_content : %1$s-%2$s',
					$m_content->get_error_code(), $m_content->get_error_message()
				)
			);
		}

		$resource_xml = create_resource_xml( $aggregator_metadata, $resource_filetype );

		$r_content = $fedora_api->modify_datastream(
			array(
				'pid'      => $these_pids[1],
				'dsID'     => 'DC',
				'mimeType' => 'text/xml',
				'content'  => $resource_xml,
			)
		);
		if ( is_wp_error( $r_content ) ) {
			echo 'Error - r_content : ' . esc_html( $r_content->get_error_message() );
			humcore_write_error_log( 'error', sprintf( '*****WP Admin HumCORE Deposit Error***** - r_content : %1$s-%2$s', $r_content->get_error_code(), $r_content->get_error_message() ) );
		}

		/**
		 * Add POST variables needed for async tika extraction
		 */
		$_POST['aggregator-post-id'] = $post_id;

		/**
		 * Extract text first if small. If Tika errors out we'll index without full text.
		 */
		if ( ! preg_match( '~^audio/|^image/|^video/~', $resource_filetype ) && (int) $resource_filesize < 1000000 ) {

			try {
				$tika_text = $tika_client->getText( $resource_fileloc );
				$content   = $tika_text;
			} catch ( Exception $e ) {
				humcore_write_error_log( 'error', sprintf( '*****HumCORE Deposit Error***** - A Tika error occurred extracting text from the uploaded file. This deposit, %1$s, will be indexed using only the web form metadata.', $these_pids[0] ) );
				humcore_write_error_log( 'error', sprintf( '*****HumCORE Deposit Error***** - Tika error message: ' . $e->getMessage(), var_export( $e, true ) ) );
				$content = '';
			}
		}

		/**
		 * Index the deposit content and metadata in Solr.
		 */
		try {
			if ( preg_match( '~^audio/|^image/|^video/~', $resource_filetype ) ) {
				$s_result = $solr_client->create_humcore_document( '', $aggregator_metadata );
			} else {
				//$s_result = $solr_client->create_humcore_extract( $resource_fileloc, $aggregator_metadata );
				$s_result = $solr_client->create_humcore_document( $content, $aggregator_metadata );
			}
		} catch ( Exception $e ) {

			echo '<h3>', __( 'An error occurred while depositing your file!', 'humcore_domain' ), '</h3>';
			humcore_write_error_log( 'error', sprintf( '*****WP Admin HumCORE Deposit Error***** - solr : %1$s-%2$s', $e->getCode(), $e->getMessage() ) );
			return  $post_id;
		}

		$old_author_unis = array_map(
			function( $element ) {
				return urlencode( $element['uni'] );
			}, $current_authors
		);
		$new_author_unis = array_map(
			function( $element ) {
				return urlencode( $element['uni'] );
			}, $aggregator_metadata['authors']
		);
		$author_uni_keys = array_filter( array_unique( array_merge( $old_author_unis, $new_author_unis ) ) );
		humcore_delete_cache_keys( 'item', urlencode( $these_pids[0] ) );
		humcore_delete_cache_keys( 'author_uni', $author_uni_keys );

		// Handle doi metadata changes.
		if ( ! empty( $aggregator_metadata['deposit_doi'] ) ) {
			$e_status = humcore_modify_handle( $aggregator_metadata );
			if ( false === $e_status ) {
				echo '<h3>', __( 'There was an EZID API error, the DOI was not sucessfully published.', 'humcore_domain' ), '</h3><br />';
			}
		}

		if ( ! preg_match( '~^audio/|^image/|^video/~', $resource_filetype ) && (int) $resource_filesize >= 1000000 ) {
			do_action( 'humcore_tika_text_extraction' );
		}
	}

	return $post_id;

}
add_action( 'save_post', 'humcore_deposit_metabox_save' );
