<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

require_once dirname( __FILE__ ) . '/' . 'functions.inc.php';

class Mla_Hcommons {

	private $api_url;
	private $api_key;
	private $api_parameters = array();
	private $api_secret;

        public function __construct() {

		if ( defined( 'CBOX_AUTH_API_URL' ) ) {
			$this->api_url = CBOX_AUTH_API_URL;
		}
		if ( defined( 'CBOX_AUTH_API_KEY' ) ) {
			$this->api_key = CBOX_AUTH_API_KEY;
		}
		if ( defined( 'CBOX_AUTH_API_SECRET' ) ) {
			$this->api_secret = CBOX_AUTH_API_SECRET;
		}
		$this->api_parameters['key'] = $this->api_key;

                add_action( 'init', array( $this, 'register_society_member_id_taxonomy' ) );
                add_action( 'wpmn_register_taxonomies', array( $this, 'register_society_member_id_taxonomy' ) );
	}

        /**
         * Register member_id taxonomy.
         */
        public function register_society_member_id_taxonomy() {
                // Add new taxonomy, NOT hierarchical (like tags).
                $labels = array(
                        'name'                          => _x( 'Member ids', 'taxonomy general name' ),
                        'singular_name'                 => _x( 'Member id', 'taxonomy singular name' ),
                        'search_items'                  => null,
                        'popular_items'                 => null,
                        'all_items'                     => null,
                        'parent_item'                   => null,
                        'parent_item_colon'             => null,
                        'edit_item'                     => null,
                        'update_item'                   => null,
                        'add_new_item'                  => null,
                        'new_item_name'                 => null,
                        'separate_items_with_commas'    => null,
                        'add_or_remove_items'           => null,
                        'choose_from_most_used'         => null,
                        'not_found'                     => null,
                        'menu_name'                     => null,
                );

                $args = array(
                        'public'                        => false,
                        'hierarchical'                  => false,
                        'labels'                        => $labels,
                        'show_ui'                       => false,
                        'show_in_nav_menus'             => false,
                        'show_admin_column'             => false,
                        'update_count_callback'         => '_update_generic_term_count',
                        'query_var'                     => 'society_member_id',
                        'rewrite'                       => false,
                );

                register_taxonomy( 'hcommons_society_member_id', array( 'user' ), $args );
                register_taxonomy_for_object_type( 'hcommons_society_member_id', 'user' );

        }

	/**
	 * Lookup group id by name.
	 */
	public function lookup_mla_group_id( $group_name ) {

		$managed_group_names = get_transient( 'mla_managed_group_names' );

		if ( ! $managed_group_names ) {

			$bp = buddypress();
			global $wpdb;
			$managed_group_names = array();
			$all_groups = $wpdb->get_results( 'SELECT * FROM ' . $bp->table_prefix . 'bp_groups' );
			foreach ( $all_groups as $group ) {

				$society_id = bp_groups_get_group_type( $group->id, true );
				if ( 'mla' === $society_id ) {
					$oid = groups_get_groupmeta( $group->id, 'mla_oid' );
					if ( ! empty( $oid ) && in_array( substr( $oid, 0, 1 ), array( 'D', 'G', 'M' ) ) ) {
						$managed_group_names[strip_tags( stripslashes( $group->name ) )] = $group->id;
					}

				}

			}
			set_transient( 'mla_managed_group_names', $managed_group_names, 24 * HOUR_IN_SECONDS );
		}
		return $managed_group_names[$group_name];

	}

