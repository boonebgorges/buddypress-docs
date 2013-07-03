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

		// Define post type and taxonomy names for use in the register functions
		$this->post_type_name 		= apply_filters( 'bp_docs_post_type_name', 'bp_doc' );
		$this->associated_item_tax_name = apply_filters( 'bp_docs_associated_item_tax_name', 'bp_docs_associated_item' );
		$this->access_tax_name          = apply_filters( 'bp_docs_access_tax_name', 'bp_docs_access' );

		// Let plugins know that BP Docs has started loading
		add_action( 'plugins_loaded',   array( $this, 'load_hook' ), 20 );

		// Load predefined constants first thing
		add_action( 'bp_docs_load', 	array( $this, 'load_constants' ), 2 );

		// Includes necessary files
		add_action( 'bp_docs_load', 	array( $this, 'includes' ), 4 );

		// Load the BP Component extension
		add_action( 'bp_docs_load', 	array( $this, 'do_integration' ), 6 );

		// Load textdomain
		add_action( 'bp_docs_load',     array( $this, 'load_plugin_textdomain' ) );

		// Let other plugins know that BP Docs has finished initializing
		add_action( 'bp_init',          array( $this, 'init_hook' ) );

		// Hooks into the 'init' action to register our WP custom post type and tax
		add_action( 'bp_docs_init',     array( $this, 'register_post_type' ), 2 );
		add_action( 'bp_docs_init',     array( &$this, 'add_rewrite_tags' ), 4 );

		// Set up doc taxonomy, etc
		add_action( 'bp_docs_init',     array( $this, 'load_doc_extras' ), 8 );

		// Add rewrite rules
		add_action( 'generate_rewrite_rules', array( &$this, 'generate_rewrite_rules' ) );

		// parse_query
		add_action( 'parse_query', array( $this, 'parse_query' ) );

		// Protect doc access
		add_action( 'template_redirect', array( $this, 'protect_doc_access' ) );

		add_action( 'admin_init', array( $this, 'flush_rewrite_rules' ) );
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

		// functions.php includes miscellaneous utility functions used throughout
		require( BP_DOCS_INCLUDES_PATH . 'functions.php' );

		// component.php extends BP_Component, and does most of the basic setup for BP Docs
		require( BP_DOCS_INCLUDES_PATH . 'component.php' );

		// caps.php handles capabilities and roles
		require( BP_DOCS_INCLUDES_PATH . 'caps.php' );

		// access-query.php is a helper for determining access to docs
		require( BP_DOCS_INCLUDES_PATH . 'access-query.php' );

		// query-builder.php contains the class that fetches the content for each view
		require( BP_DOCS_INCLUDES_PATH . 'query-builder.php' );

		// templatetags.php has all functions in the global space available to templates
		require( BP_DOCS_INCLUDES_PATH . 'templatetags.php' );

		require( BP_DOCS_INCLUDES_PATH . 'templatetags-edit.php' );

		require( BP_DOCS_INCLUDES_PATH . 'attachments.php' );

		require( BP_DOCS_INCLUDES_PATH . 'ajax-validation.php' );

		require( BP_DOCS_INCLUDES_PATH . 'theme-bridge.php' );

		// formatting.php contains filters and functions used to modify appearance only
		require( BP_DOCS_INCLUDES_PATH . 'formatting.php' );

		// Dashboard-specific functions
		if ( is_admin() ) {
			require( BP_DOCS_INCLUDES_PATH . 'admin.php' );
			require( BP_DOCS_INCLUDES_PATH . 'upgrade.php' );
		}
	}

	/**
	 * Defines bp_docs_load action
	 *
	 * This action fires on WP's plugins_loaded action and provides a way for the rest of
	 * BuddyPress Docs, as well as other dependent plugins, to hook into the loading process in
	 * an orderly fashion.
	 *
	 * @package BuddyPress Docs
	 * @since 1.2
	 */
	function load_hook() {
		do_action( 'bp_docs_load' );
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
		if ( ! defined( 'BP_DOCS_PLUGIN_SLUG' ) ) {
			define( 'BP_DOCS_PLUGIN_SLUG', 'buddypress-docs' );
		}

		// You should never really need to override this bad boy
		if ( !defined( 'BP_DOCS_INSTALL_PATH' ) )
			define( 'BP_DOCS_INSTALL_PATH', WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . BP_DOCS_PLUGIN_SLUG . DIRECTORY_SEPARATOR );

		// Ditto
		if ( !defined( 'BP_DOCS_INCLUDES_PATH' ) )
			define( 'BP_DOCS_INCLUDES_PATH', BP_DOCS_INSTALL_PATH . 'includes/' );

		// Ditto^2. For deprecated files, we need a non-system path. Note: doesn't work
		// right with symlinks
		if ( !defined( 'BP_DOCS_INCLUDES_PATH_ABS' ) )
			define( 'BP_DOCS_INCLUDES_PATH_ABS', str_replace( ABSPATH, '', BP_DOCS_INCLUDES_PATH ) );

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

		// The slug used for the Started section of My Docs
		if ( !defined( 'BP_DOCS_STARTED_SLUG' ) )
			define( 'BP_DOCS_STARTED_SLUG', 'started' );

		// The slug used for the Edited section of My Docs
		if ( !defined( 'BP_DOCS_EDITED_SLUG' ) )
			define( 'BP_DOCS_EDITED_SLUG', 'edited' );

		// The slug used for 'my-docs'
		if ( !defined( 'BP_DOCS_MY_DOCS_SLUG' ) )
			define( 'BP_DOCS_MY_DOCS_SLUG', 'my-docs' );

		// The slug used for 'my-groups'
		if ( !defined( 'BP_DOCS_MY_GROUPS_SLUG' ) )
			define( 'BP_DOCS_MY_GROUPS_SLUG', 'my-groups' );

		// By default, BP Docs will replace the Recent Comments WP Dashboard Widget
		if ( !defined( 'BP_DOCS_REPLACE_RECENT_COMMENTS_DASHBOARD_WIDGET' ) )
			define( 'BP_DOCS_REPLACE_RECENT_COMMENTS_DASHBOARD_WIDGET', true );
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
			'name' 		     => _x( 'BuddyPress Docs', 'post type general name', 'bp-docs' ),
			'singular_name'      => _x( 'Doc', 'post type singular name', 'bp-docs' ),
			'add_new' 	     => _x( 'Add New', 'add new', 'bp-docs' ),
			'add_new_item' 	     => __( 'Add New Doc', 'bp-docs' ),
			'edit_item' 	     => __( 'Edit Doc', 'bp-docs' ),
			'new_item' 	     => __( 'New Doc', 'bp-docs' ),
			'view_item' 	     => __( 'View Doc', 'bp-docs' ),
			'search_items' 	     => __( 'Search Docs', 'bp-docs' ),
			'not_found' 	     =>  __( 'No Docs found', 'bp-docs' ),
			'not_found_in_trash' => __( 'No Docs found in Trash', 'bp-docs' ),
			'parent_item_colon'  => ''
		);

		// Set up the arguments to be used when the post type is registered
		// Only filter this if you are hella smart and/or know what you're doing
		$bp_docs_post_type_args = apply_filters( 'bp_docs_post_type_args', array(
			'label'        => __( 'BuddyPress Docs', 'bp-docs' ),
			'labels'       => $post_type_labels,
			'public'       => true,
			'show_ui'      => $this->show_cpt_ui(),
			'hierarchical' => true,
			'supports'     => array( 'title', 'editor', 'revisions', 'excerpt', 'comments', 'author' ),
			'query_var'    => false,
			'has_archive'  => true,
			'rewrite'      => array(
				'slug'       => bp_docs_get_slug(),
				'with_front' => false
			)
		) );

		// Register the bp_doc post type
		register_post_type( $this->post_type_name, $bp_docs_post_type_args );

		// Define the labels to be used by the taxonomy bp_docs_associated_item
		$associated_item_labels = array(
			'name'          => __( 'Associated Items', 'bp-docs' ),
			'singular_name' => __( 'Associated Item', 'bp-docs' )
		);

		// Register the bp_docs_associated_item taxonomy
		register_taxonomy( $this->associated_item_tax_name, array( $this->post_type_name ), array(
			'labels'       => $associated_item_labels,
			'hierarchical' => true,
			'show_ui'      => true,
			'query_var'    => true,
			'rewrite'      => array( 'slug' => 'item' ),
		) );

		// Register the bp_docs_access taxonomy
		register_taxonomy( $this->access_tax_name, array( $this->post_type_name ), array(
			'hierarchical' => false,
			'show_ui'      => false,
			'query_var'    => false,
		) );

		do_action( 'bp_docs_registered_post_type' );

		// Only register on the root blog
		if ( !bp_is_root_blog() )
			restore_current_blog();
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
			$this->history =& new BP_Docs_History;
		}

		// Load the wikitext addon
		require_once( BP_DOCS_INCLUDES_PATH . 'addon-wikitext.php' );
		$this->wikitext = new BP_Docs_Wikitext;

		do_action( 'bp_docs_load_doc_extras' );
	}

	/**
	 * Add rewrite tags
	 *
	 * @since 1.2
	 */
	function add_rewrite_tags() {
		add_rewrite_tag( '%%' . BP_DOCS_EDIT_SLUG      . '%%', '([1]{1,})' );
		add_rewrite_tag( '%%' . BP_DOCS_HISTORY_SLUG   . '%%', '([1]{1,})' );
		add_rewrite_tag( '%%' . BP_DOCS_DELETE_SLUG    . '%%', '([1]{1,})' );
		add_rewrite_tag( '%%' . BP_DOCS_CREATE_SLUG    . '%%', '([1]{1,})' );
		add_rewrite_tag( '%%' . BP_DOCS_MY_GROUPS_SLUG . '%%', '([1]{1,})' );
	}

	/**
	 * Generates custom rewrite rules
	 *
	 * @since 1.2
	 */
	function generate_rewrite_rules( $wp_rewrite ) {
		$bp_docs_rules = array(
			/**
			 * Top level
			 */

			// Create
			BP_DOCS_SLUG . '/' . BP_DOCS_CREATE_SLUG . '/?$' =>
				'index.php?post_type=' . $this->post_type_name . '&name=' . $wp_rewrite->preg_index( 1 ) . '&' . BP_DOCS_CREATE_SLUG . '=1',

			// My Groups
			BP_DOCS_SLUG . '/' . BP_DOCS_MY_GROUPS_SLUG . '/?$' =>
				'index.php?post_type=' . $this->post_type_name . '&name=' . $wp_rewrite->preg_index( 1 ) . '&' . BP_DOCS_MY_GROUPS_SLUG . '=1',

			/**
			 * Single Docs
			 */

			// Edit
			BP_DOCS_SLUG . '/([^/]+)/' . BP_DOCS_EDIT_SLUG . '/?$' =>
				'index.php?post_type=' . $this->post_type_name . '&name=' . $wp_rewrite->preg_index( 1 ) . '&' . BP_DOCS_EDIT_SLUG . '=1',

			// History
			BP_DOCS_SLUG . '/([^/]+)/' . BP_DOCS_HISTORY_SLUG . '/?$' =>
				'index.php?post_type=' . $this->post_type_name . '&name=' . $wp_rewrite->preg_index( 1 ) . '&' . BP_DOCS_HISTORY_SLUG . '=1',

			// Delete
			BP_DOCS_SLUG . '/([^/]+)/' . BP_DOCS_DELETE_SLUG . '/?$' =>
				'index.php?post_type=' . $this->post_type_name . '&name=' . $wp_rewrite->preg_index( 1 ) . '&' . BP_DOCS_HISTORY_SLUG . '=1'


		);

		// Merge Docs rules with existing
		$wp_rewrite->rules = array_merge( $bp_docs_rules, $wp_rewrite->rules );

		return $wp_rewrite;
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
	 * Handles stuff that needs to be done at 'parse_query'
	 *
	 * - Ensures that no post is loaded on the creation screen
	 * - Ensures that an archive template is loaded in /docs/my-groups/
	 */
	function parse_query( $posts_query ) {

		// Bail if $posts_query is not the main loop
		if ( ! $posts_query->is_main_query() )
			return;

		// Bail if filters are suppressed on this query
		if ( true == $posts_query->get( 'suppress_filters' ) )
			return;

		// Bail if in admin
		if ( is_admin() )
			return;

		// Don't query for any posts on /docs/create/
		if ( $posts_query->get( BP_DOCS_CREATE_SLUG ) ) {
			$posts_query->is_404 = false;
			$posts_query->set( 'p', -1 );
		}

		// Fall back on archive template on /docs/my-groups/
		if ( $posts_query->get( BP_DOCS_MY_GROUPS_SLUG ) ) {
			$posts_query->is_404 = false;
		}
	}

	/**
	 * Protects group docs from unauthorized access
	 *
	 * @since 1.2
	 * @uses bp_docs_current_user_can() This does most of the heavy lifting
	 */
	function protect_doc_access() {
		// What is the user trying to do?
		if ( bp_docs_is_doc_read() ) {
			$action = 'read';
		} else if ( bp_docs_is_doc_create() ) {
			$action = 'create';
		} else if ( bp_docs_is_doc_edit() ) {
			$action = 'edit';
		} else if ( bp_docs_is_doc_history() ) {
			$action = 'view_history';
		}

		if ( ! isset( $action ) ) {
			return;
		}

		if ( ! bp_docs_current_user_can( $action ) ) {
			$redirect_to = wp_get_referer();

			if ( ! $redirect_to || trailingslashit( $redirect_to ) == trailingslashit( wp_guess_url() ) ) {
				$redirect_to = bp_get_root_domain();
			}

			switch ( $action ) {
				case 'read' :
					$message = __( 'You are not allowed to read that Doc.', 'bp-docs' );
					break;

				case 'create' :
					$message = __( 'You are not allowed to create Docs.', 'bp-docs' );
					break;

				case 'edit' :
					$message = __( 'You are not allowed to edit that Doc.', 'bp-docs' );
					break;

				case 'view_history' :
					$message = __( 'You are not allowed to view that Doc\'s history.', 'bp-docs' );
					break;
			}

			bp_core_add_message( $message, 'error' );
			bp_core_redirect( $redirect_to );
		}
	}

	function flush_rewrite_rules() {
		if ( ! is_admin() ) {
			return;
		}

		if ( ! is_super_admin() ) {
			return;
		}

		global $wp_rewrite;

		// Check to see whether our rules have been registered yet, by
		// finding a Docs rule and then comparing it to the registered rules
		foreach ( $wp_rewrite->extra_rules_top as $rewrite => $rule ) {
			if ( 0 === strpos( $rewrite, bp_docs_get_slug() ) ) {
				$test_rule = $rule;
			}
		}
		$registered_rules = get_option( 'rewrite_rules' );

		if ( is_array( $registered_rules ) && ! in_array( $test_rule, $registered_rules ) ) {
			flush_rewrite_rules();
		}
	}

	function activation() {
		error_log('activating');
	}

	/**
	 * Initiates the BP Component extension
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function do_integration() {
		global $bp;

		$bp->bp_docs = new BP_Docs_Component;
	}
}

?>
