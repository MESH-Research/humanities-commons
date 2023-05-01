<?php
/**
 * API to access EZID.
 *
 * @package HumCORE
 * @subpackage Deposits
 */

/**
 * Class using WP_HTTP to access the EZIP API.
 */
class Humcore_Deposit_Ezid_Api {

	private $ezid_settings = array();
	private $base_url;
	private $options           = array();
	private $upload_filehandle = array(); // Handle the WP_HTTP inability to process file uploads by hooking curl settings.
	private $ezid_path;
	private $ezid_mint_path;
	private $ezid_prefix;

	/* getting removed
	public $servername_hash;
	*/
	public $service_status;
	public $namespace;
	public $temp_dir;

	/**
	 * Initialize EZID API settings.
	 */
	public function __construct() {

		$humcore_settings = get_option( 'humcore-deposits-humcore-settings' );

		/* getting removed
		if ( defined( 'CORE_EZID_HOST' ) && ! empty( CORE_EZID_HOST ) ) { // Better have a value if defined.
			$this->servername_hash = md5( $humcore_settings['servername'] );
		} else {
			$this->servername_hash = $humcore_settings['servername_hash'];
		}
		*/

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

		$this->ezid_settings = get_option( 'humcore-deposits-ezid-settings' );

		if ( defined( 'CORE_EZID_PROTOCOL' ) ) {
				$this->ezid_settings['protocol'] = CORE_EZID_PROTOCOL;
		}
		if ( defined( 'CORE_EZID_HOST' ) ) {
				$this->ezid_settings['host'] = CORE_EZID_HOST;
		}
		if ( defined( 'CORE_EZID_PORT' ) ) {
				$this->ezid_settings['port'] = CORE_EZID_PORT;
		}
		if ( defined( 'CORE_EZID_PATH' ) ) {
				$this->ezid_settings['path'] = CORE_EZID_PATH;
		}
		if ( defined( 'CORE_EZID_LOGIN' ) ) {
				$this->ezid_settings['login'] = CORE_EZID_LOGIN;
		}
		if ( defined( 'CORE_EZID_PASSWORD' ) ) {
				$this->ezid_settings['password'] = CORE_EZID_PASSWORD;
		}
		if ( defined( 'CORE_EZID_PREFIX' ) ) {
				$this->ezid_settings['prefix'] = CORE_EZID_PREFIX;
		}

		if ( ! empty( $this->ezid_settings['port'] ) ) {
			$this->base_url = $this->ezid_settings['protocol'] . $this->ezid_settings['host'] . ':' . $this->ezid_settings['port'];
		} else {
			$this->base_url = $this->ezid_settings['protocol'] . $this->ezid_settings['host'];
		}

		$this->ezid_path = $this->ezid_settings['path'];
		//      $this->ezid_mint_path = $this->ezid_settings['mintpath'];
		$this->ezid_prefix                                     = $this->ezid_settings['prefix'];
		$this->options['api-auth']['headers']['Authorization'] = 'Basic ' . base64_encode( $this->ezid_settings['login'] . ':' . $this->ezid_settings['password'] );
		$this->options['api-auth']['httpversion']              = '1.1';
		$this->options['api-auth']['sslverify']                = true;
		$this->options['api']['httpversion']                   = '1.1';
		$this->options['api']['sslverify']                     = true;

		/* getting removed
		// Prevent copying prod config data to dev.
		if ( ! empty( $this->servername_hash ) && $this->servername_hash != md5( $_SERVER['SERVER_NAME'] ) ) {
			$this->base_url = '';
			$this->options['api-auth']['headers']['Authorization'] = '';
		}
		*/

	}


