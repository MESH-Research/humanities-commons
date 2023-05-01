// Select 2 controls
jQuery(document).ready( function($) {

  $(".js-basic-single-required").select2({
    minimumResultsForSearch: "36",
  });

  $(".js-basic-single-optional").select2({
    allowClear: "true",
    minimumResultsForSearch: "36",
  });

  $(".js-basic-multiple").select2({
  });


  $( '.js-basic-multiple-academic-interests-tags' ).select2( {
    minimumInputLength: 1,
    tags: true,
    tokenSeparators: [','],
    ajax: {
      url: '/wp-json/mla-academic-interests/v1/terms',
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
  $( '.js-basic-multiple-academic-interests-tags' ).on( 'select2:open', function( e ) {
    var observer = new MutationObserver( function() {
      $( '.select2-results__options [aria-selected]' ).attr( 'aria-selected', false );
    } );

    observer.observe( $( '.select2-results__options' )[0], { childList: true } );
  } );

  // ensure user-input terms conform to existing terms regardless of case
  // e.g. if user enters "music" and "Music" exists, select "Music"
  $( '.js-basic-multiple-academic-interests-tags' ).on( 'select2:selecting', function( e ) {
    var input_term = e.params.args.data.id;
    var existing_terms = $( '.select2-results__option' ).not( '.select2-results__option--highlighted' );
    var Select2 = $( '.js-basic-multiple-academic-interests-tags' ).data( 'select2' );

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

} );
