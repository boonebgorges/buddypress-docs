<?php

/**
 * This file contains the functions used to enable tags and categories for docs.
 * Separated into this file so that the feature can be turned off.
 *
 * @package BuddyPress Docs
 */
 
class BP_Docs_Taxonomy {
	var $taxonomies;
	
	/**
	 * PHP 4 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function bp_docs_query() {
		$this->__construct();
	}

	/**
	 * PHP 5 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */	
	function __construct() {
		// Make sure that the bp_docs post type supports our post taxonomies
		add_filter( 'bp_docs_post_type_args', array( $this, 'register_with_post_type' ) );
	
		// Hook into post saves to save any taxonomy terms. 
		add_action( 'bp_docs_doc_saved', array( $this, 'save_post' ) );
	}
	
	/**
	 * Registers the post taxonomies with the bp_docs post type
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @param array The $bp_docs_post_type_args array created in BP_Docs::register_post_type()
	 * @return array $args The modified parameters
	 */	
	function register_with_post_type( $args ) {
		$this->taxonomies = array( 'category', 'post_tag' );
	
		// Todo: make this fine-grained for tags and/or categories
		$args['taxonomies'] = array( 'category', 'post_tag' );
		
		return $args;		
	}
	
	/**
	 * Saves post taxonomy terms to a doc when saved from the front end
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @param object $query The query object created by BP_Docs_Query
	 * @return int $post_id Returns the doc's post_id on success
	 */	
	function save_post( $query ) {
		foreach( $this->taxonomies as $tax_name ) {
			// Separate out the terms
			$terms = !empty( $_POST['doc'][$tax_name] ) ? explode( ',', $_POST['doc'][$tax_name] ) : array();
			
			$tax = get_taxonomy( $tax_name );
			
			// Hierarchical terms like categories have to be handled differently, with
			// term IDs rather than the term names themselves
			if ( !empty( $tax->hierarchical ) ) {
				$term_ids = array();
				foreach( $terms as $term ) {
					$parent = 0;
					$term_ids[] = term_exists( $term, $tax_id, $parent );
				}
			}
			
			wp_set_post_terms( $query->doc_id, $terms, $tax_name );
		}
	}
}

?>