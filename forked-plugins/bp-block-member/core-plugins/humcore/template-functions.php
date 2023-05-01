<?php
/**
 * Template functions and deposits search results class.
 *
 * @package HumCORE
 * @subpackage Deposits
 */

/**
 * Set the ajax query string for deposits.
 */
function humcore_ajax_querystring_filter( $query ) {

	if ( ! empty( $_POST['action'] ) ) {
		if ( 'deposits_filter' == $_POST['action'] ) {

			$search_params = array();
			$scope_cookie  = $_COOKIE['bp-deposits-scope'];
			if ( ! empty( $scope_cookie ) and 'society' === $scope_cookie ) {
				$search_params[] = 'facets[society_facet][]=' . humcore_get_current_society_id();
			}

			$search_field_cookie = $_COOKIE['bp-deposits-field'];
			if ( ! empty( $search_field_cookie ) and 'all' !== $search_field_cookie ) {
				$search_field = $search_field_cookie;
			} else {
				$search_field = 's';
			}
			if ( false != $_POST['search_terms'] && ! empty( $_POST['search_terms'] ) ) {
				$search_params[] = $search_field . '=' . $_POST['search_terms'];
			}
			if ( ! empty( $_POST['extras'] ) && 'undefined' !== $_POST['extras'] ) {
				$search_params[] = $_POST['extras'];
			}
			if ( ! empty( $_POST['page'] ) ) {
				$search_params[] = 'page' . '=' . $_POST['page'];
			}
			if ( ! empty( $_POST['filter'] ) && 'undefined' !== $_POST['filter'] ) {
				$search_params[] = 'sort' . '=' . $_POST['filter'];
			}

			if ( ! empty( $search_params ) ) {
				$query = '?' . implode( $search_params, '&' );
			} else {
				$query = '';
			}
		}
	}

	return $query;

}
add_filter( 'bp_ajax_querystring', 'humcore_ajax_querystring_filter', 999 );

/**
 * Return solr results when called from an ajax call.
 */
function humcore_ajax_return_solr_results() {

	ob_start();
	if ( bp_is_user() ) {
		bp_locate_template( array( 'deposits/user-deposits-loop.php' ), true );
	} elseif ( bp_is_group() ) {
		bp_locate_template( array( 'deposits/group-deposits-loop.php' ), true );
	} else {
		bp_locate_template( array( 'deposits/deposits-loop.php' ), true );
	}

	$results = ob_get_contents();
	ob_end_clean();
	echo $results; // XSS OK.
	exit();

}
add_action( 'wp_ajax_nopriv_deposits_filter', 'humcore_ajax_return_solr_results' );
add_action( 'wp_ajax_deposits_filter', 'humcore_ajax_return_solr_results' );

/**
 * Return solr results when called from an ajax call.
 */
function humcore_before_has_deposits_parse_args( $retval ) {

	if ( ! empty( $retval['?tag'] ) ) {
		$retval['search_tag'] = $retval['?tag'];
	} elseif ( ! empty( $retval['?title'] ) ) {
		$retval['search_title'] = $retval['?title'];
	} elseif ( ! empty( $retval['?subject'] ) ) {
		$retval['search_subject'] = $retval['?subject'];
	} elseif ( ! empty( $retval['?author'] ) ) {
		$retval['search_author'] = $retval['?author'];
	} elseif ( ! empty( $retval['?username'] ) ) {
		$retval['search_username'] = $retval['?username'];
	} elseif ( ! empty( $retval['?facets'] ) ) {
		$retval['search_facets'] = $retval['?facets'];
	} elseif ( ! empty( $retval['?s'] ) ) {
		$retval['search_terms'] = $retval['?s'];
	} elseif ( ! empty( $retval['?page'] ) ) {
		$retval['page'] = $retval['?page'];
	} elseif ( ! empty( $retval['?sort'] ) ) {
		$retval['sort'] = $retval['?sort'];
	}
	return $retval;
}
add_action( 'bp_after_has_deposits_parse_args', 'humcore_before_has_deposits_parse_args' );

