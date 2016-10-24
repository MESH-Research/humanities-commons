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

require_once ( dirname( __FILE__ ) . '/wpmn-taxonomy-functions.php' );
require_once ( dirname( __FILE__ ) . '/admin-toolbar.php' );

class Humanities_Commons {

	/**
	 * the network called "Humanities Commons" a.k.a. the hub
	 */
	public static $main_network;

	/**
	 * root blog of the main network
	 */
	public static $main_site;

	/**
	 * current society id
	 */
	public static $society_id;

	public function __construct() {

                if ( defined( 'HC_SITE_ID' ) ) {
                        self::$main_network = wp_get_network( (int) HC_SITE_ID );
                } else {
                        self::$main_network = wp_get_network( (int) '1' );
                }
		self::$main_site = get_site_by_path( self::$main_network->domain, self::$main_network->path );
		self::$society_id = get_network_option( '', 'society_id' );

		add_filter( 'bp_get_taxonomy_term_site_id', array( $this, 'hcommons_filter_bp_taxonomy_storage_site' ), 10, 2 );
		add_filter( 'wpmn_get_taxonomy_term_site_id', array( $this, 'hcommons_filter_hc_taxonomy_storage_site' ), 10, 2 );
		add_action( 'bp_register_member_types', array( $this, 'hcommons_register_member_types' ) );
		add_action( 'bp_groups_register_group_types', array( $this, 'hcommons_register_group_types' ) );
		add_action( 'bp_after_has_members_parse_args', array( $this, 'hcommons_set_members_query' ) );
		add_filter( 'bp_before_has_groups_parse_args', array( $this, 'hcommons_set_groups_query_args' ) );
		add_action( 'groups_create_group_step_save_group-details', array( $this, 'hcommons_set_group_type' ) );
		add_action( 'shibboleth_set_user_roles', array( $this, 'hcommons_set_user_member_types' ) );
		add_action( 'shibboleth_set_user_roles', array( $this, 'hcommons_maybe_set_user_role_for_site' ) );
		add_filter( 'bp_before_has_blogs_parse_args', array( $this, 'hcommons_set_network_blogs_query' ) );
		add_filter( 'bp_get_total_blog_count', array( $this, 'hcommons_get_total_blog_count' ) );
		add_filter( 'bp_get_total_blog_count_for_user', array( $this, 'hcommons_get_total_blog_count_for_user' ) );
		add_filter( 'bp_before_has_activities_parse_args', array( $this, 'hcommons_set_network_activities_query' ) );
		add_filter( 'bp_activity_get_where_conditions', array( $this, 'hcommons_filter_activity_where_conditions' ) );
		add_filter( 'bp_activity_after_save', array( $this, 'hcommons_set_activity_society_meta' ) );
		add_filter( 'bp_activity_get_permalink', array( $this, 'hcommons_filter_activity_permalink' ), 10, 2 );
		add_filter( 'body_class', array( $this, 'hcommons_society_body_class_name' ) );
		// this filter makes 'bp_xprofile_change_field_visibility' false which is required for profile plugin visibility controls
		// doesn't work with local users without a member type, but also doesn't work when member type & blog_id don't match?
		// should always return true for any logged-in user, since visibility controls on xprofile fields are not restricted
		//add_filter( 'bp_current_user_can', array( $this, 'hcommons_check_site_member_can' ), 10, 4 );
		add_filter( 'shibboleth_user_role', array( $this, 'hcommons_check_user_site_membership' ) );
		add_filter( 'bp_get_groups_directory_permalink', array( $this, 'hcommons_set_group_permalink' ) );
		add_filter( 'get_blogs_of_user', array( $this, 'hcommons_filter_get_blogs_of_user'), 10, 3 );
		add_filter( 'bp_core_avatar_upload_path', array( $this, 'hcommons_set_bp_core_avatar_upload_path' ) );
		add_filter( 'bp_core_avatar_url', array( $this, 'hcommons_set_bp_core_avatar_url' ) );
		add_filter( 'bp_get_group_join_button', array( $this, 'hcommons_check_bp_get_group_join_button' ), 10, 2 );
		add_action( 'shibboleth_set_user_roles', array( $this, 'hcommons_sync_bp_profile' ), 10, 3 );
		add_action( 'pre_user_query', array( &$this, 'hcommons_filter_site_users_only' ) ); // do_action_ref_array() is used for pre_user_query 
		add_action( 'wp_login_failed', array( $this, 'hcommons_login_failed' ) );
		add_filter( 'bp_get_signup_page', array( $this, 'hcommons_register_url' ) );
		add_filter( 'invite_anyone_is_large_network', '__return_true' ); //hide invite anyone member list on create/edit group screen
		add_filter( 'login_url', array( $this, 'hcommons_login_url' ) );
		add_action( 'bp_init',  array( $this, 'hcommons_remove_nav_items' ) );
		add_action( 'bp_init', array( $this, 'hcommons_remove_bpges_actions' ) );
		add_filter( 'password_protected_login_headertitle', array( $this, 'hcommons_password_protect_title' ) );
		add_filter( 'password_protected_login_headerurl', array( $this, 'hcommons_password_protect_url' ) );
		add_action( 'password_protected_login_messages', array( $this, 'hcommons_password_protect_message' ) );

	}


