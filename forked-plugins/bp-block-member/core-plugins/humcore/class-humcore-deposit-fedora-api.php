<?php
/**
 * API to access Fedora 3.
 *
 * @package HumCORE
 * @subpackage Deposits
 */

/**
 * Class using WP_HTTP to access the Fedora REST API.
 */
class Humcore_Deposit_Fedora_Api {

	private $fedora_settings = array();
	private $base_url;
	private $max_results;
	private $options           = array();
	private $upload_filehandle = array(); // Handle the WP_HTTP inability to process file uploads by hooking curl settings.

	/* getting removed
	public $servername_hash;
	*/
	public $service_status;
	public $namespace;
	public $temp_dir;
	public $collection_pid;

	/**
	 * Initialize Fedora API settings.
	 */
	public function __construct() {

		$humcore_settings = get_option( 'humcore-deposits-humcore-settings' );

		$this->fedora_settings = get_option( 'humcore-deposits-fedora-settings' );
		if ( defined( 'CORE_FEDORA_PROTOCOL' ) ) {
				$this->fedora_settings['protocol'] = CORE_FEDORA_PROTOCOL;
		}
		if ( defined( 'CORE_FEDORA_HOST' ) ) {
				$this->fedora_settings['host'] = CORE_FEDORA_HOST;
		}
		if ( defined( 'CORE_FEDORA_PORT' ) ) {
				$this->fedora_settings['port'] = CORE_FEDORA_PORT;
		}
		if ( defined( 'CORE_FEDORA_PATH' ) ) {
				$this->fedora_settings['path'] = CORE_FEDORA_PATH;
		}
		if ( defined( 'CORE_FEDORA_LOGIN' ) ) {
				$this->fedora_settings['login'] = CORE_FEDORA_LOGIN;
		}
		if ( defined( 'CORE_FEDORA_PASSWORD' ) ) {
				$this->fedora_settings['password'] = CORE_FEDORA_PASSWORD;
		}
		$this->base_url = $this->fedora_settings['protocol'] . $this->fedora_settings['host'] . ':' . $this->fedora_settings['port'] . $this->fedora_settings['path'];
		/* getting removed
				if ( defined( 'CORE_FEDORA_HOST' ) && ! empty( CORE_FEDORA_HOST ) ) { // Better have a value if defined.
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

		if ( defined( 'CORE_HUMCORE_COLLECTION_PID' ) && ! empty( CORE_HUMCORE_COLLECTION_PID ) ) {
			$this->collection_pid = CORE_HUMCORE_COLLECTION_PID;
		} else {
			$this->collection_pid = $humcore_settings['collectionpid'];
		}

		$this->max_results                                  = 512;
		$this->options['api-m']['headers']['Authorization'] = 'Basic ' . base64_encode( $this->fedora_settings['login'] . ':' . $this->fedora_settings['password'] );
		$this->options['api-m']['httpversion']              = '1.1';
		$this->options['api-m']['sslverify']                = true;
		$this->options['api-a']['httpversion']              = '1.1';
		$this->options['api-a']['sslverify']                = true;

		/* getting removed
		// Prevent copying prod config data to dev.
		if ( ! empty( $this->servername_hash ) && $this->servername_hash != md5( $_SERVER['SERVER_NAME'] ) ) {
			$this->base_url = '';
			$this->options['api-m']['headers']['Authorization'] = '';
		}
		*/

	}


	/**
	 *
	 * API-A methods.
	 */


	/**
	 * Describe (unofficial).
	 *
	 * @return WP_Error|string Body of the Response object.
	 * @see wp_remote_request()
	 */
	public function describe() {

		$url = sprintf( '%1$s/describe', $this->base_url );

		$request_args           = $this->options['api-a'];
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
		if ( empty( $response_body ) ) {
			return new WP_Error( 'fedoraServerError', 'Fedora server is not okay.' );
		}

		return $response_body;

	}


