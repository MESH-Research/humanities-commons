<?php
/**
 * Class Test_Functions
 *
 * @package Hc_Member_Profiles
 */

/**
 * Tests for standalone functions.
 */
class Test_Functions extends BP_UnitTestCase {

	/**
	 * Ensure various possible URL user-input values are normalized correctly.
	 *
	 * @dataProvider hcmp_get_normalized_url_field_value_provider
	 *
	 * @param string $field_name Name.
	 * @param string $field_value Value.
	 * @param string $expected_return_value Value.
	 */
	function test_hcmp_get_normalized_url_field_value( $field_name, $field_value, $expected_return_value ) {
		$user_id = $this->factory->user->create();

		add_filter(
			'bp_displayed_user_id', function() use ( $user_id ) {
				return $user_id;
			}
		);

		$field_id = $this->factory->xprofile_field->create(
			[
				'field_group_id' => $this->factory->xprofile_group->create(),
				'type'           => 'textbox',
				'name'           => $field_name,
			]
		);

		xprofile_set_field_data( $field_name, $user_id, $field_value );

		$this->assertEquals(
			hcmp_get_normalized_url_field_value( $field_name ),
			$expected_return_value
		);
	}

	/**
	 * Only the first two elements are used by the actual test directly,
	 * the third provides a value to the function being tested so we can
	 * compare to the second.
	 */
	function hcmp_get_normalized_url_field_value_provider() {
		$domains = [
			HC_Member_Profiles_Component::TWITTER  => 'twitter.com',
			HC_Member_Profiles_Component::FACEBOOK => 'facebook.com',
			HC_Member_Profiles_Component::LINKEDIN => 'linkedin.com/in',
			HC_Member_Profiles_Component::ORCID    => 'orcid.org',
		];

		$field_names = [
			HC_Member_Profiles_Component::TWITTER,
			HC_Member_Profiles_Component::FACEBOOK,
			HC_Member_Profiles_Component::LINKEDIN,
			HC_Member_Profiles_Component::ORCID,
		];

		$data_sets = [];

		foreach ( $field_names as $name ) {
			$patterns = [
				'#@#',
				'#(https?://)?(www\.)?' . preg_quote( $domains[ $name ], '#' ) . '/?#',
			];

			// Use same user input values for all fields.
			$field_values = [
				'0123456789',
				'example',
				'@example',
				'@@example',
				"{$domains[ $name ]}/example",
				"www.{$domains[ $name ]}/example",
				"http://{$domains[ $name ]}/example",
				"http://www.{$domains[ $name ]}/example",
				"https://{$domains[ $name ]}/example",
				"https://www.{$domains[ $name ]}/example",
			];

			foreach ( $field_values as $value ) {
				$cleaned_value = strip_tags(
					preg_replace(
						$patterns,
						'',
						$value
					)
				);

				$data_sets[] = [
					$name,
					$value,
					"<a href=\"https://{$domains[ $name ]}/$cleaned_value\">$cleaned_value</a>",
				];
			}
		}

		return $data_sets;
	}

	/**
	 * Ensure all required xprofile fields are correctly created.
	 */
	function test__hcmp_create_xprofile_fields() {
		$default_fields = [
			HC_Member_Profiles_Component::NAME         => 'textbox',
			HC_Member_Profiles_Component::AFFILIATION  => 'textbox',
			HC_Member_Profiles_Component::TITLE        => 'textbox',
			HC_Member_Profiles_Component::SITE         => 'url',
			HC_Member_Profiles_Component::TWITTER      => 'textbox',
			HC_Member_Profiles_Component::ORCID        => 'textbox',
			HC_Member_Profiles_Component::FACEBOOK     => 'url',
			HC_Member_Profiles_Component::LINKEDIN     => 'url',
			HC_Member_Profiles_Component::ABOUT        => 'textarea',
			HC_Member_Profiles_Component::EDUCATION    => 'textarea',
			HC_Member_Profiles_Component::PUBLICATIONS => 'textarea',
			HC_Member_Profiles_Component::PROJECTS     => 'textarea',
			HC_Member_Profiles_Component::TALKS        => 'textarea',
			HC_Member_Profiles_Component::MEMBERSHIPS  => 'textarea',
		];

		// These are only installed if the required dependency is active.
		$extra_fields = [
			HC_Member_Profiles_Component::DEPOSITS     => 'core_deposits',
			HC_Member_Profiles_Component::CV           => 'bp_attachment',
			HC_Member_Profiles_Component::INTERESTS    => 'academic_interests',
			HC_Member_Profiles_Component::GROUPS       => 'bp_groups',
			HC_Member_Profiles_Component::ACTIVITY     => 'bp_activity',
			HC_Member_Profiles_Component::BLOGS        => 'bp_blogs',
		];

		$result = _hcmp_create_xprofile_fields();

		foreach ( $default_fields as $name => $type ) {
			$field_id = xprofile_get_field_id_from_name( $name );
			$field = xprofile_get_field( $field_id );

			$this->assertInstanceOf( 'BP_XProfile_Field', $field );
			$this->assertSame( $type, $field->type );
		}

		$existing_types = bp_xprofile_get_field_types();

		foreach ( $extra_fields as $name => $type ) {
			$field_id = xprofile_get_field_id_from_name( $name );
			$field = xprofile_get_field( $field_id );

			if ( in_array( $type, array_keys( $existing_types ) ) ) {
				$this->assertInstanceOf( 'BP_XProfile_Field', $field );
				$this->assertSame( $type, $field->type );
			}
		}
	}

	/**
	 * Ensure newlines are preserved in xprofile field data when editing.
	 *
	 * @dataProvider newlines_preserved_provider
	 *
	 * @param string Field value.
	 */
	function test_newlines_preserved( $value ) {
		$u = self::factory()->user->create();
		add_filter(
			'bp_displayed_user_id', function() use ( $u ) {
				return $u;
			}
		);

		$g = self::factory()->xprofile_group->create();
		$f = self::factory()->xprofile_field->create( [
			'field_group_id' => $g,
			'type' => 'textarea',
		] );

		$field = xprofile_get_field( $f );

		xprofile_set_field_data( $f, $u, $value );

		$args = [
			'field' => $f,
			'user_id' => $u,
		];

		$expected = nl2br( $value );
		$actual = _hcmp_get_field_data( $field->name );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * Provider for test_newlines_preserved.
	 */
	function newlines_preserved_provider() {
		return [
			[ 'This value does not contain a newline.' ],
			[ 'This value has multiple newlines in a row!


Oh my.' ],
			[ '<strong>Nowhere In Particular</strong>
Ph.D., Zebra Affairs

<strong>The Graduate Center, University of Mars</strong>
M.Phil., Special Topics

<strong>Elsewhere College</strong>
B.A., English, summa cum laude' ],
		];
	}
}
