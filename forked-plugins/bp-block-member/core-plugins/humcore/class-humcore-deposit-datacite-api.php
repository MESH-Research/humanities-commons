<?php
/**
 * API to access DataCite.
 *
 * @package HumCORE
 * @subpackage Deposits
 */

/**
 * Class using WP_HTTP to access the EZIP API.
 */
class Humcore_Deposit_Datacite_Api {

	private $datacite_settings = array();
	private $base_url;
	private $options           = array();
	private $datacite_path;
	private $datacite_prefix;

	public $datacite_proxy;
	public $service_status;
	public $namespace;
	public $temp_dir;

	/**
	 * Initialize DataCite API settings.
	 */
	public function __construct() {

		$humcore_settings = get_option( 'humcore-deposits-humcore-settings' );

		$this->service_status = $humcore_settings['service_status'];

		if ( defined( 'CORE_HUMCORE_NAMESPACE' ) && ! empty( CORE_HUMCORE_NAMESPACE ) ) {
			$this->namespace = CORE_HUMCORE_NAMESPACE;
		} else {
			$this->namespace = $humcore_settings['namespace'];
		}
		if ( defined( 'CORE_HUMCORE_TEMP_DIR' ) && ! empty( CORE_HUMCORE_TEMP_DIR ) ) {
			$this->temp_dir = CORE_HUMCORE_TEMP_DIR;
		} else {
			$this->temp_dir = $humcore_settings['tempdir'];
		}

		$this->datacite_settings = get_option( 'humcore-deposits-datacite-settings' );

		if ( defined( 'CORE_DATACITE_PROTOCOL' ) ) {
				$this->datacite_settings['protocol'] = CORE_DATACITE_PROTOCOL;
		}
		if ( defined( 'CORE_DATACITE_HOST' ) ) {
				$this->datacite_settings['host'] = CORE_DATACITE_HOST;
		}
		if ( defined( 'CORE_DATACITE_PORT' ) ) {
				$this->datacite_settings['port'] = CORE_DATACITE_PORT;
		}
		if ( defined( 'CORE_DATACITE_PATH' ) ) {
				$this->datacite_settings['path'] = CORE_DATACITE_PATH;
		}
		if ( defined( 'CORE_DATACITE_LOGIN' ) ) {
				$this->datacite_settings['login'] = CORE_DATACITE_LOGIN;
		}
		if ( defined( 'CORE_DATACITE_PASSWORD' ) ) {
				$this->datacite_settings['password'] = CORE_DATACITE_PASSWORD;
		}
		if ( defined( 'CORE_DATACITE_PROXY' ) ) {
				$this->datacite_settings['proxy'] = CORE_DATACITE_PROXY;
		}
		if ( defined( 'CORE_DATACITE_PREFIX' ) ) {
				$this->datacite_settings['prefix'] = CORE_DATACITE_PREFIX;
		}

		if ( ! empty( $this->datacite_settings['port'] ) ) {
			$this->base_url = $this->datacite_settings['protocol'] . $this->datacite_settings['host'] . ':' . $this->datacite_settings['port'];
		} else {
			$this->base_url = $this->datacite_settings['protocol'] . $this->datacite_settings['host'];
		}

		$this->datacite_path                                   = $this->datacite_settings['path'];
		$this->datacite_proxy                                  = $this->datacite_settings['proxy'];
		$this->datacite_prefix                                 = $this->datacite_settings['prefix'];
		$this->options['api-auth']['headers']['Authorization'] = 'Basic ' . base64_encode( $this->datacite_settings['login'] . ':' . $this->datacite_settings['password'] );
		$this->options['api-auth']['timeout']                  = '10';
		$this->options['api-auth']['httpversion']              = '1.1';
		$this->options['api-auth']['sslverify']                = true;
		$this->options['api']['httpversion']                   = '1.1';
		$this->options['api']['sslverify']                     = true;

	}


