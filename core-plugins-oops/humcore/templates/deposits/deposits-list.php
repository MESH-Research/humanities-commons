<?php
/**
 * Deposits List
 *
 * @package BuddyPress
 * @subpackage bp-default
 */

Humcore_Theme_Compatibility::get_header(); ?>

<?php do_action( 'bp_before_deposits_list_page' ); ?>

<div id="content" role="main" class="<?php do_action( 'content_class' ); ?>">
<div class="padder">

<h3><?php _e( 'CORE Deposits Listing ', 'humcore_domain' ); ?>

<?php do_action( 'bp_before_deposits_list_loop' ); ?>

<?php if ( humcore_has_deposits( '&per_page=250' ) ) : ?>

	<div id="deposits-stream" class="item-list">

	<?php
	while ( humcore_deposits() ) :
		humcore_the_deposit();
?>

		<?php do_action( 'bp_before_deposit_item' ); ?>

				<?php do_action( 'humcore_deposits_list_entry_content' ); ?>

		<?php do_action( 'bp_after_deposit_item' ); ?>

	<?php endwhile; ?>

	</div><!-- #deposits-stream -->

	<?php if ( humcore_deposit_has_more_items() ) : ?>

			<?php $page_url = sprintf( '%1$s/deposits/list/?dpage=%2$s', bp_get_root_domain(), humcore_get_deposit_page_number() + 1 ); ?>

		<div id="pag-bottom" class="pagination">
			<div id="deposits-loop-pag-bottom" class="pagination-links"><a href="<?php echo $page_url; ?>">Next Page</a></div>
		</div>

	<?php endif; ?>

<?php else : ?>

	<div id="message" class="info">
		<p><?php _e( 'Sorry, there were no deposits found.', 'humcore_domain' ); ?></p>
	</div>

<?php endif; ?>

<?php do_action( 'bp_after_deposits_list_loop' ); ?>

</div><!-- .padder -->
</div><!-- #content -->

<?php do_action( 'bp_after_deposits_list_page' ); ?>

<?php Humcore_Theme_Compatibility::get_footer(); ?>
