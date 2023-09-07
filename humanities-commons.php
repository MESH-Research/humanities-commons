<?php
/**
 * The Humanities Commons Plugin
 *
 * Humanities Commons is a set of functions, filters and actions used to support a specific multi-network BuddyPress configuration.
 *
 * @package Humanities Commons
 * @subpackage Configuration
 */

/**
 * Plugin Name: Humanities Commons
 * Description: Humanities Commons is a set of functions, filters and actions used to support a specific multi-network BuddyPress configuration.
 * Version: 1.0
 * Author: MLA
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MLA\Commons\Plugin\Logging\Logger;

global $hcommons_logger;
$hcommons_logger = new Logger( 'hcommons_error' );
$hcommons_logger->createLog( 'hcommons_error' );

/**
 * Write a formatted HCommons error or informational message.
 */
function hcommons_write_error_log( $error_type, $error_message, $info = null ) {

	global $hcommons_logger;
	try {
		if ( 'info' === $error_type ) {
			if ( empty( $info ) ) {
				$hcommons_logger->addInfo( $error_message );
			} else {
				$hcommons_logger->addInfo( $error_message . ' : ', $info );
			}
		} else {
			$hcommons_logger->addError( $error_message );
		}
	} catch ( Exception $e ) {
		//Do nothing
	}
	
}

require_once ( dirname( __FILE__ ) . '/society-settings.php' );
require_once ( dirname( __FILE__ ) . '/wpmn-taxonomy-functions.php' );
require_once ( dirname( __FILE__ ) . '/admin-toolbar.php' );
require_once ( dirname( __FILE__ ) . '/hc-simplesaml.php' );
require_once ( dirname( __FILE__ ) . '/class.comanage-api.php' );
require_once ( dirname( __FILE__ ) . '/class-logger.php' );
require_once ( dirname( __FILE__ ) . '/frontend-filters.php' );
require_once ( dirname( __FILE__ ) . '/plugin-hooks.php' );
require_once ( dirname( __FILE__ ) . '/buddypress.php' );
require_once ( dirname( __FILE__ ) . '/mailchimp.php' );

class Humanities_Commons {

	/**
	 * the network called "Humanities Commons" a.k.a. the hub
	 */
	public static $main_network;

	/**
	 * root blog of the main network
	 */
	public static $main_site;

	/**
	 * current society id
	 */
	public static $society_id;

	/**
	 * current shib session id
	 */
	public static $shib_session_id;

	public function __construct() {

		if ( defined( 'HC_SITE_ID' ) ) {
			self::$main_network = get_network( (int) HC_SITE_ID );
		} else {
			self::$main_network = get_network( (int) '1' );
		}

		self::$main_site = get_site_by_path( self::$main_network->domain, self::$main_network->path );
		self::$society_id = get_network_option( '', 'society_id' );

		add_filter( 'bp_get_signup_page', function() { return '/membership/'; } );
		add_filter( 'bp_get_taxonomy_term_site_id', array( $this, 'hcommons_filter_bp_taxonomy_storage_site' ), 10, 2 );
		add_filter( 'wpmn_get_taxonomy_term_site_id', array( $this, 'hcommons_filter_hc_taxonomy_storage_site' ), 10, 2 );
		add_action( 'bp_after_has_members_parse_args', array( $this, 'hcommons_set_members_query' ) );
		add_filter( 'bp_before_has_groups_parse_args', array( $this, 'hcommons_set_groups_query_args' ) );
		add_filter( 'groups_get_groups', array( $this, 'hcommons_groups_get_groups' ), 10, 2 );
		add_action( 'groups_create_group_step_save_group-details', array( $this, 'hcommons_set_group_type' ) );
		add_action( 'groups_create_group_step_save_group-details', array( $this, 'hcommons_set_group_mla_oid' ) );
		add_filter( 'invite_anyone_send_follow_requests_on_acceptance', '__return_false' );
		add_filter( 'bp_before_has_blogs_parse_args', array( $this, 'hcommons_set_network_blogs_query' ) );
		add_filter( 'bp_get_total_blog_count', array( $this, 'hcommons_get_total_blog_count' ) );
		add_filter( 'bp_get_total_blog_count_for_user', array( $this, 'hcommons_get_total_blog_count_for_user' ) );
		add_filter( 'bp_before_has_activities_parse_args', array( $this, 'hcommons_set_network_activities_query' ) );
		add_filter( 'bp_activity_get_where_conditions', array( $this, 'hcommons_filter_activity_where_conditions' ) );
		add_action( 'bp_activity_after_save', array( $this, 'hcommons_set_activity_society_meta' ) );
		add_action( 'bp_notification_after_save', array( $this, 'hcommons_set_notification_society_meta' ) );
		add_filter( 'bp_activity_get_permalink', array( $this, 'hcommons_filter_activity_permalink' ), 10, 2 );
		add_filter( 'body_class', array( $this, 'hcommons_society_body_class_name' ) );
		// this filter makes 'bp_xprofile_change_field_visibility' false which is required for profile plugin visibility controls
		// doesn't work with local users without a member type, but also doesn't work when member type & blog_id don't match?
		// should always return true for any logged-in user, since visibility controls on xprofile fields are not restricted
		//add_filter( 'bp_current_user_can', array( $this, 'hcommons_check_site_member_can' ), 10, 4 );
		add_filter( 'bp_get_groups_directory_permalink', array( $this, 'hcommons_set_groups_directory_permalink' ) );
		add_filter( 'bp_get_group_permalink', array( $this, 'hcommons_set_group_permalink' ),10, 2 );
		add_filter( 'bp_core_get_user_domain', array( $this, 'hcommons_set_members_directory_permalink' ),10, 4 );
		add_filter( 'get_blogs_of_user', array( $this, 'hcommons_filter_get_blogs_of_user'), 10, 3 );
		add_filter( 'bp_core_avatar_upload_path', array( $this, 'hcommons_set_bp_core_avatar_upload_path' ) );
		add_filter( 'bp_core_avatar_url', array( $this, 'hcommons_set_bp_core_avatar_url' ) );

		// disable in favor of bp-blog-avatar
		// see https://buddypress.trac.wordpress.org/ticket/6544
		add_filter( 'bp_is_blogs_site-icon_active', '__return_false' );

		add_filter( 'bp_get_group_join_button', array( $this, 'hcommons_check_bp_get_group_join_button' ), 10, 2 );

		add_action( 'pre_user_query', array( &$this, 'hcommons_filter_site_users_only' ) ); // do_action_ref_array() is used for pre_user_query
		add_filter( 'invite_anyone_is_large_network', '__return_true' ); //hide invite anyone member list on create/edit group screen
		add_action( 'bp_init',  array( $this, 'hcommons_remove_nav_items' ) );
		add_action( 'bp_init', array( $this, 'hcommons_set_default_scope_society' ) );
		add_filter( 'password_protected_login_headertitle', array( $this, 'hcommons_password_protect_title' ) );
		add_filter( 'password_protected_login_headerurl', array( $this, 'hcommons_password_protect_url' ) );
		add_action( 'password_protected_login_messages', array( $this, 'hcommons_password_protect_message' ) );
		add_filter( 'bp_activity_time_since', array( $this, 'hcommons_filter_activity_time_since' ), 10, 2 );
		add_filter( 'bp_attachments_cover_image_upload_dir', array( $this, 'hcommons_cover_image_upload_dir' ), 10, 2 );
		//add_filter( 'bp_attachments_pre_cover_image_ajax_upload', array( $this, 'hcommons_cover_image_ajax_upload' ), 10, 4 );
		add_filter( 'bp_attachments_uploads_dir_get', array( $this, 'hcommons_attachments_uploads_dir_get' ), 10, 2 );
		add_filter( 'bp_attachment_upload_dir', array( $this, 'hcommons_attachment_upload_dir' ), 10, 2 );

		add_filter( 'bp_get_new_group_enable_forum', array( $this, 'hcommons_get_new_group_enable_forum' ) );
		add_action( 'wp_ajax_hcommons_settings_general', array( $this, 'hcommons_settings_general_ajax' ) );
		add_filter( 'bp_before_activity_get_parse_args', array( $this, 'hcommons_set_network_admin_activities_query' ) );
		add_action( 'init', array( $this, 'hcommons_remove_bp_settings_general' ) );
		add_action( 'bp_before_group_settings_creation_step', array( $this, 'hcommons_groups_group_before_save') );
		add_action( 'bp_groups_admin_meta_boxes', array( $this, 'hcommons_remove_group_type_meta_boxes' ) );
		add_action( 'bp_groups_admin_meta_boxes', array( $this, 'hcommons_add_group_type_meta_box' ) );
		add_action( 'bp_members_admin_user_metaboxes', array( $this, 'hcommons_remove_member_type_meta_boxes' ), 10, 2 );
		add_action( 'bp_members_admin_user_metaboxes', array( $this, 'hcommons_add_member_type_meta_box' ), 10, 2 );
		add_action( 'bp_groups_admin_meta_boxes', array( $this, 'hcommons_add_manage_group_memberships_meta_box' ) );
		add_action( 'bp_groups_admin_load', array( $this, 'hcommons_save_managed_group_membership' ) );
		add_filter( 'bp_docs_map_meta_caps', array( $this, 'hcommons_check_docs_new_member_caps' ), 10, 4 );
		add_filter( 'wpmu_active_signup', array( $this, 'hcommons_check_sites_new_member_status' ) );
		add_shortcode( 'hcommons_society_page', array( $this, 'hcommons_get_society_page_by_slug' ) );
		add_shortcode( 'hcommons_env_variable', array( $this, 'hcommons_get_env_variable' ) );
		add_filter( 'bp_blogs_format_activity_action_new_blog_post', array( $this, 'hcommons_blogs_format_activity_new_blog_post' ),  10, 2 );
		add_filter( 'bp_blogs_format_activity_action_new_blog_comment', array( $this, 'hcommons_blogs_format_activity_new_blog_comment' ), 10, 2 );

		// Disable Akismet for BuddyPress docs. Docs are getting spammed when moved around in folders. --Mike 21-08-19
		add_filter( 'bp_docs_post_args_before_save', array( $this, 'hcommons_disable_akismet_for_moving_docs' ), 5, 3 );

		// Add hcommons.org to list of allowed redirect hosts on dev, for site importing purposes. --Mike 21-12-10
		add_filter( 'http_request_host_is_external', array( $this, 'allow_external_hcommons'), 10, 3 );

		add_filter( 'user_has_cap', array( $this, 'hcommons_vet_user_for_bpeo' ), 10, 4 );
		add_filter( 'map_meta_cap', array( $this, 'hcommons_bpeo_event_creation_capability' ), 20, 4 );
		add_filter( 'bp_loggedin_user_id', array( $this, 'hcommons_bp_loggedin_user_id'), 10, 1 );
		add_filter( 'bp_follow_blogs_show_footer_button', array( $this, 'hcommons_filter_show_footer_button' ), 10, 1 );
		add_filter( 'bp_follow_blogs_get_follow_button', array( $this, 'hcommons_filter_get_follow_button' ), 10, 3 );
	
		add_action( 'init', array( $this, 'add_hide_site_option' ), 10 , 0 );
	}

