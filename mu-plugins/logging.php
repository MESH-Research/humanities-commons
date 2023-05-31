<?php
/**
 * Plugin Name: MLA Logging
 * Plugin URI:  https://github.com/mlaa/commons
 * Description: Provides debugging and logging utilities to plugins and themes.
 * Version:     1.0.0
 * License:     CC BY-NC 4.0
 */

namespace MLA\Commons\Plugin\Logging;

use \Monolog\Logger as MonologLogger;
use \Monolog\Handler\NullHandler;
use \Monolog\Handler\StreamHandler;

/**
 * This Logger extends Monolog but provides a simple alternative to Monolog's
 * pushHandler. It requires only a "slug" -- a short string that currently is
 * used to derive the log file name but could in the future be used to namespace
 * other methods of log storage (e.g., a remote database). It sets the log level
 * according to the value of WP_DEBUG. This extended class will allow us to
 * easily swap out logging methods in the future.
 *
 * @package Logging
 * @subpackage Logger
 * @class Logger
 */
class Logger extends MonologLogger {

	/**
	 * Log slug/prefix.
	 *
	 * @var string
	 */
	private $slug = '';

	/**
	 * Additionally log errors to WordPress's error_log.
	 * @param string $message An error message.
	 */
	public function addError ( $message, array $context = array() ) {

		// Send to Monolog.
		parent::addError ( $message, $context );

		if ( ! empty( $context ) ) {
			$message .= ' *** ' . serialize( $context );
		}

		error_log( $this->slug . $message );

	}

	/**
	 * Create a handler for the channel.
	 * @param string $slug A slug used to create log file names.
	 */
	public function createLog ( $slug = false ) {

		// In this simplified implementation, only allow one handler.
		if ( count( $this->handlers ) ) {
			return false;
		}

		// If no slug was passed, use the null handler.
		if ( ! ( is_string( $slug ) && strlen( $slug ) ) ) {
			$this->pushHandler( new NullHandler(), parent::DEBUG );
			return true;
		}

		// Ignore debug unless WP_DEBUG is turned on.
		$log_level = ( WP_DEBUG ) ? parent::DEBUG : parent::INFO;

		$this->slug = '[' . $slug . '] ';
		$this->pushHandler( new StreamHandler( WP_LOGS_DIR . '/' . $slug . '.log', $log_level ) );

		return true;

	}


}
