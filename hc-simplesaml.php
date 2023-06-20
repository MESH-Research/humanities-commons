<?php
/**
 * Actions & filters relating to user authentication.
 * SimpleSAML-related functions.
 *
 * @package Commons
 */

/**
 * Filter the login redirect to send users to the frontend rather than the dashboard.
 *
 * @param string $location
 * @return string Modified url
 */
function hcommons_remove_admin_redirect( $location ) {
	remove_filter( 'login_redirect', 'buddyboss_redirect_previous_page', 10, 3 );

	if ( false !== strpos( $location, 'wp-admin' ) ) {
		$location = get_site_url();
	}

	return $location;
}
// priority 5 to run before buddyboss_redirect_previous_page
add_filter( 'login_redirect', 'hcommons_remove_admin_redirect', 5 );

/**
 * Handle a failed login attempt. Determine if the user has visitor status.
 *
 * @param string $username User who is attempting to log in.
 */
function hcommons_login_failed( $username ) {

	$referrer = $_SERVER['HTTP_REFERER'];

	// TODO fix path for simplesaml
	if ( ! empty( $referrer ) && strstr( $referrer, 'idp/profile/SAML2/Redirect/SSO?' ) ) {
		if ( ! strstr( $_SERVER['REQUEST_URI'], '/not-a-member' ) && ! strstr( $_SERVER['REQUEST_URI'], '/inactive-member' ) ) { // one redirect
			wp_redirect( 'https://' . $_SERVER['HTTP_X_FORWARDED_HOST'] . '/not-a-member' );
			exit();
		}
	}
}
//add_action( 'wp_login_failed', 'hcommons_login_failed' );

/**
 * Syncs the HCommons managed WordPress profile data to HCommons XProfile Group fields.
 *
 * @param object $user   User object whose profile is being synced. Passed by reference.
 */
function hcommons_sync_bp_profile( $user ) {
	hcommons_set_env_saml_attributes();

	$user_id = $user->ID;

	hcommons_write_error_log( 'info', '****SYNC_BP_PROFILE****-'.var_export( $user->ID, true ) );

	$current_name = xprofile_get_field_data( 'Name', $user->ID );
	if ( empty( $current_name ) ) {
		$server_displayname = !empty($_SERVER['HTTP_DISPLAYNAME']) && isset($_SERVER['HTTP_DISPLAYNAME']) ? $_SERVER['HTTP_DISPLAYNAME'] : '';
		$name = $server_displayname;  // user record maybe not fully populated for first time users.
		if ( ! empty( $name ) ) {
			xprofile_set_field_data( 'Name', $user->ID, $name );
		}
	}

	$current_title = xprofile_get_field_data( 'Title', $user->ID );
	if ( empty( $current_title ) ) {
		$titles = maybe_unserialize( get_user_meta( $user->ID, 'shib_title', true ) );
		if ( is_array( $titles ) ) {
			$title = $titles[0];
		} else {
			$title = $titles;
		}
		if ( ! empty( $title ) ) {
			xprofile_set_field_data( 'Title', $user->ID, $title );
		}
	}

	$current_org = xprofile_get_field_data( 'Institutional or Other Affiliation', $user->ID );
	if ( empty( $current_org ) ) {
		$orgs = maybe_unserialize( get_user_meta( $user->ID, 'shib_org', true ) );
		if ( is_array( $orgs ) ) {
			$org = $orgs[0];
		} else {
			$org = $orgs;
		}
		if ( ! empty( $org ) ) {
			xprofile_set_field_data( 'Institutional or Other Affiliation', $user->ID, str_replace( 'Mla', 'MLA', $org ) );
		}
	}

	$current_orcid = xprofile_get_field_data( 18, $user->ID );
	if ( empty( $current_orcid ) ) {
		$orcid = get_user_meta( $user->ID, 'shib_orcid', true );
		if ( ! empty( $orcid ) ) {
			xprofile_set_field_data( 18, $user->ID, $orcid );
		}
	}

}
add_action( 'wp_saml_auth_existing_user_authenticated', 'hcommons_sync_bp_profile' );

/**
 * Sync user memberships from COmanage for organizations and managed groups.
 *
 * For societies, user must be a member of that socitey in COmanage to be a
 * member of that organization in WP. For unmanaged organization groups, remove
 * group membership for users who are not a member of that organization. For
 * managed groups (set in WordPress admin for that group), add/remove users
 * based on group membership in COmanage.
 *
 * @param WP_User $user The user.
 */
