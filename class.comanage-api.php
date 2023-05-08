<?php
/**
 * COmanage API
 *
 * A limited set of functions to access the COmanage REST API from Humanities Commons
 *
 * @package Humanities Commons
 * @subpackage Configuration
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class comanageApi {
	
	public $username;
	protected $password;
	public $format = "json";
	public $url;
	public $api_args;
	public $has_accepted_terms = false;

	public function __construct() {

		try {
			
			//look up co_person_role endpoint to see if user's current role is expired
			$this->url = getenv( 'COMANAGE_API_URL' );
			$this->username = getenv( 'COMANAGE_API_USERNAME' );
			$this->password = getenv( 'COMANAGE_API_PASSWORD' );

			$this->api_args = [ 
				'sslverify' => false,
				'headers' => [ 
					'Authorization' => 'Basic ' . base64_encode( $this->username . ':' . $this->password )
				]
			];

			if( ! $this->username && ! $this->password ) {
				throw new Exception('Uh oh! Username and password for comanage api not found!');
			}

		} catch( Exception $e ) {
			//echo 'Caught Exception: ' . $e->getMessage() . '<br />';
			return;
		}

		//lets get the current user's id in COmanage
		/*$co_person = $this->get_co_person( $this->user->data->user_login );

		//now lets check if the user has accepted the terms, if not -- redirect them
		if( ! $this->check_t_c_agreement( $co_person->CoPeople[0]->Id ) ) {
			
			//do something if the response comes back as a string
			//$this->has_accepted_terms = json_decode( $req['body'] );

			//var_dump( $this->has_accepted_terms );

			//since the user already has accepted the terms at this point previously, we now update the user meta to reflect that
			//update_user_meta( $user->ID, 'accepted_t_and_c', '1' );

		} else {

			//method to have user accept terms
			//$comanage_terms = $this->comanage_accept_terms( $co_person->CoPeople[0]->Id );

			//if status code returns 201 which means "added", we then add that to the user meta
			//if( $comanage_terms['response']['code'] == 201 )
				//update_user_meta( $user->ID, 'accepted_t_and_c', '1' );

		}*/

	} 


	/**
	 * Posts to COmanage api the accepted terms and agreement from the user
	 * 
	 * @param  int 	   $co_person_id  id of current user
	 * @return object                 returned response from the api if successful
	 */
	public function comanage_accept_terms( $co_person_id ) {

		//raw post body to send to comanage
		$post_body = array( 
			'RequestType' => 'CoTAndCAgreements',
			'Version' => '1.0',
			'CoTAndCAgreements' => array( 
				array(
					'Version' => '1.0',
					'CoTermsAndConditionsId' => 1,
					'Person' => array(
						'Type' => 'CO',
						'Id' => $co_person_id
					)
				)
			)
		);

		$arr = array_merge( $this->api_args, [ 'body' => json_encode( $post_body ) ] );

		$req = wp_remote_post( $this->url . '/co_t_and_c_agreements.' . $this->format, $arr );

		return $req;
	}

	public function get_terms_conditions() {

		$req = wp_remote_get( $this->url . '/co_terms_and_conditions.' . $this->format, $this->api_args );

		return json_decode( $req['body'] );

	}

	public function get_terms_conditions_agreement() {

		$terms = $this->get_terms_conditions();

		$req = wp_remote_get( $this->url . '/co_t_and_c_agreements.' . $this->format, $this->api_args );

	}

	/**
	 * Checks T&C Agreement endpoint for a response if the user id exists 
	 * as a user that accepted the terms
	 * 
	 * @param  int 	$co_person_id id of user in COmanage
	 * @return mixed 			  either status code or json decoded response  
	 */
	public function check_t_c_agreement( $co_person_id ) {

		$req = wp_remote_get( $this->url . '/co_t_and_c_agreements.' . $this->format . '?copersonid=' . $co_person_id, $this->api_args );

		$retval = false;

		if ( isset( $req['response'] ) && isset( $req['response']['code'] ) ) {

			if ( in_array( $req['response']['code'], [ 401, 404 ] ) ) {
				$retval = true;
				//hcommons_write_error_log( 'info','comanage did not respond as expected'.var_export( $args, true ) );
			} else if ( in_array( $req['response']['code'], [ 204 ] ) ) {
				$retval = true;
			}

		}

		return $retval;

	}

	/**
	 * Returns data on t and c agreements endpoint if the person has accepted/not accepted the agreement
	 * 
	 * @param  int   $co_person_id  id of person in comanage
	 * @return object $req 			request object
	 */
	public function get_t_c_agreement_person( $co_person_id ) {

		//echo "t & c agreement person <br/>";
		$req = wp_remote_get( $this->url . '/co_t_and_c_agreements.' . $this->format . '?copersonid=' . $co_person_id, $this->api_args );

		return $req;

	}


	/**
	 * Gets COPerson object from comanage api
	 * 
	 * @param  string       $username  wordpress user object
	 * @return array|object $req   json decoded array of objects from the request to comanage api        
	 */
	public function get_co_person( $username ) { 

		//echo "co person method <br />";
		//$req = wp_remote_get( $this->url . '/co_people.' . $this->format . '?coid=2&search.identifier=tester8', $this->api_args );
		//$req = wp_remote_get( $this->url . '/co_people.' . $this->format . '?coid=2&search.identifier=tester', $this->api_args );
		$req = wp_remote_get( $this->url . '/co_people.' . $this->format . '?coid=2&search.identifier=' . $username, $this->api_args );
		if ( is_wp_error( $req ) ) {
			return false;
		}

		$data = json_decode( $req['body'] );

		return $data;

	}

	/**
	 * Gets role from co_person by passing in person_id
	 * 
	 * @param  int    $co_person_id  CO user id
	 * 
	 * @return object $req			  object from api if request is successful               
	 */
	public function get_co_person_role( $co_person_id ) {
		
		//GET /co_person_roles.<format>?copersonid=
		$req = wp_remote_get( $this->url . '/co_person_roles.' . $this->format . '?copersonid=' . $co_person_id,  $this->api_args );

		$data = json_decode( $req['body'] );
		
		return $data;

	}

	/**
	 * Gets COU for output into global class variable, returns all cous by default
	 *
	 * @param  string  $society_id
	 * @return array   $cous    array of items retrieved from the comanage api
	 */
	public function get_cous( $society_id = '' ) {


		$req = wp_cache_get( 'comanage_cous', 'hcommons_settings' );

		if ( ! $req ) {

			//Hard code COU values becasue REST API call gets a memory error on COmanage - PMO bug #329
			$temp_cous = array();
			$temp_cous['Cous'][] = [
				'Id'          => '1',
				'Name'        => 'MLA',
				'Status'      => 'Active',
				'Type'        => 'Closed',
				'Description' => 'Modern Language Association'
			];
			$temp_cous['Cous'][] = [
				'Id'          => '2',
				'Name'        => 'CAA',
				'Status'      => 'Inactive',
				'Type'        => 'Closed',
				'Description' => 'College Art Association'
			];
			$temp_cous['Cous'][] = [
				'Id'          => '3',
				'Name'        => 'ASEEES',
				'Status'      => 'Inactive',
				'Type'        => 'Closed',
				'Description' => 'Association for Slavic, Eastern European, and Eurasian Studies'
			];
			$temp_cous['Cous'][] = [
				'Id'          => '4',
				'Name'        => 'AJS',
				'Status'      => 'Inactive',
				'Type'        => 'Closed',
				'Description' => 'Association for Jewish Studies'
			];
			$temp_cous['Cous'][] = [
				'Id'          => '5',
				'Name'        => 'HC',
				'Status'      => 'Active',
				'Type'        => 'Open',
				'Description' => 'Humanities Commons'
			];
			$temp_cous['Cous'][] = [
				'Id'          => '6',
				'Name'        => 'UP',
				'Status'      => 'Active',
				'Type'        => 'Closed',
				'Description' => 'Association of American University Presses'
			];
			$temp_cous['Cous'][] = [
				'Id'          => '7',
				'Name'        => 'MSU',
				'Status'      => 'Active',
				'Type'        => 'Closed',
				'Description' => 'Michigan State University'
			];
			$temp_cous['Cous'][] = [
				'Id'          => '8',
				'Name'        => 'ARLISNA',
				'Status'      => 'Active',
				'Type'        => 'Closed',
				'Description' => 'ARLIS/NA'
			];
			$temp_cous['Cous'][] = [
				'Id'          => '9',
				'Name'        => 'SAH',
				'Status'      => 'Active',
				'Type'        => 'Closed',
				'Description' => 'SAH'
			];
			$temp_cous['Cous'][] = [
				'Id'          => '11',
				'Name'        => 'HUB',
				'Status'      => 'Inactive',
				'Type'        => 'Open',
				'Description' => 'HUB'
			];
			$temp_cous['Cous'][] = [
				'Id'          => '12',
				'Name'        => 'SOCSCI',
				'Status'      => 'Inactive',
				'Type'        => 'Open',
				'Description' => 'SOCSCI'
			];
			$temp_cous['Cous'][] = [ 
				'Id'          => '13',
				'Name'        => 'STEM',
				'Status'      => 'Inactive',
				'Type'        => 'Open',
				'Description' => 'STEM'
			];
			$temp_cous['Cous'][] = [ 
				'Id'          => '18',
				'Name'        => 'HASTAC',
				'Status'      => 'Active',
				'Type'        => 'Open',
				'Description' => 'HASTAC'
			];
			$temp_cous['Cous'][] = [ 
				'Id'          => '19',
				'Name'        => 'DHRI',
				'Status'      => 'Inactive',
				'Type'        => 'Closed',
				'Description' => 'Digital Humanities Research Institutes'
			];

			$req['body'] = json_encode( $temp_cous );

			//$req = wp_remote_get( $this->url . '/cous.' . $this->format . '?coid=2', $this->api_args );
			wp_cache_set( 'comanage_cous', $req, 'hcommons_settings', 24 * HOUR_IN_SECONDS );
		}

		//json_decode the data from the request
		$data = json_decode( $req['body'], true );
		$cous = array();

		//loops through cou data to find the one matching the string in param
		foreach( $data['Cous'] as $item ) {

			if ( empty( $society_id ) || $item['Name'] == strtoupper( $society_id ) ) {

				$cous[] = [
					'id' => $item['Id'],
					'name' => $item['Name'],
					'status' => $item['Status'],
					'type' => $item['Type'],
					'description' => $item['Description']
				];

			}
		}

		return $cous;

	}

	/**
	 * Checks if the user's society role is still active
	 *
	 * @param  string     $wordpress_username  wordpress username of logged in user
	 * @param  string     $society_id  society to check
	 * @return array
	 */
	public function get_person_roles( $wordpress_username, $society_id = '' ) {

		//lets get the ID in comanage for the current logged in user
		$co_person = $this->get_co_person( $wordpress_username );
		
		if ( false === $co_person ) {
			return false;
		}
		//multiple records - find first active
		foreach( $co_person as $person_record ) {
			if ( $person_record[0]->CoId == "2" && $person_record[0]->Status == 'Active' ) {
				$co_person_id = $person_record[0]->Id;
				break 1;
			}
		}
		//gets all of the roles the person currently has
		$co_person_roles = $this->get_co_person_role( $co_person_id );

		$roles = $co_person_roles->CoPersonRoles;

		//retrieve current society COU from API or retrieve all
		$cous = $this->get_cous( $society_id );
		$roles_found = array();

		foreach( $cous as $cou ) {

			//loop through each role
			foreach( $roles as $role ) {
				//check if each role matches the cou id of the society and provide a case for each status
				if( $role->CouId == $cou['id'] ) {

					$roles_found[$cou['name']] = [
						'status' => $role->Status,
						'affiliation' => $role->Affiliation,
						'title' => $role->Title,
						'o' => $role->O,
						'valid_from' => substr( $role->ValidFrom, 0, 10 ),
						'valid_through' => substr( $role->ValidThrough, 0, 10 ),
					];

				}

			}

		}

		ksort( $roles_found );
		return $roles_found;

	}

	/**
	 * Add a co person role and a bp member type for an open commons
	 *
	 * @param  string     $wordpress_username  wordpress username of logged in user
	 * @param  string     $society_id  society to add
	 * @return array
	 */
	public function add_person_role( $wordpress_username, $society_id ) {

		//get the ID in WP for the user
		$wp_user = get_user_by( 'login', $wordpress_username );
		if ( false === $wp_user ) {
			return false;
		}
		//get the existing member types for the user
		$member_types = bp_get_member_type( $wp_user->ID, false );
		//echo "<br />", var_export( $member_types, true ), "<br />";

		//get the ID in CoManage for the user
		$co_person = $this->get_co_person( $wordpress_username );
		if ( false === $co_person ) {
			return false;
		}
		//multiple records - find first active
		foreach( $co_person as $person_record ) {
			if ( $person_record[0]->CoId == "2" && $person_record[0]->Status == 'Active' ) {
				$co_person_id = $person_record[0]->Id;
				break 1;
			}
		}
		//echo "<br />", var_export( $co_person_id, true ), "<br />";
		//get all of the roles the person currently has
		$co_person_roles = $this->get_co_person_role( $co_person_id );
		$roles = $co_person_roles->CoPersonRoles;
		echo "<br />", var_export( $roles, true ), "<br />";

		//get the COU from CoManage for the society_id
		$cou = $this->get_cous( $society_id );
		//echo "<br />", var_export( $cou, true ), "<br />";
		if ( false === $cou ) {
			return false;
		}

		$post_api_args = [ 'sslverify' => false,
				   'headers' => [ 
					'Authorization' => 'Basic ' . base64_encode( $this->username . ':' . $this->password ),
					'Content-Type' => 'application/json; charset=utf-8'
				   ],
				   'method' => 'POST',
				   'data_format' => 'body',
				   'timeout'     => 30,
				   'blocking'    => false
				 ];

		//raw post body to send to CoManage
		$post_body = array(
			'RequestType' => 'CoPersonRoles',
			'Version' => '1.0',
			'CoPersonRoles' => array(
				array(
					'Version' => '1.0',
					'Person' => array(
						'Type' => 'CO',
						'Id' => $co_person_id
					),
					'CouId' => $cou[0]['id'],
					'Affiliation' => 'member',
					'Title' => '',
					'O' => '',
					'Ordr' => '',
					'Ou' => '',
					'Status' => 'Active',
					'ValidFrom' => '',
					'ValidThrough' => ''
				)
			)
		);

		//$req_data = array_merge( $post_api_args, [ 'body' => json_encode( $post_body ) ] );
		//echo "REQ<br />", var_export( $req_data, true ), "<br />";

                //$req = wp_remote_post( $this->url . '/co_person_roles.' . $this->format, $req_data );
		//echo "RES<br />", var_export( $req, true ), "<br />";

		//if ( ! in_array( strtolower( $society_id ), $member_types ) ) {
			//bp_set_member_type( $wp_user->ID, strtolower( $society_id ), true );
		//}

		return;

	}
}

$comanage_api = new comanageApi;
