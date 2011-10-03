<?php

class BP_Docs {
	var $post_type_name;
	var $associated_item_tax_name;

	/**
	 * PHP 4 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function bp_docs() {
		$this->__construct();
	}

	/**
	 * PHP 5 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function __construct() {
		global $bp;

		// Define post type and taxonomy names for use in the register functions
		$this->post_type_name 		= apply_filters( 'bp_docs_post_type_name', 'bp_doc' );
		$this->associated_item_tax_name = apply_filters( 'bp_docs_associated_item_tax_name', 'bp_docs_associated_item' );

		// Then stash them in the $bp global for use in template tags
		$bp->bp_docs->post_type_name		= $this->post_type_name;
		$bp->bp_docs->associated_item_tax_name	= $this->associated_item_tax_name;

		// Load predefined constants first thing
		add_action( 'bp_docs_init', 	array( $this, 'load_constants' ), 2 );

		// Set up doc taxonomy
		add_action( 'init', 		array( $this, 'load_doc_extras' ) );

		// Hooks into the 'init' action to register our WP custom post type and tax
		add_action( 'init', 		array( $this, 'register_post_type' ) );

		// Load textdomain
		add_action( 'init',		array( $this, 'load_plugin_textdomain' ) );

		// Includes necessary files
		add_action( 'bp_docs_init', 	array( $this, 'includes' ), 4 );

		// Load the BP integration functions
		add_action( 'bp_docs_init', 	array( $this, 'do_integration' ), 6 );

		// Let plugins know that BP Docs has started loading
		$this->init_hook();

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
	 * @since 1.0-beta
	 */
	function init_hook() {
		do_action( 'bp_docs_init' );
	}

	/**
	 * Defines bp_docs_loaded action
	 *
	 * This action tells BP Docs and other plugins that the main initialization process has
	 * finished.
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
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
	 * @since 1.0-beta
	 */
	function load_constants() {
		// You should never really need to override this bad boy
		if ( !defined( 'BP_DOCS_INSTALL_PATH' ) )
			define( 'BP_DOCS_INSTALL_PATH', dirname(__FILE__) . '/' );

		// Ditto
		if ( !defined( 'BP_DOCS_INCLUDES_PATH' ) )
			define( 'BP_DOCS_INCLUDES_PATH', BP_DOCS_INSTALL_PATH . 'includes/' );

		// The main slug
		if ( !defined( 'BP_DOCS_SLUG' ) )
			define( 'BP_DOCS_SLUG', 'docs' );

		// The slug used when viewing a doc category
		if ( !defined( 'BP_DOCS_CATEGORY_SLUG' ) )
			define( 'BP_DOCS_CATEGORY_SLUG', 'category' );

		// The slug used when editing a doc
		if ( !defined( 'BP_DOCS_EDIT_SLUG' ) )
			define( 'BP_DOCS_EDIT_SLUG', 'edit' );

		// The slug used when viewing doc histor
		if ( !defined( 'BP_DOCS_HISTORY_SLUG' ) )
			define( 'BP_DOCS_HISTORY_SLUG', 'history' );

		// The slug used when viewing a single doc
		if ( !defined( 'BP_DOCS_SINGLE_SLUG' ) )
			define( 'BP_DOCS_SINGLE_SLUG', 'single' );

		// The slug used when creating a new doc
		if ( !defined( 'BP_DOCS_CREATE_SLUG' ) )
			define( 'BP_DOCS_CREATE_SLUG', 'create' );

		// The slug used when deleting a doc
		if ( !defined( 'BP_DOCS_DELETE_SLUG' ) )
			define( 'BP_DOCS_DELETE_SLUG', 'delete' );

		// By default, BP Docs will replace the Recent Comments WP Dashboard Widget
		if ( !defined( 'BP_DOCS_REPLACE_RECENT_COMMENTS_DASHBOARD_WIDGET' ) )
			define( 'BP_DOCS_REPLACE_RECENT_COMMENTS_DASHBOARD_WIDGET', true );
	}

