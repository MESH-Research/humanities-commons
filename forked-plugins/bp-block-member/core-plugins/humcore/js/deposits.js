// Deposit form button control
jQuery(document).ready( function($) {

 	function maybe_show_published_fields(event) {
		var value = $(this).val();
		if ( value == 'published' ) {
		   	$('#lookup-doi-entry').show();
//			$('input[type=radio][name="deposit-publication-type"]:checked').prop('checked', false);
		} else if ( value == 'not-published' ) {
			$('#lookup-doi-entry').hide();
			$('input[type=radio][name="deposit-publication-type"][value="none"]').prop('checked', true);
		} else {
			$('#lookup-doi-entry').hide();
		}
	}

 	function maybe_show_extra_genre_fields(event) {
		var value = $(this).val();
		if ( value == 'Dissertation' || value == 'Technical report' || value == 'Thesis' || value == 'White paper' ) {
			$('#deposit-conference-entries').hide();
		   	$('#deposit-institution-entries').show();
			$('#deposit-meeting-entries').hide();
		} else if ( value == 'Conference paper' || value == 'Conference proceeding' || value == 'Conference poster' ) {
			$('#deposit-conference-entries').show();
			$('#deposit-institution-entries').hide();
			$('#deposit-meeting-entries').hide();
		} else if ( value == 'Presentation' ) {
			$('#deposit-conference-entries').hide();
			$('#deposit-institution-entries').hide();
			$('#deposit-meeting-entries').show();
		} else {
			$('#deposit-conference-entries').hide();
			$('#deposit-institution-entries').hide();
			$('#deposit-meeting-entries').hide();
		}
	}

 	function maybe_show_submitter_fields(event) {
		var value = $(this).val();
		if ( value == 'yes' ) {
			$('input[type=radio][name="deposit-author-role"][value="author"]').prop('checked', false);
			$('input[type=radio][name="deposit-author-role"][value="submitter"]').prop('checked', true);
			$('#deposit-author-display').hide();
			$('#deposit-other-authors-entry span.description').html('Add the authors and any other contributors to this work.');
			$('#deposit-insert-other-author-button').click();
		} else {
			$('#deposit-author-display').show();
			$('input[type=radio][name="deposit-author-role"][value="submitter"]').prop('checked', false);
	 	 	$('input[type=radio][name="deposit-on-behalf-flag"][value="no"]').prop('checked', true);
	 	 	$('input[type=radio][name="deposit-on-behalf-flag"][value="no"]').click();
			$('#deposit-other-authors-entry span.description').html('Add any other contributors in addition to yourself.');
		}
	}

 	function maybe_show_committee_fields(event) {
		var value = $(this).val();
		if ( value == 'yes' ) {
			$('input[type=radio][name="deposit-author-role"][value="submitter"]').prop('checked', true);
			$('#deposit-author-display').hide();
			$('#deposit-committee-entry').show();
			$('#deposit-other-authors-entry span.description').html('Add any other contributors in addition to the group.');
		} else {
			$('#deposit-author-display').show();
			$('#deposit-committee-entry').hide();
			$('input[type=radio][name="deposit-author-role"][value="submitter"]').prop('checked', false);
			$('#deposit-other-authors-entry span.description').html('Add any other contributors in addition to yourself.');
		}
	}

 	function maybe_show_publication_type_fields(event) {
		var value = $(this).val();
		if ( value == 'book' ) {
			$('#deposit-book-entries').show();
			$('#deposit-book-chapter-entries').hide();
			$('#deposit-book-review-entries').hide();
	 		$('#deposit-book-section-entries').hide();
			$('#deposit-journal-entries').hide();
			$('#deposit-magazine-section-entries').hide();
			$('#deposit-monograph-entries').hide();
			$('#deposit-newspaper-article-entries').hide();
			$('#deposit-online-publication-entries').hide();
			$('#deposit-podcast-entries').hide();
			$('#deposit-proceedings-entries').hide();
			$('#deposit-non-published-entries').hide();
			$('input[type=radio][name="deposit-published"][value="published"]').prop('checked', true);
			$('input[type=radio][name="deposit-published"][value="published"]').click();
		} else if ( value == 'book-chapter' ) {
			$('#deposit-book-entries').hide();
			$('#deposit-book-chapter-entries').show();
			$('#deposit-book-review-entries').hide();
			$('#deposit-book-section-entries').hide();
			$('#deposit-journal-entries').hide();
			$('#deposit-magazine-section-entries').hide();
			$('#deposit-monograph-entries').hide();
			$('#deposit-newspaper-article-entries').hide();
			$('#deposit-online-publication-entries').hide();
			$('#deposit-podcast-entries').hide();
			$('#deposit-proceedings-entries').hide();
			$('#deposit-non-published-entries').hide();
			$('input[type=radio][name="deposit-published"][value="published"]').prop('checked', true);
			$('input[type=radio][name="deposit-published"][value="published"]').click();
		} else if ( value == 'book-review' ) {
			$('#deposit-book-entries').hide();
			$('#deposit-book-chapter-entries').hide();
			$('#deposit-book-review-entries').show();
			$('#deposit-book-section-entries').hide();
			$('#deposit-journal-entries').hide();
			$('#deposit-magazine-section-entries').hide();
			$('#deposit-monograph-entries').hide();
			$('#deposit-newspaper-article-entries').hide();
			$('#deposit-online-publication-entries').hide();
			$('#deposit-podcast-entries').hide();
			$('#deposit-proceedings-entries').hide();
			$('#deposit-non-published-entries').hide();
			$('input[type=radio][name="deposit-published"][value="published"]').prop('checked', true);
			$('input[type=radio][name="deposit-published"][value="published"]').click();
		} else if ( value == 'book-section' ) {
			$('#deposit-book-entries').hide();
			$('#deposit-book-chapter-entries').hide();
			$('#deposit-book-review-entries').hide();
			$('#deposit-book-section-entries').show();
			$('#deposit-journal-entries').hide();
			$('#deposit-magazine-section-entries').hide();
			$('#deposit-monograph-entries').hide();
			$('#deposit-newspaper-article-entries').hide();
			$('#deposit-online-publication-entries').hide();
			$('#deposit-podcast-entries').hide();
			$('#deposit-proceedings-entries').hide();
			$('#deposit-non-published-entries').hide();
			$('input[type=radio][name="deposit-published"][value="published"]').prop('checked', true);
			$('input[type=radio][name="deposit-published"][value="published"]').click();
		} else if ( value == 'journal-article' ) {
			$('#deposit-book-entries').hide();
			$('#deposit-book-chapter-entries').hide();
			$('#deposit-book-review-entries').hide();
			$('#deposit-book-section-entries').hide();
			$('#deposit-journal-entries').show();
			$('#deposit-magazine-section-entries').hide();
			$('#deposit-monograph-entries').hide();
			$('#deposit-newspaper-article-entries').hide();
			$('#deposit-online-publication-entries').hide();
			$('#deposit-podcast-entries').hide();
			$('#deposit-proceedings-entries').hide();
			$('#deposit-non-published-entries').hide();
			$('input[type=radio][name="deposit-published"][value="published"]').prop('checked', true);
			$('input[type=radio][name="deposit-published"][value="published"]').click();
		} else if ( value == 'magazine-section' ) {
			$('#deposit-book-entries').hide();
			$('#deposit-book-chapter-entries').hide();
			$('#deposit-book-review-entries').hide();
			$('#deposit-book-section-entries').hide();
			$('#deposit-journal-entries').hide();
			$('#deposit-magazine-section-entries').show();
			$('#deposit-monograph-entries').hide();
			$('#deposit-newspaper-article-entries').hide();
			$('#deposit-online-publication-entries').hide();
			$('#deposit-podcast-entries').hide();
			$('#deposit-proceedings-entries').hide();
			$('#deposit-non-published-entries').hide();
			$('input[type=radio][name="deposit-published"][value="published"]').prop('checked', true);
			$('input[type=radio][name="deposit-published"][value="published"]').click();
		} else if ( value == 'monograph' ) {
		   	$('#deposit-book-entries').hide();
			$('#deposit-book-chapter-entries').hide();
		   	$('#deposit-book-review-entries').hide();
		   	$('#deposit-book-section-entries').hide();
		   	$('#deposit-journal-entries').hide();
			$('#deposit-magazine-section-entries').hide();
			$('#deposit-monograph-entries').show();
			$('#deposit-newspaper-article-entries').hide();
			$('#deposit-online-publication-entries').hide();
			$('#deposit-podcast-entries').hide();
			$('#deposit-proceedings-entries').hide();
			$('#deposit-non-published-entries').hide();
			$('input[type=radio][name="deposit-published"][value="published"]').prop('checked', true);
			$('input[type=radio][name="deposit-published"][value="published"]').click();
		} else if ( value == 'newspaper-article' ) {
		   	$('#deposit-book-entries').hide();
			$('#deposit-book-chapter-entries').hide();
		   	$('#deposit-book-review-entries').hide();
		   	$('#deposit-book-section-entries').hide();
		   	$('#deposit-journal-entries').hide();
			$('#deposit-magazine-section-entries').hide();
			$('#deposit-monograph-entries').hide();
			$('#deposit-newspaper-article-entries').show();
			$('#deposit-online-publication-entries').hide();
			$('#deposit-podcast-entries').hide();
			$('#deposit-proceedings-entries').hide();
			$('#deposit-non-published-entries').hide();
			$('input[type=radio][name="deposit-published"][value="published"]').prop('checked', true);
			$('input[type=radio][name="deposit-published"][value="published"]').click();
		} else if ( value == 'online-publication' ) {
		   	$('#deposit-book-entries').hide();
			$('#deposit-book-chapter-entries').hide();
		   	$('#deposit-book-review-entries').hide();
		   	$('#deposit-book-section-entries').hide();
		   	$('#deposit-journal-entries').hide();
			$('#deposit-magazine-section-entries').hide();
			$('#deposit-monograph-entries').hide();
			$('#deposit-newspaper-article-entries').hide();
			$('#deposit-online-publication-entries').show();
			$('#deposit-podcast-entries').hide();
			$('#deposit-proceedings-entries').hide();
			$('#deposit-non-published-entries').hide();
			$('input[type=radio][name="deposit-published"][value="published"]').prop('checked', true);
			$('input[type=radio][name="deposit-published"][value="published"]').click();
		} else if ( value == 'podcast' ) {
		   	$('#deposit-book-entries').hide();
			$('#deposit-book-chapter-entries').hide();
		   	$('#deposit-book-review-entries').hide();
		   	$('#deposit-book-section-entries').hide();
		   	$('#deposit-journal-entries').hide();
			$('#deposit-magazine-section-entries').hide();
			$('#deposit-monograph-entries').hide();
			$('#deposit-newspaper-article-entries').hide();
			$('#deposit-online-publication-entries').hide();
			$('#deposit-podcast-entries').show();
			$('#deposit-proceedings-entries').hide();
			$('#deposit-non-published-entries').hide();
			$('input[type=radio][name="deposit-published"][value="published"]').prop('checked', true);
			$('input[type=radio][name="deposit-published"][value="published"]').click();
		} else if ( value == 'proceedings-article' ) {
		   	$('#deposit-book-entries').hide();
			$('#deposit-book-chapter-entries').hide();
		   	$('#deposit-book-review-entries').hide();
		   	$('#deposit-book-section-entries').hide();
		   	$('#deposit-journal-entries').hide();
			$('#deposit-magazine-section-entries').hide();
			$('#deposit-monograph-entries').hide();
			$('#deposit-newspaper-article-entries').hide();
			$('#deposit-online-publication-entries').hide();
			$('#deposit-podcast-entries').hide();
			$('#deposit-proceedings-entries').show();
			$('#deposit-non-published-entries').hide();
			$('input[type=radio][name="deposit-published"][value="published"]').prop('checked', true);
			$('input[type=radio][name="deposit-published"][value="published"]').click();
		} else if ( value == 'none' ) {
		   	$('#deposit-book-entries').hide();
			$('#deposit-book-chapter-entries').hide();
		   	$('#deposit-book-review-entries').hide();
		   	$('#deposit-book-section-entries').hide();
			$('#deposit-journal-entries').hide();
			$('#deposit-magazine-section-entries').hide();
			$('#deposit-monograph-entries').hide();
			$('#deposit-newspaper-article-entries').hide();
			$('#deposit-online-publication-entries').hide();
			$('#deposit-podcast-entries').hide();
			$('#deposit-proceedings-entries').hide();
			$('#deposit-non-published-entries').show();
			$('input[type=radio][name="deposit-published"][value="not-published"]').prop('checked', true);
			$('input[type=radio][name="deposit-published"][value="not-published"]').click();
		}
	}

 	function maybe_show_embargoed_fields(event) {
		var value = $(this).val();
		if ( value == 'yes' ) {
		   	$('#deposit-embargoed-entries').show();
		} else if ( value == 'no' ) {
			$('#deposit-embargoed-entries').hide();
		} else {
			$('#deposit-embargoed-entries').hide();
		}
	}

	// Setup a character counter for the abstract and notes fields.
	function update_char_counter() {
		var total_chars = $(this).val().length;
		$(this).siblings('.character-count').text(total_chars + " chars");
	}
	$('#deposit-abstract-unchanged').keyup(update_char_counter);
	$('#deposit-abstract-unchanged').keydown(update_char_counter);
	$('#deposit-notes-unchanged').keyup(update_char_counter);
	$('#deposit-notes-unchanged').keydown(update_char_counter);

	// Add other authors as needed.
	$('#deposit-insert-other-author-button').on('click', function(e) {
		e.preventDefault();
		var row_count = $('#deposit-other-authors-entry-table>tbody tr').length - 2;
		$('#deposit-other-authors-entry-table>tbody').append('		<tr><td class="borderTop"><input type="text" name="deposit-other-authors-first-name[' + row_count + ']" class="text" value="" /></td>' +
				'<td class="borderTop"><input type="text" name="deposit-other-authors-last-name[' + row_count + ']" class="text deposit-other-authors-last-name" value="" /></td>' +
				'<td class="borderTop" style="vertical-align: top;">' +
				'<span style="white-space: nowrap;"><input type="radio" name="deposit-other-authors-role[' + row_count + ']" class="styled" style="margin-top: 12px;" value="author">Author &nbsp;</span>' +
				'<span style="white-space: nowrap;"><input type="radio" name="deposit-other-authors-role[' + row_count + ']" class="styled" style="margin-top: 12px;" value="contributor">Contributor &nbsp;</span>' +
				'<span style="white-space: nowrap;"><input type="radio" name="deposit-other-authors-role[' + row_count + ']" class="styled" style="margin-top: 12px;" value="editor">Editor &nbsp;</span>' +
				'<span style="white-space: nowrap;"><input type="radio" name="deposit-other-authors-role[' + row_count + ']" class="styled" style="margin-top: 12px;" value="translator">Translator &nbsp;</span>' +
				'</td>' +
				'<td class="borderTop"></td></tr>');
	});

	//Hide the conditional fields by default.
	$('#lookup-doi-entry').hide();
	$('#deposit-conference-entries').hide();
	$('#deposit-institution-entries').hide();
	$('#deposit-meeting-entries').hide();
	$('#deposit-committee-entry').hide();
	$('#deposit-book-entries').hide();
	$('#deposit-book-chapter-entries').hide();
	$('#deposit-book-review-entries').hide();
	$('#deposit-book-section-entries').hide();
	$('#deposit-journal-entries').hide();
	$('#deposit-magazine-section-entries').hide();
	$('#deposit-monograph-entries').hide();
	$('#deposit-newspaper-article-entries').hide();
	$('#deposit-online-publication-entries').hide();
	$('#deposit-podcast-entries').hide();
	$('#deposit-proceedings-entries').hide();
	$('#deposit-embargoed-entries').hide();

	// Show any selected conditional fields.
	$('select[name=deposit-genre]').on('change', maybe_show_extra_genre_fields);
	$('select[name=deposit-genre]').on('genreload', maybe_show_extra_genre_fields);
	$('input[type=radio][name=deposit-for-others-flag]').on('click', maybe_show_submitter_fields);
	$('input[type=radio][name=deposit-for-others-flag]').on('submitterload', maybe_show_submitter_fields);
	$('input[type=radio][name=deposit-on-behalf-flag]').on('click', maybe_show_committee_fields);
	$('input[type=radio][name=deposit-on-behalf-flag]').on('committeeload', maybe_show_committee_fields);
	$('input[type=radio][name=deposit-publication-type]').on('click', maybe_show_publication_type_fields);
	$('input[type=radio][name=deposit-publication-type]').on('pubtypeload', maybe_show_publication_type_fields);
	$('input[type=radio][name=deposit-published]').on('click', maybe_show_published_fields);
	$('input[type=radio][name=deposit-published]').on('pubload', maybe_show_published_fields);
	$('input[type=radio][name=deposit-embargoed-flag]').on('click', maybe_show_embargoed_fields);
	$('input[type=radio][name=deposit-embargoed-flag]').on('embargoedbload', maybe_show_embargoed_fields);

	// Setup triggers for page load from server.
	$('select[name=deposit-genre]').trigger('genreload');
	$('input[type=radio][name=deposit-for-others-flag]:checked').trigger('submitterload');
	$('input[type=radio][name=deposit-on-behalf-flag]:checked').trigger('committeeload');
	$('input[type=radio][name=deposit-publication-type]:checked').trigger('pubtypeload');
	$('input[type=radio][name=deposit-published]:checked').trigger('pubload');
	$('input[type=radio][name=deposit-embargoed-flag]:checked').trigger('embargoedload');

	// Setup warning and error dialogs.
	$( "#deposit-warning-dialog" ).dialog({
		autoOpen: false,
		buttons: [{
			text: 'Edit',
			class: 'button-primary',
			click: function() {
				$(this).dialog('close');
				}
			},
			{
			text: 'Confirm',
			class: 'button-primary',
			click: function() {
				$(this).dialog('close');
				$('#deposit-submit').prop('disabled', true);
				$('#deposit-submit').attr('value', 'Please wait...');
				$('#deposit-form')[0].submit();
				}
			}],
		closeOnEscape: false,
		dialogClass: 'no-close',
		modal: true,
		title: 'Please review your entries.',
		width: 688
		});
	$( "#deposit-error-dialog" ).dialog({
		autoOpen: false,
		buttons: [{
			text: 'Edit',
			class: 'button-primary',
			click: function() {
				$(this).dialog('close');
				return false;
				}
			}],
		closeOnEscape: false,
		dialogClass: 'no-close',
		modal: true,
		title: 'This item is not complete.',
		width: 400
		});

	// Check required and suggested entries before submitting form.
 	$('#deposit-form').on('submit', function(e) {

		var selected_file = $.trim($('input[type=hidden][name=selected_file_name]').val());
		var title = $.trim($('#deposit-title-unchanged').val());
		var item_type = $('#deposit-genre').val();
		var description = $.trim($('#deposit-abstract-unchanged').val());
		var description_length = $('#deposit-abstract-unchanged').val().length;
		var deposit_on_behalf_of = $('input[type=radio][name=deposit-on-behalf-flag]:checked').val();
		var deposit_for_others = $('input[type=radio][name=deposit-for-others-flag]:checked').val();
		var committee = $('#deposit-committee').val();
		var other_first_name = $("input[name^='deposit-other-authors-first-name']").val();
		var other_last_name = $("input[name^='deposit-other-authors-last-name']").val();
		var groups = $('select[name="deposit-group[]"]').val();
		var subjects = $('select[name="deposit-subject[]"]').val();
		var notes_length = $('#deposit-notes-unchanged').val().length;
		var embargoed = $('input[type=radio][name=deposit-embargoed-flag]:checked').val();
		var embargo_length = $('#deposit-embargo-length').val();

		var error_message = '<ul>';
		var warning_message = 'Please review the information you provided, as you left some important fields blank.<p /><ul>';

		if ( selected_file === '' ) {
			error_message += '<li>Please select a file.</li>';
			$('#pickfile').addClass('deposit-input-highlight');
		}
		if ( title === '' ) {
			error_message += '<li>Please add a title.</li>';
			$('#deposit-title-unchanged').addClass('deposit-input-highlight');
		}
		if ( item_type === '' ) {
			error_message += '<li>Please add an item type.</li>';
			$('#deposit-genre-entry span.select2.select2-container span.selection span.select2-selection').addClass('deposit-input-highlight');
		}
		if ( description === '' ) {
			error_message += '<li>Please add a description.</li>';
			$('#deposit-abstract-unchanged').addClass('deposit-input-highlight');
		}
		if ( description_length > 2000 ) {
			error_message += '<li>Please limit description to 2000 characters.</li>';
			$('#deposit-abstract-unchanged').addClass('deposit-input-highlight');
		}
		if ( committee === '' && deposit_on_behalf_of === 'yes' ) {
			error_message += '<li>Please add the group you are depositing on behalf of.</li>';
			$('#deposit-committee-entry span.select2.select2-container span.selection span.select2-selection').addClass('deposit-input-highlight');
		}
		if ( notes_length > 500 ) {
			error_message += '<li>Please limit notes to 500 characters.</li>';
			$('#deposit-notes-unchanged').addClass('deposit-input-highlight');
		}
		if ( embargo_length === '' && embargoed === 'yes' ) {
			error_message += '<li>Please add an embargo length.</li>';
			$('#deposit-embargo-length-entry span.select2.select2-container span.selection span.select2-selection').addClass('deposit-input-highlight');
		}
		if ( groups === null ) {
			warning_message += '<li>We noticed you haven’t shared your deposit with any groups. Group members receive a notification about the work you’ve uploaded to <em>CORE</em>.</li>';
			$('#deposit-group-entry span.select2.select2-container span.selection span.select2-selection').addClass('deposit-input-highlight');
		}
		//console.log(typeof other_first_name === 'undefined' || other_first_name === '');
		//console.log(deposit_for_others);
		//console.log(other_first_name);
		//console.log(other_last_name);
		//console.log(deposit_for_others === 'yes' && (typeof other_first_name === 'undefined' || other_first_name === ''));
		if ( deposit_for_others === 'yes' && ( (typeof other_first_name === 'undefined' || other_first_name === '') &&
							(typeof other_first_name === 'undefined' || other_first_name === '') ) ) {
			error_message += '<li>Please add at least one author to deposit on behalf of others.</li>';
			$('#deposit-other-authors-first-name').addClass('deposit-input-highlight');
			$('#deposit-other-authors-last-name').addClass('deposit-input-highlight');
		}
		if ( subjects === null ) {
			warning_message += '<li>We noticed you did not select a subject for your item, which could make it harder for others to find.</li>';
			$('#deposit-subject-entry span.select2.select2-container span.selection span.select2-selection').addClass('deposit-input-highlight');
		}

		// Show a dialog if needed, otherwise submit the form.
		if ( title === '' || item_type === '' || description === '' || selected_file === '' || description_length > 2000 || notes_length > 500 ||
			( committee === '' && deposit_on_behalf_of === 'yes' ) ||
			( deposit_for_others === 'yes' && ( (typeof other_first_name === 'undefined' || other_first_name === '') &&
								(typeof other_first_name === 'undefined' || other_first_name === '') ) ) ) {
			$('#deposit-error-dialog').html(error_message).dialog('open');
			return false;
		} else if ( ( groups === null ) || subjects === null ) {
			warning_message += '</ul>Want to fix this? Press <b>Edit</b> to make changes. To upload your item as is, press <b>Confirm</b>.';
			$('#deposit-warning-dialog').html(warning_message).dialog('open');
			return false;
		} else {
			$('#deposit-submit').attr('value', 'Please wait...');
			$('#deposit-submit').prop('disabled', true);
			return true;
		}
	});

} );

