<?php
/**
 * Buddypress template override.
 *
 * @package Hc_Member_Profiles
 */

do_action( 'bp_before_member_header' );

$follow_counts          = hcmp_get_follow_counts();
$affiliation_data       = _hcmp_get_field_data( HC_Member_Profiles_Component::AFFILIATION );
$affiliation_search_url = esc_url(
	sprintf(
		'/?%s',
		http_build_query(
			[
				's'         => $affiliation_data,
				'post_type' => [ 'user' ],
			]
		)
	)
);
$twitter_link           = hcmp_get_normalized_url_field_value( HC_Member_Profiles_Component::TWITTER );
$mastodon_link          = hcmp_get_normalized_mastodon_field_value();
$orcid_link             = hcmp_get_normalized_url_field_value( HC_Member_Profiles_Component::ORCID );
$facebook_link          = hcmp_get_normalized_url_field_value( HC_Member_Profiles_Component::FACEBOOK );
$linkedin_link          = hcmp_get_normalized_url_field_value( HC_Member_Profiles_Component::LINKEDIN );
$figshare_link          = hcmp_get_normalized_url_field_value( HC_Member_Profiles_Component::FIGSHARE );
$site_link              = _hcmp_get_field_data( HC_Member_Profiles_Component::SITE );

?>

<?php echo buddyboss_cover_photo( 'user', bp_displayed_user_id() ); ?>

<div id="item-header-cover" class="table">

	<div id="item-header-content">

		<div id="item-main">
			<h4 class="name">
				<?php echo _hcmp_get_field_data( HC_Member_Profiles_Component::NAME ); ?>
			</h4>
			<h4 class="title">
				<?php echo _hcmp_get_field_data( HC_Member_Profiles_Component::TITLE ); ?>
			</h4>
			<h4 class="affiliation">
				<a href="<?php echo esc_url( $affiliation_search_url ); ?>" rel="nofollow"><?php echo $affiliation_data; ?></a>
			</h4>
			<div class="username">
				<span class="social-label"><em>Commons</em> username:</span> <?php echo hcmp_get_username_link(); ?>
			</div>
			<?php if ( $twitter_link ) : ?>
				<div class="twitter">
					<span class="social-label"><?php echo HC_Member_Profiles_Component::$display_names[ HC_Member_Profiles_Component::TWITTER ]; ?>:</span> <?php echo $twitter_link; ?>
				</div>
			<?php endif ?>
			<?php if ( $mastodon_link ) : ?>
				<div class="mastodon">
					<span class="social-label"><?php echo HC_Member_Profiles_Component::$display_names[ HC_Member_Profiles_Component::MASTODON ]; ?>:</span> <?php echo $mastodon_link; ?>
				</div>
			<?php endif ?>
			<?php if ( $orcid_link ) : ?>
				<div class="orcid">
					<span class="social-label"><?php echo HC_Member_Profiles_Component::$display_names[ HC_Member_Profiles_Component::ORCID ]; ?>:</span> <?php echo $orcid_link; ?>
				</div>
			<?php endif ?>
			<?php if ( $facebook_link ) : ?>
				<div class="facebook">
					<span class="social-label"><?php echo HC_Member_Profiles_Component::$display_names[ HC_Member_Profiles_Component::FACEBOOK ]; ?>:</span> <?php echo $facebook_link; ?>
				</div>
			<?php endif ?>
			<?php if ( $linkedin_link ) : ?>
				<div class="linkedin">
					<span class="social-label"><?php echo HC_Member_Profiles_Component::$display_names[ HC_Member_Profiles_Component::LINKEDIN ]; ?>:</span> <?php echo $linkedin_link; ?>
				</div>
			<?php endif ?>
			<?php if ( $figshare_link ) : ?>
				<div class="figshare">
					<span class="social-label"><?php echo HC_Member_Profiles_Component::$display_names[ HC_Member_Profiles_Component::FIGSHARE ]; ?>:</span> <?php echo $figshare_link; ?>
				</div>
			<?php endif ?>
			<?php if ( strip_tags( $site_link ) ) : ?>
				<div class="website">
					<?php echo $site_link; ?>
				</div>
			<?php endif ?>
		</div><!-- #item-main -->

		<?php do_action( 'bp_before_member_header_meta' ); ?>

		<div id="item-meta">
			<div class="avatar-wrap">
				<div id="item-header-avatar">
					<a href="<?php bp_displayed_user_link(); ?>">
						<?php bp_member_avatar( 'type=full' ); ?>
					</a>
				</div><!-- #item-header-avatar -->
			</div>

			<div class="following-n-members">
				<?php if ( bp_displayed_user_id() === bp_loggedin_user_id() ) : ?>
					<a href="<?php echo bp_loggedin_user_domain() . BP_FOLLOWING_SLUG; ?>">
				<?php endif ?>
					<?php printf( 'Following <span>%d</span> members', $follow_counts['following'] ); ?>
				<?php if ( bp_displayed_user_id() === bp_loggedin_user_id() ) : ?>
					</a>
				<?php endif ?>
			</div>

			<div id="item-buttons">
				<?php // This span is only here to prevent Boss from hiding the parent. see themes/boss/js/buddyboss.js:408. ?>
				<span class="generic-button" style="display: none;"></span>
				<?php echo hcmp_get_header_actions(); ?>
			</div><!-- #item-buttons -->

			<?php do_action( 'bp_profile_header_meta' ); ?>
		</div><!-- #item-meta -->

	</div><!-- #item-header-content -->

</div><!-- #item-header-cover -->

<?php do_action( 'bp_after_member_header' ); ?>
