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
			$temp_cous['Cous'][] = [ 'Id' => '1', 'Name' => 'MLA',
						'Description' => 'Modern Language Association' ];
			$temp_cous['Cous'][] = [ 'Id' => '2', 'Name' => 'CAA',
						'Description' => 'College Art Association' ];
			$temp_cous['Cous'][] = [ 'Id' => '3', 'Name' => 'ASEEES',
						'Description' => 'Association for Slavic, Eastern European, and Eurasian Studies' ];
			$temp_cous['Cous'][] = [ 'Id' => '4', 'Name' => 'AJS',
						'Description' => 'Association for Jewish Studies' ];
			$temp_cous['Cous'][] = [ 'Id' => '5', 'Name' => 'HC',
						'Description' => 'Humanities Commons' ];
			$temp_cous['Cous'][] = [ 'Id' => '6', 'Name' => 'UP',
						'Description' => 'Association of American University Presses' ];
			$temp_cous['Cous'][] = [ 'Id' => '7', 'Name' => 'MSU',
						'Description' => 'Michigan State University' ];
			$temp_cous['Cous'][] = [ 'Id' => '8', 'Name' => 'ARLISNA',
						'Description' => 'ARLIS/NA' ];
			$temp_cous['Cous'][] = [ 'Id' => '10', 'Name' => 'SAH',
						'Description' => 'SAH' ];
			$temp_cous['Cous'][] = [ 'Id' => '11', 'Name' => 'HUB',
						'Description' => 'HUB' ];
			$temp_cous['Cous'][] = [ 'Id' => '12', 'Name' => 'SOCSCI',
						'Description' => 'SOCSCI' ];
			$temp_cous['Cous'][] = [ 'Id' => '13', 'Name' => 'STEM',
						'Description' => 'STEM' ];
			$temp_cous['Cous'][] = [ 
				'Id'          => '13',
				'Name'        => 'STEM',
				'Description' => 'STEM'
			];
			$temp_cous['Cous'][] = [ 
				'Id'          => '14',
				'Name'        => 'HASTAC',
				'Description' => 'HASTAC'
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

}

$comanage_api = new comanageApi;