	public function allow_external_hcommons( $external, $host, $url ) {
		if ( ! defined('WP_ENV') || ( WP_ENV != 'staging' && WP_ENV != 'development' ) ) {
			return $external;
		}

		if ( ! strpos( $host, 'hcommons.org' ) !== False ) {
			return True;
		}
		return False;
	}

	public static function society_name() {
		if ( ! self::$society_id ) {
			return '';
		}
		switch ( self::$society_id ) {
			case 'hc' :
				return 'Humanities Commons';
			case 'msu' :
				return 'MSU Commons';
			case 'mla' :
				return 'MLA Commons';
			case 'up' :
				return 'UP Commons';
			case 'sah' :
				return 'SAH Commons';
			case 'asees' :
				return 'ASEEES Commons';
			case 'arlisna' :
				return 'ARLIS/NA Commons';
			case 'hastac' :
				return 'HASTAC Commons';
			default :
				return strtoupper( self::$society_id ) . ' Commons';
		}
	}

	/**
	 * Prevent 'Follow Site' and 'Followed Sites' buttons from appearing in footer.
	 *
	 * @see buddypress-followers/_inc/modules/blogs.php::show_footer_button()
	 * 
	 * @param boolean $retval Whether buttons should appear
	 * @return boolean Whether buttons should appear (false)
	 */
	public function hcommons_filter_show_footer_button( $retval ) {
		return false;
	}

	/**
	 * 
	 * @see buddypress-followers/_inc/modules/blogs.php::get_button()
	 * 
	 * @return array Empty array to prevent button from showing.
	 * 
	 */
	public function hcommons_filter_get_follow_button( $button, $r, $is_following ) {
		return [];
	}

	/**
	 * Disable Akismet for BuddyPress docs when a doc is being moved.
	 *
	 * This is meant to address an issue where docs get spuriously marked as
	 * spam when being moved.
	 * @link https://github.com/MESH-Research/commons/issues/76
	 *
	 * Note: This disables Akisment whenever a doc is being moved, and doesn't
	 * check whether there are other changes. In theory you could avoid the spam
	 * filter by moving the doc in addition to whatever else you're doing, but
	 * this seems like it'd be a lot of trouble to go through.
	 *
	 * @see buddypress-docs/addon-akismet.php BP_Docs_Akismet::check_for_spam
	 *
	 * @author Mike Thicke
	 *
	 * @global $bp_docs The BP_Docs object. @see buddypress-docs/bp-docs.php
	 *
	 * @param array         $save_args   The arguments to be saved.
	 * @param BP_Docs_Query $bdq_object  The query object.
	 * @param array         $passed_args The arguments passed from the save
	 * request.
	 *
	 * @return array Pass through $save_args unchanged. This filter acts by
	 *               removing a lower priority filter if necessary.
	 */
	public function hcommons_disable_akismet_for_moving_docs( $save_args, $bdq_object, $passed_args ) {
		global $bp_docs;

		$folder_id          = intval( $_POST['bp-docs-folder'] );
		$existing_folder_id = bp_docs_get_doc_folder( $passed_args['doc_id'] );

		if ( $folder_id !== $existing_folder_id ) {
			remove_filter(
				'bp_docs_post_args_before_save',
				array( $bp_docs->akismet, 'check_for_spam' ),
				10
			);
		}

		return $save_args;
	}

	/**
	 * For new blog comment posts in activity feed
	 *
	 * @param  string  $action    current bp_action the user is on in the loop
	 * @param  object  $activity  current activity object in the loop
	 *
	 * @return string $action    corrected current action from activity object
	 */
	public function hcommons_blogs_format_activity_new_blog_comment( $action, $activity ) {

		//force $action to contain the same $activity->type text to avoid issues with titles for comments
		if( $activity->type == 'new_blog_comment' && isset( $activity->action ) ) {
			$action = $activity->action;
		}

		return $action;

	}

	/**
	 * For new blog posts in activity feed
	 *
	 * @param  string  $action    current bp_action the user is on in the loop
	 * @param  object  $activity  current activity object in the loop
	 *
	 * @return string $action    corrected current action from activity object
	 */
	public function hcommons_blogs_format_activity_new_blog_post( $action, $activity ) {

		//force $action to contain the same $activity->type text to avoid issues with titles
		// Make sure there is action text as well.
		if( $activity->type == 'new_blog_post' && isset( $activity->action ) ) {
			$action = $activity->action;
		}

		return $action;
	}

	/**
	 * Handles saving of manage group metabox
	 *
	 * @return void
	 */
	public function hcommons_save_managed_group_membership() {

		//displays what action we are in
		$action = bp_admin_list_table_current_bulk_action();

		//lets check if the request method and action are on post and save
		if( $action == 'save' ) {

			//is the new value set?
			if( isset( $_POST['autopopulate'] ) ) {

				//grabs group_id from get and sanitizes it
				$group_id = filter_var( $_GET['gid'], FILTER_SANITIZE_NUMBER_INT );

				$autopopulate = filter_var( $_POST['autopopulate'], FILTER_SANITIZE_STRIPPED );
				$autopopulate_meta = groups_get_groupmeta( $group_id, 'autopopulate', true );

				//lets update the group meta for manage membership
				if( $autopopulate !== $autopopulate_meta ) {

					groups_update_groupmeta( $group_id, 'autopopulate', $autopopulate );
					wp_cache_delete( 'managed_group_names', 'hcommons_settings' );

				}


			}

		}

	}

	/**
	 * Handles metabox creation for manage membership metabox
	 *
	 * @return void
	 */
	public function hcommons_add_manage_group_memberships_meta_box() {

		if( is_admin() && $_GET['page'] == 'bp-groups' ) {

			add_meta_box(
				'hcommons_admin_groups_manage',
				_x( 'Manage Group Memberships', 'Manages group memberships', 'buddypress' ),
				array( $this, 'hcommons_admin_manage_group_memberships_view' ),
				get_current_screen()->id,
				'side',
				'core'
			);

		}
	}

	/**
	 * Filter the register url to be society specific
	 *
	 * @since HCommons
	 *
	 * @param string $register_url
	 * @return string $register_url Modified url.
	 */
	public static function hcommons_register_url( $register_url ) {

		if ( ! empty( self::$society_id ) && defined( strtoupper( self::$society_id ) . '_ENROLLMENT_URL' ) ) {
			$register_url = constant( strtoupper( self::$society_id ) . '_ENROLLMENT_URL' ) . '/done:core';
		}
		return apply_filters( 'hcommons_register_url', $register_url );
	}

	/**
	 * Outputs view for manage membership metabox
	 *
	 * @return void
	 */
	public function hcommons_admin_manage_group_memberships_view() {

		//grabs group_id from get and sanitizes it
		$group_id = filter_var( $_GET['gid'], FILTER_SANITIZE_NUMBER_INT );
		$autopopulate_meta = groups_get_groupmeta( $group_id, 'autopopulate', true );
?>

		<label>
			<input type="radio" name="autopopulate" value="Y" <?php echo ( $autopopulate_meta == 'Y' ) ? 'checked' : '' ; ?> />Yes
		</label>
		<br />
		<label>
			<input type="radio" name="autopopulate" value="N" <?php echo ( $autopopulate_meta == 'N' ) ? 'checked' : '' ; ?> />No
		</label>

<?php

	}

