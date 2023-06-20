<?php
/**
 * Buddypress template override.
 *
 * @package Hc_Member_Profiles
 */

do_action( 'bp_before_profile_edit_content' );

?>

<form action="<?php bp_the_profile_group_edit_form_action(); ?>" method="post" id="profile-edit-form" class="standard-form <?php bp_the_profile_group_slug(); ?>">

	<div class="left">
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::NAME ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::TITLE ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::AFFILIATION ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::MASTODON ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::TWITTER ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::LINKEDIN ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::FACEBOOK ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::FIGSHARE ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::ORCID ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::SITE ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::INTERESTS ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::GROUPS ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::ACTIVITY ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::BLOGS ); ?>
	</div>

	<div class="right">
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::ABOUT ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::EDUCATION ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::CV ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::DEPOSITS ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::PUBLICATIONS ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::BLOGPOSTS ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::PROJECTS ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::TALKS ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::MEMBERSHIPS ); ?>
	</div>

	<div class="edit-action-bar">
		<?php do_action( 'template_notices' ); ?>

		<div class="generic-button">
			<input type="submit" value="Back to View Mode" id="cancel">
			<input type="submit" name="profile-group-edit-submit" id="profile-group-edit-submit" value="Save Changes" />
		</div>
	</div>

	<input type="hidden" name="field_ids" id="field_ids" value="<?php bp_the_profile_field_ids(); ?>" />

	<?php wp_nonce_field( 'bp_xprofile_edit' ); ?>

</form>

<?php do_action( 'bp_after_profile_edit_content' ); ?>
