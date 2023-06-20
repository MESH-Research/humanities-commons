// Facet widget button control
jQuery( document ).ready( function( $ ) {

	$( '.search-facets .facet-display-button' ).on('click', function() {

		if ( $( this ).find( '.show-more' ).length ) {
			if ( $( this ).siblings( 'li:hidden' ).length > 10 ) {
				$( this ).parent().css( 'height', '625px' );
				$( this ).parent().css( 'overflow-y', 'scroll' );
				$( this ).siblings( 'li:hidden:lt(11)' ).show();
			} else {
				$( this ).siblings( 'li:hidden' ).show();
				$( this ).html( '<span class="show-less button white right">less>></span>' );
			}
		} else {
			$( this ).parent().css( 'height', 'auto' );
			$( this ).parent().css( 'overflow-y', 'hidden' );
			$( this ).siblings( 'li:gt(1)' ).hide();
			$( this ).html( '<span class="show-more button white right">more>></span>' );
		}

	} );

	$( '.directory-facets .facet-display-button' ).on('click', function() {

		if ( $( this ).find( '.show-more' ).length ) {
			if ( $( this ).siblings( 'li:hidden' ).length > 10 ) {
				$( this ).siblings( 'li:hidden:lt(11)' ).show();
			} else {
				$( this ).siblings( 'li:hidden' ).show();
				$( this ).html( '<span class="show-less button white right">less>></span>' );
			}
		} else {
			$( this ).siblings( 'li:gt(3)' ).hide();
			$( this ).html( '<span class="show-more button white right">more>></span>' );
		}

	} );

	$( 'a.facet-search-link' ).on('click', function() {
		if ( $( "#deposits-society" ).length ) {
			if ( $( '#deposits-society' ).hasClass( 'selected' ) ) {
                        	var scope = 'society';
			} else {
                        	var scope = 'all';
			}
		}
	 	$.cookie( 'bp-deposits-scope', scope, { path : '/' } );
		var current_url = $( this ).attr( 'href' ).split( '?' );
		var facet_value = current_url[1];
		var cookie_value = $.cookie( 'bp-deposits-extras' );
		var search_term = $( '#search-deposits-term' ).val().trim();
		if ( !$( this ).find( 'span.facet-list-item-control ' ).length ) {
	 		$.cookie( 'bp-deposits-extras', facet_value, { path : '/' } );
		} else if ( $( this ).find( 'span.facet-list-item-control.selected' ).length ) {
			var combined_matches = cookie_value.replace( facet_value, '' ).replace( /\&\&/, '&' ).replace( /^\&|\&$/, '' );
 			$.cookie( 'bp-deposits-extras', combined_matches, { path : '/' } );
 			$( this ).siblings( 'span.count' ).show();
 			$( this ).find( 'span.facet-list-item-control' ).attr( 'style', 'display: none !important' );
 			if ( combined_matches.trim() ) {
				if ( search_term ) {
		  			var combined_matches = combined_matches.concat( '&', 's=' + search_term );
		  		}
	 			$( this ).attr( 'href', current_url[0] + '?' + combined_matches );
	 		} else {
				if ( search_term ) {
		  			var combined_matches = combined_matches.concat( '&', 's=' + search_term );
		 			$( this ).attr( 'href', current_url[0] + '?' + search_term );
		  		} else {
		 			$( this ).attr( 'href', current_url[0] );
		 		}
	 		}
	 	} else {
			if ( cookie_value.trim() ) {
		 		var combined_matches = facet_value.concat( '&', cookie_value );
			} else {
	 			var combined_matches = facet_value;
	 		}
			if ( search_term.trim() ) {
	  			var combined_matches = combined_matches.concat( '&', 's=' + search_term );
	  		}
	 		$.cookie( 'bp-deposits-extras', combined_matches, { path : '/' } );
	 		$( this ).attr( 'href', current_url[0] + '?' + combined_matches );
 		}

	} );

//                if ($.cookie('bp-deposits-filter')) {
//                        $('select#deposits-order-by').val($.cookie('bp-deposits-filter'));
//                }
                $( '#deposits-order-by' ).on( 'change', function() {

                        var object = 'deposits';
			if ( $( "#deposits-society" ).length ) {
				if ( $( '#deposits-society' ).hasClass( 'selected' ) ) {
                        		var scope = 'society';
				} else {
                        		var scope = 'all';
				}
			}
                        var filter = $('select#deposits-order-by').val();
                        $.cookie('bp-deposits-filter',filter,{ path: '/' });
                        var search_field = $('#search-deposits-field').val();
                        $.cookie('bp-deposits-field',search_field,{ path: '/' });
                        var search_terms = '';
                        if ( $('.dir-search input#search-deposits-term').length ) {
                                search_terms = $('.dir-search input#search-deposits-term').val();
                        }
                        if ($.cookie('bp-deposits-extras')) {
                                $('#search-deposits-facets').val($.cookie('bp-deposits-extras'));
                        }

                        bp_filter_request( object, filter, scope, 'div.' + object, search_terms, 1, $.cookie('bp-' + object + '-extras') );

                        return false;

                });

                //if ($.cookie('bp-deposits-field')) {
                //        $('select#search-deposits-field').val($.cookie('bp-deposits-field'));
                //}
                $( '#search-deposits-field' ).on( 'change', function() {

                        var object = 'deposits';
			if ( $( "#deposits-society" ).length ) {
				if ( $( '#deposits-society' ).hasClass( 'selected' ) ) {
                        		var scope = 'society';
				} else {
                        		var scope = 'all';
				}
			}
                        var filter = $('select#deposits-order-by').val();
                        $.cookie('bp-deposits-filter',filter,{ path: '/' });
                        var search_field = $('#search-deposits-field').val();
                        $.cookie('bp-deposits-field',search_field,{ path: '/' });
                        var search_terms = '';
                        if ( $('.dir-search input#search-deposits-term').length ) {
                                search_terms = $('.dir-search input#search-deposits-term').val();
                        }
                        if ($.cookie('bp-deposits-extras')) {
                                $('#search-deposits-facets').val($.cookie('bp-deposits-extras'));
                        }

                       	bp_filter_request( object, filter, scope, 'div.' + object, search_terms, 1, $.cookie('bp-' + object + '-extras') );

                        return false;

                });

	$( '.dir-search input#search-deposits-term' ).on( 'change', function() {

                        var object = 'deposits';
			if ( $( "#deposits-society" ).length ) {
				if ( $( '#deposits-society' ).hasClass( 'selected' ) ) {
                        		var scope = 'society';
				} else {
                        		var scope = 'all';
				}
			}
                        var filter = $('select#deposits-order-by').val();
                        $.cookie('bp-deposits-filter',filter,{ path: '/' });
                        var search_field = $('#search-deposits-field').val();
                        $.cookie('bp-deposits-field',search_field,{ path: '/' });
                        var search_terms = '';
                        if ( $('.dir-search input#search-deposits-term').length ) {
                                search_terms = $('.dir-search input#search-deposits-term').val();
                        }
                        if ($.cookie('bp-deposits-extras')) {
                                $('#search-deposits-facets').val($.cookie('bp-deposits-extras'));
                        }

                        bp_filter_request( object, filter, scope, 'div.' + object, search_terms, 1, $.cookie('bp-' + object + '-extras') );

                        return false;
	} );

	$( '.dir-search input#search-deposits-submit' ).on( 'click', function() {

                        var object = 'deposits';
			if ( $( "#deposits-society" ).length ) {
				if ( $( '#deposits-society' ).hasClass( 'selected' ) ) {
                        		var scope = 'society';
				} else {
                        		var scope = 'all';
				}
			}
                        var filter = $('select#deposits-order-by').val();
                        $.cookie('bp-deposits-filter',filter,{ path: '/' });
                        var search_field = $('#search-deposits-field').val();
                        $.cookie('bp-deposits-field',search_field,{ path: '/' });
                        var search_terms = '';
                        if ( $('.dir-search input#search-deposits-term').length ) {
                                search_terms = $('.dir-search input#search-deposits-term').val();
                        }
                        if ($.cookie('bp-deposits-extras')) {
                                $('#search-deposits-facets').val($.cookie('bp-deposits-extras'));
                        }

                        bp_filter_request( object, filter, scope, 'div.' + object, search_terms, 1, $.cookie('bp-' + object + '-extras') );

                        return false;
	} );

	$('form#core-terms-acceptance-form input[type=submit][name=core_accept_terms_continue]').on('click', function(){
		if ( $('form#core-terms-acceptance-form input[type=checkbox][name=core_accept_terms]').is(':checked') ) {
			$('#core-terms-acceptance-form').submit();
		} else {
			alert('Please agree to the terms by checking the box next to "I agree".');
		}
	});

	$( '.defList .pub-metadata-display-button' ).on('click', function() {
		if ( $( '.defList .deposit-item-pub-metadata' ).css( 'display' ) == 'none' ) {
			$( '.defList .deposit-item-pub-metadata' ).css( 'display', 'block' );
			$( this ).html( '<span class="pub-metadata-display-button button white right">Hide details</span>' );
		} else {
			$( '.defList .deposit-item-pub-metadata' ).css( 'display', 'none' );
			$( this ).html( '<span class="pub-metadata-display-button button white right">Show details</span>' );
		}
	} );

} );
