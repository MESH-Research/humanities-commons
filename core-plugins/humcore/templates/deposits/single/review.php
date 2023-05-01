<?php

/**
 * BuddyPress - Deposits (Single Item)
 *
 * This template is used to show
 * each deposit.
 *
 * @package BuddyPress
 * @subpackage bp-default
 */

?>

<?php Humcore_Theme_Compatibility::get_header(); ?>

		<div class="page-right-sidebar">
		<div id="primary" class="site-content">
	<div id="content">
		<div id="buddypress">

			<?php do_action( 'bp_before_deposit_item_template' ); ?>

			<div id="item-header">

				<?php //bp_locate_template( array( 'deposits/single/deposit-header.php' ), true ); ?>

			</div><!-- #item-header -->
<!--
						<div id="item-nav">
								<div class="item-list-tabs no-ajax" id="object-nav" role="navigation">
										<ul>
-->
												<?php //bp_get_displayed_user_nav(); ?>
												<?php //do_action( 'humcore_deposit_options_nav' ); ?>
<!--

										</ul>
								</div>
						</div>--!><!-- #item-nav -->

<div id="item-body" role="main">
<?php do_action( 'bp_before_deposit_item' ); ?>
<ul class="deposit-list item-list">
<?php
while ( humcore_deposits() ) :
	humcore_the_deposit();
?>

<li class="deposit-item mini" id="deposit-<?php humcore_deposit_id(); ?>">

	<div class="deposit-content">

<?php
$current_deposit = humcore_get_current_deposit();
if ( $current_deposit->record_creation_date === $current_deposit->record_change_date ) {
	?>
<h3>Deposit Complete!</h3>
Thank you for uploading your work to <em>CORE</em>! Please take a moment to review your metadata. If you need to make changes or upload a different file, press Edit this Deposit. This file will be available for edit until it is reviewed and its status changed to published by the Humanities Commons team, at which point we'll send notifications to your groups. If you want to make later changes, please contact us at <a href="mailto:hello@hcommons.org?subject=My Deposit">hello@hcommons.org</a>
	<?php } else { ?>
<h3>Deposit Updated!</h3>
We've made those edits for you. Should you wish to make any further edits, you can visit this item's <a href="/deposits/item/<?php echo $current_deposit->pid; ?>">permanent URL</a> until its status has changed from provisional to published. After that, please contact us at <a href="mailto:hello@hcommons.org?subject=My Deposit">hello@hcommons.org</a>
	<?php } ?>

		<?php do_action( 'humcore_deposit_item_review_content' ); ?>

		<?php if ( is_user_logged_in() ) : ?>

			<?php if ( ! humcore_is_deposit_item_review() ) : ?>
			<div class="deposit-meta">
<!--TODO check if activity component is active -->
				<?php if ( 1 == 1 ) : ?>
					<?php $activity_id = humcore_get_deposit_activity_id(); ?>

					<?php if ( ! humcore_deposit_activity_is_favorite( $activity_id ) ) : ?>

						<a href="<?php humcore_deposit_activity_favorite_link( $activity_id ); ?>" class="button fav bp-secondary-action" title="<?php esc_attr_e( 'Mark as Favorite', 'humcore_domain' ); ?>"><?php _e( 'Favorite', 'humcore_domain' ); ?></a>

					<?php
					$wp_referer = wp_get_referer();
					printf(
						'<a id="deposit-return" href="%1$s" class="button deposits-return white">Back to Deposits</a>',
						( ! empty( $wp_referer ) && ! strpos( $wp_referer, 'item/new' ) ) ? $wp_referer : '/deposits/'
					);
					?>

					<?php else : ?>

						<a href="<?php humcore_deposit_activity_unfavorite_link( $activity_id ); ?>" class="button unfav bp-secondary-action" title="<?php esc_attr_e( 'Remove Favorite', 'humcore_domain' ); ?>"><?php _e( 'Remove Favorite', 'humcore_domain' ); ?></a>

					<?php
					$wp_referer = wp_get_referer();
					printf(
						'<a id="deposit-return" href="%1$s" class="button deposits-return white">Back to Deposits</a>',
						( ! empty( $wp_referer ) && ! strpos( $wp_referer, 'item/new' ) ) ? $wp_referer : '/deposits/'
					);
					?>

					<?php endif; ?>

				<?php endif; ?>

				<?php do_action( 'humcore_deposit_entry_meta' ); ?>

			</div>
			<?php else : ?>
				<?php
				$wp_referer = wp_get_referer();
				printf(
					'<a id="deposit-return" href="%1$s" class="button deposits-return white">Back to Deposits</a>',
					( ! empty( $wp_referer ) && ! strpos( $wp_referer, 'item/new' ) ) ? $wp_referer : '/deposits/'
				);
				?>
			<?php endif; ?>

		<?php else : ?>
		<?php
		$wp_referer = wp_get_referer();
		printf(
			'<a id="deposit-return" href="%1$s" class="button deposits-return white">Back to Deposits</a>',
			( ! empty( $wp_referer ) && ! strpos( $wp_referer, 'item/new' ) ) ? $wp_referer : '/deposits/'
		);
		?>

		<?php endif; ?>

	</div>

</li>
<?php endwhile; ?>
</ul>
<?php do_action( 'bp_after_deposit_item' ); ?>

</div><!-- #item-body -->

<?php do_action( 'bp_after_deposit_item_template' ); ?>

</div><!-- #buddypress -->
</div><!-- #content -->
</div><!-- #primary -->

<div id="secondary" class="widget-area" role="complementary">
<aside id="deposits-sidebar" role="complementary">
<?php dynamic_sidebar( 'deposits-directory-sidebar' ); ?>
</aside>
</div><!-- #secondary -->
</div><!-- .page-right-sidebar -->

<?php Humcore_Theme_Compatibility::get_footer(); ?>
