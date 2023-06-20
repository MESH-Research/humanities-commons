<?php
/**
 * HC Member Profiles field types
 *
 * @package Hc_Member_Profiles
 */

/**
 * Academic Interests xprofile field type.
 */
class BP_XProfile_Field_Type_Academic_Interests extends BP_XProfile_Field_Type {

	/**
	 * Name for field type.
	 *
	 * @var string The name of this field type.
	 */
	public $name = 'Academic Interests';

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

		// Change UP member's interests field display name.
		$displayed_user = bp_get_displayed_user();
		if ( $displayed_user ) {
			$memberships = bp_get_member_type( $displayed_user->id, false );
			if ( is_array( $memberships ) && in_array( 'up', $memberships ) ) {
				HC_Member_Profiles_Component::$display_names[ HC_Member_Profiles_Component::INTERESTS ] = 'Professional Interests';
			}
		}
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
		$tax       = get_taxonomy( 'mla_academic_interests' );
		$interests = wpmn_get_object_terms(
			bp_displayed_user_id(), 'mla_academic_interests', array(
				'fields' => 'names',
			)
		);
		$html      = '<ul>';
		foreach ( $interests as $term_name ) {
			$search_url = esc_url(
				sprintf(
					'/?%s',
					http_build_query(
						[
							's'         => $term_name,
							'post_type' => [ 'user' ],
						]
					)
				)
			);
			$html      .= '<li><a href="' . esc_url( $search_url ) . '" rel="nofollow">';
			$html      .= $term_name;
			$html      .= '</a></li>';
		}
		$html .= '</ul>';
		return $html;
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @uses DOMDocument
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 * @return void
	 */
	public function edit_field_html( array $raw_properties = [] ) {
		global $mla_academic_interests;

		printf( '<label>%s</label>', $this->name );

		$doc = new DOMDocument();

		ob_start();
		$mla_academic_interests->edit_user_mla_academic_interests_section( wp_get_current_user() );

		// Encoding prevents mangling of multibyte characters.
		// Constants ensure no <body> or <doctype> tags are added.
		$doc->loadHTML(
			mb_convert_encoding( ob_get_clean(), 'HTML-ENTITIES', 'UTF-8' ),
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);

		// we only want the actual select element, not the header or table wrapper etc.
		echo $doc->saveHTML( $doc->getElementsByTagName( 'select' )[0] );
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
		echo "This field lists the user's academic interests.";
	}

}