// Custom plupload logic
jQuery(document).ready( function($) {

var uploader = new plupload.Uploader( {
	runtimes : 'html5,flash,silverlight,html4',
	multi_selection : false, // only one file allowed for this phase
	unique_names : true, //handle unique names in php
	chunk_size: '2mb',
	max_retries: 3,

	browse_button : 'pickfile', // you can pass in id...
	container: document.getElementById('container'), // ... or DOM Element itself
	url : MyAjax.ajaxurl, //MyAjax set in php
	flash_swf_url : MyAjax.flash_swf_url,
	silverlight_xap_url : MyAjax.silverlight_xap_url,

    // additional post data to send to our ajax hook - nonce and action name
    multipart_params : {
    	_ajax_nonce : MyAjax._ajax_nonce,
		action : 'humcore_upload_handler'
	},
	
	filters : {
		max_file_size : '100mb',
		mime_types: [
			{ title : 'Image files', extensions : 'gif,jpeg,jpg,png,psd,tiff' },
//			{ title : 'Raw Image files', extensions : 'cr2,crw,dng,nef' },
			{ title : 'Web files', extensions : 'htm,html' }, //css,js maybe?
//			{ title : 'Archive files', extensions : 'gz,rar,tar,zip' },
			{ title : 'Document files', extensions : 'csv,doc,docx,odp,ods,odt,pdf,ppt,pptx,pps,rdf,rtf,sxc,sxi,sxw,txt,tsv,wpd,xls,xlsx,xml' },
			{ title : 'Audio files', extensions : 'mp3,ogg,wav' },
			{ title : 'Video files', extensions : 'f4v,flv,mov,mp4' }
 		]
	},
	init: {
		PostInit: function() {
			if ( "" != $('#selected_file_name').val() ) {
        			$('#filelist').html(
                			'<div><br />' + $('#selected_file_name').val() +
					' (' + plupload.formatSize( $('#selected_file_size').val() ) + ')</div>');
        			$('#console').html(
                			'The file has been uploaded. Use the fields below to enter information about the file and press Deposit.');
			} else {
				$('#filelist').html('');
			}
//			document.getElementById( 'uploadfile' ).onclick = function() {
//				uploader.start();
//				return false;
//			};
		},
		FilesAdded: function( up, files ) {
			// only one file allowed for this phase
			if ( up.files.length > 1 ) {
				up.splice( 0, 1 );
            }
			plupload.each( files, function( file ) {
				document.getElementById( 'filelist' ).innerHTML = '<div id="' + file.id + '"><br />' + file.name + ' (' + plupload.formatSize( file.size ) + ')</div>';
			});
//			document.getElementById( 'uploadfile' ).focus();
			document.getElementById( 'progressbar' ).style.display = 'block';
			up.start();
		},
		UploadFile: function( up, file ) {
			document.getElementById( 'console' ).innerHTML = 'Uploading file ... ';
		},
		UploadProgress: function( up, file ) {
			if ( file.percent <= 100 && file.percent >= 1 ) {
				document.getElementById( 'indicator' ).style.width = file.percent + '%';
			}  
		},
		FileUploaded: function( up, file, info ) {
			var response = JSON.parse( info.response );
			if ( "0" == response.OK ) {
				document.getElementById( 'console' ).appendChild( document.createTextNode( "\nError #" + response.error.code + ": " + response.error.message ) );
				document.getElementById( 'indicator' ).style.width = '0%';
			} else {
//				document.getElementById( 'lookup-doi' ).focus();
				$('input[type=radio][name="deposit-published"]:checked').focus();
				document.getElementById( 'console' ).innerHTML = 'The file has been uploaded. Use the fields below to enter information about the file and press Deposit.';
				document.getElementById( 'selected_file_size' ).setAttribute( 'value', file.size );
				document.getElementById( 'selected_temp_name' ).setAttribute( 'value', file.target_name );
				document.getElementById( 'selected_file_name' ).setAttribute( 'value', file.name );
				document.getElementById( 'selected_file_type' ).setAttribute( 'value', file.type );

			}
		},
		Error: function( up, err ) {
			if ( err.code == "-600" )	{
				document.getElementById( 'console' ).appendChild( document.createTextNode( '\nSorry, the size of that file exceeds our 100MB limit!' ) );

			} else if ( err.code == "-601" )	{
				document.getElementById( 'console' ).appendChild( document.createTextNode( '\nSorry, that type of file cannot be selected.' ) );

			} else {
				document.getElementById( 'console' ).appendChild( document.createTextNode( '\nError #' + err.code + ': ' + err.message ) );
			}
			return false;
		}
	}

} );

uploader.init();
} );

