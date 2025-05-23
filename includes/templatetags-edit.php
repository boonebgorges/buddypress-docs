<?php

/**
 * This file contains the template tags used on the Docs edit and create screens. They are
 * separated out so that they don't need to be loaded all the time.
 *
 * @package BuddyPressDocs
 */

/**
 * Echoes the output of bp_docs_get_edit_doc_title()
 *
 * @since 1.0-beta
 */
function bp_docs_edit_doc_title() {
	echo esc_html( bp_docs_get_edit_doc_title() );
}
	/**
	 * Returns the title of the doc currently being edited, when it exists
	 *
	 * @since 1.0-beta
	 *
	 * @return string Doc title
	 */
	function bp_docs_get_edit_doc_title() {
		// If a previously-submitted value is found, prefer it. It
		// means that there was a failed submission just prior to this
		if ( isset( buddypress()->bp_docs->submitted_data->doc->title ) && buddypress()->bp_docs->submitted_data->doc->title ) {
			$title = buddypress()->bp_docs->submitted_data->doc->title;
		} else {
			$title = bp_docs_is_existing_doc() ? get_the_title() : '';
		}

		// If no title has been found yet, check to see whether one has
		// been submitted using create_title URL param (from the
		// [[wikitext]] linking functionality)
		if ( empty( $title ) && ! empty( $_GET['create_title'] ) ) {
			$title = urldecode( $_GET['create_title'] );
		}

		return apply_filters( 'bp_docs_get_edit_doc_title', esc_attr( $title ) );
	}

/**
 * Echoes the output of bp_docs_get_edit_doc_slug()
 *
 * @since 1.0-beta
 */
function bp_docs_edit_doc_slug() {
	echo esc_attr( bp_docs_get_edit_doc_slug() );
}
	/**
	 * Returns the slug of the doc currently being edited, when it exists
	 *
	 * @since 1.0-beta
	 *
	 * @return string Doc slug
	 */
	function bp_docs_get_edit_doc_slug() {
		global $post;

		// If a previously-submitted value is found, prefer it. It
		// means that there was a failed submission just prior to this
		if ( isset( buddypress()->bp_docs->submitted_data->doc->permalink ) && buddypress()->bp_docs->submitted_data->doc->permalink ) {
			$slug = buddypress()->bp_docs->submitted_data->doc->permalink;
		} else {
			$slug = isset( $post->post_name ) ? $post->post_name : '';
		}

		return apply_filters( 'bp_docs_get_edit_doc_slug', esc_attr( $slug ) );
	}

/**
 * Echoes the output of bp_docs_get_edit_doc_content()
 *
 * @since 1.0-beta
 */
function bp_docs_edit_doc_content() {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo bp_docs_get_edit_doc_content();
}
	/**
	 * Returns the content of the doc currently being edited, when it exists
	 *
	 * @since 1.0-beta
	 *
	 * @return string Doc content
	 */
	function bp_docs_get_edit_doc_content() {
		global $post;

		if ( isset( buddypress()->bp_docs->submitted_data->doc_content ) && buddypress()->bp_docs->submitted_data->doc_content ) {
			$content = buddypress()->bp_docs->submitted_data->doc_content;
		} else {
			$content = bp_docs_is_existing_doc() ? $post->post_content : '';
		}

		return apply_filters( 'bp_docs_get_edit_doc_content', $content );
	}

/**
 * Echoes a nonce field for specific doc editing.
 *
 * @since 2.2.5
 *
 * @param int $doc_id The ID of the doc being edited. If not set, will use the current doc.
 *                    We make this optional because old templates may not pass it.
 * @return void
 */
function bp_docs_edit_doc_nonce( $doc_id = null ) {
	if ( ! $doc_id ) {
		$doc = bp_docs_get_current_doc();
		if ( $doc ) {
			$doc_id = $doc->ID;
		}
	}

	if ( ! $doc_id ) {
		return;
	}

	wp_nonce_field( 'bp_docs_edit_' . (string) $doc_id, 'bp_docs_edit_nonce' );
}
add_action( 'bp_docs_before_doc_edit_content', 'bp_docs_edit_doc_nonce' );

/**
 * Get a list of an item's docs for display in the parent dropdown
 *
 * @since 1.0-beta
 */
