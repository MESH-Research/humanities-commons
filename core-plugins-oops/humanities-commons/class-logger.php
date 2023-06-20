<?php
/**
 * Logger
 *
 * @package Logging
 */

namespace MLA\Commons\Plugin\Logging;

if ( ! class_exists( '\MLA\Commons\Plugin\Logging\Logger' ) ) {

	/**
	 * Provide a logger that can be used for debugging and change logging. This
	 * is a fallback class that is only used if the Monolog-based logger has not
	 * loaded (e.g., in unit tests).
	 *
	 * @package Logging
	 * @subpackage Logger
	 * @class Logger
	 */
	class Logger {

		/**
		 * Log slug/prefix.
		 *
		 * @var string
		 */
		private $slug = '';

		/**
		 * Suppress logging.
		 *
		 * @var bool
		 */
		private $suppress = false;

		/**
		 * Log to PHP error_log.
		 *
		 * @param string $message  Message to log.
		 * @param array  $context  Array of extra data to append to log entry.
		 * @param bool   $is_debug True if message is a debug message.
		 */
		private function log( $message, $context, $is_debug = true ) {

			if ( $this->suppress || ( $is_debug && true !== WP_DEBUG ) ) {
				return false;
			}

			if ( ! empty( $context ) ) {
				$message .= ' *** ' . serialize( $context );
			}

			error_log( $this->slug . $message );

		}

		/**
		 * Create log. Since Monolog isn't available, we just send what we have to
		 * the PHP error log and prefix it with the slug. If null is provided as
		 * slug, we dev-null everything.
		 *
		 * @param string $slug Log slug/prefix.
		 */
		public function createLog( $slug = null ) { // @codingStandardsIgnoreLine camelCase
			if ( null === $slug ) {
				$this->suppress = true;
			} else if ( is_string( $slug ) && strlen( $slug ) ) {
				$this->slug = '[' . $slug . '] ';
			}
		}

		/**
		 * Add debug message.
		 *
		 * @param string $message Message to log.
		 * @param array  $context Array of extra data to append to log entry.
		 */
		public function addDebug( $message, array $context = array() ) { // @codingStandardsIgnoreLine camelCase
			$this->log( $message, $context );
		}

		/**
		 * Add error message.
		 *
		 * @param string $message Message to log.
		 * @param array  $context Array of extra data to append to log entry.
		 */
		public function addError( $message, array $context = array() ) { // @codingStandardsIgnoreLine camelCase
			$this->log( $message, $context, false );
		}

		/**
		 * Add info message.
		 *
		 * @param string $message Message to log.
		 * @param array  $context Array of extra data to append to log entry.
		 */
		public function addInfo( $message, array $context = array() ) { // @codingStandardsIgnoreLine camelCase
			$this->log( $message, $context );
		}

		/**
		 * Add warning message.
		 *
		 * @param string $message Message to log.
		 * @param array  $context Array of extra data to append to log entry.
		 */
		public function addWarning( $message, array $context = array() ) { // @codingStandardsIgnoreLine camelCase
			$this->log( $message, $context );
		}
	}

}
