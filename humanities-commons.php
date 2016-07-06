<?php
/**
 * The Humanities Commons Plugin
 *
 * Humanities Commons is a set of functions, filters and actions used to support a specific multi-network BuddyPress configuration.
 *
 * @package Humanities Commons
 * @subpackage Configuration
 */

/**
 * Plugin Name: Humanities Commons
 * Description: Humanities Commons is a set of functions, filters and actions used to support a specific multi-network BuddyPress configuration.
 * Version: 1.0
 * Author: MLA
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require ( dirname( __FILE__ ) . '/wpmn-taxonomy-functions.php' );

class Humanities_Commons {

        public function __construct() {

		add_filter( 'bp_get_taxonomy_term_site_id', array( $this, 'hcomm_filter_bp_taxonomy_storage_site' ), 10, 2 );
		add_filter( 'wpmn_get_taxonomy_term_site_id', array( $this, 'hcomm_filter_hc_taxonomy_storage_site' ), 10, 2 );
		add_action( 'bp_register_member_types', array( $this, 'hcomm_register_member_types' ) );
		add_action( 'bp_groups_register_group_types', array( $this, 'hcomm_register_group_types' ) );
		add_action( 'bp_after_has_members_parse_args', array( $this, 'hcomm_set_members_query' ) );
		add_filter( 'bp_after_has_groups_parse_args', array( $this, 'hcomm_set_groups_query_args' ) );
		add_action( 'groups_create_group_step_save_group-details', array( $this, 'hcomm_set_group_type' ), 10, 3 );
		add_action( 'shibboleth_set_user_roles', array( $this, 'hcomm_set_user_member_types' ), 10, 3 );
		add_filter( 'bp_before_has_blogs_parse_args', array( $this, 'hcomm_set_network_blogs_query' ) );
		add_filter( 'bp_before_has_activities_parse_args', array( $this, 'hcomm_set_network_activities_query' ) );
		add_filter( 'bp_activity_add', array( $this, 'hcomm_set_activity_society_meta' ), 10, 3 );
		add_filter( 'body_class', array( $this, 'hcomm_society_body_class_name' ) );
	}

	public function hcomm_filter_bp_taxonomy_storage_site( $site_id, $taxonomy ) {

		if ( in_array( $taxonomy, array( 'bp_group_type', 'bp_member_type' ) ) ) {
			$main_network = wp_get_network( get_main_network_id() );
			$site = get_site_by_path( $main_network->domain, $main_network->path );
			return $site->site_id;
		} else {
			return $site_id;
		}
	}

	public function hcomm_filter_hc_taxonomy_storage_site( $site_id, $taxonomy ) {

		if ( in_array( $taxonomy, array( 'mla_academic_interests', 'humcore_deposit_subject', 'humcore_deposit_tag' ) ) ) {
			$main_network = wp_get_network( get_main_network_id() );
			$site = get_site_by_path( $main_network->domain, $main_network->path );
			return $site->site_id;
		} else {
			return $site_id;
		}
	}

	public function hcomm_register_member_types() {

		bp_register_member_type(
			'ajs',
	 		array(
				'labels' => array(
				'name' => 'AJS',
				'singular_name' => 'AJS',
			),
			'has_directory' => 'ajs'
			) );

		bp_register_member_type(
			'aseees',
	 		array(
				'labels' => array(
				'name' => 'ASEEES',
				'singular_name' => 'ASEEES',
			),
			'has_directory' => 'aseees'
			) );

		bp_register_member_type(
			'caa',
	 		array(
				'labels' => array(
				'name' => 'CAA',
				'singular_name' => 'CAA',
			),
			'has_directory' => 'caa'
			) );

		bp_register_member_type(
			'hc',
	 		array(
				'labels' => array(
				'name' => 'HC',
				'singular_name' => 'HC',
			),
			'has_directory' => 'hc'
			) );

		bp_register_member_type(
			'mla',
	 		array(
				'labels' => array(
				'name' => 'MLA',
				'singular_name' => 'MLA',
			),
			'has_directory' => 'mla'
			) );
		}

	public function hcomm_register_group_types() {

		bp_groups_register_group_type(
			'ajs',
	 		array(
				'labels' => array(
				'name' => 'AJS',
				'singular_name' => 'AJS',
			),
			'has_directory' => 'ajs'
			) );

		bp_groups_register_group_type(
			'aseees',
	 		array(
				'labels' => array(
				'name' => 'ASEEES',
				'singular_name' => 'ASEEES',
			),
			'has_directory' => 'aseees'
			) );

		bp_groups_register_group_type(
			'caa',
	 		array(
				'labels' => array(
				'name' => 'CAA',
				'singular_name' => 'CAA',
			),
			'has_directory' => 'caa'
			) );

		bp_groups_register_group_type(
			'hc',
	 		array(
				'labels' => array(
				'name' => 'HC',
				'singular_name' => 'HC',
			),
			'has_directory' => 'hc'
			) );

		bp_groups_register_group_type(
			'mla',
	 		array(
				'labels' => array(
				'name' => 'MLA',
				'singular_name' => 'MLA',
			),
			'has_directory' => 'mla'
			) );
	}

	public function hcomm_set_members_query( $args ) {

		$society_id = get_network_option( '', 'society_id' );
		$args['member_type'] = $society_id;
		return $args;
	}

	public function hcomm_set_groups_query_args( $args ) {

		$society_id = get_network_option( '', 'society_id' );
		if ( 'hc' !== $society_id ) {
			$args['group_type'] = $society_id;
		}
		return $args;
	}

	public function hcomm_set_group_type( $group_id ) {

		global $bp;
		if ( $bp->groups->new_group_id ) {
			$id = $bp->groups->new_group_id;
		} else {
			$id = $group_id;
		}

		$society_id = get_network_option( '', 'society_id' );
		bp_groups_set_group_type( $id, $society_id );
	}

	public function hcomm_get_user_memberships( $user_id ) {

        	$memberships = array();
		$member_types = bp_get_member_types();
        	$membership_header = $_SERVER['HTTP_ISMEMBEROF'] . ';';
        	error_log( '**********************GET_MEMBERSHIPS********************-'.$user_id.'-'.var_export( $membership_header, true ) );

        	foreach ( $member_types as $key=>$value ) {

                	$pattern = sprintf( '/Humanities Commons:%1$s:members_%1$s;/', strtoupper( $key ) );
                	if ( preg_match( $pattern, $membership_header, $matches ) ) {
                        	$memberships['societies'][] = $key;
                	}

                	$pattern = sprintf( '/Humanities Commons:%1$s_(.*?);/', strtoupper( $key ) );
                	if ( preg_match_all( $pattern, $membership_header, $matches ) ) {
				error_log( '**********************GET_MATCHES********************-'.$key.'-'.var_export( $matches, true ) );
                        	$memberships['groups'][$key] = $matches[1];
                	}

        	}

		return $memberships;
	}

	public function hcomm_set_user_member_types( $user ) {

		$user_id = $user->ID;
		$memberships = $this->hcomm_get_user_memberships( $user_id );
		$member_types = bp_get_member_types();
        	error_log( '**********************RETURNED_MEMBERSHIPS********************-'.var_export( $memberships, true ) );

		$result = bp_set_member_type( $user_id, '' ); // Clear existing types, if any.
		$append = true;
		foreach( $memberships['societies'] as $member_type ) {
			$result = bp_set_member_type( $user_id, $member_type, $append );
			error_log( '**********************SET_EACH_MEMBER_TYPE********************-'.$user_id.'-'.$member_type.'-'.var_export( $result, true ) );
		}
	}

	public function hcomm_set_network_blogs_query( $args ) {

		$current_network = get_current_site();
        	if ( 1 !== (int) $current_network->id ) {
			$network_sites = wp_get_sites( array( 'network_id' => $current_network->id, 'limit' => 9999 ) );
			$blog_ids = array();
			foreach( $network_sites as $site ) {
				$blog_ids[] = $site['blog_id'];
			}
			$include_blogs = implode( ',', $blog_ids );
                	$args['include_blog_ids'] = $include_blogs;
        	}

		return $args;
	}

	public function hcomm_set_network_activities_query( $args ) {

		$society_id = get_network_option( '', 'society_id' );
		$args['meta_query'] = array(
			array(
				'key'     => 'society_id',
				'value'   => $society_id,
				'type'    => 'string',
				'compare' => '='
			),
		);

		return $args;
	}

	public function hcomm_set_activity_society_meta( $args = '' ) {

		$society_id = get_network_option( '', 'society_id' );

		$r = array();
		$r['user_id']           = $args['user_id'];
		$r['component']         = $args['component'];
		$r['type']              = $args['type'];
		$r['item_id']           = $args['item_id'];
		$r['secondary_item_id'] = $args['secondary_item_id'];
		$r['action']            = $args['action'];
		$r['content']           = $args['content'];
		$r['date_recorded']     = $args['recorded_time'];

		$activity_id = bp_activity_get_activity_id( $r );

		if ( $activity_id ) {
			bp_activity_add_meta( $activity_id, 'society_id', $society_id, true );
		}
	}

	public function hcomm_society_body_class_name( $classes ) {

		$society_id = get_network_option( '', 'society_id' );
        	$classes[] = 'society-' . $society_id;
        	return $classes;
	}

}

$humanities_commons = new Humanities_Commons;

/**
 * Verify that correct nonce was used with time limit.
 *
 * The user is given an amount of time to use the token, so therefore, since the
 * UID and $action remain the same, the independent variable is the time.
 *
 * @since 2.0.3
 *
 * @param string     $nonce  Nonce that was used in the form to verify
 * @param string|int $action Should give context to what is taking place and be the same when nonce was created.
 * @return false|int False if the nonce is invalid, 1 if the nonce is valid and generated between
 *                   0-12 hours ago, 2 if the nonce is valid and generated between 12-24 hours ago.
 */
