<?php

/**
 * Plugin Name: ElasticPress REST
 * Version: alpha
 * Description: ElasticPress custom feature to support live filtering via a custom WordPress REST API endpoint.
 */

class EPR_REST_Posts_Controller extends WP_REST_Controller {

	// include debug output in REST response
	const DEBUG = true;

	/**
	 * Constructor.
	 *
	 * @since alpha
	 * @access public
	 */
	public function __construct() {
		$this->namespace = 'epr/v1';
		$this->rest_base = '/query';

		// this is not necessary and can cause bad results from elasticsearch, disable it.
		remove_filter( 'request', 'bbp_request', 10 );
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since alpha
	 * @access public
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, $this->rest_base, [
			'methods' => 'GET',
			'callback' => [ $this, 'get_items' ],
		] );
	}

	/**
	 * Query ElasticPress using query vars
	 *
	 * @return WP_REST_Response
	 */
	public function get_items( $data ) {
		global $wp_query;

		$response = new WP_REST_Response;

		$debug = [];

		add_action( 'ep_add_query_log', function( $ep_query ) use ( &$response, &$debug ) {
			$debug['ep_query'] = $ep_query;
			$response->set_status( $ep_query['request']['response']['code'] );
		} );

		$query_params = $data->get_query_params();
		if ( array_key_exists( 'numberposts', $query_params ) ) {
			$numberposts = $query_params['numberposts'];
		} else {
			$numberposts = 10;
		}

		if ( array_key_exists( 'paged', $query_params ) ) {
			$paged = $query_params['paged'];
		} else {
			$paged = 1;
		}

		if ( array_key_exists( 'future', $query_params ) ) {
			$future = $query_params['future'] === 'future';
		} else {
			$future = false;
		}

		$today = getdate();

		$query_params = array_merge (
			$data->get_query_params(),
			[ 
				'ep_integrate'   => true,
				'search_fields'  => 'post_content',
				'posts_per_page' => $numberposts,
				'paged'          => $paged,
				'date_query'     => [
					[
						'before' => [
							'year'  => $today['year'],
							'month' => $today['mon'],
							'day'   => $today['mday'],
						],
						'inclusive' => true,
					]
				]
			]
		);

		// Retrieve past-dated posts unless the 'future' param has been set.
		if ( ! $future ) {
			[ $past_posts, $past_pages ] = $this->get_posts_to( $query_params, $numberposts );
		} else {
			$past_posts = [];
			$past_pages = 0;
		}
		
		// If not enough posts have been retrieved, get future-dated posts
		if ( count( $past_posts ) < $numberposts ) {
			$query_params['paged'] = 1;
			$query_params['date_query'] = [
				'after' => [
					'year'  => $today['year'],
					'month' => $today['mon'],
					'day'   => $today['mday'],
				],
				'inclusive' => false,
			];
			[ $future_posts, $future_pages ] = $this->get_posts_to( $query_params, $numberposts );
		} else {
			$future_posts = [];
			$future_pages = 0;
		}
		
		$response_data = [
			'posts'  => array_merge( $past_posts, $future_posts ),
			'pages'  => $future_pages ? $future_pages : $past_pages,
			'future' => $future_pages > 0
		];

		$debug['wp_query'] = $wp_query;

		$response->set_data( $response_data );

		return $response;
	}


	/**
	 * Retrieve posts until there are no more or the target_count has been reached.
	 *
	 * Runs multiple query pages to account for possibly skipped results.
	 *
	 * @param Array $query_params The query parameters.
	 * @param int   $target_count The desired number of posts to retrieve.
	 *
	 * @return [ Array, int ] The Array contains the post contents. 
	 *                        The int indicates how many pages were retrieved.
	 */
	private function get_posts_to( $query_params, $target_count ) {
		$max_page_count = 10; // Limit how many pages to fetch to prevent 504 errors.
		$starting_page =  intval( $query_params['paged'] );
		$page_count = 0;
		$posts = [];
		$existing_posts = [];
		do {
			$query_params['paged'] = $starting_page + $page_count;
			[ $new_posts, $existing_posts, $skipped ] = $this->query_posts( $query_params, $existing_posts );
			$posts = array_merge( $posts, $new_posts );
			$page_count++;
		} while ( $skipped && count( $posts ) < $target_count && $page_count < $max_page_count );
		return [ $posts, $page_count ];
	}
	
	/**
	 * Retrieve posts and output.
	 *
	 * @param Array $query_params The query parameters.
	 *
	 * @return [Array, boolean] The Array contains the post contents. 
	 *                          The boolean indicates whether any posts were skipped.
	 */
	private function query_posts( $query_params, $existing_posts = [] ) {
		global $wp_query;
		$skipped = false;
		$posts = [];
		$wp_query->query( $query_params );

		while( have_posts() ) {
			the_post();
			// Checking for duplicate posts.
			if ( 
				array_key_exists( $wp_query->post->ID, $existing_posts ) && 
				$existing_posts[ $wp_query->post->ID ] === $wp_query->post->post_title
			) {
				$skipped = true;
				continue;
			}
			if ( $wp_query->post->post_parent ) {
				$parent_post = get_post( $wp_query->post->post_parent );
				// Prevent humcore_deposit posts with parents (ie. attachments) from showing in results
				if ( $wp_query->post->post_type === 'humcore_deposit') {
					$skipped = true;
					continue;
				}
				// Prevent posts in private groups from showing in search results
				if ( $parent_post->post_status != 'publish' ) {
					$skipped = true;
					continue;
				}
			}
			$existing_posts[ $wp_query->post->ID ] = $wp_query->post->post_title;
			ob_start();
			get_template_part( 'content', get_post_format() );
			$posts[] = ob_get_contents();
			ob_end_clean();
		}
		return [ $posts, $existing_posts, $skipped ];
	}
}
