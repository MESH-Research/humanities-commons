/**
 * File: js/select2.js
 * 
 * Description: All Select2 code including for FAST Subject selection
 * 
 */
//
// FAST subject functions
/**
 * description: this controls the format of the actual FAST subject drop down list
 *
 * @param subject
 */
 function formatFASTSubjectResult(subject){
	var formatted_subject = "";
    if (subject.loading){
        return "Loading FAST data ...";
    }
    // what the choosen item will look like in the drop down list
	formatted_subject = $(
		`<span>${subject["suggestall"][0]}</span> &nbsp;` +
		`<span><b>${subject["auth"]}</b></span> &nbsp;` +
		`<span>(<em>${getFASTTypeFromTag(subject["tag"])}</em>)</span>`
	);
	return formatted_subject;
}

/**
 * Based on:
 *  formatFASTSubjectResult
 * but modified to be used on admin/dashboard screens
 *
 * @param subject
 */
 function formatFASTSubjectResultAdmin(subject){
	var formatted_subject = "";
    if (subject.loading){
        return "Loading FAST data ...";
    }
    // what the choosen item will look like in the drop down list
	formatted_subject = `${subject["suggestall"][0]} ${subject["auth"]} (${getFASTTypeFromTag(subject["tag"])}`;
	return formatted_subject;
}

/**
 *
 * description: Controls what the FAST subject select field looks like after
 *              the user has made a choice (may be "" (blank) if you want the select field to be empty)
 *              It also can be used to do any side affects such as writing to other parts of the page
 * @param subject
 * @returns {string}
 */
function formatFASTSubjectSelection(subject) {
	var formatted_subject = "";
    if (subject.auth) {
      node = `<span><b>${subject["auth"]}</b></span> &nbsp;` +
      `<span>(<em>${getFASTTypeFromTag(subject["tag"])}</em>)</span>`;
      formatted_subject = $(node);
    } else {
      //
      // if there is no subject.auth create the display string from subject.text
      const [id, auth, facet] = subject.text.split(":");
      node = `<span><b>${auth}</b></span> &nbsp;` +
      `<span>(<em>${facet}</em>)</span>`; 
      formatted_subject = $(node);
	}
	return formatted_subject;
}

/**
 *
 * Based on:
 *  formatFASTSubjectSelection
 * but modified to be used on admin/dashboard screens
 * 
 * @param subject
 * @returns {string}
 */
 function formatFASTSubjectSelectionAdmin(subject) {
	var formatted_subject = "";
    if (subject.auth) {
      formatted_subject = `${subject["auth"]} (${getFASTTypeFromTag(subject["tag"])})`;
    } else {
      //
      // if there is no subject.auth create the display string from subject.text
      const [id, auth, facet] = subject.text.split(":");
      formatted_subject = `${auth} (${facet})`;
	  }
	return formatted_subject;
}

/**
 * Returns FAST subject facet name (e.g. Topic, Meeting, etc.) as a string
 * based on the FAST facet tag (numeric code)
 *
 * @param tag
 * @returns {string}
 */
 function getFASTTypeFromTag(tag) {
    switch (tag) {
        case 100:
            return "Personal Name";
            break;
        case 110:
            return "Corporate Name";
            break;
        case 111:
            return "Meeting";
            break;
        case 130:
            return "Uniform Title";
            break;
        case 147:
            return "Event";
            break;
        case 148:
            return "Period";
            break;
        case 150:
            return "Topic";
            break;
        case 151:
            return "Geographic";
            break;
        case 155:
            return "Form/Genre";
            break;
        default:
            return "unknown";
    }
}