function wp_verify_nonce( $nonce, $action = -1 ) {
	$nonce = (string) $nonce;
	$user = wp_get_current_user();
	$uid = (int) $user->ID;
	if ( ! $uid ) {
		/**
		 * Filter whether the user who generated the nonce is logged out.
		 *
		 * @since 3.5.0
		 *
		 * @param int    $uid    ID of the nonce-owning user.
		 * @param string $action The nonce action.
		 */
		$uid = apply_filters( 'nonce_user_logged_out', $uid, $action );
	}

	if ( empty( $nonce ) ) {
		return false;
	}

	$token = wp_get_session_token();
	$i = wp_nonce_tick();

	// Nonce generated 0-12 hours ago
	$expected = substr( wp_hash( $i . '|' . $action . '|' . $uid . '|' . $token, 'nonce'), -12, 10 );
	if ( hash_equals( $expected, $nonce ) ) {
		return 1;
	}

	// Nonce generated 12-24 hours ago
	$expected = substr( wp_hash( ( $i - 1 ) . '|' . $action . '|' . $uid . '|' . $token, 'nonce' ), -12, 10 );
	if ( hash_equals( $expected, $nonce ) ) {
		return 2;
	}

	/**
	 * Fires when nonce verification fails.
	 *
	 * @since 4.4.0
	 *
	 * @param string     $nonce  The invalid nonce.
	 * @param string|int $action The nonce action.
	 * @param WP_User    $user   The current user object.
	 * @param string     $token  The user's session token.
	 */
	do_action( 'wp_verify_nonce_failed', $nonce, $action, $user, $token );

	// Invalid nonce
	return false;
	//return 1;
}

function hcomm_debug_shibboleth_user_role( $user_role ) {
	error_log( '**********************USER_ROLE********************-'.$user_role.'-'.var_export( $_SERVER, true ) );
	return $user_role;
}
add_filter( 'shibboleth_user_role', 'hcomm_debug_shibboleth_user_role', 10, 3 );

function hcomm_debug_ajax_referer( $action, $result ) {
	error_log( '**********************AJAX_REFERER********************-'.$action.'-'.var_export( $result, true ).'-'.var_export( $_REQUEST, true ) );
}
add_action( 'check_ajax_referer', 'hcomm_debug_ajax_referer', 10, 2 );

function hcomm_debug_nonce_failed( $nonce, $action, $user, $token ) {
	error_log( '**********************NONCE_FAILED********************-'.var_export( $nonce, true ).'-'.var_export( $action, true).'-'.var_export( $user->ID, true ).'-'.var_export( $token, true ) );
}
add_action( 'wp_verify_nonce_failed', 'hcomm_debug_nonce_failed', 10, 4 );

