<?php
/**
 * Class HC_Notification_Join_Group_Site_Test
 *
 * @package Hc_Notifications
 */

/**
 * Join Group Site notification tests.
 */
class HC_Notification_Join_Group_Site_Test extends BP_UnitTestCase {

	/**
	 * Ensure only one notification is created regardless of multiple join/leave events.
	 */
	public function test_only_one_notification_after_multiple_events() {
		if ( ! is_multisite() ) {
			$this->assertTrue( true );
			return;
		}

		if ( function_exists( 'wp_initialize_site' ) ) {
			$this->setExpectedDeprecated( 'wpmu_new_blog' );
		}

		$u = $this->factory()->user->create();
		$g = $this->factory()->group->create();
		$b = $this->factory()->blog->create();

		groups_update_groupmeta( $g, 'groupblog_blog_id', $b );

		// Join & leave the group a few times to fire the corresponding events which would trigger notifications.
		for ( $i = 0; $i < 5; $i++ ) {
			groups_join_group( $g, $u );
			$this->assertTrue( (bool) groups_is_user_member( $u, $g ) );
			groups_leave_group( $g, $u );
			$this->assertFalse( (bool) groups_is_user_member( $u, $g ) );
		}

		$notifications = bp_notifications_get_all_notifications_for_user( $u );

		$count = 0;

		foreach ( $notifications as $n ) {
			if (
				buddypress()->hc_notifications->id === $n->component_name &&
				HC_Notification_Join_Group_Site::$action === $n->component_action
			) {
				$count++;
			}
		}

		$this->assertSame( 1, $count );

	}

	/**
	 * Ensure deleting a notification does not result in the same type of notification being added later.
	 */
	public function test_permanent_delete() {
		if ( ! is_multisite() ) {
			$this->assertTrue( true );
			return;
		}
		
		if ( function_exists( 'wp_initialize_site' ) ) {
			$this->setExpectedDeprecated( 'wpmu_new_blog' );
		}

		$u = $this->factory()->user->create();
		$g = $this->factory()->group->create();
		$b = $this->factory()->blog->create();

		groups_update_groupmeta( $g, 'groupblog_blog_id', $b );

		groups_join_group( $g, $u );

		$notifications = bp_notifications_get_all_notifications_for_user( $u );

		foreach ( $notifications as $n ) {
			if (
				buddypress()->hc_notifications->id === $n->component_name &&
				HC_Notification_Join_Group_Site::$action === $n->component_action
			) {
				// Bypass the permission check in bp_notifications_delete_notification() and delete directly.
				BP_Notifications_Notification::delete( array( 'id' => $n->id ) );
			}
		}

		groups_leave_group( $g, $u );
		groups_join_group( $g, $u );

		$notifications = bp_notifications_get_all_notifications_for_user( $u );

		$count = 0;

		foreach ( $notifications as $n ) {
			if (
				buddypress()->hc_notifications->id === $n->component_name &&
				HC_Notification_Join_Group_Site::$action === $n->component_action
			) {
				$count++;
			}
		}

		$this->assertSame( 0, $count );
	}
}