function hcommons_set_user_member_types( $user ) {

	if ( ! $user ) {
		$user = wp_get_current_user();
	}

	if ( $user->ID === 0 ) {
		return;
	}
	
	$user_info = get_userdata( $user->ID );
	hcommons_write_error_log( 'info', "Setting user member types for {$user_info->user_login}." );

	$memberships = Humanities_Commons::hcommons_get_user_memberships();
	hcommons_write_error_log( 'info', "Memberships passed from SAML: " . var_export( $memberships, true ) );

	if ( count( $memberships['societies'] ) === 0 && count( $memberships['groups'] ) === 0 ) {
		hcommons_write_error_log( 'info', "User is logged in but has expired SAML session, so don't change anything." );
		return;
	}
	
	// Everyone should be a member of hc. Ensure that they are if something has gone wrong in COmanage.
	if ( ! in_array( 'hc', $memberships['societies'] ) ) {
		$memberships['societies'][] = 'hc';
	}

	bp_set_member_type( $user->ID, '' ); // Clear existing types, if any.

	foreach( $memberships['societies'] as $member_type ) {
		hcommons_write_error_log( 'info', "Adding {$user_info->user_login} to $member_type." );
		bp_set_member_type( $user->ID, $member_type, true );
	}

	$user_groups = groups_get_groups( ['user_id' => $user->ID ] );
	hcommons_write_error_log( 'info', "User Groups: " . var_export( $user_groups, true ) );

	// Sync group membership for managed groups.
	$managed_groups = Humanities_Commons::hcommons_get_managed_groups();
	foreach ( $managed_groups as $society_id => $society_groups ) {
		foreach ( $society_groups as $group_name => $group_id ) {
			if ( 
				array_key_exists( $society_id, $memberships['groups'] ) &&
				in_array( $group_name, $memberships['groups'][ $society_id ] )
			) {
				hcommons_write_error_log( 'info', "Adding {$user_info->user_login} to $society_id group $group_name" );
				groups_join_group( $group_id, $user->ID );
			} elseif ( in_array( $group_id, array_map( function( $g ) { return $g->id; }, $user_groups['groups'] ) ) ) {
				hcommons_write_error_log( 'info', "Removing {$user_info->user_login} from $society_id group $group_name" );
				hcommons_safe_leave_group( $group_id, $user->ID );
			}
		}
	}

	// Remove a user from society groups if they are not a member of that society.
	if ( array_key_exists( 'groups', $user_groups ) ) {
		foreach ( $user_groups['groups'] as $user_group ) {
			$group_type = bp_groups_get_group_type( $user_group->id );
			if ( ! $group_type ) {
				continue;
			}
			if ( ! in_array( $group_type, $memberships['societies'] ) ) {
				hcommons_write_error_log( 'info', "Removing {$user_info->user_login} from society group {$user_group->name}." );
				hcommons_safe_leave_group( $user_group->id, $user->ID );
			}
		}
	}

	hcommons_write_error_log( 'info', "Finished setting user member types for {$user_info->user_login}." );
}
add_action( 'bp_init', 'hcommons_set_user_member_types', 50 );

/**
 * If the user is the only group admin, BuddyPress will not allow them to leave
 * and will throw a message. There's not a good way to suppress the message, so
 * we'll check to make sure they aren't the only admin before trying to remove.
 *
 * @see bp-groups-functions.php::groups_leave_group()
 */
function hcommons_safe_leave_group( $group_id, $user_id ) {
	if ( count( groups_get_group_admins( $group_id ) ) < 2 ) {
		if ( groups_is_user_admin( $user_id, $group_id ) ) {
			return false;
		}
	}

	return groups_leave_group( $group_id, $user_id );
}

/**
 * When a user logs in, if they are a member of the society, ensure that they
 * have at least the subscriber role. If they are not a member of the society,
 * remove all of their roles.
 *
 * Note: the logic of this function is slightly different than the previous
 * version when a user is not the member of a site. The previous version was
 * more thorough in removing user capabilities. (Mike 21-10-26)
 *
 * @param WP_User $user The user who has just logged in.
 */
