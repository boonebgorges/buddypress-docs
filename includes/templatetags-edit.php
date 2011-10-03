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
			$title = isset( $_GET['create_title'] ) ? urldecode( $_GET['create_title'] ) : '';
		} else {
			$title = $bp->bp_docs->current_post->post_title;
		}

		return apply_filters( 'bp_docs_get_edit_doc_title', $title );
	}

/**
 * Echoes the output of bp_docs_get_edit_doc_slug()
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 */
function bp_docs_edit_doc_slug() {
	echo bp_docs_get_edit_doc_slug();
}
	/**
	 * Returns the slug of the doc currently being edited, when it exists
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 *
	 * @return string Doc slug
	 */
	function bp_docs_get_edit_doc_slug() {
		global $bp;

		if ( empty( $bp->bp_docs->current_post ) || empty( $bp->bp_docs->current_post->post_name ) ) {
			$slug = '';
		} else {
			$slug = $bp->bp_docs->current_post->post_name;
		}

		return apply_filters( 'bp_docs_get_edit_doc_slug', $slug );
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
 * Modifies TinyMCE init parameters to include and exclude plugins
 *
 * WP 3.1 introduced a fancy wplink plugin for TinyMCE, which allows for internal linking. It's not
 * playing nice with BuddyPress Docs, so I'm removing it for the moment and falling back on
 * TinyMCE's default link button.
 *
 * This function also adds the
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

		// Remove internal linking
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

/**
 * Adds BuddyPress Docs-specific TinyMCE plugins
 *
 * Includes:
 *   - table
 *   - tabindent
 *
 * @package BuddyPress Docs
 * @since 1.1.5
 *
 * @param array $plugins TinyMCE external plugins registered in WP
 * @return array $plugins Plugin list, with BP Docs plugins added
 */
function bp_docs_add_external_tinymce_plugins( $plugins ) {
	if ( bp_docs_is_bp_docs_page() ) {
		$plugins['table'] 	= WP_PLUGIN_URL . '/buddypress-docs/lib/js/tinymce/plugins/table/editor_plugin.js';
		$plugins['tabindent'] 	= WP_PLUGIN_URL . '/buddypress-docs/lib/js/tinymce/plugins/tabindent/editor_plugin.js';
		$plugins['print'] 	= WP_PLUGIN_URL . '/buddypress-docs/lib/js/tinymce/plugins/print/editor_plugin.js';
	}

	return $plugins;
}
add_filter( 'mce_external_plugins', 'bp_docs_add_external_tinymce_plugins' );

/**
 * Adds BuddyPress Docs-specific TinyMCE plugin buttons to row 1 of the editor
 *
 * Does some funny business to get things in a nice order
 *
 * Includes:
 *   - tabindent
 *   - print
 *
 * @package BuddyPress Docs
 * @since 1.1.5
 *
 * @param array $buttons TinyMCE buttons
 * @return array $buttons Button list, with BP Docs buttons added
 */
function bp_docs_add_external_tinymce_buttons_row1( $buttons ) {
	$justify_right_key = array_search( 'justifyright', $buttons );

	if ( $justify_right_key !== 0 ) {
		// Shift the buttons one to the right and remove from original array
		$count = count( $buttons );
		$new_buttons = array();
		for ( $i = $justify_right_key + 1; $i < $count; $i++ ) {
			$new_buttons[] = $buttons[$i];
			unset( $buttons[$i] );
		}

		// Put the three pieces together
		$buttons = array_merge( $buttons, array( 'tabindent' ), $new_buttons );
	}

	// Add the Print button just before the kitchen sink
	$ks = array_pop( $buttons );
	$buttons = array_merge( $buttons, array( 'print' ), array( $ks ) );

	return $buttons;
}
add_filter( 'mce_buttons', 'bp_docs_add_external_tinymce_buttons_row1' );

/**
 * Adds BuddyPress Docs-specific TinyMCE plugin buttons to row 2 of the editor
 *
 * Includes:
 *   - tablecontrols
 *
 * @package BuddyPress Docs
 * @since 1.1.5
 *
 * @param array $buttons TinyMCE buttons
 * @return array $buttons Button list, with BP Docs buttons added
 */
function bp_docs_add_external_tinymce_buttons_row2( $buttons ) {
	$buttons[] = 'tablecontrols';

	return $buttons;
}
add_filter( 'mce_buttons_2', 'bp_docs_add_external_tinymce_buttons_row2' );

?>