	/**
	 * Find objects.
	 *
	 * @param array $args Array of arguments. Supports all arguments from REST+API#RESTAPI-findObjects.
	 * @link https://wiki.duraspace.org/display/FEDORA38/REST+API#RESTAPI-findObjects
	 * @return WP_Error|string body of the Response object
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function find_objects( array $args = array() ) {

		$defaults = array(

			'terms'        => '',
			'query'        => '',
			'maxResults'   => $this->max_results,
			'resultFormat' => 'xml',
			'pid'          => '',
			'label'        => '',
			'state'        => '',
			'ownerid'      => '',
			'cDate'        => '',
			'mDate'        => '',
			'dcmDate'      => '',
			'title'        => '',
			'creator'      => '',
			'subject'      => '',
			'description'  => '',
			'publisher'    => '',
			'contributor'  => '',
			'date'         => '',
			'type'         => '',
			'format'       => '',
			'identifier'   => '',
			'source'       => '',
			'language'     => '',
			'relation'     => '',
			'coverage'     => '',
			'rights'       => '',

		);
		$params = wp_parse_args( $args, $defaults );

		$query_string = http_build_query( array_filter( $params ), '', '&' );
		$url          = implode(
			'?', array(
				sprintf( '%1$s/objects', $this->base_url ),
				$query_string,
			)
		);

		$request_args           = $this->options['api-a'];
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

		return $response_body;

	}


	/**
	 * Get datastream dissemination.
	 *
	 * @param array $args Array of arguments. Supports all arguments from REST+API#RESTAPI-getDatastreamDissemination.
	 * @link https://wiki.duraspace.org/display/FEDORA38/REST+API#RESTAPI-getDatastreamDissemination
	 * @return WP_Error|string body of the Response object
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function get_datastream_dissemination( array $args = array() ) {

		$defaults = array(

			'pid'          => '',
			'dsID'         => '',
			'asOfDateTime' => '',
			'download'     => false,

		);
		$params = wp_parse_args( $args, $defaults );

		$pid = $params['pid'];
		unset( $params['pid'] );
		$ds_id = $params['dsID'];
		unset( $params['dsID'] );

		if ( empty( $pid ) ) {
			return new WP_Error( 'missingArg', 'PID is missing.' );
		}

		if ( empty( $ds_id ) ) {
			return new WP_Error( 'missingArg', 'Datastream ID is missing.' );
		}

		$query_string = http_build_query( array_filter( $params ), '', '&' );
		$url          = implode(
			'?', array(
				sprintf( '%1$s/objects/%2$s/datastreams/%3$s/content', $this->base_url, $pid, $ds_id ),
				$query_string,
			)
		);

		$request_args           = $this->options['api-a'];
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

		return $response_body;

	}


	/**
	 * API method: getDissemination.
	 *
	 * Not implemented.
	 */


	/**
	 * API method: getObjectHistory.
	 *
	 * Not implemented.
	 */


	/**
	 * API method: getObjectProfile.
	 *
	 * Not implemented.
	 */


	/**
	 * List datastreams.
	 *
	 * @param array $args Array of arguments. Supports all arguments from REST+API#RESTAPI-listDatastreams.
	 * @link https://wiki.duraspace.org/display/FEDORA38/REST+API#RESTAPI-listDatastreams
	 * @return WP_Error|string body of the Response object
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function list_datastreams( array $args = array() ) {

		$defaults = array(

			'pid'          => '',
			'format'       => 'xml',
			'asOfDateTime' => '',

		);
		$params = wp_parse_args( $args, $defaults );

		$pid = $params['pid'];
		unset( $params['pid'] );

		if ( empty( $pid ) ) {
			return new WP_Error( 'missingArg', 'PID is missing.' );
		}

		$query_string = http_build_query( array_filter( $params ), '', '&' );
		$url          = implode(
			'?', array(
				sprintf( '%1$s/objects/%2$s/datastreams', $this->base_url, $pid ),
				$query_string,
			)
		);

		$request_args           = $this->options['api-a'];
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

		return $response_body;

	}


	/**
	 * API method: listMethods.
	 *
	 * Not implemented.
	 */


	/**
	 * API method: resumeFindObjects.
	 *
	 * Not implemented.
	 */


	/**
	 *
	 * API-M methods.
	 */

