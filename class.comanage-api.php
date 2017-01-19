<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( !class_exists( 'WP_Http' ) ) {
    include_once( ABSPATH . WPINC. '/class-http.php' );
}

class comanageApi {
	
	public $username;
	protected $password;
	public $format = "json";
	public $url;
	public $request;
	public $api_args;
	public $has_accepted_terms = false;

	public function __construct( $user ) {
		
		//look up co_person_role endpoint to see if user's current role is expired
		$this->url = getenv( 'COMANAGE_API_URL' );
		$this->request = new WP_Http();
		$user = $this->get_wp_user();

		try {

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
			echo 'Caught Exception: ' . $e->getMessage() . '<br />';
			return;
		}

		//we need to add that method to the init hook so we can grab the username from wp itself
		add_action( 'init', array( $this, 'get_wp_username' ) );

		//lets get the current user's id in COmanage
		$co_person = $this->get_co_person( $user );

		//now lets check if the user has accepted the terms, if not -- redirect them
		if( ! $this->check_t_c_agreement( $co_person->CoPeople[0]->Id ) ) {
			
			//do something if the response comes back as a string
			//$this->has_accepted_terms = json_decode( $req['body'] );

			//var_dump( $this->has_accepted_terms );

			//since the user already has accepted the terms at this point previously, we now update the user meta to reflect that
			//update_user_meta( $user->ID, 'accepted_t_and_c', '1' );

		} else {

			//method to have user accept terms
			$comanage_terms = $this->comanage_accept_terms( $co_person->CoPeople[0]->Id );

			//if status code returns 201 which means "added", we then add that to the user meta
			//if( $comanage_terms['response']['code'] == 201 )
				//update_user_meta( $user->ID, 'accepted_t_and_c', '1' );

		}

		//die();

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

		$req = $this->request->post( $this->url . '/co_t_and_c_agreements.' . $this->format, $arr );

		return $req;
	}

	public function get_wp_user() {

		global $current_user;
		get_currentuserinfo();
		return $current_user->data;
	
	}

	public function get_terms_conditions() {

		$req = $this->request->get( $this->url . '/co_terms_and_conditions.' . $this->format, $this->api_args );

		return json_decode( $req['body'] );

	}

	public function get_terms_conditions_agreement() {

		$terms = $this->get_terms_conditions();

		$req = $this->request->get( $this->url . '/co_t_and_c_agreements.' . $this->format, $this->api_args );

	}

	/**
	 * Checks T&C Agreement endpoint for a response if the user id exists 
	 * as a user that accepted the terms
	 * 
	 * @param  int 	$co_person_id id of user in COmanage
	 * @return mixed 			  either status code or json decoded response  
	 */
	public function check_t_c_agreement( $co_person_id ) {

		$req = $this->request->get( $this->url . '/co_t_and_c_agreements.' . $this->format . '?copersonid=' . $co_person_id, $this->api_args );

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
		$req = $this->request->get( $this->url . '/co_t_and_c_agreements.' . $this->format . '?copersonid=' . $co_person_id, $this->api_args );

		return $req;

	}


	/**
	 * Gets COPerson object from comanage api
	 * 
	 * @param  object       $user  wordpress user object
	 * @return array|object $req   json decoded array of objects from the request to comanage api        
	 */
	public function get_co_person( $user ) { 

		//echo "co person method <br />";
		//$req = $this->request->get( $this->url . '/co_people.' . $this->format . '?coid=2&search.identifier=tester8', $this->api_args );
		//$req = $this->request->get( $this->url . '/co_people.' . $this->format . '?coid=2&search.identifier=tester', $this->api_args );
		$req = $this->request->get( $this->url . '/co_people.' . $this->format . '?coid=2&search.identifier=' . $user->user_login, $this->api_args );

		return json_decode( $req['body'] );

	}

	public function getPassword() {
		return $this->password;
	}


}
