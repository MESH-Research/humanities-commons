<?php

if ( !defined( 'BUDDYBLOCK_VERSION' ) ) exit;

function bp_block_member_init() {

	if ( is_user_logged_in() ) {

		if(  bp_is_user() )  {

			if( function_exists( 'bpmm_process_private' ) )
				global $block_member_instance;  // needed so that BPMessageUX only creates public message button when allowed
		}

		$block_member_instance = new BP_Block_Member();
	}

	if( is_admin() )
		bp_block_member_list();

}
add_action( 'bp_init', 'bp_block_member_init' );



class BP_Block_Member {

	private  $their_blocked_ids = array(); 	// ids that are blocking you

	private  $your_blocked_ids = array();	// ids that you are blocking

	private  $visiblity = '';

	private $this_id = 0;					// your id

	private $block_create_message = ''; 	// success / error message for actions in wp-admin
	private $block_assign_message = ''; 	// success / error message for actions in wp-admin
	private $block_visibility_message = ''; 	// success / error message for actions in wp-admin

		public function __construct() {

			$this->this_id = bp_loggedin_user_id();
			$this->their_blocked_ids = $this->_get_their_blocked_ids();
			$this->your_blocked_ids = $this->_get_your_blocked_ids();
			$this->visibility = get_option( 'bp_block_visibility' );

			$this->_block_visibility_update();
			$this->_block_roles_update();
			$this->_block_create();
			$this->_bp_block_handle_actions();


			add_action( 'wp_footer',	array( $this, 'block_button_js' ), 1 );


			add_action( 'bp_pre_user_query_construct',	array( $this, '_members_query' ), 1, 1 );
			add_action( 'bp_init', 						array( $this, '_member_profile'), 99 );
			add_action( 'bp_member_header_actions', 	array( $this, '_profile_page_block_button'), 50 );  //use 50 so block button is last

			// members loop
			add_action( 'bp_directory_members_actions', array( $this, '_members_loop' ), 1 );

			//wp-admin
			add_action( 'admin_head', array( $this, '_block_admin_styles' ) );
			add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', array( $this, '_block_admin_menu' ) );

			//groups
			if ( bp_is_active( 'groups' ) ) {
				add_filter( 'bp_group_members_user_join_filter', 	array( $this, '_group_members'), 20, 1 );
				add_action( 'bp_group_members_list_item_action',    array( $this, '_group_members_loop' ), 1 );
			}

			//activity
			if ( bp_is_active( 'activity' ) ) {
				add_filter( 'bp_get_send_public_message_button',    array( $this, '_remove_profile_public_message_button'), 1, 1 );
				add_filter( 'bp_activity_get_where_conditions',     array( $this, '_activity_where_query'), 1, 5);
				add_filter( 'bp_activity_can_comment_reply',        array( $this, 'current_comment_reply_check'), 1, 2);
				add_action('bp_before_activity_comment',            array( $this, 'before_current_activity_comment') );
			}

			//bbPress  // commented out for now
			/*
			if( class_exists('bbPress') ) {
				add_filter( 'bbp_get_single_forum_description', array( $this, 'bbp_blocked_forum_description'), 1, 2 );
				//add_filter( 'bbp_get_forum_topic_count', array( $this, 'bbp_blocked_topic_count'), 1, 2 );
				add_filter( 'pre_get_posts',                    array( $this, 'bbp_blocked_replies'), 9, 2 );
				add_filter( 'bbp_before_has_topics_parse_args', array( $this, 'bbp_blocked_topics' ), 10, 1 );
			}
			*/

			//messages
			if ( bp_is_active( 'messages' ) ) {
				add_filter( 'bp_get_send_message_button', array( $this, '_remove_profile_private_message_button'), 100, 1 );
				add_action( 'messages_message_before_save', array( $this, '_check_recipients' ) );
			}

			//friends
			if ( bp_is_active( 'friends' ) )
				add_filter( 'bp_get_add_friend_button', array( $this, '_remove_add_friend_button'), 1, 10 );
		}

		function block_button_js() {
			echo '<script type="text/javascript" >
			jQuery(document).ready(function($) {
				$("a.block-button").one("click", function() {
				    $(this).click(function () { return false; });
				});
			});
			</script>';
		}

	    public function get_your_blocked_ids() {
	         return $this->your_blocked_ids;
	    }

		/* bbPress functions */

		public function bbp_blocked_forum_description( $retstr, $r ) {
			global $wpdb;

			$forum_id = $r['forum_id'];

			if( ! empty( $this->their_blocked_ids ) ) {

				$blocked_ids = implode(",", $this->their_blocked_ids);
				$blocked_topics_num = $wpdb->get_var( "SELECT COUNT( ID ) FROM {$wpdb->prefix}posts WHERE post_author IN ({$blocked_ids}) AND post_parent = $forum_id" );

				if( $blocked_topics_num != NULL )
					$retstr = '';

			}

			return $retstr;
		}