function bp_docs_edit_parent_dropdown() {
	$bp = buddypress();

	$current_doc = get_queried_object();
	$exclude = $parent = false;

	// If this is a failed submission, use the value from the POST cookie
	if ( ! empty( $bp->bp_docs->submitted_data->parent_id ) ) {
		$parent = intval( $bp->bp_docs->submitted_data->parent_id );
	} else if ( isset( $current_doc->post_type ) && $bp->bp_docs->post_type_name === $current_doc->post_type ) {
		$exclude = $current_doc->ID;
		$parent = $current_doc->post_parent;
	}

	$include = array( 0 );

	$query_args = apply_filters( 'bp_docs_parent_dropdown_query_args', array(
		'doc_slug' => false,
		'posts_per_page' => -1,
	) );
	$doc_query_builder = new BP_Docs_Query( $query_args );
	$doc_query = $doc_query_builder->get_wp_query();

	if ( $doc_query->have_posts() ) {
		while ( $doc_query->have_posts() ) {
			$doc_query->the_post();
			if ( ! $exclude || $exclude !== get_the_ID() ) {
				$include[] = get_the_ID();
			}
		}
	}

	$doc_query->reset_postdata();

	$pages = wp_dropdown_pages( array(
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		'post_type'        => $bp->bp_docs->post_type_name,
		'include'          => $include,
		'selected'         => $parent,
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		'name'             => 'parent_id',
		'show_option_none' => esc_html__( '(no parent)', 'buddypress-docs' ),
		'sort_column'      => 'menu_order, post_title',
		'echo'             => 0 )
	);

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $pages;
}

/**
 * Removes the More button from the TinyMCE editor in the Docs context
 *
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
 * Hook our idle function to the TinyMCE.onInit event
 *
 * @since 1.1.20
 */
function bp_docs_add_idle_function_to_tinymce( $initArray, $editor_id ) {
	// We only apply the init to the visual post editor for BP Docs.
	if ( 'doc_content' === $editor_id ) {
		$initArray['init_instance_callback'] = 'bp_docs_tiny_mce_init';
	}

	return $initArray;
}
add_filter( 'tiny_mce_before_init', 'bp_docs_add_idle_function_to_tinymce', 10, 2 );

/**
 * Adds BuddyPress Docs-specific TinyMCE plugins
 *
 * Includes:
 *   - table
 *   - tabindent
 *
 * @since 1.1.5
 *
 * @param array $plugins TinyMCE external plugins registered in WP
 * @return array $plugins Plugin list, with BP Docs plugins added
 */
function bp_docs_add_external_tinymce_plugins( $plugins ) {
	if ( bp_docs_is_bp_docs_page() ) {
		$plugins['table']     = WP_PLUGIN_URL . '/'. BP_DOCS_PLUGIN_SLUG . '/lib/js/tinymce/plugins/table/plugin.min.js';
		$plugins['tabindent'] = WP_PLUGIN_URL . '/'. BP_DOCS_PLUGIN_SLUG . '/lib/js/tinymce/plugins/tabindent/editor_plugin.js';
		$plugins['print']     = WP_PLUGIN_URL . '/'. BP_DOCS_PLUGIN_SLUG . '/lib/js/tinymce/plugins/print/plugin.min.js';
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
 * @since 1.1.5
 *
 * @param array $buttons TinyMCE buttons
 * @return array $buttons Button list, with BP Docs buttons added
 */
function bp_docs_add_external_tinymce_buttons_row1( $buttons ) {
	// TinyMCE 4.0+
	$justify_right_key = array_search( 'alignright', $buttons );

	// 3.0
	if ( false === $justify_right_key ) {
		$justify_right_key = array_search( 'justifyright', $buttons );
	}

	if ( $justify_right_key !== 0 ) {
		// Shift the buttons one to the right and remove from original array
		$new_buttons = array();
		foreach ( $buttons as $bkey => $bvalue ) {
			if ( $bkey <= $justify_right_key ) {
				continue;
			}

			$new_buttons[] = $bvalue;
			unset( $buttons[ $bkey ] );
		}

		// Put the three pieces together
		$buttons = array_merge( $buttons, array( 'tabindent' ), $new_buttons );
	}

	// Add the Print button just before the kitchen sink
	$ks = array_pop( $buttons );
	$buttons = array_merge( $buttons, array( 'print' ), array( $ks ) );

	// Fullscreen is kinda busted here, so remove it
	$fs = array_search( 'fullscreen', $buttons );
	if ( false !== $fs ) {
		unset( $buttons[ $fs ] );
	}

	// Reset indexes
	$buttons = array_values( $buttons );

	return $buttons;
}
add_filter( 'mce_buttons', 'bp_docs_add_external_tinymce_buttons_row1' );

/**
 * Adds BuddyPress Docs-specific TinyMCE plugin buttons to row 3 of the editor
 *
 * Includes:
 *   - tablecontrols
 *
 * @since 1.1.5
 *
 * @param array $buttons TinyMCE buttons
 * @return array $buttons Button list, with BP Docs buttons added
 */
function bp_docs_add_external_tinymce_buttons_row3( $buttons ) {
	$buttons[] = 'table';

	return $buttons;
}
add_filter( 'mce_buttons_3', 'bp_docs_add_external_tinymce_buttons_row3' );

?>
