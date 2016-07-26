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
require ( dirname( __FILE__ ) . '/admin-toolbar.php' );

class Humanities_Commons {

	/**
	 * the network called "Humanities Commons" a.k.a. the hub
	 */
	public static $main_network;

	/**
	 * root blog of the main network
	 */
	public static $main_site;

	public function __construct() {

		self::$main_network = wp_get_network( get_main_network_id() );
		self::$main_site = get_site_by_path( self::$main_network->domain, self::$main_network->path );

		add_filter( 'bp_get_taxonomy_term_site_id', array( $this, 'hcommons_filter_bp_taxonomy_storage_site' ), 10, 2 );
		add_filter( 'wpmn_get_taxonomy_term_site_id', array( $this, 'hcommons_filter_hc_taxonomy_storage_site' ), 10, 2 );
		add_action( 'bp_register_member_types', array( $this, 'hcommons_register_member_types' ) );
		add_action( 'bp_groups_register_group_types', array( $this, 'hcommons_register_group_types' ) );
		add_action( 'bp_after_has_members_parse_args', array( $this, 'hcommons_set_members_query' ) );
		add_filter( 'bp_after_has_groups_parse_args', array( $this, 'hcommons_set_groups_query_args' ) );
		add_action( 'groups_create_group_step_save_group-details', array( $this, 'hcommons_set_group_type' ) );
		add_action( 'shibboleth_set_user_roles', array( $this, 'hcommons_set_user_member_types' ) );
		add_action( 'shibboleth_set_user_roles', array( $this, 'hcommons_maybe_set_user_role_for_site' ) );
		add_filter( 'bp_before_has_blogs_parse_args', array( $this, 'hcommons_set_network_blogs_query' ) );
		add_filter( 'bp_get_total_blog_count', array( $this, 'hcommons_get_total_blog_count' ) );
		add_filter( 'bp_get_total_blog_count_for_user', array( $this, 'hcommons_get_total_blog_count_for_user' ) );
		add_filter( 'bp_before_has_activities_parse_args', array( $this, 'hcommons_set_network_activities_query' ) );
		add_filter( 'bp_activity_after_save', array( $this, 'hcommons_set_activity_society_meta' ) );
		add_filter( 'bp_activity_get_permalink', array( $this, 'hcommons_filter_activity_permalink' ), 10, 2 );
		add_filter( 'body_class', array( $this, 'hcommons_society_body_class_name' ) );
		//add_filter( 'bp_current_user_can', array( $this, 'hcommons_check_site_member_can' ), 10, 4 );
		add_filter( 'shibboleth_user_role', array( $this, 'hcommons_check_user_site_membership' ) );
		add_filter( 'bp_get_groups_directory_permalink', array( $this, 'hcommons_set_group_permalink' ) );
		add_filter( 'get_blogs_of_user', array( $this, 'hcommons_filter_get_blogs_of_user'), 10, 3 );
		add_filter( 'bp_core_avatar_upload_path', array( $this, 'hcommons_set_bp_core_avatar_upload_path' ) );
		add_filter( 'bp_core_avatar_url', array( $this, 'hcommons_set_bp_core_avatar_url' ) );
		add_filter( 'bp_get_group_join_button', array( $this, 'hcommons_check_bp_get_group_join_button' ), 10, 2 );
		add_action( 'shibboleth_set_user_roles', array( $this, 'hcommons_sync_bp_profile' ), 10, 3 );
		add_action( 'pre_user_query', array( &$this, 'hcommons_filter_site_users_only' ) );

	}

	public function hcommons_filter_bp_taxonomy_storage_site( $site_id, $taxonomy ) {

		if ( in_array( $taxonomy, array( 'bp_group_type', 'bp_member_type' ) ) ) {
			return self::$main_site->site_id;
		} else {
			return $site_id;
		}
	}

	public function hcommons_filter_hc_taxonomy_storage_site( $site_id, $taxonomy ) {

		if ( in_array( $taxonomy, array( 'mla_academic_interests', 'humcore_deposit_subject', 'humcore_deposit_tag' ) ) ) {
			return self::$main_site->site_id;
		} else {
			return $site_id;
		}
	}