	public function hcommons_filter_bp_taxonomy_storage_site( $site_id, $taxonomy ) {

		if ( in_array( $taxonomy, array( 'bp_group_type', 'bp_member_type' ) ) ) {
			return self::$main_site->blog_id;
		} else {
			return $site_id;
		}

	}

	public function hcommons_filter_hc_taxonomy_storage_site( $site_id, $taxonomy ) {

		if ( in_array( $taxonomy, array( 'mla_academic_interests', 'humcore_deposit_subject', 'humcore_deposit_tag' ) ) ) {
			return (int) '1'; // Go legacy during beta.
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

		$args['member_type'] = self::$society_id;
		return $args;
	}

	public function hcommons_set_groups_query_args( $args ) {

		if ( ( !empty( $_REQUEST['page'] ) && is_admin() && 'bp-groups' == $_REQUEST['page'] ) || ( 'hc' !== self::$society_id && ! bp_is_user_profile() ) && ! bp_is_current_action( 'my-groups' ) ) {
			$args['group_type'] = self::$society_id;
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

		bp_groups_set_group_type( $id, self::$society_id );
	}

	public function hcommons_set_user_member_types( $user ) {

		$user_id = $user->ID;
		$memberships = $this->hcommons_get_user_memberships();
		hcommons_write_error_log( 'info', '****RETURNED_MEMBERSHIPS****-' . $_SERVER['HTTP_HOST'] . '-' . var_export( $user->user_login, true ) . '-' . var_export( $memberships, true ) );
		$member_societies = (array) bp_get_member_type( $user_id, false );
		hcommons_write_error_log( 'info', '****PRE_SET_USER_MEMBER_TYPES****-' . var_export( $member_societies, true ) );
		$result = bp_set_member_type( $user_id, '' ); // Clear existing types, if any.
		$append = true;
		foreach( $memberships['societies'] as $member_type ) {
			$result = bp_set_member_type( $user_id, $member_type, $append );
			hcommons_write_error_log( 'info', '****SET_EACH_MEMBER_TYPE****-' . $user_id . '-' . $member_type . '-' . var_export( $result, true ) );
		}
	}

	public function hcommons_maybe_set_user_role_for_site( $user ) {

		//TODO Can we find WP functions that avoid messing directly with usermeta for a user that has not yet signed in?
		global $wpdb;
		$prefix = $wpdb->get_blog_prefix();
		$user_id = $user->ID;
		$site_caps = get_user_meta( $user_id, $prefix . 'capabilities', true );
		$site_caps_array = maybe_unserialize( $site_caps );
		$memberships = $this->hcommons_get_user_memberships();
		$is_site_member = in_array( self::$society_id, $memberships['societies'] );

		if ( $is_site_member ) {
			//TODO Copy role check logic from hcommons_check_user_site_membership().
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
	 * @return string $blog_society_id or self::$society_id
	 */
	public function hcommons_get_blog_society_id( $blog_id = '' ) {

		$fields = array();
		if ( ! empty( $blog_id ) ) {
			$fields['blog_id'] = $blog_id;
		} else {
			return self::$society_id;
		}
		$blog_details = get_blog_details( $fields );
		$blog_society_id = get_network_option( $blog_details->site_id, 'society_id' );

		return $blog_society_id;
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
			// do not include HC
			foreach ( $user_blogs['blogs'] as $key => $user_blog ) {
				if ( $user_blog->blog_id === self::$main_site->blog_id ) {
					unset( $user_blogs['blogs'][$key] );
				}
			}

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

		$blog_ids = array();
		$current_blog_id = get_current_blog_id();

		if ( 'hc' !== self::$society_id && empty( $args['user_id'] ) && ! bp_is_current_action('my-sites') ) {

			$current_network = get_current_site();
			$network_sites = wp_get_sites( array( 'network_id' => $current_network->id, 'limit' => 9999 ) );
			foreach( $network_sites as $site ) {
				if ( $site['blog_id'] != $current_blog_id ) {
					$blog_ids[] = $site['blog_id'];
				}
			}
		} else {
			//TODO Find a better way, this won't scale to all of HC.
			$sites = wp_get_sites( array( 'network_id' => null, 'limit' => 9999 ) );
			foreach( $sites as $site ) {
				if ( $site['blog_id'] != $current_blog_id ) {
					$blog_ids[] = $site['blog_id'];
				}
			}
		}

		if ( ! empty( $blog_ids ) ) {
			$include_blogs = implode( ',', $blog_ids );
			$args['include_blog_ids'] = $include_blogs;
		}

		hcommons_write_error_log( 'info', '****SET_NETWORK_BLOGS_QUERY***-'.var_export( $args, true ) );
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

		if ( 'hc' !== self::$society_id && ! bp_is_user_profile() ) {
			$args['meta_query'] = array(
				array(
					'key'     => 'society_id',
					'value'   => self::$society_id,
					'type'    => 'string',
					'compare' => '='
				),
			);
		}

		return $args;
	}

	/**
	 * Filter the activity query "WHERE" conditions to exclude 'joined_group' (etc.?) types
	 *
	 * @since HCommons
	 *
	 * @param array $args
	 * @return array $args
	 */
	public function hcommons_filter_activity_where_conditions( $args ) {
		//$default_excluded_types = "a.type NOT IN ('activity_comment', 'last_activity')";
		$our_excluded_types = "a.type NOT IN ('activity_comment', 'last_activity', 'joined_group')";

		$args['excluded_types'] = $our_excluded_types;

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

		bp_activity_add_meta( $activity->id, 'society_id', self::$society_id, true );
	}

	/**
	 * Set the activity permalink to contain the proper network.
	 *
	 * @since HCommons
	 *
	 * @param string $link
	 * @param object $activity Passed by reference.
	 * @return string $link
	 */
	public function hcommons_filter_activity_permalink( $link, $activity ) {

		$activity_society_id = bp_activity_get_meta( $activity->id, 'society_id', true  );
		if ( self::$society_id == $activity_society_id ) {
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
		//hcommons_write_error_log( 'info', '****FILTER_ACTIVITY_PERMALINK***-'.$link.'-'.$society_activity_link.'-'.bp_get_root_domain().'-'.self::$society_id.'-'.var_export( $activity->id, true ) );

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

		if ( function_exists( 'shibboleth_session_active' ) ) {
			if ( shibboleth_session_active() ) {
				$classes[] = 'active-session';
				$user_memberships = self::hcommons_get_user_memberships();
				if ( ! in_array( self::$society_id, $user_memberships['societies'] ) ) {
					$classes[] = 'non-member';
				}
			}
		}
		$classes[] = 'society-' . self::$society_id;
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

		$user_id = get_current_user_id();
		if ( $user_id < 2 ) {
			return $retval;
		}
		//TODO Why is taxonomy invalid here on HC?
		if ( 'hc' === self::$society_id && ! get_taxonomy( 'bp_member_type' ) ) {
			bp_register_taxonomies();
		}
		$member_societies = (array) bp_get_member_type( $user_id, false );
		if ( bp_has_member_type( $user_id, self::$society_id ) ) {
			//hcommons_write_error_log( 'info', '****CHECK_USER_MEMBER_TYPE_TRUE***-' . var_export( $user_id, true ) . '-' . var_export( $member_societies, true ) . '-' . var_export( self::$society_id, true ) . var_export( $capability, true ) );
			return $retval;
		} else {
			//hcommons_write_error_log( 'info', '****CHECK_USER_MEMBER_TYPE_FALSE***-' . var_export( $user_id, true ) . '-' . var_export( $member_societies, true ) . '-' . var_export( self::$society_id, true ) . var_export( $capability, true ) );
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

		$username = $_SERVER['HTTP_EMPLOYEENUMBER'];

		$user = get_user_by( 'login', $username );
		$user_id = $user->ID;

		$memberships = $this->hcommons_get_user_memberships();
		if ( ! in_array( self::$society_id, $memberships['societies'] ) && ! is_super_admin( $user_id ) ) {
                hcommons_write_error_log( 'info', '****CHECK_USER_SITE_MEMBERSHIP_FAIL****-' . var_export( $memberships['societies'], true ) . var_export( self::$society_id, true ) . var_export( $user, true ) );
			return '';
		}

		//Check for existing user role, we don't want to overwrite role assignments made in WP.
		global $wp_roles;
		$user_role_set = false;
                foreach ( $wp_roles->roles as $role_key=>$role_name ) {
                        if ( false === strpos( $role_key, 'bbp_' ) ) {
                                $user_role_set = user_can( $user, $role_key );
                        }
                        if ( $user_role_set ) {
				$user_role = $role_key;
                                break;
                        }
                }
                hcommons_write_error_log( 'info', '****CHECK_USER_SITE_MEMBERSHIP****-' . var_export( $user_role, true ) . var_export( $user_role_set, true ) . var_export( $user->user_login, true ) );

		return $user_role;

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

		$group_id = bp_get_group_id();
		$group_society_id = bp_groups_get_group_type( $group_id );

                if ( $group_society_id === self::$society_id ) {
                        return $group_permalink;
                }

		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT site_id FROM $wpdb->sitemeta WHERE meta_key = '%s' AND meta_value = '%s'", 'society_id', $group_society_id ) );
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

    if ( 'hc' !== self::$society_id && ! bp_is_current_action('my-sites') ) {

			$network_blogs = $blogs;
			$current_network = get_current_site();
			$current_blog_id = get_current_blog_id();

			foreach ($blogs as $blog) {

				if ( $current_network->id != $blog->site_id || $current_blog_id == $blog->userblog_id ) {
					unset ( $network_blogs[$blog->userblog_id] );
				}

			}

			//hcommons_write_error_log( 'info', '****GET_BLOGS_OF_USER****-'.var_export( $user_id, true ) );
			return $network_blogs;

		} else {
			return $blogs;
		}

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
			$tax_query = new WP_Tax_Query( array(
				array(
					'taxonomy' => 'bp_member_type',
					'field'    => 'name',
					'operator' => 'IN',
					'terms'    => self::$society_id,
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

		if ( 'hc' !== self::$society_id ) {
			return $button;
		}
		$group_society_id = bp_groups_get_group_type( $group->id );
		//hcommons_write_error_log( 'info', '****BP_GET_GROUP_JOIN_BUTTON****-'.var_export( $group_society_id, true ).'-'.var_export( $group, true ) );
		if ( 'hc' !== $group_society_id ) {
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

		hcommons_write_error_log( 'info', '****SYNC_BP_PROFILE****-'.var_export( $user->user_login, true ) );
		xprofile_set_field_data( 2, $user->ID, $user->display_name );
	}

	/**
	 * Handle a failed login attempt. Determine if the user has visitor status.
	 *
	 * @since HCommons
	 *
	 * @param string $username   User who is attempting to log in.
	 */
	public function hcommons_login_failed( $username ) {

                global $wpdb;
                $prefix = $wpdb->get_blog_prefix();
		$referrer = $_SERVER['HTTP_REFERER'];
		hcommons_write_error_log( 'info', '****LOGIN_FAILED****-' . var_export( $referrer, true ) );
		if ( ! empty( $referrer ) && strstr( $referrer, 'idp/profile/SAML2/Redirect/SSO?' ) ) {
			if ( ! strstr( $_SERVER['REQUEST_URI'], '/not-a-member' ) ) { // make sure we don’t redirect twice
				wp_redirect( 'https://' . $_SERVER['HTTP_X_FORWARDED_HOST'] . '/not-a-member' );
				exit();
			}
		}
		// Otherwise, we assume we have an active session coming in as a visitor.
		$username = $_SERVER['HTTP_EMPLOYEENUMBER']; //TODO Why is the username parameter empty?
		$user = get_user_by( 'login', $username );
		$user_id = $user->ID;
		$visitor_notice = get_user_meta( $user_id, $prefix . 'commons_visitor', true );
		if ( ( empty( $visitor_notice ) ) && ! strstr( $_SERVER['REQUEST_URI'], '/not-a-member' ) ) {
			hcommons_write_error_log( 'info', '****LOGIN_FAILED_FIRST_TIME_NOTICE****-' . var_export( $username, true ) . '-' . var_export( $prefix, true ) );
			update_user_meta( $user_id, $prefix . 'commons_visitor', 'Y' );
			wp_redirect( 'https://' . $_SERVER['HTTP_X_FORWARDED_HOST'] . '/not-a-member' );
			exit();
		}

	}

	/**
	 * Filter the register url to be society specific
	 *
	 * @since HCommons
	 *
	 * @param string $register_url
	 * @return string $register_url Modified url.
	 */
	public function hcommons_register_url( $register_url ) {

		if ( ! empty( self::$society_id ) && defined( strtoupper( self::$society_id ) . '_ENROLLMENT_URL' ) ) {
			return constant( strtoupper( self::$society_id ) . '_ENROLLMENT_URL' );
		} else {
			return $register_url;
		}

	}

	/**
	 * Filter the login url to be society specific
	 * This prevents redirect to /wp-admin after logging in
	 *
	 * @since HCommons
	 *
	 * @param string $login_url
	 * @return string $login_url Modified url.
	 */
	public function hcommons_login_url( $login_url ) {
		remove_filter( 'login_url', 'shibboleth_login_url' );

		if ( ! empty( self::$society_id ) && defined( 'LOGIN_PATH' ) ) {
			return bp_get_root_domain() . LOGIN_PATH;
		} else {
			return $login_url;
		}

	}

	/**
	 * Action to modify nav and sub nav items
	 *
	 * @since HCommons
	 *
	 */
	public function hcommons_remove_nav_items() {

		global $bp;
		//bp_core_remove_subnav_item( 'settings', 'general' );
		bp_core_remove_subnav_item( 'settings', 'profile' );
		// Example of how you change the default tab.
		//bp_core_new_nav_default( array( 'parent_slug' => 'settings', 'screen_function' =>'bp_settings_screen_notification', 'subnav_slug' => 'notifications' ) );

	}

	/**
	 * Action to remove BPGES digest actions from all networks except HC
	 *
	 * @since HCommons
	 *
	 */
	public function hcommons_remove_bpges_actions() {

		// BPGES is not multi-network aware, Let's run BPGES digests from HC only.
		if ( 'hc' !== self::$society_id ) {
			remove_action( 'ass_digest_event', 'ass_daily_digest_fire' );
			remove_action( 'ass_digest_event_weekly', 'ass_weekly_digest_fire' );
		}

	}

	/**
	 * Filter password protect page url
	 *
	 * @since HCommons
	 *
	 */
	public function hcommons_password_protect_url( $url ) {

		return 'https://news.hcommons.org';

	}

	/**
	 * Filter password protect page title
	 *
	 * @since HCommons
	 *
	 */
	public function hcommons_password_protect_title( $title ) {

		return $title;

	}

	/**
	 * Filter password protect page message
	 *
	 * @since HCommons
	 *
	 */
	public function hcommons_password_protect_message( $title ) {

		echo '<style type="text/css">body.login { background-color: #ffffff !important; } ' .
			' body.login h1 a { color: #000000 !important; ' .
			'   font-family: lexia,serif; font-weight: 300; text-transform: unset !important; line-height: 1.2;} ' .
			' #entry-content p { line-height: 1.5; margin-top: 12px !important; } ' .
			' #login form p.submit input { background-color: #0085ba !important; } ' .
			' .login form { margin-top: 0px; !important; }</style>';
		echo '<div class="entry-content entry-summary"><p>Welcome to the future home of Humanities Commons. Please forgive our appearance while we get ready for our big debut in November 2016. For information about the project, and to sign up for e-mail updates, please visit <a href="https://news.hcommons.org">news.hcommons.org.</a></p></div>';

	}

	/**
	 * Functions not tied to any filter or action.
	 */

        /**
         * Return user memberships from session
         *
         * @since HCommons
         *
         * @return array $memberships
         */
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

        /**
         * Return user login method from session
         *
         * @since HCommons
         *
         * @return string|bool $login_method
         */
	public function hcommons_get_user_login_method() {

                if ( function_exists( 'shibboleth_session_active' ) && shibboleth_session_active() ) {
			$methods = array (
				'dev.mla.org' => 'Legacy <em>MLA Commons</em>',
				'commons.mla.org' => 'Google Gateway',
				'twitter-gateway.hcommons-dev.org' => 'Twitter Gateway',
				'hcommons-test.mla.org' => 'Humanities Commons',
			);
			$login_method = '';
			$login_method_header = $_SERVER['HTTP_EPPN'];
			$login_method = explode( '@', $login_method_header );
			//hcommons_write_error_log( 'info', '**********************GET_LOGIN_METHOD********************-' . var_export( $login_method_header, true ) . '-' . $login_method[1] );

			return $methods[$login_method];
                }
                return false;

	}

        /**
         * Return identity provider from session
         *
         * @since HCommons
         *
         * @return string|bool $identity_provider
         */
	public function hcommons_get_identity_provider() {

                if ( function_exists( 'shibboleth_session_active' ) && shibboleth_session_active() ) {
			$providers = array ();
                	if ( defined( 'GOOGLE_IDENTITY_PROVIDER' ) ) {
				$providers[GOOGLE_IDENTITY_PROVIDER] = 'Google';
			}
                	if ( defined( 'TWITTER_IDENTITY_PROVIDER' ) ) {
				$providers[TWITTER_IDENTITY_PROVIDER] = 'Twitter';
			}
                	if ( defined( 'HC_IDENTITY_PROVIDER' ) ) {
				$providers[HC_IDENTITY_PROVIDER] = 'HC ID';
			}
                	if ( defined( 'MLA_IDENTITY_PROVIDER' ) ) {
				$providers[MLA_IDENTITY_PROVIDER] = 'Legacy <em>MLA Commons</em>';
			}
			$identity_provider = '';
			$identity_provider = $_SERVER['HTTP_SHIB_IDENTITY_PROVIDER'];
			//hcommons_write_error_log( 'info', '**********************GET_IDENTITY_PROVIDER********************-' . var_export( $identity_provider, true ) );

			return $providers[$identity_provider];
                }
                return false;
	}

        /**
         * Check for non-member active session
         *
         * @since HCommons
         *
         * @return bool $classes
         */
        public function hcommons_non_member_active_session() {

                if ( function_exists( 'shibboleth_session_active' ) && shibboleth_session_active() ) {
                        $user_memberships = self::hcommons_get_user_memberships();
                        if ( ! in_array( self::$society_id, $user_memberships['societies'] ) ) {
                                return true;
                        }
                	return false;
                }
                return false;
        }

        /**
         * Return user login name from session
         *
         * @since HCommons
         *
         * @return string|bool $username
         */
        public function hcommons_get_session_username() {

                if ( function_exists( 'shibboleth_session_active' ) && shibboleth_session_active() ) {
			$username = $_SERVER['HTTP_EMPLOYEENUMBER'];
			return $username;
                }
                return false;
        }

}

$humanities_commons = new Humanities_Commons;

function hcommons_check_non_member_active_session() {

	global $humanities_commons;
	return $humanities_commons->hcommons_non_member_active_session();
}
