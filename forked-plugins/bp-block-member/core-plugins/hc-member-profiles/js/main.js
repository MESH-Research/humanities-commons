( function( $ ) {

	window.hcMemberProfiles = {

		init: function() {
			// visibility controls
			$( '#profile-edit-form .editable.hideable' ).each(function() {
				var div = $( this );

				// add visibility controls
				div.append( '<a href="#" class="visibility">hide</a>' );

				// bind visibility controls
				div.find( '.visibility' ).click(function() {
					var el = $( this );

					if ( 'hide' === el.html() ) {
						el.html( 'show' );
						div.addClass( 'collapsed' );
						div.find( 'input[value="adminsonly"]' ).attr( 'checked', true );
						div.find( 'input[value="public"]' ).attr( 'checked', false );
					} else {
						el.html( 'hide' );
						div.removeClass( 'collapsed' );
						div.find( 'input[value="adminsonly"]' ).attr( 'checked', false );
						div.find( 'input[value="public"]' ).attr( 'checked', true );
					}

					return false;
				});

				if ( div.find( 'input[value="adminsonly"]' ).is( ':checked' ) ) {
					div.find( '.visibility' ).triggerHandler( 'click' );
				}
			});

			// cancel button to send user back to view mode
			$( '#profile-edit-form #cancel' ).click(function( event ) {
				event.preventDefault();
				window.location = $( '#public' ).attr( 'href' );
			});

			window.hcMemberProfiles.initShowMoreButtons();
		},

		initShowMoreButtons: function() {
			$( '.profile .show-more' ).each(function() {
				var div = $( this );
				var header = div.find( 'h4' );
				var showMoreButton = $( '<button class="js-dynamic-show-hide button" title="Show more" data-replace-text="Show less">Show more</button>' );

				header.remove(); // this will be restored after wrapping the remaining contents in div.dynamic-height-wrap

				div
					.addClass( 'js-dynamic-height' )
					.attr( 'data-maxheight', 250 )
					.html( header[0].outerHTML + '<div class="dynamic-height-wrap">' + div.html() + '</div>' + showMoreButton[0].outerHTML );
			});

			// some fields should be taller than the rest
			$( '.profile .work-shared-in-core, .profile .other-publications' ).each(function() {
				$( this ).attr( 'data-maxheight', 400 );
			});

			$( '.js-dynamic-height' ).dynamicMaxHeight();

			// buddypress adds ajax & link-like functionality to buttons.
			// prevent page from reloading when "show more" button pressed.
			$( '.js-dynamic-show-hide' ).click(function( event ) {
				event.preventDefault();
			});

			// button is also not automatically hid if itemheight < maxheight. fix it
			$.each( $( '.js-dynamic-height' ), function() {
				var maxHeight = parseInt( $( this ).attr( 'data-maxheight' ), 10 );
				var itemHeight = parseInt( $( this ).attr( 'data-itemheight' ), 10 );

				if ( maxHeight > itemHeight ) {
					$( this ).find( '.js-dynamic-show-hide' ).hide();
				}
			});
		},

	};

	$( document ).ready( window.hcMemberProfiles.init );

})( jQuery );