/**
 * Initialize the deposits loop.
 *
 * @param array $args
 *
 * @return bool Returns true when deposits are found, otherwise false.
 */
function humcore_has_deposits( $args = '' ) {

	global $deposits_results;
		// Note: any params used for filtering can be a single value, or multiple values comma separated.
	$defaults = array(
		'page_arg'           => 'page',
		'sort'               => 'newest',     // Sort date, author or title.
		'page'               => 1,            // Which page to load.
		'per_page'           => 25,           // Number of items per page.
		'max'                => false,        // Max number to return.
		'include'            => false,        // Specify pid to get.
		'search_by'          => false,        // Specify field to search
		'search_tag'         => false,        // Specify tag to search for (keyword_search field).
		'search_subject'     => false,        // Specify subject to search for (subject_search field).
		'search_author'      => false,        // Specify author to search for (author_search field).
		'search_username'    => false,        // Specify username to search for (author_uni field).
		'search_terms'       => false,        // Specify terms to search on.
		'search_title'       => false,        // Specify title to search for an widlcard match (title_search field).
		'search_title_exact' => false,        // Specify title to search for an exact match (title_search field).
		'search_facets'      => false,        // Specify facets to filter search on.
	);

	$params = bp_parse_args( $args, $defaults, 'has_deposits' );

	if ( empty( $params['search_tag'] ) && ! empty( $params['tag'] ) ) {
		$params['search_tag'] = $params['tag'];
	}

	if ( empty( $params['search_tag'] ) && ! empty( $_REQUEST['tag'] ) ) {
		$params['search_tag'] = $_REQUEST['tag'];
	}

	if ( empty( $params['search_subject'] ) && ! empty( $params['subject'] ) ) {
		$params['search_subject'] = $params['subject'];
	}

	if ( empty( $params['search_subject'] ) && ! empty( $_REQUEST['subject'] ) ) {
		$params['search_subject'] = $_REQUEST['subject'];
	}

	if ( empty( $params['search_author'] ) && ! empty( $params['author'] ) ) {
		$params['search_author'] = $params['author'];
	}

	if ( empty( $params['search_author'] ) && ! empty( $_REQUEST['author'] ) ) {
		$params['search_author'] = $_REQUEST['author'];
	}

	if ( empty( $params['search_username'] ) && ! empty( $params['username'] ) ) {
		$params['search_username'] = $params['username'];
	}

	if ( empty( $params['search_username'] ) && ! empty( $_REQUEST['username'] ) ) {
		$params['search_username'] = $_REQUEST['username'];
	}

	if ( empty( $params['search_terms'] ) && ! empty( $params['s'] ) ) {
		$params['search_terms'] = $params['s'];
	}

	if ( empty( $params['search_terms'] ) && ! empty( $_REQUEST['s'] ) ) {
		$params['search_terms'] = $_REQUEST['s'];
	}

	// TODO figure out how to remove this hack (copy date_issued to text in solr?).
	$params['search_terms'] = preg_replace( '/^(\d{4})$/', 'date_issued:$1', $params['search_terms'] );

	if ( empty( $params['search_title'] ) && ! empty( $params['title'] ) ) {
		$params['search_title'] = $params['title'];
	}

	if ( empty( $params['search_title'] ) && ! empty( $_REQUEST['title'] ) ) {
		$params['search_title'] = $_REQUEST['title'];
	}

	if ( empty( $params['search_title_exact'] ) && ! empty( $params['title_exact'] ) ) {
		$params['search_title_exact'] = $params['title_exact'];
	}

	if ( empty( $params['search_title_exact'] ) && ! empty( $_REQUEST['title_exact'] ) ) {
		$params['search_title_exact'] = $_REQUEST['title_exact'];
	}

	$scope_cookie = $_COOKIE['bp-deposits-scope'];
	if ( ! empty( $scope_cookie ) and 'society' === $scope_cookie ) {
		$params['facets']['society_facet'][] = humcore_get_current_society_id();
	}

	// TODO rework the logic for the other search fields
	if ( ! empty( $params['search_facets'] ) ) {
		$params['search_facets'] = array_merge( $params['search_facets'], (array) $params['facets'] );
	} else {
		$params['search_facets'] = $params['facets'];
	}
	if ( ! empty( $_REQUEST['facets'] ) ) {
		$params['search_facets'] = array_merge( (array) $params['search_facets'], $_REQUEST['facets'] );
	}

	if ( ! empty( $_REQUEST['sort'] ) ) {
		$params['sort'] = esc_attr( $_REQUEST['sort'] );
	}

	// Do not exceed the maximum per page.
	if ( ! empty( $params['max'] ) && ( (int) $params['per_page'] > (int) $params['max'] ) ) {
		$params['per_page'] = $params['max'];
	}

	$search_args = array(
		'page'               => $params['page'],
		'per_page'           => $params['per_page'],
		'page_arg'           => $params['page_arg'],
		'max'                => $params['max'],
		'sort'               => $params['sort'],
		'include'            => $params['include'],
		'search_tag'         => $params['search_tag'],
		'search_subject'     => $params['search_subject'],
		'search_author'      => $params['search_author'],
		'search_username'    => $params['search_username'],
		'search_terms'       => $params['search_terms'],
		'search_title'       => $params['search_title'],
		'search_title_exact' => $params['search_title_exact'],
		'search_facets'      => $params['search_facets'],
	);

	$deposits_results = new Humcore_Deposit_Search_Results( $search_args );

	return apply_filters( 'humcore_has_deposits', $deposits_results->has_deposits(), $deposits_results, $search_args );

}

