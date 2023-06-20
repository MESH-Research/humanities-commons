<?php
/**
 * Adds compatibility to Buddypress and bbpress to the Avatar Privacy plugin
 *
 * @package Hc_Custom
 */

/**
 * Retrieves the salt for current the site/network.
 *
 * @return string
 */
function hc_custom_get_salt() {

	$salt = apply_filters( 'avatar_privacy_salt', '' );

	if ( empty( $salt ) ) {
		// Let's try the network option next.
		$network_id = ! empty( get_current_network_id() );
		$salt       = get_network_option( $network_id, $raw ? $option : 'avatar_privacy_salt', $default );

		if ( is_array( $default ) && '' === $value ) {
			$salt = [];
		}

		if ( empty( $salt ) ) {
			// Still nothing? Generate a random value.
			$salt = mt_rand();

			// Save the generated salt.
			update_network_option( $network_id, $raw ? $option : 'avatar_privacy_salt', $value );
		}
	}

	return $salt;
}

/**
 * Validates if a gravatar exists for the given e-mail address. Function originally
 * taken from: http://codex.wordpress.org/Using_Gravatars
 *
 * @param  string $email    The e-mail address to check.
 * @param  int    $age      Optional. The age of the object associated with the email address. Default 0.
 * @param  string $mimetype Optional. Set to the mimetype of the gravatar if present. Passed by reference. Default null.
 *
 * @return bool             True if a gravatar exists for the given e-mail address,
 *                          false otherwise, including if gravatar.com could not be
 *                          reached or answered with a different error code or if
 *                          no e-mail address was given.
 */
function hc_custom_validate_gravatar( $email = '', $age = 0, &$mimetype = null ) {
	// Make sure we have a real address to check.
	if ( empty( $email ) ) {
		return false;
	}

	// Build the hash of the e-mail address.
	$hash = md5( strtolower( trim( $email ) ) );

	// Try to find it via transient cache. On multisite, we use site transients.
	$transient_key = "avatar_privacy_check_{$hash}";

	$result = get_site_transient( $transient_key );

	if ( false !== $result ) {
		$validate_gravatar_cache[ $hash ] = $result;
		if ( null !== $mimetype && ! empty( $result ) ) {
			$mimetype = $result;
		}

		return ! empty( $result );
	}

	// Ask gravatar.com.
	$response = wp_remote_head( "https://gravatar.com/avatar/{$hash}?d=404" );
	if ( $response instanceof WP_Error ) {
		return false; // Don't cache the result.
	}

	switch ( wp_remote_retrieve_response_code( $response ) ) {
		case 200:
			// Valid image found.
			$result = wp_remote_retrieve_header( $response, 'content-type' );
			if ( null !== $mimetype && ! empty( $result ) ) {
				$mimetype = $result;
			}
			break;

		case 404:
			// No image found.
			$result = 0;
			break;

		default:
			return false; // Don't cache the result.
	}

	// Cache the result across all blogs (a YES for 1 week, a NO for 10 minutes or longer,
	// depending on the age of the object (comment, post), since a YES basically shouldn't
	// change, but a NO might change when the user signs up with gravatar.com).
	$duration = WEEK_IN_SECONDS;
	if ( empty( $result ) ) {
		$duration = ( $age < HOUR_IN_SECONDS  ? 10 * MINUTE_IN_SECONDS : ( $age < DAY_IN_SECONDS ? HOUR_IN_SECONDS : ( $age < WEEK_IN_SECONDS ? DAY_IN_SECONDS : $duration ) ) );
	}

	/**
	 * Filters the interval between gravatar validation checks.
	 *
	 * @param int  $duration The validation interval. Default 1 week if the check was successful, less if not.
	 * @param bool $result   The result of the validation check.
	 * @param int  $age      The "age" (difference between now and the creation date) of a comment or post (in sceonds).
	 */
	$duration = apply_filters( 'avatar_privacy_validate_gravatar_interval', $duration, ! empty( $result ), $age );

	$set_site_transient = set_site_transient( $transient_key, $result, $duration );

	$validate_gravatar_cache[ $hash ] = $result;

	return ! empty( $result );
}
/**
 * Filters the result of Buddypress's fetch avatar function
 *
 * @param string $avatar_html  Image tag for the user's avatar.
 * @param array  $params       Arguments associated with the avatar request.
 **/
