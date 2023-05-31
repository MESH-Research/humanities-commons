<?php
/**
 * Add more options to default settings for new sites, and set those defaults
 * accordingly.
 *
 * Developed to address high usage of Akismet API because sites allow comments
 * by default and don't close comments on posts automatically (@link
 * https://github.com/MESH-Research/commons/issues/157).
 *
 * @author Mike Thicke
 */

namespace MESHResearch\HumanitiesCommons\MoreNewSiteDefaults;

add_action( 'wpmu_options', __NAMESPACE__ . '\add_options_to_network_settings', 10, 0 );
add_action( 'update_wpmu_options', __NAMESPACE__ . '\update_network_settings_options', 10, 0 );

// Re: hook & priority, see: https://developer.wordpress.org/reference/hooks/wp_insert_site/#comment-4878
add_action( 'wp_initialize_site', __NAMESPACE__ . '\set_new_blog_defaults', 900, 2 );

/**
 * Add options to network settings.
 *
 * Adds the following options:
 *  - default_close_comments_for_old_posts bool Whether sites should close comments on old posts by defualt.
 *  - default_close_comments_days_old      int  Days after which comments are closed on posts.
 *
 * Called by 'wpmu_options' action hook.
 * @see wp-admin/network/settings.php
 */
function add_options_to_network_settings() {
	?>
	<h2><?= __( 'Additional New Site Defaults' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?= __( 'Old comments' ); ?></th>
			<td>
				<label>
					<input
						type="checkbox"
						id="default_close_comments_for_old_posts"
						name="default_close_comments_for_old_posts" 
						value="1"
						<?php checked( (bool) get_site_option( 'default_close_comments_for_old_posts', false ) ); ?>
					/>
					<?= __( 'Automatically close comments on posts older than ' ); ?>
				</label>
				<label>
					<input
						type="number"
						id="default_close_comments_days_old"
						name="default_close_comments_days_old"
						style="width: 100px"
						value=<?= esc_attr( get_site_option( 'default_close_comments_days_old', 14 ) ); ?>
						aria-describedby="default_close_comments_days_old_desc"
					/>
				</label>
				<?= __( 'days.' ); ?>
				<br />
				<p class="screen-reader-text" id="default_close_comments_days_old_desc">
					<?= __( 'Duration in days' ); ?>
				</p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Updates network settings options from submitted form.
 *
 * Called by 'update_wpmu_options' action hook.
 * @see wp-admin/network/settings.php
 */
function update_network_settings_options() {
	$option_checkbox_defaults = [
		'default_close_comments_for_old_posts' => 0,
	];

	foreach ( $option_checkbox_defaults as $option_name => $unchecked_value ) {
		if ( ! isset( $_POST[ $option_name ] ) ) {
			$_POST[ $option_name ] = $unchecked_value;
		}
	}
	
	$options = [
		'default_close_comments_for_old_posts',
		'default_close_comments_days_old',
	];

	foreach ( $options as $option_name ) {
		if ( isset ( $_POST[ $option_name] ) ) {
			if ( is_null( get_site_option( $option_name, null ) ) ) {
				add_site_option(
					$option_name,
					wp_unslash( $_POST[ $option_name ] )
				);
			} else {
				update_site_option(
					$option_name,
					wp_unslash( $_POST[ $option_name ] )
				);
			}
		}
	}
}

/**
 * Sets default site options upon site creation.
 *
 * Called by 'wp_initialize_site' action hook.
 * @see wp-includes/ms-site.php
 * @link https://developer.wordpress.org/reference/hooks/wp_initialize_site/
 *
 * @param \WP_SITE $new_site
 */
function set_new_blog_defaults( $new_site ) {
	switch_to_blog( $new_site->blog_id );

	$options = [
		'default_close_comments_for_old_posts' => 'close_comments_for_old_posts',
		'default_close_comments_days_old'      => 'close_comments_days_old',
	];

	foreach ( $options as $network_option => $blog_option ) {
		$option_value = get_site_option( $network_option, null );

		if ( ! is_null( $option_value ) ) {
			update_option( $blog_option, $option_value );
		}
	}

	restore_current_blog();
}