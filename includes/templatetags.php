<?php

/**
 * Gets the current view, based on the page you're looking at.
 *
 * Filter 'bp_docs_get_current_view' to extend to different components.
 *
 * @package BuddyPress Docs
 * @since 1.0
 *
 * @return str $view The current view. Core values: edit, single, list, category
 */
function bp_docs_get_current_view() {
	global $bp;
	
	// When possible, get the current view from the $bp global
	if ( !empty( $bp->bp_docs->current_view ) ) {
		$view = $bp->bp_docs->current_view;
	} else {
		$view = '';
		
		// First, test to see whether this is a group docs page
		if ( $bp->current_component == $bp->groups->slug && $bp->current_action == $bp->bp_docs->slug ) {
			if ( empty( $bp->action_variables[0] ) ) {
				// An empty $bp->action_variables[0] means that you're looking at a list
				$view = 'list';
			} else if ( $bp->action_variables[0] == BP_DOCS_CATEGORY_SLUG ) {
				// Category view
				$view = 'category';
			} else if ( empty( $bp->action_variables[1] ) ) {
				// $bp->action_variables[1] is the slug for this doc. If there's no
				// further chunk, then we're attempting to view a single item
				$view = 'single';
			} else if ( !empty( $bp->action_variables[1] ) && $bp->action_variables[1] == BP_DOCS_EDIT_SLUG ) {
				// This is an edit page
				$view = 'edit';
			}
		}
		
		// Stash in $bp for quicker lookup on the same page load
		$bp->bp_docs->current_view = $view;
	}
	
	return apply_filters( 'bp_docs_get_current_view', $view );
}

?>