<?php

class HC_Notification_Newsletter_Opt_Out_Test extends BP_UnitTestCase {

	/**
	 * Ensure deleting a notification does not result in the same type of notification being added later.
	 */
	public function test_permanent_delete() {
		$u = $this->factory()->user->create();
		$g = $this->factory()->group->create();

		$user = get_userdata( $u );
		wp_set_current_user( $u );

		add_user_meta( get_current_user_id(), 'newsletter_optin', 'no' );

		do_action( 'wp_login', $user->user_login, $user );

		$notifications = bp_notifications_get_all_notifications_for_user( $u );

		foreach ( $notifications as $n ) {
			if (
				buddypress()->hc_notifications->id === $n->component_name &&
				HC_Notification_Newsletter_Opt_Out::$action === $n->component_action
			) {
				// Bypass the permission check in bp_notifications_delete_notification() and delete directly.
				BP_Notifications_Notification::delete( array( 'id' => $n->id ) );
			}
		}

		do_action( 'wp_login', $user->user_login, $user );

		$notifications = bp_notifications_get_all_notifications_for_user( $u );

		$count = 0;

		foreach ( $notifications as $n ) {
			if (
				buddypress()->hc_notifications->id === $n->component_name &&
				HC_Notification_Newsletter_Opt_Out::$action === $n->component_action
			) {
				$count++;
			}
		}

		$this->assertSame( 0, $count );
	}
}
