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
		
		// Display a doc's terms on its single doc page
		add_action( 'bp_docs_single_doc_meta', array( $this, 'show_terms' ) );
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
		$this->taxonomies = array( /* 'category', */ 'post_tag' );
	
		// Todo: make this fine-grained for tags and/or categories
		$args['taxonomies'] = array( 'post_tag' );
		
		//$args['taxonomies'] = array( 'category', 'post_tag' );
		
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
			
			if ( $tax_name == 'category' )
				$tax_name = 'post_category';
		
			// Separate out the terms
			$terms = !empty( $_POST[$tax_name] ) ? explode( ',', $_POST[$tax_name] ) : array();
			
			// Strip whitespace from the terms
			foreach ( $terms as $key => $term ) {
				$terms[$key] = trim( $term );
			}
			
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
			
			// Store these terms in the item term cache, to be used for tag clouds etc
			$this->cache_terms_for_item( $terms, $query->doc_id );
		}
	}
	
	/**
	 * Shows a doc's taxonomy terms
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */	
	function show_terms() {
	 	foreach( $this->taxonomies as $tax_name ) {
	 		// Todo: Make the tax name dynamic by adding a tag name to $this->taxonomies
	 		// Todo: Make these terms link to a group-specific tax director
	 		echo get_the_term_list( get_the_ID(), $tax_name, 'Tags: ', ', ', '' );
	 	}
	}
	
	/**
	 * Store taxonomy terms and their use count for a given item
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @param array $terms The terms submitted in the most recent save
	 * @param int $doc_id The unique id of the doc
	 */	
	function cache_terms_for_item( $terms = array(), $doc_id ) {
		$existing_terms = $this->get_item_terms();
		
		// First, make sure that each submitted term is recorded
		foreach ( $terms as $term ) {
			if ( empty( $existing_terms[$term] ) || ! is_array( $existing_terms[$term] ) )
				$existing_terms[$term] = array();
			
			if ( ! in_array( $doc_id, $existing_terms[$term] ) )
				$existing_terms[$term][] = $doc_id;
		}
		
		// Then, loop through to see if any existing terms have been deleted
		foreach ( $existing_terms as $existing_term => $docs ) {
			// If the existing term is not in the list of submitted terms...
			if ( ! in_array( $existing_term, $terms ) ) {
				// ... check to see whether the current doc is listed under that
				// term. If so, that indicates that the term has been removed from
				// the doc
				$key = array_search( $doc_id, $docs );
				if ( $key !== false ) {
					unset( $docs[$key] );
				}
			}
			
			// Reset the array keys for the term's docs
			$docs = array_values( $docs );
			
			if ( empty( $docs ) ) {
				// If there are no more docs associated with the term, we can remove
				// it from the array
				unset( $existing_terms[$existing_term] );
			} else {
				// Othewise, store the docs back in the existing terms array
				$existing_terms[$existing_term] = $docs;
			}
		}
		
		// Save the terms back to the item
		$this->save_item_terms( $existing_terms );
	}
	
	/**
	 * Gets the list of terms used by an item's docs
	 *
	 * This is a dummy function that allows specific item types to hook in their own methods
	 * for retrieving metadata (groups_update_groupmeta(), get_user_meta(), etc)
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @return array $terms The item's terms
	 */	
	function get_item_terms() {
		$terms = array();
		
		return apply_filters( 'bp_docs_taxonomy_get_item_terms', $terms );
	}
	
	/**
	 * Save list of terms used by an item's docs
	 *
	 * Just a dummy hook for the moment, for the integration modules to hook into
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @return array $terms The item's terms
	 */	
	function save_item_terms( $terms ) {
		do_action( 'bp_docs_taxonomy_save_item_terms', $terms );
	}
	 
	
}

?>