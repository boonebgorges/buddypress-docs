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
 * @param mixed $args Arguments
 * @uses get_post() To get the post
 * @uses get_post_type_object() To get the post type object
 * @uses apply_filters() Calls 'bp_docs_map_meta_caps' with caps, cap, user id and
 *                        args
 * @return array Actual capabilities for meta capability
 */
function bp_docs_map_meta_caps( $caps, $cap, $user_id, $args ) {
	global $post;
	
	switch ( $cap ) {
		case 'read_bp_doc' :
			// Super admin can read anything
			if ( !is_super_admin() ) {
				if ( $doc = bp_docs_get_doc_for_caps( $args ) ) {
					//var_dump( $doc );
				}
			}
			
			//var_dump( $args );
			//var_dump( get_the_author_meta( 'ID' ) );
			$caps = array( 'do_not_allow' );
			//var_dump( $caps );
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
