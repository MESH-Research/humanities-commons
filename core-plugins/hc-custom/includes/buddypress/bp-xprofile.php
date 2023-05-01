<?php
/**
 * Customizations to BuddyPress xProfile.
 *
 * @package Hc_Custom
 */

function hc_custom_bp_xprofile_field_type_is_valid( $validated, $values, $instance ) { 
	
   if ( is_string ( $values ) && !is_array( $values )  && !empty( $values ) && (false === $validated ) ) {
	$validated = true;
   }
    
    return $validated; 
} 
         

add_filter( 'bp_xprofile_field_type_is_valid', 'hc_custom_bp_xprofile_field_type_is_valid', 10, 3 ); 
