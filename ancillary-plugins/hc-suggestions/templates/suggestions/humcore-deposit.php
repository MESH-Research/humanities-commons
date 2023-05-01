<?php
/**
 * Template for humcore_deposit post results
 *
 * @package HC_Suggestions
 */

global $post;

// CORE deposit icons & download URLs depend on file data set in post meta.
$post_meta = get_post_meta( $post->ID );
// @codingStandardsIgnoreLine
$file_metadata = @json_decode( $post_meta['_deposit_file_metadata'][0], true );

// CORE icon.
// @codingStandardsIgnoreLine
$file_type_data = @wp_check_filetype( $file_metadata['files'][0]['filename'], wp_get_mime_types() );
if ( empty( $file_type_data['ext'] ) ) {
	$file_type_data = [
		'ext' => 'txt',
	]; // Fallback in case we didn't find an actual type.
}
// @codingStandardsIgnoreLine
$avatar_img = @sprintf(
	'<img class="deposit-icon" src="%s" alt="%s" />',
	'/app/plugins/humcore/assets/' . esc_attr( $file_type_data['ext'] ) . '-icon-48x48.png',
	esc_attr( $file_type_data['ext'] )
);

// CORE download URL.
// @codingStandardsIgnoreLine
$download_url = @sprintf(
	'/deposits/download/%s/%s/%s/',
	$file_metadata['files'][0]['pid'],
	$file_metadata['files'][0]['datastream_id'],
	$file_metadata['files'][0]['filename']
);

$permalink = get_permalink( $post->ID );

?>

<div class="result" data-post-id="<?php echo $post->ID; ?>">
	<div class="image">
		<a href="<?php echo $permalink; ?>"><?php echo $avatar_img; ?></a>
	</div>

	<div class="excerpt">
		<span class="name"><a href="<?php echo $permalink; ?>"><?php the_title(); ?></a></span>
		<span class="description"><?php echo wp_trim_words( get_the_excerpt(), 20 ); ?></span>
	</div>

	<div class="actions">
		<a class="btn" href="<?php echo $permalink; ?>">View</a>
		<a class="btn" href="<?php echo $download_url; ?>">Download</a>
		<?php
		if ( is_user_logged_in() ) {
			printf(
				'<a class="hide btn" data-post-id="%s" data-post-type="%s" href="#">Hide suggestion</a>',
				$post->ID,
				$post->post_type
			);
		}
		?>
	</div>
</div>
