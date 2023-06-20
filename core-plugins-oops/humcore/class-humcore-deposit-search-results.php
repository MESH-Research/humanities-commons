<?php
/**
 * Template functions and deposits search results class.
 *
 * @package HumCORE
 * @subpackage Deposits
 */

/**
 * The main deposit search results loop class.
 *
 * This is responsible for loading a group of deposit items and displaying them.
 */
class Humcore_Deposit_Search_Results {

	var $current_deposit = -1;
	var $deposit_count;
	var $total_deposit_count;
	var $facet_counts = '';

	var $deposits;
	var $deposit;

	var $in_the_loop;

	var $pag_page;
	var $pag_num;
	var $pag_links;

	/**
	 * Constructor method.
	 *
	 * @param array $args Array of arguments.
	 */
	function __construct( $args ) {

		$defaults                   = array(
			'page'               => 1,
			'per_page'           => 25,
			'page_arg'           => 'page',
			'max'                => false,
			'sort'               => 'newest',
			'include'            => false,
			'search_tag'         => '',
			'search_subject'     => '',
			'search_author'      => '',
			'search_username'    => '',
			'search_terms'       => '',
			'search_title'       => '',
			'search_title_exact' => '',
			'search_facets'      => '',
		);
		$r                          = wp_parse_args( $args, $defaults );
		$page                       = $r['page'];
		$per_page                   = $r['per_page'];
		$page_arg                   = $r['page_arg'];
		$max                        = $r['max'];
		$sort                       = $r['sort'];
		$include                    = $r['include'];
		$lucene_reserved_characters = preg_quote( '+-&|!(){}[]^"~*?:\\' );

		$search_tag = preg_replace_callback(
			'/([' . $lucene_reserved_characters . '])/',
			function( $matches ) {
				return '\\' . $matches[0];
			},
			trim( $r['search_tag'], '"' )
		);

		$search_tag = str_replace( ' ', '\ ', $search_tag );
		if ( false !== strpos( $search_tag, ' ' ) ) {
			$search_tag = '"' . $search_tag . '"';
		}

		if ( ! empty( $search_tag ) ) {
			$search_tag = 'keyword_search:' . $search_tag;
		}

		$search_subject = preg_replace_callback(
			'/([' . $lucene_reserved_characters . '])/',
			function( $matches ) {
				return '\\' . $matches[0];
			},
			trim( $r['search_subject'], '"' )
		);

		$search_subject = str_replace( ' ', '\ ', $search_subject );
		if ( false !== strpos( $search_subject, ' ' ) ) {
			$search_subject = '"' . $search_subject . '"';
		}

		if ( ! empty( $search_subject ) ) {
			$search_subject = 'subject_search:' . $search_subject;
		}

		$search_author = preg_replace_callback(
			'/([' . $lucene_reserved_characters . '])/',
			function( $matches ) {
				return '\\' . $matches[0];
			},
			trim( $r['search_author'], '"' )
		);

		$search_author = str_replace( ' ', '\ ', $search_author );
		if ( false !== strpos( $search_author, ' ' ) ) {
			$search_author = '"' . $search_author . '"';
		}

		if ( ! empty( $search_author ) ) {
			$search_author = 'author_search:' . $search_author;
		}

		$search_username = preg_replace_callback(
			'/([' . $lucene_reserved_characters . '])/',
			function( $matches ) {
				return '\\' . $matches[0];
			},
			trim( $r['search_username'], '"' )
		);

		$search_username = str_replace( ' ', '\ ', $search_username );
		if ( false !== strpos( $search_username, ' ' ) ) {
			$search_username = '"' . $search_username . '"';
		}

		if ( ! empty( $search_username ) ) {
			$search_username = 'author_uni:' . $search_username;
		}

		$search_terms = preg_replace_callback(
			'/([' . $lucene_reserved_characters . '])/',
			function( $matches ) {
				return '\\' . $matches[0];
			},
			trim( $r['search_terms'], '"' )
		);

		$search_terms = str_replace( ' ', '\ ', $search_terms );
		if ( false !== strpos( $search_terms, ' ' ) ) {
			$search_terms = '"' . $search_terms . '"';
		}

		$search_title = preg_replace_callback(
			'/([' . $lucene_reserved_characters . '])/',
			function( $matches ) {
				return '\\' . $matches[0];
			},
			trim( $r['search_title'], '"' )
		);

		$search_title = str_replace( ' ', '\ ', $search_title );
		if ( false !== strpos( $search_title, ' ' ) ) {
			$search_title = '"' . $search_title . '"';
		}

		if ( ! empty( $search_title ) ) {
			$search_title = 'title_search:' . $search_title;
		}

		$search_title_exact = preg_replace_callback(
			'/([' . $lucene_reserved_characters . '])/',
			function( $matches ) {
				return '\\' . $matches[0];
			},
			trim( $r['search_title_exact'], '"' )
		);

		$search_title_exact = str_replace( ' ', '\ ', $search_title_exact );
		if ( false !== strpos( $search_title_exact, ' ' ) ) {
			$search_title_exact = '"' . $search_title_exact . '"';
		}

		if ( ! empty( $search_title_exact ) ) {
			$search_title_exact = 'title_display:' . $search_title_exact;
		}

		$search_facets = $r['search_facets'];

		$this->pag_page = isset( $_REQUEST[ $page_arg ] ) ? intval( $_REQUEST[ $page_arg ] ) : $page;
		$this->pag_num  = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;

		global $fedora_api, $solr_client;

		// Hardcode two collections during HC beta period, if we don't override via configuration.
		if ( 'hccollection:1' !== $fedora_api->collection_pid ) {
			$query_collection = 'member_of:' . str_replace( ':', '\:', $fedora_api->collection_pid );
		} else {
			$query_collection = '( member_of:' . str_replace( ':', '\:', 'hccollection:1' ) .
				' OR member_of:' . str_replace( ':', '\:', 'mlacollection:1' ) . ' )';
		}

		if ( ! empty( $search_tag ) ) {
			$restricted_search_terms = implode( ' AND ', array( $query_collection, $search_tag ) );
		} elseif ( ! empty( $search_subject ) ) {
			$restricted_search_terms = implode( ' AND ', array( $query_collection, $search_subject ) );
		} elseif ( ! empty( $search_author ) ) {
			$restricted_search_terms = implode( ' AND ', array( $query_collection, $search_author ) );
		} elseif ( ! empty( $search_username ) ) {
			$restricted_search_terms = implode( ' AND ', array( $query_collection, $search_username ) );
		} elseif ( ! empty( $search_terms ) ) {
			$restricted_search_terms = implode( ' AND ', array( $query_collection, $search_terms ) );
		} elseif ( ! empty( $search_title ) ) {
			$restricted_search_terms = implode( ' AND ', array( $query_collection, $search_title ) );
		} elseif ( ! empty( $search_title_exact ) ) {
			$restricted_search_terms = implode( ' AND ', array( $query_collection, $search_title_exact ) );
		} else {
			$restricted_search_terms = $query_collection;
		}

		if ( ! $include ) {

			$cache_key = http_build_query( array( $restricted_search_terms, $search_facets, $this->pag_page, $sort, $this->pag_num ) );
			// Check cache for search results.
			$results = wp_cache_get( $cache_key, 'humcore_solr_search_results' );
			if ( false === $results ) {
				try {
					$results = $solr_client->get_search_results( $restricted_search_terms, $search_facets, $this->pag_page, $sort, $this->pag_num );
					if ( false === strpos( $cache_key, 'author_uni' ) ) {
						$cache_ttl = 60;
					} else {
						$cache_ttl = 0;
					}
					$cache_status = wp_cache_set( $cache_key, $results, 'humcore_solr_search_results', $cache_ttl );
					//humcore_write_error_log('info','*****cache set sea*****'.var_export($cache_key,true));
				} catch ( Exception $e ) {
					$this->total_deposit_count = 0;
					$this->facet_counts        = '';
					$this->deposits            = '';
					humcore_write_error_log(
						'error',
						sprintf(
							'*****HumCORE Search***** - A Solr error occurred. %1$s - %2$s',
							$e->getCode(),
							$e->getMessage()
						)
					);

				}
			}
		} else {
			$cache_key = http_build_query( array( $include ) );
			$results   = wp_cache_get( $cache_key, 'humcore_solr_search_results' );
			if ( false === $results ) {
				try {
					$results      = $solr_client->get_humcore_document( $include );
					$cache_status = wp_cache_set( $cache_key, $results, 'humcore_solr_search_results', 0 );
					//humcore_write_error_log('info','*****cache set inc*****'.var_export($cache_key,true));
				} catch ( Exception $e ) {
					$this->total_deposit_count = 0;
					$this->facet_counts        = '';
					$this->deposits            = '';
					humcore_write_error_log(
						'error',
						sprintf(
							'*****HumCORE Search***** - A Solr error occurred. %1$s - %2$s',
							$e->getCode(),
							$e->getMessage()
						)
					);

				}
			}
		}

		if ( ! $max || $max >= (int) $results['total'] ) {
			//$this->total_deposit_count = (int) $results['total'];
			$this->total_deposit_count = isset($results['total']) ? (int) $results['total']:0;
		} else {
			$this->total_deposit_count = (int) $max;
		}

		//$this->facet_counts = $results['facets'];
		//$this->deposits     = $results['documents'];
		$this->facet_counts = isset($results['facets'])?$results['facets']:0;
		$this->deposits     = isset($results['documents'])?$results['documents']:false;

		if ( $max ) {
			if ( $max >= count( $this->deposits ) ) { // TODO count must be changed.
				$this->deposit_count = count( $this->deposits ); // TODO count must be changed.
			} else {
				$this->deposit_count = (int) $max; // TODO count must be changed.
			}
		} else {
			$this->deposit_count = count( $this->deposits ); // TODO count must be changed.
		}

		if ( (int) $this->total_deposit_count && (int) $this->pag_num ) {
			$this->pag_links = paginate_links(
				array(
					'base'      => add_query_arg( $page_arg, '%#%', '' ),
					'format'    => '',
					'total'     => ceil( (int) $this->total_deposit_count / (int) $this->pag_num ),
					'current'   => (int) $this->pag_page,
					'prev_text' => _x( '&larr;', 'Deposit pagination previous text', 'humcore_domain' ),
					'next_text' => _x( '&rarr;', 'Deposit pagination next text', 'humcore_domain' ),
					'mid_size'  => 1,
				)
			);
		}
	}

