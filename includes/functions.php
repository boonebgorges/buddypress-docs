<?php

/**
 * Miscellaneous utility functions
 *
 * @package BuddyPress_Docs
 * @since 1.2
 */

/**
 * 
 */
function bp_docs_get_associated_item_id_from_term_slug( $term_slug = '', $item_type = '' ) {
	if ( !$term_slug || !$item_type ) {
		return false;
	}

	$item_id = 0;
	
	// The item_type should be hidden in the slug
	$slug_array = explode( '-', $term_slug );
	
	if ( $item_type == $slug_array[1] ) {	
		$item_id = $slug_array[0];
	}
	
	return apply_filters( 'bp_docs_get_associated_item_id_from_term_slug', $item_id, $term_slug, $item_type );
}


?>