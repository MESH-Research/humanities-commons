<?php
/**
 * filters for the ElasticPress BuddyPress feature
 */

use ElasticPress\Elasticsearch as Elasticsearch;

/**
 * Filter search request path to search groups & members as well as posts.
 */
function ep_bp_filter_ep_search_request_path( $path ) {
	//return str_replace( '/post/', '/post,' . EP_BP_API::GROUP_TYPE_NAME . ',' . EP_BP_API::MEMBER_TYPE_NAME . '/', $path );
	return str_replace( '/post/', '/', $path );
}

/**
 * Filter index name to include all sub-blogs when on a root blog.
 * This is optional and only affects multinetwork installs.
 */
function ep_bp_filter_ep_index_name( $index_name, $blog_id ) {
	// since we call ep_get_index_name() which uses this filter, we need to disable the filter while this function runs.
	remove_filter( 'ep_index_name', 'ep_bp_filter_ep_index_name', 10, 2 );

	$index_names = [ $index_name ];

	// checking is_search() prevents changing index name while indexing
	// only one of the below methods should be active. the others are left here for reference.
	if ( is_search() ) {
		/**
		 * METHOD 1: all indices
		 * only works if the number of shards being sufficiently low
		 * results in 400/413 error if > 1000 shards being searched
		 * see ep_bp_filter_ep_default_index_number_of_shards()
		 */
		//$index_names = [ '_all' ];

		/**
		 * METHOD 2: all main sites for all networks
		 * most practical if there are lots of sites (enough to worry about exceeded the shard query limit of 1000)
		 */
		foreach ( get_networks() as $network ) {
			$network_main_site_id = get_main_site_for_network( $network );
			$index_names[] = ep_get_index_name( $network_main_site_id );
		}

		/**
		 * METHOD 3: some blogs, e.g. 50 most recently active
		 * compromise if one of the prior two methods doesn't work for some reason.
		 */
		//if ( bp_is_root_blog() ) {
		//	$querystring =  bp_ajax_querystring( 'blogs' ) . '&' . http_build_query( [
		//		'type' => 'active',
		//		'search_terms' => false, // do not limit results based on current search query
		//		'per_page' => 50, // TODO setting this too high results in a query url which is too long (400, 413 errors)
		//	] );

		//	if ( bp_has_blogs( $querystring ) ) {
		//		while ( bp_blogs() ) {
		//			bp_the_blog();
		//			$index_names[] = ep_get_index_name( bp_get_blog_id() );
		//		}
		//	}
		//}

		// handle facets
		if ( isset( $_REQUEST['index'] ) ) {
			$index_names = $_REQUEST['index'];
		}
	}

	// restore filter now that we're done abusing ep_get_index_name()
	add_filter( 'ep_index_name', 'ep_bp_filter_ep_index_name', 10, 2 );

	return implode( ',', array_unique( $index_names ) );
}

/**
 * this is an attempt at limiting the total number of shards to make searching lots of sites in multinetwork feasible
 * not necessary unless querying lots of sites at once.
 * doesn't seem to hurt to leave it enabled in any case though.
 */
function ep_bp_filter_ep_default_index_number_of_shards( $number_of_shards ) {
	$number_of_shards = 1;
	return $number_of_shards;
}

/**
 * Filter the search results loop to fix some bad permalinks.
 */
function ep_bp_filter_the_permalink( $permalink ) {
	global $wp_query, $post;

	if ( $wp_query->is_search ) {
		if ( in_array( $post->post_type,  [ EP_BP_API::GROUP_TYPE_NAME, EP_BP_API::MEMBER_TYPE_NAME ] ) ) {
			$permalink = $post->permalink;
		} else if ( in_array( $post->post_type,  [ 'reply' ] ) ) {
			$permalink = bbp_get_topic_permalink( $post->post_parent ) . "#post-{$post->ID}";
		}
	}

	return $permalink;
}


/**
 * Adjust args to handle facets
 */