	public function hcommons_register_member_types() {

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

	public function hcommons_register_group_types() {

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

	public function hcommons_set_members_query( $args ) {

		$society_id = get_network_option( '', 'society_id' );
		$args['member_type'] = $society_id;
		return $args;
	}

	public function hcommons_set_groups_query_args( $args ) {

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

	public function hcommons_set_group_type( $group_id ) {

		global $bp;
		if ( $bp->groups->new_group_id ) {
			$id = $bp->groups->new_group_id;
		} else {
			$id = $group_id;
		}

		$society_id = get_network_option( '', 'society_id' );
		bp_groups_set_group_type( $id, $society_id );
	}

	public function hcommons_get_user_memberships() {

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

	public function hcommons_set_user_member_types( $user ) {

		$user_id = $user->ID;
		$memberships = $this->hcommons_get_user_memberships();
		hcommons_write_error_log( 'info', '****RETURNED_MEMBERSHIPS****-'.$_SERVER['HTTP_HOST'].'-'.var_export($user,true).'-'.var_export($memberships,true) );
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

	public function hcommons_maybe_set_user_role_for_site( $user ) {

		//TODO Can we find WP functions that avoid messing directly with usermeta for a user that has not yet signed in?
		global $wpdb;
		$prefix = $wpdb->get_blog_prefix();
		$user_id = $user->ID;
		$site_caps = get_user_meta( $user_id, $prefix . 'capabilities', true );
		$site_caps_array = maybe_unserialize( $site_caps );
		$society_id = get_network_option( '', 'society_id' );
		$memberships = $this->hcommons_get_user_memberships();
		$is_site_member = in_array( $society_id, $memberships['societies'] );

		if ( $is_site_member ) {
			$site_role_found = false;
			foreach( $site_caps_array as $key=>$value ) {
				if ( in_array( $key, array( 'subscriber', 'contributor', 'author', 'editor', 'administrator' ) ) ) {
					$site_role_found = true;
					break;
				}
			}
			if ( $is_site_member && ! $site_role_found ) {
				$site_caps_array['subscriber'] = true;
				$site_caps_updated = maybe_serialize( $site_caps_array );
				$result = update_user_meta( $user_id, $prefix . 'capabilities', $site_caps_updated );
				$user->init_caps();
				hcommons_write_error_log( 'info', '****MAYBE_SET_USER_ROLE_FOR_SITE***-'.var_export( $result, true ).'-'.var_export( $is_site_member, true ).'-'.var_export( $site_caps_updated, true ).'-'.var_export( $prefix, true ).'-'.var_export( $user_id, true ) );
			}
		} else {
			if ( ! empty( $site_caps ) ) {
				delete_user_meta( $user_id, $prefix . 'capabilities' );
				delete_user_meta( $user_id, $prefix . 'user_level' );
			}
		}
	}

	/**
	 * Get the society_id for the current blog or a given blog.
	 *
	 * @since HCommons
	 *
	 * @param string $blog_id
	 * @return string $current_society_id
	 */
	public function hcommons_get_blog_society_id( $blog_id = '' ) {

		$fields = array();
		if ( ! empty( $blog_id ) ) {
			$fields['blog_id'] = $blog_id;
		}
		$blog_details = get_blog_details( $fields );
		$current_society_id = get_network_option( $blog_details->site_id, 'society_id' );

		return $current_society_id;
	}

	/**
	 * Filter the count returned by bp_get_total_blog_count() which ultimately depends on BP_Blogs_Blog::get_all().
	 * We want to use the filtered results returned by BP_Blogs_Blog::get() instead, so that we accommodate MPO.
	 *
	 * @since HCommons
	 *
	 * @param string $count
	 * @return string $count
	 */
	public function hcommons_get_total_blog_count( $count ) {
		// let's see what the blogs query will actually include and use that for the count
		$blogs_query_args = $this->hcommons_set_network_blogs_query( array() );

		// now make sure the More Privacy Options filter removes any blogs it needs to
		$mpo_filtered_blogs = bp_blogs_get_blogs( $blogs_query_args );

		if ( $mpo_filtered_blogs ) {
			$count = $mpo_filtered_blogs['total'];
		}

		return $count;
	}

	/**
	 * Like hcommons_get_total_blog_count() except for users.
	 * Because the logged-in logic in BP_Blogs_Blog::get_blogs_for_user() doesn't check the 'public' column,
	 * MPO doesn't need to be accommodated, which is different than in hcommons_get_total_blog_count().
	 *
	 * @since HCommons
	 *
	 * @param string $count
	 * @return string $count
	 */
	public function hcommons_get_total_blog_count_for_user( $count ) {
		$user_blogs = bp_blogs_get_blogs_for_user( get_current_user_id() );

		if ( $user_blogs ) {
			// $user_blogs['total'] is WRONG! that's why this filter is here, just count the actual blogs instead.
			$count = count( $user_blogs['blogs'] );
		}

		return $count;
	}

	/**
	 * Filter the sites query by the society id for the current network except for HC.
	 *
	 * @since HCommons
	 *
	 * @param array $args
	 * @return array $args
	 */
	public function hcommons_set_network_blogs_query( $args ) {

		$current_society_id = get_network_option( '', 'society_id' );
		$blog_ids = array();

		if ( 'hc' !== $current_society_id ) {
			$current_network = get_current_site();
			$current_blog_id = get_current_blog_id();
			$network_sites = wp_get_sites( array( 'network_id' => $current_network->id, 'limit' => 9999 ) );
			foreach( $network_sites as $site ) {
				if ( $site['blog_id'] != $current_blog_id ) {
					$blog_ids[] = $site['blog_id'];
				}
			}
		} else {
			$sites = wp_get_sites( array( 'network_id' => null, 'limit' => 9999 ) );
			foreach( $sites as $site ) {
				if ( $site['blog_id'] != self::$main_site->blog_id ) {
					$blog_ids[] = $site['blog_id'];
				}
			}
		}

		if ( ! empty( $blog_ids ) ) {
			$include_blogs = implode( ',', $blog_ids );
			$args['include_blog_ids'] = $include_blogs;
		}

		return $args;
	}

	/**
	 * Filter the activity query by the society id for the current network.
	 *
	 * @since HCommons
	 *
	 * @param array $args
	 * @return array $args
	 */
	public function hcommons_set_network_activities_query( $args ) {

		$current_society_id = get_network_option( '', 'society_id' );
                if ( 'hc' !== $current_society_id ) {
			$args['meta_query'] = array(
				array(
					'key'     => 'society_id',
					'value'   => $current_society_id,
					'type'    => 'string',
					'compare' => '='
				),
			);
		}

		return $args;
	}

	/**
	 * Add the current society id to the current activity as an activity_meta record.
	 *
	 * @since HCommons
	 *
	 * @param array $activity
	 */
	public function hcommons_set_activity_society_meta( $activity ) {

		$society_id = get_network_option( '', 'society_id' );
		bp_activity_add_meta( $activity->id, 'society_id', $society_id, true );
	}

	/**
	 * Add the current society id to the current activity as an activity_meta record.
	 *
	 * @since HCommons
	 *
	 * @param string $link
	 * @param object $activity Passed by reference.
	 * @return string $link
	 */
	public function hcommons_filter_activity_permalink( $link, $activity ) {

		$society_id = get_network_option( '', 'society_id' );
		$activity_society_id = bp_activity_get_meta( $activity->id, 'society_id', true  );
		if ( $society_id == $activity_society_id ) {
			return $link;
		}

		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT site_id FROM $wpdb->sitemeta WHERE meta_key = '%s' AND meta_value = '%s'", 'society_id', $activity_society_id ) );
		if ( is_object( $row ) ) {
			$society_network = wp_get_network( $row->site_id );
			$scheme = ( is_ssl() ) ? 'https://' : 'http://';
			$activity_root_domain = $scheme . $society_network->domain . $society_network->path;
		}
		$society_activity_link = str_replace( trailingslashit( bp_get_root_domain() ), $activity_root_domain, $link );
		//hcommons_write_error_log( 'info', '****FILTER_ACTIVITY_PERMALINK***-'.$link.'-'.$society_activity_link.'-'.bp_get_root_domain().'-'.$society_id.'-'.var_export( $activity->id, true ) );

		return $society_activity_link;

	}

	/**
	 * Add the current society id to the body classes.
	 *
	 * @since HCommons
	 *
	 * @param array $classes
	 * @return array $classes
	 */
	public function hcommons_society_body_class_name( $classes ) {

		$society_id = get_network_option( '', 'society_id' );
		$classes[] = 'society-' . $society_id;
		return $classes;
	}

	/**
	 * Check if user has a capability on a given site.
	 *
	 * @since HCommons
	 *
	 * @param string $retval
	 * @param string $capability
	 * @param string $blog_id
	 * @param array $args
	 * @return string|bool $retval or false
	 */
	public function hcommons_check_site_member_can( $retval, $capability, $blog_id, $args ) {

		//hcommons_write_error_log( 'info', '****CHECK_USER_MEMBER_TYPE***-'.var_export( $retval, true ).'-'.var_export( $capability, true ).'-'.$blog_id.'-'.var_export( $args, true ) );
		$user_id = get_current_user_id();
		if ( $user_id < 2 ) {
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

	/**
	 * Check the user's membership to this network prior to login and if valid return the role.
	 *
	 * @since HCommons
	 *
	 * @param string $user_role
	 * @return string $user_role Role or null.
	 */
	public function hcommons_check_user_site_membership( $user_role ) {

		//TODO maybe get user role for site here and remove custom code from shibboleth
		$user_login = $_SERVER['HTTP_EMPLOYEENUMBER'];
		$user = get_user_by( 'login', $user_login );
		$user_id = $user->ID;

		$society_id = get_network_option( '', 'society_id' );
		$memberships = $this->hcommons_get_user_memberships();
		if ( in_array( $society_id, $memberships['societies'] ) || is_super_admin( $user_id ) ) {
			return $user_role;
		} else {
			return '';
		}
	}

	/**
	 * Set the group permalink to contain the proper network.
	 *
	 * @since HCommons
	 *
	 * @param string $group_permalink
	 * @return string $group_permalink Modified url.
	 */
	public function hcommons_set_group_permalink( $group_permalink ) {

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

	/**
	 * Filter out the user blogs that are not in the current network.
	 *
	 * @since HCommons
	 *
	 * @param array $blogs
	 * @param string $user_id
	 * @param bool $all
	 * @return array $network_blogs
	 */
	public function hcommons_filter_get_blogs_of_user( $blogs, $user_id, $all ) {

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

	/**
	 * Filter the BP Core avatar upload path to be global and not network specific.
	 *
	 * @since HCommons
	 *
	 * @param string $path
	 * @return string $path Modified path.
	 */
	public function hcommons_set_bp_core_avatar_upload_path( $path  ) {

		if ( ! empty( $path ) ) {
			$site_loc = strpos( $path, '/site' );
			if ( false === $site_loc ) {
				return $path;
			} else {
				$global_path = substr( $path, 0, $site_loc );
				//hcommons_write_error_log( 'info', '****BP_CORE_AVATAR_UPLOAD_PATH****-'.var_export( $global_path, true ) );
				return $global_path;
			}
		} else {
			return $path;
		}

	}

	/**
	 * Filter the BP Core avatar url to be global and not network specific.
	 *
	 * @since HCommons
	 *
	 * @param string $url
	 * @return string $url Modified url.
	 */
	public function hcommons_set_bp_core_avatar_url( $url  ) {

		if ( ! empty( $url ) ) {
			$site_loc = strpos( $url, '/site' );
			if ( false === $site_loc ) {
				return $url;
			} else {
				$global_url = substr( $url, 0, $site_loc );
				//hcommons_write_error_log( 'info', '****BP_CORE_AVATAR_URL****-'.var_export( $global_url, true ) );
				return $global_url;
			}
		} else {
			return $url;
		}

	}

	/**
	 * Filter the Invite Anyone user query by member type for this network.
	 *
	 * @since HCommons
	 *
	 * @param Invite_Anyone_User_Query $query Current instance of Invite_Anyone_User_Query. Passed by reference.
	 */
	public function hcommons_filter_site_users_only( $query ) {

		global $wpdb;
		$context = debug_backtrace(); //TODO get a proper filter in Invite Anyone and get rid of backtrace.

		if ( 'Invite_Anyone_User_Query' === get_class( $context[1]['args'][1][0] ) ) {
			//hcommons_write_error_log( 'info', '****FILTER_SITE_USERS_ONLY_QUERY****-'.var_export( $query, true ) );
			//hcommons_write_error_log( 'info', '****FILTER_SITE_USERS_ONLY_TRACE****-'.var_export( get_class( $context[1]['args'][1][0] ), true ) );
			$current_society_id = get_network_option( '', 'society_id' );
			$tax_query = new WP_Tax_Query( array(
				array(
					'taxonomy' => 'bp_member_type',
					'field'    => 'name',
					'operator' => 'IN',
					'terms'    => $current_society_id,
				),
			) );

			// Switch to the root blog, where member type taxonomies live.
			$site_id  = bp_get_taxonomy_term_site_id( 'bp_member_type' );
			$switched = false;
			if ( $site_id !== get_current_blog_id() ) {
				switch_to_blog( $site_id );
				$switched = true;
			}

			$sql_clauses = $tax_query->get_sql( 'u', $this->uid_name );

			$clause = '';

			if ( false !== strpos( $sql_clauses['where'], '0 = 1' ) ) {
				$clause = array( 'join' => '', 'where' => '0 = 1' );
				// IN clauses must be converted to a subquery.
			} elseif ( preg_match( '/' . $wpdb->term_relationships . '\.term_taxonomy_id IN \([0-9, ]+\)/', $sql_clauses['where'], $matches ) ) {
				$clause = "wp_users.ID IN ( SELECT object_id FROM $wpdb->term_relationships WHERE {$matches[0]} )";
			}

			if ( $switched ) {
				restore_current_blog();
			}
			//hcommons_write_error_log( 'info', '****FILTER_SITE_USERS_ONLY_CLAUSE****-'.var_export( $clause, true ) );
			$query->query_where .= ' AND ' . $clause;
		}

	}

	/**
	 * Filter the group join button by network.
	 *
	 * @since HCommons
	 *
	 * @param array       $button Button settings.
	 * @param object      $group
	 * @return array|null Button attributes.
	 */
	public function hcommons_check_bp_get_group_join_button( $button, $group ) {

		$current_society_id = get_network_option( '', 'society_id' );
		if ( 'hc' !== $current_society_id ) {
			return $button;
		}
		$society_id = bp_groups_get_group_type( $group->id );
		//hcommons_write_error_log( 'info', '****BP_GET_GROUP_JOIN_BUTTON****-'.var_export( $society_id, true ).'-'.var_export( $group, true ) );
		if ( 'hc' !== $society_id ) {
			return null;
		} else {
			return $button;
		}

	}

	/**
	 * Syncs the HCommons managed WordPress profile data to HCommons XProfile Group fields.
	 *
	 * @since HCommons
	 *
	 * @param object $user   User object whose profile is being synced. Passed by reference.
	 */
	function hcommons_sync_bp_profile( $user ) {

		hcommons_write_error_log( 'info', '****SYNC_BP_PROFILE****-'.var_export( $user, true ) );

		xprofile_set_field_data( 2, $user->ID, $user->display_name );
	}

}

$humanities_commons = new Humanities_Commons;

function hcommons_debug_shibboleth_user_role( $user_role ) {
	hcommons_write_error_log( 'info', '****USER_ROLE****-'.$user_role.'-'.var_export( $_SERVER, true ) );
	return $user_role;
}
//add_filter( 'shibboleth_user_role', 'hcommons_debug_shibboleth_user_role', 10, 3 );

function hcommons_debug_ajax_referer( $action, $result ) {
	hcommons_write_error_log( 'info', '****AJAX_REFERER****-'.$action.'-'.var_export( $result, true ).'-'.var_export( $_REQUEST, true ) );
}
//add_action( 'check_ajax_referer', 'hcommons_debug_ajax_referer', 10, 2 );

function hcommons_debug_nonce_failed( $nonce, $action, $user, $token ) {
	hcommons_write_error_log( 'info', '****NONCE_FAILED****-'.var_export( $nonce, true ).'-'.var_export( $action, true).'-'.var_export( $user->ID, true ).'-'.var_export( $token, true ) );
	$cookie = wp_parse_auth_cookie( '', 'logged_in' );
	hcommons_write_error_log( 'info', '****NONCE_FAILED-COOKIE****-'.var_export( $cookie, true ) );
}
//add_action( 'wp_verify_nonce_failed', 'hcommons_debug_nonce_failed', 10, 4 );

function hcommons_debug_auth_cookie_malformed( $cookie, $scheme  ) {
	hcommons_write_error_log( 'info', '****AUTH_COOKIE_MALFORMED****-'.var_export( $cookie, true ).'-'.var_export( $scheme, true) );
}
//add_action( 'auth_cookie_malformed', 'hcommons_debug_auth_cookie_malformed' );

function hcommons_debug_auth_cookie_expired( $cookie_elements  ) {
	hcommons_write_error_log( 'info', '****AUTH_COOKIE_EXPIRED****-'.var_export( $cookie_elements, true ) );
}
//add_action( 'auth_cookie_expired', 'hcommons_debug_auth_cookie_expired' );

function hcommons_debug_auth_cookie_bad_username( $cookie_elements  ) {
	hcommons_write_error_log( 'info', '****AUTH_COOKIE_BAD_USERNAME****-'.var_export( $cookie_elements, true ) );
}
//add_action( 'auth_cookie_bad_username', 'hcommons_debug_auth_cookie_bad_username' );

function hcommons_debug_auth_cookie_bad_hash( $cookie_elements  ) {
	hcommons_write_error_log( 'info', '****AUTH_COOKIE_BAD_HASH****-'.var_export( $cookie_elements, true ) );
}
//add_action( 'auth_cookie_bad_hash', 'hcommons_debug_auth_cookie_bad_hash' );

function hcommons_debug_auth_cookie_bad_session_token( $cookie_elements  ) {
	hcommons_write_error_log( 'info', '****AUTH_COOKIE_BAD_SESSION_TOKEN****-'.var_export( $cookie_elements, true ) );
}
//add_action( 'auth_cookie_bad_session_token', 'hcommons_debug_auth_cookie_bad_session_token' );

function hcommons_debug_nonce_user_logged_out( $uid, $action ) {
	hcommons_write_error_log( 'info', '****NONCE_USER_LOGGED_OUT****-'.var_export( $uid, true ).'-'.var_export( $action, true) );
	return uid;
}
//add_filter( 'nonce_user_logged_out', 'hcommons_debug_nonce_user_logged_out', 10, 2 );

function hcommons_debug_secure_logged_in_cookie( $secure_logged_in_cookie, $user_id, $secure ) {
	hcommons_write_error_log( 'info', '****SECURE_LOGGED_IN_COOKIE****-'.$user_id.'-'.var_export( $secure_logged_in_cookie, true ).'-'.var_export( $secure, true) );
	return $secure_logged_in_cookie;
}
//add_filter( 'secure_logged_in_cookie', 'hcommons_debug_secure_logged_in_cookie', 10, 3 );

function hcommons_debug_set_auth_cookie( $auth_cookie, $expire, $expiration, $user_id, $scheme ) {
	hcommons_write_error_log( 'info', '****SET_AUTH_COOKIE****-'.var_export( $auth_cookie, true ).'-'.var_export( $expire, true).'-'.var_export( $expiration, true).'-'.var_export( $user_id, true ).'-'.var_export( $scheme, true ) );
}
//add_action( 'set_auth_cookie', 'hcommons_debug_set_auth_cookie', 10, 5 );

function hcommons_debug_set_logged_in_cookie( $logged_in_cookie, $expire, $expiration, $user_id, $scheme ) {
	hcommons_write_error_log( 'info', '****SET_LOGGED_IN_COOKIE****-'.var_export( $logged_in_cookie, true ).'-'.var_export( $expire, true).'-'.var_export( $expiration, true).'-'.var_export( $user_id, true ).'-'.var_export( $scheme, true ) );
}
//add_action( 'set_logged_in_cookie', 'hcommons_debug_set_logged_in_cookie', 10, 5 );

function hcommons_debug_load_widgets() {
	hcommons_write_error_log( 'info', '****LOAD_WIDGETS.PHP****' );
}
//add_action( 'load-widgets.php', 'hcommons_debug_load_widgets' );

function hcommons_debug_sidebar_admin_page() {
	hcommons_write_error_log( 'info', '****sidebar_admin_page****' );
}
//add_action( 'sidebar_admin_page', 'hcommons_debug_sidebar_admin_page' );

function hcommons_debug_user_has_cap( $all_caps, $caps, $args, $stuff ) {
	hcommons_write_error_log( 'info', '****USER_HAS_CAP****-'.var_export( $stuff, true ).'-'.var_export( $all_caps, true ).'-'.var_export( $args, true) );
	return $all_caps;
}
//add_filter( 'user_has_cap', 'hcommons_debug_user_has_cap', 10, 4 );

