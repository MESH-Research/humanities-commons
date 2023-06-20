<?php
/**
 * Template Name: HumCORE Terms Acceptance
 */

	Humcore_Theme_Compatibility::get_header();
?>
	<div class="page-full-width">
	<div id="primary" class="site-content">
	<div id="content" role="main" class="<?php do_action( 'content_class' ); ?>">
		<?php
			do_action( 'bp_before_deposits_page_content' );
			do_action( 'bp_before_deposits_page' );
		?>

		<?php
			bp_get_template_part( 'deposits/page', 'content' );
		?>

<div id="core-terms-entry-form">
<form id="core-terms-acceptance-form" class="standard-form" method="post" action="">
	<?php wp_nonce_field( 'accept_core_terms', 'accept_core_terms_nonce' ); ?>
	<div id="core-terms-entry" class="entry">
		<input type="checkbox" id="core-accept-terms" name="core_accept_terms" value="Yes" />
		<span class="description"><strong>I agree</strong></span> &nbsp; &nbsp; &nbsp; 
		<input id="core-accept-terms-continue" name="core_accept_terms_continue" class="button-large" type="submit" value="Continue" /> &nbsp; &nbsp; &nbsp; 
		<a href="/core/" id="core-accept-terms-cancel" class="button button-large">Cancel</a>
	</div>
</form>
</div>
		<?php
			do_action( 'bp_after_deposits_page' );
			do_action( 'bp_after_deposits_page_content' );
		?>
	</div><!-- #content -->
	</div><!-- #primary -->

	</div><!-- .page-full-width -->

<?php
	Humcore_Theme_Compatibility::get_footer();

?>
