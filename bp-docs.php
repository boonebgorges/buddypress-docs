<?php

/**
 * Main plugin class.
 *
 * @package BuddyPressDocs
 */
class BP_Docs {
	var $post_type_name;
	var $associated_item_tax_name;
	var $access_tax_name;
	var $comment_access_tax_name;

	/**
	 * Folders add-on.
	 *
	 * @var BP_Docs_Folders
	 * @since 1.8
	 */
	var $folders;

	/**
	 * PHP 5 constructor
	 *
	 * @since 1.0-beta
	 */
	function __construct() {

		// Define post type and taxonomy names for use in the register functions
		$this->post_type_name 		= apply_filters( 'bp_docs_post_type_name', 'bp_doc' );
		$this->associated_item_tax_name = apply_filters( 'bp_docs_associated_item_tax_name', 'bp_docs_associated_item' );
		$this->access_tax_name          = apply_filters( 'bp_docs_access_tax_name', 'bp_docs_access' );
		$this->comment_access_tax_name  = apply_filters( 'bp_docs_comment_access_tax_name', 'bp_docs_comment_access' );

		// :'(
		wp_cache_add_non_persistent_groups( array( 'bp_docs_nonpersistent' ) );

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
	 * PHP 4 constructor
	 *
	 * @since 1.0-beta
	 */
	function bp_docs() {
		$this->__construct();
	}

	/**
	 * Loads the textdomain for the plugin.
	 * Language files are used in this order of preference:
	 *    - WP_LANG_DIR/plugins/buddypress-docs-LOCALE.mo
	 *    - WP_PLUGIN_DIR/buddypress-docs/languages/buddypress-docs-LOCALE.mo
	 *
	 * @since 1.0.2
	 */
	function load_plugin_textdomain() {
		/*
		 * As of WP 4.6, WP has, by this point in the load order, already
		 * automatically added language files in this location:
		 * wp-content/languages/plugins/buddypress-docs-es_ES.mo
		 * load_plugin_textdomain() also looks for language files in that location,
		 * then it falls back to translations in the plugin's /languages folder, like
		 * wp-content/buddypress-docs/languages/buddypress-docs-es_ES.mo
		 */
		load_plugin_textdomain( 'buddypress-docs', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Includes files needed by BuddyPress Docs
	 *
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

		require( BP_DOCS_INCLUDES_PATH . 'edit-lock.php' );

		// formatting.php contains filters and functions used to modify appearance only
		require( BP_DOCS_INCLUDES_PATH . 'formatting.php' );

		// class-wp-widget-recent-docs.php adds a widget to show recently created docs.
		require( BP_DOCS_INCLUDES_PATH . 'class-wp-widget-recent-docs.php' );

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
	 * @since 1.0-beta
	 */
	function load_constants() {
		if ( ! defined( 'BP_DOCS_PLUGIN_SLUG' ) ) {
			define( 'BP_DOCS_PLUGIN_SLUG', 'buddypress-docs' );
		}

		// You should never really need to override this bad boy
		if ( !defined( 'BP_DOCS_INSTALL_PATH' ) ) {
			define( 'BP_DOCS_INSTALL_PATH', plugin_dir_path( __FILE__ ) );
		}

		// Ditto
		if ( !defined( 'BP_DOCS_INCLUDES_PATH' ) )
			define( 'BP_DOCS_INCLUDES_PATH', BP_DOCS_INSTALL_PATH . 'includes/' );

		// Ditto^2. For deprecated files, we need a non-system path. Note: doesn't work
		// right with symlinks
		if ( !defined( 'BP_DOCS_INCLUDES_PATH_ABS' ) )
			define( 'BP_DOCS_INCLUDES_PATH_ABS', str_replace( ABSPATH, '', BP_DOCS_INCLUDES_PATH ) );

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

		// The slug used when deleting a doc
		if ( !defined( 'BP_DOCS_UNTRASH_SLUG' ) )
			define( 'BP_DOCS_UNTRASH_SLUG', 'untrash' );

		// The slug used when removing a doc from a group
		if ( ! defined( 'BP_DOCS_UNLINK_FROM_GROUP_SLUG' ) )
			define( 'BP_DOCS_UNLINK_FROM_GROUP_SLUG', 'unlink-from-group' );

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
	 * @since 1.0-beta
	 */
	function register_post_type() {
		// Only register on the root blog
		if ( !bp_is_root_blog() )
			switch_to_blog( BP_ROOT_BLOG );

		// Define the labels to be used by the post type bp_doc
		$post_type_labels = array(
			'name' 		     => _x( 'Docs', 'post type general name', 'buddypress-docs' ),
			'singular_name'      => _x( 'Doc', 'post type singular name', 'buddypress-docs' ),
			'add_new' 	     => _x( 'Add New', 'add new', 'buddypress-docs' ),
			'add_new_item' 	     => __( 'Add New Doc', 'buddypress-docs' ),
			'edit_item' 	     => __( 'Edit Doc', 'buddypress-docs' ),
			'new_item' 	     => __( 'New Doc', 'buddypress-docs' ),
			'view_item' 	     => __( 'View Doc', 'buddypress-docs' ),
			'search_items' 	     => __( 'Search Docs', 'buddypress-docs' ),
			'not_found' 	     =>  __( 'No Docs found', 'buddypress-docs' ),
			'not_found_in_trash' => __( 'No Docs found in Trash', 'buddypress-docs' ),
			'parent_item_colon'  => ''
		);

		// Set up the arguments to be used when the post type is registered
		// Only filter this if you are hella smart and/or know what you're doing
		$bp_docs_post_type_args = apply_filters( 'bp_docs_post_type_args', array(
			'label'        => __( 'Docs', 'buddypress-docs' ),
			'labels'       => $post_type_labels,
			'public'       => true,
			'show_ui'      => $this->show_cpt_ui(),
			'hierarchical' => true,
			'supports'     => array( 'title', 'editor', 'revisions', 'excerpt', 'comments', 'author' ),
			'query_var'    => true,
			'has_archive'  => true,
			'rewrite'      => array(
				'slug'       => bp_docs_get_docs_slug(),
				'with_front' => false
			)
		) );

		// Register the bp_doc post type
		register_post_type( $this->post_type_name, $bp_docs_post_type_args );

		// Define the labels to be used by the taxonomy bp_docs_associated_item
		$associated_item_labels = array(
			'name'          => __( 'Associated Items', 'buddypress-docs' ),
			'singular_name' => __( 'Associated Item', 'buddypress-docs' )
		);

		// Register the bp_docs_associated_item taxonomy
		register_taxonomy( $this->associated_item_tax_name, array( $this->post_type_name ), array(
			'labels'       => $associated_item_labels,
			'hierarchical' => true,
			'show_ui'      => false,
			'query_var'    => true,
			'rewrite'      => array( 'slug' => 'item' ),
		) );

		// Register the bp_docs_access taxonomy
		register_taxonomy( $this->access_tax_name, array( $this->post_type_name ), array(
			'hierarchical' => false,
			'show_ui'      => false,
			'query_var'    => false,
		) );

		// Register the bp_docs_comment_access taxonomy.
		register_taxonomy( $this->comment_access_tax_name, array( $this->post_type_name ), array(
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
	 * @since 1.0-beta
	 */
	function load_doc_extras() {
		// Todo: make this conditional with a filter or a constant
		require_once( BP_DOCS_INCLUDES_PATH . 'addon-taxonomy.php' );
		$this->taxonomy = new BP_Docs_Taxonomy;

		require_once( BP_DOCS_INCLUDES_PATH . 'addon-hierarchy.php' );
		$this->hierarchy = new BP_Docs_Hierarchy;

		// Don't load the History component if post revisions are disabled
		$wp_post_revisions = defined( 'WP_POST_REVISIONS' ) && WP_POST_REVISIONS;
		$bp_docs_revisions = defined( 'BP_DOCS_REVISIONS' ) && BP_DOCS_REVISIONS;
		if ( $wp_post_revisions || $bp_docs_revisions ) {
			require_once( BP_DOCS_INCLUDES_PATH . 'addon-history.php' );
			$this->history = new BP_Docs_History;
		}

		// Load the wikitext addon
		require_once( BP_DOCS_INCLUDES_PATH . 'addon-wikitext.php' );
		$this->wikitext = new BP_Docs_Wikitext;

		// Load the Folders addon
		require_once( BP_DOCS_INCLUDES_PATH . 'addon-folders.php' );
		$this->folders = new BP_Docs_Folders();

		// Load Akismet support if Akismet is configured and desired.
		/**
		 * Should we apply Akismet filtration to BuddyPress Docs content?
		 *
		 * @since 2.1.0
		 *
		 * @param bool $use Whether we want to use Akismet for BP Docs.
		 */
		if ( apply_filters( 'bp_docs_use_akismet', true ) && defined( 'AKISMET_VERSION' ) && class_exists( 'Akismet' ) ) {
			$wordpress_api_key = bp_get_option( 'wordpress_api_key' );
			if ( ! empty( $wordpress_api_key ) || defined( 'WPCOM_API_KEY' ) ) {
				require_once( BP_DOCS_INCLUDES_PATH . 'addon-akismet.php' );
				$this->akismet = new BP_Docs_Akismet();
				$this->akismet->add_hooks();
			}
		}

		// Load the Moderation addon.
		require_once( BP_DOCS_INCLUDES_PATH . 'addon-moderation.php' );
		$this->moderation = new BP_Docs_Moderation();
		$this->moderation->add_hooks();

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
		add_rewrite_tag( '%%' . BP_DOCS_UNTRASH_SLUG    . '%%', '([1]{1,})' );
		add_rewrite_tag( '%%' . BP_DOCS_CREATE_SLUG    . '%%', '([1]{1,})' );
		add_rewrite_tag( '%%' . BP_DOCS_MY_GROUPS_SLUG . '%%', '([1]{1,})' );
		add_rewrite_tag( '%%' . BP_DOCS_UNLINK_FROM_GROUP_SLUG . '%%', '([1]{1,})' );

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
			bp_docs_get_docs_slug() . '/' . BP_DOCS_CREATE_SLUG . '/?$' =>
				'index.php?post_type=' . $this->post_type_name . '&name=' . $wp_rewrite->preg_index( 1 ) . '&' . BP_DOCS_CREATE_SLUG . '=1',

			// My Groups
			bp_docs_get_docs_slug() . '/' . BP_DOCS_MY_GROUPS_SLUG . "/{$wp_rewrite->pagination_base}/([0-9]{1,})/?$" =>
				'index.php?post_type=' . $this->post_type_name . '&' . BP_DOCS_MY_GROUPS_SLUG . '=1' . '&paged=' . $wp_rewrite->preg_index( 1 ),
			bp_docs_get_docs_slug() . '/' . BP_DOCS_MY_GROUPS_SLUG . '/?$' =>
				'index.php?post_type=' . $this->post_type_name . '&name=' . $wp_rewrite->preg_index( 1 ) . '&' . BP_DOCS_MY_GROUPS_SLUG . '=1',

			/**
			 * Single Docs
			 */

			// Edit
			bp_docs_get_docs_slug() . '/([^/]+)/' . BP_DOCS_EDIT_SLUG . '/?$' =>
				'index.php?post_type=' . $this->post_type_name . '&name=' . $wp_rewrite->preg_index( 1 ) . '&' . BP_DOCS_EDIT_SLUG . '=1',

			// History
			bp_docs_get_docs_slug() . '/([^/]+)/' . BP_DOCS_HISTORY_SLUG . '/?$' =>
				'index.php?post_type=' . $this->post_type_name . '&name=' . $wp_rewrite->preg_index( 1 ) . '&' . BP_DOCS_HISTORY_SLUG . '=1',

			// Delete
			bp_docs_get_docs_slug() . '/([^/]+)/' . BP_DOCS_DELETE_SLUG . '/?$' =>
				'index.php?post_type=' . $this->post_type_name . '&name=' . $wp_rewrite->preg_index( 1 ) . '&' . BP_DOCS_HISTORY_SLUG . '=1',

			// Untrash
			bp_docs_get_docs_slug() . '/([^/]+)/' . BP_DOCS_UNTRASH_SLUG . '/?$' =>
				'index.php?post_type=' . $this->post_type_name . '&name=' . $wp_rewrite->preg_index( 1 ) . '&' . BP_DOCS_UNTRASH_SLUG . '=1',

			// Unlink from group
			bp_docs_get_docs_slug() . '/([^/]+)/' . BP_DOCS_UNLINK_FROM_GROUP_SLUG . '/?$' =>
				'index.php?post_type=' . $this->post_type_name . '&name=' . $wp_rewrite->preg_index( 1 ) . '&' . BP_DOCS_UNLINK_FROM_GROUP_SLUG . '=1',

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

		// For single Doc views, allow access to 'deleted' or 'pending' items
		// that the current user is the admin of
		if ( $posts_query->is_single && bp_docs_get_post_type_name() === $posts_query->get( 'post_type' ) ) {

			$doc_slug = $posts_query->get( 'name' );

			// Direct query, because barf
			global $wpdb;
			$author_id = $wpdb->get_var( $wpdb->prepare(
				"SELECT post_author FROM {$wpdb->posts} WHERE post_type = %s AND post_name = %s",
				bp_docs_get_post_type_name(),
				$doc_slug
			) );

			// Post author or mod can visit it
			if ( $author_id && ( $author_id == get_current_user_id() || current_user_can( 'bp_moderate' ) ) ) {
				$posts_query->set( 'post_status', array( 'publish', 'trash', 'bp_docs_pending' ) );

				// Make the 'trash' post status public
				add_filter( 'posts_request', array( $this, 'make_post_statuses_public' ) );

				// ... and undo that when we're done
				add_filter( 'the_posts', array( $this, 'remove_make_post_statuses_public' ) );
			}
		}
	}

	/**
	 * Make the 'trash' post status public.
	 *
	 * This is an unavoidable hack for cases where we want a trashed doc
	 * to be visitable by the post author or by an admin.
	 *
	 * Access is public because it needs to be accessible by
	 * call_user_func(), but should *not* be called directly.
	 *
	 * @since BuddyPress 1.5.5
	 *
	 * @param $request Passthrough.
	 */
	public function make_post_statuses_public( $request ) {
		global $wp_post_statuses;
		$wp_post_statuses['trash']->public = true;
		$wp_post_statuses['bp_docs_pending']->public = true;
		return $request;
	}

	/**
	 * Reverse the public-trash hack applied in self::make_trash_public()
	 *
	 * @since BuddyPress 1.5.5
	 *
	 * @param $posts Passthrough.
	 */
	public function remove_make_post_statuses_public( $posts ) {
		global $wp_post_statuses;
		$wp_post_statuses['trash']->public = false;
		$wp_post_statuses['bp_docs_pending']->public = false;
		return $posts;
	}

	/**
	 * Protects group docs from unauthorized access
	 *
	 * @since 1.2
	 */
	function protect_doc_access() {
		// What is the user trying to do?
		if ( bp_docs_is_doc_read() ) {
			$action = 'bp_docs_read';
		} else if ( bp_docs_is_doc_create() ) {
			$action = 'bp_docs_create';
		} else if ( bp_docs_is_doc_edit() ) {
			$action = 'bp_docs_edit';
		} else if ( bp_docs_is_doc_history() ) {
			$action = 'bp_docs_view_history';
		}

		if ( ! isset( $action ) ) {
			return;
		}

		if ( current_user_can( $action ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			$redirect_to = bp_docs_get_doc_link();

			bp_core_no_access( array(
				'mode' => 2,
				'redirect' => $redirect_to,
			) );
		} else {
			bp_core_add_message( __( 'You do not have permission to do that.', 'buddypress-docs' ), 'error' );
			bp_core_redirect( bp_get_root_domain() );
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
		$test_rewrite = null;
		foreach ( $wp_rewrite->extra_rules_top as $rewrite => $rule ) {
			if ( 0 === strpos( $rewrite, bp_docs_get_docs_slug() ) ) {
				$test_rewrite = $rewrite;
				$test_rule = $rule;
				break;
			}
		}
		$registered_rules = get_option( 'rewrite_rules' );

		if ( $test_rewrite && is_array( $registered_rules ) && ( ! isset( $registered_rules[ $test_rewrite ] ) || $test_rule !== $registered_rules[ $test_rewrite ] ) ) {
			flush_rewrite_rules();
		}
	}

	/**
	 * Initiates the BP Component extension
	 *
	 * @since 1.0-beta
	 */
	function do_integration() {
		global $bp;

		$bp->bp_docs = new BP_Docs_Component;
	}
}
