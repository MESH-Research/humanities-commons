<?php
/**
 * Screen display functions.
 *
 * @package HumCORE
 * @subpackage Deposits
 */

/**
 * Output the deposits search form.
 */
function humcore_deposits_search_form() {

	$default_search_value = bp_get_search_default_text( 'humcore_deposits' );
	$search_value         = '';
	if ( ! empty( $_REQUEST['s'] ) ) {
		$search_value = stripslashes( $_REQUEST['s'] ); }

	$search_form_html = '
<div id="deposits-dir-search" class="dir-search" role="search">
  <form action="" method="post" id="search-deposits-form">
	<label>
	<input type="text" name="s" id="search-deposits-term" value="' . esc_attr( $search_value ) . '" placeholder="' . esc_attr( $default_search_value ) . '" />
	</label>
	<input type="hidden" name="facets" id="search-deposits-facets" />
	<input type="hidden" name="field" id="search-deposits-field" />
	<input type="submit" id="search-deposits-submit" name="search_deposits_submit" value="' . __( 'Search', 'humcore_domain' ) . '" />
  </form>
</div><!-- #deposits-dir-search -->
';

	echo apply_filters( 'humcore_deposits_search_form', $search_form_html ); // XSS OK.
}

/**
 * Prepare the content for deposits/item/new.
 */
function humcore_new_deposit_form() {

	if ( ! empty( $_POST ) ) {
		//check nonce
		$deposit_id = humcore_deposit_file();
		if ( $deposit_id ) {
					$review_url = sprintf( '/deposits/item/%1$s/review/', $deposit_id );
					wp_redirect( $review_url );
			exit();
		}
	}

	ob_end_flush(); // We've been capturing output.
	if ( ! humcore_check_externals() ) {
		echo '<h3>New <em>CORE</em> Deposit</h3>';
		echo "<p>We're so sorry, but one of the components of <em>CORE</em> is currently down and it can't accept deposits just now. We're working on it (and we're delighted that you want to share your work) so please come back and try again later.</p>";
		$wp_referer = wp_get_referer();
		printf(
			'<a href="%1$s" class="button white" style="line-height: 1.2em;">Go Back</a>',
			( ! empty( $wp_referer ) && ! strpos( $wp_referer, 'item/new' ) ) ? $wp_referer : '/deposits/'
		);
		return;
	}

	if ( ! humanities_commons::hcommons_vet_user() ) {
		echo '<h3>New <em>CORE</em> Deposit</h3>';
		echo "<p>We're sorry, but uploading to <em>CORE</em> is currently unavailable. We're we're delighted that you want to share your work so please come back and try again later.</p>";
		$wp_referer = wp_get_referer();
		printf(
			'<a href="%1$s" class="button white" style="line-height: 1.2em;">Go Back</a>',
			( ! empty( $wp_referer ) && ! strpos( $wp_referer, 'item/new' ) ) ? $wp_referer : '/deposits/'
		);
		return;
	}

	if ( ! Humanities_Commons::hcommons_user_in_current_society() && ! is_super_admin() ) {
		$society_name = Humanities_Commons::society_name();
		echo '<h3>New <em>CORE</em> Deposit</h3>';
		echo "<br/>";
        echo "<p>Only members of $society_name can upload CORE deposits on this network.</p>";
		return;
	}
	
	$current_group_id = '';
	preg_match( '~.*?/groups/(.*[^/]?)/deposits/~i', wp_get_referer(), $slug_match );
	if ( ! empty( $slug_match ) ) {
		$current_group_id = BP_Groups_Group::get_id_from_slug( $slug_match[1] );
	}

	$user_id        = bp_loggedin_user_id();
	$user_login     = get_the_author_meta( 'user_login', $user_id );
	$user_firstname = get_the_author_meta( 'first_name', $user_id );
	$user_lastname  = get_the_author_meta( 'last_name', $user_id );
	$prev_val       = array();
	if ( ! empty( $_POST ) ) {
		$prev_val = $_POST;
	} else {
		$prev_val['deposit-author-role'] = 'author';
	}
	humcore_display_deposit_form( $current_group_id, $user_id, $user_login, $user_firstname, $user_lastname, $prev_val, 'new' );

}

/**
 * Prepare the content for deposits/item/edit.
 */
function humcore_edit_deposit_form() {

	global $solr_client, $wp;

	if ( ! empty( $_POST ) ) {
		//check nonce
			$deposit_id = humcore_deposit_edit_file();
		if ( $deposit_id ) {
				$review_url = sprintf( '/deposits/item/%1$s/review/', $deposit_id );
				wp_redirect( $review_url );
				exit();
		}
	}

		ob_end_flush(); // We've been capturing output.
	if ( ! humcore_check_externals() ) {
			echo '<h3>Edit <em>CORE</em> Deposit</h3>';
			echo "<p>We're so sorry, but one of the components of <em>CORE</em> is currently down and it can't accept deposits just now. We're working on it (and we're delighted that you want to edit your work) so please come back and try again later.</p>";
			$wp_referer = wp_get_referer();
			printf(
				'<a href="%1$s" class="button white" style="line-height: 1.2em;">Go Back</a>',
				( ! empty( $wp_referer ) && ! strpos( $wp_referer, 'item/edit' ) ) ? $wp_referer : '/deposits/'
			);
			return;
	}

		$current_group_id = '';
		preg_match( '~.*?/groups/(.*[^/]?)/deposits/~i', wp_get_referer(), $slug_match );
	if ( ! empty( $slug_match ) ) {
			$current_group_id = BP_Groups_Group::get_id_from_slug( $slug_match[1] );
	}

	$deposit_id = $wp->query_vars['deposits_item'];
	$item_found = humcore_has_deposits( 'include=' . $deposit_id );
	humcore_the_deposit();
	$record_identifier = humcore_get_deposit_record_identifier();
	$record_location   = explode( '-', $record_identifier );
	// handle legacy MLA Commons value
	if ( $record_location[0] === $record_identifier ) {
		$record_location[0] = '1';
		$record_location[1] = $record_identifier;
	}
	//Switch blog not needed here, current blog already checked.
	$post_data                      = get_post( $record_location[1] );
	$post_metadata                  = json_decode( get_post_meta( $record_location[1], '_deposit_metadata', true ), true );
	$prev_val                       = humcore_prepare_edit_page_metadata( $post_metadata );
	$file_metadata                  = json_decode( get_post_meta( $record_location[1], '_deposit_file_metadata', true ), true );
	$full_tempname                  = pathinfo( $file_metadata['files'][0]['fileloc'], PATHINFO_BASENAME );
	$tempname                       = str_replace( '.' . $file_metadata['files'][0]['filename'], '', $full_tempname );
	$prev_val['selected_temp_name'] = $tempname;
	$prev_val['selected_file_name'] = $file_metadata['files'][0]['filename'];
	$prev_val['selected_file_type'] = $file_metadata['files'][0]['filetype'];
	$prev_val['selected_file_size'] = $file_metadata['files'][0]['filesize'];
	if ( 'yes' == $prev_val['deposit-on-behalf-flag'] || 'yes' == $prev_val['deposit-for-others-flag'] ) {
		$user = get_user_by( 'ID', sanitize_text_field( $prev_val['submitter'] ) );
	} else {
		$user = get_user_by( 'login', $prev_val['deposit-author-uni'] );
	}
		$user_id        = $user->ID;
		$user_login     = $prev_val['deposit-author-uni'];
		$user_firstname = $prev_val['deposit-author-first-name'];
		$user_lastname  = $prev_val['deposit-author-last-name'];

	/*
	 * Maybe get file data and load prev_val
	 * in deposit check for type and if found we need to do edit - maybe a whole new file shoud be used.
	 */
	if ( ! empty( $_POST ) ) {
			$prev_val = $_POST;
	}
		humcore_display_deposit_form( $current_group_id, $user_id, $user_login, $user_firstname, $user_lastname, $prev_val, 'edit' );

}

/**
 * Render the content for deposits/item/new and deposits/item/edit.
 */
