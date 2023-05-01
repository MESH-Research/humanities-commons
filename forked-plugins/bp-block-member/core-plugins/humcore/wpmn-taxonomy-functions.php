<?php
/**
 * Multi-network taxonomy functions.
 *
 * Most Multi-network taxonomy functions are wrappers for their WordPress counterparts.
 * Because users and therefore some taxonomies are on the main network in a multi-network environment, we
 * must be able switch to the proper blog before using the WP functions.
 *
 * @package Xxxx
 * @subpackage Xxxx
 * @since x.x.x
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register any taxonomies needed.
 *
 * @since x.x.x
 */
if ( ! function_exists( 'wpmn_register_taxonomies' ) ) {
	function wpmn_register_taxonomies() {

		do_action( 'wpmn_register_taxonomies' );

	}
}
//add_action( 'wpmn_register_taxonomies', 'wpmn_register_taxonomies' );

/**
 * Gets the ID of the site that we should use for taxonomy term storage.
 *
 * Defaults to the root blog ID.
 *
 * @since x.x.x
 *
 * @return int
 */
if ( ! function_exists( 'wpmn_get_taxonomy_term_site_id' ) ) {
	function wpmn_get_taxonomy_term_site_id( $taxonomy = '' ) {

		global $wpdb;
		$site_id = $wpdb->blogid;

		/**
		 * Filters the ID of the site where we should store taxonomy terms.
		 *
		 * @since x.x.x
		 *
		 * @param int    $site_id
		 * @param string $taxonomy
		 */
		return (int) apply_filters( 'wpmn_get_taxonomy_term_site_id', $site_id, $taxonomy );
	}
}

/**
 * Set taxonomy terms on an object.
 *
 * @since x.x.x
 *
 * @see wp_set_object_terms() for a full description of function and parameters.
 *
 * @param int          $object_id Object ID.
 * @param string|array $terms     Term or terms to set.
 * @param string       $taxonomy  Taxonomy name.
 * @param bool         $append    Optional. True to append terms to existing terms. Default: false.
 * @return array Array of term taxonomy IDs.
 */
if ( ! function_exists( 'wpmn_set_object_terms' ) ) {
	function wpmn_set_object_terms( $object_id, $terms, $taxonomy, $append = false ) {
		$site_id = wpmn_get_taxonomy_term_site_id( $taxonomy );

		$switched = false;
		if ( get_current_blog_id() !== $site_id ) {
			switch_to_blog( $site_id );
			wpmn_register_taxonomies();
			$switched = true;
		}

		$retval = wp_set_object_terms( $object_id, $terms, $taxonomy, $append );

		if ( $switched ) {
			restore_current_blog();
		}

		return $retval;
	}
}

/**
 * Get taxonomy terms for an object.
 *
 * @since x.x.x
 *
 * @see wp_get_object_terms() for a full description of function and parameters.
 *
 * @param int|array    $object_ids ID or IDs of objects.
 * @param string|array $taxonomies Name or names of taxonomies to match.
 * @param array        $args       See {@see wp_get_object_terms()}.
 * @return array
 */
if ( ! function_exists( 'wpmn_get_object_terms' ) ) {
	function wpmn_get_object_terms( $object_ids, $taxonomies, $args = array() ) {
		// Different taxonomies must be stored on different sites.
		$taxonomy_site_map = array();
		foreach ( (array) $taxonomies as $taxonomy ) {
			$taxonomy_site_id                         = wpmn_get_taxonomy_term_site_id( $taxonomy );
			$taxonomy_site_map[ $taxonomy_site_id ][] = $taxonomy;
		}

		$retval = array();
		foreach ( $taxonomy_site_map as $taxonomy_site_id => $site_taxonomies ) {
			$switched = false;
			if ( get_current_blog_id() !== $site_id ) {
				switch_to_blog( $taxonomy_site_id );
				wpmn_register_taxonomies();
				$switched = true;
			}

			$site_terms = wp_get_object_terms( $object_ids, $site_taxonomies, $args );
			$retval     = array_merge( $retval, $site_terms );
			//TODO Handle taxonomy error.

			if ( $switched ) {
				restore_current_blog();
			}
		}

		return $retval;
	}
}

/**
 * Remove taxonomy terms on an object.
 *
 * @since x.x.x
 *
 * @see wp_remove_object_terms() for a full description of function and parameters.
 *
 * @param int          $object_id Object ID.
 * @param string|array $terms     Term or terms to remove.
 * @param string       $taxonomy  Taxonomy name.
 * @return bool|WP_Error True on success, false or WP_Error on failure.
 */
