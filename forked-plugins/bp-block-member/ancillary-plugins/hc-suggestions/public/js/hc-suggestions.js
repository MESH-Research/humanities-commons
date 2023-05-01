window.hc_suggestions  = {

	widget_container_class: 'hc-suggestions-widget',

	hide_path: '/wp-json/hc-suggestions/v1/hide?',

	query_path: '/wp-json/hc-suggestions/v1/query?',

	/**
	 * Handle "hide" button click event.
	 *
	 * @param event $e
	 */
	handle_hide_click: function( e ) {
		e.preventDefault();

		var params = {
			post_id: $( this ).attr( 'data-post-id' ),
			post_type: $( this ).attr( 'data-post-type' ),
		};

		$.post( hc_suggestions.hide_path + $.param( params ) );

		$( this ).parents( '.result' ).fadeOut();
	},

	/**
	 * Load results into target element via XHR.
	 *
	 * @param object         $params object containing "s" & "post_type" keys to query
	 * @param jQuery element $target element into which to inject search results
	 */
	load_results: function( params, target ) {
		var loader_img = $(
			'<img class="loader" src="/app/plugins/hc-suggestions/public/images/ajax-loader.gif" alt="Loading...">'
		);

		params.cache_buster = Date.now();

		loader_img.appendTo( target );

		$.ajax( {
			url: hc_suggestions.query_path + $.param( params ),
			headers: { 'X-WP-Nonce': wpApiSettings.nonce }
		} ).then( function( data ) {
			var no_results_markup = $( '<p>No results.</p>' );

			$( target ).find( '.btn.more' ).remove();

			if ( Object.keys( data.results ).length > 0 ) {
				html = '';

				$.each( data.results, function( i, result ) {
					// only append result if it's not already listed
					if ( 0 === target.find( '.result[data-post-id="' + i + '"]' ).length ) {
						html += result;
					}
				} );

				$( html ).appendTo( target );

				$( target ).find( '.hide' ).on( 'click', hc_suggestions.handle_hide_click );

				$( '<a href="#" class="btn more">More results</a>' )
					.appendTo( target )
					.on( 'click', function( e ) {
						e.preventDefault();
						params.paged = 1 + ( params.paged || 1 );
						hc_suggestions.load_results( params, target );
					} );
			} else if ( ! $( target ).is( ':contains(' + no_results_markup.html() + ')' ) ) {
				no_results_markup.appendTo( target );
			}

			$( target ).find( loader_img ).remove();
		} );
	},

	handle_resize: function( e ) {
		var widget = $( '.' + hc_suggestions.widget_container_class );

		if ( 450 > widget.outerWidth() ) {
			widget.addClass( 'narrow' );
		} else {
			widget.removeClass( 'narrow' );
		}
	},

	/**
	 * Initialize widget tab ui & load results for each tab
	 */
	init: function() {
		var widget = $( '.' + hc_suggestions.widget_container_class );
		if ( widget && typeof widget.tabs === 'function' ) {
			widget
				.tabs()
				.on( 'resize', hc_suggestions.handle_resize )
				.find( 'div' ).each( function( i, el ) {
					hc_suggestions.load_results(
						{
							s: $( el ).attr( 'data-hc-suggestions-query' ),
							post_type: $( el ).attr( 'data-hc-suggestions-type' ),
						},
						$( el )
					);
				} );
		}
	}
}

jQuery( hc_suggestions.init );
