<?php
/**
 * HC Member Profiles field types
 *
 * @package Hc_Member_Profiles
 */

/**
 * Groups xprofile field type.
 */
class BP_XProfile_Field_Type_Groups extends BP_XProfile_Field_Type {

	/**
	 * Name for field type.
	 *
	 * @var string The name of this field type.
	 */
	public $name = 'BP Groups';

	/**
	 * The name of the category that this field type should be grouped with. Used on the [Users > Profile Fields] screen in wp-admin.
	 *
	 * @var string
	 */
	public $category = 'HC';

	/**
	 * If allowed to store null/empty values.
	 *
	 * @var bool If this is set, allow BP to store null/empty values for this field type.
	 */
	public $accepts_null_value = true;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Allow field types to modify the appearance of their values.
	 *
	 * By default, this is a pass-through method that does nothing. Only
	 * override in your own field type if you need to provide custom
	 * filtering for output values.
	 *
	 * @param mixed      $field_value Field value.
	 * @param string|int $field_id    ID of the field.
	 * @return mixed
	 */
	public static function display_filter( $field_value, $field_id = '' ) {
		$html        = '';
		$group_types = bp_groups_get_group_types();

		foreach ( $group_types as $group_type ) {
			$querystring = bp_ajax_querystring( 'groups' ) . '&' . http_build_query(
				[
					'group_type' => $group_type,
					// Action & type are blank to override cookies setting filters from directory.
					'action'     => '',
					'type'       => '',
					// Use alpha order rather than whatever directory set.
					'orderby'    => 'name',
					'order'      => 'ASC',
				]
			);

			if ( bp_has_groups( $querystring ) ) {
				$html .= '<h5>' . strtoupper( $group_type ) . '</h5>';
				$html .= '<ul class="group-type-' . $group_type . '">';
				while ( bp_groups() ) {
					bp_the_group();
					$html .= '<li><a href="' . bp_get_group_permalink() . '">' . bp_get_group_name() . '</a></li>';
				}
				$html .= '</ul>';
			}
		}

		return $html;
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 * @return void
	 */
	public function edit_field_html( array $raw_properties = [] ) {
		echo 'This field lists your group memberships.';
	}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 * @return void
	 */
	public function admin_field_html( array $raw_properties = [] ) {
		echo "This field lists the user's group memberships.";
	}

}
