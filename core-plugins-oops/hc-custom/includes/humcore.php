<?php
/**
 * Customizations to Humanities Core
 *
 * @package Hc_Custom
 */

/**
 * Array of member groups that can author deposits in Core.
 *
 * @return array Group ids.
 */
function hcommons_core_member_groups_with_authorship( $current_groups = array() ) {

	//$current_groups passed in not implemented
	$committee_group_ids = array();
	$args                = array(
		'type'       => 'alphabetical',
		'meta_query' => array(
			array(
				'key'     => 'mla_oid',
				'value'   => 'M',
				'compare' => 'LIKE',
			),
		),
		'per_page'   => '500',
	);

	return array_merge( 
		$committee_group_ids, 
		array( 
			184, 
			296, 
			378, 
			444, 
			1002994, 
			1003452, 
			1003458, 
			1001245, 
			1003565, 
			15, 
			1003584, 
			1003768, 
			1003973, 
			1004001, 
			1020814, 
			1004014, 
			1004097, // Intaglio Journal
		) 
	);

}
add_filter( 'humcore_member_groups_with_authorship', 'hcommons_core_member_groups_with_authorship' );

/**
 * Remove groups that are marked as committees from Core group list.
 *
 * @param array $groups Groups.
 * @return array Groups with committees removed.
 */
function hcommons_filter_humcore_groups_list( $groups ) {

	$filtered_groups = array();
	foreach ( $groups as $group_id => $group_name ) {
		if ( ! mla_is_group_committee( $group_id ) ) {
			$filtered_groups[ $group_id ] = $group_name;
		}
	}

	return $filtered_groups;

}
add_filter( 'humcore_deposits_group_list', 'hcommons_filter_humcore_groups_list' );

/**
 * Change post type label for core deposits
 * TODO either update the actual post type data or put in humcore plugin
 *
 * @param array $labels Post type labels.
 */
function hcommons_filter_post_type_labels_humcore_deposit( $labels ) {
	$labels->name = 'CORE Deposits';
	return $labels;
}
add_filter( 'post_type_labels_humcore_deposit', 'hcommons_filter_post_type_labels_humcore_deposit' );

/**
 * Ensure that the 'All Deposits' facet is selected through settings cookie if
 * nothing is selected.
 */
function hcommons_enqueue_all_deposits_script() {
	
	if ( array_key_exists( 'scope', $_POST ) ) {
		$scope = sanitize_key( $_POST['scope'] );
		setcookie( 'bp-deposits-scope', $scope, time() + 60*60*24*30, '/' );
		// unless the $_COOKIE global is updated in addition to the actual cookie above,
		// bp will not use the value for the first pageload.
		$_COOKIE[ 'bp-deposits-scope' ] = $scope;
		setcookie( 'ssss', $scope, time() + 60*60*24*30, '/' );
	}
	
	
	$js_path    = 'includes/js/humcore-select-all-deposits.js';
	$js_version = filemtime( trailingslashit( plugin_dir_path( __DIR__ ) ) . $js_path );
	wp_enqueue_script( 'hc-humcore-add-all-deposits-cookie', plugins_url( $js_path, __DIR__ ), [], $js_version, true );
}
add_action( 'wp_enqueue_scripts', 'hcommons_enqueue_all_deposits_script', 10, 0 );