function ep_bp_filter_ep_formatted_args( $formatted_args ) {
	// because we changed the mapping for post_type with ep_bp_filter_ep_config_mapping(), change query accordingly
	foreach ( $formatted_args['post_filter']['bool']['must'] as &$must ) {
		// maybe term, maybe terms - depends on whether or not the value of "post_type.raw" is an array. need to handle both.
		foreach ( [ 'term', 'terms' ] as $key ) {
			if ( isset( $must[ $key ]['post_type.raw'] ) ) {
				$must[ $key ]['post_type'] = $must[ $key ]['post_type.raw'];
				unset( $must[ $key ]['post_type.raw'] );

				// re-index 'must' array keys using array_values (non-sequential keys pose problems for elasticpress)
				if ( is_array( $must[ $key ]['post_type'] ) ) {
					$must[ $key ]['post_type'] = array_values( $must[ $key ]['post_type'] );
				}
			}
		}
	}

	// Remove xprofile from highest priority of matched fields, so other fields have more boost.
	$existing_fields = ( isset( $formatted_args['query']['bool']['should'][0]['multi_match']['fields'] ) )
		? $formatted_args['query']['bool']['should'][0]['multi_match']['fields']
		: [];
	$formatted_args['query']['bool']['should'][0]['multi_match']['fields'] = array_values( array_diff(
		$existing_fields,
		[ 'terms.xprofile.name' ]
	) );

	// Add a match block to give extra boost to matches in post name
	$existing_query = ( isset( $formatted_args['query']['bool']['should'][0]['multi_match']['query'] ) )
		? $formatted_args['query']['bool']['should'][0]['multi_match']['query']
		: [];
	$formatted_args['query']['bool']['should'] = array_values( array_merge(
		[ [
			'multi_match' => [
				'query' => $existing_query,
				'type' => 'phrase',
				'fields' => ['post_title'],
				'boost' => 4
			]
		] ],
		$formatted_args['query']['bool']['should']
	) );

	if ( empty( $_REQUEST['s'] ) ) {
		// remove query entirely since results are incomplete otherwise
		unset( $formatted_args['query'] );

		// "relevancy" has no significance without a search query as context, just sort by most recent
		$formatted_args['sort'] = [ [
			'post_date' => [ 'order' => 'desc' ]
		] ];
	}

	return $formatted_args;
}

/**
 * Translate args to ElasticPress compat format.
 *
 * @param WP_Query $query
 */
function ep_bp_translate_args( $query ) {
	/**
	 * Make sure this is an ElasticPress search query
	 */
	$indexables = ElasticPress\Indexables::factory()->get_all();
	if ( empty( $indexables ) ) {
		return;
	}
	if ( ! $indexables[0]->elasticpress_enabled( $query ) || ! $query->is_search() ) {
		return;
	}

	$fallback_post_types = apply_filters( 'ep_bp_fallback_post_type_facet_selection', [
		EP_BP_API::GROUP_TYPE_NAME,
		EP_BP_API::MEMBER_TYPE_NAME,
		'topic',
		'reply',
		'post',
		'page',
	] );

	if ( ! isset( $_REQUEST['post_type'] ) || empty( $_REQUEST['post_type'] ) ) {
		$_REQUEST['post_type'] = $fallback_post_types;
	}

	$query->set( 'post_type', $_REQUEST['post_type'] );

	if ( ! isset( $_REQUEST['index'] ) ) {
		// TODO find a way to avoid removing & adding this filter again
		remove_filter( 'ep_index_name', 'ep_bp_filter_ep_index_name', 10, 2 );
		$_REQUEST['index'] = [ ep_get_index_name() ];
		add_filter( 'ep_index_name', 'ep_bp_filter_ep_index_name', 10, 2 );
	}

	if ( isset( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['orderby'] ) ) {
		$query->set( 'orderby', $_REQUEST['orderby'] );
	}

	if ( isset( $_REQUEST['paged'] ) && ! empty( $_REQUEST['paged'] ) ) {
		$query->set( 'paged', $_REQUEST['paged'] );
	}

	// search xprofile field values
	$query->set( 'search_fields', array_unique( array_merge_recursive(
		(array) $query->get( 'search_fields' ),
		[ 'taxonomies' => [ 'xprofile' ] ]
	), SORT_REGULAR ) );
}

/**
 * Add user sites to search when their network index is selected.
 *
 * To have a site's content included in the search results, the ElasticPress
 * plugin needs to be enabled for that site, which will generate an index for
 * it. This function will add that index to the search when the base site's
 * index is included.
 *
 * This function determines network membership by comparing index names. For
 * example, if the base site index is hastachcommonsstagingorg-post-1002702,
 * then a user site index could be
 * humanitiesartsmediahastachcommonsstagingorg-post-1002706.
 * 'hastachcommonsstagingorg' is a substring of
 * 'humanitiesartsmediahastachcommonsstagingorg', and so the latter is a member
 * site of the former.
 *
 * @param $index_name string The index(es) to be searched, separated by commas.
 * @param $blog_id ID of the blog initiating the search.
 *
 * @return string Indexes to be searched including any user sites.
 */