	/**
	 * Lookup member data
	 */
	public function lookup_mla_member_data( $user_id, $full_check = false ) {

		$request_body = '';
                $member_types = (array)bp_get_member_type( $user_id, false );
		if ( ! in_array( 'mla', $member_types ) && ! $full_check ) {
			return false;
		}
                $mla_oid = get_user_meta( $user_id, 'mla_oid', true );
                if ( ! empty( $mla_oid ) && ! $full_check ) {
                        return array( 'mla_member_id' => $mla_oid );
                }
		if ( ! empty( $mla_oid ) ) {
			$api_base_url = $this->api_url . 'members/' . $mla_oid; // add username
			// Generate a "signed" request URL.
			$api_request_url = generateRequest( 'GET', $api_base_url, $this->api_secret, $this->api_parameters );
			// Initiate request.
			$api_response = sendRequest( 'GET', $api_request_url, $request_body );
			//echo var_export( $api_response, true ), "\n";
			// Server response.
			$json_response = json_decode( $api_response['body'], true );
			$request_status = $json_response['meta']['code'];
		}
                if ( empty( $mla_oid ) || 'API-2100' === $request_status ) {
                        $user_emails = (array)maybe_unserialize( get_user_meta( $user_id, 'shib_email', true ) );
                        if ( empty( $user_emails ) ) {
                                $user_emails = array();
                                $user_emails[] = $user->user_email;
                        }
                        foreach( array_unique( $user_emails ) as $user_email ) {
				$this->api_parameters['membership_status'] = 'ALL';
                        	$this->api_parameters['email'] = $user_email;
                        	$api_base_url = $this->api_url . 'members/'; // search
                        	// Generate a "signed" request URL.
                        	$api_request_url = generateRequest( 'GET', $api_base_url, $this->api_secret, $this->api_parameters );
                        	// Initiate request.
                        	$api_response = sendRequest( 'GET', $api_request_url, $request_body );
                		//echo var_export( $api_response, true ), "\n";
                        	$json_response = json_decode( $api_response['body'], true );
                        	unset( $this->api_parameters['membership_status'] );
                        	unset( $this->api_parameters['email'] );
				if ( 'API-1000' === $json_response['meta']['code'] ) {
                                	$search_member_id = $json_response['data'][0]['search_results'][0]['id'];
					$api_base_url = $this->api_url . 'members/' . $search_member_id; // add member_id
                                	// Generate a "signed" request URL.
                                	$api_request_url = generateRequest( 'GET', $api_base_url, $this->api_secret, $this->api_parameters );
                                	// Initiate request.
					$api_response = sendRequest( 'GET', $api_request_url, $request_body );
					//echo var_export( $api_response, true ), "\n";
                                	$json_response = json_decode( $api_response['body'], true );
					if ( 'API-1000' !== $json_response['meta']['code'] ) {
						return false;
					} else {
						break;
					}
				}
			}
		}
		if ( 'API-1000' !== $json_response['meta']['code'] ) {
			return false;
		}
		$mla_member_id = $json_response['data'][0]['id'];
		$mla_username = $json_response['data'][0]['authentication']['username'];
		$mla_membership_status = $json_response['data'][0]['authentication']['membership_status'];
		$mla_expiring_date = $json_response['data'][0]['membership']['expiring_date'];
		$mla_title = $json_response['data'][0]['general']['title'];
		$mla_first_name = $json_response['data'][0]['general']['first_name'];
		$mla_last_name = $json_response['data'][0]['general']['last_name'];
		$mla_suffix = $json_response['data'][0]['general']['suffix'];
		$mla_email = $json_response['data'][0]['general']['email'];
		$mla_joined_commons = $json_response['data'][0]['general']['joined_commons'];
		$member_term = wpmn_get_terms( array( 'taxonomy' => 'hcommons_society_member_id', 'name' => 'mla_' . $mla_member_id ) );
		//echo var_export( $member_term, true );
		$ref_user_id = '';
		$mla_ref_user_id = '';
		if ( ! empty( $member_term ) ) {
			$ref_user_id = wpmn_get_objects_in_term( $member_term[0]->term_id, 'hcommons_society_member_id' );
		}
		if ( ! empty( $ref_user_id ) ) {
			$mla_ref_user_id = $ref_user_id[0];
		}
		//echo $mla_member_id, ',', $mla_username, ',', $mla_membership_status, ',', $mla_expiring_date, ',', $mla_crossref_user_id, ',', $mla_joined_commons;
		return array( 'mla_member_id' => $mla_member_id,
				'mla_username' => $mla_username,
				'mla_membership_status' => $mla_membership_status,
				'mla_expiring_date' => $mla_expiring_date,
				'mla_title' => $mla_title,
				'mla_first_name' => $mla_first_name,
				'mla_last_name' => $mla_last_name,
				'mla_suffix' => $mla_suffix,
				'mla_email' => $mla_email,
				'mla_joined_commons' => $mla_joined_commons,
				'mla_ref_user_id' => $mla_ref_user_id );

	}

}