function humcore_display_deposit_form( $current_group_id, $user_id, $user_login, $user_firstname, $user_lastname, $prev_val, $form_type ) {

	$deposit_button_label = ( 'new' === $form_type ) ? 'Deposit' : 'Update';
?>

<script type="text/javascript">
	var MyAjax = {
		ajaxurl : '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
		flash_swf_url : '<?php echo esc_url( includes_url( '/js/plupload/Moxie.swf' ) ); ?>',
		silverlight_xap_url : '<?php echo esc_url( includes_url( '/js/plupload/Moxie.xap' ) ); ?>',
		_ajax_nonce : '<?php echo esc_attr( wp_create_nonce( 'file-upload' ) ); ?>',
	};
</script>

<h3><?php echo ucfirst( $form_type ); ?> CORE</em> Deposit</h3>

<form id="deposit-form" name="deposit-form" class="standard-form" method="post" enctype="multipart/form-data">

	<input type="hidden" name="action" id="action" value="deposit_file" />
	<?php wp_nonce_field( 'new_core_deposit', 'new_core_deposit_nonce' ); ?>

		<input type="hidden" name="selected_temp_name" id="selected_temp_name"
		<?php
		if ( ! empty( $prev_val['selected_temp_name'] ) ) {
			echo ' value="' . sanitize_text_field( $prev_val['selected_temp_name'] ) . '" '; }
?>
/>
		<input type="hidden" name="selected_file_name" id="selected_file_name"
		<?php
		if ( ! empty( $prev_val['selected_file_name'] ) ) {
			echo ' value="' . sanitize_text_field( $prev_val['selected_file_name'] ) . '" '; }
?>
/>
		<input type="hidden" name="selected_file_type" id="selected_file_type"
		<?php
		if ( ! empty( $prev_val['selected_file_type'] ) ) {
			echo ' value="' . sanitize_text_field( $prev_val['selected_file_type'] ) . '" '; }
?>
/>
		<input type="hidden" name="selected_file_size" id="selected_file_size"
		<?php
		if ( ! empty( $prev_val['selected_file_type'] ) ) {
			echo ' value="' . sanitize_text_field( $prev_val['selected_file_size'] ) . '" '; }
?>
/>
		<input type="hidden" name="deposit-form-type" id="deposit-form-type" value="<?php echo $form_type; ?>" />
		<input type="hidden" name="deposit_blog_id" id="deposit_blog_id"
		<?php
		if ( ! empty( $prev_val['deposit_blog_id'] ) ) {
			echo ' value="' . sanitize_text_field( $prev_val['deposit_blog_id'] ) . '" '; }
?>
/>
		<input type="hidden" name="deposit_post_id" id="deposit_post_id"
		<?php
		if ( ! empty( $prev_val['deposit_post_id'] ) ) {
			echo ' value="' . sanitize_text_field( $prev_val['deposit_post_id'] ) . '" '; }
?>
/>

		<div id="deposit-file-entry">
<br />
				<label for="deposit-file">Select the file you wish to upload and deposit. *</label>
		<div id="container">
			<button id="pickfile">Select File</button> 
	<?php
	$wp_referer = wp_get_referer();
		printf(
			'<a href="%1$s" class="button white" style="line-height: 1.2em;">Cancel</a>',
			( ! empty( $wp_referer ) && ! strpos( $wp_referer, 'item/new' ) ) ? $wp_referer : '/deposits/'
		);
	?>
		</div>
	</div>
	<div id="deposit-file-entries">
		<div id="filelist">Your browser doesn't have Flash, Silverlight or HTML5 support.</div>
		<div id="progressbar">
			<div id="indicator"></div>
		</div>
		<div id="console"></div>
	</div>

		<div id="deposit-published-entry">
				<label for="deposit-published">Has this item been previously published?</label>
						<input type="radio" name="deposit-published" value="published"
						<?php
						if ( ! empty( $prev_val['deposit-published'] ) ) {
							checked( sanitize_text_field( $prev_val['deposit-published'] ), 'published' ); }
?>
>Published &nbsp;
						<input type="radio" name="deposit-published" value="not-published"
						<?php
						if ( ! empty( $prev_val['deposit-published'] ) ) {
							checked( sanitize_text_field( $prev_val['deposit-published'] ), 'not-published' );
						} else {
							echo 'checked="checked"'; }
?>
>Not published &nbsp;
		</div>

	<div id="deposit-metadata-entries">
	<div id="lookup-doi-entry">
<br />
		<label for="lookup-doi">Retrieve information</label>
				<span class="description">Use <a onclick="target='_blank'" href="http://www.sherpa.ac.uk/romeo/">SHERPA/RoMEO</a> to check a journal’s open access policies.</span><br />
		<span class="description">Enter a publisher DOI to automatically retrieve information about your item.</span> <br />
		<input type="text" id="lookup-doi" name="lookup-doi" class="long" value="" placeholder="Enter the publisher DOI for this item." />
		<button onClick="javascript:retrieveDOI(); return false;">Retrieve</button>
		<div id="lookup-doi-message"></div>
	</div>
	</div>
	<div id="deposit-title-entry">
<br />
		<label for="deposit-title">Title</label>
		<input type="text" id="deposit-title-unchanged" name="deposit-title-unchanged" size="75" maxlength="255" class="long"
		<?php
		if ( ! empty( $prev_val['deposit-title-unchanged'] ) ) {
			echo ' value="' . wp_kses(
				stripslashes( $prev_val['deposit-title-unchanged'] ), array(
					'b'      => array(),
					'em'     => array(),
					'strong' => array(),
				)
			) . '" '; }
?>
/>
		<span class="description">*</span>
	</div>
	<label for="deposit-genre">Item Type</label>
	<div id="deposit-genre-entry">
		<select name="deposit-genre" id="deposit-genre" class="js-basic-single-required" data-placeholder="Select an item type">
			<option class="level-0" value=""></option>
<?php
	$genre_list   = humcore_deposits_genre_list();
	$posted_genre = '';
if ( ! empty( $prev_val['deposit-genre'] ) ) {
	$posted_genre = sanitize_text_field( $prev_val['deposit-genre'] );
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
		<span class="description">*</span>
	</div>
	<div id="deposit-conference-entries">
	<div id="deposit-conference-title-entry">
		<label for="deposit-conference-title-entry-list">Conference Title</label>
		<input type="text" name="deposit-conference-title" size="75" class="text"
		<?php
		if ( ! empty( $prev_val['deposit-conference-title'] ) ) {
			echo ' value="' . sanitize_text_field( $prev_val['deposit-conference-title'] ) . '" '; }
?>
/>
	</div>

	<div id="deposit-conference-organization-entry">
		<label for="deposit-conference-organization-entry-list">Conference Host Organization</label>
		<input type="text" name="deposit-conference-organization" size="60" class="text"
		<?php
		if ( ! empty( $prev_val['deposit-conference-organization'] ) ) {
			echo ' value="' . sanitize_text_field( $prev_val['deposit-conference-organization'] ) . '" '; }
?>
/>
	</div>

	<div id="deposit-conference-location-entry">
		<label for="deposit-conference-location-entry-list">Conference Location</label>
		<input type="text" name="deposit-conference-location" size="75" class="text"
		<?php
		if ( ! empty( $prev_val['deposit-conference-location'] ) ) {
			echo ' value="' . sanitize_text_field( $prev_val['deposit-conference-location'] ) . '" '; }
?>
/>
	</div>

	<div id="deposit-conference-date-entry">
		<label for="deposit-conference-date-entry-list">Conference Date</label>
		<input type="text" name="deposit-conference-date" size="75" class="text"
		<?php
		if ( ! empty( $prev_val['deposit-conference-date'] ) ) {
			echo ' value="' . sanitize_text_field( $prev_val['deposit-conference-date'] ) . '" '; }
?>
/>
	</div>
	</div>

	<div id="deposit-meeting-entries">
	<div id="deposit-meeting-title-entry">
		<label for="deposit-meeting-title-entry-list">Meeting Title</label>
		<input type="text" name="deposit-meeting-title" size="75" class="text"
		<?php
		if ( ! empty( $prev_val['deposit-meeting-title'] ) ) {
			echo ' value="' . sanitize_text_field( $prev_val['deposit-meeting-title'] ) . '" '; }
?>
/>
	</div>

	<div id="deposit-meeting-organization-entry">
		<label for="deposit-meeting-organization-entry-list">Meeting Host Organization</label>
		<input type="text" name="deposit-meeting-organization" size="60" class="text"
		<?php
		if ( ! empty( $prev_val['deposit-meeting-organization'] ) ) {
			echo ' value="' . sanitize_text_field( $prev_val['deposit-meeting-organization'] ) . '" '; }
?>
/>
	</div>

	<div id="deposit-meeting-location-entry">
		<label for="deposit-meeting-location-entry-list">Meeting Location</label>
		<input type="text" name="deposit-meeting-location" size="75" class="text"
		<?php
		if ( ! empty( $prev_val['deposit-meeting-location'] ) ) {
			echo ' value="' . sanitize_text_field( $prev_val['deposit-meeting-location'] ) . '" '; }
?>
/>
	</div>

	<div id="deposit-meeting-date-entry">
		<label for="deposit-meeting-date-entry-list">Meeting Date</label>
		<input type="text" name="deposit-meeting-date" size="75" class="text"
		<?php
		if ( ! empty( $prev_val['deposit-meeting-date'] ) ) {
			echo ' value="' . sanitize_text_field( $prev_val['deposit-meeting-date'] ) . '" '; }
?>
/>
	</div>
	</div>

	<div id="deposit-institution-entries">
	<div id="deposit-institution-entry">
		<label for="deposit-institution-entry-list">Name of Institution</label>
		<input type="text" name="deposit-institution" size="60" class="text"
		<?php
		if ( ! empty( $prev_val['deposit-institution'] ) ) {
			echo ' value="' . sanitize_text_field( $prev_val['deposit-institution'] ) . '" '; }
?>
/>
	</div>
	</div>

	<div id="deposit-abstract-entry">
		<label for="deposit-abstract">Description or Abstract</label>
		<textarea class="abstract_area" rows="12" autocomplete="off" cols="80" name="deposit-abstract-unchanged" id="deposit-abstract-unchanged">
<?php
if ( ! empty( $prev_val['deposit-abstract-unchanged'] ) ) {
	echo wp_kses(
		stripslashes( $prev_val['deposit-abstract-unchanged'] ), array(
			'b'      => array(),
			'em'     => array(),
			'strong' => array(),
		)
	); }
?>
</textarea>
		<span class="description">*</span>
	<div class="character-count"></div>
	</div>
	<p>Depositor</p>
	<div id="deposit-for-others-flag-entry">
<?php
		$can_deposit_for_others = humcore_deposits_can_deposit_for_others( $user_id );
if ( ! $can_deposit_for_others ) {
?>
<input type="hidden" name="deposit-for-others-flag" id="deposit-for-others-flag" value="" />
<?php } else { ?>
		<span class="description">Is this deposit authored by others?</span>
			<input type="radio" name="deposit-for-others-flag" value="yes"
			<?php
			if ( ! empty( $prev_val['deposit-for-others-flag'] ) ) {
				checked( sanitize_text_field( $prev_val['deposit-for-others-flag'] ), 'yes' ); }
?>
>Yes &nbsp;
			<input type="radio" name="deposit-for-others-flag" value="no"
			<?php
			if ( ! empty( $prev_val['deposit-for-others-flag'] ) ) {
				checked( sanitize_text_field( $prev_val['deposit-for-others-flag'] ), 'no' );
			} else {
				echo 'checked="checked"'; }
?>
>No &nbsp;
<?php
}
	?>
	</div>

	<div id="deposit-on-behalf-flag-entry">
<?php
		$committee_list = humcore_deposits_user_committee_list( $user_id );
if ( empty( $committee_list ) ) {
?>
<input type="hidden" name="deposit-on-behalf-flag" id="deposit-on-behalf-flag" value="" />
<?php } else { ?>
		<span class="description">Is this deposit authored by a group?</span>
			<input type="radio" name="deposit-on-behalf-flag" value="yes"
			<?php
			if ( ! empty( $prev_val['deposit-on-behalf-flag'] ) ) {
				checked( sanitize_text_field( $prev_val['deposit-on-behalf-flag'] ), 'yes' ); }
?>
>Yes &nbsp;
			<input type="radio" name="deposit-on-behalf-flag" value="no"
			<?php
			if ( ! empty( $prev_val['deposit-on-behalf-flag'] ) ) {
				checked( sanitize_text_field( $prev_val['deposit-on-behalf-flag'] ), 'no' );
			} else {
				echo 'checked="checked"'; }
?>
>No &nbsp;
<?php
}
	?>
	</div>

	<div id="deposit-committee-entry">
<?php
if ( empty( $committee_list ) ) {
?>
<input type="hidden" name="deposit-committee" id="deposit-committee" value="" />
<?php	} else { ?>

		<label for="deposit-committee">Deposit Group</label>
		<select name="deposit-committee" id="deposit-committee" class="js-basic-single-optional" data-placeholder="Select group">
			<option class="level-0" selected value=""></option>
<?php
	$posted_committee = '';
if ( ! empty( $prev_val['deposit-committee'] ) ) {
	$posted_committee = sanitize_text_field( $prev_val['deposit-committee'] ); }
foreach ( $committee_list as $committee_key => $committee_value ) {
	printf(
		'			<option class="level-1" %1$s value="%2$s">%3$s</option>' . "\n",
		( $committee_key == $posted_committee ) ? 'selected="selected"' : '',
		$committee_key,
		$committee_value
	);
}
?>
		</select>
<?php
}
	?>
	</div>
	<div id="deposit-other-authors-entry">
		<label for="deposit-other-authors-entry-list">Contributors</label>
		<span class="description">Add any contributors in addition to yourself.</span>
		<div id="deposit-other-authors-entry-list">
		<table id="deposit-other-authors-entry-table"><tbody>
		<tr><td class="noBorderTop" style="width:205px;">
		Given Name
		</td><td class="noBorderTop" style="width:205px;">
		Family Name
		</td><td class="noBorderTop">
		Role
		</td><td class="noBorderTop">
		<input type="button" id="deposit-insert-other-author-button" class="button add_author" value="Add a Contributor">
		</td></tr>
		<tr id="deposit-author-display"><td class="borderTop" style="width:205px;">
		<?php echo esc_html( $user_firstname ); ?>
		<input type="hidden" name="deposit-author-first-name" id="deposit-author-first-name" value="<?php echo esc_html( $user_firstname ); ?>" />
		</td><td class="borderTop" style="width:205px;">
		<?php echo esc_html( $user_lastname ); ?>
		<input type="hidden" name="deposit-author-last-name" id="deposit-author-last-name" value="<?php echo esc_html( $user_lastname ); ?>" />
		</td><td class="borderTop" style="width:230px;">
		<span style="white-space: nowrap;"><input type="radio" name="deposit-author-role" class="styled" value="author"
		<?php
		if ( ! empty( $prev_val['deposit-author-role'] ) ) {
			checked( sanitize_text_field( $prev_val['deposit-author-role'] ), 'author' ); }
?>
>Author &nbsp;</span>
		<span style="white-space: nowrap;"><input type="radio" name="deposit-author-role" class="styled" value="contributor"
		<?php
		if ( ! empty( $prev_val['deposit-author-role'] ) ) {
			checked( sanitize_text_field( $prev_val['deposit-author-role'] ), 'contributor' ); }
?>
>Contributor &nbsp;</span>
		<span style="white-space: nowrap;"><input type="radio" name="deposit-author-role" class="styled" value="editor"
		<?php
		if ( ! empty( $prev_val['deposit-author-role'] ) ) {
			checked( sanitize_text_field( $prev_val['deposit-author-role'] ), 'editor' ); }
?>
>Editor &nbsp;</span>
		<?php if ( humcore_deposits_can_deposit_for_others( $user_id) ) : ?>
		<span style="white-space: nowrap;"><input type="radio" name="deposit-author-role" class="styled" value="submitter"
		<?php
		if ( ! empty( $prev_val['deposit-author-role'] ) ) {
			checked( sanitize_text_field( $prev_val['deposit-author-role'] ), 'submitter' ); }
?>
>Submitter &nbsp;</span>
		<?php endif; ?>
		<span style="white-space: nowrap;"><input type="radio" name="deposit-author-role" class="styled" value="translator"
		<?php
		if ( ! empty( $prev_val['deposit-author-role'] ) ) {
			checked( sanitize_text_field( $prev_val['deposit-author-role'] ), 'translator' ); }
?>
>Translator &nbsp;</span>
		<input type="hidden" name="deposit-author-uni" id="deposit-author-uni"
		<?php
		if ( 'new' === $form_type ) {
			echo ' value="' . $user_login . '" ';
		} elseif ( ! empty( $prev_val['deposit-author-uni'] ) ) {
			echo ' value="' . sanitize_text_field( $prev_val['deposit-author-uni'] ) . '" ';
		}
			?>
			/>
		</td><td class="borderTop">
		</td></tr>

<?php
if ( ! empty( $prev_val['deposit-other-authors-first-name'] ) && ! empty( $prev_val['deposit-other-authors-last-name'] ) ) {
	$other_authors = array_map(
		function ( $first_name, $last_name, $role, $uni ) {
			return array(
				'first_name' => sanitize_text_field( $first_name ),
				'last_name'  => sanitize_text_field( $last_name ),
				'role'       => sanitize_text_field( $role ),
				'uni'        => sanitize_text_field( $uni ),
			); },
		$prev_val['deposit-other-authors-first-name'],
		$prev_val['deposit-other-authors-last-name'],
		$prev_val['deposit-other-authors-role'],
		$prev_val['deposit-other-authors-uni']
	);
	$row_counter = 0;
	foreach ( $other_authors as $author_array ) {
		if ( ! empty( $author_array['first_name'] ) && ! empty( $author_array['last_name'] ) ) {
?>
	<tr><td class="borderTop" style="width:205px;">
	<input type="text" name="deposit-other-authors-first-name[<?php echo $row_counter; ?>]" class="text" value="<?php echo $author_array['first_name']; ?>" />
	</td><td class="borderTop" style="width:205px;">
	<input type="text" name="deposit-other-authors-last-name[<?php echo $row_counter; ?>]" class="text deposit-other-authors-last-name" value="<?php echo $author_array['last_name']; ?>" />
	</td><td class="borderTop" style="width:230px; vertical-align: top;">
	<span style="white-space: nowrap;"><input type="radio" name="deposit-other-authors-role[<?php echo $row_counter; ?>]" class="styled" style="margin-top: 12px;" value="author"
					<?php
					if ( ! empty( $author_array['role'] ) ) {
						checked( sanitize_text_field( $author_array['role'] ), 'author' ); }
?>
>Author &nbsp;</span>
	<span style="white-space: nowrap;"><input type="radio" name="deposit-other-authors-role[<?php echo $row_counter; ?>]" class="styled" style="margin-top: 12px;" value="contributor"
					<?php
					if ( ! empty( $author_array['role'] ) ) {
						checked( sanitize_text_field( $author_array['role'] ), 'contributor' ); }
?>
>Contributor &nbsp;</span>
	<span style="white-space: nowrap;"><input type="radio" name="deposit-other-authors-role[<?php echo $row_counter; ?>]" class="styled" style="margin-top: 12px;" value="editor"
					<?php
					if ( ! empty( $author_array['role'] ) ) {
						checked( sanitize_text_field( $author_array['role'] ), 'editor' ); }
?>
>Editor &nbsp;</span>
	<span style="white-space: nowrap;"><input type="radio" name="deposit-other-authors-role[<?php echo $row_counter; ?>]" class="styled" style="margin-top: 12px;" value="translator"
					<?php
					if ( ! empty( $author_array['role'] ) ) {
						checked( sanitize_text_field( $author_array['role'] ), 'translator' ); }
?>
>Translator &nbsp;</span>
	<input type="hidden" name="deposit-other-authors-uni[<?php echo $row_counter; ?>]" value="<?php echo $author_array['uni']; ?>" />
	</td><td class="borderTop">
	</td></tr>
<?php
		}
		$row_counter++;
	}
}
?>
		</tbody></table>
		</div>
	</div>
	<div id="deposit-group-entry">
		<label for="deposit-group">Groups</label>
		<span class="description">Share this item with up to five groups that you are a member of.<br />Selecting a group will notify members of that group about your deposit.</span><br />
		<select name="deposit-group[]" id="deposit-group[]" class="js-basic-multiple" multiple="multiple" data-placeholder="Select groups">
<?php
	$group_list        = humcore_deposits_group_list( $user_id );
	$posted_group_list = array();
if ( ! empty( $prev_val['deposit-group'] ) ) {
	$posted_group_list = array_map( 'sanitize_text_field', $prev_val['deposit-group'] ); }
foreach ( $group_list as $group_key => $group_value ) {
	printf(
		'			<option class="level-1" %1$s value="%2$s">%3$s</option>' . "\n",
		( $current_group_id == $group_key || in_array( $group_key, $posted_group_list ) ) ? 'selected="selected"' : '',
		$group_key,
		$group_value
	);
}
?>
		</select>
	</div>
	<div id="deposit-subject-entry">
		<label for="deposit-subject">Subjects</label>
		<span class="description">Assign up to ten subject fields to your item.
		<!-- FAST subjects -->
		<select 
			name="deposit-subject[]" 
			id="deposit-subject[]" 
			class="js-basic-multiple-fast-subjects"
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
<?php
$posted_subject_list = array();
if ( ! empty( $prev_val['deposit-subject'] ) ) {
	$posted_subject_list = array_map( 'sanitize_text_field', $prev_val['deposit-subject'] );
}
foreach ( $posted_subject_list as $subject ) {
	printf(
		'			<option class="level-1" %1$s value="%2$s">%3$s</option>' . "\n",
		'selected="selected"',
		$subject,
		$subject
	);
}
?>
		</select>
	</div>
	<div id="deposit-keyword-entry">
		<label for="deposit-keyword">Tags</label>
		<span class="description">Enter up to ten tags to further categorize this item.</span><br />
		<select 
			name="deposit-keyword[]"
			id="deposit-keyword[]"
			class="js-basic-multiple-keywords"
			multiple="multiple" 
			data-placeholder="Enter tags">
<?php
	$posted_keyword_list = array();
if ( ! empty( $prev_val['deposit-keyword'] ) ) {
	$posted_keyword_list = array_map( 'sanitize_text_field', $prev_val['deposit-keyword'] );
}
foreach ( $posted_keyword_list as $keyword_value ) {
	printf(
		'			<option class="level-1" %1$s value="%2$s">%3$s</option>' . "\n",
		'selected="selected"',
		$keyword_value,
		$keyword_value
	);
}
?>
		</select>
	</div>
	<label for="deposit-resource-type">File Type</label>
	<div id="deposit-resource-type-entry">
		<select name="deposit-resource-type" id="deposit-resource-type" class="js-basic-single-optional" data-placeholder="Select a file type" data-allowClear="true">
			<option class="level-0" selected="selected" value=""></option>

<?php
	$resource_type_list   = humcore_deposits_resource_type_list();
	$posted_resource_type = '';
if ( ! empty( $prev_val['deposit-resource-type'] ) ) {
	$posted_resource_type = sanitize_text_field( $prev_val['deposit-resource-type'] );
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
	</div>
	<label for="deposit-language">Language</label>
	<div id="deposit-language-entry">
		<select name="deposit-language" id="deposit-language" class="js-basic-single-optional" data-placeholder="Select a language" data-allowClear="true">
			<option class="level-0" selected="selected" value=""></option>

<?php
	$language_list   = humcore_deposits_language_list();
	$posted_language = '';
if ( ! empty( $prev_val['deposit-language'] ) ) {
	$posted_language = sanitize_text_field( $prev_val['deposit-language'] );
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
	</div>
	<div id="deposit-notes-entry">
		<label for="deposit-notes">Notes or Background</label>
		<span class="description">Any additional information about your item?</span><br />
		<textarea name="deposit-notes-unchanged" class="the-notes" id="deposit-notes-unchanged">
<?php
if ( ! empty( $prev_val['deposit-notes-unchanged'] ) ) {
	echo wp_kses(
		stripslashes( $prev_val['deposit-notes-unchanged'] ), array(
			'b'      => array(),
			'em'     => array(),
			'strong' => array(),
		)
	); }
?>
</textarea>
	<div class="character-count"></div>
	</div>
	<div id="deposit-publication-type-entry">
		<label for="deposit-publication-type">Publication Type</label>
		<div id="deposit-publication-type-entries">
			<span style="white-space:nowrap;"><input type="radio" name="deposit-publication-type" value="book"
			<?php
			if ( ! empty( $prev_val['deposit-publication-type'] ) ) {
				checked( sanitize_text_field( $prev_val['deposit-publication-type'] ), 'book' ); }
?>
>Book &nbsp;</span>
			<span style="white-space:nowrap;"><input type="radio" name="deposit-publication-type" value="book-chapter"
			<?php
			if ( ! empty( $prev_val['deposit-publication-type'] ) ) {
				checked( sanitize_text_field( $prev_val['deposit-publication-type'] ), 'book-chapter' ); }
?>
>Book chapter &nbsp;</span>
			<span style="white-space:nowrap;"><input type="radio" name="deposit-publication-type" value="book-review"
			<?php
			if ( ! empty( $prev_val['deposit-publication-type'] ) ) {
				checked( sanitize_text_field( $prev_val['deposit-publication-type'] ), 'book-review' ); }
?>
>Book review &nbsp;</span>
			<span style="white-space:nowrap;"><input type="radio" name="deposit-publication-type" value="book-section"
			<?php
			if ( ! empty( $prev_val['deposit-publication-type'] ) ) {
				checked( sanitize_text_field( $prev_val['deposit-publication-type'] ), 'book-section' ); }
?>
>Book section &nbsp;</span>
			<span style="white-space:nowrap;"><input type="radio" name="deposit-publication-type" value="proceedings-article"
			<?php
			if ( ! empty( $prev_val['deposit-publication-type'] ) ) {
				checked( sanitize_text_field( $prev_val['deposit-publication-type'] ), 'proceedings-article' ); }
?>
>Conference proceeding &nbsp;</span>
			<span style="white-space:nowrap;"><input type="radio" name="deposit-publication-type" value="journal-article"
			<?php
			if ( ! empty( $prev_val['deposit-publication-type'] ) ) {
				checked( sanitize_text_field( $prev_val['deposit-publication-type'] ), 'journal-article' ); }
?>
>Journal article &nbsp;</span>
			<span style="white-space:nowrap;"><input type="radio" name="deposit-publication-type" value="magazine-section"
			<?php
			if ( ! empty( $prev_val['deposit-publication-type'] ) ) {
				checked( sanitize_text_field( $prev_val['deposit-publication-type'] ), 'magazine-section' ); }
?>
>Magazine section &nbsp;</span>
			<span style="white-space:nowrap;"><input type="radio" name="deposit-publication-type" value="monograph"
			<?php
			if ( ! empty( $prev_val['deposit-publication-type'] ) ) {
				checked( sanitize_text_field( $prev_val['deposit-publication-type'] ), 'monograph' ); }
?>
>Monograph &nbsp;</span>
			<span style="white-space:nowrap;"><input type="radio" name="deposit-publication-type" value="newspaper-article"
			<?php
			if ( ! empty( $prev_val['deposit-publication-type'] ) ) {
				checked( sanitize_text_field( $prev_val['deposit-publication-type'] ), 'newspaper-article' ); }
?>
>Newspaper article &nbsp;</span>
			<span style="white-space:nowrap;"><input type="radio" name="deposit-publication-type" value="online-publication"
			<?php
			if ( ! empty( $prev_val['deposit-publication-type'] ) ) {
				checked( sanitize_text_field( $prev_val['deposit-publication-type'] ), 'online-publication' ); }
?>
>Online publication &nbsp;</span>
			<span style="white-space:nowrap;"><input type="radio" name="deposit-publication-type" value="podcast"
			<?php
			if ( ! empty( $prev_val['deposit-publication-type'] ) ) {
				checked( sanitize_text_field( $prev_val['deposit-publication-type'] ), 'podcast' ); }
?>
>Podcast &nbsp;</span>
			<span style="white-space:nowrap;"><input type="radio" name="deposit-publication-type" value="none"
			<?php
			if ( ! empty( $prev_val['deposit-publication-type'] ) ) {
				checked( sanitize_text_field( $prev_val['deposit-publication-type'] ), 'none' );
			} else {
				echo 'checked="checked"'; }
?>
>Not published &nbsp;</span>
	</div>
	</div>
<br />
<?php format_book_input( $prev_val ); ?>
<?php format_book_chapter_input( $prev_val ); ?>
<?php format_book_review_input( $prev_val ); ?>
<?php format_book_section_input( $prev_val ); ?>
<?php format_journal_article_input( $prev_val ); ?>
<?php format_magazine_section_input( $prev_val ); ?>
<?php format_monograph_input( $prev_val ); ?>
<?php format_newspaper_article_input( $prev_val ); ?>
<?php format_online_publication_input( $prev_val ); ?>
<?php format_podcast_input( $prev_val ); ?>
<?php format_proceedings_article_input( $prev_val ); ?>
	<div id="deposit-non-published-entries">

		<div id="deposit-non-published-date-entry">
			<label for="deposit-non-published-date">Date of Creation</label>
			<input type="text" id="deposit-non-published-date" name="deposit-non-published-date" class="text"
			<?php
			if ( ! empty( $prev_val['deposit-non-published-date'] ) ) {
				echo ' value="' . sanitize_text_field( $prev_val['deposit-non-published-date'] ) . '" '; }
?>
/>
		</div>

	</div>
	<div id="deposit-license-type-entry">
		<label for="deposit-license-type">Creative Commons License</label>
		<span class="description">By default, and in accordance with section 2 of the <em>Commons</em> terms of service, no one may reuse this content in any way. Should you wish to allow others to distribute, display, modify, or otherwise reuse your content, please attribute it with the appropriate Creative Commons license from the drop-down menu below. See <a onclick="target='_blank'" href="http://creativecommons.org/licenses/">this page</a> for more information about the different types of Creative Commons licenses.</span><br /><br />
		<select name="deposit-license-type" id="deposit-license-type" class="js-basic-single-required">
<?php
	$license_type_list   = humcore_deposits_license_type_list();
	$posted_license_type = '';
if ( ! empty( $prev_val['deposit-license-type'] ) ) {
	$posted_license_type = sanitize_text_field( $prev_val['deposit-license-type'] );
}
foreach ( $license_type_list as $license_key => $license_value ) {
	printf(
		'			<option class="level-1" %1$s value="%2$s">%3$s</option>' . "\n",
		( $license_key == $posted_license_type ) ? 'selected="selected"' : '',
		$license_key,
		$license_value
	);
}
?>
		</select>
		<span class="description">*</span>
	</div>
		<div id="deposit-embargoed-entry">
				<label for="deposit-embargoed-flag">Embargo this deposit?</label>
						<input type="radio" name="deposit-embargoed-flag" value="yes"
						<?php
						if ( ! empty( $prev_val['deposit-embargoed-flag'] ) ) {
							checked( sanitize_text_field( $prev_val['deposit-embargoed-flag'] ), 'yes' ); }
?>
>Yes &nbsp;
						<input type="radio" name="deposit-embargoed-flag" value="no"
						<?php
						if ( ! empty( $prev_val['deposit-embargoed-flag'] ) ) {
							checked( sanitize_text_field( $prev_val['deposit-embargoed-flag'] ), 'no' );
						} else {
							echo 'checked="checked"'; }
?>
>No &nbsp;
		</div>

	<div id="deposit-embargoed-entries">
		<label for="deposit-embargo-length">Embargo Length</label>
		<div id="deposit-embargo-length-entry">
				<span class="description">Use <a onclick="target='_blank'" href="http://www.sherpa.ac.uk/romeo/">SHERPA/RoMEO</a> to check a journal’s open access policies.</span><br />
		<span class="description">Enter the length of time (up to two years from now) after which this item should become available.</span> <br />
				<select name="deposit-embargo-length" id="deposit-embargo-length" class="js-basic-single-required" data-placeholder="Select the embargo length." data-allowClear="true">
						<option class="level-0" selected="selected" value=""></option>

<?php
		$embargo_length_list   = humcore_deposits_embargo_length_list();
		$posted_embargo_length = '';
if ( ! empty( $prev_val['deposit-embargo-length'] ) ) {
		$posted_embargo_length = sanitize_text_field( $prev_val['deposit-embargo-length'] );
}
foreach ( $embargo_length_list as $embargo_key => $embargo_value ) {
		printf(
			'                        <option class="level-0" %1$s value="%2$s">%3$s</option>' . "\n",
			( $embargo_key == $posted_embargo_length ) ? 'selected="selected"' : '',
			$embargo_key,
			$embargo_value
		);
}
?>
				</select>
		<span class="description">*</span>
		</div>
	</div>
<br />
	<input id="deposit-submit" name="deposit-submit" type="submit" value="<?php echo ucfirst( $deposit_button_label ); ?>" />
	<?php
	$wp_referer = wp_get_referer();
		printf(
			'<a id="deposit-cancel" href="%1$s" class="button white">Cancel</a>',
			( ! empty( $wp_referer ) && ! strpos( $wp_referer, 'item/new' ) ) ? $wp_referer : '/deposits/'
		);
	?>
	</div>

</form>
	<br /><span class="description">Required fields are marked *.</span><br />
<br />

<div id="deposit-warning-dialog">
</div>
<div id="deposit-error-dialog">
</div>

<?php

}

/**
 * Output deposits list entry html.
 */
function humcore_deposits_list_entry_content() {

	$metadata     = (array) humcore_get_current_deposit();
	$authors      = array_filter( $metadata['authors'] );
	$authors_list = implode( ', ', $authors );
	$item_url     = sprintf( '%1$s/deposits/item/%2$s', HC_SITE_URL, $metadata['pid'] );
?>
<ul class="deposits-item">
<li>
<span class="list-item-label">Title</span>
<span class="list-item-value"><?php echo $metadata['title']; ?></span>
</li>
<li>
<span class="list-item-label">URL</span>
<span class="list-item-value"><a href="<?php echo esc_url( $item_url ); ?>"><?php echo esc_url( $item_url ); ?></a></span>
</li>
<li>
<span class="list-item-label">Author(s)</span>
<span class="list-item-value"><?php echo esc_html( $authors_list ); ?></span>
</li>
<li>
<span class="list-item-label">Date</span>
<span class="list-item-value"><?php echo esc_html( $metadata['date'] ); ?></span>
</li>
</ul>
<?php

}

/**
 * Output deposits feed item html.
 */
function humcore_deposits_feed_item_content() {

	$metadata = (array) humcore_get_current_deposit();

	$contributors      = array_filter( $metadata['authors'] );
	$contributor_uni   = humcore_deposit_parse_author_info( $metadata['author_info'][0], 1 );
	$contributor_type  = humcore_deposit_parse_author_info( $metadata['author_info'][0], 3 );
	$contributors_list = array_map( null, $contributors, $contributor_uni, $contributor_type );
	$authors_list      = array();
	$authors_list      = '';

	foreach ( $contributors_list as $contributor ) {
		if ( in_array( $contributor[2], array( 'creator', 'author' ) ) || empty( $contributor[2] ) ) {
			$authors_list .= "\t\t" . sprintf( '<dc:creator>%s</dc:creator>', htmlspecialchars( $contributor[0], ENT_QUOTES ) );
		}
	}

//	foreach ( $authors as $author ) {
//	}

	$item_url = sprintf( '%1$s/deposits/item/%2$s', HC_SITE_URL, $metadata['pid'] );
	$pub_date = DateTime::createFromFormat( 'Y-m-d\TH:i:s\Z', $metadata['record_creation_date'] );
?>
		<title><?php echo htmlspecialchars( $metadata['title'], ENT_QUOTES ); ?></title>
		<link><?php echo esc_url( $item_url ); ?></link>
		<pubDate><?php echo $pub_date->format( 'D, d M Y H:i:s +0000' ); ?></pubDate>
		<?php echo $authors_list; ?>
		<guid isPermaLink="false"><?php echo esc_url( $item_url ); ?></guid>
		<description><![CDATA[<?php echo $metadata['abstract']; ?>]]></description>
<?php

}

/**
 * Output deposits loop entry html.
 */
function humcore_deposits_entry_content() {

	$metadata = (array) humcore_get_current_deposit();

	if ( ! empty( $metadata['group'] ) ) {
		$groups = array_filter( $metadata['group'] );
	}
	if ( ! empty( $groups ) ) {
		$group_list = implode( ', ', array_map( 'humcore_linkify_group', $groups ) );
	}
	if ( ! empty( $metadata['subject'] ) ) {
		$subjects = array_filter( $metadata['subject'] );
	}
	if ( ! empty( $subjects ) ) {
		$subject_list = implode( ', ', array_map( 'humcore_linkify_subject', $subjects ) );
	}
	if ( ! empty( $metadata['keyword'] ) ) {
			$keywords               = array_filter( $metadata['keyword'] );
			$keyword_display_values = array_filter( explode( ', ', $metadata['keyword_display'] ) );
	}
	if ( ! empty( $keywords ) ) {
			$keyword_list = implode( ', ', array_map( 'humcore_linkify_tag', $keywords, $keyword_display_values ) );
	}

	$contributors           = array_filter( $metadata['authors'] );
	$contributor_uni        = humcore_deposit_parse_author_info( $metadata['author_info'][0], 1 );
	$contributor_type       = humcore_deposit_parse_author_info( $metadata['author_info'][0], 3 );
	$contributors_list      = array_map( null, $contributors, $contributor_uni, $contributor_type );
	$authors_list           = array();
	$contribs_list          = array();
	$editors_list           = array();
	$translators_list       = array();
	$project_directors_list = array();
	foreach ( $contributors_list as $contributor ) {
		if ( in_array( $contributor[2], array( 'creator', 'author' ) ) || empty( $contributor[2] ) ) {
			$authors_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
		} elseif ( 'contributor' === $contributor[2] ) {
						$contribs_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
		} elseif ( 'editor' === $contributor[2] ) {
						$editors_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
		} elseif ( 'project director' === $contributor[2] ) {
						$project_directors_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
		} elseif ( 'translator' === $contributor[2] ) {
						$translators_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
		}
	}
	//$item_url = sprintf( '%1$s/deposits/item/%2$s', HC_SITE_URL, $metadata['pid'] );
	$item_url = sprintf( '/deposits/item/%1$s', $metadata['pid'] );
?>
<h4 class="bp-group-documents-title"><a href="<?php echo esc_url( $item_url ); ?>/"><?php echo $metadata['title_unchanged']; ?></a></h4>
<div class="bp-group-documents-meta">
<dl class='defList'>
<?php if ( ! empty( $project_directors_list ) ) : ?>
<dt><?php _e( 'Project Director(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $project_directors_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $authors_list ) ) : ?>
<dt><?php _e( 'Author(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $authors_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $contirbs_list ) ) : ?>
<dt><?php _e( 'Contributor(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $editors_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $editors_list ) ) : ?>
<dt><?php _e( 'Editor(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $editors_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $translators_list ) ) : ?>
<dt><?php _e( 'Translator(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $translators_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['date_issued'] ) ) : ?>
<dt><?php _e( 'Date:', 'humcore_domain' ); ?></dt>
<dd><a href="/deposits/?facets[pub_date_facet][]=<?php echo urlencode( $metadata['date_issued'] ); ?>"><?php echo esc_html( $metadata['date_issued'] ); ?></a></dd>
<?php endif; ?>
<?php if ( ! empty( $groups ) ) : ?>
<dt><?php _e( 'Group(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo $group_list; // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $subjects ) ) : ?>
<dt><?php _e( 'Subject(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo $subject_list; // XSS OK. ?></dd>
<?php endif; ?>

<?php if ( ! empty( $metadata['genre'] ) ) : ?>
<dt><?php _e( 'Item Type:', 'humcore_domain' ); ?></dt>
<dd><a href="/deposits/?facets[genre_facet][]=<?php echo urlencode( $metadata['genre'] ); ?>"><?php echo esc_html( $metadata['genre'] ); ?></a></dd>
<?php endif; ?>
<?php if ( ! empty( $keywords ) ) : ?>
<dt><?php _e( 'Tag(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo $keyword_list; // XSS OK. ?></dd>
<?php endif; ?>
<!-- <dt><?php _e( 'Permanent URL:', 'humcore_domain' ); ?></dt> -->
<!-- <dd><a href="<?php echo esc_attr( $item_url ); ?>"><?php echo esc_html( $metadata['handle'] ); ?></a></dd> -->
<?php
$highlights = $metadata['highlights'];
if ( ! empty( $highlights ) ) {
?>
<dt>Search term matches:</dt><dd></dd>
<?php
foreach ( $highlights as $field => $highlight ) {
  // for Subject ONLY we extract the subject from "1234:Music:topic"
  if($field == "Subject"){
    $subject_list = array();
    foreach ( $highlight as $fast_subject ){
      [$id, $subject, $topic] = explode(":", $fast_subject);
      $subject_list[] = $subject;
    }
    $highlight = $subject_list;
  }
	echo '<dt>' . $field . '</dt>';
	echo '<dd>... ' . implode( ' ( ... ) ', $highlight ) . ' ...</dd>';
}
}
?>
</dl>
</div>
<br style='clear:both'>
<?php
}

/**
 * Output deposits single item html.
 */
function humcore_deposit_item_content() {

	$metadata = (array) humcore_get_current_deposit();

	if ( ! empty( $metadata['group'] ) ) {
		$groups = array_filter( $metadata['group'] );
	}
	if ( ! empty( $groups ) ) {
		$group_list = implode( ', ', array_map( 'humcore_linkify_group', $groups ) );
	}
	if ( ! empty( $metadata['subject'] ) ) {
		$subjects = array_filter( $metadata['subject'] );
	}
	if ( ! empty( $subjects ) ) {
		$subject_list = implode( ', ', array_map( 'humcore_linkify_subject', $subjects ) );
	}
	if ( ! empty( $metadata['keyword'] ) ) {
			$keywords               = array_filter( $metadata['keyword'] );
			$keyword_display_values = array_filter( explode( ', ', $metadata['keyword_display'] ) );
	}
	if ( ! empty( $keywords ) ) {
			$keyword_list = implode( ', ', array_map( 'humcore_linkify_tag', $keywords, $keyword_display_values ) );
	}

	$contributors           = array_filter( $metadata['authors'] );
	$contributor_uni        = humcore_deposit_parse_author_info( $metadata['author_info'][0], 1 );
	$contributor_type       = humcore_deposit_parse_author_info( $metadata['author_info'][0], 3 );
	$contributors_list      = array_map( null, $contributors, $contributor_uni, $contributor_type );
	$authors_list           = array();
	$contribs_list          = array();
	$editors_list           = array();
	$translators_list       = array();
	$project_directors_list = array();

	foreach ( $contributors_list as $contributor ) {
		if ( in_array( $contributor[2], array( 'creator', 'author' ) ) || empty( $contributor[2] ) ) {
				$authors_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
		} elseif ( 'contributor' === $contributor[2] ) {
				$contribs_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
		} elseif ( 'editor' === $contributor[2] ) {
				$editors_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
		} elseif ( 'project director' === $contributor[2] ) {
				$project_directors_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
		} elseif ( 'translator' === $contributor[2] ) {
				$translators_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
		}
	}

	$wpmn_record_identifier = array();
	$wpmn_record_identifier = explode( '-', $metadata['record_identifier'] );
	// handle legacy MLA value
	if ( $wpmn_record_identifier[0] === $metadata['record_identifier'] ) {
		$wpmn_record_identifier[0] = '1';
		$wpmn_record_identifier[1] = $metadata['record_identifier'];
	}
		$switched = false;
	if ( get_current_blog_id() != $wpmn_record_identifier[0] ) {
		switch_to_blog( $wpmn_record_identifier[0] );
		$switched = true;
	}

	$site_url        = get_option( 'siteurl' );
	$deposit_post_id = $wpmn_record_identifier[1];
	$post_data       = get_post( $deposit_post_id );
	$post_metadata   = json_decode( get_post_meta( $deposit_post_id, '_deposit_metadata', true ), true );

	$update_time = '';
	if ( ! empty( $metadata['record_change_date'] ) ) {
		$update_time = human_time_diff( strtotime( $metadata['record_change_date'] ) );
	}
	$file_metadata              = json_decode( get_post_meta( $deposit_post_id, '_deposit_file_metadata', true ), true );
	$content_downloads_meta_key = sprintf( '_total_downloads_%s_%s', $file_metadata['files'][0]['datastream_id'], $file_metadata['files'][0]['pid'] );
	$total_content_downloads    = get_post_meta( $deposit_post_id, $content_downloads_meta_key, true );
	$content_views_meta_key     = sprintf( '_total_views_%s_%s', $file_metadata['files'][0]['datastream_id'], $file_metadata['files'][0]['pid'] );
	$total_content_views        = get_post_meta( $deposit_post_id, $content_views_meta_key, true );
	$views_meta_key             = sprintf( '_total_views_%s', $metadata['pid'] );
	$total_views                = get_post_meta( $deposit_post_id, $views_meta_key, true ) + 1; // Views counted at item page level.
	if ( bp_loggedin_user_id() != $post_data->post_author && ! humcore_is_bot_user_agent() ) {
		$post_meta_id = update_post_meta( $deposit_post_id, $views_meta_key, $total_views );
	}
	$download_url   = sprintf(
		'%s/deposits/download/%s/%s/%s/',
		$site_url,
		$file_metadata['files'][0]['pid'],
		$file_metadata['files'][0]['datastream_id'],
		$file_metadata['files'][0]['filename']
	);
	$view_url       = sprintf(
		'%s/deposits/view/%s/%s/%s/',
		$site_url,
		$file_metadata['files'][0]['pid'],
		$file_metadata['files'][0]['datastream_id'],
		$file_metadata['files'][0]['filename']
	);
	$metadata_url   = sprintf(
		'%s/deposits/download/%s/%s/%s/',
		$site_url,
		$metadata['pid'],
		'descMetadata',
		'xml'
	);
	$file_type_data = wp_check_filetype( $file_metadata['files'][0]['filename'], wp_get_mime_types() );
	$file_type_icon = sprintf(
		'<img class="deposit-icon" src="%s" alt="%s" />',
		plugins_url( 'assets/' . esc_attr( $file_type_data['ext'] ) . '-icon-48x48.png', __FILE__ ),
		esc_attr( $file_type_data['ext'] )
	);
	if ( in_array( $file_type_data['type'], array( 'application/pdf', 'text/html', 'text/plain' ) ) ||
		in_array( strstr( $file_type_data['type'], '/', true ), array( 'audio', 'image', 'video' ) ) ) {
		$content_viewable = true;
	} else {
		$content_viewable = false;
	}

	if ( ! empty( $file_metadata['files'][0]['thumb_filename'] ) ) {
		$thumb_url = sprintf(
			'<img class="deposit-thumb" src="%s/deposits/view/%s/%s/%s/" alt="%s" />',
			$site_url,
			$file_metadata['files'][0]['pid'],
			$file_metadata['files'][0]['thumb_datastream_id'],
			$file_metadata['files'][0]['thumb_filename'],
			'thumbnail'
		);
	} else {
		$thumb_url = '';
	}

	$share_command = humcore_social_sharing_shortcode( 'display', $metadata );

	if ( $switched ) {
			restore_current_blog();
	}
	//$item_url = sprintf( '%1$s/deposits/item/%2$s', HC_SITE_URL, $metadata['pid'] );
	$item_url  = sprintf( '/deposits/item/%1$s', $metadata['pid'] );
	$edit_url  = sprintf( '/deposits/item/%1$s/edit/', $metadata['pid'] );
	$admin_url = sprintf( '%1$s/wp-admin/post.php?post=%2$s&action=edit', $site_url, $wpmn_record_identifier[1] );

?>

<h3 class="bp-group-documents-title"><?php echo $metadata['title_unchanged']; ?></h3>
<div class="bp-group-documents-meta">
<dl class='defList'>
<?php if ( ! empty( $project_directors_list ) ) : ?>
<dt><?php _e( 'Project Director(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $project_directors_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $authors_list ) ) : ?>
<dt><?php _e( 'Author(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $authors_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $contribs_list ) ) : ?>
<dt><?php _e( 'Contributor(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $contribs_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $editors_list ) ) : ?>
<dt><?php _e( 'Editor(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $editors_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $translators_list ) ) : ?>
<dt><?php _e( 'Translator(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $translators_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['date_issued'] ) ) : ?>
<dt><?php _e( 'Date:', 'humcore_domain' ); ?></dt>
<dd><a href="/deposits/?facets[pub_date_facet][]=<?php echo urlencode( $metadata['date_issued'] ); ?>"><?php echo esc_html( $metadata['date_issued'] ); ?></a></dd>
<?php endif; ?>
<?php if ( ! empty( $groups ) ) : ?>
<dt><?php _e( 'Group(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo $group_list; // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $subjects ) ) : ?>
<dt><?php _e( 'Subject(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo $subject_list; // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['genre'] ) ) : ?>
<dt><?php _e( 'Item Type:', 'humcore_domain' ); ?></dt>
<dd><a href="/deposits/?facets[genre_facet][]=<?php echo urlencode( $metadata['genre'] ); ?>"><?php echo esc_html( $metadata['genre'] ); ?></a></dd>
<?php endif; ?>
<?php if ( 'Conference paper' == $metadata['genre'] || 'Conference proceeding' == $metadata['genre'] || 'Conference poster' == $metadata['genre'] ) : ?>
<?php if ( ! empty( $metadata['conference_title'] ) ) : ?>
<dt><?php _e( 'Conf. Title:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['conference_title']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['conference_organization'] ) ) : ?>
<dt><?php _e( 'Conf. Org.:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['conference_organization']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['conference_location'] ) ) : ?>
<dt><?php _e( 'Conf. Loc.:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['conference_location']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['conference_date'] ) ) : ?>
<dt><?php _e( 'Conf. Date:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['conference_date']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php elseif ( 'Presentation' == $metadata['genre'] ) : ?>
<?php if ( ! empty( $metadata['meeting_title'] ) ) : ?>
<dt><?php _e( 'Meeting Title:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['meeting_title']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['meeting_organization'] ) ) : ?>
<dt><?php _e( 'Meeting Org.:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['meeting_organization']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['meeting_location'] ) ) : ?>
<dt><?php _e( 'Meeting Loc.:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['meeting_location']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['meeting_date'] ) ) : ?>
<dt><?php _e( 'Meeting Date:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['meeting_date']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php
elseif ( 'Dissertation' == $metadata['genre'] || 'Technical report' == $metadata['genre'] || 'Thesis' == $metadata['genre'] ||
		'White paper' == $metadata['genre'] ) :
			?>
<?php if ( ! empty( $metadata['institution'] ) ) : ?>
<dt><?php _e( 'Institution:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['institution']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php endif; ?>
<?php if ( ! empty( $keywords ) ) : ?>
<dt><?php _e( 'Tag(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo $keyword_list; // XSS OK. ?></dd>
<?php endif; ?>
<dt><?php _e( 'Permanent URL:', 'humcore_domain' ); ?></dt>
<dd><a href="<?php echo esc_attr( $item_url ); ?>"><?php echo esc_html( $metadata['handle'] ); ?></a></dd>
<dt><?php _e( 'Abstract:', 'humcore_domain' ); // Google Scholar wants Abstract. ?></dt>
<?php if ( ! empty( $metadata['abstract_unchanged'] ) ) : ?>
<dd><?php echo $metadata['abstract_unchanged']; ?></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['notes_unchanged'] ) ) : ?>
<dt><?php _e( 'Notes:', 'humcore_domain' ); ?></dt>
<dd><?php echo $metadata['notes_unchanged']; ?></dd>
<?php endif; ?>
<dt><?php _e( 'Metadata:', 'humcore_domain' ); ?></dt>
<dd><a onclick="target='_blank'" class="bp-deposits-metadata" title="MODS Metadata" rel="nofollow" href="<?php echo esc_url( $metadata_url ); ?>">xml</a></dd>
<?php
if ( 'book' == $post_metadata['publication-type'] ) :
		humcore_display_book_pub_metadata( $metadata );
elseif ( 'book-chapter' == $post_metadata['publication-type'] ) :
		humcore_display_book_chapter_pub_metadata( $metadata );
elseif ( 'book-review' == $post_metadata['publication-type'] ) :
		humcore_display_book_review_pub_metadata( $metadata );
elseif ( 'book-section' == $post_metadata['publication-type'] ) :
		humcore_display_book_section_pub_metadata( $metadata );
elseif ( 'journal-article' == $post_metadata['publication-type'] ) :
		humcore_display_journal_article_pub_metadata( $metadata );
elseif ( 'magazine-section' == $post_metadata['publication-type'] ) :
		humcore_display_magazine_section_pub_metadata( $metadata );
elseif ( 'monograph' == $post_metadata['publication-type'] ) :
		humcore_display_monograph_pub_metadata( $metadata );
elseif ( 'newspaper-article' == $post_metadata['publication-type'] ) :
		humcore_display_newspaper_article_pub_metadata( $metadata );
elseif ( 'online-publication' == $post_metadata['publication-type'] ) :
		humcore_display_online_publication_pub_metadata( $metadata );
elseif ( 'podcast' == $post_metadata['publication-type'] ) :
		humcore_display_podcast_pub_metadata( $metadata );
elseif ( 'proceedings-article' == $post_metadata['publication-type'] ) :
		humcore_display_proceedings_article_pub_metadata( $metadata );
endif;
?>
<dt><?php _e( 'Status:', 'humcore_domain' ); ?></dt> 
<?php if ( 'draft' === $post_data->post_status ) : ?>
<dd><?php echo '<strong>Provisional</strong>'; ?>
<?php elseif ( 'pending' === $post_data->post_status ) : ?>
<dd><?php echo 'Pending Review'; ?>
<?php elseif ( 'publish' === $post_data->post_status ) : ?>
<dd><?php echo 'Published'; ?>
<?php elseif ( 'future' === $post_data->post_status ) : ?>
<dd><?php echo 'Scheduled'; ?>
<?php endif; ?>
<?php if ( humcore_user_can_edit_deposit( $wpmn_record_identifier ) ) : ?>
&nbsp; &nbsp; <a class="bp-deposits-edit-button" title="Edit this Deposit" href="<?php echo esc_url( $edit_url ); ?>"><?php _e( 'Edit this Deposit', 'humcore_domain' ); ?></a>
<?php endif; ?>
<?php if ( hcommons_is_global_super_admin() ) : ?>
&nbsp; &nbsp; <a class="bp-deposits-edit-button" title="Admin Edit" href="<?php echo esc_url( $admin_url ); ?>"><?php _e( 'Admin Edit', 'humcore_domain' ); ?></a>
<?php endif; ?>
</dd>
<?php if ( ! empty( $update_time ) ) : ?>
<dt><?php _e( 'Last Updated:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $update_time . ' ago'; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $thumb_url ) ) : ?>
<dt><?php _e( 'Preview:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $thumb_url;// XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $post_metadata['type_of_license'] ) ) : ?>
<dt><?php _e( 'License:', 'humcore_domain' ); ?></dt>
<dd><?php echo humcore_linkify_license( $post_metadata['type_of_license'] ); ?></dd>
<?php endif; ?>
<?php if ( in_array( $post_data->post_status, array( 'publish' ) ) && ! empty( $share_command ) ) : ?>
<dt><?php _e( 'Share this:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo do_shortcode( $share_command ); ?></span></dd>
<?php endif; ?>
</dl>
<?php if ( 'yes' === $post_metadata['embargoed'] && '2099/12/31' == date( 'Y/m/d', strtotime( $post_metadata['embargo_end_date'] ) ) ) { ?>
<div><h4>This deposit has been removed as requested by a subsequent publisher. <br />See https://team.hcommons.org/2022/07/05/on-prior-publication/</h4></div> 
<?php } elseif ( 'yes' === $post_metadata['embargoed'] && current_time( 'Y/m/d' ) < date( 'Y/m/d', strtotime( $post_metadata['embargo_end_date'] ) ) ) { ?>
<div><h4>This item will be available for download beginning <?php echo $post_metadata['embargo_end_date']; ?></h4></div> 
<?php } elseif ( ! $post_data ) { ?>
<div><h3>Note</h3>
There is a problem retrieving some of the data for this item. This error has been logged.
</div>
<?php humcore_write_error_log( 'error', '*****HumCORE Data Error*****', $wpmn_record_identifier ); ?>
<?php } else { ?>
<div><h4><?php _e( 'Downloads', 'humcore_domain' ); ?></h4>
<div class="doc-attachments">
	<table class="view_downloads">
	<tr>
		<td class="prompt"><?php _e( 'Item Name:', 'humcore_domain' ); ?></td>
		<td class="value"><?php echo $file_type_icon . ' ' . esc_attr( $file_metadata['files'][0]['filename'] ); // XSS OK. ?></td>
	</tr>
	<tr>
		<td class="prompt">&nbsp;</td>
		<td class="value"><a class="bp-deposits-download button" title="Download" rel="nofollow" href="<?php echo esc_url( $download_url ); ?>"><?php _e( 'Download', 'humcore_domain' ); ?></a>
<?php if ( $content_viewable ) : ?>
		<a onclick="target='_blank'" class="bp-deposits-view button" title="View" rel="nofollow" href="<?php echo esc_url( $view_url ); ?>"><?php _e( 'View in browser', 'humcore_domain' ); ?></a>
<?php endif; ?>
		</td>
	</tr>
	</table>
</div>
<div class="doc-statistics">
	<table class="view_statistics">
	<tr>
		<td class="prompt">Activity:</td>
		<td class="value">
		<?php
		_e( 'Downloads:', 'humcore_domain' );
		echo ' ' . esc_html( $total_content_downloads + $total_content_views );
?>
</td>
	</tr>
	</table>
</div>
</div>
<?php } ?>
</div>
<br style='clear:both'>
<?php //echo do_shortcode( '[pdfjs-viewer url="' . $view_url . '" viewer_height="640px;" print="false" download="false"]' ); ?>
<?php //echo do_shortcode( '[embeddoc url="' . $view_url . '" width="550px" height="700px" viewer="google"]' ); ?>
<?php //echo apply_filters( 'the_content', rtrim( $view_url, '/' ) ); ?>
<?php //humcore_embed_resource( $item_url, $file_metadata ); ?>
<?php

}

/**
 * format subjects for newly depositd item (for review by the author)
 */
function text_format_subject($subject) {
	$formatted_subject = "";
	// if $subject contains colons, it is a FAST subject ("ID:subject:facet")
	// else it is a legacy/MLA subject ("subject")
	if (str_contains($subject, ':')) {
		[$fast_id, $fast_subject, $fast_facet] = explode(":", $subject);
		$formatted_subject = $fast_subject . " (" . $fast_facet . ")";
	} else {
		$formatted_subject = $subject;
	}
	return $formatted_subject;
}
/**
 * Output deposits single item review page html.
 */
function humcore_deposit_item_review_content() {

	$metadata = (array) humcore_get_current_deposit();

	if ( ! empty( $metadata['group'] ) ) {
			$groups = array_filter( $metadata['group'] );
	}
	if ( ! empty( $groups ) ) {
			$group_list = implode( ', ', array_map( 'esc_html', $groups ) );
	}
	if ( ! empty( $metadata['subject'] ) ) {
			// remove any empty elements
			$subjects = array_filter( $metadata['subject'] );
			$formatted_subjects = array_map( 'text_format_subject', $subjects );
	}
	if ( ! empty( $formatted_subjects ) ) {
			$subject_list = implode( ', ', array_map( 'esc_html', $formatted_subjects ) );
	}
	if ( ! empty( $metadata['keyword'] ) ) {
			$keywords               = array_filter( $metadata['keyword'] );
			$keyword_display_values = explode( ', ', array_filter( $metadata['keyword_display'] ) );
	}
	if ( ! empty( $keywords ) ) {
			$keyword_list = implode( ', ', array_map( 'esc_html', $keywords, $keyword_display_values ) );
	}

	$contributors           = array_filter( $metadata['authors'] );
	$contributor_uni        = humcore_deposit_parse_author_info( $metadata['author_info'][0], 1 );
	$contributor_type       = humcore_deposit_parse_author_info( $metadata['author_info'][0], 3 );
	$contributors_list      = array_map( null, $contributors, $contributor_uni, $contributor_type );
	$authors_list           = array();
	$contribs_list          = array();
	$editors_list           = array();
	$translators_list       = array();
	$project_directors_list = array();

	foreach ( $contributors_list as $contributor ) {
		if ( in_array( $contributor[2], array( 'creator', 'author' ) ) || empty( $contributor[2] ) ) {
				$authors_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
		} elseif ( 'contributor' === $contributor[2] ) {
				$contribs_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
		} elseif ( 'editor' === $contributor[2] ) {
				$editors_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
		} elseif ( 'project director' === $contributor[2] ) {
				$project_directors_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
		} elseif ( 'translator' === $contributor[2] ) {
				$translators_list[] = humcore_linkify_author( $contributor[0], $contributor[1], $contributor[2] );
		}
	}

	//$item_url = sprintf( '%1$s/deposits/item/%2$s', HC_SITE_URL, $metadata['pid'] );
	$item_url = sprintf( '/deposits/item/%1$s', $metadata['pid'] );
	$edit_url = sprintf( '/deposits/item/%1$s/edit/', $metadata['pid'] );

	$wpmn_record_identifier = array();
	$wpmn_record_identifier = explode( '-', $metadata['record_identifier'] );
	// handle legacy MLA value
	if ( $wpmn_record_identifier[0] === $metadata['record_identifier'] ) {
			$wpmn_record_identifier[0] = '1';
			$wpmn_record_identifier[1] = $metadata['record_identifier'];
	}
		$switched = false;
	if ( get_current_blog_id() != $wpmn_record_identifier[0] ) {
		switch_to_blog( $wpmn_record_identifier[0] );
		$switched = true;
	}

	$site_url            = get_option( 'siteurl' );
		$deposit_post_id = $wpmn_record_identifier[1];
		$post_data       = get_post( $deposit_post_id );
		$post_metadata   = json_decode( get_post_meta( $deposit_post_id, '_deposit_metadata', true ), true );

	$update_time = '';
	if ( ! empty( $metadata['record_change_date'] ) ) {
		$update_time = human_time_diff( strtotime( $metadata['record_change_date'] ) );
	}
	$file_metadata                  = json_decode( get_post_meta( $deposit_post_id, '_deposit_file_metadata', true ), true );
		$content_downloads_meta_key = sprintf( '_total_downloads_%s_%s', $file_metadata['files'][0]['datastream_id'], $file_metadata['files'][0]['pid'] );
		$total_content_downloads    = get_post_meta( $deposit_post_id, $content_downloads_meta_key, true );
		$content_views_meta_key     = sprintf( '_total_views_%s_%s', $file_metadata['files'][0]['datastream_id'], $file_metadata['files'][0]['pid'] );
		$total_content_views        = get_post_meta( $deposit_post_id, $content_views_meta_key, true );
		$views_meta_key             = sprintf( '_total_views_%s', $metadata['pid'] );
		$total_views                = get_post_meta( $deposit_post_id, $views_meta_key, true ) + 1; // Views counted at item page level.
	if ( bp_loggedin_user_id() != $post_data->post_author && ! humcore_is_bot_user_agent() ) {
		$post_meta_id = update_post_meta( $deposit_post_id, $views_meta_key, $total_views );
	}
		$download_url   = sprintf(
			'%s/deposits/download/%s/%s/%s/',
			$site_url,
			$file_metadata['files'][0]['pid'],
			$file_metadata['files'][0]['datastream_id'],
			$file_metadata['files'][0]['filename']
		);
		$view_url       = sprintf(
			'%s/deposits/view/%s/%s/%s/',
			$site_url,
			$file_metadata['files'][0]['pid'],
			$file_metadata['files'][0]['datastream_id'],
			$file_metadata['files'][0]['filename']
		);
		$metadata_url   = sprintf(
			'%s/deposits/download/%s/%s/%s/',
			$site_url,
			$metadata['pid'],
			'descMetadata',
			'xml'
		);
		$file_type_data = wp_check_filetype( $file_metadata['files'][0]['filename'], wp_get_mime_types() );
		$file_type_icon = sprintf(
			'<img class="deposit-icon" src="%s" alt="%s" />',
			plugins_url( 'assets/' . esc_attr( $file_type_data['ext'] ) . '-icon-48x48.png', __FILE__ ),
			esc_attr( $file_type_data['ext'] )
		);
	if ( ! empty( $file_metadata['files'][0]['thumb_filename'] ) ) {
			$thumb_url = sprintf(
				'<img class="deposit-thumb" src="%s/deposits/view/%s/%s/%s/" alt="%s" />',
				$site_url,
				$file_metadata['files'][0]['pid'],
				$file_metadata['files'][0]['thumb_datastream_id'],
				$file_metadata['files'][0]['thumb_filename'],
				'thumbnail'
			);
	} else {
			$thumb_url = '';
	}

	$share_command = humcore_social_sharing_shortcode( 'deposit', $metadata );

	if ( $switched ) {
			restore_current_blog();
	}
?>

<div class="bp-group-documents-meta">
<dl class='defList'>
<dt><?php _e( 'Title:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['title_unchanged']; // XSS OK. ?></span></dd>
<dt><?php _e( 'Item Type:', 'humcore_domain' ); ?></dt>
<dd><?php echo esc_html( $metadata['genre'] ); ?></dd>
<!-- //new stuff -->
<?php if ( 'Conference paper' == $metadata['genre'] || 'Conference proceeding' == $metadata['genre'] || 'Conference poster' == $metadata['genre'] ) : ?>
<dt><?php _e( 'Conf. Title:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['conference_title'] ) ) : ?>
<dd><span><?php echo $metadata['conference_title']; // XSS OK. ?></span></dd>
<?php endif; ?>
<dt><?php _e( 'Conf. Org.:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['conference_organization'] ) ) : ?>
<dd><span><?php echo $metadata['conference_organization']; // XSS OK. ?></span></dd>
<?php endif; ?>
<dt><?php _e( 'Conf. Loc.:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['conference_location'] ) ) : ?>
<dd><span><?php echo $metadata['conference_location']; // XSS OK. ?></span></dd>
<?php endif; ?>
<dt><?php _e( 'Conf. Date:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['conference_date'] ) ) : ?>
<dd><span><?php echo $metadata['conference_date']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php elseif ( 'Presentation' == $metadata['genre'] ) : ?>
<?php if ( ! empty( $metadata['meeting_title'] ) ) : ?>
<dt><?php _e( 'Meeting Title:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['meeting_title']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['meeting_organization'] ) ) : ?>
<dt><?php _e( 'Meeting Org.:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['meeting_organization']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['meeting_location'] ) ) : ?>
<dt><?php _e( 'Meeting Loc.:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['meeting_location']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $metadata['meeting_date'] ) ) : ?>
<dt><?php _e( 'Meeting Date:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $metadata['meeting_date']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php
elseif ( 'Dissertation' == $metadata['genre'] || 'Technical report' == $metadata['genre'] || 'Thesis' == $metadata['genre'] ||
				'White paper' == $metadata['genre'] ) :
					?>
<dt><?php _e( 'Institution:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['institution'] ) ) : ?>
<dd><span><?php echo $metadata['institution']; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php endif; ?>
<dt><?php _e( 'Abstract:', 'humcore_domain' ); // Google Scholar wants Abstract. ?></dt>
<dd><?php echo $metadata['abstract_unchanged']; ?></dd>
<?php if ( 'yes' === $post_metadata['committee_deposit'] ) : // Do not show unless this is a committee deposit. ?>
<dt><?php _e( 'Deposit Type:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo 'Committee'; ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $project_directors_list ) ) : ?>
<dt><?php _e( 'Project Director(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $project_directors_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $authors_list ) ) : ?>
<dt><?php _e( 'Author(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $authors_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $contribs_list ) ) : ?>
<dt><?php _e( 'Contributor(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $editors_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $editors_list ) ) : ?>
<dt><?php _e( 'Editor(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $editors_list ); // XSS OK. ?></dd>
<?php endif; ?>
<?php if ( ! empty( $translators_list ) ) : ?>
<dt><?php _e( 'Translator(s):', 'humcore_domain' ); ?></dt>
<dd><?php echo implode( ', ', $translators_list ); // XSS OK. ?></dd>
<?php endif; ?>
<dt><?php _e( 'Subject(s):', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $subjects ) ) : ?>
<dd><?php echo $subject_list; // XSS OK. ?></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'Group(s):', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $groups ) ) : ?>
<dd><?php echo $group_list; // XSS OK. ?></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'Tag(s):', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $keywords ) ) : ?>
<dd><?php echo $keyword_list; // XSS OK. ?></dd>
<?php else : ?>
<dd>&nbsp;</dd>
<?php endif; ?>
<dt><?php _e( 'File Type:', 'humcore_domain' ); ?></dt>
<dd><?php echo esc_html( $metadata['type_of_resource'] ); ?></dd>
<dt><?php _e( 'Language:', 'humcore_domain' ); ?></dt>
<dd><?php echo esc_html( $metadata['language'] ); ?></dd>
<dt><?php _e( 'Notes:', 'humcore_domain' ); ?></dt>
<?php if ( ! empty( $metadata['notes_unchanged'] ) ) : ?>
<dd><?php echo $metadata['notes_unchanged']; ?></dd>
<?php else : ?>
<dd>( None )</dd>
<?php endif; ?>
<?php
if ( 'book' == $post_metadata['publication-type'] ) :
		humcore_display_book_pub_metadata( $metadata );
elseif ( 'book-chapter' == $post_metadata['publication-type'] ) :
		humcore_display_book_chapter_pub_metadata( $metadata );
elseif ( 'book-review' == $post_metadata['publication-type'] ) :
		humcore_display_book_review_pub_metadata( $metadata );
elseif ( 'book-section' == $post_metadata['publication-type'] ) :
		humcore_display_book_section_pub_metadata( $metadata );
elseif ( 'journal-article' == $post_metadata['publication-type'] ) :
		humcore_display_journal_article_pub_metadata( $metadata );
elseif ( 'magazine-section' == $post_metadata['publication-type'] ) :
		humcore_display_magazine_section_pub_metadata( $metadata );
elseif ( 'monograph' == $post_metadata['publication-type'] ) :
		humcore_display_monograph_pub_metadata( $metadata );
elseif ( 'newspaper-article' == $post_metadata['publication-type'] ) :
		humcore_display_newspaper_article_pub_metadata( $metadata );
elseif ( 'online-publication' == $post_metadata['publication-type'] ) :
		humcore_display_online_publication_pub_metadata( $metadata );
elseif ( 'podcast' == $post_metadata['publication-type'] ) :
		humcore_display_podcast_pub_metadata( $metadata );
elseif ( 'proceedings-article' == $post_metadata['publication-type'] ) :
		humcore_display_proceedings_article_pub_metadata( $metadata );
elseif ( empty( $post_metadata['publication-type'] ) || 'none' == $post_metadata['publication-type'] ) :
	humcore_display_non_published_metadata( $metadata );
endif;
?>
<?php if ( ! empty( $post_metadata['type_of_license'] ) ) : ?>
<dt><?php _e( 'License:', 'humcore_domain' ); ?></dt>
<dd><?php echo humcore_linkify_license( $post_metadata['type_of_license'] ); ?></dd>
<?php endif; ?>
<dt><?php _e( 'Status:', 'humcore_domain' ); ?></dt>
<?php if ( 'draft' === $post_data->post_status ) : ?>
<dd><?php echo '<strong>Provisional</strong>'; ?>
<?php elseif ( 'pending' === $post_data->post_status ) : ?>
<dd><?php echo 'Pending Review'; ?>
<?php elseif ( 'publish' === $post_data->post_status ) : ?>
<dd><?php echo 'Published'; ?>
<?php elseif ( 'future' === $post_data->post_status ) : ?>
<dd><?php echo 'Scheduled'; ?>
<?php endif; ?>
<?php if ( humcore_user_can_edit_deposit( $wpmn_record_identifier ) ) : ?>
&nbsp; &nbsp; <a class="bp-deposits-edit-button" title="Edit this Deposit" href="<?php echo esc_url( $edit_url ); ?>"><?php _e( 'Edit this Deposit', 'humcore_domain' ); ?></a>
<?php endif; ?>
</dd>
<?php if ( ! empty( $update_time ) ) : ?>
<dt><?php _e( 'Last Updated:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $update_time . ' ago'; // XSS OK. ?></span></dd>
<?php endif; ?>
<?php if ( ! empty( $post_metadata['embargoed'] ) ) : ?>
<dt><?php _e( 'Embargoed?', 'humcore_domain' ); ?></dt>
<dd><?php echo $post_metadata['embargoed']; ?></dd>
<?php endif; ?>
<?php if ( ! empty( $post_metadata['embargo_end_date'] ) ) : ?>
<dt><?php _e( 'Embargo End Date:', 'humcore_domain' ); ?></dt>
<dd><?php echo $post_metadata['embargo_end_date']; ?></dd>
<?php endif; ?>
<?php if ( ! empty( $thumb_url ) ) : ?>
<dt><?php _e( 'Preview:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo $thumb_url;// XSS OK. ?></span></dd>
<?php endif; ?>
<dt><?php _e( 'File Name:', 'humcore_domain' ); ?></dt>
<dd><?php echo esc_html( $file_metadata['files'][0]['filename'] ); ?></dd>
<dt><?php _e( 'File Size:', 'humcore_domain' ); ?></dt>
<dd><?php echo number_format( $file_metadata['files'][0]['filesize'] ), ' bytes'; ?></dd>
<?php if ( in_array( $post_data->post_status, array( 'draft', 'publish' ) ) && ! empty( $share_command ) ) : ?>
<dt><?php _e( 'Share this:', 'humcore_domain' ); ?></dt>
<dd><span><?php echo do_shortcode( $share_command ); ?></span></dd>
<?php endif; ?>
</dl>
</div>
<br style='clear:both'>
<a class="bp-deposits-view button white" title="View" href="<?php echo esc_url( $item_url ); ?>"><?php _e( 'View your Deposit', 'humcore_domain' ); ?></a>
<?php

}

/**
 * Output the search sidebar facet list content.
 */
function humcore_search_sidebar_content() {

	$extended_query_string = humcore_get_search_request_querystring();
	$facet_display_counts  = humcore_get_facet_counts();
	$facet_display_titles  = humcore_get_facet_titles();
	$query_args            = wp_parse_args( $extended_query_string );
	?>
	<ul class="facet-set">
	<?php
	foreach ( $facet_display_counts as $facet_key => $facet_values ) {
		$facet_list_count = 0;
		if ( ! empty( $facet_display_titles[ $facet_key ] ) ) :
		?>
		<li class="facet-set-item"><h5><?php echo esc_html( trim( $facet_display_titles[ $facet_key ] ) ); ?></h5>
			<ul id="<?php echo sanitize_title_with_dashes( trim( $facet_key ) ); ?>-list" class="facet-list">
			<?php
			$sorted_counts = $facet_values['counts'];
			if ( 'pub_date_facet' === $facet_key ) {
				arsort( $sorted_counts );
			}
			foreach ( $sorted_counts as $facet_value_counts ) {
				if ( ! empty( $facet_value_counts[0] ) ) {
					$facet_list_item_selected = false;
					if ( ! empty( $query_args['facets'][ $facet_key ] ) ) {
						if ( in_array( $facet_value_counts[0], $query_args['facets'][ $facet_key ] ) ) {
							$facet_list_item_selected = true;
						}
					}
					$display_count    = sprintf(
						'<span class="count facet-list-item-count"%1$s>%2$s</span>',
						( $facet_list_item_selected ) ? ' style="display: none;"' : '',
						$facet_value_counts[1]
					);
					$display_selected = sprintf(
						'<span class="iconify facet-list-item-control%1$s"%2$s>%3$s</span>',
						( $facet_list_item_selected ) ? ' selected' : '',
						( $facet_list_item_selected ) ? '' : ' style="display: none !important;"',
						'X'
					);
          // if we are doing the subject_facet we need to format the Subject clean it up
          // (for display ONLY, the link URL stays the same)
          // "123:Art:Topic" -> "Art"
          $facet_display_string = $facet_value_counts[0];
          if($facet_key == 'subject_facet') {
            [$fast_id, $fast_subject, $fast_facet] = explode(":", $facet_display_string);
            $facet_display_string = $fast_subject;
          }  
					echo sprintf(
						'<li class="facet-list-item"%1$s><a class="facet-search-link" rel="nofollow" href="/deposits/?facets[%2$s][]=%3$s">%4$s %5$s%6$s</a></li>',
						( $facet_list_count < 2 || $facet_list_item_selected ) ? '' : ' style="display: none;"',
						trim( $facet_key ),
						urlencode( trim( $facet_value_counts[0] ) ),
						trim( $facet_display_string ),
						$display_count,
						$display_selected
					); // XSS OK.
					$facet_list_count++;
				}
			}
			if ( 2 < $facet_list_count ) {
				echo '<div class="facet-display-button"><span class="show-more button white right">' . esc_attr__( 'more>>', 'humcore_domain' ) . '</span></div>';
			}
			?>
			</ul>
		</li>
		<?php
		endif;
	}
	?>
	</ul>
	<?php

}

/**
 * Output the search sidebar facet list content.
 */
function humcore_directory_sidebar_content() {

	$extended_query_string = humcore_get_search_request_querystring();
	humcore_has_deposits( $extended_query_string );
	$facet_display_counts = humcore_get_facet_counts();
	$facet_display_titles = humcore_get_facet_titles();
	$query_args           = wp_parse_args( $extended_query_string );
	?>
	<ul class="facet-set">
	<?php
	foreach ( $facet_display_counts as $facet_key => $facet_values ) {
		if ( ! in_array( $facet_key, array( 'genre_facet', 'subject_facet', 'pub_date_facet' ) ) ) {
			continue; }
		$facet_list_count = 0;
		?>
		<li class="facet-set-item"><h5>Browse by <?php echo esc_html( trim( $facet_display_titles[ $facet_key ] ) ); ?></h5>
			<ul id="<?php echo sanitize_title_with_dashes( trim( $facet_key ) ); ?>-list" class="facet-list">
			<?php
			$sorted_counts = $facet_values['counts'];
			if ( 'pub_date_facet' === $facet_key ) {
				arsort( $sorted_counts );
			}
			foreach ( $sorted_counts as $facet_value_counts ) {
				if ( ! empty( $facet_value_counts[0] ) ) {
					$facet_list_item_selected = false;
					if ( ! empty( $query_args['facets'][ $facet_key ] ) ) {
						if ( in_array( $facet_value_counts[0], $query_args['facets'][ $facet_key ] ) ) {
							$facet_list_item_selected = true;
						}
					}
					$display_count = sprintf(
						'<span class="count facet-list-item-count"%1$s>%2$s</span>',
						( $facet_list_item_selected ) ? ' style="display: none;"' : '',
						$facet_value_counts[1]
					);
          // if we are doing the subject_facet we need to format the Subject clean it up
          // (for display ONLY, the link URL stays the same)
          // "123:Art:Topic" -> "Art"
          $facet_display_string = $facet_value_counts[0];
          if($facet_key == 'subject_facet') {
            [$fast_id, $fast_subject, $fast_facet] = explode(":", $facet_display_string);
            $facet_display_string = $fast_subject;
          }  
					echo sprintf(
						'<li class="facet-list-item"%1$s><a class="facet-search-link" rel="nofollow" href="/deposits/?facets[%2$s][]=%3$s">%4$s %5$s</a></li>',
						( $facet_list_count < 4 || $facet_list_item_selected ) ? '' : ' style="display: none;"',
						trim( $facet_key ),
						urlencode( trim( $facet_value_counts[0] ) ),
						trim( $facet_display_string ),
						$display_count
					); // XSS OK.
					$facet_list_count++;
				}
			}
			if ( 4 < $facet_list_count ) {
				echo '<div class="facet-display-button"><span class="show-more button white right">' . esc_attr__( 'more>>', 'humcore_domain' ) . '</span></div>';
			}
		?>
			</ul>
		</li>
	<?php } ?>
	</ul>
<?php

}
