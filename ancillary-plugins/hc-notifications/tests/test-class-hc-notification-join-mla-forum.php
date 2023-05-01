<?php

class HC_Notification_Join_MLA_Forum_Test extends BP_UnitTestCase {

	/**
	 * Ensure notification only fires for groups with an MLA forum ID.
	 */
	public function test_only_mla_committees() {
		$u = $this->factory()->user->create();
		$g1 = $this->factory()->group->create();
		$g2 = $this->factory()->group->create();

		groups_update_groupmeta( $g1, 'mla_oid', 'M the only thing that makes this MLA is that this value starts with M' );
		groups_update_groupmeta( $g2, 'mla_oid', 'this is not MLA because this value does not start with M' );

		groups_join_group( $g1, $u );
		groups_join_group( $g2, $u );

		$notifications = bp_notifications_get_all_notifications_for_user( $u );

		$count = 0;

		foreach ( $notifications as $n ) {
			if (
				buddypress()->hc_notifications->id === $n->component_name &&
				HC_Notification_Join_MLA_Forum::$action === $n->component_action
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
		$u = $this->factory()->user->create();
		$g = $this->factory()->group->create();

		groups_update_groupmeta( $g, 'mla_oid', 'M the only thing that makes this MLA is that this value starts with M' );

		groups_join_group( $g, $u );

		$notifications = bp_notifications_get_all_notifications_for_user( $u );

		foreach ( $notifications as $n ) {
			if (
				buddypress()->hc_notifications->id === $n->component_name &&
				HC_Notification_Join_MLA_Forum::$action === $n->component_action
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
				HC_Notification_Join_MLA_Forum::$action === $n->component_action
			) {
				$count++;
			}
		}

		$this->assertSame( 0, $count );
	}
}