function hcommons_maybe_set_user_role_for_site( $user ) {
	hcommons_write_error_log( 'info', "hcommons_maybe_set_user_role_for_site - user: {$user->data->user_nicename} current site: " . get_current_blog_id() );
	$memberships = Humanities_Commons::hcommons_get_user_memberships();
	$is_site_member = in_array( Humanities_Commons::$society_id, $memberships['societies'] );

	switch_to_blog( get_main_site_id() );

	hcommons_write_error_log( 'info', "hcommons_maybe_set_user_role_for_site - user: {$user->data->user_nicename} switched to site: " . get_current_blog_id() );

	$base_site_user = get_userdata( $user->ID );
	$user_roles = $base_site_user->roles;
	$site_roles = [ 'subscriber', 'contributor', 'author', 'editor', 'administrator' ];
	$user_site_roles = array_intersect( $user_roles, $site_roles );

	if ( $is_site_member ) {
		if ( empty( $user_site_roles ) ) {
			hcommons_write_error_log( 'info', "hcommons_maybe_set_user_role_for_site - user: {$user->data->user_nicename} - setting role to subscriber" );
			$base_site_user->set_role( 'subscriber' );
		}
	} else {
		hcommons_write_error_log( 'info', 'hcommons_maybe_set_user_role_for_site - removing roles for site' );
		foreach ( $user_site_roles as $role ) {
			$base_site_user->remove_role( $role );
		}
	}

	restore_current_blog();
}
add_action( 'wp_saml_auth_existing_user_authenticated', 'hcommons_maybe_set_user_role_for_site' );

/**
 * When a user logs in, check if the email address passsed from the IDMS matches
 * the user's current email address. If not, update the user's address
 * accordingly.
 *
 * This supports moving user account administration out of WordPress and into
 * COmanage widgets.
 *
 * Will only update if exactly one email address is passed from COmanage. This
 * prevents unintended changes if the COmanage elector data filter is not
 * operating or a user's record has not been reprovisioned.
 *
 * @see https://github.com/MESH-Research/commons/issues/560
 */
function hcommons_maybe_update_email( $user ) {
	if ( ! $_SERVER['HTTP_MAIL'] ) {
		return;
	}

	$email_addresses = explode( ';', $_SERVER['HTTP_MAIL'] );

	if ( count( $email_addresses ) !== 1 ) {
		return;
	}

	if ( $user->data->user_email !== $email_addresses[0] ) {
		wp_update_user(
			[
				'ID'          => $user->ID,
				'user_email' => $email_addresses[0],
			]
		);
	}
}
add_action( 'wp_saml_auth_existing_user_authenticated', 'hcommons_maybe_update_email', 10, 1 );

function hcommons_dump_new_user_data( $user_args, $attributes ) {

	hcommons_write_error_log( 'info', '****DUMP_NEW_USER_DATA***-'.var_export( $user_args, true ) );
	hcommons_write_error_log( 'info', '****DUMP_NEW_USER_DATA***-'.var_export( $attributes, true ) );
	return $user_args;
}
add_action( 'wp_saml_auth_insert_user', 'hcommons_dump_new_user_data', 10, 2 );

/**
 * Capture shibboleth data in user meta once per shibboleth session
 *
 * @param object $user
 */
