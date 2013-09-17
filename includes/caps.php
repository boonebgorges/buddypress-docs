<?php

/**
 * BuddyPress Docs capabilities and roles
 *
 * Inspired by bbPress 2.0
 *
 * @package BuddyPress_Docs
 * @subpackage Caps
 * @since 1.2
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Map our caps to WP's
 *
 * @since 1.2
 *
 * @param array $caps Capabilities for meta capability
 * @param string $cap Capability name
 * @param int $user_id User id
 * @param mixed $args Arguments passed to map_meta_cap filter
 * @uses get_post() To get the post
 * @uses get_post_type_object() To get the post type object
 * @uses apply_filters() Calls 'bp_docs_map_meta_caps' with caps, cap, user id and
 *                        args
 * @return array Actual capabilities for meta capability
 */
function bp_docs_map_meta_caps( $caps, $cap, $user_id, $args ) {
	global $post, $wp_post_types;

	// No need to continue if BuddyPress Docs hasn't been initialized
	$pt = bp_docs_get_post_type_name();
	if ( empty( $pt ) ) {
		return $caps;
	}

	// Set up some data we'll need for these permission checks
	$doc = bp_docs_get_doc_for_caps( $args );

	// Nothing to check
	if ( empty( $doc ) ) {
		return $caps;
	}

	$post_type    = get_post_type_object( $doc->post_type );
	$doc_settings = bp_docs_get_doc_settings( $doc_id );

	// Reset all caps. We bake from scratch
	$caps = array();

	switch ( $cap ) {
		case 'create_bp_doc' :
			// @todo This will probably need more thought
			if ( ! is_user_logged_in() ) {
				$caps[] = 'do_not_allow';
			} else {
				// @todo - need to detect group membership
				$caps[] = $cap;
			}

			break;

		case 'read_bp_doc' :
			$caps[] = 'exist'; // anyone can read Docs by default
			break;

		case 'edit_bp_doc' :
			if ( $user_id == $doc->post_author ) {
				$caps[] = $cap;
			} else if ( isset( $doc_settings['edit'] ) ) {
				var_dump( $doc_settings['edit'] );
			} else if ( bp_docs_user_has_custom_access( $user_id, $doc_settings, 'edit' ) ) {
				$caps[] = $cap;
			} else {
				$caps[] = 'do_not_allow';
			}

			break;

		case 'view_bp_doc_history' :
			if ( $user_id == $doc->post_author ) {
				$caps[] = $cap;
			} else if ( bp_docs_user_has_custom_access( $user_id, $doc_settings, 'view_history' ) ) {
				$caps[] = $cap;
			} else {
				$caps[] = 'do_not_allow';
			}

			break;

	}

	return apply_filters( 'bp_docs_map_meta_caps', $caps, $cap, $user_id, $args );
}
//add_filter( 'map_meta_cap', 'bp_docs_map_meta_caps', 10, 4 );

/**
 * Load up the doc to check against for meta cap mapping
 *
 * @since 1.2
 *
 * @param array $args The $args argument passed by the map_meta_cap filter. May be empty
 * @return obj $doc
 */
function bp_docs_get_doc_for_caps( $args = array() ) {
	global $post;

	$doc_id = 0;
	$doc = NULL;
	if ( isset( $args[0] ) ) {
		$doc_id = $args[0];
		$doc = get_post( $doc_id );
	} else if ( isset( $post->ID ) ) {
		$doc = $post;
	}

	return $doc;
}

/**
 * Utility function for checking whether a doc's permission settings are set to Custom, and that
 * a given user has access to the doc for that particular action.
 *
 * @since 1.2
 *
 * @param
 */
function bp_docs_user_has_custom_access( $user_id, $doc_settings, $key ) {
	// Default to true, so that if it's not set to 'custom', you pass through
	$has_access = true;

	if ( isset( $doc_settings[$key] ) && 'custom' == $doc_settings[$key] && is_array( $doc_settings[$key] ) ) {
		$has_access = in_array( $user_id, $doc_settings[$key] );
	}

	return $has_access;
}