		public function bbp_blocked_topic_count( $topics, $forum_id ) {
			global $wpdb;

			if( ! empty( $this->their_blocked_ids ) ) {

				$blocked_ids = implode(",", $this->their_blocked_ids);
				$blocked_topics_num = $wpdb->get_var( "SELECT COUNT( ID ) FROM {$wpdb->prefix}posts WHERE post_author IN ({$blocked_ids}) AND post_parent = $forum_id AND post_type = 'topic'" );

				$topics -= $blocked_topics_num;

			}

			return $topics;

		}

		public function bbp_blocked_topics( $args ) {
			global $wpdb;

			if( ! empty( $this->their_blocked_ids ) ) {

				$blocked_ids = implode(",", $this->their_blocked_ids);
				$blocked_posts = $wpdb->get_col( "SELECT ID FROM {$wpdb->prefix}posts WHERE post_author IN ({$blocked_ids}) AND post_type = 'topic'" );

				$args['post__not_in']  = $blocked_posts;

			}

			return $args;

		}



		public function bbp_blocked_replies( $query = false ) {
			global $wpdb;

			// Bail if not a bbPress topic and reply query

			$bb_types = array( bbp_get_topic_post_type(), bbp_get_reply_post_type() );
			if ( in_array( $query->get( 'post_type'), $bb_types ) ) {
				return $query;
			}


			if( ! empty( $this->their_blocked_ids ) ) {

				$blocked_ids = implode(",", $this->their_blocked_ids);
				$blocked_posts = $wpdb->get_col( "SELECT ID FROM {$wpdb->prefix}posts WHERE post_author IN ({$blocked_ids}) AND post_type = 'reply'" );

				//$query->set( 'post__not_in', array(206) );
				$query->set( 'post__not_in', $blocked_posts );
			}

			return $query;
		}

		/* end bbPress functions */




		public function before_current_activity_comment() {
			global $activities_template;

			if( in_array( $activities_template->activity->current_comment->user_id, $this->their_blocked_ids ) ) {
				$activities_template->activity->current_comment->user_id = '0';
				$activities_template->activity->current_comment->content = 'comment removed';
				$activities_template->activity->current_comment->primary_link = '';
				$activities_template->activity->current_comment->display_name = 'anon';
				$activities_template->activity->current_comment->user_fullname = 'anon';
				//$activities_template->activity->current_comment->date_recorded = '';
			}

			if( ! $this->visibility )
				return;
			else {
				if( in_array( $activities_template->activity->current_comment->user_id, $this->your_blocked_ids ) ) {

					$activities_template->activity->current_comment->user_id = '0';
					$activities_template->activity->current_comment->content = 'blocked member comment';
					$activities_template->activity->current_comment->primary_link = '';
					$activities_template->activity->current_comment->display_name = 'blocked member';
					$activities_template->activity->current_comment->user_fullname = 'blocked member';
					//$activities_template->activity->current_comment->date_recorded = '';
				}
			}
		}


		public function current_comment_reply_check( $can_comment, $comment ) {

			if( $comment->user_id == 0 )
				$can_comment = false;

			return $can_comment;
		}



		// get the ids of everyone blocking you
		private function _get_their_blocked_ids() {
			global $wpdb, $bp;

			$target_id = $this->this_id;

			$blocked_ids = $wpdb->get_col( "SELECT user_id FROM {$bp->table_prefix}bp_block_member WHERE target_id = '$target_id' ");

			return $blocked_ids;
		}

		// get the ids of everyone you are blocking
		private function _get_your_blocked_ids() {
			global $wpdb, $bp;

			$user_id = $this->this_id;

			$blocked_ids = $wpdb->get_col( "SELECT target_id FROM {$bp->table_prefix}bp_block_member WHERE user_id = '$user_id' ");

			return $blocked_ids;
		}



		// adjust the members query
		function _members_query( $query_array ) {

			if( ! $this->visibility ) {

				if( !empty( $this->their_blocked_ids ) )
					$query_array->query_vars['exclude'] = $this->their_blocked_ids;

			}
			else {

				$exclude_ids = array_merge( $this->their_blocked_ids, $this->your_blocked_ids );

				if( !empty( $exclude_ids ) )
					$query_array->query_vars['exclude'] = $exclude_ids;
			}
		}


