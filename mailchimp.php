<?php
/**
 * Integration with MailChimp. Adds new users to MailChimp list with the 'new-user' tag.
 * 
 * This script requires MAILCHIMP_LIST_ID, MAILCHIMP_API_KEY, and MAILCHIMP_DC to be defined in .env.
 * 
 * Note: See dev-scripts/mailchimp/update-mailchimp.php for reccurring update script.
 */

 /**
  * Add user to MailChimp list on user registration.
  */
function hcommons_add_new_user_to_mailchimp( $user_id, $userdata ) {

	$user = get_user_by( 'id', $user_id );
	if ( ! $user ) {
		hcommons_write_error_log( 'error', 'Mailchimp user creation failed: no user found for ID ' . $user_id );
		return;
	}

	// Make sure user has member types set. This function normally triggers on bp_init, but we
	// can't count on that for newly-registered users.
	hcommons_set_user_member_types( $user );
	
	if ( ! isset( $userdata['user_email'] ) ) {
		hcommons_write_error_log( 'error', 'Mailchimp user creation failed: no email address provided.' );
		return;
	}

	$existing_mailchimp_response = hcommons_mailchimp_request(
		'/lists/' . MAILCHIMP_LIST_ID . '/members/' . $userdata['user_email']
	);

	$mailchimp_user_id = '';
	$request_method = 'POST';
	if ( is_array( $existing_mailchimp_response ) && isset( $existing_mailchimp_response['email_address'] ) ) {
		hcommons_write_error_log( 'info', 'Mailchimp user exists for email ' . $userdata['user_email'] );
		if ( $existing_mailchimp_response['status'] === 'archived') {
			$mailchimp_user_id = $existing_mailchimp_response['id'];
			$request_method = 'PUT';
		} else {
			hcommons_write_error_log( 'info', 'Mailchimp user exists and is not archived for email ' . $userdata['user_email'] );
			return;
		}
	}

	$member_types = bp_get_member_type( $user_id, false );
	if ( ! is_array( $member_types ) || empty( $member_types ) ) {
		$member_types = [ "hc" ];
	}
	$tags = array_merge( $member_types, [ 'new-user' ] );

	$mailchimp_response = hcommons_mailchimp_request(
		'/lists/' . MAILCHIMP_LIST_ID . '/members/' . $mailchimp_user_id,
		$request_method,
		[
			'email_address' => $userdata['user_email'],
			'status'        => 'subscribed',
			'merge_fields'  => [
				'FNAME'    => $userdata['first_name'],
				'LNAME'    => $userdata['last_name'],
				'DNAME'    => $user->display_name,
				'USERNAME' => $userdata['user_login'],
			],
			'tags'          => $tags,
			'interests'     => [
				MAILCHIMP_NEWSLETTER_GROUP_ID => true, // Newsletter
			],
		]
	);

	if ( is_array( $mailchimp_response ) && isset( $mailchimp_response['id'] ) ) {
		hcommons_write_error_log( 'info', 'Mailchimp user created for email ' . $userdata['user_email'] . ' with status ' . $mailchimp_response['status'] );
	} else {
		hcommons_write_error_log( 'error', 'Mailchimp user creation failed. Response:' . var_export( $mailchimp_response, true ) );
	}
}
add_action( 'user_register', 'hcommons_add_new_user_to_mailchimp', 10, 2 );

/**
 * Remove user from MailChimp list on user deletion.
 */
function hcommons_remove_user_from_mailchimp( $user_id ) {
	$user = get_user_by( 'id', $user_id );
	
	if ( ! $user ) {
		hcommons_write_error_log( 'error', 'Mailchimp user deletion failed: no user found for ID ' . $user_id );
		return;
	}
	
	hcommons_write_error_log( 'info', 'Removing user ' . $user->user_login . ' from Mailchimp.');

	$existing_mailchimp_response = hcommons_mailchimp_request(
		'/lists/' . MAILCHIMP_LIST_ID . '/members/' . $user->user_email
	);

	if ( is_array( $existing_mailchimp_response ) && isset( $existing_mailchimp_response['email_address'] ) ) {
		$mailchimp_user_id = $existing_mailchimp_response['id'];
		$mailchimp_response = hcommons_mailchimp_request(
			'/lists/' . MAILCHIMP_LIST_ID . '/members/' . $mailchimp_user_id,
			'DELETE',
			[]
		);

		if ( $mailchimp_response !== false ) {
			hcommons_write_error_log( 'info', 'Mailchimp user deleted for email ' . $user->user_email );
		} else {
			hcommons_write_error_log( 'error', 'Mailchimp user deletion failed. Response:' . var_export( $mailchimp_response, true ) );
		}
	} else {
		hcommons_write_error_log( 'info', 'Mailchimp deletiion falied: user does not exist for email ' . $user->user_email );
	}
}
add_action( 'delete_user', 'hcommons_remove_user_from_mailchimp', 10, 1 );
add_action( 'wpmu_delete_user', 'hcommons_remove_user_from_mailchimp', 10, 1 );

/**
 * Make a request to the MailChimp API and return the response body.
 *
 * @param string $endpoint The API endpoint to request. Eg. '/lists/12345/members'
 * @param string $method   The HTTP method to use. Eg. 'GET', 'POST', 'PATCH', 'DELETE'
 * @param array  $params   The request parameters. Eg. [ 'email_address' => ' ... ' ]
 */
function hcommons_mailchimp_request( $endpoint, $method='GET', $params=[] ) {
	$api_base = "https://" . MAILCHIMP_DC . ".api.mailchimp.com/3.0";
	$url = $api_base . $endpoint;
	if ( $method === 'GET' ) {
		$url .= '?' . http_build_query( $params );
		$body = '';
	} else {
		$body = json_encode( $params );
	}

	$auth_string = 'Basic ' . base64_encode( 'HumanitiesCommons:' . MAILCHIMP_API_KEY );

	$response = wp_remote_request( 
		$url,
		[
			'url'    => $url,
			'method' => $method,
			'headers' => [
				'Authorization' => $auth_string,
				'Content-Type'  => 'application/json',
			],
			'body'   => $body,
		]
	);

	if ( is_wp_error( $response ) ) {
		hcommons_write_error_log( 'error', 'MailChimp request error: ' . $response->get_error_message() );
		return false;
	}

	$response_body = json_decode( $response['body'], true );
	return $response_body;
}