	/**
	 * Get an identifier.
	 *
	 * @param array $args Array of arguments. Supports only the doi argument.
	 * @link http://ezid.cdlib.org/doc/apidoc.html#operation-get-identifier-metadata
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

		$url = sprintf( '%1$s/id/%2$s', $this->base_url, $doi );

		$request_args           = $this->options['api'];
		$request_args['method'] = 'GET';

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

		$ezid_response = explode( "\n", str_replace( "\r", '', $response_body ) );
		$ezid_metadata = array();
		foreach ( $ezid_response as $meta_row ) {
			$row_values = explode( ': ', $meta_row, 2 );
			if ( ! empty( $row_values[0] ) ) {
				$decoded_value = preg_replace_callback(
					'/\\\\u([0-9a-fA-F]{4})/',
					function ( $match ) {
						return mb_convert_encoding( pack( 'H*', $match[1] ), 'UTF-8', 'UCS-2BE' );
					},
					$row_values[1]
				);
				$ezid_metadata[ $row_values[0] ] = $decoded_value;
			}
		}
		return $ezid_metadata;

	}


	/**
	 * Create an identifier.
	 *
	 * @param array $args Array of arguments. Supports all arguments from apidoc.html#operation-create-identifier.
	 * @link http://ezid.cdlib.org/doc/apidoc.html#operation-create-identifier
	 * @return WP_Error|string body of the Response object
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function create_identifier( array $args = array() ) {

		$defaults = array(
			'doi'          => '',
			'_status'      => '',
			'_export'      => '',
			'_profile'     => '',
			'_target'      => '',
		);
		$params   = wp_parse_args( $args, $defaults );

		$doi = $params['doi'];

		if ( empty( $doi ) ) {
			return new WP_Error( 'missingArg', 'DOI is missing.' );
		}

		if ( empty( $params['_target'] ) ) {
				return new WP_Error( 'missingArg', 'Target URL is missing.' );
		}

		$url = sprintf( '%1$s/id/%2$s%3$s', $this->base_url, $this->ezid_prefix, $doi );

		$content = '';
		foreach ( $params as $key => $value ) {
			if ( ! empty( $value ) ) {
				$encoded_value = str_replace( array( "\n", "\r", '%' ), array( '\u000A', '\u000D', '\u0025' ), $value );
				$content      .= $key . ': ' . $encoded_value . "\n";
			}
		}

		$request_args                            = $this->options['api-auth'];
		$request_args['method']                  = 'PUT';
		$request_args['headers']['Content-Type'] = 'text/plain';
		$request_args['body']                    = $content;
		humcore_write_error_log( 'info', 'URL ', array( 'url' => $url ) );
		humcore_write_error_log( 'info', 'Request Args ', array( 'request_args' => $request_args ) );
		humcore_write_error_log( 'info', 'XML ', array( 'xml' => $params['datacite'] ) );

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

		$response_array = explode( ':', $response_body, 2 );
		if ( 'success' == $response_array[0] ) {
			$ezid = explode( '|', $response_array[1], 2 );
			return trim( $ezid[0] );
		} else {
			return false;
		}

	}


	/**
	 * Mint an identifier.
	 *
	 * @param array $args Array of arguments. Supports all arguments from apidoc.html#operation-mint-identifier.
	 * @link http://ezid.cdlib.org/doc/apidoc.html#operation-mint-identifier
	 * @return WP_Error|string body of the Response object
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function reserve_identifier( array $args = array() ) {

		// bypass this function if host = 'none'
		if ( 'none' === $this->ezid_settings['host'] ) {
			return trim( $this->ezid_settings['host'] );
		}

		$defaults = array(
			'_status'  => '',
			'_export'  => '',
			'_profile' => '',
			'_target'  => '',
			'datacite' => '',
		);

		$params = wp_parse_args( $args, $defaults );

		if ( empty( $params['_target'] ) ) {
			return new WP_Error( 'missingArg', 'Target URL is missing.' );
		}

		$url = sprintf( '%1$s/shoulder/%2$s', $this->base_url, $this->ezid_prefix );

		$content = '';
		foreach ( $params as $key => $value ) {
			if ( ! empty( $value ) ) {
				$encoded_value = str_replace( array( "\n", "\r", '%' ), array( '\u000A', '\u000D', '\u0025' ), $value );
				$content      .= $key . ': ' . $encoded_value . "\n";
			}
		}

		$request_args                            = $this->options['api-auth'];
		$request_args['method']                  = 'POST';
		$request_args['headers']['Content-Type'] = 'text/plain';
		$request_args['body']                    = $content;
		humcore_write_error_log( 'info', 'URL ', array( 'url' => $url ) );
		humcore_write_error_log( 'info', 'Request Args ', array( 'request_args' => $request_args ) );
		humcore_write_error_log( 'info', 'XML ', array( 'xml' => $params['datacite'] ) );

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

		humcore_write_error_log( 'info', 'Reserve DOI ', array( 'response' => $response_body ) );

		$doi_response = explode( "\n", str_replace( "\r", '', $response_body ) );
		$doi_metadata = array();
		foreach ( $doi_response as $meta_row ) {
			$row_values = explode( ': ', $meta_row, 2 );
			if ( ! empty( $row_values[0] ) ) {
				$decoded_value = preg_replace_callback(
					'/\\\\u([0-9a-fA-F]{4})/',
					function ( $match ) {
						return mb_convert_encoding( pack( 'H*', $match[1] ), 'UTF-8', 'UCS-2BE' );
					},
					$row_values[1]
				);
				$doi_metadata[ $row_values[0] ] = $decoded_value;
			}
		}
		return $doi_metadata['success'];

	}


	/**
	 * Modify an identifier.
	 *
	 * @param array $args Array of arguments. Supports all arguments from apidoc.html#operation-modify-identifier.
	 * @link http://ezid.cdlib.org/doc/apidoc.html#operation-modify-identifier
	 * @return WP_Error|string body of the Response object
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function modify_identifier( array $args = array() ) {

		$defaults = array(
			'doi'      => '',
			'_status'  => '',
			'_export'  => '',
			'_profile' => '',
			'datacite' => '',
		);

		$params = wp_parse_args( $args, $defaults );

		$doi = $params['doi'];
		unset( $params['doi'] ); // Leave out of the body.

		if ( empty( $doi ) ) {
			return new WP_Error( 'missingArg', 'DOI is missing.' );
		}
		if ( empty( $params ) ) {
				return new WP_Error( 'missingArg', 'Metadata is missing.' );
		}

		$url = sprintf( '%1$s/id/%2$s', $this->base_url, $doi );

		$content = '';
		foreach ( $params as $key => $value ) {
			if ( ! empty( $value ) ) {
				$encoded_value = str_replace( array( "\n", "\r", '%' ), array( '\u000A', '\u000D', '\u0025' ), $value );
				$content      .= $key . ': ' . $encoded_value . "\n";
			}
		}

		$request_args                            = $this->options['api-auth'];
		$request_args['method']                  = 'POST';
		$request_args['headers']['Content-Type'] = 'text/plain';
		$request_args['body']                    = $content;
		humcore_write_error_log( 'info', 'URL ', array( 'url' => $url ) );
		humcore_write_error_log( 'info', 'Request Args ', array( 'request_args' => $request_args ) );
		humcore_write_error_log( 'info', 'XML ', array( 'xml' => $params['datacite'] ) );

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

		$response_array = explode( ':', $response_body, 2 );
		if ( 'success' == $response_array[0] ) {
			$ezid = explode( '|', $response_array[1], 2 );
			return trim( $ezid[0] );
		} else {
			return false;
		}

	}


	/**
	 * Delete an identifier.
	 *
	 * @param array $args Array of arguments. Supports only doi argument.
	 * @link http://ezid.cdlib.org/doc/apidoc.html#operation-delete-identifier
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

		$url = sprintf( '%1$s/id/%2$s', $this->base_url, $doi );

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

		if ( 200 != $response_code ) {
			return new WP_Error( $response_code, $response_message, $response_body );
		}

		$response_array = explode( ':', $response_body, 2 );
		if ( 'success' == $response_array[0] ) {
			$ezid = explode( '|', $response_array[1], 2 );
			return trim( $ezid[0] );
		} else {
			return false;
		}

	}

	/**
	 * Prepare doi metadata.
	 *
	 * @return WP_Error|string body of the Response object
	 * @see wp_remote_request()
	 */
	public function prepare_doi_metadata( $metadata ) {
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

		$doi_metadata = new SimpleXMLElement( '<?xml version="1.0" encoding="UTF-8" ?>
		 <resource xmlns="http://datacite.org/schema/kernel-4" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://datacite.org/schema/kernel-4 http://schema.datacite.org/meta/kernel-4/metadata.xsd"></resource>'
		);

		if ( empty( $metadata['deposit_doi'] ) ) {
			$deposit_doi = '(:tba)';
		} else {
			$deposit_doi = $metadata['deposit_doi'];
		}
		$doi_identifier = $doi_metadata->addChild( 'identifier', $deposit_doi );
		$doi_identifier->addAttribute( 'identifierType', 'DOI' );
		$doi_titles    = $doi_metadata->addChild( 'titles' );
		$doi_title     = $doi_titles->addChild( 'title', htmlspecialchars( $metadata['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) );
		$doi_publisher = $doi_metadata->addChild( 'publisher', 'Humanities Commons' );
		if ( 'yes' === $metadata['embargoed'] ) {
			$doi_publication_year = $doi_metadata->addChild( 'publicationYear', substr( $metadata['embargo_end_date'], 6, 4 ) );
		} else {
			$doi_publication_year = $doi_metadata->addChild( 'publicationYear', $metadata['date_issued'] );
		}

		$doi_dates = $doi_metadata->addChild( 'dates' );

		$doi_date_c = $doi_dates->addChild( 'date', $metadata['record_creation_date'] );
		$doi_date_c->addAttribute( 'dateType', 'Created' );

		$doi_date_u = $doi_dates->addChild( 'date', $metadata['record_change_date'] );
		$doi_date_u->addAttribute( 'dateType', 'Updated' );

		$creator_found = false;
		if ( ! empty( $metadata['authors'] ) ) {
			$doi_creators = $doi_metadata->addChild( 'creators' );
			foreach ( $metadata['authors'] as $creator ) {
				if ( in_array( $creator['role'], array( 'creator', 'author' ) ) && ! empty( $creator['fullname'] ) ) {
					$creator_found = true;
					$doi_creator   = $doi_creators->addChild( 'creator' );
					if ( empty( $creator['given'] ) ) {
						$doi_creator_name = $doi_creator->addChild( 'creatorName', htmlspecialchars( $creator['fullname'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) );
					} else {
						$doi_creator_name = $doi_creator->addChild( 'creatorName', $creator['family'] . ', ' . $creator['given'] );
					}
					if ( 'author' === $creator['role'] ) {
						if ( ! empty( $creator['given'] ) ) {
							$doi_creator_given = $doi_creator->addChild( 'givenName', $creator['given'] );
						}
						if ( ! empty( $creator['family'] ) ) {
							$doi_creator_family = $doi_creator->addChild( 'familyName', $creator['family'] );
						}
						if ( ! empty( $creator['affiliation'] ) ) {
							$doi_creator_affiliation = $doi_creator->addChild( 'affiliation', htmlspecialchars( $creator['affiliation'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) );
						}
/*
						$doi_creator_name->addAttribute( 'nameType', 'Personal' );
					} else {
						$doi_creator_name->addAttribute( 'nameType', 'Organizational' );
*/
					}
				}
			}
			if ( ! $creator_found ) {
				$doi_creator      = $doi_creators->addChild( 'creator' );
				$doi_creator_name = $doi_creator->addChild( 'creatorName', 'HC User ' );
			}
			$doi_contributors = $doi_metadata->addChild( 'contributors' );
			foreach ( $metadata['authors'] as $contributor ) {
				if ( in_array( $contributor['role'], array( 'contributor', 'editor', 'translator' ) ) && ! empty( $contributor['fullname'] ) ) {
					$doi_contributor = $doi_contributors->addChild( 'contributor' );
					if ( 'editor' === $contributor['role'] ) {
						$doi_contributor->addAttribute( 'contributorType', 'Editor' );
					} else {
						$doi_contributor->addAttribute( 'contributorType', 'Other' );
					}
					if ( empty( $contributor['given'] ) ) {
						$doi_contributor_name = $doi_contributor->addChild( 'contributorName', htmlspecialchars( $contributor['fullname'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) );
					} else {
						$doi_contributor_name = $doi_contributor->addChild( 'contributorName', $contributor['family'] . ', ' . $contributor['given'] );
					}
					if ( in_array( $contributor['role'], array( 'contributor', 'editor', 'translator' ) ) ) {
						if ( ! empty( $contributor['given'] ) ) {
							$doi_contributor_given = $doi_contributor->addChild( 'givenName', $contributor['given'] );
						}
						if ( ! empty( $contributor['family'] ) ) {
							$doi_contributor_family = $doi_contributor->addChild( 'familyName', $contributor['family'] );
						}
						if ( ! empty( $contributor['affiliation'] ) ) {
							$doi_contributor_affiliation = $doi_contributor->addChild( 'affiliation', htmlspecialchars( $contributor['affiliation'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) );
						}
/*
						$doi_contributor_name->addAttribute( 'nameType', 'Personal' );
					} else {
						$doi_contributor_name->addAttribute( 'nameType', 'Organizational' );
*/
					}
				}
			}
		}
		if ( ! empty( $metadata['subject'] ) ) {
			$doi_subjects = $doi_metadata->addChild( 'subjects' );
			foreach ( $metadata['subject'] as $subject ) {
		    	// split subject on ":"
        		[$fast_id, $fast_subject, $fast_facet] = explode(":", $subject);
				if( $fast_subject ) {
					// FAST style subject
					//$doi_subject = $doi_subjects->addChild( 'subject', htmlspecialchars( $fast_subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) );
					$doi_subject = $doi_subjects->addChild( 'subject', "the way is void" );
					$doi_subject->addAttribute("xml:lang", "en-US");
					$doi_subject->addAttribute("subjectScheme","fast");
					$doi_subject->addAttribute("schemeURI","http://id.worldcat.org/fast");
					$doi_subject->addAttribute("valueURI", "http://id.worldcat.org/fast/" . $fast_id);
				} else {
					// MLA/legacy style subject
					$doi_subject = $doi_subjects->addChild( 'subject', htmlspecialchars( $subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) );
				}
			}
		}

		$doi_resource_type = $doi_metadata->addChild( 'resourceType', htmlspecialchars( $metadata['genre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) );
		$doi_resource_type->addAttribute( 'resourceTypeGeneral', $resource_type_general );
		$datacite_language = $datacite_language_map[ $metadata['language'] ];
		if ( ! empty( $datacite_language ) ) {
			$doi_language = $doi_metadata->addChild( 'language', $datacite_language );
		}
		/* Let's see if we want this
		$doi_alternate_identifiers = $doi_metadata->addChild( 'alternateIdentifiers' );
		$doi_alternate_identifier  = $doi_alternate_identifiers->addChild(
			'alternateIdentifier',
			htmlspecialchars( sprintf( HC_SITE_URL . '/deposits/item/%s/', $metadata['pid'] ), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false )
		);
		$doi_alternate_identifier->addAttribute( 'alternateIdentifierType', 'URL' );
		*/
		if ( ! empty( $metadata['type_of_license'] ) ) {
			$doi_rights = $doi_metadata->addChild( 'rightsList' );
			$doi_right  = $doi_rights->addChild( 'rights', $metadata['type_of_license'] );
			if ( 'All Rights Reserved' !== $metadata['type_of_license'] ) {
				$doi_right->addAttribute( 'rightsURI', htmlspecialchars( $license_link_list[ $metadata['type_of_license'] ], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) );
			}
		}

		$doi_descriptions = $doi_metadata->addChild( 'descriptions' );
		$doi_description  = $doi_descriptions->addChild( 'description', htmlspecialchars( str_replace( "\n", ' ', $metadata['abstract'] ), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false ) );
		$doi_description->addAttribute( 'descriptionType', 'Abstract' );

		return trim( str_replace( "\n", '', $doi_metadata->asXML() ) );
	}

	/**
	 * Get the EZID server status
	 *
	 * @param array $args Array of arguments. Supports only subsystems argument.
	 * @link http://ezid.cdlib.org/doc/apidoc.html#server-status
	 * @return WP_Error|array subsystems status
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function server_status( array $args = array() ) {
		// bypas this function if host == 'none'
		if ( 'none' === $this->ezid_settings['host'] ) {
			return trim( $this->ezid_settings['host'] );
		}

		$params = wp_parse_args( $args, $defaults );

		$url = sprintf( '%1$s/%2$s', $this->base_url, 'status' );

		$request_args           = $this->options['api'];
		$request_args['method'] = 'GET';

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

		$ezid_response = explode( "\n", str_replace( "\r", '', $response_body ) );
		$ezid_metadata = array();
		foreach ( $ezid_response as $meta_row ) {
			$row_values = explode( ': ', $meta_row, 2 );
			if ( ! empty( $row_values[0] ) ) {
				$decoded_value = preg_replace_callback(
					'/\\\\u([0-9a-fA-F]{4})/',
					function ( $match ) {
						return mb_convert_encoding( pack( 'H*', $match[1] ), 'UTF-8', 'UCS-2BE' );
					},
					$row_values[1]
				);
				$ezid_metadata[ $row_values[0] ] = $decoded_value;
			}
		}
		if ( 'API is up' !== $ezid_metadata['success'] ) {
			return new WP_Error( 'ezidServerError', 'EZID server is not okay.', var_export( $ezid_metadata, true ) );
		}

		return $ezid_metadata;

	}

}
