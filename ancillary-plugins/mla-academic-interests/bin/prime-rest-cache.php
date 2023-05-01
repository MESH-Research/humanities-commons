<?php
/**
 * Script to prime the object cache for existing terms.
 *
 * Once primed, user time spent waiting for results after entering an existing term will be minimal.
 *
 * If you want to remove existing term search cache first, flush object cache.
 * wp cache flush
 *
 * Then, run this script with wp-cli eval-file e.g.
 * wp eval-file bin/prime-rest-cache.php
 *
 * Use --quiet if you don't want a line of output for each term.
 *
 * @package Mla_Academic_Interests
 */

global $mla_academic_interests;

$rest_controller = new Mla_Academic_Interests_REST_Controller;

foreach ( $mla_academic_interests->mla_academic_interests_list() as $term ) {
	$request = new WP_REST_Request( 'GET', '/mla-academic-interests/v1/terms' );
	$request->set_query_params(
		[
			'q' => $term,
		]
	);

	$result = $rest_controller->get_terms( $request );

	if ( $result->is_error() ) {
		WP_CLI::error( "failed to prime '$term'" );
	} else {
		WP_CLI::success( "primed '$term'" );
	}
}