/**
 * Determine if there are still deposits left in the loop.
 *
 * @return bool Returns true when deposits are found.
 */
function humcore_deposits() {
	global $deposits_results;

	return $deposits_results->deposits();
}

/**
 * Get the current deposit object in the loop.
 *
 * @return object The current deposit within the loop.
 */
function humcore_the_deposit() {
	global $deposits_results;

	return $deposits_results->the_deposit();
}

/**
 * Return the curret deposit object.
 *
 * @return object The current deposit object.
 */
function humcore_get_current_deposit() {
	global $deposits_results;

	return apply_filters( 'humcore_get_current_deposit', $deposits_results->deposit );
}

/**
 * Output the deposit count.
 *
 * @uses humcore_get_deposit_count()
 */
function humcore_deposit_count() {
	echo humcore_get_deposit_count(); // XSS OK.
}

/**
 * Return the deposit count.
 *
 * @global object $deposits_results {@link Humcore_Deposit_Search_Results}
 * @uses apply_filters() To call the 'humcore_get_deposit_count' hook.
 *
 * @return int The deposit count.
 */
function humcore_get_deposit_count() {
	global $deposits_results;

	return apply_filters( 'humcore_get_deposit_count', (int) $deposits_results->total_deposit_count );
}

/**
 * Output the deposit id.
 *
 * @uses humcore_get_deposit_id()
 */
function humcore_deposit_id() {
	echo humcore_get_deposit_id(); // XSS OK.
}

/**
 * Return the deposit id.
 *
 * @global object $deposits_results {@link Humcore_Deposit_Search_Results}
 * @uses apply_filters() To call the 'humcore_get_deposit_id' hook.
 *
 * @return The deposit id.
 */
function humcore_get_deposit_id() {
	global $deposits_results;

	return apply_filters( 'humcore_get_deposit_id', $deposits_results->deposit->id );
}

/**
 * Return the facet counts.
 *
 * @global object $deposits_results {@link Humcore_Deposit_Search_Results}
 * @uses apply_filters() To call the 'humcore_get_facet_counts' hook.
 *
 * @return array Facets and counts.
 */
function humcore_get_facet_counts() {
	global $deposits_results;

	return apply_filters( 'humcore_get_facet_counts', (array) $deposits_results->facet_counts );
}

