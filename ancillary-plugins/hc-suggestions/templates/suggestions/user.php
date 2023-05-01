<?php
/**
 * Template for user post results
 *
 * @package HC_Suggestions
 */

/**
 * A WP_User in WP_Post disguise.
 */
global $post;

/**
 * The return value of bp_core_fetch_avatar() can contain badges and other markup.
 * We only want the <img>.
 */
$bp_avatar = bp_core_fetch_avatar(
	[
		'item_id' => $post->ID,
		'type'    => 'thumb',
		'width'   => 70,
		'height'  => 70,
	]
);
preg_match( '/<img.*>/', $bp_avatar, $matches );
$avatar_img = $matches[0];

$name        = xprofile_get_field_data( HC_Member_Profiles_Component::NAME, $post->ID );
$title       = xprofile_get_field_data( HC_Member_Profiles_Component::TITLE, $post->ID );
$affiliation = xprofile_get_field_data( HC_Member_Profiles_Component::AFFILIATION, $post->ID );

$common_term_names = array_intersect(
	wpmn_get_object_terms(
		get_current_user_id(), HC_Suggestions_Widget::TAXONOMY, [
			'fields' => 'names',
		]
	),
	wpmn_get_object_terms(
		$post->ID, HC_Suggestions_Widget::TAXONOMY, [
			'fields' => 'names',
		]
	)
);

?>

<div class="result" data-post-id="<?php echo $post->ID; ?>">
	<div class="image">
		<a href="<?php echo $post->permalink; ?>"><?php echo $avatar_img; ?></a>
	</div>

	<div class="excerpt">
		<span class="name"><a href="<?php echo $post->permalink; ?>"><?php echo $name; ?></a></span>
		<span class="title"><?php echo $title; ?></span>
		<span class="affiliation"><?php echo $affiliation; ?></span>

		<span class="terms">
			<?php
			foreach ( $common_term_names as $term_name ) {
				$search_url = add_query_arg(
					[
						'academic_interests' => urlencode( $term_name ),
					],
					bp_get_members_directory_permalink()
				);
				printf(
					'<a class="term" href="%s">%s</a>',
					$search_url,
					$term_name
				);
			}
			?>
		</span>

	</div>

	<div class="actions">
		<a class="btn" href="<?php echo $post->permalink; ?>">View</a>
		<?php
		bp_follow_add_follow_button(
			[
				'leader_id'   => $post->ID,
				'follower_id' => get_current_user_id(),
				'link_class'  => 'btn',
				'wrapper'     => false,
			]
		);
		?>
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