	/**
	 * Removes member type meta box on user screen in wp-admin
	 *
	 * @return void
	 */
	public function hcommons_remove_member_type_meta_boxes() {

		if( is_admin() && $_GET['page'] == 'bp-profile-edit' ) {
			remove_meta_box( 'bp_members_admin_member_type', 'users_page_bp-profile-edit-network', 'side' );
		}

	}

	/**
	 * Adds new member type meta box on user screen in wp-admin
	 *
	 * @return void
	 */
	public function hcommons_add_member_type_meta_box( $profile, $user_id ) {

		if( is_admin() && $_GET['page'] == 'bp-profile-edit' ) {
			add_meta_box(
				'hcommons_members_admin_member_type',
				_x( 'Member Type', 'members user-admin edit screen', 'buddypress' ),
				array( $this, 'hcommons_member_type_meta_box_view' ),
				get_current_screen()->id,
				'side',
				'core'
			);
		}

	}

	/**
	 * Outputs view for member type meta box on user screen in wp-admin
	 *
	 * @return void
	 */
	public function hcommons_member_type_meta_box_view() {

		if( isset( $_GET['user_id'] ) && is_admin() ) {

			//make sure user id is only numerical
			$user_id = filter_var( $_GET['user_id'], FILTER_SANITIZE_NUMBER_INT );
			$member_types = bp_get_member_type( $user_id, false );

			echo "<ul>";

			//output member types user currently has
			foreach( $member_types as $type ) {

				echo "<li>" . strtoupper( $type ) . "</li>";

			}

			echo "</ul>";

		}

	}

	/**
	 * Adds new group type metabox to user admin area in bp-groups
	 *
	 * @return void
	 */
	public function hcommons_add_group_type_meta_box() {

		if( is_admin() && $_GET['page'] == 'bp-groups' ) {
			add_meta_box(
				'hcommons_admin_group_type',
				_x( 'Group Type', 'groups admin edit screen', 'buddypress' ),
				array( $this, 'hcommons_group_type_meta_box_view' ),
				get_current_screen()->id,
				'side',
				'core'
			);
		}

	}

	/**
	 * Outputs view for new group type metabox
	 *
	 * @return void
	 */
	public function hcommons_group_type_meta_box_view() {

		//make sure group id is only numerical
		$group_id = filter_var( $_GET['gid'], FILTER_SANITIZE_NUMBER_INT );
		$current_types = (array) bp_groups_get_group_type( $group_id, false );

		?>

		<ul class="categorychecklist form-no-clear">
			<?php foreach ( $current_types as $type ) : ?>
				<li>
					<label class="selectit">
						<?php echo strtoupper( esc_html( $type ) ); ?>
					</label>
				</li>

			<?php endforeach; ?>
		</ul>

	<?php

	}

	/**
	 * Removes current group type meta box to be replaced by another
	 *
	 * @return void
	 */
	public function hcommons_remove_group_type_meta_boxes() {

		if( is_admin() && $_GET['page'] == 'bp-groups' ) {
			remove_meta_box( 'bp_groups_admin_group_type', 'toplevel_page_bp-groups-network', 'side' );
		}

	}

	public function hcommons_filter_bp_taxonomy_storage_site( $site_id, $taxonomy ) {

		if ( in_array( $taxonomy, array( 'bp_group_type', 'bp_member_type' ) ) ) {
			return self::$main_site->blog_id;
		} else {
			return $site_id;
		}

	}

	public function hcommons_filter_hc_taxonomy_storage_site( $site_id, $taxonomy ) {

		if ( in_array( $taxonomy, array( 'mla_academic_interests', 'humcore_deposit_language', 'humcore_deposit_subject', 'humcore_deposit_tag',
						 'hcommons_society_member_id' ) ) ) {
			return (int) '1'; // Go legacy during beta.
		} else {
			return $site_id;
		}

	}

	public function hcommons_set_members_query( $args ) {

		if ( ! bp_is_members_directory() || ( isset( $args['scope'] ) && 'society' === $args['scope'] ) ) {
			$args['member_type'] = self::$society_id;
		}
		return $args;
	}

	public function hcommons_set_groups_query_args( $args ) {
		// profile loops per-type, leave as-is
		if ( bp_is_user_profile() || ( bp_is_settings_component() && bp_is_current_action( 'notifications' ) ) ) {
			return $args;
		}

		//hcommons_write_error_log( 'info', '****GROUPS_QUERY_ARGS****-' . var_export( $args, true ) );
		if ( isset( $args['scope'] ) && $args['scope'] == 'personal' ) {
			$args['group_type'] = '';
			return $args;
		}

		if ( is_admin() && ! empty( $_REQUEST['page'] ) && 'bp-groups' == $_REQUEST['page'] ) {
			$args['group_type'] = self::$society_id;
			return $args;
		}

		if ( 'hc' === self::$society_id && empty( $args['scope'] ) && ! self::backtrace_contains( 'class', 'EP_BP_API' ) ) {
			$args['group_type'] = '';
		} else {
			$args['group_type'] = self::$society_id;
		}

		// only show hc groups on /members/*/invite-anyone
		if (
			! is_super_admin() &&
			( bp_is_user() && false !== strpos( $_SERVER['REQUEST_URI'], 'invite-anyone' ) )
		) {
			$args['group_type'] = 'hc';
		}

		return $args;
	}