function hcommons_set_shibboleth_based_user_meta( $user ) {
	hcommons_set_env_saml_attributes();
	$user_id = $user->ID;
	$login_host = $_SERVER['HTTP_X_FORWARDED_HOST'];
	$result = update_user_meta( $user_id, 'shib_session_id', Humanities_Commons::$shib_session_id );
	$result = update_user_meta( $user_id, 'shib_login_host', $login_host );

	$shib_orcid = isset( $_SERVER['HTTP_EDUPERSONORCID'] ) ? $_SERVER['HTTP_EDUPERSONORCID'] : '';
	if ( ! empty( $shib_orcid ) ) {
		if ( false === strpos( $shib_orcid, ';' ) ) {
			$shib_orcid_updated = str_replace( array( 'https://orcid.org/', 'http://orcid.org/' ), '', $shib_orcid );
			$result = update_user_meta( $user_id, 'shib_orcid', $shib_orcid_updated );
		} else {
			$shib_orcid_updated = array();
			$shib_orcids = explode( ';', $shib_orcid );
			foreach( $shib_orcids as $each_orcid ) {
				if ( ! empty( $each_orcid ) ) {
					$shib_orcid_updated[] = str_replace( array( 'https://orcid.org/', 'http://orcid.org/' ), '', $each_orcid );
				}
			}
			$result = update_user_meta( $user_id, 'shib_orcid', $shib_orcid_updated[0] );
		}
	}

	$shib_org = $_SERVER['HTTP_O'];
	if ( false === strpos( $shib_org, ';' ) ) {
		$shib_org_updated = $shib_org;
		if ( 'Humanities Commons' === $shib_org_updated ) {
			$shib_org_updated = '';
		}
	} else {
		$shib_org_updated = array();
		$shib_orgs = explode( ';', $shib_org );
		foreach( $shib_orgs as $shib_org ) {
			if ( 'Humanities Commons' !== $shib_org && ! empty( $shib_org ) ) {
				$shib_org_updated[] = $shib_org;
			}
		}
	}
	$result = update_user_meta( $user_id, 'shib_org', maybe_serialize( $shib_org_updated ) );

	$http_title = !empty($_SERVER['HTTP_TITLE'] ) && isset($_SERVER['HTTP_TITLE']) ? $_SERVER['HTTP_TITLE'] : '';
	$shib_title = $http_title;
	if ( false === strpos( $shib_title, ';' ) ) {
		$shib_title_updated = $shib_title;
	} else {
		$shib_title_updated = explode( ';', $shib_title );
	}
	$result = update_user_meta( $user_id, 'shib_title', maybe_serialize( $shib_title_updated ) );

	$login_method = Humanities_Commons::hcommons_get_identity_provider( false );
	if ( $login_method ) {
		$user_login_methods = (array) maybe_unserialize( get_usermeta( $user_id, 'saml_login_methods', true ) );
		if ( ! in_array( $_SERVER['HTTP_IDPDISPLAYNAME'], $user_login_methods ) ) {
			$user_login_methods[] = $_SERVER['HTTP_IDPDISPLAYNAME'];
			$result = update_user_meta( $user_id, 'saml_login_methods', maybe_serialize( $user_login_methods ) );
		}
	} else {
		hcommons_write_error_log( 'info', '****HTTP_IDPDISPLAYNAME NOT SET****-' );
	}
	$shib_uid = $_SERVER['HTTP_UID'];
	if ( false === strpos( $shib_uid, ';' ) ) {
		$shib_uid_updated = $shib_uid;
	} else {
		$shib_uid_updated = explode( ';', $shib_uid );
	}
	$result = update_user_meta( $user_id, 'shib_uid', maybe_serialize( $shib_uid_updated ) );

	$shib_ismemberof = $_SERVER['HTTP_ISMEMBEROF'];
	if ( false === strpos( $shib_ismemberof, ';' ) ) {
		$shib_ismemberof_updated = $shib_ismemberof;
	} else {
		$shib_ismemberof_updated = explode( ';', $shib_ismemberof );
	}
	$result = update_user_meta( $user_id, 'shib_ismemberof', maybe_serialize( $shib_ismemberof_updated ) );

	$shib_email = $_SERVER['HTTP_MAIL'];
	if ( false === strpos( $shib_email, ';' ) ) {
		$shib_email_updated = $shib_email;
	} else {
		$shib_email_updated = explode( ';', $shib_email );
	}
	$result = update_user_meta( $user_id, 'shib_email', maybe_serialize( $shib_email_updated ) );

	$shib_identity_provider = $_SERVER['HTTP_SHIB_IDENTITY_PROVIDER'];
	if ( false === strpos( $shib_identity_provider, ';' ) ) {
		$shib_identity_provider_updated = $shib_identity_provider;
	} else {
		$shib_identity_provider_updated = explode( ';', $shib_identity_provider );
	}
	$result = update_user_meta( $user_id, 'shib_identity_provider', maybe_serialize( $shib_identity_provider_updated ) );
}
add_action( 'wp_saml_auth_existing_user_authenticated', 'hcommons_set_shibboleth_based_user_meta' );

/**
 * ensure invite-anyone correctly sets up notifications after user registers
 */
function hcommons_invite_anyone_activate_user( $user ) {
	$meta_key = 'hcommons_invite_anyone_activate_user_done';

	if (
		! empty( $user->user_email ) &&
		! get_user_meta( $user->ID, $meta_key ) &&
		function_exists( 'invite_anyone_activate_user' )
	) {
		invite_anyone_activate_user( $user->ID, null, null );
		update_user_meta( $user->ID, $meta_key, true );
	}
}
// TODO hopefully no longer necessary - test & confirm
//add_action( 'wp_saml_auth_existing_user_authenticated', array( $this, 'hcommons_invite_anyone_activate_user' ) );

/**
 * Check the user's membership to this network prior to login and if valid return the role.
 *
 * @param string $user_role
 * @return string $user_role Role or null.
 */
