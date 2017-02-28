<?php 

$types 		   = bp_groups_get_group_types( array(), 'objects' ); 
$current_types = (array) bp_groups_get_group_type( $group->id, false );
$backend_only  = bp_groups_get_group_types( array( 'show_in_create_screen' => false ) );

?>

	<ul class="categorychecklist form-no-clear">
		<?php foreach ( $types as $type ) : ?>
			<li>
				<label class="selectit">
					<?php
						if( in_array( $type->name, $current_types ) ) {

							echo esc_html( $type->labels['singular_name'] );
							if ( in_array( $type->name, $backend_only ) ) {
								printf( ' <span class="description">%s</span>', esc_html__( '(Not available on the front end)', 'buddypress' ) );
							}

						} elseif( ! $current_types[0] && $type->name == 'hc' ) {
							echo esc_html( $type->labels['singular_name'] );
							if ( in_array( $type->name, $backend_only ) ) {
								printf( ' <span class="description">%s</span>', esc_html__( '(Not available on the front end)', 'buddypress' ) );
							}
						}

					?>

				</label>
			</li>

		<?php endforeach; ?>
	</ul>