		// filter activity
		function _activity_where_query( $where_conditions, $r, $select_sql, $from_sql, $join_sq) {

			if( ! $this->visibility ) {

				if( !empty( $this->their_blocked_ids ) ) {

					$blocked_ids = implode(",", $this->their_blocked_ids);

					$where_conditions["blocked_sql"] = "a.user_id NOT IN ({$blocked_ids}) AND a.secondary_item_id NOT IN ({$blocked_ids}) ";

				}
			}
			else {

				$blocked_ids = array_merge( $this->their_blocked_ids, $this->your_blocked_ids );

				if( !empty( $blocked_ids ) ) {

					$blocked_ids = implode(",", $blocked_ids);

					$where_conditions["blocked_sql"] = "a.user_id NOT IN ({$blocked_ids}) AND a.secondary_item_id NOT IN ({$blocked_ids}) ";

				}

			}

			return $where_conditions;

		}


		// check if a member is trying to access a blocked profile
		function _member_profile() {

			if( ! bp_is_user() )
				return;

			if( in_array( bp_displayed_user_id(), $this->their_blocked_ids ) ) {
				// just send them to home page for now
				bp_core_redirect( get_option('siteurl')."/" );
			}

		}

		// insert a custom message if you are blocked or blocking re recipient
		function _override_bp_l10n( $kind ) {
			global $l10n;

			$mo = new MO();

			if( $kind == 'their' ) {
				$mo->add_entry( array( 'singular' => 'There was an error sending that message, please try again', 'translations' => array( __ ('You have been blocked by one of the persons you are attempting to send a message to.  Your message has not been sent.', 'bp-block-member' ) ) ) );
				$mo->add_entry( array( 'singular' => 'There was a problem sending that reply. Please try again.', 'translations' => array( __ ('You have been blocked by one of the persons you are attempting to send a reply to.  Your reply has not been sent.', 'bp-block-member' ) ) ) );
			}
			else {
				$mo->add_entry( array( 'singular' => 'There was an error sending that message, please try again', 'translations' => array( __ ('You are blocking   one of the persons you are attempting to send a message to.  Your message has not been sent.', 'bp-block-member' ) ) ) );
				$mo->add_entry( array( 'singular' => 'There was a problem sending that reply. Please try again.', 'translations' => array( __ ('You are blocking  one of the persons you are attempting to send a reply to.  Your reply has not been sent.', 'bp-block-member' ) ) ) );
			}

			if ( isset( $l10n['buddypress'] ) )
				$mo->merge_with( $l10n['buddypress'] );

			$l10n['buddypress'] = &$mo;
			unset( $mo );
		}


		// remove members who have blocked you or you have blocked from receiving messages or replies
		function _check_recipients( $message_info ) {

			$recipients = $message_info->recipients;

			$u = 0; // # of recipients in the message that are blocked

			$kind = '';

			foreach ( $recipients as $key => $recipient ) {

				if (($key = array_search( $recipient->user_id, $this->their_blocked_ids )) !== FALSE) {
					$u++;
					$kind = 'their';
				}
				// to prevent harassment
				if (($key = array_search( $recipient->user_id, $this->your_blocked_ids )) !== FALSE) {
					$u++;
					$kind = 'your';
				}
			}

			// if any recipients being blocked, remove everyone from the recipient's list
			// this is done to prevent the message from being sent to anyone and is another spam prevention measure

			if (  $u > 0 && $kind != '' ) {
				$this->_override_bp_l10n( $kind );
				unset( $message_info->recipients );
			}

			return $message_info;
		}


		//	Remove members that have blocked you from group->members screen
		function _group_members( $sql ) {

			$exclude = implode( ',', $this->their_blocked_ids );

			if( !empty( $exclude ) ) {
				$exclude_sql = " AND m.user_id NOT IN ({$exclude}) ";

				$pos = strpos( $sql, 'ORDER' );

				$sql = substr_replace($sql, $exclude_sql, $pos, 0);
			}

			return $sql;
		}

		/*
		 *  Create Block button on Profile page
		*/

		function _profile_page_block_button() {

			if ( bp_is_my_profile() )
				return;

			$target_id = bp_displayed_user_id();

			if( $this->this_id != $target_id )
				$this->_block_button( $target_id );

		}


		/*
		 *  remove action buttons on Profile page if you are blocking them - to prevent harassment
		*/


		function _remove_profile_public_message_button( $button ) {
			$target_id = bp_displayed_user_id();

			if( in_array( $target_id, $this->your_blocked_ids ) )
				$button = NULL;

			return $button;
		}

		function _remove_profile_private_message_button( $button ) {
			$target_id = bp_displayed_user_id();

			if( in_array( $target_id, $this->your_blocked_ids ) )
				$button = '';

			return $button;

		}

