<?php
/**
 * Feature for ElasticPress to enable BuddyPress content.
 */

/**
 * styles to clean up search results
 */
function ep_bp_enqueue_style() {
	wp_register_style( 'elasticpress-buddypress', plugins_url( '/elasticpress-buddypress/css/elasticpress-buddypress.css' ) );
	wp_enqueue_style( 'elasticpress-buddypress' );
}

/**
 * javascript powering search facets
 */
function ep_bp_enqueue_scripts() {
	wp_register_script(
		'elasticpress-buddypress-jquery-tabselect',
		plugins_url( 'js/jquery.tabselect-0.2.js',  dirname( __FILE__ ) . '/../../..'  ),
		[ 'jquery' ]
	);
	wp_enqueue_script( 'elasticpress-buddypress-jquery-tabselect' );

	wp_register_script(
		'elasticpress-buddypress',
		plugins_url( 'js/elasticpress-buddypress.js', dirname( __FILE__ ) . '/../../..' ),
		[ 'elasticpress-buddypress-jquery-tabselect' ]
	);
	wp_enqueue_script( 'elasticpress-buddypress', [ 'elasticpress-buddypress-jquery-tabselect' ] );
}

/**
 * Setup all feature filters
 */
function ep_bp_setup() {
	add_action( 'pre_get_posts', 'ep_bp_translate_args', 20 ); // after elasticpress ep_improve_default_search()

	// $wp_query->is_search is not set until parse_query
	add_action( 'parse_query', function() {
		if ( is_search() ) {
			add_action( 'wp_enqueue_scripts', 'ep_bp_enqueue_style' );
			add_action( 'wp_enqueue_scripts', 'ep_bp_enqueue_scripts' );

			// these actions are removed at the end of ep_bp_get_sidebar()
			add_action( 'is_active_sidebar', '__return_true' );
			add_action( 'dynamic_sidebar_before', 'ep_bp_get_sidebar' );

			add_filter( 'the_permalink', 'ep_bp_filter_the_permalink' );

			// temporarily filter titles to include post type in results
			add_action( 'loop_start', function() {
				add_filter( 'the_title', 'ep_bp_filter_result_titles', 1, 20 );
				add_filter( 'author_link', 'ep_bp_filter_result_author_link' );
			} );
			add_action( 'loop_end', function() {
				remove_filter( 'the_title', 'ep_bp_filter_result_titles', 1, 20 );
				remove_filter( 'author_link', 'ep_bp_filter_result_author_link' );
			} );
		}
	} );

	// these don't require conditions since they only trigger during ep functions in the first place
	add_filter( 'ep_formatted_args', 'ep_bp_filter_ep_formatted_args' );
	add_filter( 'ep_indexable_post_types', 'ep_bp_post_types' );
	add_filter( 'ep_index_name', 'ep_bp_filter_ep_index_name', 10, 2 );
	add_filter( 'ep_default_index_number_of_shards', 'ep_bp_filter_ep_default_index_number_of_shards' );
	add_filter( 'ep_sync_taxonomies', 'ep_bp_whitelist_taxonomies' );
	add_filter( 'ep_search_request_path', 'ep_bp_filter_ep_search_request_path' );
	add_filter( 'ep_search_results_array', 'ep_bp_filter_ep_search_results_array' );
	add_filter( 'ep_config_mapping', 'ep_bp_filter_ep_config_mapping' );
	add_filter( 'ep_elasticpress_enabled', 'ep_bp_filter_ep_elasticpress_enabled', 20, 2 ); // after ep_integrate_search_queries()

	add_action( 'ep_wp_cli_pre_index', function() {
		// replace the bbpress filter with a filter when ep syncs
		// bbpress is overzealous and excludes more posts than it should
		remove_action( 'pre_get_posts', 'bbp_pre_get_posts_normalize_forum_visibility', 4 );
		add_filter( 'ep_post_sync_kill', 'ep_bp_filter_ep_post_sync_kill', 10, 3 );

		// prevent infinite loops as bbpress waffles with reply titles
		add_filter( 'bbp_get_topic_id', function( $bbp_topic_id, $topic_id ) {
			if ( $bbp_topic_id === 0 && $topic_id === 0 ) {
				the_post();
			}

			return $bbp_topic_id;
		}, 10, 2 );
	} );
}

/**
 * Determine BP feature reqs status
 *
 * @param  EP_Feature_Requirements_Status $status
 * @return EP_Feature_Requirements_Status
 */
function ep_bp_requirements_status( $status ) {
	$required_classes = [ 'BuddyPress', 'bbPress' ];

	foreach ( $required_classes as $class ) {
		if ( ! class_exists( $class ) ) {
			$status->code = 2;
			$status->message = __( "$class is not active.", 'elasticpress' );
		}
	}

	return $status;
}

/**
 * Output feature box summary
 */
function ep_bp_feature_box_summary() {
	echo esc_html_e( 'Index BuddyPress content like groups and members.', 'elasticpress-buddypress' );
}

/**
 * Register the feature
 */
function ep_bp_register_feature() {
	if ( function_exists( 'ep_register_feature' ) ) {
		ep_register_feature( 'buddypress', [
			'title' => 'BuddyPress',
			'setup_cb' => 'ep_bp_setup',
			'requirements_status_cb' => 'ep_bp_requirements_status',
			'feature_box_summary_cb' => 'ep_bp_feature_box_summary',
			'requires_install_reindex' => false,
		] );
	}
}