if ( ! function_exists( 'wpmn_remove_object_terms' ) ) {
	function wpmn_remove_object_terms( $object_id, $terms, $taxonomy ) {
		$site_id = wpmn_get_taxonomy_term_site_id( $taxonomy );

		$switched = false;
		if ( get_current_blog_id() !== $site_id ) {
			switch_to_blog( $site_id );
			wpmn_register_taxonomies();
			$switched = true;
		}

		$retval = wp_remove_object_terms( $object_id, $terms, $taxonomy );

		if ( $switched ) {
			restore_current_blog();
		}

		return $retval;
	}
}

/**
 * Retrieve the terms in a given taxonomy or list of taxonomies
 *
 * @since x.x.x
 *
 * @see get_terms() for a full description of function and parameters.
 *
 * @param string|array $args Args or names of taxonomies to match.
 * @param array        $deprecated       Args the old way.
 * @return array
 */
if ( ! function_exists( 'wpmn_get_terms' ) ) {
	function wpmn_get_terms( $args = array(), $deprecated = array() ) {

		$key_intersect  = array_intersect_key( array( 'taxonomy' => null ), (array) $args );
		$is_legacy_args = $deprecated || empty( $key_intersect );
		if ( $is_legacy_args ) {
			$taxonomies = $args;
			$wp_args    = $deprecated;
		} else {
			$taxonomies = $args['taxonomy'];
			$wp_args    = $args;
			unset( $wp_args['taxonomy'] );
		}

		// Different taxonomies must be stored on different sites.
		$taxonomy_site_map = array();
		foreach ( (array) $taxonomies as $taxonomy ) {
			$taxonomy_site_id                         = wpmn_get_taxonomy_term_site_id( $taxonomy );
			$taxonomy_site_map[ $taxonomy_site_id ][] = $taxonomy;
		}

		$retval = array();
		foreach ( $taxonomy_site_map as $taxonomy_site_id => $site_taxonomies ) {
			$switched = false;
			if ( get_current_blog_id() !== $taxonomy_site_id ) {
				switch_to_blog( $taxonomy_site_id );
				wpmn_register_taxonomies();
				$switched = true;
			}

			$wp_args['taxonomy'] = $site_taxonomies;
			$site_terms          = get_terms( $wp_args );
			$retval              = array_merge( $retval, $site_terms );

			if ( $switched ) {
				restore_current_blog();
			}
		}

		return $retval;
	}
}

/**
 * Get all Term data from database by Term ID.
 *
 * @since x.x.x
 *
 * @see get_term() for a full description of function and parameters.
 *
 * @param int|WP_Term $term      Term ID or object.
 * @param string      $taxonomy  Optional. Taxonomy name.
 * @param string      $output    Optional constant. Default: OBJECT.
 * @param string      $filter    Optional. Default: raw.
 * @return array      Array of term taxonomy IDs.
 */
if ( ! function_exists( 'wpmn_get_term' ) ) {
	function wpmn_get_term( $term, $taxonomy = '', $output = OBJECT, $filter = 'raw' ) {

		$switched = false;
		if ( ! empty( $taxonomy ) ) {
			$site_id = wpmn_get_taxonomy_term_site_id( $taxonomy );

			if ( get_current_blog_id() !== $site_id ) {
				switch_to_blog( $site_id );
				wpmn_register_taxonomies();
				$switched = true;
			}
		}

		$retval = get_term( $term, $taxonomy, $output, $filter );

		if ( $switched ) {
			restore_current_blog();
		}

		return $retval;
	}
}

/**
 * Get all Term data from database by Term field and data
 *
 * @since x.x.x
 *
 * @see get_term_by() for a full description of function and parameters.
 *
 * @param string        $field     Either 'slug', 'name', 'id' (term_id), or 'term_taxonomy_id'.
 * @param string        $value     Search for this term value.
 * @param string        $taxonomy  Optional. Taxonomy name.
 * @param string        $output    Optional constant. Default: OBJECT.
 * @param string        $filter    Optional. Default: raw.
 * @return WP_Term|bool WP_Term instance on success.
 */
if ( ! function_exists( 'wpmn_get_term_by' ) ) {
	function wpmn_get_term_by( $field, $value, $taxonomy = '', $output = OBJECT, $filter = 'raw' ) {

		$switched = false;
		if ( ! empty( $taxonomy ) ) {
				$site_id = wpmn_get_taxonomy_term_site_id( $taxonomy );

			if ( get_current_blog_id() !== $site_id ) {
				switch_to_blog( $site_id );
				wpmn_register_taxonomies();
				$switched = true;
			}
		}

		$retval = get_term_by( $field, $value, $taxonomy, $output, $filter );

		if ( $switched ) {
				restore_current_blog();
		}

		return $retval;
	}
}

/**
 * Get objects in term and taxonomy.
 *
 * @since x.x.x
 *
 * @see get_objects_in_term() for a full description of function and parameters.
 *
 * @param int|array    $term_ids ID or IDs of terms.
 * @param string|array $taxonomies Name or names of taxonomies to match.
 * @param array        $args       See {@see get_objects_in_term()}.
 * @return array
 */