		function _remove_add_friend_button( $button ) {

			if( bp_is_user() )
				$target_id = bp_displayed_user_id();
			else
				$target_id = bp_get_member_user_id();

			if( in_array( $target_id, $this->your_blocked_ids ) )
				$button = '';

			return $button;
		}

		/*
		 *  Create Block buttons in Members loop
		*/

		function _members_loop() {

			$target_id = bp_get_member_user_id();

			if( $this->this_id != $target_id )
				$this->_block_button( $target_id );

		}


		/*
		 *  Create Block buttons in Group Members loop
		*/

		function _group_members_loop() {

			$target_id = bp_get_group_member_id();

			if( $this->this_id != $target_id )
				$this->_block_button( $target_id );

		}



		/*
		 * Create and handle the Block button
		*/

		function _block_button( $target_id ) {

			if( !$target_id )
				return;

			if ( user_can( $target_id, 'unblock_member' ) )
				return;

			if ( in_array( $target_id, $this->your_blocked_ids ) ) {
				$block_button_text = __('UnBlock', 'bp-block-member');
				$block_button_title = __('Allow this member to see you.', 'bp-block-member');
				$style = 'style="color: #CC0000"';
				$action = 'unblock';
			}
			else {
				$block_button_text = __('Block', 'bp-block-member');
				$block_button_title = __('Block this member from seeing you.', 'bp-block-member');
				$style = '';
				$action = 'block';
			}
			?>

			<div class="generic-button block-member" id="block-member-<?php echo $target_id; ?>"><a class="block-button" <?php echo $style; ?> id="<?php echo $target_id; ?>" href="<?php echo $this->_bp_block_link( $target_id, $action ); ?>" title="<?php echo $block_button_title; ?>"><?php echo $block_button_text; ?></a></div>
			<?php
		}



		function _bp_block_link( $target_id, $action ) {
			return apply_filters( 'bp_profile_block_link', esc_url( add_query_arg( array(
				'action'    => $action,
				'id'        => $this->this_id,
				'target'    => $target_id,
				'token'     => wp_create_nonce( 'block-' . $target_id )
			) ) ), $this->this_id, $target_id );
		}

		function _bp_block_handle_actions() {

			if ( !isset( $_REQUEST['action'] ) || !isset( $_REQUEST['id'] ) || !isset( $_REQUEST['token'] ) || !isset( $_REQUEST['target'] ) ) return;

			if ( ! wp_verify_nonce( $_REQUEST['token'], 'block-' . $_REQUEST['target'] ) )
                die( 'Block Button Security Check - Failed' );

			switch ( $_REQUEST['action'] ) {
				case 'unblock' :
					$this->_unblock( $_REQUEST['id'], $_REQUEST['target'] );
				break;

				case 'block' :
					$this->_block( $_REQUEST['id'], $_REQUEST['target'] );
				break;

				default :
					do_action( 'bp_block_action' );
				break;
			}

			/**
			 * since cbox-* themes load some pages over ajax (e.g. tabs on member directory),
			 * this function may get confused and try to redirect to /wp-admin/admin-ajax.php
			 * we don't want that.
			 */
			$url = esc_url_raw( remove_query_arg( array( 'action', 'id', 'target', 'token' ) ) );
			$url = str_replace( '/wp-admin/admin-ajax.php', '/members', $url );

			wp_safe_redirect( $url );
			exit();
		}

