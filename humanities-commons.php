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

use MLA\Commons\Plugin\Logging\Logger;

global $hcommons_logger;
$hcommons_logger = new Logger( 'hcommons_error' );
$hcommons_logger->createLog( 'hcommons_error' );

/**
 * Write a formatted HCommons error or informational message.
 */
function hcommons_write_error_log( $error_type, $error_message, $info = null ) {

        global $hcommons_logger;
        if ( 'info' === $error_type ) {
                if ( empty( $info ) ) {
                        $hcommons_logger->addInfo( $error_message );
                } else {
                        $hcommons_logger->addInfo( $error_message . ' : ', $info );
                }
        } else {
                        $hcommons_logger->addError( $error_message );
        }
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
		add_action( 'groups_create_group_step_save_group-details', array( $this, 'hcomm_set_group_type' ) );
		add_action( 'shibboleth_set_user_roles', array( $this, 'hcomm_set_user_member_types' ) );
		add_filter( 'bp_before_has_blogs_parse_args', array( $this, 'hcomm_set_network_blogs_query' ) );
		add_filter( 'bp_before_has_activities_parse_args', array( $this, 'hcomm_set_network_activities_query' ) );
		add_filter( 'bp_activity_after_save', array( $this, 'hcomm_set_activity_society_meta' ) );
		add_filter( 'body_class', array( $this, 'hcomm_society_body_class_name' ) );
                add_filter( 'bp_current_user_can', array( $this, 'hcomm_check_user_member_type' ), 10, 4 );
                add_filter( 'shibboleth_user_role', array( $this, 'hcomm_check_user_site_membership' ) );
                add_filter( 'bp_get_groups_directory_permalink', array( $this, 'hcomm_set_group_permalink' ) );
                add_filter( 'get_blogs_of_user', array( $this, 'hcomm_filter_get_blogs_of_user'), 10, 3 );

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

		$member_types = bp_get_member_types();
        	hcommons_write_error_log( 'info', '****GROUP_QUERY_MEMBER_TYPES****-'.var_export($member_types,true) );
		$user_id = get_current_user_id();
		if ( 0 !== $user_id) {
                $society_id = get_network_option( '', 'society_id' );
		$member_societies = (array) bp_get_member_type( $user_id, false );
        	hcommons_write_error_log( 'info', '****GROUP_QUERY_USER_MEMBER_TYPES****-'.var_export($member_societies,true) );
		}
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

	public function hcomm_get_user_memberships() {

        	$memberships = array();
		$member_types = bp_get_member_types();
        	$membership_header = $_SERVER['HTTP_ISMEMBEROF'] . ';';
        	hcommons_write_error_log( 'info', '**********************GET_MEMBERSHIPS********************-'.var_export( $membership_header, true ).'-'.var_export($member_types,true) );

        	foreach ( $member_types as $key=>$value ) {

                	$pattern = sprintf( '/Humanities Commons:%1$s:members_%1$s;/', strtoupper( $key ) );
                	if ( preg_match( $pattern, $membership_header, $matches ) ) {
                        	$memberships['societies'][] = $key;
                	}

                	$pattern = sprintf( '/Humanities Commons:%1$s_(.*?);/', strtoupper( $key ) );
                	if ( preg_match_all( $pattern, $membership_header, $matches ) ) {
				hcommons_write_error_log( 'info', '****GET_MATCHES****-'.$key.'-'.var_export( $matches, true ) );
                        	$memberships['groups'][$key] = $matches[1];
                	}

        	}

		return $memberships;
	}

	public function hcomm_set_user_member_types( $user ) {

		$user_id = $user->ID;
		$memberships = $this->hcomm_get_user_memberships();
        	hcommons_write_error_log( 'info', '****RETURNED_MEMBERSHIPS****-'.var_export($memberships,true) );
		$main_network = wp_get_network( get_main_network_id() );
		
		$member_societies = (array) bp_get_member_type( $user_id, false );
        	hcommons_write_error_log( 'info', '****PRE_SET_USER_MEMBER_TYPES****-'.var_export($member_societies,true) );
		$result = bp_set_member_type( $user_id, '' ); // Clear existing types, if any.
		$append = true;
		foreach( $memberships['societies'] as $member_type ) {
			$result = bp_set_member_type( $user_id, $member_type, $append );
			hcommons_write_error_log( 'info', '****SET_EACH_MEMBER_TYPE****-'.$user_id.'-'.$member_type.'-'.var_export( $result, true ) );
		}
	}

	public function hcomm_set_network_blogs_query( $args ) {

                $current_society_id = get_network_option( '', 'society_id' );
                if ( 'hc' !== $current_society_id ) {
			$current_network = get_current_site();
			$current_blog_id = get_current_blog_id();
			$network_sites = wp_get_sites( array( 'network_id' => $current_network->id, 'limit' => 9999 ) );
			$blog_ids = array();
			foreach( $network_sites as $site ) {
				if ( $site['blog_id'] != $current_blog_id ) {
					$blog_ids[] = $site['blog_id'];
				}
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

	public function hcomm_set_activity_society_meta( $activity ) {

		$society_id = get_network_option( '', 'society_id' );
		bp_activity_add_meta( $activity->id, 'society_id', $society_id, true );
	}

	public function hcomm_society_body_class_name( $classes ) {

		$society_id = get_network_option( '', 'society_id' );
        	$classes[] = 'society-' . $society_id;
        	return $classes;
	}

        public function hcomm_check_user_member_type( $retval, $capability, $blog_id, $args ) {

		//hcommons_write_error_log( 'info', '****CHECK_USER_MEMBER_TYPE***-'.var_export( $retval, true ).'-'.var_export( $capability, true ).'-'.$blog_id.'-'.var_export( $args, true ) );
		$user_id = get_current_user_id();
		if ( 0 === $user_id) {
			return $retval;
		}
                $society_id = get_network_option( '', 'society_id' );
		//TODO Why is taxonomy invalid here on HC?
		$member_societies = (array) bp_get_member_type( $user_id, false );
		if ( bp_has_member_type( $user_id, $society_id ) ) {
		//hcommons_write_error_log( 'info', '****CHECK_USER_MEMBER_TYPE_TRUE***-'.var_export( $user_id, true ).'-'.var_export( $member_societies, true ).'-'.var_export( $society_id, true ) );
                	return $retval;
		} else {
		//hcommons_write_error_log( 'info', '****CHECK_USER_MEMBER_TYPE_FALSE***-'.var_export( $user_id, true ).'-'.var_export( $member_societies, true ).'-'.var_export( $society_id, true ) );
			return false;
		}
        }

        public function hcomm_check_user_site_membership( $user_role ) {

                $society_id = get_network_option( '', 'society_id' );
		$memberships = $this->hcomm_get_user_memberships();
		if ( in_array( $society_id, $memberships['societies'] ) ) {
                	return $user_role;
		} else {
			return '';
		}
        }

        public function hcomm_set_group_permalink( $group_permalink ) {

                $current_society_id = get_network_option( '', 'society_id' );
                if ( 'hc' !== $current_society_id ) {
			return $group_permalink;
		}
		global $wpdb;
		$group_id = bp_get_group_id();
                $society_id = bp_groups_get_group_type( $group_id );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT site_id FROM $wpdb->sitemeta WHERE meta_key = '%s' AND meta_value = '%s'", 'society_id', $society_id ) );
		if ( is_object( $row ) ) {
	        	$society_network = wp_get_network( $row->site_id );
        		$scheme = ( is_ssl() ) ? 'https://' : 'http://';
			$group_permalink = trailingslashit( $scheme . $society_network->domain . $society_network->path . bp_get_groups_root_slug() );
		}
		return $group_permalink;
        }

	public function hcomm_filter_get_blogs_of_user( $blogs, $user_id, $all ) {

		$network_blogs = $blogs;
                $current_network = get_current_site();
		$current_blog_id = get_current_blog_id();
		foreach ($blogs as $blog) {
			if ( $current_network->id != $blog->site_id || $current_blog_id == $blog->userblog_id ) {
				unset ( $network_blogs[$blog->userblog_id] );
			}
		}
		return $network_blogs;
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
	hcommons_write_error_log( 'info', '**********************USER_ROLE********************-'.$user_role.'-'.var_export( $_SERVER, true ) );
	return $user_role;
}
add_filter( 'shibboleth_user_role', 'hcomm_debug_shibboleth_user_role', 10, 3 );

function hcomm_debug_ajax_referer( $action, $result ) {
	hcommons_write_error_log( 'info', '**********************AJAX_REFERER********************-'.$action.'-'.var_export( $result, true ).'-'.var_export( $_REQUEST, true ) );
}
add_action( 'check_ajax_referer', 'hcomm_debug_ajax_referer', 10, 2 );

function hcomm_debug_nonce_failed( $nonce, $action, $user, $token ) {
	hcommons_write_error_log( 'info', '**********************NONCE_FAILED********************-'.var_export( $nonce, true ).'-'.var_export( $action, true).'-'.var_export( $user->ID, true ).'-'.var_export( $token, true ) );
}
add_action( 'wp_verify_nonce_failed', 'hcomm_debug_nonce_failed', 10, 4 );

