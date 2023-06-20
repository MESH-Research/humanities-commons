<?php
/**
 * HumCORE Solr API commands.
 *
 * @package HumCORE
 * @subpackage Deposits
 */

class Solr_Command extends WP_CLI_Command {

	/**
	 * Delete a PID.
	 *
	 * ## OPTIONS
	 *
	 * <doi>
	 * : The PID to be deleted.
	 *
	 * ## EXAMPLES
	 *
	 *     wp solr delete --pid="pid"
	 *
	 * @synopsis --pid=<pid>
	 */
	public function delete( $args, $assoc_args ) {

		global $solr_client;

		$id = $assoc_args['pid'];

		$s_status = $solr_client->delete_humcore_document( $id );

		if ( is_wp_error( $s_status ) ) {
			WP_CLI::error( sprintf( 'Error deleting pid : %1$s, %2$s-%3$s', $id, $s_status->get_error_code(), $s_status->get_error_message() ) );
		} else {

			// Print a success message
			WP_CLI::success( sprintf( 'Deleted pid : %1$s!', $id ) );
		}
	}

	/**
	 * Get Solr Status.
	 *
	 * ## OPTIONS
	 *
	 * no options
	 *
	 * ## EXAMPLES
	 *
	 *     wp solr getstatus
	 *
	 */
	public function getstatus( $args ) {

		global $solr_client;

		$s_status = $solr_client->get_solr_status();

		if ( is_wp_error( $s_status ) ) {
			WP_CLI::error( sprintf( 'Error getting Solr status : %1$s-%2$s', $s_status->get_error_code(), $s_status->get_error_message() ) );
		} else {

			// Print a success message
			WP_CLI::success( sprintf( 'Solr status : %1$s', $s_status['status'] ) );
		}
	}

	/**
	 * Get Solr Document.
	 *
	 * ## OPTIONS
	 *
	 * <doi>
	 * : The PID of the document to be retrieved.
	 *
	 * ## EXAMPLES
	 *
	 *     wp solr getdocument --pid="pid"
	 *
	 * @synopsis --pid=<pid>
	 */
	public function getdocument( $args, $assoc_args ) {

		global $solr_client;

		$id = $assoc_args['pid'];

		$s_status = $solr_client->get_humcore_document( $id );

		if ( is_wp_error( $s_status ) ) {
			WP_CLI::error( sprintf( 'Error getting docuemnt for pid : %1$s, %2$s-%3$s', $id, $s_status->get_error_code(), $s_status->get_error_message() ) );
		} else {

			// Print a success message
			WP_CLI::success( sprintf( 'Document for pid : %1$s: %2$s:', $id, var_dump( $s_status ) ) );
		}
	}
}

WP_CLI::add_command( 'solr', 'Solr_Command' );