	/**
	 * Add datastream.
	 *
	 * @param array $args Array of arguments. Supports all arguments from REST+API#RESTAPI-addDatastream.
	 * @link https://wiki.duraspace.org/display/FEDORA38/REST+API#RESTAPI-addDatastream
	 * @return WP_Error|string body of the Response object
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function add_datastream( array $args = array() ) {

		$defaults = array(

			'pid'          => '',
			'dsID'         => '',
			'controlGroup' => 'M',
			'dsLocation'   => '',
			'altIDs'       => '',
			'dsLabel'      => '',
			'versionable'  => true,
			'dsState'      => 'A',
			'formatURI'    => '',
			'checksumType' => '',
			'checksum'     => '',
			'mimeType'     => false,
			'logMessage'   => '',
			'content'      => false,

		);
		$params = wp_parse_args( $args, $defaults );

		$pid = $params['pid'];
		unset( $params['pid'] );
		$ds_id = $params['dsID'];
		unset( $params['dsID'] );
		$content = ( $params['content'] ) ? $params['content'] : '';
		unset( $params['content'] );

		if ( empty( $pid ) ) {
			return new WP_Error( 'missingArg', 'PID is missing.' );
		}

		if ( empty( $ds_id ) ) {
			return new WP_Error( 'missingArg', 'Datastream ID is missing.' );
		}

		$mime_type = ( $params['mimeType'] ) ? $params['mimeType'] : '';

		$query_string = http_build_query( array_filter( $params ), '', '&' );
		$url          = implode(
			'?', array(
				sprintf( '%1$s/objects/%2$s/datastreams/%3$s', $this->base_url, $pid, $ds_id ),
				$query_string,
			)
		);

		$request_args                            = $this->options['api-m'];
		$request_args['method']                  = 'POST';
		$request_args['headers']['Content-Type'] = $mime_type;
		$request_args['body']                    = $content;

		$response = wp_remote_request( $url, $request_args );
		if ( is_wp_error( $response ) ) {
			return new WP_Error( $response->get_error_code(), $response->get_error_message(), $response->get_error_data( $response->get_error_code() ) );
		}

		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );
		$response_body    = wp_remote_retrieve_body( $response );

		if ( 201 != $response_code ) {
			return new WP_Error( $response_code, $response_message, $response_body );
		}

		return $response_body;

	}


	/**
	 * Add relationship.
	 *
	 * @param array $args Array of arguments. Supports all arguments from REST+API#RESTAPI-addRelationship.
	 * @link https://wiki.duraspace.org/display/FEDORA38/REST+API#RESTAPI-addRelationship
	 * @return WP_Error|string body of the Response object
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function add_relationship( array $args = array() ) {

		$defaults = array(

			'pid'       => '',
			'subject'   => '',
			'predicate' => '',
			'object'    => '',
			'isLiteral' => true,
			'datatype'  => '',

		);
		$params = wp_parse_args( $args, $defaults );

		$pid = $params['pid'];
		unset( $params['pid'] );

		if ( empty( $pid ) ) {
			return new WP_Error( 'missingArg', 'PID is missing.' );
		}

		if ( empty( $params['subject'] ) ) {
			return new WP_Error( 'missingArg', 'Subject is missing.' );
		}

		if ( empty( $params['predicate'] ) ) {
			return new WP_Error( 'missingArg', 'Predicate is missing.' );
		}

		if ( empty( $params['object'] ) ) {
			return new WP_Error( 'missingArg', 'Object is missing.' );
		}

		$query_string = http_build_query( array_filter( $params ), '', '&' );
		$url          = implode(
			'?', array(
				sprintf( '%1$s/objects/%2$s/relationships/new', $this->base_url, $pid ),
				$query_string,
			)
		);

		$request_args           = $this->options['api-m'];
		$request_args['method'] = 'POST';

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

		return $response_body;

	}


	/**
	 * API method: compare_datastream_checksum.
	 *
	 * See get_datastream.
	 */


