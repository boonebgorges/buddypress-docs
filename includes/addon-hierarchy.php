<?php

/**
 * This file contains the functions used to enable hierarchical docs.
 * Separated into this file so that the feature can be turned off.
 *
 * @package BuddyPress Docs
 */
 
class BP_Docs_Hierarchy {
	var $parent;
	var $children;
	
	/**
	 * PHP 4 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function bp_docs_hierarchy() {
		$this->__construct();
	}

	/**
	 * PHP 5 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */	
	function __construct() {
		// Make sure that the bp_docs post type supports our post taxonomies
		add_filter( 'bp_docs_post_type_args', array( $this, 'register_with_post_type' ) );
	
		// Hook into post saves to save any taxonomy terms. 
		add_action( 'bp_docs_doc_saved', array( $this, 'save_post' ) );
		
		// Display a doc's parent on its single doc page
		add_action( 'bp_docs_single_doc_meta', array( $this, 'show_parent' ) );
	}
	
	/**
	 * Registers the post taxonomies with the bp_docs post type
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 *
	 * @param array The $bp_docs_post_type_args array created in BP_Docs::register_post_type()
	 * @return array $args The modified parameters
	 */	
	function register_with_post_type( $args ) {
		$args['hierarchical'] = true;
		
		return $args;		
	}
	
	/**
	 * Saves post parent to a doc when saved from the front end
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 *
	 * @param object $query The query object created by BP_Docs_Query
	 * @return int $post_id Returns the doc's post_id on success
	 */	
	function save_post( $query ) {
		if ( ! empty( $_POST['parent_id'] ) ) {
			$args = array(
				'ID' => $query->doc_id,
				'post_parent' => (int) $_POST['parent_id']
			);
			
			if ( $post_id = wp_update_post( $args ) )
				return $post_id;
			else
				return false;
		}
	}
	
	/**
	 * Display a link to the doc's parent
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 *
	 * @param object $query The query object created by BP_Docs_Query
	 * @return int $post_id Returns the doc's post_id on success
	 */	
	 function show_parent() {
	 	global $post;
	 	
	 	$html = '';
	 	$parent = false;
	 	
	 	if ( ! empty( $post->post_parent ) ) {			
			$parent = get_post( $post->post_parent );
			if ( !empty( $parent->ID ) ) {
				$parent_url = bp_docs_get_group_doc_permalink( $parent->ID );
				$parent_title = $post->post_title;
				
				$html = "<p>" . __( 'Parent: ', 'bp-docs' ) . "<a href=\"$parent_url\" title=\"$parent_title\">$parent_title</a></p>";
			}
	 	}
	 	
	 	echo apply_filters( 'bp_docs_hierarchy_show_parent', $html, $parent );
	 }
}

?>