	/**
	 * Get an identifier.
	 *
	 * @param array $args Array of arguments. Supports only the doi argument.
	 * @link http://datacite.cdlib.org/doc/apidoc.html#operation-get-identifier-metadata
	 * @return WP_Error|array identifier metadata
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function get_identifier( $args ) {

		$defaults = array(
			'doi' => '',
		);

		$params = wp_parse_args( $args, $defaults );

		$doi = $params['doi'];

		if ( empty( $doi ) ) {
			return new WP_Error( 'missingArg', 'DOI is missing.' );
		}

		$url = sprintf( '%1$s/dois/%2$s', $this->base_url, $doi );
		$request_args           = $this->options['api-auth'];
		$request_args['method'] = 'GET';
		$request_args['headers']['Accept'] = 'application/vnd.api+json';

		$response = wp_remote_request( $url, $request_args );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( $response->get_error_code(), $response->get_error_message(), $response->get_error_data( $response->get_error_code() ) );
		}

		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );
		$response_body    = wp_remote_retrieve_body( $response );

		if ( 200 != $response_code ) {
			return new WP_Error( $response_code, $response_message, $response_body );
		}

		$datacite_metadata = $response_body;
		return $datacite_metadata;

	}


	/**
	 * Create an identifier.
	 *
	 * @param array $args Array of arguments. Supports all arguments from apidoc.html#operation-create-identifier.
	 * @link http://datacite.cdlib.org/doc/apidoc.html#operation-create-identifier
	 * @return WP_Error|string body of the Response object
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function create_identifier( array $args = array() ) {

		$defaults = array(
			'url'         => '',
			'json'        => '',
		);
		$params   = wp_parse_args( $args, $defaults );

		if ( empty( $params['json'] ) ) {
			return new WP_Error( 'missingArg', 'Metadata is missing.' );
		}

		$url = sprintf( '%1$s/dois/', $this->base_url );

		$content = $params['json'];

		//temporary override
		//$content = '{"data":{"type":"dois","attributes":{"state":"draft","prefix":"' . $this->datacite_prefix . '","url":"' . $params['url'] . '"}}}';

		$request_args                            = $this->options['api-auth'];
		$request_args['method']                  = 'POST';
		$request_args['headers']['Content-Type'] = 'application/vnd.api+json';
		$request_args['body']                    = $content;
		humcore_write_error_log( 'info', 'URL ', array( 'url' => $url ) );
		humcore_write_error_log( 'info', 'Request Args ', array( 'request_args' => $request_args ) );
		humcore_write_error_log( 'info', 'JSON ', array( 'JSON' => $params['json'] ) );

		$response = wp_remote_request( $url, $request_args );
		humcore_write_error_log( 'info', 'Response  ', array( 'response' => $response ) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( $response->get_error_code(), $response->get_error_message(), $response->get_error_data( $response->get_error_code() ) );
		}

		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );
		$response_body    = wp_remote_retrieve_body( $response );

		if ( 201 != $response_code ) {
			return new WP_Error( $response_code, $response_message, $response_body );
		}

		humcore_write_error_log( 'info', 'Create DOI ', array( 'response' => $response_body ) );

		$doi_response = $response_body;
		return $doi_response;

	}


	/**
	 * Create a draft identifier.
	 *
	 * @param array $args Array of arguments. no args necessary to create a draft doi.
	 * @link https://support.datacite.org/docs/api-create-dois
	 * @return WP_Error|string body of the Response object
	 * @see wp_remote_request()
	 */
	public function reserve_identifier( array $args = array() ) {

		// bypass this function if host = 'none'
		if ( 'none' === $this->datacite_settings['host'] ) {
			return trim( $this->datacite_settings['host'] );
		}

		$url = sprintf( '%1$s/dois/', $this->base_url );

		$content = '{"data":{"type":"dois","attributes":{"state":"draft","prefix":"' . $this->datacite_prefix . '"}}}';

		$request_args                            = $this->options['api-auth'];
		$request_args['method']                  = 'POST';
		$request_args['headers']['Content-Type'] = 'application/vnd.api+json';
		$request_args['body']                    = $content;
		humcore_write_error_log( 'info', 'URL ', array( 'url' => $url ) );
		humcore_write_error_log( 'info', 'Request Args ', array( 'request_args' => $request_args ) );

		$response = wp_remote_request( $url, $request_args );
		humcore_write_error_log( 'info', 'Response  ', array( 'response' => $response ) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( $response->get_error_code(), $response->get_error_message(), $response->get_error_data( $response->get_error_code() ) );
		}

		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );
		$response_body    = wp_remote_retrieve_body( $response );