/**
 * Return the facet titles.
 *
 * @uses apply_filters() To call the 'humcore_get_facet_titles' hook.
 *
 * @return array Facets and titles.
 */
function humcore_get_facet_titles() {
	$facet_titles = array(
		'author_facet'           => __( 'Author', 'humcore_domain' ),
		'group_facet'            => __( 'Group', 'humcore_domain' ),
		'subject_facet'          => __( 'Subject', 'humcore_domain' ),
		'genre_facet'            => __( 'Item Type', 'humcore_domain' ),
		'pub_date_facet'         => __( 'Date', 'humcore_domain' ),
		'type_of_resource_facet' => __( 'File Type', 'humcore_domain' ),
	);

	return apply_filters( 'humcore_get_facet_titles', $facet_titles );
}

/**
 * Output the deposit pagination count.
 */
function humcore_deposit_pagination_count() {
		echo humcore_get_deposit_pagination_count(); // XSS OK.
}

/**
 * Return the deposit pagination count.
 *
 * @global object $deposits_results {@link Humcore_Deposit_Search_Results}
 * @uses bp_core_number_format()
 *
 * @return string The pagination text.
 */
function humcore_get_deposit_pagination_count() {
	global $deposits_results;

	$start_num = intval( ( $deposits_results->pag_page - 1 ) * $deposits_results->pag_num ) + 1;
	$from_num  = bp_core_number_format( $start_num );
	$to_num    = bp_core_number_format(
		( $start_num + ( $deposits_results->pag_num - 1 ) > $deposits_results->total_deposit_count )
		? $deposits_results->total_deposit_count
		: $start_num + ( $deposits_results->pag_num - 1 )
	);
	$total     = bp_core_number_format( $deposits_results->total_deposit_count );

	return sprintf( _n( 'Viewing item %1$s to %2$s (of %3$s items)', 'Viewing item %1$s to %2$s (of %3$s items)', $total, 'humcore_domain' ), $from_num, $to_num, $total );
}

/**
 * Output the deposit pagination links.
 *
 * @uses humcore_get_deposit_pagination_links()
 */
function humcore_deposit_pagination_links() {
	echo humcore_get_deposit_pagination_links(); // XSS OK.
}

/**
 * Return the deposit pagination links.
 *
 * @global object $deposits_results {@link Humcore_Deposit_Search_Results}
 * @uses apply_filters() To call the 'humcore_get_deposit_pagination_links' hook.
 *
 * @return string The pagination links.
 */
function humcore_get_deposit_pagination_links() {
	global $deposits_results;

	return apply_filters( 'humcore_get_deposit_pagination_links', $deposits_results->pag_links );
}

/**
 * Return the deposit page number.
 *
 * @global object $deposits_results {@link Humcore_Deposit_Search_Results}
 * @uses apply_filters() To call the 'humcore_get_deposit_page_number' hook.
 *
 * @return string The page number.
 */
function humcore_get_deposit_page_number() {
		global $deposits_results;

		return apply_filters( 'humcore_get_deposit_page_number', $deposits_results->pag_page );
}

/**
 * Return true when there are more deposit items to be shown than currently appear.
 *
 * @global object $deposits_results {@link Humcore_Deposit_Search_Results}
 * @uses apply_filters() To call the 'humcore_deposit_has_more_items' hook.
 *
 * @return bool $has_more_items True if more items, false if not.
 */
function humcore_deposit_has_more_items() {
	global $deposits_results;

	$remaining_pages = 0;

	if ( ! empty( $deposits_results->pag_page ) ) {
		$remaining_pages = floor( ( $deposits_results->total_deposit_count - 1 ) / ( $deposits_results->pag_num * $deposits_results->pag_page ) );
	}

	$has_more_items = (int) $remaining_pages ? true : false;

	return apply_filters( 'humcore_deposit_has_more_items', $has_more_items );
}

