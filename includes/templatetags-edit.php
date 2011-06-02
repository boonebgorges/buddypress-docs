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
	
	// Make sure we don't limit the posts displayed
	$qt['showposts']	= -1;
	
	// Order them by name, no matter what
	$qt['orderby'] 		= 'post_title';
	$qt['order']		= 'ASC';
	
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
 * Removes the More button from the TinyMCE editor in the Docs context
 *
 * @package BuddyPress Docs
 * @since 1.0.3
 *
 * @param array $buttons The default TinyMCE buttons as set by WordPress
 * @return array $buttons The buttons with More removed
 */
function bp_docs_remove_tinymce_more_button( $buttons ) {
	if ( bp_docs_is_bp_docs_page() ) {
		$wp_more_key = array_search( 'wp_more', $buttons );
		if ( $wp_more_key ) {
			unset( $buttons[$wp_more_key] );
			$buttons = array_values( $buttons );
		}
	}
	
	return $buttons;
}
add_filter( 'mce_buttons', 'bp_docs_remove_tinymce_more_button' );

/**
 * Disables incompatible plugins in the bp_docs editor
 *
 * WP 3.1 introduced a fancy wplink plugin for TinyMCE, which allows for internal linking. It's not
 * playing nice with BuddyPress Docs, so I'm removing it for the moment and falling back on
 * TinyMCE's default link button.
 *
 * For BuddyPress Docs 1.0.9, I'm doing the same thing with the new distraction-free writing in WP
 * 3.2.
 *
 * @package BuddyPress Docs
 * @since 1.0.4
 *
 * @param array $initArray The default TinyMCE init array as set by WordPress
 * @return array $initArray The init array with the wplink plugin removed
 */
function bp_docs_remove_tinymce_plugins( $initArray ) {
	if ( bp_docs_is_bp_docs_page() ) {
		$plugins 	= explode( ',', $initArray['plugins'] );		

		// Internal linking
		$wplink_key = array_search( 'wplink', $plugins );
		if ( $wplink_key ) {
			unset( $plugins[$wplink_key] );
		}
		
		$plugins = array_values( $plugins );	
		$initArray['plugins'] = implode( ',', $plugins );
	}
	
	return $initArray;
}
add_filter( 'tiny_mce_before_init', 'bp_docs_remove_tinymce_plugins' );

?>