function hc_custom_bp_core_fetch_avatar( $avatar_html, $params ) {
	$mimetype = '';

	$doc = new DOMDocument();
	$doc->loadHTML( $avatar_html );
	$xpath = new DOMXPath( $doc );
	$src   = $xpath->evaluate( 'string(//img/@src)' );

	if ( ! strstr( $src, 'gravatar' ) ) {
		return $avatar_html;
	}

	$user_id = $params['item_id'];
	$email   = $params['email'];

	// Generate the hash.
	$hash = '';

	if ( ! empty( $user_id ) ) {
		$hash = get_user_meta( $user_id, 'avatar_privacy_hash', true );

		if ( empty( $hash ) ) {
			$user       = get_user_by( 'ID', $user_id );
			$user_email = $user->user_email;
			$hash       = hash( 'sha256', "{hc_custom_get_salt()}{$user_email}" );

			update_user_meta( $user_id, 'avatar_privacy_hash', $hash );
		}
	} elseif ( ! empty( $email ) ) {
		$hash = hash( 'sha256', "{hc_custom_get_salt()}{$email}" );
	}

	// Check if a gravatar exists for the e-mail address.
	if ( empty( $email ) ) {
		$show_gravatar = false;
	} else {

		/**
		 * Filters whether we check if opting-in users and commenters actually have a Gravatar.com account.
		 *
		 * @param bool      $enable_check Defaults to true.
		 * @param string    $email        The email address.
		 * @param int|false $user_id      A WordPress user ID (or false).
		 */
		if ( apply_filters( 'avatar_privacy_enable_gravatar_check', true, $email, $user_id ) ) {
			$show_gravatar = hc_custom_validate_gravatar( $email, 0, $mimetype );
		}
	}

	$url = apply_filters(
		'avatar_privacy_default_icon_url', includes_url( 'images/blank.gif' ), $hash, $params['width'], [
			'default' => 'identicon',
		]
	);

	// Maybe display a Gravatar.
	if ( $show_gravatar ) {
		if ( empty( $mimetype ) ) {
			$mimetype = 'image/png';
		}

		$url = apply_filters(
			'avatar_privacy_gravatar_icon_url', $url, $hash, $params['width'], [
				'user_id'  => $user_id,
				'email'    => $email,
				'rating'   => 'g',
				'mimetype' => $mimetype,
			]
		);
	}

	$args['url'] = $url;

	foreach ( $doc->getElementsByTagName( 'img' ) as $img ) {
		$img->setAttribute( 'src', $args['url'] );
	}

	$content = $doc->saveHTML();

	return $content;
}

/**
 * Retrieve the avatar `<img>` tag for a user, email address, MD5 hash, comment, or post for bbpress.
 *
 *  @param string            $avatar       Image tag for the user's avatar.
 *  @param int|string|object $id_or_email  The Gravatar to retrieve. Accepts a user_id, user email, WP_User object, WP_Post object, or WP_Comment object.
 *  @param int               $size         Square avatar width and height in pixels to retrieve.
 *  @param string            $default      Optional URL for the default image or a default type.
 *  @param string            $alt          Alternative text to use in the avatar image tag.
 **/
function hc_custom_get_avatar( $avatar, $id_or_email, $size, $default, $alt ) {
	$user_id = false;
	$email   = '';
	$age     = 0;

	if ( is_numeric( $id_or_email ) ) {
		$user_id = absint( $id_or_email );
	} elseif ( is_string( $id_or_email ) ) {
		// E-mail address.
		$email = $id_or_email;
	} elseif ( $id_or_email instanceof WP_User ) {
		// User object.
		$user_id = $id_or_email->ID;
		$email   = $id_or_email->user_email;
	} elseif ( $id_or_email instanceof WP_Post ) {
		// Post object.
		$user_id = (int) $id_or_email->post_author;
		$age     = time() - mysql2date( 'U', $id_or_email->post_date_gmt );
	} elseif ( $id_or_email instanceof WP_Comment ) {
		/** This filter is documented in wp-includes/pluggable.php */
		$allowed_comment_types = apply_filters( 'get_avatar_comment_types', [ 'comment' ] );

		if ( ! empty( $id_or_email->comment_type ) && ! in_array( $id_or_email->comment_type, (array) $allowed_comment_types, true ) ) {
			return [ false, '' ]; // Abort.
		}

		if ( ! empty( $id_or_email->user_id ) ) {
			$user_id = (int) $id_or_email->user_id;
		}
		if ( empty( $user_id ) && ! empty( $id_or_email->comment_author_email ) ) {
			$email = $id_or_email->comment_author_email;
		}
	}

	if ( ! empty( $user_id ) ) {
		$avatar = bp_core_fetch_avatar(
			array(
				'item_id' => $user_id,
				'height'  => $size,
				'width'   => $size,
				'default' => 'identicon',
			)
		);
	}

	return $avatar;
}

if ( function_exists( 'run_avatar_privacy' ) ) {
	add_filter( 'get_avatar', 'hc_custom_get_avatar', 10, 5 );
	add_filter( 'bp_core_fetch_avatar', 'hc_custom_bp_core_fetch_avatar', 12, 2 );
}