/**
 * Return the deposit post id.
 *
 * @global object $deposits_results {@link Humcore_Deposit_Search_Results}
 * @uses apply_filters() To call the 'humcore_get_deposit_record_identifier' hook.
 *
 * @return The deposit record identifier ( post_id ).
 */
function humcore_get_deposit_record_identifier() {
	global $deposits_results;

	return apply_filters( 'humcore_get_deposit_record_identifier', $deposits_results->deposit->record_identifier );
}

/**
 * Return the deposit activity id.
 *
 * @uses apply_filters() To call the 'humcore_get_deposit_activity_id' hook.
 *
 * @return The deposit id.
 */
function humcore_get_deposit_activity_id() {
	global $bp;

		$wpmn_record_identifier = array();
	$deposit_id                 = humcore_get_deposit_record_identifier();
		$wpmn_record_identifier = explode( '-', $deposit_id );
		// handle legacy MLA value
	if ( $wpmn_record_identifier[0] === $deposit_id ) {
			$wpmn_record_identifier[0] = '1';
			$wpmn_record_identifier[1] = $deposit_id;
	}

	$activity_id = bp_activity_get_activity_id(
		array(
			'type'              => 'new_deposit',
			'component'         => $bp->humcore_deposits->id,
			'secondary_item_id' => $wpmn_record_identifier[1],
		)
	);

	return apply_filters( 'humcore_get_deposit_activity_id', $activity_id );
}

/**
 * Return true when the deposit activity is a favorite of the current user.
 *
 * @global object $activities_template {@link BP_Activity_Template}
 * @uses apply_filters() To call the 'humcore_deposit_activity_is_favorite' hook.
 *
 * @return bool $is_favorite True if favorite, false if not.
 */
function humcore_deposit_activity_is_favorite( $activity_id ) {
	// TODO activity component must be active.
	$user_favs = bp_activity_get_user_favorites( bp_loggedin_user_id() );

	return apply_filters( 'humcore_deposit_activity_is_favorite', in_array( $activity_id, (array) $user_favs ) );
}

/**
 * Output the deposit activity favorite link.
 *
 * @uses humcore_get_deposit_activity_favorite_link()
 */
function humcore_deposit_activity_favorite_link( $activity_id ) {
	echo humcore_get_deposit_activity_favorite_link( $activity_id ); // XSS OK.
}

/**
 * Return the deposit activity favorite link.
 *
 * @global object $activities_template {@link BP_Activity_Template}
 * @uses wp_nonce_url()
 * @uses home_url()
 * @uses bp_get_activity_root_slug()
 * @uses apply_filters() To call the 'humcore_get_deposit_activity_favorite_link' hook
 *
 * @return string The activity favorite link.
 */
function humcore_get_deposit_activity_favorite_link( $activity_id ) {
	global $activities_template;
	return apply_filters( 'humcore_get_deposit_activity_favorite_link', wp_nonce_url( home_url( bp_get_activity_root_slug() . '/favorite/' . $activity_id . '/' ), 'mark_favorite' ) );
}

/**
 * Output the deposit activity unfavorite link.
 *
 * @uses humcore_get_deposit_activity_unfavorite_link()
 */
function humcore_deposit_activity_unfavorite_link( $activity_id ) {
	echo humcore_get_deposit_activity_unfavorite_link( $activity_id ); // XSS OK.
}

/**
 * Return the deposit activity unfavorite link.
 *
 * @global object $activities_template {@link BP_Activity_Template}
 * @uses wp_nonce_url()
 * @uses home_url()
 * @uses bp_get_activity_root_slug()
 * @uses apply_filters() To call the 'humcore_get_deposit_activity_unfavorite_link' hook.
 *
 * @return string The activity unfavorite link.
 */
function humcore_get_deposit_activity_unfavorite_link( $activity_id ) {
	global $activities_template;
	return apply_filters( 'humcore_get_deposit_activity_unfavorite_link', wp_nonce_url( home_url( bp_get_activity_root_slug() . '/unfavorite/' . $activity_id . '/' ), 'unmark_favorite' ) );
}