	/**
	 * Export.
	 *
	 * @param array $args Array of arguments. Supports all arguments from REST+API#RESTAPI-export.
	 * @link https://wiki.duraspace.org/display/FEDORA38/REST+API#RESTAPI-export
	 * @return WP_Error|string body of the Response object
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function export( $args ) {

		$defaults = array(
			'pid'      => '',
			'format'   => 'info:fedora/fedora-system:FOXML-1.1',
			'context'  => 'public',
			'encoding' => 'UTF-8',
		);
		$params   = wp_parse_args( $args, $defaults );

		$pid = $params['pid'];
		unset( $params['pid'] );

		if ( empty( $pid ) ) {
			return new WP_Error( 'missingArg', 'PID is missing.' );
		}

		$query_string = http_build_query( array_filter( $params ), '', '&' );
		$url          = implode(
			'?', array(
				sprintf( '%1$s/objects/%2$s/export', $this->base_url, $pid ),
				$query_string,
			)
		);

		$request_args           = $this->options['api-m'];
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

		return $response_body;

	}


	/**
	 * Get datastream.
	 *
	 * @param array $args Array of arguments. Supports all arguments from REST+API#RESTAPI-getDatastream.
	 * @link https://wiki.duraspace.org/display/FEDORA38/REST+API#RESTAPI-getDatastream
	 * @return WP_Error|string body of the Response object
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function get_datastream( array $args = array() ) {

		$defaults = array(
			'pid'              => '',
			'dsID'             => '',
			'format'           => 'xml',
			'asOfDateTime'     => '',
			'validateChecksum' => false,
		);
		$params   = wp_parse_args( $args, $defaults );

		$pid = $params['pid'];
		unset( $params['pid'] );
		$ds_id = $params['dsID'];
		unset( $params['dsID'] );

		if ( empty( $pid ) ) {
			return new WP_Error( 'missingArg', 'PID is missing.' );
		}

		if ( empty( $ds_id ) ) {
			return new WP_Error( 'missingArg', 'Datastream ID is missing.' );
		}

		$query_string = http_build_query( array_filter( $params ), '', '&' );
		$url          = implode(
			'?', array(
				sprintf( '%1$s/objects/%2$s/datastreams/%3$s', $this->base_url, $pid, $ds_id ),
				$query_string,
			)
		);

		$request_args           = $this->options['api-m'];
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

		return $response_body;

	}


	/**
	 * Get datastream history.
	 *
	 * @param array $args Array of arguments. Supports all arguments from REST+API#RESTAPI-getDatastreamHistory.
	 * @link https://wiki.duraspace.org/display/FEDORA38/REST+API#RESTAPI-getDatastreamHistory
	 * @return WP_Error|string body of the Response object
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function get_datastream_history( $args ) {

		$defaults = array(
			'pid'    => '',
			'dsID'   => '',
			'format' => 'xml',
		);
		$params   = wp_parse_args( $args, $defaults );

		$pid = $params['pid'];
		unset( $params['pid'] );
		$ds_id = $params['dsID'];
		unset( $params['dsID'] );

		if ( empty( $pid ) ) {
			return new WP_Error( 'missingArg', 'PID is missing.' );
		}

		if ( empty( $ds_id ) ) {
			return new WP_Error( 'missingArg', 'Datastream ID is missing.' );
		}

		$query_string = http_build_query( array_filter( $params ), '', '&' );
		$url          = implode(
			'?', array(
				sprintf( '%1$s/objects/%2$s/datastreams/%3$s/history', $this->base_url, $pid, $ds_id ),
				$query_string,
			)
		);

		$request_args           = $this->options['api-m'];
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

		return $response_body;

	}


	/**
	 * Get datastreams.
	 *
	 * @param array $args Array of arguments. Supports all arguments from REST+API#RESTAPI-getDatastreams.
	 * @link https://wiki.duraspace.org/display/FEDORA38/REST+API#RESTAPI-getDatastreams
	 * @return WP_Error|string body of the Response object
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function get_datastreams( $args ) {

		$defaults = array(
			'pid'          => '',
			'profile'      => false,
			'asOfDateTime' => '',
		);
		$params   = wp_parse_args( $args, $defaults );

		$pid = $params['pid'];
		unset( $params['pid'] );

		if ( empty( $pid ) ) {
			return new WP_Error( 'missingArg', 'PID is missing.' );
		}

		$query_string = http_build_query( array_filter( $params ), '', '&' );
		$url          = implode(
			'?', array(
				sprintf( '%1$s/objects/%2$s/datastreams', $this->base_url, $pid ),
				$query_string,
			)
		);

		$request_args           = $this->options['api-m'];
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

		return $response_body;

	}


	/**
	 * Get next pid.
	 *
	 * @param array $args Array of arguments. Supports all arguments from REST+API#RESTAPI-getNextPid.
	 * @link https://wiki.duraspace.org/display/FEDORA38/REST+API#RESTAPI-getNextPid
	 * @return WP_Error|array next pid values
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function get_next_pid( $args ) {

		$defaults = array(
			'numPIDs'   => 1,
			'namespace' => $this->namespace,
			'format'    => 'xml',
		);
		$params   = wp_parse_args( $args, $defaults );

		$query_string = http_build_query( array_filter( $params ), '', '&' );
		$url          = implode(
			'?', array(
				sprintf( '%1$s/objects/nextPID', $this->base_url ),
				$query_string,
			)
		);

		$request_args           = $this->options['api-m'];
		$request_args['method'] = 'POST';

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

		$doc = new DOMDocument;
		$doc->loadXML( $response_body );

		$pids = array();
		foreach ( $doc->getElementsByTagName( 'pid' ) as $each_pid ) {
			$pids[] = $each_pid->nodeValue; // @codingStandardsIgnoreLine camelCase
		}

		return $pids;

	}


	/**
	 * Get object xml.
	 *
	 * @param array $args Array of arguments. Supports all arguments from REST+API#RESTAPI-getObjectXML.
	 * @link https://wiki.duraspace.org/display/FEDORA38/REST+API#RESTAPI-getObjectXML
	 * @return WP_Error|string body of the Response object
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function get_object_xml( $args ) {

		$pid = $args['pid'];

		if ( empty( $pid ) ) {
			return new WP_Error( 'missingArg', 'PID is missing.' );
		}

		$url = sprintf( '%1$s/objects/%2$s/objectXML', $this->base_url, $pid );

		$request_args           = $this->options['api-m'];
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

				$doc               = new DOMDocument;
				$doc->formatOutput = true; // @codingStandardsIgnoreLine camelCase
				$doc->loadXML( $response_body );

				return $doc->saveXML();

	}


	/**
	 * Get relationships
	 *
	 * @param array $args Array of arguments. Supports all arguments from REST+API#RESTAPI-getRelationships.
	 * @link https://wiki.duraspace.org/display/FEDORA38/REST+API#RESTAPI-getRelationships
	 * @return WP_Error|string body of the Response object
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function get_relationships( $args ) {

		$defaults = array(
			'pid'       => '',
			'subject'   => '',
			'predicate' => '',
			'format'    => 'rdf/xml',
		);
		$params   = wp_parse_args( $args, $defaults );

		$pid = $params['pid'];
		unset( $params['pid'] );

		if ( empty( $pid ) ) {
			return new WP_Error( 'missingArg', 'PID is missing.' );
		}

		$query_string = http_build_query( array_filter( $params ), '', '&' );
		$url          = implode(
			'?', array(
				sprintf( '%1$s/objects/%2$s/relationships', $this->base_url, $pid ),
				$query_string,
			)
		);

		$request_args           = $this->options['api-m'];
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

		return $response_body;

	}


	/**
	 * Ingest.
	 *
	 * @param array $args Array of arguments. Supports all arguments from REST+API#RESTAPI-ingest.
	 * @link https://wiki.duraspace.org/display/FEDORA38/REST+API#RESTAPI-ingest
	 * @return WP_Error|string body of the Requests_Response object
	 * @see wp_parse_args()
	 * @see wp_remote_request
	 */
	public function ingest( $args ) {

		$defaults = array(
			'pid'        => 'new',
			'label'      => '',
			'ownerId'    => '',
			'format'     => 'info:fedora/fedora-system:FOXML-1.1',
			'encoding'   => 'UTF-8',
			'namespace'  => $this->namespace,
			'logMessage' => '',
			'ignoreMime' => false,
			'xmlContent' => '',

		);
		$params = wp_parse_args( $args, $defaults );

		$pid = $params['pid'];
		unset( $params['pid'] );
		$xml_content = $params['xmlContent'];
		unset( $params['xmlContent'] );

		if ( empty( $xml_content ) ) {
			return new WP_Error( 'missingArg', 'XML content is missing.' );
		}

		$query_string = http_build_query( array_filter( $params ), '', '&' );
		$url          = implode(
			'?', array(
				sprintf( '%1$s/objects/%2$s', $this->base_url, $pid ),
				$query_string,
			)
		);

		$request_args                            = $this->options['api-m'];
		$request_args['method']                  = 'POST';
		$request_args['headers']['Content-Type'] = 'text/xml';
		$request_args['body']                    = $xml_content;

		$response = wp_remote_request( $url, $request_args );
		if ( is_wp_error( $response ) ) {
			return new WP_Error( $response->get_error_code(), $response->get_error_message(), $response->get_error_data( $response->get_error_code() ) );
		}

		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );
		$response_body    = wp_remote_retrieve_body( $response );

