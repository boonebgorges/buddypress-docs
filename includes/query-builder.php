<?php

class BP_Docs_Query {
	var $item_type;
	var $item_id;
	var $item_name;
	var $item_slug;
	
	var $doc_id;
	var $doc_slug;
	
	var $current_view;
	
	var $term_id;
	var $item_type_term_id;
	
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
		$this->item_type = $this->get_item_type();
		$this->setup_item();
		$this->current_view = $this->get_current_view();
		
		// Get the item slug, if there is one available
		if ( $this->current_view == 'single' || $this->current_view == 'edit' )
			$this->doc_slug = $this->get_doc_slug();
		
	}

	/**
	 * Gets the item type of the item you're looking at - e.g 'group', 'user'.
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @return str $view The current item type
	 */
	function get_item_type() {
		global $bp;
		
		$type = '';
		
		return apply_filters( 'bp_docs_get_item_type', $type, $this );
	}
	
	/**
	 * Gets the item id of the item (eg group, user) associated with the page you're on.
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @return str $view The current item type
	 */
	function setup_item() {
		global $bp;
		
		if ( empty( $this->item_type ) )
			return false;
		
		$id = '';
		$name = '';
		$slug = '';
		
		switch ( $this->item_type ) {
			case 'group' :
				if ( !empty( $bp->groups->current_group->id ) ) {
					$id = $bp->groups->current_group->id;
					$name = $bp->groups->current_group->name;
					$slug = $bp->groups->current_group->slug;
				}
				break;
			case 'user' :
				if ( !empty( $bp->displayed_user->id ) ) {
					$id = $bp->displayed_user->id;
					$id = $bp->displayed_user->display_name;
					$id = $bp->displayed_user->userdata->user_nicename;
				}
				break;
		}
		
		// Todo: abstract into groups. Will be a pain
		$this->item_id = apply_filters( 'bp_docs_get_item_id', $id );
		$this->item_name = apply_filters( 'bp_docs_get_item_name', $name );
		$this->item_slug = apply_filters( 'bp_docs_get_item_slug', $slug );
	}
	
	/**
	 * Gets the doc slug as represented in the URL
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @return str $view The current doc slug
	 */
	function get_doc_slug() {
		global $bp;
		
		$slug = false;
		
		if ( $this->item_type == 'group' )
			$slug = $bp->action_variables[0];
		
		return apply_filters( 'bp_docs_this_doc_slug', $slug, $this );
	}
	
	/**
	 * Gets the id of the taxonomy term associated with the item
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @return str $view The current item type
	 */
	function setup_terms() {
		global $bp;
		
		// Get the term id for the item type
		$item_type_term = term_exists( $this->item_type, 'bp_docs_associated_item' );
		
		// If the item type term doesn't exist, then create it
		if ( empty( $item_type_term ) ) {
			// Filter this value to add your own item types, or to change slugs
			$defaults = apply_filters( 'bp_docs_item_type_term_values', array(
				'group' => array(
					'description' => __( 'Groups that have docs associated with them', 'bp-docs' ),
					'slug' => 'group'
				),
				'user' => array(
					'description' => __( 'Users that have docs associated with them', 'bp-docs' ),
					'slug' => 'user'
				)
			) );
		
			// Select the proper values from the defaults array
			$item_type_term_args = !empty( $defaults[$this->item_type] ) ? $defaults[$this->item_type] : false;
			
			// Create the item type term
			if ( !$item_type_term = wp_insert_term( __( 'Groups', 'buddypress' ), 'bp_docs_associated_item', $item_type_term_args ) )
				return false;	
		} 
		
		$this->item_type_term_id = apply_filters( 'bp_docs_get_item_type_term_id', $item_type_term['term_id'], $this );
			
		// Now, find the term associated with the item itself
		$item_term = term_exists( $this->item_id, 'bp_docs_associated_item', $this->item_type_term_id );
		
		// If the item term doesn't exist, then create it
		if ( empty( $item_term ) ) {
			// Set up the arguments for creating the term. Filter this to set your own
			$item_term_args = apply_filters( 'bp_docs_item_term_values', array(
				'description' => $this->item_name,
				'slug' => $this->item_slug,
				'parent' => $this->item_type_term_id
			) );
			
			// Create the item term
			if ( !$item_term = wp_insert_term( $this->item_id, 'bp_docs_associated_item', $item_term_args ) )
				return false;	
		}
		
		$this->term_id = apply_filters( 'bp_docs_get_item_term_id', $item_term['term_id'], $this );
	}

	/**
	 * Gets the current view, based on the page you're looking at.
	 *
	 * Filter 'bp_docs_get_current_view' to extend to different components.
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @param str $item_type Defaults to the object's item type
	 * @return str $view The current view. Core values: edit, single, list, category
	 */
	function get_current_view( $item_type = false ) {
		global $bp;
		
		$view = '';
		
		if ( !$item_type )
			$item_type = $this->item_type;
		
		$view = apply_filters( 'bp_docs_get_current_view', $view, $item_type );
	
		// Stuffing into the $bp global for later use. Cheating, I know.
		$bp->bp_docs->current_view = $view;
	
		return $view;
	}
	
	/**
	 * Builds the WP query
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 */
	function build_query() {
		// Only call this here to reduce database calls on other pages
		$this->setup_terms();
		
		// Get the tax term by id. Todo: Why can't I make this work less stupidly?
		$term = get_term_by( 'id', $this->term_id, 'bp_docs_associated_item' );
		
		$args = array(
			'post_type' => 'bp_doc',
			'bp_docs_associated_item' => $term->slug
		);
		
		return $args;
	}
	
	function load_template() {
		global $bp, $post;
		
		$template_path = BP_DOCS_INSTALL_PATH . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR;
		
		switch ( $this->current_view ) {
			case 'create' :
				// Todo: Make sure the user has permission to create
								
				/** 
				 * Load the template tags for the edit screen
				 */
				 require BP_DOCS_INSTALL_PATH . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'templatetags-edit.php';
				
				$template = $template_path . 'edit-doc.php';
				break;
			case 'list' :
				$args = $this->build_query();
				
				/* Todo: Get this into its own 'tree' view */
				/*
				$the_docs = get_posts( $args );
				$f = walk_page_tree($the_docs, 0, 0, array( 'walker' => new Walker_Page ) );
				print_r( $f );
				*/
				
				query_posts( $args );
				$template = $template_path . 'docs-loop.php';				
				break;
			case 'category' :
				// Check to make sure the category exists
				// If not, redirect back to list view with error
				// Otherwise, get args based on category ID
				// Then load the loop template
				break;
			case 'single' :
			case 'edit' :
				
				$args = $this->build_query();
				
				// Add a 'name' argument so that we only get the specific post
				$args['name'] = $this->doc_slug;
				
				query_posts( $args );
				
				// If this is the edit screen, we won't really be able to use a 
				// regular have_posts() loop in the template, so we'll stash the
				// post in the $bp global for the edit-specific template tags
				if ( $this->current_view == 'edit' ) {
					if ( have_posts() ) : while ( have_posts() ) : the_post();
						$bp->bp_docs->current_post = $post;
					endwhile; endif;
					
					/** 
					 * Load the template tags for the edit screen
					 */
					 require BP_DOCS_INSTALL_PATH . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'templatetags-edit.php';
				}

				if ( $this->current_view == 'single' )
					$template = $template_path . 'single-doc.php';	
				else
					$template = $template_path . 'edit-doc.php';
				
				// Todo: Maybe some sort of error if there is no edit permission?
	
				break;
		}
		
		if ( !empty( $template ) )
			include( apply_filters( 'bp_docs_template', $template, $this ) );
	}

	/**
	 * Saves a doc.
	 *
	 * This method handles saving for both new and existing docs. It detects the difference by
	 * looking for the presence of $this->doc_slug
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function save( $args = false ) {
		global $bp;
		
		// Get the required taxonomy items associated with the group. We only run this
		// on a save because it requires extra database hits.
		$this->setup_terms();
		
		// Set up the default value for the result message
		$results = array(
			'message' => __( 'Unknown error. Please try again.', 'bp-docs' ),
			'redirect' => 'create'
		);
		
		if ( empty( $_POST['doc']['title'] ) || empty( $_POST['doc']['content'] ) ) {
			// Both the title and the content fields are required
			$result['message'] = __( 'Both the title and the content fields are required.', 'bp-doc' );
			$result['redirect'] = $this->current_view;
		} else {
			// If both the title and content fields are filled in, we can proceed
			$defaults = array(
				'post_type' => 'bp_doc',
				'post_author' => bp_loggedin_user_id(),
				'post_title' => $_POST['doc']['title'],
				'post_content' => $_POST['doc']['content'],
				'post_status' => 'publish'
			);
			
			$r = wp_parse_args( $args, $defaults );
			
			if ( empty( $this->doc_slug ) ) {
				// This is a new doc
				if ( !$post_id = wp_insert_post( $r ) ) {
					$result['message'] = __( 'There was an error when creating the doc.', 'bp-doc' );
					$result['redirect'] = 'create';
				} else {
					// If the doc was saved successfully, place it in the proper tax
					wp_set_post_terms( $post_id, $this->term_id, 'bp_docs_associated_item' );
					
					$this->doc_id = $post_id;
					
					$the_doc = get_post( $this->doc_id );
					$this->doc_slug = $the_doc->post_name;
					
					$result['message'] = __( 'Doc successfully created!', 'bp-doc' );
					$result['redirect'] = 'single';
				}				
			} else {
				// This is an existing doc, so we need to get the post ID
				$the_doc_args = array(
					'name' => $this->doc_slug,
					'post_type' => 'bp_doc'
				);
				
				$the_docs = get_posts( $the_doc_args );			
				$this->doc_id = $the_docs[0]->ID;	
					
				$r['ID'] = $this->doc_id;
				
				if ( !wp_update_post( $r ) ) {
					$result['message'] = __( 'There was an error when saving the doc.', 'bp-doc' );
					$result['redirect'] = 'edit';
				} else {
					$result['message'] = __( 'Doc successfully saved!', 'bp-doc' );
					$result['redirect'] = 'single';
				}
			}
			
			// Provide a custom hook for plugins and optional components.
			// WP's default save_post isn't enough, because we need something that fires
			// only when we save from the front end (for things like taxonomies, which
			// the WP admin handles automatically)
			do_action( 'bp_docs_doc_saved', $this );
		}

		$message_type = $result['redirect'] == 'single' ? 'success' : 'error';
		bp_core_add_message( $result['message'], $message_type );
		
		// todo: abstract this out so I don't have to call group permalink here
		$redirect_url = bp_get_group_permalink( $bp->groups->current_group ) . $bp->bp_docs->slug . '/';
		
	
		if ( $result['redirect'] == 'single' ) {
			$redirect_url .= $this->doc_slug;
		} else if ( $result['redirect'] == 'edit' ) {
			$redirect_url .= $this->doc_slug . '/' . BP_DOCS_EDIT_SLUG;
		} else if ( $result['redirect'] == 'create' ) {
			$redirect_url .= BP_DOCS_CREATE_SLUG;
		}
		
		bp_core_redirect( $redirect_url );
	}	


}

?>