<?php
/**
 * REST controller to query ElasticPress dynamically
 *
 * @package HC_Suggestions
 */

/**
 * Controller
 */
class HC_Suggestions_REST_Controller extends WP_REST_Controller {

	/**
	 * User meta key for hidden posts.
	 */
	const META_KEY_USER_HIDDEN_POSTS = 'hc_suggestions_hidden_posts';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->namespace = 'hc-suggestions/v1';
	}

	/**
	 * Registers the routes for the objects of the controller
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/query',
			[
				'methods'  => 'GET',
				'callback' => [ $this, 'query' ],
			]
		);
		register_rest_route(
			$this->namespace,
			'/hide',
			[
				'methods'  => 'POST',
				'callback' => [ $this, 'hide' ],
			]
		);
	}

	/**
	 * Hide a post from appearing in suggestions for the current user
	 *
	 * @param WP_REST_Request $data request data. Expected to contain "post_id" & "post_type" params.
	 * @return WP_REST_Response
	 */
	public function hide( WP_REST_Request $data ) {
		/**
		 * The global $current_user isn't populated here, have to do it ourselves.
		 * This won't work without shibd setting this header.
		 */
		wp_set_current_user( get_user_by( 'login', $_SERVER['HTTP_EMPLOYEENUMBER'] ) );

		$params = $data->get_query_params();

		$user_hidden_posts = $this->_get_user_hidden_posts();

		$user_hidden_posts[ $params['post_type'] ] = array_unique(
			array_merge(
				isset( $user_hidden_posts[ $params['post_type'] ] ) ? $user_hidden_posts[ $params['post_type'] ] : [],
				[ $params['post_id'] ]
			)
		);

		// Note $result will be false if $user_hidden_posts is the same as the old value.
		$result = update_user_meta( get_current_user_id(), self::META_KEY_USER_HIDDEN_POSTS, $user_hidden_posts );

		$response = new WP_REST_Response;

		$response->set_status( $result ? 200 : 500 );

		return $response;
	}

	/**
	 * Helper function to define a WP_Query from WP_REST_Request params.
	 *
	 * @param array $params WP_REST_Request params.
	 * @return WP_Query Query from request params.
	 */
	protected function get_wp_query( array $params ) {
		global $wpdb;

		/**
		 * $_REQUEST param names are hardcoded to be parsed by elasticpress-buddypress,
		 * (and possibly elsewhere) so the names must match here
		 */
		$wp_query_params = [
			'ep_integrate'  => true,
			'post_type'     => $params['post_type'],
			's'             => $params['s'],
			'paged'         => isset( $params['paged'] ) ? $params['paged'] : 1,
		];

		if ( is_user_logged_in() ) {
			switch ( $params['post_type'] ) {
				case 'user':
					// Exclude self.
					$exclude_user_ids = [ get_current_user_id() ];

					// Exclude users already being followed by the current user.
					$exclude_user_ids = array_merge(
						$exclude_user_ids,
						(array) bp_follow_get_following(
							[
								'user_id' => get_current_user_id(),
							]
						)
					);

					$wp_query_params['post__not_in'] = array_unique( $exclude_user_ids );
					break;
				case 'bp_group':
					// Exclude groups already joined by the current user.
					$exclude_group_ids = array_keys(
						bp_get_user_groups(
							get_current_user_id(),
							[
								'is_admin' => null,
								'is_mod'   => null,
							]
						)
					);

					// Exclude groups on society networks the current user does not belong to.
					$current_user_memberships = (array) bp_get_member_type( get_current_user_id(), false );
					$non_member_society_groups = groups_get_groups(
						[
							'group_type__not_in' => $current_user_memberships,
							'per_page'           => 999, // TODO This won't scale well.
						]
					);
					foreach ( $non_member_society_groups['groups'] as $group ) {
						$exclude_group_ids[] = $group->id;
					}

					// Exclude private groups.
					// TODO should do this here, but there's no 'status' param to groups_get_groups until bp 2.9.
					// For now, check in the loop below and just exclude there.
					$wp_query_params['post__not_in'] = array_unique( $exclude_group_ids );
					break;
				case 'humcore_deposit':
					/**
					 * Exclude posts authored by the current user.
					 * There's a bug, possibly in elasticpress-buddypress, that breaks 'author__not_in'.
					 * This is a workaround to avoid using that param.
					 */
					$sql = [];
					foreach ( get_networks() as $network ) {
						switch_to_blog( $network->blog_id );
						$sql[] = sprintf(
							"SELECT ID FROM %s %s",
							$wpdb->posts,
							get_posts_by_author_sql( 'humcore_deposit', true, get_current_user_id() )
						);
						restore_current_blog();
					}
					$author_post_ids = $wpdb->get_col( implode( ' UNION ', $sql ) );

					$wp_query_params['post__not_in'] = array_unique( $author_post_ids );
					break;
				default:
					break;
			}

			// Exclude user-hidden posts.
			$user_hidden_posts = $this->_get_user_hidden_posts();
			if ( isset( $user_hidden_posts[ $params['post_type'] ] ) ) {
				$existing_post__not_in = isset( $wp_query_params['post__not_in'] ) ? $wp_query_params['post__not_in'] : [];

				$wp_query_params['post__not_in'] = array_unique(
					array_merge(
						$existing_post__not_in,
						$user_hidden_posts[ $params['post_type'] ]
					)
				);
			}
		}

		/**
		 * By default, ElasticPress converts the 's' search parameter into a
		 * phrase-type ElasticSearch query. The recommendations widget passes a
		 * user's academic interests as a concactinated string, so we need to do
		 * a normal match search.
		 *
		 * @see https://www.elasticpress.io/blog/2019/02/custom-search-with-elasticpress-how-to-limit-results-to-full-text-matches/
		 * @see https://www.elastic.co/guide/en/elasticsearch/guide/current/match-multi-word.html
		 */
		add_filter( 'ep_formatted_args', function( $formatted_args, $args ) {
			if ( ! empty( $formatted_args['query']['bool']['should'] &&
				is_array( $formatted_args['query']['bool']['should'] ) ) ) {
					foreach ( $formatted_args['query']['bool']['should'] as &$es_search ) {
						unset( $es_search['multi_match']['type'] );
						$es_search['multi_match']['operator'] = 'or';
					}
				}
			return $formatted_args;
		}, 10, 2 );

		$query = new WP_Query( $wp_query_params );
		return $query;
	}

	/**
	 * Query ElasticPress for relevant content
	 *
	 * @param WP_REST_Request $data request data. Expected to contain "s" & "post_type" params.
	 * @return WP_REST_Response
	 */
	public function query( WP_REST_Request $data ) {
		$response_data = [];
		$params = $data->get_query_params();

		$hcs_query = $this->get_wp_query( $params );

		while ( $hcs_query->have_posts() ) {
			$hcs_query->the_post();

			// TODO once BP is upgraded to 2.9, move this to the switch above.
			if ( 'bp_group' === $params['post_type'] ) {
				$group = groups_get_group( get_the_ID() );
				if ( ! $group || 'public' !== $group->status ) {
					continue;
				}
			}

			// Skip humcore_deposit results that have parents (are attachments).
			if ( 'humcore_deposit' === $params['post_type'] ) {
				if ( $hcs_query->post->post_parent ) {
					continue;
				}
			}


			$response_data[ get_the_ID() ] = $this->_get_formatted_post();
		}
		
		$response = new WP_REST_Response;

		$response->set_data(
			[
				'results' => $response_data,
			]
		);

		return $response;
	}

	/**
	 * Format a search result for output
	 *
	 * @global $post current post in the search results loop
	 * @return string formatted post markup
	 */
	public function _get_formatted_post() {
		global $post;

		ob_start();

		bp_get_template_part( 'suggestions/' . str_replace( '_', '-', $post->post_type ) );

		return ob_get_clean();
	}

	/**
	 * Fetch user-hidden posts for exclusion from query() or updating in hide()
	 *
	 * @return array multidimensional array e.g. [ 'user' => [ 1, 2 ], 'humcore_deposit => [ 1 ] ]
	 */
	function _get_user_hidden_posts() {
		$retval = [];

		$meta = get_user_meta( get_current_user_id(), self::META_KEY_USER_HIDDEN_POSTS, true );

		if ( $meta ) {
			$retval = $meta;
		}

		return $retval;
	}
}
