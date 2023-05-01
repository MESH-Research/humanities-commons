<?php
/**
 * Buddypress template override.
 *
 * @package Hc_Member_Profiles
 */

do_action( 'bp_before_profile_content' );

?>

<div id="profile-main" role="main">

	<?php
		// HC_Member_Profiles_Component Edit.
	if ( bp_is_current_action( 'edit' ) ) {
		bp_locate_template( array( 'members/single/profile/edit.php' ), true );
	} // Display XHC_Member_Profiles_Component.
	elseif ( bp_is_active( 'xprofile' ) ) {
		bp_locate_template( array( 'members/single/profile/profile-loop.php' ), true );
	}
	?>

</div><!-- .profile -->

<?php do_action( 'bp_after_profile_content' ); ?>
