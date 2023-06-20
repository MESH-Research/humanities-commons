<?php
/**
 * HC Member Profiles field types
 *
 * @package Hc_Member_Profiles
 */

/**
 * Blogs xprofile field type.
 */
class BP_XProfile_Field_Type_Blogs extends BP_XProfile_Field_Type {

	/**
	 * Name for field type.
	 *
	 * @var string The name of this field type.
	 */
	public $name = 'BP Blogs';

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
	 * @uses DOMDocument
	 *
	 * @param mixed      $field_value Field value.
	 * @param string|int $field_id    ID of the field.
	 * @return mixed
	 */
	public static function display_filter( $field_value, $field_id = '' ) {
		// TODO rewrite to use get_networks() instead, and make not wpmn-dependent.
		global $humanities_commons;

		$html           = '';
		$societies_html = [];

		$querystring = bp_ajax_querystring( 'blogs' ) . '&' . http_build_query(
			[
				'type' => 'alphabetical',
			]
		);

		if ( bp_has_blogs( $querystring ) ) {
			while ( bp_blogs() ) {
				bp_the_blog();
				switch_to_blog( bp_get_blog_id() );
				$user = get_userdata( bp_core_get_displayed_userid( bp_get_displayed_user_username() ) );
				if ( ! empty( array_intersect( [ 'administrator', 'editor' ], $user->roles ) ) ) {
					$society_id                      = $humanities_commons->hcommons_get_blog_society_id( bp_get_blog_id() );
					$societies_html[ $society_id ][] = '<li><a href="' . bp_get_blog_permalink() . '">' . bp_get_blog_name() . '</a></li>';
				}
				restore_current_blog();
			}

			ksort( $societies_html );

			foreach ( $societies_html as $society_id => $society_html ) {
				$html .= '<h5>' . strtoupper( $society_id ) . '</h5>';
				$html .= '<ul>' . implode( '', $society_html ) . '</ul>';
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
		echo 'This field lists your blog memberships.';
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
		echo "This field lists the user's blog memberships.";
	}

}