	/**
	 * Loads the file that enables the use of extras (doc taxonomy, hierarchy, etc)
	 *
	 * This is loaded conditionally, so that the use of extras can be disabled by the
	 * administrator. It is loaded before the bp_docs post type is registered so that we have
	 * access to the 'taxonomy' argument of register_post_type.
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function load_doc_extras() {
		// Todo: make this conditional with a filter or a constant
		require_once( BP_DOCS_INCLUDES_PATH . 'addon-taxonomy.php' );
		$this->taxonomy = new BP_Docs_Taxonomy;

		require_once( BP_DOCS_INCLUDES_PATH . 'addon-hierarchy.php' );
		$this->hierarchy = new BP_Docs_Hierarchy;

		// Don't load the History component if post revisions are disabled
		if ( defined( 'WP_POST_REVISIONS' ) && WP_POST_REVISIONS ) {
			require_once( BP_DOCS_INCLUDES_PATH . 'addon-history.php' );
			$this->history = new BP_Docs_History;
		}

		// Load the wikitext addon
		require_once( BP_DOCS_INCLUDES_PATH . 'addon-wikitext.php' );
		$this->wikitext = new BP_Docs_Wikitext;

		do_action( 'bp_docs_load_doc_extras' );
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
	 * @since 1.0-beta
	 */
	function register_post_type() {
		// Only register on the root blog
		if ( !bp_is_root_blog() )
			switch_to_blog( BP_ROOT_BLOG );

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

		// Set up the arguments to be used when the post type is registered
		// Only filter this if you are hella smart and/or know what you're doing
		$bp_docs_post_type_args = apply_filters( 'bp_docs_post_type_args', array(
			'label' => __( 'BuddyPress Docs', 'bp-docs' ),
			'labels' => $post_type_labels,
			'public' => false,
			'_builtin' => false,
			'show_ui' => $this->show_cpt_ui(),
			'hierarchical' => false,
			'supports' => array( 'title', 'editor', 'revisions', 'excerpt', 'comments' ),
			'query_var' => true,
			'rewrite' => false // Todo: This bites
		) );

		// Register the bp_doc post type
		register_post_type( $this->post_type_name, $bp_docs_post_type_args );

		// Define the labels to be used by the taxonomy bp_docs_associated_item
		$associated_item_labels = array(
			'name' => __( 'Associated Items', 'bp-docs' ),
			'singular_name' => __( 'Associated Item', 'bp-docs' )
		);

		// Register the bp_docs_associated_item taxonomy
		register_taxonomy( $this->associated_item_tax_name, array( $this->post_type_name ), array(
			'labels' => $associated_item_labels,
			'hierarchical' => true,
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => array( 'slug' => 'item' ),
		));

		do_action( 'bp_docs_registered_post_type' );

		// Only register on the root blog
		if ( !bp_is_root_blog() )
			restore_current_blog();
	}

	/**
	 * Show the CPT Dashboard UI to the current user?
	 *
	 * Defaults to is_super_admin(), but is filterable
	 *
	 * @package BuddyPress Docs
	 * @since 1.0.8
	 *
	 * @return bool $show_ui
	 */
	function show_cpt_ui() {
		$show_ui = is_super_admin();

		return apply_filters( 'bp_docs_show_cpt_ui', $show_ui );
	}

	/**
	 * Loads the textdomain for the plugin
	 *
	 * @package BuddyPress Docs
	 * @since 1.0.2
	 */
	function load_plugin_textdomain() {
		load_plugin_textdomain( 'bp-docs', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Includes files needed by BuddyPress Docs
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function includes() {

		// query-builder.php contains the class that fetches the content for each view
		require_once( BP_DOCS_INCLUDES_PATH . 'query-builder.php' );

		// bp-integration.php provides the hooks necessary to hook into BP navigation
		require_once( BP_DOCS_INCLUDES_PATH . 'integration-bp.php' );

		// templatetags.php has all functions in the global space available to templates
		require_once( BP_DOCS_INCLUDES_PATH . 'templatetags.php' );

		// formatting.php contains filters and functions used to modify appearance only
		require_once( BP_DOCS_INCLUDES_PATH . 'formatting.php' );

		// Dashboard-specific functions
		if ( is_admin() )
			require_once( BP_DOCS_INCLUDES_PATH . 'admin.php' );
	}

	/**
	 * Initiates the BP integration functions
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function do_integration() {
		$this->bp_integration = new BP_Docs_BP_Integration;
	}
}

?>
