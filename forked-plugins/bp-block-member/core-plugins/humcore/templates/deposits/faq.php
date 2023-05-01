<?php
/**
 * Template Name: HumCORE FAQ
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
