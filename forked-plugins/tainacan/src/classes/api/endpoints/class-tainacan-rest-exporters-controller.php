<?php

namespace Tainacan\API\EndPoints;

use \Tainacan\API\REST_Controller;
use Tainacan\Repositories;
use Tainacan\Entities;
use \Tainacan\Exposers\Mappers\Value;

/**
 * Represents the Exporters REST Controller
 *
 * */
class REST_Exporters_Controller extends REST_Controller {

	/**
	* REST_Exporters_Controller constructor.
	* Define the namespace, rest base and instantiate your attributes.
	*/
	public function __construct() {
		$this->rest_base = 'exporters';
		parent::__construct();
		add_action('init', array(&$this, 'init_objects'), 11);
	}

	/**
	 * Initialize objects after post_type register
	 */
	public function init_objects() {
		$this->collections_repository = Repositories\Collections::get_instance();
	}

	/**
	* Register the collections route and their endpoints
	*/
	public function register_routes() {
		register_rest_route($this->namespace, '/' . $this->rest_base . '/available', array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array($this, 'get_registered_exporters'),
				'permission_callback' => array($this, 'export_permissions_check'),
			),
		));

		register_rest_route($this->namespace, '/' . $this->rest_base . '/session', array(
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array($this, 'create_item'),
				'permission_callback' => array($this, 'export_permissions_check'),
				'args'                => [
					'importer_slug' => [
						'type'        => 'string',
						'description' => __( 'The slug of the exporter to be initialized', 'tainacan' ),
					]
				],
			),
		));

		register_rest_route($this->namespace, '/' . $this->rest_base . '/session/(?P<session_id>[0-9a-f]+)', array(
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array($this, 'update_item'),
				'permission_callback' => array($this, 'export_permissions_check'),
				'args'                => [
					'send_email' => [
						'type'        => 'string',
						'description' => __( 'The e-mail to be used by the export to send a message when the process ends', 'tainacan' ),
					],
					'collection' => [
						'type'        => ['array', 'object'],
						'description' => __( 'The array describing the collection as expected by the exporter', 'tainacan' ),
					],
					'options' => [
						'type'        => ['array', 'object'],
						'description' => __( 'The exporter options', 'tainacan' ),
					]
				],
			),
		));

		register_rest_route($this->namespace, '/' . $this->rest_base . '/session/(?P<session_id>[0-9a-f]+)/run', array(
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array($this, 'run'),
				'permission_callback' => array($this, 'export_permissions_check'),
			),
		));

	}

	/**
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 * @throws \Exception
	 */
	public function  export_permissions_check($request) {
		return true;
	}

	public function get_registered_exporters() {
		global $Tainacan_Exporter_Handler;
		$exporters = $Tainacan_Exporter_Handler->get_registered_exporters();
		return new \WP_REST_Response( $exporters, 200 );
	}

	/**
	 * Creates a new instance of the desired exporter and returns its ID
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return array|\WP_Error|\WP_REST_Response
	 */
	public function create_item( $request ) {
		$body = json_decode($request->get_body(), true);

		if(empty($body)) {
			return new \WP_REST_Response([
				'error_message' => __('Body cannot be empty.', 'tainacan'),
			], 400);
		}
		$slug = $body['exporter_slug'];
		global $Tainacan_Exporter_Handler;

		if ($object = $Tainacan_Exporter_Handler->initialize_exporter($slug)) {
			$response = $object->_to_Array();
			$Tainacan_Exporter_Handler->save_exporter_instance($object);
			return new \WP_REST_Response($response, 201);
		} else {
			return new \WP_REST_Response([
				'error_message' => __('Exporter not found', 'tainacan'),
			], 400);
		}
	}

	/**
	 * Update a collection
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return string|\WP_Error|\WP_REST_Response
	 */
	public function update_item( $request ) {

		$session_id = $request['session_id'];
		$body = json_decode($request->get_body(), true);

		if(!empty($body)) {
			global $Tainacan_Exporter_Handler;
			$exporter = $Tainacan_Exporter_Handler->get_exporter_instance_by_session_id($session_id);
			
			if($exporter) {
				foreach ($body as $att => $value) {
					if ($att == 'collection') {
						if (is_array($value) && isset($value['id'])) {
							$collection = $this->collections_repository->fetch($value['id']);
							$total_items = wp_count_posts( $collection->get_db_identifier(), 'readable' );
							$value['total_items'] = ($total_items->publish + $total_items->private + $total_items->draft);
							$exporter->add_collection($value);
							continue;
						} else {
							return new \WP_REST_Response([
								'error_message' => __('Invalid collection', 'tainacan' ),
										'session_id' => $session_id
							], 400);
						}
					}
					$method = 'set_' . $att;
					if (method_exists($exporter, $method)) {
						$exporter->$method($value);
					}
				}

				$response = $exporter->_to_Array();
				$Tainacan_Exporter_Handler->save_exporter_instance($exporter);
				return new \WP_REST_Response( $response, 200 );
			}

			return new \WP_REST_Response([
				'error_message' => __('Exporter Session not found', 'tainacan' ),
				'session_id' => $session_id
			], 400);
		}

		return new \WP_REST_Response([
			'error_message' => __('The body cannot be empty', 'tainacan'),
			'body'          => $body
		], 400);
	}
	
	/**
	 * Run a exporter
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return string|\WP_Error|\WP_REST_Response
	 */
	public function run($request) {
		$session_id = $request['session_id'];
		global $Tainacan_Exporter_Handler;
		$exporter = $Tainacan_Exporter_Handler->get_exporter_instance_by_session_id($session_id);

		if(!$exporter) {
			return new \WP_REST_Response([
				'error_message' => __('Exporter Session not found', 'tainacan' ),
				'session_id' => $session_id
			], 400);
		}

		global $Tainacan_Exporter_Handler;
		$process = $Tainacan_Exporter_Handler->add_to_queue($exporter);
		if (false === $process) {
			return new \WP_REST_Response([
				'error_message' => __('Error starting exporter', 'tainacan' ),
				'session_id' => $session_id
			], 400);
		}
		$response = [
			'bg_process_id' => $process->ID
		];
		$Tainacan_Exporter_Handler->delete_exporter_instance($exporter);
		return new \WP_REST_Response( $response, 200 );
	}
	
	protected function map($item_arr, $mapper) {
		$ret = $item_arr;
		if(array_key_exists('metadatum', $item_arr)) { // getting a unique metadatum
			$ret = $this->map_metadatum($item_arr, $mapper);
		} else { // array of elements
			$ret = [];
			foreach ($item_arr as $item) {
				if(array_key_exists('metadatum', $item)) {
					$ret = array_merge($ret, $this->map($item, $mapper) );
				} else {
					$ret[] = $this->map($item, $mapper);
				}
			}
		}
		return $ret;
	}

	protected function map_metadatum($item_arr, $mapper) {
		$ret = $item_arr;
		$metadatum_mapping = $item_arr['metadatum']['exposer_mapping'];
		if(array_key_exists($mapper->slug, $metadatum_mapping)) {
			if(
					is_string($metadatum_mapping[$mapper->slug]) && is_array($mapper->metadata) && !array_key_exists( $metadatum_mapping[$mapper->slug], $mapper->metadata) ||
					is_array($metadatum_mapping[$mapper->slug]) && $mapper->allow_extra_metadata != true
			) {
				throw new \Exception('Invalid Mapper Option');
			}
			$slug = '';
			if(is_string($metadatum_mapping[$mapper->slug])) {
				$slug = $metadatum_mapping[$mapper->slug];
			} else {
				$slug = $metadatum_mapping[$mapper->slug]['slug'];
			}
			$ret = [$mapper->prefix.$slug.$mapper->sufix => $item_arr['value']]; //TODO Validate option
		} elseif($mapper->slug == 'value') {
			$ret = [$item_arr['metadatum']['name'] => $item_arr['value']];
		} else {
			$ret = [];
		}
		return $ret;
	}
	
}

?>
