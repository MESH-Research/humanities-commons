<?php
/**
 * HumCORE DataCite API commands.
 *
 * @package HumCORE
 * @subpackage Deposits
 */

class DataCite_Command extends WP_CLI_Command {

	/**
	 * Delete a reserved DOI.
	 *
	 * ## OPTIONS
	 *
	 * <doi>
	 * : The reserved DOI to be deleted.
	 *
	 * ## EXAMPLES
	 *
	 *     wp datacite delete --doi="doi"
	 *
	 * @synopsis --doi=<doi>
	 */
	public function delete( $args, $assoc_args ) {

		global $datacite_api;

		$id = $assoc_args['doi'];

		$e_status = $datacite_api->delete_identifier(
			array(
				'doi' => $id,
			)
		);

		if ( is_wp_error( $e_status ) ) {
			WP_CLI::error( sprintf( 'Error deleting doi : %1$s, %2$s-%3$s', $id, $e_status->get_error_code(), $e_status->get_error_message() ) );
		} else {

			// Print a success message
			WP_CLI::success( sprintf( 'Deleted doi : %1$s!', $id ) );
		}
	}

	/**
	 * Reserve a DOI.
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     wp datacite reserve
	 *
	 */
	public function reserve( $args ) {

		global $datacite_api;

		$e_status = $datacite_api->reserve_identifier();

		if ( is_wp_error( $e_status ) ) {
			WP_CLI::error( sprintf( 'Error reserving a doi : %1$s-%2$s', $e_status->get_error_code(), $e_status->get_error_message() ) );
		} else {

			// Print a success message
			$datacite_response =  json_decode( $e_status, true );
			$id =  $datacite_response['data']['attributes']['doi'];

			WP_CLI::success( sprintf( 'reserve doi : %1$s', $id ) . "\n" . json_encode( $datacite_response, JSON_PRETTY_PRINT ) );
		}
	}


	/**
	 * Get a DOI.
	 *
	 * ## OPTIONS
	 *
	 * <doi>
	 * : The DOI to be retrieved.
	 *
	 * ## EXAMPLES
	 *
	 *     wp datacite get --doi="doi"
	 *
	 * @synopsis --doi=<doi>
	 */
	public function get( $args, $assoc_args ) {

		global $datacite_api;

		$id = $assoc_args['doi'];

		$e_status = $datacite_api->get_identifier(
			array(
				'doi' => $id,
			)
		);

		if ( is_wp_error( $e_status ) ) {
			WP_CLI::error( sprintf( 'Error getting doi : %1$s, %2$s-%3$s', $id, $e_status->get_error_code(), $e_status->get_error_message() ) );
		} else {
			// Print a success message
			WP_CLI::success( sprintf( 'retrieve doi : %1$s', $id ) . "\n" . json_encode( json_decode( $e_status, true ), JSON_PRETTY_PRINT ) );
		}
	}


	/**
	 * Check server status
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *     wp datacite status
	 *
	 */
	public function status( $args ) {

		global $datacite_api;

		$e_status = $datacite_api->server_status();

		if ( is_wp_error( $e_status ) ) {
			WP_CLI::error( sprintf( 'Error checking DOI service status: %1$s: %2$s', $e_status->get_error_code(), $e_status->get_error_message() ) );
		} else {
			// Print a success message
			WP_CLI::success( sprintf( 'DOI service is available.' ) );
		}

	}


	/**
	 * Unpublish a published DOI.
	 *
	 * ## OPTIONS
	 *
	 * <doi>
	 * : The published DOI to be made unavailable.
	 *
	 * ## EXAMPLES
	 *
	 *     wp datacite unpublish --doi="doi"
	 *
	 * @synopsis --doi=<doi>
	 */
	public function unpublish( $args, $assoc_args ) {

		global $ezid_api;

		$id = $assoc_args['doi'];

		$e_status = $ezid_api->modify_identifier(
			array(
				'doi'     => $id,
				'_status' => 'unavailable|Created in error.',
			)
		);

		if ( is_wp_error( $e_status ) ) {
			WP_CLI::error( sprintf( 'Error modifying doi : %1$s, %2$s-%3$s', $id, $e_status->get_error_code(), $e_status->get_error_message() ) );
		} else {

			// Print a success message
			WP_CLI::success( sprintf( 'Doi : %1$s! is now unavailable.', $id ) );
		}
	}
}

WP_CLI::add_command( 'datacite', 'DataCite_Command' );