if ( ! function_exists( 'wpmn_get_objects_in_term' ) ) {
	function wpmn_get_objects_in_term( $term_ids, $taxonomies, $args = array() ) {
		// Different taxonomies may be stored on different sites.
		$taxonomy_site_map = array();
		foreach ( (array) $taxonomies as $taxonomy ) {
			$taxonomy_site_id                         = wpmn_get_taxonomy_term_site_id( $taxonomy );
			$taxonomy_site_map[ $taxonomy_site_id ][] = $taxonomy;
		}

		$retval = array();
		foreach ( $taxonomy_site_map as $taxonomy_site_id => $site_taxonomies ) {
			$switched = false;
			if ( get_current_blog_id() !== $taxonomy_site_id ) {
				switch_to_blog( $taxonomy_site_id );
				wpmn_register_taxonomies();
				$switched = true;
			}

			$site_terms = get_objects_in_term( $term_ids, $site_taxonomies, $args );
			$retval     = array_merge( $retval, $site_terms );
			//TODO Handle taxonomy error.

			if ( $switched ) {
				restore_current_blog();
			}
		}

		return $retval;
	}
}

/**
 * Check if Term exists.
 *
 * @since x.x.x
 *
 * @see term_exists() for a full description of function and parameters.
 *
 * @param int|string  $term      Term ID or object.
 * @param string      $taxonomy  Optional. Taxonomy name.
 * @param int         $parent    Optional int. ID of parent term. Default: null.
 * @return mixed      Returns null, term ID or array of term ID and taxonomy.
 */
if ( ! function_exists( 'wpmn_term_exists' ) ) {
	function wpmn_term_exists( $term, $taxonomy = '', $parent = '' ) {

		$switched = false;
		if ( ! empty( $taxonomy ) ) {
			$site_id = wpmn_get_taxonomy_term_site_id( $taxonomy );

			if ( get_current_blog_id() !== $site_id ) {
				switch_to_blog( $site_id );
				wpmn_register_taxonomies();
				$switched = true;
			}
		}

		$retval = term_exists( $term, $taxonomy, $parent );

		if ( $switched ) {
			restore_current_blog();
		}

		return $retval;

	}
}

/**
 * Add a new term to the database.
 *
 * @since x.x.x
 *
 * @see wp_insert_term() for a full description of function and parameters.
 *
 * @param string       $term     Term to add.
 * @param string       $taxonomy Taxonomy name.
 * @param array        $args     Additional arguments.
 * @return array|WP_Error Array on success, WP_Error on failure.
 */
if ( ! function_exists( 'wpmn_insert_term' ) ) {
	function wpmn_insert_term( $term, $taxonomy, $args = array() ) {
		$site_id = wpmn_get_taxonomy_term_site_id( $taxonomy );

		$switched = false;
		if ( get_current_blog_id() !== $site_id ) {
			switch_to_blog( $site_id );
			wpmn_register_taxonomies();
			$switched = true;
		}

		$args   = array();
		$retval = wp_insert_term( $term, $taxonomy, $args );

		if ( $switched ) {
			restore_current_blog();
		}

		return $retval;
	}
}

/**
 * Removes the taxonomy relationship to terms from the cache.
 *
 * @since x.x.x
 *
 * @see clean_object_term_cache() for a full description of function and parameters.
 *
 * @param string|array $terms     Term or terms to set.
 * @param string       $taxonomy  Taxonomy name.
 * @param bool         $append    Optional. True to append terms to existing terms. Default: false.
 * @return array Array of term taxonomy IDs.
 */
if ( ! function_exists( 'wpmn_clean_object_term_cache' ) ) {
	function wpmn_clean_object_term_cache( $terms, $taxonomy ) {
		$site_id = wpmn_get_taxonomy_term_site_id( $taxonomy );

		$switched = false;
		if ( get_current_blog_id() !== $site_id ) {
			switch_to_blog( $site_id );
			wpmn_register_taxonomies();
			$switched = true;
		}

		$retval = clean_object_term_cache( $terms, $taxonomy );

		if ( $switched ) {
			restore_current_blog();
		}

		return $retval;

	}
}

/**
 * Return the site url of the root blog of the primary network.
 *
 * @since x.x.x
 *
 *
 * @return string Primary network root blog site url
 */
if ( ! function_exists( 'wpmn_get_primary_network_root_domain' ) ) {
	function wpmn_get_primary_network_root_domain() {
		$main_network = wp_get_network( get_main_network_id() );
		$scheme       = ( is_ssl() ) ? 'https://' : 'http://';
		return apply_filters( 'wpmn_get_primary_network_root_domain', rtrim( $scheme . $main_network->domain . $main_network->path, '/' ) );

	}
}

