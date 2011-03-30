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
 * @since 1.0
 */
function bp_docs_edit_doc_title() {
	echo bp_docs_get_edit_doc_title();
}
	/**
	 * Returns the title of the doc currently being edited, when it exists
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
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
 * @since 1.0
 */
function bp_docs_edit_doc_content() {
	echo bp_docs_get_edit_doc_content();
}
	/**
	 * Returns the content of the doc currently being edited, when it exists
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
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

?>