<?php

/**
 * This file contains functions and filters that modify the appearance of content in the context
 * of BuddyPress Docs pages
 *
 * @package BuddyPress Docs
 * @since 1.0
 */

/**
 * Reduces the length of excerpts on the BP Docs doc list
 *
 * @package BuddyPress Docs
 * @since 1.0
 *
 * @uses apply_filters() Plugins can filter bp_docs_excerpt_length to change the default
 * @param int $length WordPress's default excerpt length
 * @return int $length The filtered excerpt length
 */
function bp_docs_excerpt_length( $length ) {
	if ( bp_docs_is_bp_docs_page() ) {
		$length = apply_filters( 'bp_docs_excerpt_length', 20 );
	}
	
	return $length;
}
add_filter( 'excerpt_length', 'bp_docs_excerpt_length' );
 
?>