<?php

/**
 * This file contains the template tags used on the Docs edit and create screens. They are
 * separated out so that they don't need to be loaded all the time.
 *
 * @package BuddyPress Docs
 */
 
/**
 * Echoes the output of bp_docs_get_edit_doc_title()
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 */
function bp_docs_edit_doc_title() {
	echo bp_docs_get_edit_doc_title();
}
	/**
	 * Returns the title of the doc currently being edited, when it exists
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 *
	 * @return string Doc title
	 */
	function bp_docs_get_edit_doc_title() {
		global $bp;
		
		if ( empty( $bp->bp_docs->current_post ) || empty( $bp->bp_docs->current_post->post_title ) ) {
			$title = '';
		} else {
			$title = $bp->bp_docs->current_post->post_title;
		}
			
		return apply_filters( 'bp_docs_get_edit_doc_title', $title );
	}

/**
 * Echoes the output of bp_docs_get_edit_doc_content()
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 */
function bp_docs_edit_doc_content() {
	echo bp_docs_get_edit_doc_content();
}
	/**
	 * Returns the content of the doc currently being edited, when it exists
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 *
	 * @return string Doc content
	 */
	function bp_docs_get_edit_doc_content() {
		global $bp;
		
		if ( empty( $bp->bp_docs->current_post ) || empty( $bp->bp_docs->current_post->post_content ) ) {
			$content = '';
		} else {
			$content = $bp->bp_docs->current_post->post_content;
		}
			
		return apply_filters( 'bp_docs_get_edit_doc_content', $content );
	}

/**
 * Get a list of an item's docs for display in the parent dropdown
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 */
function bp_docs_edit_parent_dropdown() {
	global $bp; 
	
	// Get the item docs to use as Include arguments
	$q 			= new BP_Docs_Query;
	$q->current_view 	= 'list';
	$qt 			= $q->build_query();
	$include_posts		= new WP_Query( $qt );
	
	$include = array();
	
	if ( $include_posts->have_posts() ) {
		while ( $include_posts->have_posts() ) {
			$include_posts->the_post();
			$include[] = get_the_ID();
		}
	}
	
	// Exclude the current doc, if this is 'edit' and not 'create' mode
	$exclude 	= ! empty( $bp->bp_docs->current_post->ID ) ? array( $bp->bp_docs->current_post->ID ) : false;

	// Highlight the existing parent doc, if any
	$parent 	= ! empty( $bp->bp_docs->current_post->post_parent ) ? $bp->bp_docs->current_post->post_parent : false;

	$pages = wp_dropdown_pages( array( 
		'post_type' 	=> $bp->bp_docs->post_type_name, 
		'exclude' 	=> $exclude,
		'include'	=> $include,
		'selected' 	=> $parent, 
		'name' 		=> 'parent_id', 
		'show_option_none' => __( '(no parent)', 'bp-docs' ),
		'sort_column'	=> 'menu_order, post_title', 
		'echo' 		=> 0 )
	);
	
	echo $pages;
}

/**
 * Are we editing an existing doc, or is this a new doc?
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 *
 * @return bool True if it's an existing doc
 */
function bp_docs_is_existing_doc() {
	global $bp;
	
	if ( empty( $bp->bp_docs->current_post ) )
		return false;
	
	return true;
}

?>