// Deposit select 2 controls
jQuery(document).ready( function($) {

	$(".js-basic-multiple").select2({
		maximumSelectionLength: 5,
		width: "75%"
	});
	$( '.js-basic-multiple-keywords' ).select2( {
		maximumSelectionLength: 10,
		width: "75%",
		tags: true,
		tokenSeparators: [','],
		minimumInputLength: 1,
		ajax: {
			url: '/wp-json/humcore-deposits-keyword/v1/terms',
			cache: true
		},
		templateResult: function( result ) {
			// hide result which exactly matches user input to avoid confusion with differently-cased matches
			if ( $('.select2-search__field').val() == result.text ) {
				result.text = null;
			}

			return result.text;
		}
	} );
	$( '.js-basic-multiple-subjects' ).select2( {
		maximumSelectionLength: 5,
		width: "75%",
		tokenSeparators: [','],
		minimumInputLength: 1,
		ajax: {
			url: '/wp-json/humcore-deposits-subject/v1/terms',
			cache: true
		},
		templateResult: function( result ) {
			// hide result which exactly matches user input to avoid confusion with differently-cased matches
			if ( $('.select2-search__field').val() == result.text ) {
				result.text = null;
			}

			return result.text;
		}
	} );

	// set selected to false for all options.
	// this allows users to click a term in the dropdown even if it is already selected.
	// (instead of that click resulting in the unselecting of the term)
	$( '.js-basic-multiple-keywords' ).on( 'select2:open', function( e ) {
		var observer = new MutationObserver( function() {
			$( '.select2-results__options [aria-selected]' ).attr( 'aria-selected', false );
		} );

		observer.observe( $( '.select2-results__options' )[0], { childList: true } );
	} );

	// ensure user-input terms conform to existing terms regardless of case
	// e.g. if user enters "music" and "Music" exists, select "Music"
	$( '.js-basic-multiple-keywords' ).on( 'select2:selecting', function( e ) {
		var input_term = e.params.args.data.id;
		var existing_terms = $( '.select2-results__option' ).not( '.select2-results__option--highlighted' );
		var Select2 = $( '.js-basic-multiple-keywords' ).data( 'select2' );

		$.each( existing_terms, function( i, term_el ) {
			var term = $( term_el ).text();

			// if this term already exists with a different case, select that instead
			if ( input_term.toUpperCase() == term.toUpperCase() ) {
				// overwrite the user-input term with the canonical one
				e.params.args.data.id = term;
				e.params.args.data.text = term;

				// trigger another select event with the updated term
				Select2.constructor.__super__.trigger.call( Select2, 'select', e.params.args );

				e.preventDefault();
			}
		} );
	} );

	// set selected to false for all options.
	// this allows users to click a term in the dropdown even if it is already selected.
	// (instead of that click resulting in the unselecting of the term)
	$( '.js-basic-multiple-subjects' ).on( 'select2:open', function( e ) {
		var observer = new MutationObserver( function() {
			$( '.select2-results__options [aria-selected]' ).attr( 'aria-selected', false );
		} );

		observer.observe( $( '.select2-results__options' )[0], { childList: true } );
	} );

	// ensure user-input terms conform to existing terms regardless of case
	// e.g. if user enters "music" and "Music" exists, select "Music"
	$( '.js-basic-multiple-subjects' ).on( 'select2:selecting', function( e ) {
		var input_term = e.params.args.data.id;
		var existing_terms = $( '.select2-results__option' ).not( '.select2-results__option--highlighted' );
		var Select2 = $( '.js-basic-multiple-subjects' ).data( 'select2' );

		$.each( existing_terms, function( i, term_el ) {
			var term = $( term_el ).text();

			// if this term already exists with a different case, select that instead
			if ( input_term.toUpperCase() == term.toUpperCase() ) {
				// overwrite the user-input term with the canonical one
				e.params.args.data.id = term;
				e.params.args.data.text = term;

				// trigger another select event with the updated term
				Select2.constructor.__super__.trigger.call( Select2, 'select', e.params.args );

				e.preventDefault();
			}
		} );
	} );

	$(".js-basic-single-required").select2({
		minimumResultsForSearch: "36",
		width: "40%"
	});
	$(".js-basic-single-optional").select2({
		allowClear: "true",
		minimumResultsForSearch: "36",
		width: "40%"
	});
	// FAST subjects
	var facet = "suggestall";
	var queryIndices = ",idroot,auth,tag,type,raw,breaker,indicator";
  var subjectDB = "autoSubject";
	var numRows = 20;
  /**
   * NOTE:
   * Each of the FAST select 2 drop downs need to be
   * wrapped inside this code. This allows the backspace to delete
   * an entire subject with one keypress.
   *  
   * The default behavior for Select2 is to turn the subject into a 
   * string ('123:History:Topic') and delete it one charectar at a time.
  */
  $.fn.select2.amd.require(['select2/selection/search'], function (Search) {
    var oldRemoveChoice = Search.prototype.searchRemoveChoice;
    //
    Search.prototype.searchRemoveChoice = function () {
        oldRemoveChoice.apply(this, arguments);
        this.$search.val('');
    };
    //+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    //
    $('.js-basic-multiple-fast-subjects').select2({
      // multiple: is set from the HTML select field option
      theme: $(this).data('theme') ? $(this).data('theme') : 'default',
      width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
      placeholder: $(this).data('placeholder') ? $(this).data('placeholder') : "Please make a selection",
      allowClear: $(this).data('allow-clear') ? Boolean($(this).data('allow-clear')) : true,
      closeOnSelect: $(this).data('close-on-select') ? Boolean($(this).data('close-on-select')) : true,
      dir: $(this).data('dir') ? $(this).data('dir') : 'ltr',
      disabled: $(this).data('disabled') ? Boolean($(this).data('disabled')) : false,
      debug: $(this).data('debug') ? Boolean($(this).data('debug')) : false,
      delay: $(this).data('delay') ? Number($(this).data('delay')) : 250,
      minimumInputLength: $(this).data('minimum-input-length') ? Number($(this).data('minimum-input-length')) : 3,
      maximumSelectionLength: $(this).data('maximum-selection-length') ? Number($(this).data('maximum-selection-length')) : 10,
      ajax: {
        url: "https://fast.oclc.org/searchfast/fastsuggest",
        // we need to use "padded" json (jsonp)
        // using regular json gives a CORS error
        dataType: 'jsonp',
        type: 'GET',
        // query parameters
        data: function (params) {
          return {
            query: params.term, // search term
            queryIndex: facet,
            queryReturn: facet + queryIndices,
            rows: numRows,
            suggest: subjectDB,
          };
        },
        /**
         * description: format FAST data into Select2 format
         *
         * @param data data returned by FAST API call
         * @returns {results: array usable by Select2}}
         */
        processResults: function (data) {
          // the docs array from FAST the actual data we need
          var arraySelect2 = data.response.docs;
    
          /**
           * description: modify the raw data from FAST into a Select2 format.
           * all we need to do is to add a field called ["id"] to the array
           *
           * @param value
           * @param index
           */
          function convertFastToSelect2(value, index) {
            var data = value;
            // Select2 requires a field called ["id"]
            // ["id"] needs to have all the data we want to save for later use
            // we want just the numeric form (without leading zeros for the fastid)
            // so we removr "fst" and any leading zeros
            let idroot = value["idroot"];
            let re = /(fst0*)/;
            let fastid = idroot.replace(re, '');
            data.id = fastid + ":" + value["auth"] + ":" + getFASTTypeFromTag(value["tag"]);
          }
          arraySelect2.forEach(convertFastToSelect2);
          return {
            results: arraySelect2
          };
        },
      },
      templateResult: formatFASTSubjectResult,
      templateSelection: formatFASTSubjectSelection,
    });
    //
    // this is based on above
    // .js-basic-multiple-fast-subjects-admin
    // but modified to be used in the admin/dashboard pages
    //
    $('.js-basic-multiple-fast-subjects-admin').select2({
      // multiple: is set from the HTML select field option
      //theme: $(this).data('theme') ? $(this).data('theme') : 'default',
      width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
      placeholder: $(this).data('placeholder') ? $(this).data('placeholder') : "Please make a selection",
      allowClear: $(this).data('allow-clear') ? Boolean($(this).data('allow-clear')) : true,
      closeOnSelect: $(this).data('close-on-select') ? Boolean($(this).data('close-on-select')) : true,
      dir: $(this).data('dir') ? $(this).data('dir') : 'ltr',
      disabled: $(this).data('disabled') ? Boolean($(this).data('disabled')) : false,
      debug: $(this).data('debug') ? Boolean($(this).data('debug')) : false,
      delay: $(this).data('delay') ? Number($(this).data('delay')) : 250,
      minimumInputLength: $(this).data('minimum-input-length') ? Number($(this).data('minimum-input-length')) : 3,
      maximumSelectionLength: $(this).data('maximum-selection-length') ? Number($(this).data('maximum-selection-length')) : 10,
      ajax: {
        url: "https://fast.oclc.org/searchfast/fastsuggest",
        // we need to use "padded" json (jsonp)
        // using regular json gives a CORS error
        dataType: 'jsonp',
        type: 'GET',
        // query parameters
        data: function (params) {
          return {
            query: params.term, // search term
            queryIndex: facet,
            queryReturn: facet + queryIndices,
            rows: numRows,
            suggest: subjectDB,
          };
        },
        /**
         * description: format FAST data into Select2 format
         *
         * @param data data returned by FAST API call
         * @returns {results: array usable by Select2}}
         */
        processResults: function (data) {
          // the docs array from FAST the actual data we need
          var arraySelect2 = data.response.docs;
    
          /**
           * description: modify the raw data from FAST into a Select2 format.
           * all we need to do is to add a field called ["id"] to the array
           *
           * @param value
           * @param index
           */
          function convertFastToSelect2(value, index) {
            var data = value;
            // Select2 requires a field called ["id"]
            // ["id"] needs to have all the data we want to save for later use
            // we want just the numeric form (without leading zeros for the fastid)
            // so we removr "fst" and any leading zeros
            let idroot = value["idroot"];
            let re = /(fst0*)/;
            let fastid = idroot.replace(re, '');
            data.id = fastid + ":" + value["auth"] + ":" + getFASTTypeFromTag(value["tag"]);
          }
          arraySelect2.forEach(convertFastToSelect2);
          return {
            results: arraySelect2
          };
        },
      },
      templateResult: formatFASTSubjectResultAdmin,
      templateSelection: formatFASTSubjectSelectionAdmin,
    });
  });
});
