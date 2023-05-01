<?php

/**
 * BuddyPress - Deposit Stream (Single Item)
 *
 * This template is used by deposits-loop.php and AJAX functions to show
 * each deposit.
 *
 * @package BuddyPress
 * @subpackage bp-default
 */

?>

<?php do_action( 'bp_before_deposit_item' ); ?>

<li class="deposit-item mini" id="deposit-<?php humcore_deposit_id(); ?>">

	<div class="deposit-content">

		<?php do_action( 'humcore_deposits_entry_content' ); ?>

		<?php if ( is_user_logged_in() ) : ?>

			<div class="activity-meta">
<!--disable favroites -->
				<?php $activity_id = humcore_get_deposit_activity_id(); ?>

				<?php if ( 1 == 2 && bp_activity_can_favorite() ) : ?>

					<?php if ( ! humcore_deposit_activity_is_favorite( $activity_id ) ) : ?>

						<a href="<?php humcore_deposit_activity_favorite_link( $activity_id ); ?>" class="button fav bp-secondary-action" title="<?php esc_attr_e( 'Mark as Favorite', 'humcore_domain' ); ?>"><?php _e( 'Favorite', 'humcore_domain' ); ?></a>

					<?php else : ?>

						<a href="<?php humcore_deposit_activity_unfavorite_link( $activity_id ); ?>" class="button unfav bp-secondary-action" title="<?php esc_attr_e( 'Remove Favorite', 'humcore_domain' ); ?>"><?php _e( 'Remove Favorite', 'humcore_domain' ); ?></a>

					<?php endif; ?>

				<?php endif; ?>

				<?php do_action( 'humcore_deposit_entry_meta' ); ?>

			</div>

		<?php endif; ?>

	</div>

</li>

<?php do_action( 'bp_after_deposit_item' ); ?>