function hcommons_check_user_site_membership( $user_role ) {

	$username = $_SERVER['HTTP_EMPLOYEENUMBER'];

	$user = get_user_by( 'login', $username );

	$global_super_admins = array();
	if ( defined( 'GLOBAL_SUPER_ADMINS' ) ) {
		$global_super_admin_list = constant( 'GLOBAL_SUPER_ADMINS' );
		$global_super_admins = explode( ',', $global_super_admin_list );
	}

	$memberships = Humanities_Commons::hcommons_get_user_memberships();
	$member_societies = (array)$memberships['societies'];
	if ( ! in_array( Humanities_Commons::$society_id, $member_societies ) && ! in_array( $user->user_login, $global_super_admins ) ) {
		hcommons_write_error_log( 'info', '****CHECK_USER_SITE_MEMBERSHIP_FAIL****-' . var_export( $memberships['societies'], true ) .
			var_export( Humanities_Commons::$society_id, true ) . var_export( $user, true ) );
		return '';
	}

	//Check for existing user role, we don't want to overwrite role assignments made in WP.
	global $wp_roles;
	$user_role_set = false;
	foreach ( $wp_roles->roles as $role_key=>$role_name ) {
		if ( false === strpos( $role_key, 'bbp_' ) ) {
			$user_role_set = user_can( $user, $role_key );
		}
		if ( $user_role_set ) {
			$user_role = $role_key;
			break;
		}
	}
	hcommons_write_error_log( 'info', '****CHECK_USER_SITE_MEMBERSHIP****-' . var_export( $user_role, true ) . var_export( $user_role_set, true ) . var_export( $user->user_login, true ) );

	return $user_role;

}
//add_filter( 'shibboleth_user_role', array( $this, 'hcommons_check_user_site_membership' ) );

/**
 * Filter shibboleth_session_active to set class variable
 *
 * @param bool $active
 * @return bool $active
 */
function hcommons_shibboleth_session_active( $active ) {
	if ( $active ) {
		Humanities_Commons::$shib_session_id = $_SERVER['HTTP_SHIB_SESSION_ID'];
	}
	return $active;
}
// TODO this might need to use init instead, check
add_filter( 'wp_saml_auth_existing_user_authenticated', 'hcommons_shibboleth_session_active' );

/**
 * Check for a saml session in a simplesamlphp environment
 *
 * @return bool $active
 */
function hcommons_saml_session_active() {

		if ( ! class_exists( 'WP_SAML_Auth' ) ) {
			return false;
		}

        if ( ! WP_SAML_Auth::get_instance()->get_provider()->isAuthenticated() ) {
                return false;
        }
	return true;
}

if ( class_exists( 'WP_SAML_Auth' ) ) {
	add_filter( 'wp_saml_auth_option', 'hcommons_wpsa_filter_option', 10, 2 );

	// Before WP_SAML_Auth->action_logout().
	add_action( 'wp_logout', 'hcommons_wpsa_wp_logout', 5 );

	add_action( 'bp_init', 'hcommons_bootstrap_wp_saml_auth', 1 );

	// After WP_SAML_Auth->action_init().
	add_action( 'bp_init', 'hcommons_set_env_saml_attributes', 2 );

	// After hcommons_set_env_saml_attributes().
	add_action( 'bp_init', 'hcommons_auto_login', 3 );
}

/**
 * Set WP SAML Auth configuration options.
 *
 * @param mixed  $value       Configuration value.
 * @param string $option_name Configuration option name.
 */
function hcommons_wpsa_filter_option( $value, string $option_name ) {
	$defaults = array(
		'connection_type'        => 'simplesamlphp',
		'simplesamlphp_autoload' => '/app/simplesamlphp/vendor/simplesamlphp/simplesamlphp/lib/_autoload.php',
		'auth_source'            => 'default-sp',
		'auto_provision'         => true,
		'permit_wp_login'        => false,
		'get_user_by'            => 'login',
		'user_login_attribute'   => 'employeeNumber',
		'user_email_attribute'   => 'mail',
		'display_name_attribute' => 'cn',
		'first_name_attribute'   => 'givenName',
		'last_name_attribute'    => 'sn',
		'default_role'           => get_option( 'default_role' ),
	);
	$value    = isset( $defaults[ $option_name ] ) ? $defaults[ $option_name ] : $value;
	return $value;
}

/**
 * Override WP SAML Auth logout action to use a custom URL.
 */
function hcommons_wpsa_wp_logout() {
	$wpsa = WP_SAML_Auth::get_instance();
	$redirect_url = esc_url( home_url() . '/logged-out' );
	$wpsa->get_provider()->logout( $redirect_url );
}

