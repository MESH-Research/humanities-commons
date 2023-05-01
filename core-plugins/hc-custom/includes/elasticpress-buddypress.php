<?php
/**
 * Customizations to elasticpress-buddypress.
 *
 * @package Hc_Custom
 */

/**
 * Add core deposit metadata to index.
 *
 * @param array   $keys Array of index-able private meta keys.
 * @param WP_Post $post The current post to be indexed.
 */
function hcommons_filter_ep_prepare_meta_allowed_protected_keys( array $keys = [], WP_Post $post ) {
	if ( 'humcore_deposit' === $post->post_type ) {
		$keys = array_merge(
			$keys, [
				'_deposit_file_metadata',
			]
		);
	}

	return $keys;
}
add_filter( 'ep_prepare_meta_allowed_protected_keys', 'hcommons_filter_ep_prepare_meta_allowed_protected_keys', 10, 2 );

/**
 * Ensure $post->permalink is used rather than the_permalink() to handle cross-network results.
 *
 * @param string $post_link Permalink.
 * @return string
 */
function hcommons_filter_post_type_link( string $post_link = '' ) {
	global $post;

	if (
		is_search() &&
		get_query_var( 'ep_integrate' ) &&
		'humcore_deposit' === $post->post_type
	) {
		$post_link = $post->permalink;
	}

	return $post_link;
}
add_filter( 'post_type_link', 'hcommons_filter_post_type_link', 20 );

/**
 * Add custom taxonomies to elasticsearch queries.
 *
 * @param WP_Query $query Search query.
 */
function hcommons_add_terms_to_search_query( WP_Query $query ) {
	if (
		is_search() &&
		! ( defined( 'WP_CLI' ) && WP_CLI ) &&
		! apply_filters( 'ep_skip_query_integration', false, $query )
	) {
		$query->set(
			'search_fields', array_unique(
				array_merge_recursive(
					(array) $query->get( 'search_fields' ),
					[
						'taxonomies' => [
							'mla_academic_interests',
							'humcore_deposit_subject',
							'humcore_deposit_tag',
						],
					]
				), SORT_REGULAR
			)
		);
	}
}
// After elasticpress ep_improve_default_search().
add_action( 'pre_get_posts', 'hcommons_add_terms_to_search_query', 20 );

/**
 * Overwrite search result post excerpt with the relevant matching text from the query so it's obvious what content matched.
 * Since ElasticSearch is fuzzy, there may not be exact matches - in which case just defer to elasticpress defaults.
 *
 * @param array  $results The unfiltered search results.
 * @param array  $response The response body retrieved from Elasticsearch.
 * @param array  $args See EP_API->query().
 * @param string $scope See EP_API->query().
 * @return array $results Filtered results.
 */
function hcommons_filter_ep_search_results_array( array $results, array $response, array $args, string $scope ) {
	$search_query = strtolower( get_search_query() );

	$abbreviate_match = function( $str, $pos ) use ( $search_query ) {
		$strlen  = strlen( $search_query );
		$padding = 20 * $strlen; // Max characters to include on either side of the matched text.
		return substr( strip_tags( $str ), ( $pos - $padding > 0 ) ? $pos - $padding : 0, 2 * $padding );
	};

	foreach ( $results['posts'] as &$post ) {
		$matched_text = [];

		if ( ! empty( $search_query ) ) {
			foreach ( $post['terms'] as $tax ) {
				foreach ( $tax as $term ) {
					$strpos = strpos( strtolower( strip_tags( $term['name'] ) ), $search_query );
					if ( false !== $strpos ) {
						$matched_text[ $term['slug'] ] = $abbreviate_match( $term['name'], $strpos );
					}
				}
			}

			foreach ( [ 'post_excerpt', 'post_content' ] as $property ) {
				if ( ! empty( $matched_text[ $property ] ) ) {
					$strpos = strpos( strtolower( strip_tags( $property ) ), $search_query );
					if ( false !== $strpos ) {
						$matched_text[ $property ] = $abbreviate_match( $property, $strpos );
					}
				}
			}
		}

		/**
		 * Ensure we're not duplicating content that's already in the excerpt.
		 * (excerpt can include terms depending on type e.g. member "about" xprofile field)
		 */
		foreach ( $matched_text as $i => $match ) {
			// Adjust comparison for different filtering.
			$clean_match   = preg_replace( '/\s+/', ' ', strip_tags( $match ) );
			$clean_excerpt = preg_replace( '/\s+/', ' ', strip_tags( $post['post_excerpt'] ) );

			if ( false !== strpos( $clean_excerpt, $clean_match ) ) {
				unset( $matched_text[ $i ] );
			}
		}

		if ( count( $matched_text ) ) {
			$post['post_excerpt'] = implode(
				'', [
					'...',
					implode( '...<br>...', array_unique( $matched_text ) ),
					'...<br><br>',
					$post['post_excerpt'],
				]
			);
		}
	}

	return $results;
}
add_filter( 'ep_search_results_array', 'hcommons_filter_ep_search_results_array', 10, 4 );

/**
 * Filter out humcore child posts from indexing.
 * 
 * @see elasticpress - SyncManager::action_queue_meta_sync
 * 
 * @param bool  $skip      True means kill sync for post
 * @param int   $object_id ID of post
 * @param int   $__        ID of post (repeated)
 * @return bool New value for $skip
 */
function hcommons_filter_ep_post_sync_kill( bool $skip, int $object_id, int $__ ) {
	$the_post = get_post( $object_id );

	if ( ! $the_post ) {
		return $skip;
	}

	if ( 'humcore_deposit' === $the_post->post_type && false !== (bool) $the_post->post_parent ) {
		$skip = true;
	}
	return $skip;
}
add_filter( 'ep_post_sync_kill', 'hcommons_filter_ep_post_sync_kill', 10, 3 );