		if ( 201 != $response_code ) {
			return new WP_Error( $response_code, $response_message, $response_body );
		}

		return $response_body;

	}


	/**
	 * Modify datastream.
	 *
	 * @param array $args Array of arguments. Supports all arguments from REST+API#RESTAPI-modifyDatastream.
	 * @link https://wiki.duraspace.org/display/FEDORA38/REST+API#RESTAPI-modifyDatastream
	 * @return WP_Error|string body of the Response object
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function modify_datastream( $args ) {

		$defaults = array(

			'pid'              => '',
			'dsID'             => '',
			'dsLocation'       => '',
			'altIDs'           => '',
			'dsLabel'          => '',
			'versionable'      => true,
			'dsState'          => 'A',
			'formatURI'        => '',
			'checksumType'     => '',
			'checksum'         => '',
			'mimeType'         => false,
			'logMessage'       => '',
			'ignoreContent'    => false,
			'lastModifiedDate' => '',
			'content'          => false,
		);
		$params = wp_parse_args( $args, $defaults );

		$pid = $params['pid'];
		unset( $params['pid'] );
		$ds_id = $params['dsID'];
		unset( $params['dsID'] );
		$content = ( $params['content'] ) ? $params['content'] : '';
		unset( $params['content'] );

		if ( empty( $pid ) ) {
			return new WP_Error( 'missingArg', 'PID is missing.' );
		}

		if ( empty( $ds_id ) ) {
			return new WP_Error( 'missingArg', 'Datastream ID is missing.' );
		}

		$mime_type = ( $params['mimeType'] ) ? $params['mimeType'] : '';

		$query_string = http_build_query( array_filter( $params ), '', '&' );
		$url          = implode(
			'?', array(
				sprintf( '%1$s/objects/%2$s/datastreams/%3$s', $this->base_url, $pid, $ds_id ),
				$query_string,
			)
		);

		$request_args                                      = $this->options['api-m'];
		$request_args['method']                            = 'POST';
		$request_args['headers']['Content-Type']           = $mime_type;
		$request_args['headers']['X-HTTP-Method-Override'] = 'PUT';
		$request_args['body']                              = $content;

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

		return $response_body;

	}


	/**
	 * Modify object.
	 *
	 * @param array $args Array of arguments. Supports all arguments from REST+API#RESTAPI-modifyObject.
	 * @link https://wiki.duraspace.org/display/FEDORA38/REST+API#RESTAPI-modifyObject
	 * @return WP_Error|string body of the Response object
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function modify_object( $args ) {

		$defaults = array(

			'pid'              => '',
			'label'            => '',
			'ownerId'          => '',
			'state'            => 'A',
			'logMessage'       => '',
			'lastModifiedDate' => '',
		);
		$params = wp_parse_args( $args, $defaults );

		$pid = $params['pid'];
		unset( $params['pid'] );

		if ( empty( $pid ) ) {
			return new WP_Error( 'missingArg', 'PID is missing.' );
		}

		$query_string = http_build_query( array_filter( $params ), '', '&' );
		$url          = implode(
			'?', array(
				sprintf( '%1$s/objects/%2$s', $this->base_url, $pid ),
				$query_string,
			)
		);

		$request_args                                      = $this->options['api-m'];
		$request_args['method']                            = 'POST';
		$request_args['headers']['X-HTTP-Method-Override'] = 'PUT';

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

		return $response_body;

	}


	/**
	 * Purge datastream.
	 *
	 * @param array $args Array of arguments. Supports all arguments from REST+API#RESTAPI-purgeDatastream.
	 * @link https://wiki.duraspace.org/display/FEDORA38/REST+API#RESTAPI-purgeDatastream
	 * @return WP_Error|string body of the Response object
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function purge_datastream( $args ) {

		$defaults = array(
			'pid'        => '',
			'dsID'       => '',
			'startDT'    => '',
			'endDT'      => '',
			'logMessage' => '',
		);
		$params   = wp_parse_args( $args, $defaults );

		$pid = $params['pid'];
		unset( $params['pid'] );
		$ds_id = $params['dsID'];
		unset( $params['dsID'] );

		if ( empty( $pid ) ) {
			return new WP_Error( 'missingArg', 'PID is missing.' );
		}

		if ( empty( $ds_id ) ) {
			return new WP_Error( 'missingArg', 'Datastream ID is missing.' );
		}

		$query_string = http_build_query( array_filter( $params ), '', '&' );
		$url          = implode(
			'?', array(
				sprintf( '%1$s/objects/%2$s/datastreams/%3$s', $this->base_url, $pid, $ds_id ),
				$query_string,
			)
		);

		$request_args                                      = $this->options['api-m'];
		$request_args['method']                            = 'POST';
		$request_args['headers']['X-HTTP-Method-Override'] = 'DELETE';

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

		return $response_body;

	}


	/**
	 * Purge object.
	 *
	 * @param array $args Array of arguments. Supports all arguments from REST+API#RESTAPI-purgeObject.
	 * @link https://wiki.duraspace.org/display/FEDORA38/REST+API#RESTAPI-purgeObject
	 * Docs wrong - 200 returned if sucessful.
	 * @return WP_Error|string body of the Response object
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function purge_object( $args ) {

		$defaults = array(
			'pid'        => '',
			'logMessage' => '',
		);
		$params   = wp_parse_args( $args, $defaults );

		$pid = $params['pid'];
		unset( $params['pid'] );

		if ( empty( $pid ) ) {
			return new WP_Error( 'missingArg', 'PID is missing.' );
		}

		$query_string = http_build_query( array_filter( $params ), '', '&' );
		$url          = implode(
			'?', array(
				sprintf( '%1$s/objects/%2$s/', $this->base_url, $pid ),
				$query_string,
			)
		);

		$request_args                                      = $this->options['api-m'];
		$request_args['method']                            = 'POST';
		$request_args['headers']['X-HTTP-Method-Override'] = 'DELETE';

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

		return $response_body;

	}


	/**
	 * Purge relationship.
	 *
	 * @param array $args Array of arguments. Supports all arguments from REST+API#RESTAPI-purgeRelationship.
	 * @link https://wiki.duraspace.org/display/FEDORA38/REST+API#RESTAPI-purgeRelationship
	 * @return WP_Error|string body of the Response object
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function purge_relationship( $args ) {

		$defaults = array(
			'pid'       => '',
			'subject'   => '',
			'predicate' => '',
			'object'    => '',
			'isLiteral' => true,
			'datatype'  => '',
		);
		$params   = wp_parse_args( $args, $defaults );

		$pid = $params['pid'];
		unset( $params['pid'] );

		if ( empty( $pid ) ) {
			return new WP_Error( 'missingArg', 'PID is missing.' );
		}

		if ( empty( $params['subject'] ) ) {
			return new WP_Error( 'missingArg', 'Subject is missing.' );
		}

		if ( empty( $params['predicate'] ) ) {
			return new WP_Error( 'missingArg', 'Predicate is missing.' );
		}

		if ( empty( $params['object'] ) ) {
			return new WP_Error( 'missingArg', 'Object is missing.' );
		}

		$query_string = http_build_query( array_filter( $params ), '', '&' );
		$url          = implode(
			'?', array(
				sprintf( '%1$s/objects/%2$s/relationships', $this->base_url, $pid ),
				$query_string,
			)
		);

		$request_args                                      = $this->options['api-m'];
		$request_args['method']                            = 'POST';
		$request_args['headers']['X-HTTP-Method-Override'] = 'DELETE';

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

		return $response_body;

	}


	/**
	 * API method: setDatastreamState.
	 *
	 * Not implemented.
	 */


	/**
	 * API method: setDatastreamVersionable.
	 *
	 * Not implemented.
	 */


	/**
	 * Validate.
	 *
	 * @param array $args Array of arguments. Supports all arguments from REST+API#RESTAPI-validate.
	 * @link https://wiki.duraspace.org/display/FEDORA38/REST+API#RESTAPI-validate
	 * @return WP_Error|array validation errors
	 * @see wp_parse_args()
	 * @see wp_remote_request()
	 */
	public function validate( $args ) {

		$defaults = array(
			'pid'          => '',
			'asOfDateTime' => '',
		);

		$params = wp_parse_args( $args, $defaults );

		$pid = $params['pid'];
		unset( $params['pid'] );

		if ( empty( $pid ) ) {
			return new WP_Error( 'missingArg', 'PID is missing.' );
		}

		$query_string = http_build_query( array_filter( $params ), '', '&' );
		$url          = implode(
			'?', array(
				sprintf( '%1$s/objects/%2$s/validate', $this->base_url, $pid ),
				$query_string,
			)
		);

		$request_args           = $this->options['api-m'];
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

		$doc               = new DOMDocument;
		$doc->formatOutput = true; // @codingStandardsIgnoreLine camelCase
		$doc->loadXML( $response_body );

		/*
		$problems = array();
		foreach ( $doc->getElementsByTagName( 'problems' ) as $eachProblem ) {
			$problems[] = trim( $eachProblem->nodeValue );
		}

		foreach ( $doc->getElementsByTagName( 'datastreamProblems' ) as $eachProblem ) {
			$problems[] = trim( $eachProblem->nodeValue );
		}
		*/
		return $doc->saveXML();

	}


	/**
	 *
	 * Utility methods.
	 */

	/**
	 * Upload.
	 *
	 * @param array $args Array of arguments. Supports all arguments from REST+API#RESTAPI-upload.
	 * @link https://wiki.duraspace.org/display/FEDORA38/REST+API#RESTAPI-upload
	 * @return WP_Error|string temporary file url
	 * @see wp_parse_args()
	 * @see curl_before_send_file()
	 * @see wp_remote_request()
	 */
	public function upload( $args ) {

		$defaults = array(
			'file'     => '',
			'filename' => '',
			'filetype' => '',
		);
		$params   = wp_parse_args( $args, $defaults );

		$file = $params['file'];

		if ( empty( $file ) ) {
			return new WP_Error( 'missingArg', 'File is missing.' );
		}

		if ( ! file_exists( $file ) ) {
			return new WP_Error( 'missingArg', $file . ' does not exist.' );
		}

		$url = sprintf( '%1$s/upload', $this->base_url );

		$request_args            = $this->options['api-m'];
		$request_args['method']  = 'POST';
		$request_args['timeout'] = 60;

		// Handle the WP_HTTP inability to process file uploads by hooking curl settings.
		$this->upload_filehandle = $params;
		add_action( 'http_api_curl', array( $this, 'curl_before_send_file' ), 10, 3 );

		$response = wp_remote_request( $url, $request_args );
		remove_action( 'http_api_curl', array( $this, 'curl_before_send_file' ), 10 );
		if ( is_wp_error( $response ) ) {
			return new WP_Error( $response->get_error_code(), $response->get_error_message(), $response->get_error_data( $response->get_error_code() ) );
		}

		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );
		$response_body    = wp_remote_retrieve_body( $response );

		if ( 202 != $response_code ) {
			return new WP_Error( $response_code, $response_message, $response_body );
		}

		return $response_body;

	}


	/**
	 *
	 * Non-API Support methods.
	 */


	/**
	 * Handle the WP_HTTP inability to process file uploads by hooking curl settings.
	 * Handle PHP versions before and after 5.5.
	 *
	 * @param resource $curl    The cURL handle returned by curl_init().
	 * @param array    $r       The HTTP request arguments.
	 * @param string   $url     The request URL.
	 * @see upload()
	 */
	public function curl_before_send_file( &$curl, $r, $url ) {

		$file     = $this->upload_filehandle['file'];
		$filename = $this->upload_filehandle['filename'];
		$filetype = $this->upload_filehandle['filetype'];

		if ( function_exists( 'curl_file_create' ) ) {

			// PHP 5.5 and later, create a CURLFile object.
			$post_data = curl_file_create( $file, $filetype, $filename );

		} else {

			// Set filename if available.
			$rename = ( ! empty( $filename ) ) ? ';filename=' . $filename : '';
			// Set file mimetype if available.
			$mimetype = ( ! empty( $filetype ) ) ? ';type=' . $filetype : '';
			// PHP 5.2 to 5.4, prefix the file name and location with @.
			$post_data = '@' . $file . $rename . $mimetype;

		}

		// Remove the Content-Length header.
		if ( ! empty( $r['headers'] ) ) {
			$headers = array();
			foreach ( $r['headers'] as $name => $value ) {
				if ( 'Content-Length' != $name ) {
					$headers[] = "{$name}: $value";
				}
			}
			curl_setopt( $curl, CURLOPT_HTTPHEADER, $headers );
		}

		// Set the body.
		curl_setopt( $curl, CURLOPT_POSTFIELDS, array( 'file' => $post_data ) );

	}

}
