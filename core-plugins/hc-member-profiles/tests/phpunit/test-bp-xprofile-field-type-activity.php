<?php
/**
 * Class Test_BP_XProfile_Field_Type_Activity
 *
 * @package Hc_Member_Profiles
 */

/**
 * Tests.
 */
class Test_BP_XProfile_Field_Type_Activity extends BP_UnitTestCase {

	/**
	 * Ensure display_filter correctly returns activity data.
	 */
	public function test_display_filter() {
		$user_id = $this->factory->user->create();

		$group_id = $this->factory->xprofile_group->create();

		$field_id = $this->factory->xprofile_field->create(
			[
				'field_group_id' => $group_id,
				'type'           => 'bp_activity',
			]
		);

		$args = [
			'type'    => 'activity_update',
			'user_id' => $user_id,
		];

		$this->factory->activity->create( $args );
		$this->factory->activity->create( $args );
		$this->factory->activity->create( $args );

		// Pretend we're viewing this user's profile.
		add_filter(
			'bp_get_displayed_user', function() use ( $user_id ) {
				return get_userdata( $user_id );
			}
		);

		$data = xprofile_get_field_data( $field_id, $user_id );

		$dom = new DOMDocument();
		$dom->loadHTML( $data );

		$this->assertTrue( 3 === $dom->getElementsByTagName( 'li' )->length );
	}

}
