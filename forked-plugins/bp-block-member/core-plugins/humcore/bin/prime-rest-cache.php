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
 * @package HumCORE
 */

$keyword_rest_controller = new Humcore_Deposits_Keyword_REST_Controller;

foreach ( humcore_deposits_keyword_list() as $term ) {
	$request = new WP_REST_Request( 'GET', '/humcore-deposits-keywords/v1/terms' );
	$request->set_query_params(
		[
			'q' => $term,
		]
	);

	$result = $keyword_rest_controller->get_terms( $request );

	if ( $result->is_error() ) {
		WP_CLI::error( "failed to prime '$term'" );
	} else {
		WP_CLI::success( "primed '$term'" );
	}
}

$subject_rest_controller = new Humcore_Deposits_Subject_REST_Controller;

foreach ( humcore_deposits_subject_list() as $term ) {
	$request = new WP_REST_Request( 'GET', '/humcore-deposits-subjects/v1/terms' );
	$request->set_query_params(
		[
			'q' => $term,
		]
	);

	$result = $subject_rest_controller->get_terms( $request );

	if ( $result->is_error() ) {
		WP_CLI::error( "failed to prime '$term'" );
	} else {
		WP_CLI::success( "primed '$term'" );
	}
}
