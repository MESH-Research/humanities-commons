<?php
if ( !defined( 'BUDDYBLOCK_VERSION' ) ) exit;

function bp_profile_block_actions () {
	bp_profile_block_handle_actions();
}
add_action( 'bp_ready', 'bp_profile_block_actions' );


function bp_block_setup_navigation() {

	if( ! is_super_admin() )
		if ( !is_user_logged_in() || get_current_user_id() != bp_displayed_user_id() ) 
			return;

	if ( bp_displayed_user_domain() ) {
		$user_domain = bp_displayed_user_domain();
	} elseif ( bp_loggedin_user_domain() ) {
		$user_domain = bp_loggedin_user_domain();
	} else {
		return;
	}		
		
	bp_core_new_subnav_item( array(
		'name'                    => __( 'Blocked Members', 'bp-block-member' ),
		'slug'                    => 'blocked',
		'parent_url'              => $user_domain . 'settings/',
		'parent_slug'             => 'settings',
		'screen_function'         => 'bp_my_blocked_members',
		'show_for_displayed_user' => false
	) );
}
add_action( 'bp_setup_nav',   'bp_block_setup_navigation' );

function bp_block_setup_tool_bar() {

	if ( !bp_use_wp_admin_bar() || defined( 'DOING_AJAX' ) )
		return;

	if ( is_user_logged_in() ) {
		global $wp_admin_bar;

		$wp_admin_bar->add_menu( array(
			'parent' => 'my-account-settings',
			'id'     => 'my-block-list',
			'title'  => __( 'Blocked Members', 'bp-block-member' ),
			'href'   => bp_loggedin_user_domain() . 'settings/blocked/'
		) );
	}
}
add_action( 'admin_bar_menu', 'bp_block_setup_tool_bar', 110 );


function bp_my_blocked_members() {
	//add_action( 'bp_template_title',   'bp_my_blocked_title' );
	add_action( 'bp_template_content', 'bp_my_blocked_members_screen' );
	bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
}

//function bp_my_blocked_title() { echo __( 'Blocked Members', 'bp-block-member' ); }


function bp_my_blocked_members_screen() {
	global $wpdb, $bp;
	//global $block_member_instance;

	$user_id = bp_displayed_user_id();

	$blocked_ids = $wpdb->get_col( "SELECT DISTINCT target_id FROM {$bp->table_prefix}bp_block_member WHERE user_id = '$user_id' ");
	//$blocked_ids = $wpdb->get_col( "SELECT target_id FROM {$bp->table_prefix}bp_block_member WHERE user_id = '$user_id' ");

?>

	<table class="users-blocked">

<?php
		if( empty( $blocked_ids ) )
			echo '<tr><td colspan="2">' . __( 'You are not blocking any members.', 'bp-block-member' ) . '</td></tr>';

		else {
			echo '<tr><td colspan="2">' . __( 'You are blocking these members:', 'bp-block-member' ) . '</td></tr>';

			foreach ( $blocked_ids as $blocked_id ) {
?>
				<tr>
					<td class="user"><a href="<?php echo bp_core_get_user_domain( $blocked_id ); ?>"><?php echo bp_core_get_username( $blocked_id ); ?></a></td>
					<td class="actions"><a href="<?php echo bp_profile_unblock_link( $user_id, $blocked_id ); ?>"><?php _e( 'UnBlock', 'bp-block-member' ); ?></a></td>
				</tr>
<?php
			}
		}
?>
	</table>
<?php
}

function bp_profile_block_unblock( $blocker, $blockee ) {
	global $wpdb, $bp;

	$wpdb->query( $wpdb->prepare(
		"DELETE FROM {$bp->table_prefix}bp_block_member WHERE user_id = %d AND target_id = %d",
		$blocker, $blockee
		)
	);

}

function bp_profile_unblock_link( $user_id = 0, $blocked_id = 0 ) {
	return apply_filters( 'bp_profile_unblock_link', esc_url( add_query_arg( array(
		'action' => 'unblock',
		'list'   => $user_id,
		'num'    => $blocked_id,
		'token'  => wp_create_nonce( 'unblock-' . $blocked_id )
	) ) ), $user_id, $blocked_id );
}

function bp_profile_block_handle_actions() {

	if ( !isset( $_REQUEST['action'] ) || !isset( $_REQUEST['list'] ) || !isset( $_REQUEST['token'] ) || !isset( $_REQUEST['num'] ) ) return;
	
	switch ( $_REQUEST['action'] ) {
		case 'unblock' :
			if ( wp_verify_nonce( $_REQUEST['token'], 'unblock-' . $_REQUEST['num'] ) ) {
					
				bp_profile_block_unblock( $_REQUEST['list'], $_REQUEST['num'] );
				
				bp_core_add_message( __( 'Member was UnBlocked.', 'bp-block-member' ) );
			}
		break;

		default :
			do_action( 'bp_block_action' );
		break;
	}
	
	wp_safe_redirect(  esc_url_raw( remove_query_arg( array( 'action', 'list', 'num', 'token' ) ) ) );
	exit();
}

/**
 * Add a link to block a member from the private messages inbox.
 *
 * Called by the bp_messages_thread_options action hook.
 *
 * @author Mike Thicke
 */
function block_member_private_messages_inbox_link() {
	$block_member_instance = new BP_Block_Member();

	$thread_id = bp_get_message_thread_id();
	if ( ! $thread_id ) {
		return;
	}

	$message_thread = new BP_Messages_Thread( $thread_id );

	$sender_id = $message_thread->last_sender_id;
	if ( ! $sender_id ) {
		return;
	}

	if ( in_array( $sender_id, $block_member_instance->get_your_blocked_ids() ) ) {
		$block_button_text  = __('UnBlock', 'bp-block-member');
		$block_button_title = __('Allow this member to see you.', 'bp-block-member');
		$style              = 'style="color: #CC0000"';
		$action             = 'unblock';
	}
	else {
		$block_button_text  = __('Block', 'bp-block-member');
		$block_button_title = __('Block this member from seeing you.', 'bp-block-member');
		$style              = '';
		$action             = 'block';
	}

	$block_href = esc_url_raw( $block_member_instance->_bp_block_link( $sender_id, $action ) );

	echo "<a href='$block_href' title='$block_button_title' $style>$block_button_text</a>";
}

add_action( 'bp_messages_thread_options', 'block_member_private_messages_inbox_link' );

