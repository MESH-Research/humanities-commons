jQuery(function(){
	var handle_response = function( data ) {
		$( '#message' ).closest( '.ui-dialog-content' ).dialog( 'close' );
	}
	var data = {
		'action': 'hc_custom_bpges_settings_warning',
	};

	$( '#hc-bpges-warning-disable-this' ).on( 'click', function( e ) {
		data.hc_custom_bpges_setting_warning_group_ids = hc_custom_bpges_setting_warning_group_ids;
		$.post( ajaxurl, data, handle_response );
		e.preventDefault();
	} );

	$( '#hc-bpges-warning-disable-all' ).on( 'click', function( e ) {
		$.post( ajaxurl, data, handle_response );
		e.preventDefault();
	} );
});
