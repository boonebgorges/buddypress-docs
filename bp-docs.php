<?php

class BP_Docs {
	/**
	 * PHP 4 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function bp_docs() {
		$this->construct();
	}

	/**
	 * PHP 5 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */	
	function __construct() {
		
		// Load predefined constants first thing
		add_action( 'bp_docs_init', array( $this, 'load_constants' ), 2 );
		
		// Hooks into the 'init' action to register our WP custom post type and tax
		add_action( 'init', array( $this, 'register_post_type' ) );
		
		// Includes necessary files
		add_action( 'bp_docs_init', array( $this, 'includes' ), 6 );		

		// Let plugins know that BP Docs has started loading
		$this->init();

		// Let other plugins know that BP Docs has finished initializing
		$this->loaded();
	}
	
	/**
	 * Defines bp_docs_init action
	 *
	 * This action fires on WP's init action and provides a way for the rest of BuddyPress
	 * Docs, as well as other dependent plugins, to hook into the loading process in an
	 * orderly fashion.
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function init() {
		do_action( 'bp_docs_init' );
	}
	
	/**
	 * Defines bp_docs_loaded action
	 *
	 * This action tells BP Docs and other plugins that the main initialization process has
	 * finished.
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function loaded() {
		do_action( 'bp_docs_loaded' );
	}
	
	/**
	 * Defines constants needed throughout the plugin.
	 *
	 * These constants can be overridden in bp-custom.php or wp-config.php.
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */	
	function load_constants() {
		if ( !defined( 'BP_DOCS_INSTALL_PATH' ) )
			define( 'BP_DOCS_INSTALL_PATH', dirname(__FILE__) );
		
		if ( !defined( 'BP_DOCS_SLUG' ) )
			define( 'BP_DOCS_SLUG', 'docs' );
	}	
	
	/**
	 * Registers BuddyPress Docs's post types and taxonomies
	 *
	 * The post type bp_doc corresponds to individual doc page, which in turn corresponds to
	 * individual WP posts (plus their revisions).
	 *
	 * The taxonomy bp_docs_associated_item is a hierarchical taxonomy that connects bp_doc
	 * items to groups. The parent terms 'groups' and 'users' are created automatically when
	 * first doc corresponding to that item type is created. Individual item ids, like
	 * group_ids or user_ids, have taxonomy item created for them (if necessary) when a doc
	 * needs to be associated with them. So a bp_doc post associated with group 6 will have
	 * the taxonomy bp_docs_associated_item '6', where '6' is a sub-tax of 'groups'.
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function register_post_type() {

		// Define the labels to be used by the post type bp_doc		
		$post_type_labels = array(
			'name' => _x( 'BuddyPress Docs', 'post type general name', 'bp-docs' ),
			'singular_name' => _x( 'Doc', 'post type singular name', 'bp-docs' ),
			'add_new' => _x( 'Add New', 'add new', 'bp-docs' ),
			'add_new_item' => __( 'Add New Doc', 'bp-docs' ),
			'edit_item' => __( 'Edit Doc', 'bp-docs' ),
			'new_item' => __( 'New Doc', 'bp-docs' ),
			'view_item' => __( 'View Doc', 'bp-docs' ),
			'search_items' => __( 'Search Docs', 'bp-docs' ),
			'not_found' =>  __( 'No Docs found', 'bp-docs' ),
			'not_found_in_trash' => __( 'No Docs found in Trash', 'bp-docs' ),
			'parent_item_colon' => ''
		);
	
		// Register the bp_doc post type
		register_post_type( 'bp_doc', array(
			'label' => __( 'BuddyPress Docs', 'bp-docs' ),
			'labels' => $post_type_labels,
			'public' => true,
			'_builtin' => false,
			'show_ui' => true,
			'hierarchical' => false,
			'supports' => array( 'title', 'editor', 'revisions', 'excerpt' ),
			'query_var' => true,
			//'rewrite' => false
			'rewrite' => array( "slug" => "doc", 'with_front' => false ), // Permalinks format
		));
		
		// Define the labels to be used by the taxonomy bp_docs_associated_item
		$associated_item_labels = array(
			'name' => __( 'Associated Items', 'bp-docs' ),
			'singular_name' => __( 'Associated Item', 'bp-docs' )
		);
		
		// Register the bp_docs_associated_item taxonomy
		register_taxonomy( 'bp_docs_associated_item', array( 'bp_doc' ), array(
			'labels' => $associated_item_labels,
			'hierarchical' => true,
			'show_ui' => true, 
			'query_var' => true,
			'rewrite' => array( 'slug' => 'item' ),
		));
	}
	
	/**
	 * Includes files needed by BuddyPress Docs
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */	
	function includes() {
		// bp-integration.php provides the hooks necessary to hook into BP navigation
		require_once( BP_DOCS_INSTALL_PATH . '/includes/bp-integration.php' );
		$this->bp_integration = new BP_Docs_BP_Integration;
	}
	
}

?>