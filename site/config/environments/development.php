<?php
/**
 * Configuration overrides for WP_ENV === 'development'
 */

use Roots\WPConfig\Config;
use function Env\env;

/** This will ensure these are only loaded on Lando */
if (getenv('LANDO_INFO')) {
	/**  Parse the LANDO INFO  */
	$lando_info = json_decode(getenv('LANDO_INFO'));
  
	/** Get the database config */
	$database_config = $lando_info->database;
	/** The name of the database for WordPress */
	Config::define('DB_NAME', $database_config->creds->database);
	/** MySQL database username */
	Config::define('DB_USER', $database_config->creds->user);
	/** MySQL database password */
	Config::define('DB_PASSWORD', $database_config->creds->password);
	/** MySQL hostname */
	Config::define('DB_HOST', $database_config->internal_connection->host);
  
	/** URL routing (Optional, may not be necessary) */
	Config::define('WP_HOME', $lando_info->appserver_nginx->urls[0] );
	Config::define('WP_SITEURL', $lando_info->appserver_nginx->urls[0] );
}

Config::define('SAVEQUERIES', true);
Config::define('WP_DEBUG', true);
Config::define('WP_DEBUG_DISPLAY', false );
Config::define('WP_DEBUG_LOG', env('WP_DEBUG_LOG') ?? true);
Config::define('WP_DISABLE_FATAL_ERROR_HANDLER', true );
Config::define('SCRIPT_DEBUG', true );
Config::define('DISALLOW_INDEXING', true );

ini_set('display_errors', '1');

// Enable plugin and theme updates and installation from the admin
Config::define('DISALLOW_FILE_MODS', false);
