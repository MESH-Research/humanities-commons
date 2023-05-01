<?php
/**
 * HC Member Profiles field types
 *
 * @package Hc_Member_Profiles
 */

/**
 * Activity xprofile field type.
 */
class BP_XProfile_Field_Type_Activity extends BP_XProfile_Field_Type {

	/**
	 * Name for field type.
	 *
	 * @var string The name of this field type.
	 */
	public $name = 'BP Activity';

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
		$max  = 5;
		$args = [
			'max'    => $max,
			// Action & type are blank to override cookies setting filters from directory.
			'action' => '',
			'type'   => '',
		];

		if ( bp_has_activities( $args ) ) {

			$actions_html = '';

			while ( bp_activities() ) {
				bp_the_activity();

				$action = trim(
					strip_tags(
						bp_get_activity_action(
							[
								'no_timestamp' => true,
							]
						), '<a>'
					)
				);
				if ( 'activity_update' === bp_get_activity_type() && bp_activity_has_content() ) {
					$action .= ': ' . trim( bp_get_activity_content_body() );
				}

				$activity_type           = bp_get_activity_type();
				$displayed_user_fullname = bp_get_displayed_user_fullname();
				$link_text_char_limit    = 30;

				if ( 'updated_profile' === $activity_type ) {
					continue;
				}

				// Some types end their action strings with ':' - remove it.
				$action = preg_replace( '/:$/', '', $action );

				// Replace "blog post" with "post".
				$action = str_ireplace( 'blog post', 'post', $action );

				// Wrapper not only serves to contain the action text but also helps DOMDocument traverse the "tree" without breaking it.
				$action     = "<li class=\"$activity_type\">" . $action . '</li>';
				$action_doc = new DOMDocument();

				// Encoding prevents mangling of multibyte characters.
				// Constants ensure no <body> or <doctype> tags are added.
				$action_doc->loadHTML(
					mb_convert_encoding( $action, 'HTML-ENTITIES', 'UTF-8' ),
					LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
				);

				// @codingStandardsIgnoreStart Codesniffer doesn't like camelCase - ignore.
				// removeChild() causes the next anchor to be skipped entirely.
				// Using a second foreach is a workaround.
				foreach ( $action_doc->getElementsByTagName( 'a' ) as $anchor ) {
					if ( $anchor->nodeValue === $displayed_user_fullname ) {
						$anchor->parentNode->removeChild( $anchor );
						break;
					}
				}
				foreach ( $action_doc->getElementsByTagName( 'a' ) as $anchor ) {
					if ( strlen( $anchor->nodeValue ) > $link_text_char_limit ) {
						// @codingStandardsIgnoreLine
						$anchor->nodeValue = htmlspecialchars( substr( $anchor->nodeValue, 0, $link_text_char_limit - 1 ) . '…' );
					}
				}
				$action = $action_doc->saveHTML();
				// @codingStandardsIgnoreEnd

				// Only add actions which are unique in the feed.
				if ( empty( $actions_html ) || strpos( $action, $actions_html ) === false ) {
					$actions_html .= $action;
				}
			}// End while().

			return "<ul>$actions_html</ul>";
		}// End if().
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
		echo 'This field lists your recent activity.';
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
		echo "This field lists the user's recent activity.";
	}

}
