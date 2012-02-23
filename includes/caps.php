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
 * Make sure caps are set
 */
function bp_docs_ensure_caps() {
	global $wp_roles;
	
	if ( is_super_admin() && !isset( $wp_roles->roles['administrator']['capabilities']['read_bp_doc'] ) ) {
		bp_docs_add_caps();
	}
}
add_action( 'admin_init', 'bp_docs_ensure_caps' );

/**
 * Adds capabilities to WordPress user roles.
 *
 * This is called on plugin activation.
 *
 * @since 1.2
 *
 * @uses get_role() To get the administrator, default and moderator roles
 * @uses WP_Role::add_cap() To add various capabilities
 * @uses do_action() Calls 'bp_add_caps'
 */
function bp_docs_add_caps() {
	global $wp_roles;

	// Load roles if not set
	if ( ! isset( $wp_roles ) )
		$wp_roles = new WP_Roles();

	// Loop through available roles
	foreach( $wp_roles->roles as $role => $details ) {

		// Load this role
		$this_role = get_role( $role );

		// Loop through caps for this role and remove them
		foreach ( bp_docs_get_caps_for_role( $role ) as $cap ) {
			$this_role->add_cap( $cap );
		}
	}
	
	do_action( 'bp_docs_add_caps' );
}

/**
 * Returns an array of capabilities based on the role that is being requested.
 *
 * @since BuddyPress (1.6)
 *
 * @param string $role Optional. Defaults to The role to load caps for
 * @uses apply_filters() Allow return value to be filtered
 *
 * @return array Capabilities for $role
 */
function bp_docs_get_caps_for_role( $role = '' ) {
	$caps = array(
		'read_bp_doc',
		'edit_bp_doc',
		'view_bp_doc_history'
	);
	return apply_filters( 'bp_docs_get_caps_for_role', $caps, $role );
}


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
	
	$pt = bp_docs_get_post_type_name();
	
	if ( isset( $wp_post_types[$pt] ) && in_array( $cap, (array)$wp_post_types[$pt]->cap ) ) {
		// Set up some data we'll need for these permission checks
		$doc  	      = bp_docs_get_doc_for_caps( $args );
		
		// Nothing to check
		if ( empty( $doc ) ) {
			return $caps;
		}
		
		$post_type    = get_post_type_object( $doc->post_type );
		$doc_settings = get_post_meta( $doc->ID, 'bp_docs_settings', true );
		
		// Reset all caps. We bake from scratch
		$caps = array();
	}
	
	switch ( $cap ) {
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
add_filter( 'map_meta_cap', 'bp_docs_map_meta_caps', 10, 4 );

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
	if ( isset( $args[0] ) ) {
		$doc_id = $args[0];
	} else if ( isset( $post->ID ) ) {
		// Fall back on current Doc
		$doc_id = $post->ID;
	}
	
	$doc = get_post( $doc_id );
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