	/**
	 * Whether there are deposit items available in the loop.
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 */
	function has_deposits() {
		if ( $this->deposit_count ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Set up the next deposit item and iterate index.
	 *
	 * @return object The next deposit item to iterate over.
	 */
	function next_deposit() {
		$this->current_deposit++;
		$this->deposit = $this->deposits[ $this->current_deposit ];
		return $this->deposit;
	}

	/**
	 * Rewind the posts and reset post index.
	 */
	function rewind_deposits() {
		$this->current_deposit = -1;
		if ( $this->deposit_count > 0 ) {
			$this->deposit = $this->deposits[0];
		}
	}

	/**
	 * Whether there are deposit items left in the loop to iterate over.
	 *
	 * @return bool True if there are more deposit items to show,
	 *              otherwise false.
	 */
	function deposits() {
		if ( $this->current_deposit + 1 < $this->deposit_count ) {
			return true;
		} elseif ( $this->current_deposit + 1 == $this->deposit_count ) {
			do_action( 'deposit_loop_end' );
			// Do some cleaning up after the loop.
			$this->rewind_deposits();
		}

		$this->in_the_loop = false;
		return false;
	}

	/**
	 * Set up the current deposit item inside the loop.
	 */
	function the_deposit() {

		$this->in_the_loop = true;
		$this->deposit     = $this->next_deposit();

		if ( is_array( $this->deposit ) ) {
			$this->deposit = (object) $this->deposit;
		}

		if ( 0 == $this->current_deposit ) { // Loop has just started.
			do_action( 'deposit_loop_start' );
		}
	}

	/**
	 * Return the array of facet counts.
	 *
	 * @return array The search results facet counts.
	 */
	function the_facets() {
		return $this->facet_counts;
	}

}
