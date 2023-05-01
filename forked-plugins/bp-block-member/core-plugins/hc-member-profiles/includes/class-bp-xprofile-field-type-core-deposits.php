<?php
/**
 * CORE Deposits field type.
 *
 * @package Hc_Member_Profiles
 */

/**
 * CORE Deposits field type.
 */
class BP_XProfile_Field_Type_CORE_Deposits extends BP_XProfile_Field_Type {

	/**
	 * Name for field type.
	 *
	 * @var string The name of this field type.
	 */
	public $name = 'CORE Deposits';

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

		// Change publications display name depending on whether the user has CORE deposits.
		$displayed_user = bp_get_displayed_user();

		// Only invoke humcore_has_deposits if there's actually a user to query.
		if ( $displayed_user ) {
			$querystring = sprintf( 'username=%s', urlencode( $displayed_user->userdata->user_login ) );
			if ( function_exists( 'humcore_has_deposits' ) && humcore_has_deposits( $querystring ) ) {
				HC_Member_Profiles_Component::$display_names[ HC_Member_Profiles_Component::PUBLICATIONS ] = 'Other Publications';
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
		$genres = humcore_deposits_genre_list();

		// Deposits display under one of these genre headers in this order.
		$genres_order = [
			'Monograph',
			'Book',
			'Article',
			'Book chapter',
			'Book section',
			'Code',
			'Conference proceeding',
			'Dissertation',
			'Documentary',
			'Essay',
			'Fictional work',
			'Music',
			'Performance',
			'Photograph',
			'Poetry',
			'Thesis',
			'Translation',
			'Video essay',
			'Visual art',
			'Conference paper',
			'Course material or learning objects',
			'Syllabus',
			'Abstract',
			'Bibliography',
			'Blog Post',
			'Book review',
			'Catalog',
			'Chart',
			'Code',
			'Data set',
			'Finding aid',
			'Image',
			'Interview',
			'Map',
			'Presentation',
			'Report',
			'Review',
			'Technical report',
			'White paper',
			'Other',
		];

		// Genres with a plural form not equal to the value returned by humcore_deposits_genre_list().
		$genres_pluralized = [
			'Abstract'              => 'Abstracts',
			'Article'               => 'Articles',
			'Bibliography'          => 'Bibliographies',
			'Blog Post'             => 'Blog Posts',
			'Book'                  => 'Books',
			'Book chapter'          => 'Book chapters',
			'Book review'           => 'Book reviews',
			'Book section'          => 'Book sections',
			'Catalog'               => 'Catalogs',
			'Chart'                 => 'Charts',
			'Conference paper'      => 'Conference papers',
			'Conference proceeding' => 'Conference proceedings',
			'Data set'              => 'Data sets',
			'Dissertation'          => 'Dissertations',
			'Documentary'           => 'Documentaries',
			'Essay'                 => 'Essays',
			'Fictional work'        => 'Fictional works',
			'Finding aid'           => 'Finding aids',
			'Image'                 => 'Images',
			'Interview'             => 'Interviews',
			'Map'                   => 'Maps',
			'Monograph'             => 'Monographs',
			'Performance'           => 'Performances',
			'Photograph'            => 'Photographs',
			'Presentation'          => 'Presentations',
			'Report'                => 'Reports',
			'Review'                => 'Reviews',
			'Syllabus'              => 'Syllabi',
			'Technical report'      => 'Technical reports',
			'Thesis'                => 'Theses',
			'Translation'           => 'Translations',
			'Video essay'           => 'Video essays',
			'White paper'           => 'White papers',
		];

		$html        = '';
		$genres_html = [];

		$displayed_user = bp_get_displayed_user();
		$querystring    = http_build_query(
			[
				'username' => $displayed_user->userdata->user_login,
				'per_page' => 99,
			]
		);

		if ( humcore_has_deposits( $querystring ) ) {
			while ( humcore_deposits() ) {
				humcore_the_deposit();
				$metadata = (array) humcore_get_current_deposit();
				$item_url = sprintf( '%1$s/deposits/item/%2$s', bp_get_root_domain(), $metadata['pid'] );

				$genres_html[ $metadata['genre'] ][] = '<li><a href="' . esc_url( $item_url ) . '/">' . $metadata['title_unchanged'] . '</a></li>';
			}

			// Sort results according to $genres_order.
			$genres_html = array_filter(
				array_replace( array_flip( $genres_order ), $genres_html ),
				'is_array'
			);

			foreach ( $genres_html as $genre => $genre_html ) {
				$html .= '<h5>' . ( isset( $genres_pluralized[ $genre ] ) ? $genres_pluralized[ $genre ] : $genre ) . '</h5>';
				$html .= '<ul>' . implode( '', $genre_html ) . '</ul>';
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
		echo 'This field lists your CORE deposits.';
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
		$this->edit_field_html();
	}

}
