<?php

/**
 * Functionality related to the association of docs to specific users
 *
 * @package BuddyPress_Docs
 * @subpackage Users
 * @since 1.2
 */

class BP_Docs_Users_Integration {
	function __construct() {
		// Filter some properties of the query object
		add_filter( 'bp_docs_get_item_type',    array( &$this, 'get_item_type' ) );
		add_filter( 'bp_docs_get_current_view', array( &$this, 'get_current_view' ), 10, 2 );
		add_filter( 'bp_docs_this_doc_slug',    array( &$this, 'get_doc_slug' ) );
	
		// Add the approriate navigation item for single docs
		add_action( 'wp', array( &$this, 'setup_single_doc_subnav' ), 1 );
	
		// These functions are used to keep the user's Doc count up to date
		add_filter( 'bp_docs_doc_saved',   array( $this, 'update_doc_count' )  );
		add_filter( 'bp_docs_doc_deleted', array( $this, 'update_doc_count' ) );
	
		// Taxonomy helpers
		add_filter( 'bp_docs_taxonomy_get_item_terms', 	array( &$this, 'get_user_terms' ) );
		add_action( 'bp_docs_taxonomy_save_item_terms', array( &$this, 'save_user_terms' ) );
	}
	
	/**
	 * Check to see whether the query object's item type should be 'user'
	 *
	 * @package BuddyPress_Docs
	 * @since 1.2
	 *
	 * @param str $type
	 * @return str $type
	 */
	function get_item_type( $type ) {
		if ( bp_is_user() ) {
			$type = 'user';
		}

		return $type;
	}
	
	/**
	 * Sets up the current view when viewing a user page
	 *
	 * @since 1.2
	 */
	function get_current_view( $view, $item_type ) {
		if ( $item_type == 'user' ) {
			if ( !bp_current_action() ) {
				// An empty $bp->action_variables[0] means that you're looking at a list
				$view = 'list';
			} else if ( bp_is_current_action( BP_DOCS_CATEGORY_SLUG ) ) {
				// Category view
				$view = 'category';
			} else if ( bp_is_current_action( BP_DOCS_CREATE_SLUG ) ) {
				// Create new doc
				$view = 'create';
			} else if ( !bp_action_variable( 0 ) ) {
				// $bp->action_variables[1] is the slug for this doc. If there's no
				// further chunk, then we're attempting to view a single item
				$view = 'single';
			} else if ( bp_is_action_variable( BP_DOCS_EDIT_SLUG, 0 ) ) {
				// This is an edit page
				$view = 'edit';
			} else if ( bp_is_action_variable( BP_DOCS_DELETE_SLUG, 0 ) ) {
				// This is an delete request
				$view = 'delete';
			} else if ( bp_is_action_variable( BP_DOCS_HISTORY_SLUG, 0 ) ) {
				// This is a history request
				$view = 'history';
			}
		}
		
		return $view;
	}
	
	/**
	 * Set the doc slug when we are viewing a user doc
	 *
	 * @package BuddyPress_Docs
	 * @since 1.2
	 */
	function get_doc_slug( $slug ) {
		global $bp;
		
		if ( bp_is_user() ) {
			// Doc slug can't be my-docs or create
			if ( !in_array( bp_current_action(), array( 'my-docs', 'create' ) ) ) {
				$slug = bp_current_action();
			}
		}

		// Cache in the $bp global
		$bp->bp_docs->doc_slug = $slug;

		return $slug;
	}

	/**
	 * When looking at a single doc, this adds the appropriate subnav item.
	 *
	 * Other navigation items are added in BP_Docs_Component. We add this item here because it's
	 * not until later in the load order when we can be certain whether we're viewing a
	 * single Doc.
	 *
	 * @since 1.2
	 */
	function setup_single_doc_subnav() {
		global $bp;
		
		if ( bp_is_user() && !empty( $bp->bp_docs->current_view ) && in_array( $bp->bp_docs->current_view, array( 'single', 'edit', 'history', 'delete' ) ) ) {
			$doc = bp_docs_get_current_doc();
			
			if ( !empty( $doc ) ) {
				bp_core_new_subnav_item( array(
					'name'            => $doc->post_title,
					'slug'            => $doc->post_name,
					'parent_url'      => trailingslashit( bp_loggedin_user_domain() . bp_docs_get_slug() ),
					'parent_slug'     => bp_docs_get_slug(),
					'screen_function' => array( $bp->bp_docs, 'template_loader' ),
					'position'        => 30,
					'user_has_access' => true // todo
				) );
			}
		}
	}
	
	/**
	 * Update's a user's Doc count
	 *
	 * @since 1.2
	 */
	function update_doc_count() {
		bp_docs_update_doc_count( bp_loggedin_user_id(), 'user' );
	}
	
	/**
	 * Gets the list of terms used by a user's docs
	 *
	 * At the moment, this method (and the next one) assumes that you want the terms of the
	 * displayed user. At some point, that should be abstracted a bit.
	 *
	 * @package BuddyPress_Docs
	 * @subpackage Users
	 * @since 1.2
	 *
	 * @return array $terms
	 */
	function get_user_terms( $terms = array() ) {

		if ( bp_is_user() ) {
			$terms = get_user_meta( bp_displayed_user_id(), 'bp_docs_terms', true );

			if ( empty( $terms ) )
				$terms = array();
		}

		return apply_filters( 'bp_docs_taxonomy_get_user_terms', $terms );
	}

	/**
	 * Saves the list of terms used by a user's docs
	 *
	 * @package BuddyPress_Docs
	 * @subpackage Users
	 * @since 1.2
	 *
	 * @param array $terms The terms to be saved to usermeta
	 */
	function save_user_terms( $terms ) {
		if ( bp_is_user() ) {
			update_user_meta( bp_displayed_user_id(), 'bp_docs_terms', $terms );
		}
	}

}

?>