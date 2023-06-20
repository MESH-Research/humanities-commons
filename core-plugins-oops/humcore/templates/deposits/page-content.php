<?php

if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		do_action( 'bp_before_deposits_core_page' );
?>
<article id="page" <?php post_class( 'humcore' ); ?>>
	<header>
	<h1 class="page-header">
		<?php
			the_title();
			edit_post_link( ' ✍', '', ' ' );
		?>
		</h1>
		</header>
		<?php
			do_action( 'bp_before_deposits_core_page_loop' );
		?>
		<div class="entry">
			<?php
				the_content( __( '<p class="serif">Read the rest of this page &rarr;</p>', 'humcore_domain' ) );
			?>
			<div style="clear: both;"></div>
			<?php
			wp_link_pages(
				array(
					'before'         => __( '<p><strong>Pages:</strong> ', 'humcore_domain' ),
					'after'          => '</p>',
					'next_or_number' => 'number',
				)
			);
			?>
			</div>
			<?php
			do_action( 'bp_after_deposits_core_page_loop' );
			?>
<?php
	do_action( 'bp_after_deposits_core_page' );
	endwhile;
	endif;
?>
	</article>
