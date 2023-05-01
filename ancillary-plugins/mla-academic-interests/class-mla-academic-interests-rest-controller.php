<?php
/**
 * REST controller used by select2 to query terms.
 *
 * @package Mla_Academic_Interests
 */

/**
 * Controller.
 */
class Mla_Academic_Interests_REST_Controller extends WP_REST_Controller {


	/**
	 * Constructor.
	 *
	 * @since  alpha
	 * @access public
	 */
	public function __construct() {

		$this->namespace = 'mla-academic-interests/v1';
		$this->rest_base = '/terms';
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since  alpha
	 * @access public
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace, $this->rest_base, [
				'methods' => 'GET',
				'callback' => [ $this, 'get_terms' ],
			]
		);
	}

	/**
	 * Sort query results relative to user input:
	 * ( case-insensitive )
	 * 1 complete word matches
	 * 2 matches at beginning of term
	 * 3 matches elsewhere in term
	 *
	 * Mla_Academic_Interests::mla_academic_interests_list() already uses natcasesort(), this does additional sorting
	 *
	 * @param  array  $matched_terms natcasesort()ed array of term objects containing properties 'id' & 'text' to be sorted.
	 * @param  string $user_input    search query.
	 * @return array $matched_terms sorted terms.
	 */
	public static function sort_matched_terms( array $matched_terms, string $user_input ) {

		$sorted_terms = [];

		// Pull out matches at beginning of term first (complete word matches are first alphabetically).
		foreach ( $matched_terms as $i => $matched_term ) {
			if ( 0 === strpos( strtolower( $matched_term->text ), strtolower( $user_input ) ) ) {
				$sorted_terms[] = $matched_term;
				unset( $matched_terms[ $i ] );
			}
		}

		// Append the remaining terms to the sorted terms.
		$sorted_terms = array_merge( $sorted_terms, $matched_terms );

		return $sorted_terms;
	}

	/**
	 * Get list of terms that match $_GET['q']
	 *
	 * @param WP_REST_Request $data request data (expected to contain a query in the 'q' parameter).
	 * @return WP_REST_Response
	 */
	public function get_terms( WP_REST_Request $data ) {

		global $mla_academic_interests;

		$start_time = microtime();

		$params = $data->get_query_params();

		$user_input = $params['q'];

		$response = new WP_REST_Response;

		$cache_key = 'mla_academic_interests_terms_' . sanitize_title( $user_input );

		$matched_terms = wp_cache_get( $cache_key );

		if ( ! $matched_terms ) {

			$all_terms = $mla_academic_interests->mla_academic_interests_list();

			$matched_terms = [];

			// Populate array of matches.
			foreach ( $all_terms as $term_id => $term ) {
				if ( false !== strpos( strtolower( $term ), strtolower( $user_input ) ) ) {
					$matched_term = new stdClass;
					$matched_term->id = $term_id;
					$matched_term->text = $term;

					$matched_terms[] = $matched_term;
				}
			}

			$matched_terms = self::sort_matched_terms( $matched_terms, $user_input );

			wp_cache_set( $cache_key, $matched_terms, null, 300 );

		}

		// Formatted for select2 consumption.
		$response->set_data(
			[
				'results' => $matched_terms,
			// 'time' => microtime() - $start_time, // for debugging
			]
		);

		return $response;
	}
}
