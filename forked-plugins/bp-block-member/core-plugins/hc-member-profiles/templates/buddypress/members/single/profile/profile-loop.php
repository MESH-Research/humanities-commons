<?php
/**
 * Buddypress template override.
 *
 * @package Hc_Member_Profiles
 */

do_action( 'bp_before_profile_loop_content' );

?>

<form> <?php // <form> is only here for styling consistency between edit & view modes ?>

	<div class="left">
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

</form>

<?php do_action( 'bp_after_profile_loop_content' ); ?>
