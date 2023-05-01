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

	<div class="page-full-width network-profile">
	<div id="primary" class="site-content">
	<div id="content">
	<div id="buddypress">

		<?php
		if ( bp_has_members( 'max=1' ) ) :
			while ( bp_members() ) :
				bp_the_member();
				do_action( 'bp_before_user_deposits_content' );
				?>

						<div id="item-header">

							<?php bp_locate_template( array( 'members/single/member-header.php' ), true ); ?>

						</div><!-- #item-header -->

						<div class="full-width">
						<div id="item-main-content">
						<div id="item-nav">
								<div class="item-list-tabs no-ajax" id="object-nav" role="navigation">
										<ul id="nav-bar-filter" class="horizontal-responsive-menu">

												<?php bp_get_displayed_user_nav(); ?>
												<?php do_action( 'bp_user_deposits_options_nav' ); ?>

										</ul>
								</div>
						</div><!-- #item-nav -->

						<div id="item-body" role="main">

							<?php do_action( 'bp_before_user_deposits_body' ); ?>

							<?php

							$displayed_user_fullname = bp_get_displayed_user_fullname();

							if ( ( ! empty( $displayed_user_fullname ) && bp_get_loggedin_user_fullname() == $displayed_user_fullname )
									&& is_user_logged_in() && humanities_commons::hcommons_vet_user() ) {
								echo '<a href="/deposits/item/new/" class="bp-deposits-deposit button" title="Upload Your Work" style="float: right;">Upload Your Work</a><p />';
							}
							?>

							<div class="item-list-tabs" id="subnav">
								<ul>
								<li class="current selected" id="deposits-personal"><a href="#">My Deposits</a></li>

								<li id="deposits-order-select" class="last filter">

									<label for="deposits-order-by"><?php _e( 'Order By:', 'humcore_domain' ); ?></label>
						<select id="deposits-order-by">
							<option value="date"><?php _e( 'Newest Deposits', 'humcore_domain' ); ?></option>
							<!-- <option value="author"><?php _e( 'Primary Author', 'humcore_domain' ); ?></option> -->
							<option value="title"><?php _e( 'Title', 'humcore_domain' ); ?></option>

							<?php do_action( 'humcore_deposits_directory_order_options' ); ?>

						</select>
					</li>

					</ul>
				</div><!-- .item-list-tabs -->

				<h3><?php do_action( 'bp_template_title' ); ?></h3>
				<div id="deposits-dir-list" class="deposits dir-list" style="display: block;">

				<?php do_action( 'bp_template_content' ); ?>

				</div>

				<?php do_action( 'bp_after_user_deposits_body' ); ?>

						</div><!-- #item-body -->
						</div><!-- .item-main-content -->
						</div><!-- #full-width -->

					<?php do_action( 'bp_after_user_deposits_content' ); ?>
					<?php
		endwhile;
endif;
?>

	</div><!-- #buddypress -->
	</div><!-- #content -->
	</div><!-- #primary -->

	</div><!-- .page-full-width -->

<?php Humcore_Theme_Compatibility::get_footer(); ?>