		if ( 201 != $response_code ) {
			return new WP_Error( $response_code, $response_message, $response_body );
		}

		humcore_write_error_log( 'info', 'Reserve DOI ', array( 'response' => $response_body ) );

		$doi_response = $response_body;
		return $doi_response;

	}


	/**
	 * Modify an identifier.
	 *
	 * @param array $args Array of arguments. Supports all arguments from apidoc.html#operation-modify-identifier.
	 * @link http://datacite.cdlib.org/doc/apidoc.html#operation-modify-identifier
	 * @return WP_Error|string body of the Response object
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function modify_identifier( array $args = array() ) {

		$defaults = array(
			'doi'   => '',
			'event' => '',
			'json'   => '',
		);

		$params = wp_parse_args( $args, $defaults );

		$doi = $params['doi'];
		unset( $params['doi'] ); // Leave out of the body.

		if ( empty( $doi ) ) {
			return new WP_Error( 'missingArg', 'DOI is missing.' );
		}
		if ( empty( $params['event'] ) && empty( $params['json'] ) ) {
				return new WP_Error( 'missingArg', 'Metadata is missing.' );
		}

		$url = sprintf( '%1$s/dois/%2$s', $this->base_url, $doi );

		if ( ! empty( $params['event'] ) ) {
			$content = '{"data":{"type":"dois","attributes":{"event":"' . $params['event'] . '"}}}';
		} else if ( ! empty( $params['json'] ) ) {
			$content = $params['json'];
		}

		$request_args                            = $this->options['api-auth'];
		$request_args['method']                  = 'PUT';
		$request_args['headers']['Content-Type'] = 'application/vnd.api+json';
		$request_args['body']                    = $content;
		humcore_write_error_log( 'info', 'URL ', array( 'url' => $url ) );
		humcore_write_error_log( 'info', 'Request Args ', array( 'request_args' => $request_args ) );
		humcore_write_error_log( 'info', 'State ', array( 'state' => $params['state'] ) );
		humcore_write_error_log( 'info', 'JSON ', array( 'json' => $params['json'] ) );

		$response = wp_remote_request( $url, $request_args );
		humcore_write_error_log( 'info', 'Response  ', array( 'response' => $response ) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( $response->get_error_code(), $response->get_error_message(), $response->get_error_data( $response->get_error_code() ) );
		}

		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );
		$response_body    = wp_remote_retrieve_body( $response );

		if ( 200 != $response_code ) {
			return new WP_Error( $response_code, $response_message, $response_body );
		}

		$doi_response = $response_body;
		return $doi_response;

	}


	/**
	 * Delete an identifier.
	 *
	 * @param array $args Array of arguments. Supports only doi argument.
	 * @link http://datacite.cdlib.org/doc/apidoc.html#operation-delete-identifier
	 * @return WP_Error|string body of the Response object
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function delete_identifier( array $args = array() ) {

		$defaults = array(
			'doi' => '',
		);
		$params   = wp_parse_args( $args, $defaults );

		$doi = $params['doi'];
		unset( $params['doi'] ); // Leave out of the body.

		if ( empty( $doi ) ) {
			return new WP_Error( 'missingArg', 'DOI is missing.' );
		}

		$url = sprintf( '%1$s/dois/%2$s', $this->base_url, $doi );

		$request_args                            = $this->options['api-auth'];
		$request_args['method']                  = 'DELETE';
		$request_args['headers']['Content-Type'] = 'text/plain';

		$response = wp_remote_request( $url, $request_args );
		if ( is_wp_error( $response ) ) {
			return new WP_Error( $response->get_error_code(), $response->get_error_message(), $response->get_error_data( $response->get_error_code() ) );
		}

		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );
		$response_body    = wp_remote_retrieve_body( $response );

		if ( 204 != $response_code ) {
			return new WP_Error( $response_code, $response_message, $response_body );
		}

		return $response_body;

	}

	/**
	 * Prepare doi metadata json.
	 *
	 * @return WP_Error|string body of the Response object
	 * @see wp_remote_request()
	 */
	public function prepare_doi_metadata_json( $metadata, $state ) {
		/*
		$metadata['title'],
		$metadata['pid'],
		$metadata['authors'],
		$metadata['type_of_resource'],
		$metadata['date_issued'],
		$metadata['publisher'],
		$metadata['subject'],
		$metadata['abstract'],
		$metadata['genre'],
		$metadata['language'],
		$metadata['license']
		*/

		$resource_type_map = array();

		$resource_type_map['Audio']          = 'Sound';
		$resource_type_map['Image']          = 'Image';
		$resource_type_map['Mixed material'] = 'Other';
		$resource_type_map['Software']       = 'Software';
		$resource_type_map['Text']           = 'Text';
		$resource_type_map['Video']          = 'Audiovisual';

		$license_link_list = array();

		$license_link_list['Attribution']                             = 'https://creativecommons.org/licenses/by/4.0/';
		$license_link_list['All Rights Reserved']                     = '';
		$license_link_list['Attribution-NonCommercial']               = 'https://creativecommons.org/licenses/by-nc/4.0/';
		$license_link_list['Attribution-ShareAlike']                  = 'https://creativecommons.org/licenses/by-sa/4.0/';
		$license_link_list['Attribution-NonCommercial-ShareAlike']    = 'https://creativecommons.org/licenses/by-nc-sa/4.0/';
		$license_link_list['Attribution-NoDerivatives']               = 'https://creativecommons.org/licenses/by-nd/4.0/';
		$license_link_list['Attribution-NonCommercial-NoDerivatives'] = 'https://creativecommons.org/licenses/by-nc-nd/4.0/';
		$license_link_list['All-Rights-Granted']                      = 'https://creativecommons.org/publicdomain/zero/1.0/';

		$datacite_language_map = array();

		$datacite_language_map['Arabic']           = 'ar';
		$datacite_language_map['Catalan']          = 'ca';
		$datacite_language_map['Chinese']          = 'zh';
		$datacite_language_map['Croatian']         = 'hr';
		$datacite_language_map['Czech']            = 'cs';
		$datacite_language_map['Dutch']            = 'nl';
		$datacite_language_map['Egyptian Arabic']  = 'ar';
		$datacite_language_map['English']          = 'en';
		$datacite_language_map['Filipino']         = 'fl';
		$datacite_language_map['Finnish']          = 'fi';
		$datacite_language_map['Frenc']            = 'fr';
		$datacite_language_map['German']           = 'de';
		$datacite_language_map['Greek']            = 'el';
		$datacite_language_map['Hebrew']           = 'he';
		$datacite_language_map['Hindi']            = 'hi';
		$datacite_language_map['Hungarian']        = 'hu';
		$datacite_language_map['Indonesian']       = 'id';
		$datacite_language_map['Iranian Persian']  = 'fa';
		$datacite_language_map['Irish']            = 'ga';
		$datacite_language_map['Italian']          = 'it';
		$datacite_language_map['Japanese']         = 'ja';
		$datacite_language_map['Korean']           = 'ko';
		$datacite_language_map['Kurdish']          = 'ku';
		$datacite_language_map['Lao']              = 'lo';
		$datacite_language_map['Mandarin Chinese'] = 'zh';
		$datacite_language_map['Norwegian']        = 'no';
		$datacite_language_map['Persian']          = 'fa';
		$datacite_language_map['Polish']           = 'pl';
		$datacite_language_map['Portuguese']       = 'pt';
		$datacite_language_map['Romanian']         = 'ro';
		$datacite_language_map['Russian']          = 'ru';
		$datacite_language_map['Serbian']          = 'sr';
		$datacite_language_map['Spanish']          = 'es';
		$datacite_language_map['Swahili']          = 'sw';
		$datacite_language_map['Swedish']          = 'sv';
		$datacite_language_map['Tagalog']          = 'tl';
		$datacite_language_map['Thai']             = 'th';
		$datacite_language_map['Tibetan']          = 'bo';
		$datacite_language_map['Turkish']          = 'tr';
		$datacite_language_map['Ukrainian']        = 'uk';
		$datacite_language_map['Urdu']             = 'ur';
		$datacite_language_map['Wolof']            = 'wo';
		$datacite_language_map['Yiddish']          = 'yi';

		$resource_type_general = $resource_type_map[ $metadata['type_of_resource'] ];
		if ( empty( $resource_type_general ) ) {
			$resource_type_general = 'Other';
		}

		$doi_metadata = [];
		$doi_metadata['data'] = [
			'type' => 'dois',
			'attributes' => [
				'state' => $state,
				'prefix' => $this->datacite_prefix,
				'identifiers' => [],
				'alternateIdentifiers' => [],
				'creators' => [],
				'titles' => [],
				'publisher' => '',
				'publicationYear' => '',
				'dates' => [],
				'subjects' => [],
				'contributors' => [],
				'language' => '',
				'types' => [],
				'rightsList' => [],
				'descriptions' => [],
				'url' => ""
			]
		];

		$doi_metadata['data']['attributes']['titles']['title'] = htmlspecialchars( $metadata['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false );
		$doi_metadata['data']['attributes']['publisher'] = 'Humanities Commons';

		if ( 'yes' === $metadata['embargoed'] ) {
			$doi_metadata['data']['attributes']['publicationYear'] = substr( $metadata['embargo_end_date'], 6, 4 );
		} else {
			$doi_metadata['data']['attributes']['publicationYear'] = $metadata['date_issued'];
		}

		$doi_metadata['data']['attributes']['dates'][] = array( 'date' => $metadata['record_creation_date'], 'dateType' => 'Created' );
		$doi_metadata['data']['attributes']['dates'][] = array( 'date' => $metadata['record_change_date'], 'dateType' => 'Updated' );

		$creator_found = false;
		if ( ! empty( $metadata['authors'] ) ) {
			foreach ( $metadata['authors'] as $creator ) {
				if ( in_array( $creator['role'], array( 'creator', 'author' ) ) && ! empty( $creator['fullname'] ) ) {
					$creator_found = true;
					$doi_creator = [];
					if ( empty( $creator['given'] ) ) {
						$doi_creator['name'] = htmlspecialchars( $creator['fullname'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false );
						$doi_creator['nameType'] =  'Organizational';
					} else {
						$doi_creator['name'] = $creator['family'] . ', ' . $creator['given'];
						$doi_creator['nameType'] =  'Personal';
					}
					if ( 'author' === $creator['role'] ) {
						if ( ! empty( $creator['given'] ) ) {
							$doi_creator['givenName'] = $creator['given'];
						}
						if ( ! empty( $creator['family'] ) ) {
							$doi_creator['familyName'] = $creator['family'];
						}
						if ( ! empty( $creator['affiliation'] ) ) {
							$doi_creator['affiliation'] = htmlspecialchars( $creator['affiliation'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false );
						}
					}
				}
				if ( ! empty(  $doi_creator ) ) {
					$doi_metadata['data']['attributes']['creators'][] = $doi_creator;
				}
			}
			if ( ! $creator_found ) {
				$doi_metadata['data']['attributes']['creators']['name'] =  'HC User';
			}
			foreach ( $metadata['authors'] as $contributor ) {
				if ( in_array( $contributor['role'], array( 'contributor', 'editor', 'translator' ) ) && ! empty( $contributor['fullname'] ) ) {
					$doi_contributor = [];
					if ( 'editor' === $contributor['role'] ) {
						$doi_contributor['contributorType'] = 'Editor';
					} else {
						$doi_contributor['contributorType'] = 'Other';
					}
					if ( empty( $contributor['given'] ) ) {
						$doi_contributor['name'] = htmlspecialchars( $contributor['fullname'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false );
						$doi_contributor['nameType'] = 'Organizational';
					} else {
						$doi_contributor['name'] = $contributor['family'] . ', ' . $contributor['given'];
						$doi_contributor['nameType'] = 'Personal';
					}
					if ( in_array( $contributor['role'], array( 'contributor', 'editor', 'translator' ) ) ) {
						if ( ! empty( $contributor['given'] ) ) {
							$doi_contributor['givenName'] = $contributor['given'];
						}
						if ( ! empty( $contributor['family'] ) ) {
							$doi_contributor['familyName'] = $contributor['family'];
						}
						if ( ! empty( $contributor['affiliation'] ) ) {
							$doi_contributor['affiliation'] = htmlspecialchars( $contributor['affiliation'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false );
						}
					}
				}
				if ( ! empty(  $doi_contributor ) ) {
					$doi_metadata['data']['attributes']['contributors'][] = $doi_contributor;
				}
			}
		}

		if ( ! empty( $metadata['subject'] ) ) {
			$next_subject = [];
			foreach ( $metadata['subject'] as $subject ) {
				[$fast_id, $fast_subject, $fast_facet] = explode(":", $subject);
				//$doi_metadata['data']['attributes']['subjects']['subject'] = htmlspecialchars( $subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false );
				if( $fast_subject ) {
					// FAST subject
					$next_subject['subject'] = htmlspecialchars( $fast_subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false );
					$next_subject['subjectScheme'] = "fast";
					$next_subject['schemeURI'] = htmlspecialchars( "http://id.worldcat.org/fast", ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false );					
					$next_subject['valueURI'] = htmlspecialchars( "http://id.worldcat.org/fast/" . $fast_id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false );
					$next_subject['xml:lang'] = "en-US";
				} else{
					// legacy/MLA subject
					$next_subject['subject'] = htmlspecialchars( $fast_subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false );
				}
				$doi_metadata['data']['attributes']['subjects'][] = $next_subject;
			}
		}

		$doi_metadata['data']['attributes']['types']['resourceType'] = htmlspecialchars( $metadata['genre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false );
		$doi_metadata['data']['attributes']['types']['resourceTypeGeneral'] = $resource_type_general;
		$datacite_language = $datacite_language_map[ $metadata['language'] ];
		if ( ! empty( $datacite_language ) ) {
			$doi_metadata['data']['attributes']['language'] = $datacite_language;
		}

		if ( ! empty( $metadata['type_of_license'] ) ) {
			$doi_metadata['data']['attributes']['rightsList']['rights'] = $metadata['type_of_license'];
			if ( 'All Rights Reserved' !== $metadata['type_of_license'] ) {
                        $doi_metadata['data']['attributes']['rightsList']['rightsURI'] = htmlspecialchars( $license_link_list[ $metadata['type_of_license'] ], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false );
			}
		}

		$doi_metadata['data']['attributes']['descriptions']['description'] = htmlspecialchars( str_replace( "\n", ' ', $metadata['abstract'] ), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false );
                $doi_metadata['data']['attributes']['descriptions']['descriptionType'] = 'Abstract';
		$doi_metadata['data']['attributes']['url'] = sprintf( HC_SITE_URL . '/deposits/item/%s/', $metadata['pid'] );

		//--------------------
		// this is for debugging:
		//	subject attributes are not all getting set
		//
		// goes to /srv/www/commons/logs/hcommons_error.loh
		//hcommons_write_error_log( 'debug', '(FAST) doi_metadata = ' . var_dump($doi_metadata) ); 
		//hcommons_write_error_log( 'debug', '(FAST) JSON conversion of (doi_metadata) = ' . json_encode($doi_metadata) ); 
		//--------------------

		return json_encode( $doi_metadata );
	}


	/**
	 * Get the DataCite server status
	 *
	 * @link https://support.datacite.org/reference/heartbeat-1
	 * @return WP_Error|string http status 
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function server_status() {
		// bypass this function if host == 'none'
		if ( 'none' === $this->datacite_settings['host'] ) {
			return trim( $this->datacite_settings['host'] );
		}

		$url = sprintf( '%1$s/heartbeat', $this->base_url );

		$request_args           = $this->options['api'];
		$request_args['method'] = 'GET';
//echo "REQ - ", $url, " - ", var_export( $request_args, true ), "\n";

		$response = wp_remote_request( $url, $request_args );
//echo "RES - ", var_export( $response, true ), "\n";
		if ( is_wp_error( $response ) ) {
			return new WP_Error( $response->get_error_code(), $response->get_error_message(), $response->get_error_data( $response->get_error_code() ) );
		}

		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );
		$response_body    = wp_remote_retrieve_body( $response );

		if ( 200 != $response_code ) {
			return new WP_Error( 'dataciteServerError', 'DataCite server returned status ' . $response_code . '.' );
		} else {
			return $response_code;

		}

	}

}
