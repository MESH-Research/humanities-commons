<?php
/**
 * Enable browser autocomplete for ninja forms.
 *
 * @package Commons
 */

/**
 * Add the ninja forms autocomplete hook to the footer.
 */
function hcommons_ninja_forms_autocomplete() {
	$print_js = function() {
		?>
		<iframe name="ninja-forms-autocomplete" style="display:none" src="https://about:blank"></iframe>
		<script>
		jQuery( function( $ ) {
				if ( 'object' !== typeof Marionette ) {
					return;
				};
				var HcNfAutocomplete = Marionette.Object.extend( {
					initialize: function() {
						this.listenTo( Backbone.Radio.channel( 'forms' ), 'submit:response', this.actionSubmit );
					},
					actionSubmit: function( response ) {
						$( '.nf-form-wrap form' )
							.attr( 'target', 'ninja-forms-autocomplete' )
							.attr( 'action', '/robots.txt' ) // This doesn't do anything except fool browsers with a 200 OK response.
							[0].submit();
					},
				});
				new HcNfAutocomplete();
		} );
		</script>
		<?php
	};

	add_action( 'wp_footer', $print_js, 100 );
}

// Run only on nf_init so as not to embed JS where the plugin isn't active.
add_action( 'nf_init', 'hcommons_ninja_forms_autocomplete' );
