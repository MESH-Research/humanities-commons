<?php
/**
 * Template for bp_group post results
 *
 * @package HC_Suggestions
 */

global $post;
global $humanities_commons;

$group = groups_get_group( $post->ID );

/**
 * The return value of bp_core_fetch_avatar() can contain badges and other markup.
 * We only want the <img>.
 */
$bp_avatar = bp_core_fetch_avatar(
	[
		'item_id'    => $group->id,
		'avatar_dir' => 'group-avatars',
		'object'     => 'group',
	]
);
preg_match( '/<img.*>/', $bp_avatar, $matches );
$avatar_img = $matches[0];

remove_filter( 'bp_get_group_join_button', [ $humanities_commons, 'hcommons_check_bp_get_group_join_button' ], 10, 2 );

$join_button = bp_get_group_join_button( $group );
preg_match( '/<a.*\/a>/', $join_button, $matches );
if ( isset( $matches[0] ) ) {
	$join_button = $matches[0]; // Only need <a>, no container.
	$join_button = preg_replace( '/Join Group/', 'Join', $join_button ); // Replace button text.
	$join_button = preg_replace( '/group-button/', 'group-button btn', $join_button ); // Add consistent btn class.
}

add_filter( 'bp_get_group_join_button', [ $humanities_commons, 'hcommons_check_bp_get_group_join_button' ], 10, 2 );

?>

<div class="result" data-post-id="<?php echo $post->ID; ?>">
	<div class="image">
		<a href="<?php echo $post->permalink; ?>"><?php echo $avatar_img; ?></a>
	</div>

	<div class="excerpt">
		<span class="name"><a href="<?php echo $post->permalink; ?>"><?php echo $group->name; ?></a></span>
		<span class="description"><?php echo wp_trim_words( $group->description, 20 ); ?></span>
	</div>

	<div class="actions">
		<a class="btn" href="<?php echo $post->permalink; ?>">View</a>
		<?php echo $join_button; ?>
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
