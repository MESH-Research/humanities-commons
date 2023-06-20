<?php

/**
 * BuddyPress - Groups Loop
 *
 * Querystring is set via AJAX in _inc/ajax.php - bp_legacy_theme_object_filter()
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

?>

<?php do_action( 'bp_before_groups_loop' ); ?>

<?php if ( bp_has_groups( bp_ajax_querystring( 'groups' ) ) ) : ?>

	<div id="pag-top" class="pagination">

		<div class="pag-count" id="group-dir-count-top">

			<?php bp_groups_pagination_count(); ?>

		</div>

		<div class="pagination-links" id="group-dir-pag-top">

			<?php bp_groups_pagination_links(); ?>

		</div>

	</div>

	<?php do_action( 'bp_before_directory_groups_list' ); ?>

	<ul id="groups-list" class="item-list" role="main">

	<?php while ( bp_groups() ) : bp_the_group(); ?>

		<li <?php bp_group_class(); ?>>
			<div class="item-avatar">
				<?php /* <a href="<?php bp_group_permalink(); ?>"> */ ?>
					<?php bp_group_avatar( 'type=full&width=70&height=70' ); ?>
				<?php /* </a> */ ?>
			</div>

			<div class="item">
				<div class="item-title"><a href="<?php bp_group_permalink(); ?>"><?php bp_group_name(); ?></a></div>
				<div class="item-meta"><div class="mobile"><?php bp_group_type(); ?></div><span class="activity"><?php printf( __( 'active %s', 'boss' ), bp_get_group_last_active() ); ?></span></div>

				<div class="item-desc"><?php bp_group_description_excerpt(); ?></div>

                <div class="item-meta">

                    <?php
                    global $groups_template;
                    if ( isset( $groups_template->group->total_member_count ) ) {
                         $count = (int) $groups_template->group->total_member_count;
                    } else {
                         $count = 0;
                    }

                    $html = sprintf( _n( '<span class="meta-wrap"><span class="count">%1s</span> <span>member</span></span>', '<a href="' . bp_get_group_all_members_permalink() . '"<span class="meta-wrap"><span class="count">%1s</span> <span>members</span></span></a>', $count, 'boss' ), $count );

                    ?>

                    <span class="desktop"><?php bp_group_type(); ?>&nbsp; / </span><?php  echo $html; ?>

                </div>

				<?php do_action( 'bp_directory_groups_item' ); ?>

			</div>

			<div class="action">

                <div class="action-wrap">

                    <?php do_action( 'bp_directory_groups_actions' ); ?>
				</div>

			</div>

			<div class="clear"></div>
		</li>

	<?php endwhile; ?>

	</ul>

	<?php do_action( 'bp_after_directory_groups_list' ); ?>

	<div id="pag-bottom" class="pagination">

		<div class="pag-count" id="group-dir-count-bottom">

			<?php bp_groups_pagination_count(); ?>

		</div>

		<div class="pagination-links" id="group-dir-pag-bottom">

			<?php bp_groups_pagination_links(); ?>

		</div>

	</div>

<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( 'There were no groups found.', 'boss' ); ?></p>
	</div>

<?php endif; ?>

<?php do_action( 'bp_after_groups_loop' ); ?>
