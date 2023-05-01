/**
 * Ensures that the 'All Deposits' filter is selected by default when viewing
 * deposit listings.
 *
 * This prevents a bug where if no filter is selected buddypress crashes when
 * handling clicks on page links. The function does two things: (1) Adds a
 * 'bp-deposits-scope' cookie set to 'all' if none exists. This applies to
 * logged-in users. (2) Sets the directoryPreferences global to have scope 'all'
 * by default. This applies to logged-out users.
 *
 * (2) Addresses an apparent bug in buddypress.js where
 * bp_get_directory_preference() uses '' (empty string) as default but
 * bp_filter_request() checks for === null.
 * 
 * Enqueued by includes/humcore.php::hcommons_enqueue_all_deposits_script()
 * 
 * @see plugins/buddypress/bp-templates/bp-legacy/js/buddypress.js
 *
 * @author Mike Thicke
 */
jQuery(function(){
	var current_cookie = jQuery.cookie('bp-deposits-scope');
	if ( typeof current_cookie === 'undefined' ) {
		jQuery.cookie( 'bp-deposits-scope', 'all' );
	} else if ( 
		typeof bp_get_directory_preference !== 'undefined' && 
		! directoryPreferences.hasOwnProperty( 'deposits' ) 
	) {
		var defaultDepositsPreferences = {
			filter: null,
			scope: 'all',
			extras: null
		}
		directoryPreferences['deposits'] = defaultDepositsPreferences;
	}
} );