		function _unblock( $user_id, $target_id ) {
			global $wpdb, $bp;

			if( $user_id != $this->this_id )
				return;

			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$bp->table_prefix}bp_block_member WHERE user_id = %d AND target_id = %d",
				$user_id, $target_id
				)
			);

		}

		function _block( $user_id, $target_id ) {
			global $wpdb, $bp;

			if( $user_id != $this->this_id )
				return;

			$wpdb->query(  $wpdb->prepare(
				"INSERT INTO {$bp->table_prefix}bp_block_member (user_id, target_id) VALUES (%d, %d)",
				$user_id, $target_id
				)
			);

			if ( bp_is_active( 'friends' ) ) {

				$is_friend = friends_check_friendship_status( $user_id, $target_id ); // friends_check_friendship( $user_id, $target_id );

				if( $is_friend != 'not_friends' ) {

					friends_remove_friend( $user_id, $target_id );

					if ( bp_is_active( 'notifications' ) )
						bp_notifications_delete_notifications_by_item_id( $target_id, $user_id, 'friends', 'friendship_request', $secondary_item_id = false );

					//if( $is_friend != 'is_friends' ) // friends_remove_friend substracts 1 even if the request is pending
					//	friends_update_friend_totals( $user_id, $target_id );

				}
			}
		}



	/**
	 *	menu page in wp-admin
	*/


	function _block_admin_menu() {

		if ( is_multisite() ) {
			add_submenu_page('settings.php', __( 'BuddyBlock', 'bp'), __( 'BuddyBlock', 'bp' ), 'unblock_member', 'bp-block-member', array( $this, '_block_admin_screen' ) );
		} else {
			add_options_page( __( 'BuddyBlock', 'bp'), __( 'BuddyBlock', 'bp' ), 'unblock_member', 'bp-block-member', array( $this, '_block_admin_screen' ) );
		}
	}


	function _block_admin_screen() {
		?>
		<div class="wrap">
			<div id="icon-tools" class="icon32"><br/></div>
			<h2><?php _e('BuddyBlock', 'bp-block-member'); ?></h2>

			<?php $this->_block_visibility();  ?>

			<?php $this->_block_roles();  ?>

			<?php $this->_block_create_form();  ?>

			<h3><?php _e('Blocked Members', 'bp-block-member'); ?></h3>

			<?php
			$bp_block_member_list_table = new BP_Block_Member_List_Table();
			$bp_block_member_list_table->prepare_items();
			?>

			<form id="notes-filter" method="post">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php $bp_block_member_list_table->display(); ?>
			</form>
		</div>
	<?php
	}


	function _block_admin_styles() {
		$page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
		if( 'bp-block-member' != $page )
			return;

		$style_str = '<style type="text/css">';
		$style_str .= '.column-username { width: 20%; }';
		$style_str .= '.column-target { width: 20%; }';
		$style_str .= '.column-unblock_target { width: 60%; }';
		$style_str .= '.alt-color1 { background-color: #fcfcfb; }';
		$style_str .= '.alt-color2 { background-color: #f8f8fb; }';
		$style_str .= '</style>';
		echo $style_str;
	}


	private function _block_visibility() {

		if( !is_super_admin() )
			return;
		?>

		<script type="text/javascript">
		jQuery(function() {
			jQuery('#visibility_display').click(function() {
				jQuery('#visibility_show').toggle();
				return false;
			});
		});
		</script>

		<div class='wrap'>

			<h3><a href="#" id="visibility_display"><?php _e('Visibility', 'bp-block-member'); ?></a></h3>

			<?php echo $this->block_visibility_message; ?>

			<div id="visibility_show" name="visibility_show" style="display: none;">

				<?php _e('If selected, a blocked member and their content will not be visible to you.', 'bp-block-member'); ?>
				<br/>
				<?php _e('If not selected, a blocked member cannot see you, but their content will be visible to you.', 'bp-block-member'); ?>

				<br/><br/>

				<form action="" name="block-visibility-form" id="block-visibility-form"  method="post" class="standard-form">

				<?php wp_nonce_field('block-visibility-action', 'block-visibility-field'); ?>

				<?php $option = get_option( 'bp_block_visibility' ); ?>

				<input type="checkbox" id="pp-visibility" name="pp-visibility" value="1" <?php checked( $option, 1 ); ?> /> <?php _e("Yes, I want to hide blocked members and their content.", "bp-block-member"); ?>

				<br/><br/>

				<input type="hidden" name="block-visibility" value="1"/>
				<input type="submit" name="submit" class="button button-primary" value="<?php _e('Save Checkbox', 'bp-block-member'); ?>"/>
				</form>
			</div>
		</div>
		<br/>
	<?php
	}

	//  update visibility checkbox
	private function _block_visibility_update() {

		if( isset( $_POST['block-visibility'] ) ) {

			if( !wp_verify_nonce($_POST['block-visibility-field'],'block-visibility-action') )
				die('Security check');

			if( !is_super_admin() )
				return;

			delete_option( 'bp_block_visibility' );

			if( ! empty( $_POST['pp-visibility'] ) )
				update_option( 'bp_block_visibility', '1' );


			$this->block_visibility_message .=
					"<div class='updated below-h2'>" .  __('Visibility has been updated.', 'bp-block-member') . "</div>";

		}
	}

	// display role access form if administrator
	private function _block_roles(){
		global $wp_roles;

		if( !is_super_admin() )
			return;

		$all_roles = $wp_roles->roles;
		$current_allowed_roles = explode(",", get_option( 'bp_block_roles' ));
		?>

		<script type="text/javascript">
		jQuery(function() {
			jQuery('#assign_user_display').click(function() {
				jQuery('#assign_user_show').toggle();
				return false;
			});
		});
		</script>

		<div class='wrap'>

			<h3><a href="#" id="assign_user_display"><?php _e('Assign User Roles', 'bp-block-member'); ?></a></h3>

			<?php echo $this->block_assign_message; ?>

			<div id="assign_user_show" name="assign_user_show" style="display: none;">

				<?php _e("1. Assigned roles can access the 'Blocked Members' list and 'Block a Member' below.", "bp-block-member"); ?><br/>
				<?php _e('2. Their profile page will not include a Block button. They cannot be blocked.', 'bp-block-member'); ?><br/><br/>

				<form action="" name="block-member-access-form" id="block-member-access-form"  method="post" class="standard-form">

				<?php
				wp_nonce_field('allowed-block-roles-action', 'allowed-block-roles-field');
				$role_checkbox_str = "";
				?>

				<ul id="pp-user_roles">

				<?php foreach(  $all_roles as $key => $value ){

					if ( in_array($key, $current_allowed_roles) ) $checked = ' checked="checked"';
					else $checked = '';

					if ( $key == 'administrator' || $key == 'super_admin' ) :?>

						<li><label><input type="checkbox" id="admin-preset-role" name="admin-preset" checked="checked" disabled /> <?php echo ucfirst($key); ?></label></li>

				<?php else: ?>

						<li><label for="pp-allow-roles-<?php echo $key ?>"><input id="pp-allow-roles-<?php echo $key ?>" type="checkbox" name="allow-roles[]" value="<?php echo $key ?>" <?php echo  $checked ; ?> /> <?php echo ucfirst($key); ?></label></li>

				<?php endif;

				}?>

				</ul>
				<input type="hidden" name="block-role-access" value="1"/>
				<input type="submit" name="submit" class="button button-primary" value="<?php _e('Save Changes', 'bp-block-member'); ?>"/>
				</form>
			</div>
		</div>
		<br/>
	<?php
	}


	//  update allowed roles
	private function _block_roles_update() {
		global $wp_roles;

		if( isset( $_POST['block-role-access'] ) ) {

			if( !wp_verify_nonce($_POST['allowed-block-roles-field'],'allowed-block-roles-action') )
				die('Security check');

			if( !is_super_admin() )
				return;

			$all_roles = $wp_roles->roles;

			foreach(  $all_roles as $key => $value ){
				if( 'administrator' != $key ) {
					$role = get_role( $key );
					$role->remove_cap( 'unblock_member' );
				}
			}

			if( isset( $_POST['allow-roles'] ) ) {
				foreach( $_POST['allow-roles'] as $key => $value ){

					if( array_key_exists($value, $all_roles ) ) {
						$new_roles[] = $value;
						$role = get_role( $value );
						$role->add_cap( 'unblock_member' );
					}
				}
				$new_roles = 'administrator,super_admin,' . implode(",", $new_roles);	//echo $new_roles;
			}
			else
				$new_roles = 'administrator,super_admin';

			$updated = update_option( 'bp_block_roles', $new_roles );

			if( $updated )
				$this->block_assign_message .=
						"<div class='updated below-h2'>" .  __('User Roles have been updated.', 'bp-block-member') . "</div>";
			else
				$this->block_assign_message .=
						"<div class='updated below-h2' style='color: red'>" .  __('No changes were detected re User Roles.', 'bp-block-member') . "</div>";

		}
	}

	// show the Block a Member form
	private function _block_create_form() {
	?>

		<script type="text/javascript">
		jQuery(function() {
			jQuery('#block_user_display').click(function() {
				jQuery('#block_user_show').toggle();
				return false;
			});
		});
		</script>

		<div class='wrap'>

			<h3><a href="#" id="block_user_display"><?php _e('Block a Member', 'bp-block-member'); ?></a></h3>

			<?php echo $this->block_create_message; ?>

			<div id="block_user_show" name="block_user_show" style="display: none;">

				<?php _e('You will need the user login names for both members.', 'bp-block-member'); ?><br/><br/>

				<?php _e('Enter the user login names:', 'bp-block-member'); ?><br/>
				<form action="" name="block-member-create-form" id="block-member-create-form"  method="post" class="standard-form">

				<?php wp_nonce_field('create-block-action', 'create-block-field'); ?>
				<input type="text" name="member" maxlength="25" value="<?php if( isset( $_POST['member'] ) ) echo $_POST['member']; ?>" />	&nbsp; <em><?php _e('wants to block', 'bp-block-member'); ?></em> &nbsp; <input type="text" name="target" maxlength="25" value="<?php if( isset( $_POST['target'] ) ) echo $_POST['target']; ?>" />
				<br/><br/>&nbsp;&nbsp;
				<input type="hidden" name="block-member-create" value="1"/>
				<input type="submit" name="submit" class="button button-primary" value="<?php _e('Create Member Block', 'bp-block-member'); ?>  "/>
				</form>
			</div>
		</div>
		<br/>
	<?php
	}

	// create a block between members if submitted
	private function _block_create() {
		global $wpdb, $bp;

		if( isset( $_POST['block-member-create'] ) ) {

			if( !wp_verify_nonce($_POST['create-block-field'],'create-block-action') )
				die('Security check');

			if( !current_user_can( 'unblock_member' ) )
				return false;

			$member_id = $target_id = NULL;

			if( isset( $_POST['member'] ) && !empty( $_POST['member'] ) ) {
				$member = $_POST['member'];
				$member_id = $wpdb->get_var( $wpdb->prepare(
					"SELECT ID FROM {$wpdb->prefix}users WHERE user_login = %s", $member
				) );
				if( NULL == $member_id )
					$this->block_create_message .=
						"<div class='updated below-h2' style='color: red'>" .
						__('Invalid user login name in the first box.', 'bp-block-member') .
						"</div>";
			}
			else
				$this->block_create_message .=
					"<div class='updated below-h2' style='color: red'>" .
					__('Please enter a user login name in the first box.', 'bp-block-member') .
					"</div>";


			if( isset( $_POST['target'] ) && !empty( $_POST['target'] ) ) {
				$target = $_POST['target'];
				$target_id = $wpdb->get_var( $wpdb->prepare(
					"SELECT ID FROM {$wpdb->prefix}users WHERE user_login = %s", $target
				) );
				if( NULL == $target_id )
					$this->block_create_message .=
						"<div class='updated below-h2' style='color: red'>" .
						__('Invalid user login name in the second box.', 'bp-block-member') .
						"</div>";
			}
			else
				$this->block_create_message .=
					"<div class='updated below-h2' style='color: red'>" .
					__('Please enter a user login name in the second box.', 'bp-block-member') .
					"</div>";

			if( ( NULL != $member_id && NULL != $target_id) && ( $member_id != $target_id ) ) {

				//make sure block doesn't already exist.
				$block_id = $wpdb->get_var( $wpdb->prepare(
					"SELECT id FROM {$bp->table_prefix}bp_block_member WHERE user_id = %d AND target_id = %d",
					$member_id, $target_id
				) );

				if( NULL == $block_id ) {
					$new_block = $wpdb->query(  $wpdb->prepare(
						"INSERT INTO {$bp->table_prefix}bp_block_member (user_id, target_id) VALUES (%d, %d)",
						$member_id, $target_id
					) );

					if( !$new_block )
						$this->block_create_message .=
							"<div class='updated below-h2' style='color: red'>" .
							__('There was a database error.', 'bp-block-member') .
							"</div>";
					else
						$this->block_create_message .=
							"<div class='updated below-h2' style='color: green'>{$member} " .
							__('is now blocking', 'bp-block-member') .
							" {$target}.</div>";

				}
				else
					$this->block_create_message .=
						"<div class='updated below-h2' style='color: red'>{$member} " .
						__('is already blocking', 'bp-block-member') .
						" {$target}.</div>";

			}
			else {
				if ( $_POST['member'] == $_POST['target']  && !empty( $_POST['member'] ) )
					$this->block_create_message .=
						"<div class='updated below-h2' style='color: red'>{$member} " .
						__('cannot block themself.', 'bp-block-member') .
						"</div>";
			}
		}
	}

} // end of BP_Block_Member class


