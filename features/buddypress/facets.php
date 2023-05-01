<?php
/**
 * functions that output search facet markup for the ElasticPress BuddyPress feature
 */

/**
 * output HTML for post type facet <select>
 * TODO filterable
 */
function ep_bp_post_type_select() {
	// buddypress fake "post" types
	$post_types = [
		EP_BP_API::GROUP_TYPE_NAME => 'Groups',
		EP_BP_API::MEMBER_TYPE_NAME => 'Members',
	];

	// actual post types
	$elasticpress_post = new ElasticPress\Indexable\Post\Post();
	$indexable_post_types = $elasticpress_post->get_indexable_post_types();
	foreach ( $indexable_post_types as $post_type ) {
		$post_type_object = get_post_type_object( $post_type );
		if ( apply_filters( 'ep_bp_show_post_type_facet_' . $post_type_object->name, true ) ) {
			$post_types[ $post_type_object->name ] = $post_type_object->label;
		}
	}

	?>
	<select multiple name="post_type[]" id="post_type" size="<?php echo count( $post_types ); ?>">
	<?php foreach ( $post_types as $name => $label ) {
		$selected = ( ! isset( $_REQUEST['post_type'] ) || in_array( $name, $_REQUEST['post_type'] ) );
		printf( '<option value="%1$s"%3$s>%2$s</option>',
			$name,
			$label,
			( $selected ) ? ' selected' : ''
		);
	} ?>
	</select>
	<span id="ep_bp_post_type_facet"></span>
	<?php
}

/**
 * output HTML for network facet
 * TODO find a way to avoid removing/adding index name filter
 */
function ep_bp_network_select() {
	// short-circuit our own index name filter to build the list
	remove_filter( 'ep_index_name', 'ep_bp_filter_ep_index_name', 10, 2 );

	$networks = [];

	foreach ( get_networks() as $network ) {
		if ( apply_filters( 'ep_bp_show_network_facet_' . $network->id, true ) ) {
			$networks[] = $network;
		}
	}

	?>
		<select multiple name="index[]" id="index" size="<?php echo count( $networks ); ?>">
		<?php foreach ( $networks as $network ) {
			switch_to_blog( get_main_site_for_network( $network ) );
			$selected = ( in_array( ep_get_index_name(), $_REQUEST['index'] ) );
			printf( '<option value="%1$s"%3$s>%2$s</option>',
				ep_get_index_name(),
				get_bloginfo(),
				( $selected ) ? ' selected' : ''
			);
			restore_current_blog();
		} ?>
	</select>
	<span id="ep_bp_index_facet"></span>
	<?php
	// restore index name filter
	add_filter( 'ep_index_name', 'ep_bp_filter_ep_index_name', 10, 2 );
}

/**
 * output HTML for orderby facet
 */
function ep_bp_orderby_select() {
	$options = [
		'_score' => 'Relevance',
		'date' => 'Date',
	];
	echo '<select name="orderby" id="orderby">';
	foreach ( $options as $value => $label ) {
		$selected = ( isset( $_REQUEST['orderby'] ) && $value === $_REQUEST['orderby'] );
		printf( '<option value="%1$s"%3$s>%2$s</option>',
			$value,
			$label,
			( $selected ) ? ' selected' : ''
		);
	}
	echo '</select>';
}

/**
 * output HTML for order facet
 */
function ep_bp_order_select() {
	$options = [
		'desc' => 'Descending',
		'asc' => 'Ascending',
	];
	echo '<select name="order" id="order">';
	foreach ( $options as $value => $label ) {
		$selected = ( isset( $_REQUEST['order'] ) && $value === $_REQUEST['order'] );
		printf( '<option value="%1$s"%3$s>%2$s</option>',
			$value,
			$label,
			( $selected ) ? ' selected' : ''
		);
	}
	echo '</select>';
}

/**
 * Add search facets to sidebar.
 * TODO widgetize?
 */
function ep_bp_get_sidebar() {
	?>
	<aside id="ep-bp-facets" class="widget" role="complementary">
		<h4>Search Facets</h4>
		<form class="ep-bp-search-facets">
			<input type="hidden" name="s" value="<?php echo get_search_query(); ?>">
			<h5><label for="post_type">Filter by type</label></h5>
			<?php ep_bp_post_type_select(); ?>
			<h5><label for="index">Filter by network</label></h5>
			<?php ep_bp_network_select(); ?>
			<h5><label for="order">Sort</label> <label for="orderby">by</label></h5>
			<?php ep_bp_orderby_select(); ?>
			<?php ep_bp_order_select(); ?>
		</form>
	</aside>
	<?php

	// only once. TODO
	remove_action( 'is_active_sidebar', '__return_true' );
	remove_action( 'dynamic_sidebar_before', 'ep_bp_get_sidebar' );
}