/*
 * Networks ( Jan 2022 )
 * '1 : MLA Commons',
 * '2 : Humanities Commons',
 * '3 : AJS Commons',
 * '4 : ASEEES Commons',
 * '5 : CAA Commons',
 * '6 : UP Commons',
 * '7 : MSU Commons',
 * '8 : ARLIS/NA Commons',
 * '10 : SAH Commons',
 * '11 : Commons Hub',
 * '12 : SocSci Commons',
 * '13 : STEM Commons'
 */

// Hide some networks & post types from search facets.
add_filter( 'ep_bp_show_network_facet_3', '__return_false' ); // AJS.
add_filter( 'ep_bp_show_network_facet_5', '__return_false' ); // CAA.
add_filter( 'ep_bp_show_network_facet_8', '__return_false' ); // ARLIS.
add_filter( 'ep_bp_show_network_facet_11', '__return_false' ); // HUB.
add_filter( 'ep_bp_show_network_facet_12', '__return_false' ); // SOCSCI.
add_filter( 'ep_bp_show_network_facet_13', '__return_false' ); // STEM.
//add_filter( 'ep_bp_show_post_type_facet_post', '__return_false' );
//add_filter( 'ep_bp_show_post_type_facet_page', '__return_false' );
add_filter( 'ep_bp_show_post_type_facet_attachment', '__return_false' );
add_filter( 'ep_bp_show_post_type_facet_forum', '__return_false' );
add_filter( 'ep_bp_show_post_type_facet_bp_doc', '__return_false' );
add_filter( 'ep_bp_show_post_type_facet_event', '__return_false' );
add_filter( 'ep_bp_show_post_type_facet_bp_docs_folder', '__return_false' );
add_filter( 'ep_bp_show_post_type_facet_elementor_library', '__return_false' );
add_filter( 'ep_bp_show_post_type_facet_e-landing-page', '__return_false' );

/**
 * If query contains quotes, no fuzziness.
 *
 * @param float $fuzziness Fuzziness argument for ElasticSearch.
 * @return float
 */
function hcommons_filter_ep_fuzziness_arg( $fuzziness ) {
	global $wp_query;
	if ( strpos( $wp_query->get( 's' ), '"' ) !== false ) {
		$fuzziness = 0;
	}
	return $fuzziness;
}
add_filter( 'ep_fuzziness_arg', 'hcommons_filter_ep_fuzziness_arg', 2 );

/**
 * Add custom post types to the fallback selections for search facets.
 *
 * @param array $post_types List of post types to index.
 * @return array
 */
function hcommons_filter_ep_bp_fallback_post_type_facet_selection( $post_types ) {
	return array_merge(
		$post_types, [
			'humcore_deposit',
		]
	);
}
add_filter( 'ep_bp_fallback_post_type_facet_selection', 'hcommons_filter_ep_bp_fallback_post_type_facet_selection' );

/**
 * Make deposits indexable.
 *
 * @param array $post_types Indexable post types.
 * @return array
 */
function hcommons_filter_ep_indexable_post_types( $post_types ) {
	return array_unique(
		array_merge(
			$post_types, [
				'humcore_deposit' => 'humcore_deposit',
			]
		)
	);
}
add_filter( 'ep_indexable_post_types', 'hcommons_filter_ep_indexable_post_types' );

/**
 * Filter humcore permalinks (for elasticpress results).
 *
 * @param string  $post_link Post link.
 * @param WP_Post $post Post.
 */
function humcore_filter_post_type_link( $post_link, $post ) {
	if ( 'humcore_deposit' === get_post_type() ) {

		// Hope index has the correct permalink or fall back to meta otherwise.
		if ( false !== strpos( $post->permalink, 'hc:' ) ) {
			$post_link = $post->permalink;
		} else {
			$meta = get_post_meta( get_the_ID() );

			// If we're missing post meta, we're probably on the wrong blog for this post.
			// TODO is there a way to get blog_id for a post, so we can switch_to_blog for meta instead of invoking solr?
			if ( ! isset( $meta['_deposit_metadata'][0] ) ) {
				preg_match( '/\/' . get_post_type() . '\/([\w]+)\//', $post_link, $matches );
				if ( isset( $matches[1] ) ) {
					$meta = humcore_has_deposits( 'include=' . $matches[1] );
				}
			}

			if ( isset( $meta['_deposit_metadata'][0] ) ) {
				$decoded_deposit_meta = json_decode( $meta['_deposit_metadata'][0] );
				$post_link            = sprintf( '%1$s/deposits/item/%2$s', bp_get_root_domain(), $decoded_deposit_meta->pid );
			}
		}
	}

	return $post_link;
}
add_filter( 'post_type_link', 'humcore_filter_post_type_link', 20, 2 );
// define the the_permalink callback

function filter_the_permalink( $get_permalink ) {

	$society_mapped_domain_constant = strtoupper( Humanities_Commons::$society_id ) . '_MAPPED_URL';
	$society_site_domain_constant = strtoupper( Humanities_Commons::$society_id ) . '_SITE_URL';
	if ( defined( $society_mapped_domain_constant ) ) {
		if ( false !== strstr( $get_permalink, 'https://' .  constant( $society_mapped_domain_constant ) ) ) {
			$get_permalink = str_replace( constant( $society_site_domain_constant ), constant( $society_mapped_domain_constant ), $get_permalink );
		}
	}
	return $get_permalink;
};

// add the filter
add_filter( 'the_permalink', 'filter_the_permalink', 10, 1 );

