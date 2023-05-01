<?php

/**
 * Hooks in asynchronous tika extraction logic.
 *
 * @see WP_Async_Task
**/
class Humcore_Async_Tika_Action extends WP_Async_Task {
	// The action to hook our asynchronous request to.
	protected $action = 'humcore_tika_text_extraction';

	/**
	 * Prepare data submitted through the Deposit form for the asynchronous tika extraction request.
	 *
	 * @param Array $data The raw data passed by the humcore_tika_extraction action hook.
	 * @return Array The data to pass along as POST data in our asynchronous request.
	**/
	protected function prepare_data( $data ) {

		//humcore_write_error_log( 'info', sprintf( '*****HumCORE Deposit***** - Tika text extract prepare data %1$s', $_POST['aggregator-post-id'] ) );

		/*
		// Check to make sure Buddypress is turned on
		if ( false === function_exists( 'buddypress' ) ) {
			throw new Exception( 'BuddyPress not active' );
		}
		*/

		if ( empty( $_POST['aggregator-post-id'] ) ) {
			throw new Exception( 'HUMCORE - No deposit to post to' );
			return;
		}

		// Nonce check
		/*
		if ( ! isset( $_POST['bp_multiple_forum_post'] )
				|| ! wp_verify_nonce( $_POST['bp_multiple_forum_post'], 'post_to_multiple_forums' ) ) {
			throw new Exception( 'HUMCORE - Nonce failure' );
		}
		*/
		return array(
			'aggregator-post-id' => $_POST['aggregator-post-id'],
		);
	}

	/**
	 * Do the wp_async_humcore_tika_text_extraction action.
	 *
	 * Called during the asynchronous wp_http_post() request.
	 * Passes along the data prepared in prepare_data() above to humcore_extract_text_with_tika().
	 *
	 * @see humcore_extract_text_with_tika()
	**/
	protected function run_action() {

		//humcore_write_error_log( 'info', sprintf( '*****HumCORE Deposit***** - Tika text extract run action %1$s', $_POST['aggregator-post-id'] ) );

		$args                       = array();
		$args['aggregator-post-id'] = $_POST['aggregator-post-id'];

		do_action( 'wp_async_' . $this->action, $args );
	}
}
