<?php
/**
 * CORE Deposits field type.
 *
 * @package Hc_Member_Profiles
 */

/**
 * CORE Deposits field type.
 */
class BP_XProfile_Field_Type_Blog_Posts extends BP_XProfile_Field_Type {

	/**
	 * Name for field type.
	 *
	 * @var string The name of this field type.
	 */
	public $name = 'Blog Posts';

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
	 * Front-end display of user's blog posts, ordered by date.
	 * 
	 * @uses DOMDocument
	 *
	 * @param mixed      $field_value Field value.
	 * @param string|int $field_id    ID of the field.
	 * @return mixed
	 */
	public static function display_filter( $field_value, $field_id = '' ) {
		$user = get_userdata( bp_core_get_displayed_userid( bp_get_displayed_user_username() ) );
		$user_posts = [];

		$cache_key = "hc-member-profiles-xprofile-blog-posts-{$user->ID}";
		$html = wp_cache_get( $cache_key );
		if ( $html ) {
			return $html;
		}

		$networks = get_networks();

		foreach ( $networks as $network ) {
			switch_to_network( $network );
			if ( bp_has_blogs() ) {
				while ( bp_blogs() ) {
					bp_the_blog();
					switch_to_blog( bp_get_blog_id() );
					$posts = get_posts( [
						'author' => $user->ID,
					] );
					foreach ( $posts as $post ) {
						if ( ! array_key_exists( $post->ID, $user_posts) ) {
							$user_posts[ $post->ID ] = [
								'permalink'  => get_permalink( $post ),
								'post_title' => $post->post_title,
								'post_date'  => get_the_date( 'Y-m-d', $post->ID ),
								'blog_title' => get_bloginfo( 'name' ),
								'blog_url'   => get_bloginfo( 'url' ),
							];
							$user_posts[ $post->ID ]->permalink = get_permalink( $post );
						}
					}
					restore_current_blog();
				}
			}
			restore_current_network();
		}

		// Sort posts in descending date order
		uasort( $user_posts, function( $a, $b ) {
			if ( $a['post_date'] === $b['post_date'] ) {
				return 0;
			}
			return ( $a['post_date'] > $b['post_date'] ? -1 : 1 );
		} );
		
		$html = "<ul>";

		foreach ( $user_posts as $post_id => $post_info ) {
			ob_start();

			?>
			<li>
				<a href='<?= $post_info['permalink']?>'><?= $post_info['post_title'] ?></a> 
				(<em><?= $post_info['blog_title'] ?>,</em>
				<?= $post_info['post_date'] ?>)
			</li>
			<?php
			$html .= ob_get_clean();
		}

		$html .= "</ul>";

		wp_cache_add( $cache_key, $html, '', 600 );
		return $html;
	}

	/**
	 * Placeholder HTML for the widget on the user side (not editable).
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 * @return void
	 */
	public function edit_field_html( array $raw_properties = [] ) {
		echo 'This field lists your blog posts.';
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