function bp_block_member_list() {

	if(!class_exists('WP_List_Table'))
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );


	class BP_Block_Member_List_Table extends WP_List_Table {

		private $block_unblock_message = ''; 	// success / error message for action;

		 function __construct() {
			//global $status, $page;
			 parent::__construct( array(
			'singular'=> 'block',
			'plural' => 'blocks',
			'ajax'	=> false //We won't support Ajax for this table
			) );
		 }

		function get_columns() {
			return $columns= array(
				'cb'				=> '<input type="checkbox" />',
				'username'			=> __('User', 'bp-member-notes'),
				'target'			=> __('is Blocking', 'bp-member-notes'),
				'unblock_target'	=> __('UnBlock', 'bp-member-notes')
			);
		}

		public function get_sortable_columns() {
			return $sortable = array(
				'username'=>array('b.user_login', 'ASC'),
			);
		}

		function get_bulk_actions() {
			$actions = array(
				'delete' => 'UnBlock'
			);
			return $actions;
		}


		function delete_block( $id ) {
			global $wpdb, $bp;

			$wpdb->query( $wpdb->prepare(
					"DELETE FROM {$bp->table_prefix}bp_block_member WHERE id = %d",
					$id
				) );

			$this->block_unblock_message .=
				"<div class='updated below-h2' style='color: green'> Member block was deleted.</div>";

			echo $this->block_unblock_message;
		}

		function process_bulk_action() {

			if( 'delete'===$this->current_action() ) {
				foreach( $_POST['id'] as $id ) {
					$this->delete_block( $id );
				}
			}

			if( 'delete-single'===$this->current_action() ) {
				$nonce = $_REQUEST['_wpnonce'];
				if (! wp_verify_nonce($nonce, 'block-nonce') ) die('Security check');

				$this->delete_block( $_GET['id'] );
			}

		}


		function prepare_items() {
			global $wpdb, $_wp_column_headers, $bp;
			$screen = get_current_screen();

			$this->process_bulk_action();

			$query = "
				SELECT a.id, a.user_id, a.target_id, b.user_login AS userName, c.user_login AS targetName
				FROM {$bp->table_prefix}bp_block_member a
				JOIN {$bp->table_prefix}users b ON ( b.ID = a.user_id )
				JOIN {$bp->table_prefix}users c ON ( c.ID = a.target_id )
			";

			$orderby = !empty($_GET["orderby"]) ? esc_sql($_GET["orderby"]) : 'ASC';
			$order = !empty($_GET["order"]) ? esc_sql($_GET["order"]) : '';
			if(!empty($orderby) & !empty($order)){ $query.=' ORDER BY '.$orderby.' '.$order; }
			else
				$query.=' ORDER BY b.user_login ASC ';

			$totalitems = $wpdb->query($query); //return the total number of affected rows

			$perpage = 10;

			$paged = !empty($_GET["paged"]) ? esc_sql($_GET["paged"]) : '';

			if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ $paged=1; }

			$totalpages = ceil($totalitems/$perpage);

			if(!empty($paged) && !empty($perpage)){
				$offset=($paged-1)*$perpage;
				$query.=' LIMIT '.(int)$offset.','.(int)$perpage;
			}

			$this->set_pagination_args( array(
				"total_items" => $totalitems,
				"total_pages" => $totalpages,
				"per_page" => $perpage,
			) );

			$columns = $this->get_columns();
			$hidden = array();
			$sortable = $this->get_sortable_columns();
			$this->_column_headers = array($columns, $hidden, $sortable);

			$this->items = $wpdb->get_results($query);

		}

		function display_rows() {

			$records = $this->items;

			list( $columns, $hidden ) = $this->get_column_info();

			$alt_color = true;

			if( !empty($records) ) {
				foreach( $records as $rec ) {

					if( $alt_color )
						$tr_class = "alt-color1";
					else
						$tr_class = "alt-color2";

					echo '<tr class="' . $tr_class . '" id="record_'.$rec->user_id.'">';

					$alt_color = !$alt_color;

					$block_name_link = bp_core_get_user_domain( $rec->user_id );
					$block_target_link = bp_core_get_user_domain( $rec->target_id );

					foreach ( $columns as $column_name => $column_display_name ) {

						$class = "class='$column_name column-$column_name'";
						$style = "";
						if ( in_array( $column_name, $hidden ) ) $style = ' style="display:none;"';

						$attributes = $class . $style;

						switch ( $column_name ) {

							case "cb":
								echo '<th scope="row" class="check-column">';
								echo '<input type="checkbox" name="id[]" value="' . $rec->id . '"/>';
								echo '</th>';
								break;

							case "username":
								$avatar = bp_core_fetch_avatar ( array( 'item_id' => $rec->user_id, 'type' => 'thumb' ) );
								echo '<td '. $attributes . '>' . $avatar  . '<strong><a href="' . $block_name_link .
									'" title="' . __('Profile', 'bp-block-member') .'" target="_blank">' .
									stripslashes($rec->userName).'</a></strong></td>';
								break;

							case "target":
								$avatar = bp_core_fetch_avatar ( array( 'item_id' => $rec->target_id, 'type' => 'thumb' ) );
								echo '<td '. 'class="username column-username"' . '>'. $avatar  . '<strong><a href="' . $block_target_link .
									'" title="' . __('Profile', 'bp-block-member') .'" target="_blank">' .
									stripslashes($rec->targetName).'</a></strong></td>';
								break;

							case "unblock_target":
								$block_nonce= wp_create_nonce('block-nonce');
								echo '<td '. $attributes . '>';
								echo sprintf('<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">' .
									__('UnBlock', 'bp-block-member') .
									'</a>',$_REQUEST['page'],'delete-single',$rec->id,$block_nonce);
								echo '</td>';
								break;

						}
					}
					echo'</tr>';
				}
			}
		}
	}  // end of BP_Block_Member_List_Table class
}

