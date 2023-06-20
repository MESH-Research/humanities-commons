<?php

namespace Humanities_Commons\Plugin\HC_Styles;

use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;
use \RecursiveRegexIterator;
use \RegexIterator;

class Template {

	/**
	 * paths to commonly used directories
	 */
	public static $plugin_dir;
	public static $plugin_templates_dir;

	function __construct() {

		self::$plugin_dir = \plugin_dir_path( realpath( __DIR__ ) );
		self::$plugin_templates_dir = \trailingslashit( self::$plugin_dir . 'templates' );

		bp_register_template_stack( [ $this, 'register_template_stack' ], 0 );
	}

	public function register_template_stack() {
		return self::$plugin_templates_dir;
	}

}
