<?php

class BP_Docs_BP_Integration {	
	var $includes_url;
	var $slugstocheck;
	
	/**
	 * PHP 4 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function bp_docs_bp_integration() {
		$this->__construct();
	}

	/**
	 * PHP 5 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */	
	function __construct() {
		add_action( 'bp_init', 		array( $this, 'do_query' 		), 90 );
		
		add_action( 'bp_setup_globals', array( $this, 'setup_globals' 		) );
		
		if ( bp_is_active( 'groups' ) ) {
			require_once( BP_DOCS_INCLUDES_PATH . 'integration-groups.php' );
			$this->groups_integration = new BP_Docs_Groups_Integration;
		}
		
		add_action( 'wp', 		array( $this, 'catch_page_load' 	), 1 );
		
		add_action( 'bp_loaded', 	array( $this, 'set_includes_url' 	) );
		add_action( 'init', 		array( $this, 'enqueue_scripts' 	) );
		add_action( 'wp_print_styles', 	array( $this, 'enqueue_styles' 		) );
	}
	
	/**
	 * Loads the Docs query.
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function do_query() {
		$this->query = new BP_Docs_Query;
	}
	
	/**
	 * Stores some handy information in the $bp global
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */	
	function setup_globals() {
		global $bp;
		
		$bp->bp_docs->format_notification_function 	= 'bp_docs_format_notifications';
		$bp->bp_docs->slug 				= BP_DOCS_SLUG;

		// This info is loaded here because it needs to happen after BP core globals are
		// set up
		$this->slugstocheck 	= bp_action_variables() ? bp_action_variables() : array();
		$this->slugstocheck[] 	= bp_current_component();
		$this->slugstocheck[] 	= bp_current_action();

		// Todo: You only need this if you need top level access: example.com/docs
		/* Register this in the active components array */
		//$bp->active_components[ $bp->wiki->slug ] = $bp->wiki->id;
		
	}
	
		
	/**
	 * Catches page loads, determines what to do, and sends users on their merry way
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function catch_page_load() {
		global $bp;
		
		if ( !empty( $_POST['doc-edit-submit'] ) ) {
			$this_doc = new BP_Docs_Query;
			$this_doc->save();
		}
		
		if ( !empty( $_POST['docs-filter-submit'] ) ) {
			$this->handle_filters();
		}
		
		// If this is the edit screen, ensure that the user can edit the
		// doc before querying, and redirect if necessary
		if ( !empty( $bp->bp_docs->current_view ) && 'edit' == $bp->bp_docs->current_view && !bp_docs_current_user_can( 'edit' ) ) {
			bp_core_add_message( __( 'You do not have permission to edit the document.', 'bp-docs' ), 'error' );
			
			$group_permalink = bp_get_group_permalink( $bp->groups->current_group );
			$doc_slug = $bp->bp_docs->doc_slug;
			
			// Redirect back to the non-edit view of this document
			bp_core_redirect( $group_permalink . $bp->bp_docs->slug . '/' . $doc_slug );
		}
		
		// Todo: get this into a proper method
		if ( $bp->bp_docs->current_view == 'delete' ) {
			check_admin_referer( 'bp_docs_delete' );
			
			if ( bp_docs_current_user_can( 'manage' ) ) {
			
				$the_doc_args = array(
					'name' => $bp->action_variables[0],
					'post_type' => 'bp_doc'
				);
				
				$the_docs = get_posts( $the_doc_args );			
				$doc_id = $the_docs[0]->ID;	
				
				do_action( 'bp_docs_before_doc_delete', $doc_id );
				
				wp_delete_post( $doc_id );
								
				bp_core_add_message( __( 'Doc successfully deleted!', 'bp-docs' ) );
			} else {
				bp_core_add_message( __( 'You do not have permission to delete that doc.', 'bp-docs' ), 'error' );
			}
			
			// todo: abstract this out so I don't have to call group permalink here
			$redirect_url = bp_get_group_permalink( $bp->groups->current_group ) . $bp->bp_docs->slug . '/';
			bp_core_redirect( $redirect_url );					
		}
	}
	
	/**
	 * Handles doc filters from a form post and translates to $_GET arguments before redirect
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function handle_filters() {
		$redirect_url = apply_filters( 'bp_docs_handle_filters', bp_docs_get_item_docs_link() );

		bp_core_redirect( $redirect_url );		
	}
	
	/**
	 * Sets the includes URL for use when loading scripts and styles
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function set_includes_url() {
		$this->includes_url = plugins_url() . '/buddypress-docs/includes/';
	}
	
	/**
	 * Loads JavaScript
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */	
	function enqueue_scripts() {
		wp_register_script( 'bp-docs-js', plugins_url( 'buddypress-docs/includes/js/bp-docs.js' ), array( 'jquery' ) );
		
		// Only load our JS on the right sorts of pages. Generous to account for
		// different item types
		if ( in_array( BP_DOCS_SLUG, $this->slugstocheck ) ) {
			wp_enqueue_script( 'bp-docs-js' );
			wp_localize_script( 'bp-docs-js', 'bp_docs', array(
				'addfilters'	=> __( 'Add Filters', 'bp-docs' ),
				'modifyfilters'	=> __( 'Modify Filters', 'bp-docs' )
			) );
		}
		
		// This is for edit/create scripts
		if ( !empty( $this->query->current_view ) && ( 'edit' == $this->query->current_view || 'create' == $this->query->current_view ) ) {
			require_once( ABSPATH . '/wp-admin/includes/post.php' );
			wp_enqueue_script( 'common' );
			wp_enqueue_script( 'jquery-color' );
			wp_enqueue_script( 'editor' );
			if (function_exists('add_thickbox')) add_thickbox();
			//wp_print_scripts('media-upload');
			wp_admin_css();
			wp_enqueue_script( 'utils' );
		}
	}
	
	/**
	 * Loads styles
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function enqueue_styles() {
		global $bp;
		
		// Load the main CSS only on the proper pages
		if ( in_array( BP_DOCS_SLUG, $this->slugstocheck ) ) {
			wp_enqueue_style( 'bp-docs-css', $this->includes_url . 'css' . DIRECTORY_SEPARATOR . 'bp-docs.css' );
		}
		
		if ( !empty( $this->query->current_view ) && ( 'edit' == $this->query->current_view || 'create' == $this->query->current_view ) ) {
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_style( 'bpd-edit-css', $this->includes_url . 'css' . DIRECTORY_SEPARATOR . 'edit.css' );
		}
	}
}

?>