function ep_bp_search_user_sites( $index_name, $blog_id ) {
	global $wp_query;

	if ( ! isset( $wp_query ) || ! is_search() ) {
		return $index_name;
	}
	
	$indexes = explode( ',', $index_name );

	$path = '_cat/indices?format=json';
	$response = Elasticsearch::factory()->remote_request( $path );

	// If something goes wrong with the request, just bail and return the original index.
	if ( is_wp_error( $response ) ) {
		return $index_name;
	}
	$cluster_indexes = json_decode( $response['body'] );

	$networks = get_networks();
	$network_base_names = [];
	foreach ( $networks as $network ) {
		$network_base_names[] = preg_replace( '/\W/', '', $network->domain );
	}

	$new_indexes = [];
	foreach ( $cluster_indexes as $cluster_index ) {
		$cluster_index_base_name = explode( '-', $cluster_index->index )[0];
		foreach ( $indexes as $existing_index ) {
			if ( $existing_index === $cluster_index->index ) {
				continue;
			}
			if ( in_array( $cluster_index_base_name, $network_base_names ) ) {
				continue;
			}
			$existing_index_base_name = explode( '-', $existing_index )[0];
			if ( strpos( $cluster_index_base_name, $existing_index_base_name ) !== false ) {
				$add_to_index = true;
				// Make sure that the index only gets added for its own network by checking
				// that no longer network url contains this index.
				foreach ( $network_base_names as $network_index_name ) {
					if ( strlen( $network_index_name ) <= strlen( $existing_index_base_name ) ) {
						continue;
					}
					if ( strpos( $cluster_index_base_name, $network_index_name ) !== false ) {
						$add_to_index = false;
						break;
					}
				}
				if ( $add_to_index ) {
					$new_indexes[] = $cluster_index->index;
				}
				break;
			}
		}
	}

	$indexes = array_merge( $indexes, $new_indexes );
	return implode( ',', $indexes );
}
// Fire after ep_bp_filter_ep_index_name.
add_filter( 'ep_index_name', 'ep_bp_search_user_sites', 20, 2 );

/**
 * Index BP-related post types
 *
 * @param  array $post_types Existing post types.
 * @return array
 */
function ep_bp_post_types( $post_types = [] ) {
	return array_unique( array_merge( $post_types, [
		bbp_get_topic_post_type() => bbp_get_topic_post_type(),
		bbp_get_reply_post_type() => bbp_get_reply_post_type(),
	] ) );
}

/**
 * Index BP taxonomies
 *
 * @param   array $taxonomies Index taxonomies array.
 * @param   array $post Post properties array.
 * @return  array
 */
function ep_bp_whitelist_taxonomies( $taxonomies ) {
	return array_merge( $taxonomies, [
		get_taxonomy( bp_get_member_type_tax_name() ),
		get_taxonomy( 'bp_group_type' ),
	] );
}

/**
 * inject "post" type into search result titles
 * TODO make configurable via ep feature settings api
 */
function ep_bp_filter_result_titles( $title ) {
	global $post;

	// if we're filtering the_title_attribute() rather than the_title(), bail
	foreach ( debug_backtrace() as $bt ) {
		if ( isset( $bt['function'] ) && 'the_title_attribute' === $bt['function'] ) {
			return $title;
		}
	}

	switch ( $post->post_type ) {
		case EP_BP_API::GROUP_TYPE_NAME:
			$name = EP_BP_API::GROUP_TYPE_NAME;
			$label = 'Group';
			break;
		case EP_BP_API::MEMBER_TYPE_NAME:
			$name = EP_BP_API::MEMBER_TYPE_NAME;
			$label = 'Member';
			break;
		default:
			$post_type_object = get_post_type_object( $post->post_type );
			$name = $post_type_object->name;
			$label = $post_type_object->labels->singular_name;
			break;
	}

	$tag = sprintf( '<span class="post_type %1$s">%2$s</span>',
		$name,
		$label
	);

	if ( strpos( $title, $tag ) !== 0 ) {
		$title = $tag . str_replace( $tag, '', $title );
	}

	return $title;
}

/**
 * Change author links to point to profiles rather than /author/username
 */
function ep_bp_filter_result_author_link( $link ) {
	$link = str_replace( '/author/', '/members/', $link );
	return $link;
}

/**
 * filter out private bbpress content this way instead of a meta_query since that also excludes some non-replies.
 * this takes the place of bbp_pre_get_posts_normalize_forum_visibility()
 */
function ep_bp_filter_ep_post_sync_kill( $kill, $post_args, $post_id ) {
	$meta = get_post_meta( $post_id );
	if ( isset( $meta['_bbp_forum_id'] ) && array_intersect( $meta['_bbp_forum_id'], bbp_exclude_forum_ids( 'array' ) ) ) {
		$kill = true;
	}
	return $kill;
}

/**
 * Unless we change post_type from text to keyword, searches for some of our buddypress fake "post" types return no results.
 */
function ep_bp_filter_ep_config_mapping( $mapping ) {
	$mapping['mappings']['post']['properties']['post_type'] = [
		'type' => 'keyword',
	];
	return $mapping;
}

/**
 * Elasticpress doesn't turn on integration if the search query is empty.
 * We consider that a valid use case to return all results (according to filters) so enable it anyway.
 */
function ep_bp_filter_ep_elasticpress_enabled( $enabled, $query ) {
	if ( method_exists( $query, 'is_search' ) && $query->is_search() && isset( $_REQUEST['s'] ) ) {
		$enabled = true;
	}
	return $enabled;
}