	/**
	 * on members/groups directories, set default scope to society
	 */
	function hcommons_set_default_scope_society() {
		if ( bp_is_groups_directory() || ( bp_is_members_directory() && 'hc' !== self::$society_id ) ) {
			$object_name = bp_current_component();
			$cookie_name = 'bp-' . $object_name . '-scope';

			if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
				setcookie( $cookie_name, 'society', null, '/' );
				// unless the $_COOKIE global is updated in addition to the actual cookie above,
				// bp will not use the value for the first pageload.
				$_COOKIE[ $cookie_name ] = 'society';
			}
		}
	}

	/**
	 * Target specific occurances of groups_get_groups filter to restrict groups to society and don't show hidden groups.
	 *
	 * @since HCommons
	 *
	 * @param object $data Groups
	 * @param array $r Arguments
	 * @return object $new_groups or $data
	 */
	public function hcommons_groups_get_groups( $data, $r ) {

		if (
			self::backtrace_contains( 'class', 'BuddyPress_Event_Organiser_EO' ) ||
			self::backtrace_contains( 'function', 'bpmfp_get_other_groups_for_user' )
		) {

			$new_groups = BP_Groups_Group::get( array(
				'type'               => $r['type'],
				'user_id'            => $r['user_id'],
				'include'            => $r['include'],
				'exclude'            => $r['exclude'],
				'search_terms'       => $r['search_terms'],
				'group_type'         => self::$society_id,
				'group_type__in'     => $r['group_type__in'],
				'group_type__not_in' => $r['group_type__not_in'],
				'meta_query'         => $r['meta_query'],
				'show_hidden'        => TRUE,
				'per_page'           => $r['per_page'],
				'page'               => $r['page'],
				'populate_extras'    => $r['populate_extras'],
				'update_meta_cache'  => $r['update_meta_cache'],
				'order'              => $r['order'],
				'orderby'            => $r['orderby'],
			) );

			return $new_groups;
		}

		return $data;
	}

	public function hcommons_set_group_type( $group_id ) {

		global $bp;
		if ( $bp->groups->new_group_id ) {
			$id = $bp->groups->new_group_id;
		} else {
			$id = $group_id;
		}

		bp_groups_set_group_type( $id, self::$society_id );
	}

	public function hcommons_set_group_mla_oid( $group_id ) {

                $society_id = self::$society_id;

                if ( 'mla' === $society_id ) {

			global $bp;
			if ( $bp->groups->new_group_id ) {
				$id = $bp->groups->new_group_id;
			} else {
				$id = $group_id;
			}
			$result = groups_add_groupmeta( $id, 'mla_oid', 'UXX', true );
                                if ( is_wp_error( $result ) ) {
					hcommons_write_error_log( 'info', '****MLA_OID_WRITE_FAILURE****-' . $id . '-' . var_export( $result, true ) );
                                        echo "ERROR: " . var_export( $result, true );
                                }
			bp_groups_set_group_type( $id, self::$society_id );
		}
	}

	/**
	 * Get the society_id for the current blog or a given blog.
	 *
	 * @since HCommons
	 *
	 * @param string $blog_id
	 * @return string $blog_society_id or self::$society_id
	 */
	public function hcommons_get_blog_society_id( $blog_id = '' ) {

		$fields = array();
		if ( ! empty( $blog_id ) ) {
			$fields['blog_id'] = $blog_id;
		} else {
			return self::$society_id;
		}
		$blog_details = get_blog_details( $fields );
		$blog_society_id = get_network_option( $blog_details->site_id, 'society_id' );

		return $blog_society_id;
	}

	/**
	 * Filter the count returned by bp_get_total_blog_count() which ultimately depends on BP_Blogs_Blog::get_all().
	 * We want to use the filtered results returned by BP_Blogs_Blog::get() instead, so that we accommodate MPO.
	 *
	 * @since HCommons
	 *
	 * @param string $count
	 * @return string $count
	 */
	public function hcommons_get_total_blog_count( $count ) {
		// let's see what the blogs query will actually include and use that for the count
		$blogs_query_args = $this->hcommons_set_network_blogs_query( array() );

		// now make sure the More Privacy Options filter removes any blogs it needs to
		$mpo_filtered_blogs = bp_blogs_get_blogs( $blogs_query_args );

		if ( $mpo_filtered_blogs ) {
			$count = $mpo_filtered_blogs['total'];
		}

		return $count;
	}

	/**
	 * Like hcommons_get_total_blog_count() except for users.
	 * Because the logged-in logic in BP_Blogs_Blog::get_blogs_for_user() doesn't check the 'public' column,
	 * MPO doesn't need to be accommodated, which is different than in hcommons_get_total_blog_count().
	 *
	 * @since HCommons
	 *
	 * @param string $count
	 * @return string $count
	 */
	public function hcommons_get_total_blog_count_for_user( $count ) {
		$user_blogs = bp_blogs_get_blogs_for_user( get_current_user_id() );


		if ( $user_blogs ) {
			// do not include HC
			foreach ( $user_blogs['blogs'] as $key => $user_blog ) {
				if ( $user_blog->blog_id === self::$main_site->blog_id ) {
					unset( $user_blogs['blogs'][$key] );
				}
			}

			// $user_blogs['total'] is WRONG! that's why this filter is here, just count the actual blogs instead.
			$count = count( $user_blogs['blogs'] );
		}

		return $count;
	}

	/**
	 * Filter the sites query by the society id for the current network except for HC.
	 *
	 * @since HCommons
	 *
	 * @param array $args
	 * @return array $args
	 */
	public function hcommons_set_network_blogs_query( $args ) {

		$blog_ids = array();
		$current_blog_id = get_current_blog_id();


		if (
			'hc' !== self::$society_id &&
			empty( $args['user_id'] ) &&
			! bp_is_current_action('my-sites') &&
			! bp_is_current_component('profile')
		) {

			$current_network = get_current_site();
			$network_sites = wp_get_sites( array( 'network_id' => $current_network->id, 'limit' => 9999 ) );
			foreach( $network_sites as $site ) {
				if ( $site['blog_id'] != $current_blog_id ) {
					$blog_ids[] = $site['blog_id'];
				}
			}
		} else {
			//TODO Find a better way, this won't scale to all of HC.
			$sites = wp_get_sites( array( 'network_id' => null, 'limit' => 9999 ) );
			foreach( $sites as $site ) {
				if ( $site['blog_id'] != $current_blog_id ) {
					$blog_ids[] = $site['blog_id'];
				}
			}
		}

		if ( ! empty( $blog_ids ) ) {
			$include_blogs = implode( ',', $blog_ids );
			$args['include_blog_ids'] = $include_blogs;
		}

		//hcommons_write_error_log( 'info', '****SET_NETWORK_BLOGS_QUERY***-'.var_export( $args, true ) );
		return $args;
	}

	/**
	 * Filter the activity query by the society id for the current network.
	 *
	 * @since HCommons
	 *
	 * @param array $args
	 * @return array $args
	 */
	public function hcommons_set_network_activities_query( $args ) {
		if ( isset( $args['type'] ) && 'sitewide' === $args['type'] ) {
			if ( is_user_logged_in() ) {
				$current_user_id = get_current_user_id();
				$current_user_blog_ids = BP_Blogs_Blog::get_blog_ids_for_user( $current_user_id );
				$current_user_following_ids = bp_follow_get_following( [ 'user_id' => $current_user_id ] );
				$current_user_groups = groups_get_user_groups( $current_user_id );
				$current_user_group_ids = $current_user_groups['groups'];

				$filter_query = array_merge( ( isset( $args['filter_query'] ) ) ? $args['filter_query'] : [], [
					// exclude self
					[
						'column' => 'user_id',
						'value' => $current_user_id,
						'compare' => '!=',
					],

					// otherwise, any of these relevant activities
					[
						'relation' => 'OR',

						// any new deposits, groups, or blogs
						[
							'column' => 'type',
							'value' => [ 'new_deposit', 'new_group_deposit', 'created_group', 'new_blog' ],
							'compare' => 'IN',
						],

						// any activity by my followers
						[
							'column' => 'user_id',
							'value' => $current_user_following_ids,
							'compare' => 'IN',
						],

						// any activity on my blogs
						[
							[
								'column' => 'component',
								'value' => 'blogs',
							],
							[
								'column' => 'item_id',
								'value' => $current_user_blog_ids,
								'compare' => 'IN',
							],
						],

						// any activity on my groups
						[
							[
								'column' => 'component',
								'value' => 'groups',
							],
							[
								'column' => 'item_id',
								'value' => $current_user_group_ids,
								'compare' => 'IN',
							],
						],
					],

				] );

				$args['filter_query'] = $filter_query;
			}
		}

		if ( 'hc' !== self::$society_id && ! bp_is_user_profile() && ! bp_is_user_activity() ) {
			$args['meta_query'] = array(
				array(
					'key'     => 'society_id',
					'value'   => self::$society_id,
					'type'    => 'string',
					'compare' => '='
				),
			);
		}

		return $args;
	}

	/**
	 * Filter the activity query "WHERE" conditions to exclude 'joined_group' (etc.?) types
	 *
	 * @since HCommons
	 *
	 * @param array $args
	 * @return array $args
	 */
	public function hcommons_filter_activity_where_conditions( $args ) {
		// BP_Activity_Activity::get() hardcodes this sql string only if $excluded_types is non-empty,
		// so we can assume a non-empty value here means there is at least one type in the sql array
		if ( ! bp_is_profile_component() && ! empty( $args['excluded_types'] ) ) {
			// these are the types we intend to filter out in addition to whatever is passed to this filter
			$not_in = [ 'joined_group', 'friendship_created' ];

			// parse the existing excluded types and merge with our own
			preg_match_all( "/a.type NOT IN \('(.*)'\)/", $args['excluded_types'], $matches );
			$not_in = array_merge( $not_in, explode( "', '", $matches[1][0] ) );

			// build new sql using combined types
			$args['excluded_types'] = "a.type NOT IN ('" . implode( "', '", $not_in) . "')";
		}

		return $args;
	}

	/**
	 * Add the current society id to the current activity as an activity_meta record.
	 *
	 * @since HCommons
	 *
	 * @param array $activity
	 */
	public function hcommons_set_activity_society_meta( $activity ) {

		bp_activity_add_meta( $activity->id, 'society_id', self::$society_id, true );
	}

	/**
	 * Add the current society id to the current notificaiton as a notification_meta record.
	 *
	 * @since HCommons
	 *
	 * @param array $notification
	 */
	public function hcommons_set_notification_society_meta( $notification ) {

		hcommons_write_error_log( 'info', '****SET_NOTIFICATION_SOCIETY_META***-'.var_export( $notification, true ) );
		bp_notifications_add_meta( $notification->id, 'society_id', self::$society_id, true );
	}

	/**
	 * Set the activity permalink to contain the proper network.
	 *
	 * @since HCommons
	 *
	 * @param string $link
	 * @param object $activity Passed by reference.
	 * @return string $link
	 */
	public function hcommons_filter_activity_permalink( $link, $activity ) {

		$activity_society_id = bp_activity_get_meta( $activity->id, 'society_id', true  );
		if ( self::$society_id == $activity_society_id ) {
			return $link;
		}

		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT site_id FROM $wpdb->sitemeta WHERE meta_key = '%s' AND meta_value = '%s'", 'society_id', $activity_society_id ) );
		if ( is_object( $row ) ) {
			$society_network = get_network( $row->site_id );
			$scheme = ( is_ssl() ) ? 'https://' : 'http://';
			$activity_root_domain = $scheme . $society_network->domain . $society_network->path;
		}
		$society_activity_link = str_replace( trailingslashit( bp_get_root_domain() ), $activity_root_domain, $link );
		//hcommons_write_error_log( 'info', '****FILTER_ACTIVITY_PERMALINK***-'.$link.'-'.$society_activity_link.'-'.bp_get_root_domain().'-'.self::$society_id.'-'.var_export( $activity->id, true ) );

		return $society_activity_link;

	}

	/**
	 * Add the current society id to the body classes.
	 *
	 * @since HCommons
	 *
	 * @param array $classes
	 * @return array $classes
	 */
	public function hcommons_society_body_class_name( $classes ) {

		if ( hcommons_saml_session_active() ) {
			$classes[] = 'active-session';
			$user_memberships = self::hcommons_get_user_memberships();
			if ( ! in_array( self::$society_id, $user_memberships['societies'] ) ) {
				$classes[] = 'non-member';
			}
		}
		$classes[] = 'society-' . self::$society_id;
		return $classes;
	}

	/**
	 * Set the group permalink to contain the proper network.
	 *
	 * @since HCommons
	 *
	 * @param string $group_permalink
	 * @return string $group_permalink Modified url.
	 */
	public function hcommons_set_groups_directory_permalink( $group_permalink ) {
		global $groups_template;

		if ( ! empty( $groups_template->group ) ) {
			$group_id = bp_get_group_id();
			$group_society_id = bp_groups_get_group_type( $group_id );

			if ( $group_society_id === self::$society_id ) {
				return $group_permalink;
			}

			global $wpdb;
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT site_id FROM $wpdb->sitemeta WHERE meta_key = '%s' AND meta_value = '%s'", 'society_id', $group_society_id ) );
			if ( is_object( $row ) ) {
				$society_network = get_network( $row->site_id );
				$scheme = ( is_ssl() ) ? 'https://' : 'http://';
				$group_permalink = trailingslashit( $scheme . $society_network->domain . $society_network->path . bp_get_groups_root_slug() );
			}
		}

		return $group_permalink;
	}

	/**
	 * Set a given group permalink to contain the proper network.
	 *
	 * @since HCommons
	 *
	 * @param string $group_permalink
	 * @param object $group
	 * @return string $group_permalink Modified url.
	 */
	public function hcommons_set_group_permalink( $group_permalink, $group ) {

		$group_id = $group->id;
		$group_society_id = bp_groups_get_group_type( $group_id );

		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT site_id FROM $wpdb->sitemeta WHERE meta_key = '%s' AND meta_value = '%s'", 'society_id', $group_society_id ) );
		if ( is_object( $row ) ) {
			$society_network = get_network( $row->site_id );
			$scheme = ( is_ssl() ) ? 'https://' : 'http://';
			$group_permalink = trailingslashit( $scheme . $society_network->domain . $society_network->path . bp_get_groups_root_slug() . '/' . $group->slug );
		}
		return $group_permalink;
	}

	/**
	 * Set a given member permalink to contain the proper network.
	 *
	 * @since HCommons
	 *
	 * @param string $member_permalink
	 * @param int $user_id
	 * @return string $member_permalink Modified url.
	 */
	public function hcommons_set_members_directory_permalink( $member_permalink, $user_id, $user_nicename, $user_login ) {

		if ( ! bp_is_members_directory() ) {
			return $member_permalink;
		}

		//hcommons_write_error_log( 'info', '****SET_MEMBERS_DIRECTORY_PERMALINK****-'.var_export( $member_permalink, true ) );
		$member_types = bp_get_member_type( $user_id, false );

		if ( in_array( self::$society_id, $member_types ) ) {
			return $member_permalink;
		}
		$after_domain = bp_core_enable_root_profiles() ? $user_login : bp_get_members_root_slug() . '/' . $user_login;

		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT site_id FROM $wpdb->sitemeta WHERE meta_key = '%s' AND meta_value = '%s'", 'society_id', 'hc' ) );
		if ( is_object( $row ) ) {
			$society_network = get_network( $row->site_id );
			$scheme = ( is_ssl() ) ? 'https://' : 'http://';
			$member_permalink = trailingslashit( $scheme . $society_network->domain . $society_network->path . $after_domain );
		}
		return $member_permalink;
	}

	/**
	 * Filter out the user blogs that are not in the current network.
	 *
	 * @since HCommons
	 *
	 * @param array $blogs
	 * @param string $user_id
	 * @param bool $all
	 * @return array $network_blogs
	 */
	public function hcommons_filter_get_blogs_of_user( $blogs, $user_id, $all ) {
		// Remove root blogs (of any network).
		foreach ( $blogs as $i => $blog ) {
			foreach ( get_networks() as $network ) {
				if ( $blog->domain === $network->domain ) {
					unset( $blogs[ $i ] );
				}
			}
		}

		if ( 'hc' !== self::$society_id && ! bp_is_current_action('my-sites') ) {

			$network_blogs = $blogs;
			$current_network = get_current_site();
			$current_blog_id = get_current_blog_id();

			foreach ($blogs as $blog) {

				if ( $current_network->id != $blog->site_id || $current_blog_id == $blog->userblog_id ) {
					unset ( $network_blogs[$blog->userblog_id] );
				}

			}

			//hcommons_write_error_log( 'info', '****GET_BLOGS_OF_USER****-'.var_export( $user_id, true ) );
			$blogs = $network_blogs;
		}

		return $blogs;
	}

	/**
	 * Filter the BP Core avatar upload path to be global and not network specific.
	 *
	 * @since HCommons
	 *
	 * @param string $path
	 * @return string $path Modified path.
	 */
	public function hcommons_set_bp_core_avatar_upload_path( $path  ) {

		if ( ! empty( $path ) ) {
			$site_loc = strpos( $path, '/site' );
			if ( false === $site_loc ) {
				return $path;
			} else {
				$global_path = substr( $path, 0, $site_loc );
				//hcommons_write_error_log( 'info', '****BP_CORE_AVATAR_UPLOAD_PATH****-'.var_export( $global_path, true ) );
				return $global_path;
			}
		} else {
			return $path;
		}

	}

	/**
	 * Filter the BP Core avatar url to be global and not network specific.
	 *
	 * @since HCommons
	 *
	 * @param string $url
	 * @return string $url Modified url.
	 */
	public function hcommons_set_bp_core_avatar_url( $url  ) {

		if ( ! empty( $url ) ) {
			$site_loc = strpos( $url, '/site' );
			if ( false === $site_loc ) {
				return $url;
			} else {
				$global_url = substr( $url, 0, $site_loc );
				//hcommons_write_error_log( 'info', '****BP_CORE_AVATAR_URL****-'.var_export( $global_url, true ) );
				return $global_url;
			}
		} else {
			return $url;
		}

	}

	/**
	 * Filter the Invite Anyone user query by member type for this network.
	 *
	 * @since HCommons
	 *
	 * @param Invite_Anyone_User_Query $query Current instance of Invite_Anyone_User_Query. Passed by reference.
	 */
	public function hcommons_filter_site_users_only( $query ) {

		global $wpdb;
		$context = debug_backtrace(); //TODO get a proper filter in Invite Anyone and get rid of backtrace.

		if ( 'Invite_Anyone_User_Query' === get_class( $context[1]['args'][1][0] ) ) {
			//hcommons_write_error_log( 'info', '****FILTER_SITE_USERS_ONLY_QUERY****-'.var_export( $query, true ) );
			//hcommons_write_error_log( 'info', '****FILTER_SITE_USERS_ONLY_TRACE****-'.var_export( get_class( $context[1]['args'][1][0] ), true ) );
			$tax_query = new WP_Tax_Query( array(
				array(
					'taxonomy' => 'bp_member_type',
					'field'    => 'name',
					'operator' => 'IN',
					'terms'    => self::$society_id,
				),
			) );

			// Switch to the root blog, where member type taxonomies live.
			$site_id  = bp_get_taxonomy_term_site_id( 'bp_member_type' );
			$switched = false;
			if ( $site_id !== get_current_blog_id() ) {
				switch_to_blog( $site_id );
				$switched = true;
			}

			$sql_clauses = $tax_query->get_sql( 'u', $this->uid_name );

			$clause = '';

			if ( false !== strpos( $sql_clauses['where'], '0 = 1' ) ) {
				$clause = array( 'join' => '', 'where' => '0 = 1' );
				// IN clauses must be converted to a subquery.
			} elseif ( preg_match( '/' . $wpdb->term_relationships . '\.term_taxonomy_id IN \([0-9, ]+\)/', $sql_clauses['where'], $matches ) ) {
				$clause = "wp_users.ID IN ( SELECT object_id FROM $wpdb->term_relationships WHERE {$matches[0]} )";
			}

			if ( $switched ) {
				restore_current_blog();
			}
			//hcommons_write_error_log( 'info', '****FILTER_SITE_USERS_ONLY_CLAUSE****-'.var_export( $clause, true ) );
			$query->query_where .= ' AND ' . $clause;
		}

	}

	/**
	 * Filter the group join button by network.
	 *
	 * @since HCommons
	 *
	 * @param array       $button Button settings.
	 * @param object      $group
	 * @return array|null Button attributes.
	 */
	public function hcommons_check_bp_get_group_join_button( $button, $group ) {

		if ( 'hc' !== self::$society_id ) {
			return $button;
		}
		$group_society_id = bp_groups_get_group_type( $group->id );
		//hcommons_write_error_log( 'info', '****BP_GET_GROUP_JOIN_BUTTON****-'.var_export( $group_society_id, true ).'-'.var_export( $group, true ) );
		if ( 'hc' !== $group_society_id ) {
			return null;
		} else {
			return $button;
		}

	}

	/**
	 * Action to modify nav and sub nav items
	 *
	 * @since HCommons
	 *
	 */
	public function hcommons_remove_nav_items() {

		global $bp;
		//bp_core_remove_subnav_item( 'settings', 'general' );
		bp_core_remove_subnav_item( 'settings', 'profile' );
		// Example of how you change the default tab.
		//bp_core_new_nav_default( array( 'parent_slug' => 'settings', 'screen_function' =>'bp_settings_screen_notification', 'subnav_slug' => 'notifications' ) );

	}

	/**
	 * Filter password protect page url
	 *
	 * @since HCommons
	 *
	 */
	public function hcommons_password_protect_url( $url ) {

		return 'https://news.hcommons.org';

	}

	/**
	 * Filter password protect page title
	 *
	 * @since HCommons
	 *
	 */
	public function hcommons_password_protect_title( $title ) {

		return $title;

	}

	/**
	 * Filter password protect page message
	 *
	 * @since HCommons
	 *
	 */
	public function hcommons_password_protect_message( $title ) {

		if ( 'msu' === self::$society_id ) {
			echo '<style type="text/css">body.login { background-color: #ffffff !important; } ' .
				' body.login h1 a { color: #000000 !important; ' .
				'   font-family: lexia,serif; font-weight: 300; text-transform: unset !important; line-height: 1.2;} ' .
				' #entry-content p { line-height: 1.5; margin-top: 12px !important; } ' .
				' #login form p.submit input { background-color: #0085ba !important; } ' .
				' .login label { display: inherit !important; } ' . 
				' .login form { margin-top: 0px; !important; }</style>';
			echo '<div class="entry-content entry-summary"><p>Welcome to the future home of MSU Commons. Please forgive our appearance while we get ready for our big debut.</p></div>';
		}
                if ( 'arlisna' === self::$society_id ) {
                        echo '<style type="text/css">body.login { background-color: #ffffff !important; } ' .
                                ' body.login h1 a { color: #000000 !important; ' .
                                '   font-family: lexia,serif; font-weight: 300; text-transform: unset !important; line-height: 1.2;} ' .
                                ' #entry-content p { line-height: 1.5; margin-top: 12px !important; } ' .
                                ' #login form p.submit input { background-color: #0085ba !important; } ' .
				' .login label { display: inherit !important; } ' . 
                                ' .login form { margin-top: 0px; !important; }</style>';
                        echo '<div class="entry-content entry-summary"><p>Welcome to the future home of ARLIS/NA Commons. Please forgive our appearance while we get ready for our big debut.</p></div>';
                }
                if ( 'sah' === self::$society_id ) {
                        echo '<style type="text/css">body.login { background-color: #ffffff !important; } ' .
                                ' body.login h1 a { color: #000000 !important; ' .
                                '   font-family: lexia,serif; font-weight: 300; text-transform: unset !important; line-height: 1.2;} ' .
                                ' #entry-content p { line-height: 1.5; margin-top: 12px !important; } ' .
                                ' #login form p.submit input { background-color: #0085ba !important; } ' .
				' .login label { display: inherit !important; } ' . 
                                ' .login form { margin-top: 0px; !important; }</style>';
                        echo '<div class="entry-content entry-summary"><p>Welcome to the future home of SAH Commons. Please forgive our appearance while we get ready for our big debut.</p></div>';
                }

	}

	/**
	 * Filter activity times.
	 *
	 * @param  string $time_markup          preformatted time string
	 * @param  object $activity             the activity
	 * @return string $society_time_markup society prepended to the time string
	 */
	public function hcommons_filter_activity_time_since( $time_markup, $activity ) {

		$society_id = bp_activity_get_meta( $activity->id, 'society_id', true );
		if ( 'hc' === $society_id ) {
			$commons_name = 'Humanities Commons';
		} else {
			$commons_name = strtoupper( $society_id ) . ' Commons';
		}
		if ( false !== strpos( $time_markup, ' on ' . $commons_name ) ) { // Deja vu
			return $time_markup;
		}
		$society_time_markup = sprintf( '<span class="time-since"> on %1$s </span>%2$s', $commons_name, $time_markup );
		return $society_time_markup;
	}

	/**
	 * Filter the BP cover image upload dir to be global and not network specific.
	 * Really hacked to handle new group cover images. TODO get this fixed in BP.
	 *
	 * @since HCommons
	 *
	 * @param array $upload_dir
	 * @return array $upload_dir Modified dir.
	 */
	public function hcommons_cover_image_upload_dir( $upload_dir  ) {

		//hcommons_write_error_log( 'info', '****BP_CORE_COVER_IMAGE_UPLOAD_DIR_BEFORE****-' . var_export( $upload_dir, true ) );

		$bp_params = $_POST['bp_params'];

		$path = preg_replace( '~/sites/\d+/~', '/', $upload_dir['path'] );
		if ( 'group' === $bp_params['object'] && ! empty( $bp_params['item_id'] ) && false !== strpos( $path, '/groups/0/cover-image' ) ) {
			$path = str_replace( '/groups/0/cover-image', '/groups/' . $bp_params['item_id'] . '/cover-image', $path );
		}
		if ( ! empty( $path ) ) {
			$upload_dir['path'] = $path;
		}
		$url = preg_replace( '~/sites/\d+/~', '/', $upload_dir['url'] );
		if ( 'group' === $bp_params['object'] && ! empty( $bp_params['item_id'] ) && false !== strpos( $url, '/groups/0/cover-image' ) ) {
			$url = str_replace( '/groups/0/cover-image', '/groups/' . $bp_params['item_id'] . '/cover-image', $url );
		}
		if ( ! empty( $url ) ) {
			$upload_dir['url'] = $url;
		}
		$subdir = $upload_dir['subdir'];
		if ( 'group' === $bp_params['object'] && ! empty( $bp_params['item_id'] ) && false !== strpos( $subdir, '/groups/0/cover-image' ) ) {
			$subdir = str_replace( '/groups/0/cover-image', '/groups/' . $bp_params['item_id'] . '/cover-image', $subdir );
		}
		if ( ! empty( $subdir ) ) {
			$upload_dir['subdir'] = $subdir;
		}
		$basedir = preg_replace( '~/sites/\d+/~', '/', $upload_dir['basedir'] );
		if ( ! empty( $basedir ) ) {
			$upload_dir['basedir'] = $basedir;
		}
		$baseurl = preg_replace( '~/sites/\d+/~', '/', $upload_dir['baseurl'] );
		if ( ! empty( $baseurl ) ) {
			$upload_dir['baseurl'] = $baseurl;
		}
		//hcommons_write_error_log( 'info', '****BP_CORE_COVER_IMAGE_UPLOAD_DIR_AFTER****-' . '-' . var_export( $upload_dir, true ) );

		return $upload_dir;
	}

	/**
	 * Filter the BP attachments upload dir to be global and not network specific.
	 *
	 * @since HCommons
	 *
	 * @param string|array $retval
	 * @param string $data
	 * @return string|array $retval
	 */
	public function hcommons_attachments_uploads_dir_get( $retval, $data  ) {

		//hcommons_write_error_log( 'info', '****BP_CORE_ATTACHMENTS_UPLOADS_DIR_GET_BEFORE****-'.var_export( $retval, true ).'-'.var_export( $data, true ) );

		if ( empty( $data ) ) {
			$basedir = preg_replace( '~/sites/\d+/~', '/', $retval['basedir'] );
			if ( ! empty( $basedir ) ) {
				$retval['basedir'] = $basedir;
			}
			$baseurl = preg_replace( '~/sites/\d+/~', '/', $retval['baseurl'] );
			if ( ! empty( $baseurl ) ) {
				$retval['baseurl'] = $baseurl;
			}
		}
		//hcommons_write_error_log( 'info', '****BP_CORE_ATTACHMENTS_UPLOADS_DIR_GET_AFTER****-'.var_export( $retval, true ).'-'.var_export( $data, true ) );

		return $retval;
	}

	/**
	 * Filter the BP attachments upload dir to be global and not network specific.
	 *
	 * @since HCommons
	 *
	 * @param string|array $data
	 * @param string $dir
	 * @return string|array $data
	 */
	public function hcommons_attachment_upload_dir( $data, $dir  ) {

		//hcommons_write_error_log( 'info', '****BP_CORE_ATTACHMENTS_UPLOAD_DIR_BEFORE****-'.var_export( $data, true ).'-'.var_export( $dir, true ) );

		$basedir = preg_replace( '~/sites/\d+/~', '/', $data['basedir'] );
		if ( ! empty( $basedir ) ) {
			$data['basedir'] = $basedir;
		}
		$baseurl = preg_replace( '~/sites/\d+/~', '/', $data['baseurl'] );
		if ( ! empty( $baseurl ) ) {
			$data['baseurl'] = $baseurl;
		}
		//hcommons_write_error_log( 'info', '****BP_CORE_ATTACHMENTS_UPLOAD_DIR_AFTER****-'.var_export( $data, true ).'-'.var_export( $dir, true ) );

		return $data;
	}

	/**
	 * Filter that enables forums by default on new group creation screen
	 *
	 * @param  int 	$forum  false by default
	 * @return int  $forum  true to enable forum by default
	 */
	public function hcommons_get_new_group_enable_forum( $forum ) {

		//grabs current step during group creation only
		$current_step = bp_get_groups_current_create_step();

		//we only want the discussion forum to be checked by default on group creation
		if( $current_step == 'forum' ) {

			$forum = 1;

		}

		return $forum;
	}

	/**
	 * Handles logic from ajax call in child-theme
	 *
	 * @return void
	 */
	public function hcommons_settings_general_ajax() {

		//lets check if the server is sending a POST request with the nonce as data
		if ( $_SERVER['REQUEST_METHOD'] == 'POST' && wp_verify_nonce( $_POST['nonce'], 'settings_general_nonce' ) ) {

			//lets get the current user data
			$user = wp_get_current_user();

			if ( isset( $_POST['primary_email'] ) && ! empty( $_POST['primary_email'] ) ) {

				$user->user_email = $_POST['primary_email'];
				$updated_user = wp_update_user( ['ID' => $user->ID, 'user_email' => esc_attr( $_POST['primary_email'] ) ] );

				//if there is a wp_error on wp_update_user(),
				//there was a problem saving the record, if there isnt then output json data for ajax
				if( ! is_wp_error( $updated_user ) )
					echo json_encode(['updated' => true, 'primary_email' => $user->user_email]);

			}

		}

		die();

	}

	/* Filter the activity query by the society id for the current network admin.
	 *
	 * @since HCommons
	 *
	 * @param array $args
	 * @return array $args
	 */
	public function hcommons_set_network_admin_activities_query( $args ) {

		if ( ! is_admin() ) {
			return $args;
		}

		$args['meta_query'] = array(
			array(
				'key'     => 'society_id',
				'value'   => self::$society_id,
				'type'    => 'string',
				'compare' => '='
			),
	);

	return $args;

	}

	/**
	 * Removes bp_settings_general action for front-end so custom built primary email switching can work
	 *
	 * @return void
	 */
	public function hcommons_remove_bp_settings_general() {
		remove_action( 'bp_actions',  'bp_settings_action_general', 10 );
	}

	/**
	 * Sets default group subscription settings in group creation step to 'digest' instead of 'all emails'
	 *
	 * @return void
	 */
	public function hcommons_groups_group_before_save() {

		global $bp;

		groups_update_groupmeta( $bp->groups->new_group_id, 'ass_default_subscription', 'dig' );

	}

	/**
	 * Waiting period for BP Events
	 */
	public function hcommons_vet_user_for_bpeo( $capabilities, $primitive_caps, $args, $user ) {
		if ( $args[0] !== 'connect_event_to_group' && $args[0] !== 'publish_events') {
			return $capabilities;
		}
		$vetted_user = $this->hcommons_vet_user();
		if ( ! $vetted_user ) {
			// 'read' is the required primitive capability for 'connect_event_to_group'
			unset( $capabilities['read'] );
		}
		return $capabilities;
	}

	/**
	 * A user should require the 'read' primitive capability in order to create events.
	 */
	public function hcommons_bpeo_event_creation_capability( $caps, $cap, $user_id, $args ) {
		if ( $cap !== 'publish_events' ) {
			return $caps;
		}

		$caps[] = 'read';
		return $caps;
	}

	/**
	 * Waiting period for BP DOCS
	 *
	 * @return array
	 */
	public function hcommons_check_docs_new_member_caps( $caps, $cap, $user_id, $args ) {

		$vetted_user = $this->hcommons_vet_user();

		if ( ! $vetted_user ) {
			return array( 'do_not_allow' );
		} else {
			return $caps;
		}
	}

	/**
	 * Waiting period for site creation
	 *
	 * @return string
	 */
	public function hcommons_check_sites_new_member_status( $active_signup ) {

		$vetted_user = $this->hcommons_vet_user();

		if ( ! $vetted_user ) {
			return 'none';
		} else {
			return $active_signup;
		}
	}

	/**
	 * Get page content from a page on given society network
	 *
	 * @return string
	 */
	public static function hcommons_get_society_page_by_slug( $atts ) {

		$atts = shortcode_atts( array( 'society_id' => 'hc', 'slug' => '' ), $atts, 'hcommons_society_page' );
		if ( empty( $atts['slug'] ) ) {
			return;
		}

		$switched = false;
		if ( defined( strtoupper( $atts['society_id'] ) . '_ROOT_BLOG_ID' ) ) {
			$society_blog_id = (int) constant( strtoupper( $atts['society_id'] ) . '_ROOT_BLOG_ID' );
			if ( $society_blog_id !== get_current_blog_id() ) {
				switch_to_blog( $society_blog_id );
				$switched = true;
			}
		} else {
			return;
		}

		$society_page = get_page_by_path( $atts['slug'] );
		if ( empty( $society_page ) ) {
			if ( $switched ) {
				restore_current_blog();
			}
			return;
		}
		$page_content = apply_filters( 'the_content', $society_page->post_content );

		if ( $switched ) {
			restore_current_blog();
		}
		return $page_content;

	}

	/**
	 * Shortcode to get variable from the server environment
	 *
	 * @return string
	 */
	public static function hcommons_get_env_variable( $atts ) {

		$atts = shortcode_atts( array( 'var' => '' ), $atts, 'hcommons_env_variable' );
		if ( empty( $atts['var'] ) ) {
			return;
		}
		//TODO whitelist the allowed values

		$env_variable = $_SERVER[$atts['var']];

		return $env_variable;

	}

	/**
	 * Functions not tied to any filter or action.
	 */

	/**
	 * Try to catch the spammers
	 *
	 * @return boolean
	 */
	public static function hcommons_vet_user() {
		return true; // disable spammer check for now
		$current_user = wp_get_current_user();
		$member_types = (array)bp_get_member_type( $current_user->ID, false );
		if ( empty( $member_types ) || ( 1 == count( $member_types ) && in_array( 'hc', $member_types ) ) ) {
			$society_member = false;
		} else {
			return true;
		}

		$timeDiff = time() - strtotime( $current_user->user_registered );

		if ( $timeDiff < ( 60 * 60 * 48 ) ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Unserializes the shib_email meta to return to the user as an array
	 *
	 * @param   object $user  		user object to be passed
	 * @return  array  $shib_email  array to be used
	 */
	public static function hcommons_shib_email( $user ) {

		$shib_email = maybe_unserialize( get_user_meta( $user->ID, 'shib_email', true ) );

		if( ! is_string( $shib_email ) ) {

			//loops through the array and filters out anything that is null
			$email = array_filter( $shib_email );
			return array_unique( $email );
		} else {
			return $shib_email;
		}

	}

	/**
	 * Return user memberships from session
	 *
	 * @since HCommons
	 *
	 * @return array $memberships
	 */
	public static function hcommons_get_user_memberships() {

		$memberships = [
			'societies' => self::hcommons_get_user_org_memberships(),
			'groups'    => self::hcommons_get_user_group_memberships(),
		];

		return $memberships;
	}

	/**
	 * Return user organization / society memberships from session.
	 *
	 * @return Array List of organization slugs that user is member of. 
	 *               Eg. [ 'hc', 'mla', 'msu' ]
	 */
	public static function hcommons_get_user_org_memberships() {
		if ( ! isset( $_SERVER['HTTP_ISMEMBEROF'] ) ) {
			return [];
		}

		$server_membership_strings = explode( ';', $_SERVER['HTTP_ISMEMBEROF'] );

		$server_memberships = [];
		$pattern = '/CO:COU:(.*?):members:(.*)/';
		foreach ( $server_membership_strings as $membership_string ) {
			if ( preg_match( $pattern, $membership_string, $matches ) ) {
				$server_memberships[strtolower($matches[1])] = $matches[2];
			}
		}

		$member_types = array_keys( bp_get_member_types() );
		$active_memberships = array_keys(
			array_filter( $server_memberships, function( $value ) {
				return $value === 'active';
			} )
		);

		// Fallback for legacy organizational memberships. If this is finding
		// memberships that are not being found above, something is going wrong.
		// This code should be removed once Grouper is retired.
		$pattern = '/Humanities Commons:(.*?):members_(.*?)/';
		foreach ( $server_membership_strings as $membership_string ) {
			if ( preg_match( $pattern, $membership_string, $matches ) ) {
				$member_org = strtolower( $matches[1] );
				if ( ! in_array( $member_org, $active_memberships ) ) {
					$active_memberships[] = $member_org;
					hcommons_write_error_log( 'info', "hcommons_get_user_org_memberships - adding fallback society membership - $member_org" );
				}
			}
		}

		$org_memberships = array_intersect( $member_types, $active_memberships );

		return $org_memberships;
	}

	/**
	 * Return user group memberships from session.
	 *
	 * @return Array Associative array where keys are organization slugs and
	 *               values are lists of group names the user is a member of.
	 */
	public static function hcommons_get_user_group_memberships() {
		if ( ! isset( $_SERVER['HTTP_ISMEMBEROF'] ) ) {
			return [];
		}

		$server_membership_strings = explode( ';', $_SERVER['HTTP_ISMEMBEROF'] );
		hcommons_write_error_log( 'info', 'HTTP_ISMEMBEROF' . var_export($_SERVER['HTTP_ISMEMBEROF'], true));
		$group_memberships = [];

		foreach ( $server_membership_strings as $membership_string ) {
			$pattern = '/Humanities Commons:([A-Z]*_)?([^:^;]*)/';
			if ( preg_match( $pattern, $membership_string, $matches ) ) {
				$society_prefix = $matches[1];
				if ( $society_prefix ) {
					$society_key = strtolower( trim( $society_prefix, '_' ) );
					if ( ! array_key_exists( $society_key, $group_memberships ) ) {
						$group_memberships[$society_key] = [];
					}
					$group_memberships[$society_key][] = $matches[2];
				} 
			}
		}
		return $group_memberships;
	}

	/**
	 * Return user login methods from user meta
	 *
	 * @since HCommons
	 *
	 * @param string $data
	 * @return bool|string|array $login_methods
	 */
	public static function hcommons_get_user_login_methods( $user_id ) {

		$user_login_methods = array_filter( (array) maybe_unserialize( get_usermeta( $user_id, 'saml_login_methods', true ) ) );
		//hcommons_write_error_log( 'info', '**********************GET_USER_LOGIN_METHODS********************-' . $user_id . '-' . var_export( $user_login_methods, true ) );
		if ( ! empty( $user_login_methods ) ) {
			$login_methods = array();
			foreach( $user_login_methods as $user_login_method ) {
				if ( ! empty( $user_login_method ) ) {
					$login_methods[] = $user_login_method;
				}
			}
			return $login_methods;
		} else {
			$methods = array ();
			if ( defined( 'GOOGLE_LOGIN_METHOD_SCOPE' ) ) {
				$methods[GOOGLE_LOGIN_METHOD_SCOPE] = 'Google login';
			}
			if ( defined( 'TWITTER_LOGIN_METHOD_SCOPE' ) ) {
				$methods[TWITTER_LOGIN_METHOD_SCOPE] = 'Twitter login';
			}
			if ( defined( 'HC_LOGIN_METHOD_SCOPE' ) ) {
				$methods[HC_LOGIN_METHOD_SCOPE] = 'HC login';
			}
			if ( defined( 'MLA_LOGIN_METHOD_SCOPE' ) ) {
				$methods[MLA_LOGIN_METHOD_SCOPE] = 'Legacy MLA login';
			}
			$user_login_methods = array_filter( (array) maybe_unserialize( get_usermeta( $user_id, 'shib_uid', true ) ) );
			$login_methods = array();
			foreach( $user_login_methods as $user_login_method ) {
				$user_method = explode( '@', $user_login_method );
				if ( ! empty( $user_method[1] ) ) {
					$login_methods[] = $methods[$user_method[1]];
				} elseif ( ! empty( $user_login_method ) ) {
					$login_methods[] = 'Unknown login';
				}
			}
			return $login_methods;
		}

	}

	/**
	 * Return identity provider from session
	 *
	 * @since HCommons
	 *
	 * @return string|bool $identity_provider
	 */
	public static function hcommons_get_identity_provider( $formatted = true ) {

		if ( ! empty( $_SERVER['HTTP_IDPENTITYID'] ) ) {
			if ( ! $formatted ) {
				return $_SERVER['HTTP_IDPENTITYID'];
			} else {
				return $_SERVER['HTTP_IDPDISPLAYNAME'];
			}
		}

		if ( ! empty( $_SERVER['HTTP_SHIB_IDENTITY_PROVIDER'] ) ) {
			//hcommons_write_error_log( 'info', '**********************GET_IDENTITY_PROVIDER********************-' . var_export( $identity_provider, true ) );
			if ( ! $formatted ) {
				return $_SERVER['HTTP_SHIB_IDENTITY_PROVIDER'];
			}
			$providers = array ();
			if ( defined( 'GOOGLE_IDENTITY_PROVIDER' ) ) {
				$providers[GOOGLE_IDENTITY_PROVIDER] = 'Google login';
			}
			if ( defined( 'TWITTER_IDENTITY_PROVIDER' ) ) {
				$providers[TWITTER_IDENTITY_PROVIDER] = 'Twitter login';
			}
			if ( defined( 'HC_IDENTITY_PROVIDER' ) ) {
				$providers[HC_IDENTITY_PROVIDER] = 'HC login';
			}
			if ( defined( 'MLA_IDENTITY_PROVIDER' ) ) {
				$providers[MLA_IDENTITY_PROVIDER] = 'Legacy MLA login';
			}
			$identity_provider = '';
			$identity_provider = $_SERVER['HTTP_SHIB_IDENTITY_PROVIDER'];

			if ( empty( $providers[$identity_provider] ) ) {
				return 'Unknown login';
			} else {
				return $providers[$identity_provider];
			}

		}
		return false;
	}

	public static function hcommons_user_in_current_society() {
		// If user has active society session, return true. 
		if ( ! self::hcommons_non_member_active_session() ) {
			return True;
		}

		//If not, check if they have the correct member type.
		$current_user = wp_get_current_user();
		$member_types = (array)bp_get_member_type( $current_user->ID, false );
		if ( in_array( self::$society_id, $member_types ) ) {
			return True;
		}
		return False;
	}

	/**
	 * Check for non-member active session
	 *
	 * @since HCommons
	 *
	 * @return bool $classes
	 */
	public static function hcommons_non_member_active_session() {

		$user_memberships = self::hcommons_get_user_memberships();
		if ( empty( $user_memberships['societies'] ) ) {
			return true;
		}
		if ( ! in_array( self::$society_id, $user_memberships['societies'] ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Return user login name from session
	 *
	 * @since HCommons
	 *
	 * @return string|bool $username
	 */
	public function hcommons_get_session_username() {

		if ( isset( $_SERVER['HTTP_EMPLOYEENUMBER'] ) ) {
			return $_SERVER['HTTP_EMPLOYEENUMBER'];
		}
		return false;
	}

	/**
	 * Return user ORCID from session
	 *
	 * @since HCommons
	 *
	 * @return string|bool $orcid
	 */
	public static function get_session_orcid() {

		if ( isset( $_SERVER['HTTP_EDUPERSONORCID'] ) ) {
			$shib_orcid = $_SERVER['HTTP_EDUPERSONORCID'];
			if ( ! empty( $shib_orcid ) ) {
				if ( false === strpos( $shib_orcid, ';' ) ) {
					$shib_orcid_updated = str_replace( array( 'https://orcid.org/', 'http://orcid.org/' ), '', $shib_orcid );
					return $shib_orcid_updated;
				} else {
					$shib_orcid_updated = array();
					$shib_orcids = explode( ';', $shib_orcid );
					foreach( $shib_orcids as $each_orcid ) {
						if ( ! empty( $each_orcid ) ) {
							$shib_orcid_updated[] = str_replace(
									array( 'https://orcid.org/', 'http://orcid.org/' ),
									'', $each_orcid );
						}
					}
					return $shib_orcid_updated[0];
				}
			}
			return;
		}
		return false;
	}

	/**
	 * Return EPPN from session
	 *
	 * @since HCommons
	 *
	 * @return string|bool $username
	 */
	public static function get_session_eppn() {

		if ( isset( $_SERVER['HTTP_EPPN'] ) ) {
			$eppn = $_SERVER['HTTP_EPPN'];
			return $eppn;
		}
		return false;
	}

	/**
	 * Return Meta Display Name from session
	 *
	 * @since HCommons
	 *
	 * @return string|bool $username
	 */
	public static function get_session_meta_displayname() {

		if ( isset( $_SERVER['HTTP_META_DISPLAYNAME'] ) ) {
			$meta_displayname = $_SERVER['HTTP_META_DISPLAYNAME'];
			return $meta_displayname;
		}
		return false;
	}

	/**
	 * Get managed groups.
	 *
	 * @return array Managed groups categorized by society
	 */
	public static function hcommons_get_managed_groups() {
		$managed_group_names = wp_cache_get( 'managed_group_names', 'hcommons_settings' );

		if ( false === $managed_group_names || empty( $managed_group_names ) ) {
			$managed_group_names = [];
			$autopopulate_groups = groups_get_groups( 
				[
					'show_hidden' => true, 
					'per_page'    => -1,
					'meta_query'  => [
						[
							'key'   => 'autopopulate',
							'value' => 'Y',
						],
					],
				] 
			);
			foreach( $autopopulate_groups['groups'] as $group ) {
				$group_society_id = bp_groups_get_group_type( $group->id, true );
				$managed_group_names[ $group_society_id ][ strip_tags( stripslashes( html_entity_decode( $group->name ) ) ) ] = $group->id;
			}
			wp_cache_set( 
				'managed_group_names',
				$managed_group_names,
				'hcommons_settings',
				24 * HOUR_IN_SECONDS
			);
		}

		return $managed_group_names;
	}

	/**
	 * Lookup society group id by name.
	 *
	 * @since HCommons
	 *
	 * @param string $society_id
	 * @param string $group_name
	 * @return string group id
	 */
	public static function hcommons_lookup_society_group_id( $society_id, $group_name ) {

		$managed_group_names = self::hcommons_get_managed_groups();

		if ( 
			array_key_exists( $society_id, $managed_group_names ) &&
			array_key_exists( $group_name, $managed_group_names[ $society_id ] )
		) {
			return $managed_group_names[ $society_id ][ $group_name ];
		}

		return [];
	}

	/**
	 * helper function to facilitate conditions where caller can be identified by function/class name
	 *
	 * @param string $key a key in the backtrace to check, e.g. 'function' or 'class'
	 * @param string $value the value of $key to look for, i.e. the function/class name
	 * @return bool does debug_backtrace() contain the specified key/value pair?
	 */
	public static function backtrace_contains( $key, $value ) {
		$retval = false;

		foreach ( debug_backtrace() as $bt ) {
			if ( isset( $bt[ $key ] ) && $value === $bt[ $key ] ) {
				$retval = true;
				break;
			}
		}

		return $retval;
	}

	/**
	 * Adds an option to hide the site from the Sites page.
	 */
	public function add_hide_site_option() {
		add_option( 'hide-site-from-listing' );
	}

	public function hcommons_bp_loggedin_user_id( $id ) {
		if ( $id && $id !== 0 ) {
			return $id;
		}

		$current_user = wp_get_current_user();
		return $current_user->ID;
	}
}

$humanities_commons = new Humanities_Commons;

function hcommons_check_non_member_active_session() {
	return Humanities_Commons::hcommons_non_member_active_session();
}
function hcommons_get_session_orcid() {
	return Humanities_Commons::get_session_orcid();
}
function hcommons_get_session_eppn() {
	return Humanities_Commons::get_session_eppn();
}
function hcommons_get_session_meta_displayname() {
	return Humanities_Commons::get_session_meta_displayname();
}
