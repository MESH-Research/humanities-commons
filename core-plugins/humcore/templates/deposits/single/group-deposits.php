<?php

/**
 * BuddyPress - Users Plugins
 *
 * This is a fallback file that external plugins can use if the template they
 * need is not installed in the current theme. Use the actions in this template
 * to output everything your plugin needs.
 *
 * @package BuddyPress
 * @subpackage bp-default
 */

?>

<?php Humcore_Theme_Compatibility::get_header(); ?>

	<div class="page-right-sidebar group-single">
	<div id="buddypress">

		<?php
		if ( bp_has_groups( 'max=1' ) ) :
			while ( bp_groups() ) :
				bp_the_group();
?>

						<div id="item-header">

				<?php bp_locate_template( array( 'groups/single/group-header.php' ), true ); ?>

						</div><!-- #item-header -->

				<div id="primary" class="site-content">
				<div id="content">

					<?php do_action( 'bp_before_group_deposits_content' ); ?>

						<div class="full-width">
						<div id="item-main-content">
						<div id="item-nav">
								<div id="object-nav" class="item-list-tabs no-ajax" role="navigation">
										<ul>
												<?php //bp_get_displayed_user_nav(); ?>
												<?php bp_get_options_nav(); ?>
												<?php //do_action( 'bp_group_deposits_options_nav' ); ?>
										</ul>
								</div>
						</div><!-- #item-nav -->

				<div class="filters">
					<div class="row">
						<div class="col-12">
				<div class="item-list-tabs" role="navigation">
						<?php do_action( 'humcore_deposits_directory_deposit_sub_types' ); ?>
						<div class="filter-type sort">

							<label for="deposits-order-by"><?php _e( 'Order By:', 'humcore_domain' ); ?></label>

							<select id="deposits-order-by">
								<option value="newest" selected="selected"><?php _e( 'Newest Deposits', 'humcore_domain' ); ?></option>
								<option value="alphabetical"><?php _e( 'Alphabetical', 'humcore_domain' ); ?></option>

								<?php do_action( 'humcore_deposits_directory_order_options' ); ?>
							</select>
						</div>
						<div class="filter-type search">

							<label for="search-deposits-field"><?php _e( 'Search Field:', 'humcore_domain' ); ?></label>

							<select id="search-deposits-field">
								<option value="all" selected="selected">All Fields</option>
								<option value="author">Author/Contributor</option>
								<option value="subject">Subject</option>
								<option value="tag">Tag</option>
								<option value="title">Title</option>

							</select>
						</div>
					<?php humcore_deposits_search_form(); ?>
				</div><!-- .item-list-tabs -->
						</div><!-- .col-12 -->
					</div><!-- .row -->
				</div><!-- .filters -->

						<div id="item-body" role="main">

				<?php do_action( 'bp_before_group_deposits_body' ); ?>

				<?php

				$displayed_user_fullname = bp_get_displayed_user_fullname();

				if ( is_user_logged_in() && humanities_commons::hcommons_vet_user() && 'public' === bp_get_group_status() ) {
					echo '<a href="/deposits/item/new/" class="bp-deposits-deposit button" title="Upload Your Work" style="float: right;">Upload Your Work</a><p />';
				}
				?>

				<h3><?php do_action( 'bp_template_title' ); ?></h3>
				<div id="deposits-dir-list" class="deposits dir-list" style="display: block;">

				<?php do_action( 'bp_template_content' ); ?>

				</div>

				<?php do_action( 'bp_after_group_deposits_body' ); ?>

						</div><!-- #item-body -->
						</div><!-- .item-main-content -->
						</div><!-- #full-width -->

					<?php do_action( 'bp_after_group_deposits_content' ); ?>
					<?php
		endwhile;
endif;
?>

	</div><!-- #content -->
	</div><!-- #primary -->

<?php Humcore_Theme_Compatibility::get_sidebar( 'buddypress' ); ?>

	</div><!-- #buddypress -->
	</div><!-- .page-right-sidebar -->

<?php Humcore_Theme_Compatibility::get_footer(); ?>
