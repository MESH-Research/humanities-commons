<?php
/**
 * Plugin Name: MLA Academic Interests
 * Version: 1.0
 * Description: Implement Academic Interests user taxonomy.
 * Author: mla
 *
 * @package Mla_Academic_Interests
 */

/**
 * Built for Humanities Commons: https://hcommons.org
 */

require_once dirname( __FILE__ ) . '/class-mla-academic-interests.php';
require_once dirname( __FILE__ ) . '/class-mla-academic-interests-rest-controller.php';

global $mla_academic_interests;

$mla_academic_interests = new Mla_Academic_Interests;

add_action(
	'rest_api_init', function () {

		$controller = new Mla_Academic_Interests_REST_Controller;
		$controller->register_routes();
	}
);