/**
 * Load WP_SAML_Auth early on bp_init so that BuddyPress has correct session data when loading.
 */
function hcommons_bootstrap_wp_saml_auth() {
	remove_action( 'init', [ WP_SAML_Auth::get_instance(), 'action_init' ] );
	WP_SAML_Auth::get_instance()->action_init();
}

/**
 * Populate $_SERVER with attributes from SimpleSAML for backwards compatibility.
 *
 * Use WP_SAML_Auth::get_instance()->get_provider()->getAttributes() instead of $_SERVER when possible.
 */
function hcommons_set_env_saml_attributes() {
	// This requires wp-saml-auth to be active.
	if ( ! class_exists( 'WP_SAML_Auth' ) ) {
		return;
	}

	$attributes = WP_SAML_Auth::get_instance()->get_provider()->getAttributes();
	$IDP = $_SERVER['HTTP_SHIB_IDENTITY_PROVIDER'] = WP_SAML_Auth::get_instance()->get_provider()->getAuthData('saml:sp:IdP');

	if ( empty( $attributes ) ) {
		return;
	}

	// Most attributes are assigned literally: 'sn' => 'HTTP_SN'. The rest are mapped here.
	$map = [
		'Meta-displayName'  => 'HTTP_META_DISPLAYNAME',
		'Meta-organizationName'  => 'HTTP_META_ORGANIZATIONDISPLAYNAME',
		'Meta-organizationDisplayName'  => 'HTTP_META_ORGANIZATIONNAME',
		'urn:oid:2.5.4.3' => 'HTTP_DISPLAYNAME',
	];

	$mapped = [];

	foreach ( $attributes as $attribute => $value ) {
		if ( 1 === count( $value ) ) {
			// Legacy code expects single values to be strings, not arrays.
			$value = $value[0];
		} else {
			// Accommodate Humanities_Commons::hcommons_get_user_memberships().
			$value = implode( ';', $value );
		}

		if ( isset( $map[ $attribute ] ) ) {
			$mapped[ $map[ $attribute ] ] = $value;
		} else {
			$mapped[ 'HTTP_' . strtoupper( $attribute ) ] = $value;
		}
	}

	foreach ( $mapped as $k => $v ) {
		$_SERVER[ $k ] = $v;
	}

	if ( ! isset( $_SERVER['HTTP_X_FORWARDED_HOST'] ) ) {
		$_SERVER['HTTP_X_FORWARDED_HOST'] = $_SERVER['HTTP_HOST'];
	}

	$_SERVER['HTTP_SHIB_SESSION_ID'] = $_COOKIE['SimpleSAMLCommons'];
	// TODO https://github.com/mlaa/humanities-commons/commit/764f6f41511a7813109c5b95a8b2fcfd444c6662
	$_SERVER['HTTP_SHIB_IDENTITY_PROVIDER'] = $IDP;
};

/**
 * Automatically log in to WordPress with an existing SimpleSAML session.
 */
function hcommons_auto_login() {
	if ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) {
		return;
	}

	if ( ! class_exists( 'WP_SAML_Auth' ) ) {
		return;
	}

	if ( ! WP_SAML_Auth::get_instance()->get_provider()->isAuthenticated() ) {
		return;
	}

	if ( is_user_logged_in() ) {
		return;
	}

	// At this point, we know there's a SimpleSAML session but no WordPress session, so try authenticating.
	hcommons_write_error_log( 'info', sprintf( '%s: authenticating token %s', __METHOD__, $_COOKIE['SimpleSAMLAuthToken'] ) );
	$result = WP_SAML_Auth::get_instance()->do_saml_authentication();

	if ( is_a( $result, 'WP_User' ) ) {
		// Make sure this user is a member of the current site.
		$member_societies = Humanities_Commons::hcommons_get_user_org_memberships();
		if ( ! in_array( Humanities_Commons::$society_id, $member_societies ) ) {
			hcommons_write_error_log( 'info', sprintf( '%s: %s is not a member of %s', __METHOD__, $result->user_login, Humanities_Commons::$society_id ) );
			return;
		}

		// If we made it this far, we know this user is a member of the current site and has an existing session.
		wp_set_current_user( $result->ID );
	} else {
		if ( is_wp_error( $result ) ) {
			hcommons_write_error_log( 'info', sprintf( '%s: %s', __METHOD__, $result->get_error_message() ) );
		} else {
			hcommons_write_error_log( 'info', sprintf( '%s: failed to authenticate', __METHOD__ ) );
		}
	}
}
