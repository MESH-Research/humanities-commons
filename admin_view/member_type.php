<?php		

	$user = wp_get_current_user();

	// Bail if no user ID.
	if ( empty( $user->data->ID ) ) {
		return;
	}

	$types = bp_get_member_types( array(), 'objects' );
	$current_type = bp_get_member_type( $user->data->ID );

	if( ! $current_type ) :
	?>
	<p>HC</p>
	<?php else : ?>
		<p><?php echo strtoupper( $current_type ); ?></p>
	<?php endif; ?>