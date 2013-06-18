<?php
/**
 * Implementation of BP_Component
 *
 * @since 1.2
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( 'BP_Component' ) ) {
	return;
}

class BP_Docs_Component extends BP_Component {
	var $groups_integration;

	var $post_type_name;
	var $associated_tax_name;
	var $access_tax_name;

	var $slugtocheck = array();
	var $query;
	var $includes_url;

	var $current_view;

	/**
	 * Constructor
	 *
	 * @since 1.2
	 */
	function __construct() {
		global $bp;

		parent::start(
			'bp_docs',
			__( 'BuddyPress Docs', 'bp-docs' ),
			BP_DOCS_INSTALL_PATH
		);

		$bp->active_components[$this->id] = '1';

		$this->setup_hooks();
	}

	/**
	 * Sets up the hooks for the Component's custom methods
	 *
	 * @since 1.2
	 */
	function setup_hooks() {
		require( BP_DOCS_INCLUDES_PATH . 'integration-users.php' );
		$this->users_integration = new BP_Docs_Users_Integration;

		if ( bp_is_active( 'groups' ) ) {
			require( BP_DOCS_INCLUDES_PATH . 'integration-groups.php' );
			$this->groups_integration = new BP_Docs_Groups_Integration;
		}

		add_action( 'bp_actions', array( &$this, 'catch_page_load' ), 1 );

		$this->attachments = new BP_Docs_Attachments();
//		add_action( 'wp', array( $this, 'setup_attachments' ), 1 );

		/**
		 * Methods related to comment behavior
		 */

		// Redirect to the correct place after a comment
		add_action( 'comment_post_redirect', array( &$this, 'comment_post_redirect' ), 99, 2 );

		// Doc comments are always from trusted members (for the moment), so approve them
		add_action( 'pre_comment_approved', array( $this, 'approve_doc_comments' ), 999, 2 );

		// Hook the doc comment activity function
		add_action( 'comment_post', array( &$this, 'post_comment_activity' ), 8 );

		// Filter the location of the comments template to allow it to be included with
		// the plugin
		add_filter( 'comments_template', array( $this, 'comments_template' ) );

		add_filter( 'post_type_link', array( &$this, 'filter_permalinks' ), 10, 4 );

		// Keep comment notifications from being sent
		add_filter( 'comment_post', array( $this, 'check_comment_type' ) );

		// Add the Search filter markup
		add_filter( 'bp_docs_filter_types', array( $this, 'filter_type' ) );
		add_filter( 'bp_docs_filter_sections', array( $this, 'filter_markup' ) );

		/**
		 * Methods related to BuddyPress activity
		 */

		// Add BP Docs activity types to the activity filter dropdown
		$dropdowns = apply_filters( 'bp_docs_activity_filter_locations', array(
			'bp_activity_filter_options',
			'bp_group_activity_filter_options',
			'bp_member_activity_filter_options'
		) );
		foreach( $dropdowns as $hook ) {
			add_action( $hook, array( &$this, 'activity_filter_options' ) );
		}

		// Hook the create/edit activity function
		add_action( 'bp_docs_doc_saved', array( &$this, 'post_activity' ) );

		// Delete activity items when a post is trashed
		add_action( 'transition_post_status', array( $this, 'delete_doc_activity' ), 10, 3 );

		/**
		 * MISC
		 */

		add_filter( 'bp_core_get_directory_page_ids', array( $this, 'remove_bp_page' ) );

		// Respect $activities_template->disable_blogforum_replies
		add_filter( 'bp_activity_can_comment',	array( $this, 'activity_can_comment'	) );

		// AJAX handler for removing the edit lock when a user clicks away from Edit mode
		add_action( 'wp_ajax_remove_edit_lock', array( $this, 'remove_edit_lock'        ) );

		// Add body class
		add_filter( 'bp_get_the_body_class', array( $this, 'body_class' ) );

		// Global directory tags
		add_filter( 'bp_docs_taxonomy_get_item_terms', array( $this, 'get_item_terms' ) );

		add_action( 'bp_docs_init',             array( $this, 'set_includes_url' 	) );
		add_action( 'wp_enqueue_scripts',       array( $this, 'enqueue_scripts' 	) );
		add_action( 'wp_print_styles',          array( $this, 'enqueue_styles' 		) );

	}

	/**
	 * Implementation of BP_Component::setup_globals()
	 *
	 * Creates globals required by BP_Component.
	 * Registers post_type and taxonomy names in component global.
	 * Sets up the 'slugstocheck', which are used when enqueuing styles and scripts.
	 *
	 * @since 1.2
	 * @see BP_Docs_Component::enqueue_scripts()
	 * @see BP_Docs_Component::enqueue_styles()
	 */
	function setup_globals() {
		global $bp_docs;

		// Set up the $globals array to be passed along to parent::setup_globals()
		$globals = array(
			'slug'                  => BP_DOCS_SLUG,
			'root_slug'             => isset( $bp->pages->{$this->id}->slug ) ? $bp->pages->{$this->id}->slug : BP_DOCS_SLUG,
			'has_directory'         => false, // Set to false if not required
			'notification_callback' => 'bp_docs_format_notifications',
			'search_string'         => __( 'Search Docs...', 'buddypress' ),
		);

		// Let BP_Component::setup_globals() do its work.
		parent::setup_globals( $globals );

		// Stash tax and post type names in the $bp global for use in template tags
		$this->post_type_name		= $bp_docs->post_type_name;
		$this->associated_item_tax_name = $bp_docs->associated_item_tax_name;
		$this->access_tax_name          = $bp_docs->access_tax_name;

		// This info is loaded here because it needs to happen after BP core globals are
		// set up
		$this->slugstocheck 	= bp_action_variables() ? bp_action_variables() : array();
		$this->slugstocheck[] 	= bp_current_component();
		$this->slugstocheck[] 	= bp_current_action();

		$this->set_current_item_type();
		$this->set_current_view();
	}

	/**
	 * Sets up Docs menu under My Account toolbar
	 *
	 * @since 1.3
	 */
	public function setup_admin_bar() {
		global $bp;

		$wp_admin_nav = array();

		if ( is_user_logged_in() ) {

			$title = __( 'Docs', 'buddypress' );

			// Add the "My Account" sub menus
			$wp_admin_nav[] = array(
				'parent' => $bp->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => $title,
				'href'   => bp_docs_get_mydocs_link(),
			);

			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-started',
				'title'  => __( 'Started By Me', 'bp-docs' ),
				'href'   => bp_docs_get_mydocs_started_link(),
			);

			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-edited',
				'title'  => __( 'Edited By Me', 'bp-docs' ),
				'href'   => bp_docs_get_mydocs_edited_link(),
			);

			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-create',
				'title'  => __( 'Create New Doc', 'bp-docs' ),
				'href'   => bp_docs_get_create_link(),
			);

		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * In Docs 1.2 through 1.2.2, there was an error in which Docs registered
	 * a bp-pages entry. This fixes the error
	 *
	 * @since 1.2.3
	 */
	function remove_bp_page( $pages ) {
		if ( isset( $pages['bp_docs'] ) ) {
			unset( $pages['bp_docs']);
			bp_update_option( 'bp-pages', $pages );
		}

		return $pages;
	}

	/**
	 * Gets the item type of the item you're looking at - e.g 'group', 'user'.
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 *
	 * @return str $view The current item type
	 */
	function set_current_item_type() {
		global $bp;

		$type = '';

		if ( bp_is_user() ) {
			$type = 'user';
		}

		$type = apply_filters( 'bp_docs_get_item_type', $type, $this );

		$this->item_type = $type;
	}

	/**
	 * Gets the current view, based on the page you're looking at.
	 *
	 * Filter 'bp_docs_get_current_view' to extend to different components.
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 *
	 * @param str $item_type Defaults to the object's item type
	 * @return str $view The current view. Core values: edit, single, list, category
	 */
	function set_current_view( $item_type = false ) {
		global $bp;

		$view = '';

		if ( !$item_type )
			$item_type = $this->item_type;

		$view = apply_filters( 'bp_docs_get_current_view', $view, $item_type );

		$this->current_view = $view;
	}

	/**
	 * Creates component navigation (Member > Docs)
	 *
	 * @since 1.2
	 * @todo Make the 'Docs' label customizable by the admin
	 */
	function setup_nav() {

		$main_nav = array(
			'name' 		      => __( 'Docs', 'bp-docs' ),

			// Disabled count for now. See https://github.com/boonebgorges/buddypress-docs/issues/261
			//'name' 		      => sprintf( __( 'Docs <span>%d</span>', 'bp-docs' ), bp_docs_get_doc_count( bp_displayed_user_id(), 'user' ) ),
			'slug' 		      => bp_docs_get_slug(),
			'position' 	      => 80,
			'screen_function'     => array( &$this, 'template_loader' ),
			'default_subnav_slug' => BP_DOCS_STARTED_SLUG
		);

		$parent_url = trailingslashit( bp_displayed_user_domain() . bp_docs_get_slug() );

		$mydocs_label = bp_is_my_profile() ? __( 'My Docs ', 'bp-docs' ) : sprintf( __( '%s&#8217;s Docs' ), bp_get_user_firstname( bp_get_displayed_user_fullname() ) );

		$sub_nav[] = array(
			'name'            => bp_is_my_profile() ? __( 'Started By Me', 'bp-docs' ) : sprintf( __( 'Started By %s', 'bp-docs' ), bp_get_user_firstname() ),
			'slug'            => BP_DOCS_STARTED_SLUG,
			'parent_url'      => $parent_url,
			'parent_slug'     => bp_docs_get_slug(),
			'screen_function' => array( &$this, 'template_loader' ),
			'position'        => 10
		);

		$sub_nav[] = array(
			'name'            => bp_is_my_profile() ? __( 'Edited By Me', 'bp-docs' ) : sprintf( __( 'Edited By %s', 'bp-docs' ), bp_get_user_firstname() ),
			'slug'            => BP_DOCS_EDITED_SLUG,
			'parent_url'      => $parent_url,
			'parent_slug'     => bp_docs_get_slug(),
			'screen_function' => array( &$this, 'template_loader' ),
			'position'        => 20,
		);

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Utility function for loading component template and hooking content
	 *
	 * @since 1.2
	 * @see self::setup_nav()
	 */
	function template_loader() {
		add_action( 'bp_template_content', array( &$this, 'select_template' ) );
		bp_core_load_template( 'members/single/plugins' );
	}

	/**
	 * Utility function for selecting the correct Docs template to be loaded in the component
	 *
	 * At the moment, this only loads a single template. Logic could be
	 * put here in the future in case more than one template needs to be
	 * displayable on the component page
	 *
	 * @since 1.2
	 */
	function select_template() {
		$template = 'docs-loop.php';
		include bp_docs_locate_template( apply_filters( 'bp_docs_select_template', $template ) );
	}

	/**
	 * Loads the Docs query.
	 *
	 * We do this in order to have some of the info about the current doc throughout the
	 * loading process
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 * @deprecated No longer used since 1.2
	 */
	function do_query() {
		_deprecated_function( __METHOD__, '1.2' );
	}

	/**
	 * Catches page loads, determines what to do, and sends users on their merry way
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 * @todo This needs a ton of cleanup
	 */
	function catch_page_load() {
		global $bp;

		if ( !empty( $_POST['doc-edit-submit'] ) ) {

			check_admin_referer( 'bp_docs_save' );

			$this_doc = new BP_Docs_Query;
			$result = $this_doc->save();

			bp_core_add_message( $result['message'], $result['message_type'] );
			bp_core_redirect( trailingslashit( $result['redirect_url'] ) );
		}

		if ( !empty( $_POST['docs-filter-submit'] ) ) {
			$this->handle_filters();
		}

		// If this is the edit screen, ensure that the user can edit the
		// doc before querying, and redirect if necessary
		if ( !empty( $bp->bp_docs->current_view ) && 'edit' == $bp->bp_docs->current_view ) {
			if ( bp_docs_current_user_can( 'edit' ) ) {
				$doc = bp_docs_get_current_doc();

				// The user can edit, so we check for edit locks
				// Because we're not using WP autosave at the moment, ensure that
				// the lock interval always returns as in process
				add_filter( 'wp_check_post_lock_window', create_function( false, 'return time();' ) );

				$lock = bp_docs_check_post_lock( $doc->ID );

				if ( $lock ) {
					bp_core_add_message( sprintf( __( 'This doc is currently being edited by %s. To prevent overwrites, you cannot edit until that user has finished. Please try again in a few minutes.', 'bp-docs' ), bp_core_get_user_displayname( $lock ) ), 'error' );

					$group_permalink = bp_get_group_permalink( $bp->groups->current_group );
					$doc_slug = $bp->bp_docs->doc_slug;

					// Redirect back to the non-edit view of this document
					bp_core_redirect( $group_permalink . $bp->bp_docs->slug . '/' . $doc_slug );
				}
			} else {
				if ( function_exists( 'bp_core_no_access' ) && !is_user_logged_in() )
					bp_core_no_access();

				// The user does not have edit permission. Redirect.
				bp_core_add_message( __( 'You do not have permission to edit the doc.', 'bp-docs' ), 'error' );

				$group_permalink = bp_get_group_permalink( $bp->groups->current_group );
				$doc_slug = $bp->bp_docs->doc_slug;

				// Redirect back to the non-edit view of this document
				bp_core_redirect( $group_permalink . $bp->bp_docs->slug . '/' . $doc_slug );
			}
		}

		if ( bp_docs_is_doc_create() ) {
			if ( !bp_docs_current_user_can( 'create' ) ) {
				// The user does not have edit permission. Redirect.
				if ( function_exists( 'bp_core_no_access' ) && !is_user_logged_in() )
					bp_core_no_access();

				bp_core_add_message( __( 'You do not have permission to create a Doc in this group.', 'bp-docs' ), 'error' );

				$group_permalink = bp_get_group_permalink( $bp->groups->current_group );

				// Redirect back to the Doc list view
				bp_core_redirect( $group_permalink . $bp->bp_docs->slug . '/' );
			}
		}

		if ( !empty( $bp->bp_docs->current_view ) && 'history' == $bp->bp_docs->current_view ) {
			if ( !bp_docs_current_user_can( 'view_history' ) ) {
				if ( !bp_docs_current_user_can( 'view_history' ) ) {
					// The user does not have edit permission. Redirect.
					if ( function_exists( 'bp_core_no_access' ) && !is_user_logged_in() )
						bp_core_no_access();

					bp_core_add_message( __( 'You do not have permission to view this Doc\'s history.', 'bp-docs' ), 'error' );

					$doc = bp_docs_get_current_doc();

					$redirect = bp_docs_get_doc_link( $doc->ID );

					// Redirect back to the Doc list view
					bp_core_redirect( $redirect );
				}
			}
		}

		// Cancel edit lock
		if ( !empty( $_GET['bpd_action'] ) && $_GET['bpd_action'] == 'cancel_edit_lock' ) {
			// Check the nonce
			check_admin_referer( 'bp_docs_cancel_edit_lock' );

			// Todo: make this part of the perms system
			if ( is_super_admin() || bp_group_is_admin() ) {
				$doc = bp_docs_get_current_doc();

				// Todo: get this into a proper method as well, blech
				delete_post_meta( $doc->ID, '_edit_lock' );

				bp_core_add_message( __( 'Lock successfully removed', 'bp-docs' ) );
				bp_core_redirect( bp_docs_get_doc_link( $doc->ID ) );
			}
		}

		// Cancel edit
		// Have to have a catcher for this so the edit lock can be removed
		if ( !empty( $_GET['bpd_action'] ) && $_GET['bpd_action'] == 'cancel_edit' ) {
			$doc = bp_docs_get_current_doc();

			// Todo: get this into a proper method as well, blech
			delete_post_meta( $doc->ID, '_edit_lock' );

			bp_core_redirect( bp_docs_get_doc_link( $doc->ID ) );
		}

		// Todo: get this into a proper method
		if ( bp_docs_is_doc_read() && ! empty( $_GET['delete'] ) ) {

			check_admin_referer( 'bp_docs_delete' );

			if ( bp_docs_current_user_can( 'manage' ) ) {
				$delete_doc_id = get_queried_object_id();

				if ( bp_docs_trash_doc( $delete_doc_id ) ) {
					bp_core_add_message( __( 'Doc successfully deleted!', 'bp-docs' ) );
				} else {
					bp_core_add_message( __( 'Could not delete doc.', 'bp-docs' ) );
				}
			} else {
				bp_core_add_message( __( 'You do not have permission to delete that doc.', 'bp-docs' ), 'error' );
			}

			bp_core_redirect( home_url( bp_docs_get_slug() ) );
		}
	}

	/**
	 * METHODS RELATED TO DOC COMMENTS
	 */

	/**
	 * Approve all Doc comments
	 *
	 * Docs handles its own comment permissions, so we override WP's value
	 *
	 * @since 1.3.3
	 * @param string $approved
	 * @param array $commentdata
	 * @return string $approved
	 */
	public function approve_doc_comments( $approved, $commentdata ) {
		$post = get_post( $commentdata['comment_post_ID'] );
		if ( bp_docs_get_post_type_name() === $post->post_type ) {
			$approved = 1;
		}

		return $approved;
	}

	/**
	 * Filters the comment_post_direct URL so that the user gets sent back to the true
	 * comment URL after posting
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 *
	 * @param str $location The original location, created by WP
	 * @param obj $comment The new comment object
	 * @return str $location The correct location
	 */
	function comment_post_redirect( $location, $comment ) {
		global $bp;

		// Check to see whether this is a BP Doc
		$post = get_post( $comment->comment_post_ID );

		if ( $bp->bp_docs->post_type_name != $post->post_type )
			return $location;

		$location = bp_docs_get_doc_link( $comment->comment_post_ID ) . '#comment-' . $comment->comment_ID;

		return $location;
	}


	/**
	 * Posts an activity item when a comment is posted to a doc
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 *
	 * @param obj $query The id of the comment that's just been saved
	 * @return int $activity_id The id number of the activity created
	 */
	function post_comment_activity( $comment_id ) {
		global $bp;

		if ( ! bp_is_active( 'activity' ) ) {
			return false;
		}

		if ( empty( $comment_id ) ) {
			return false;
		}

		$comment = get_comment( $comment_id );
		$doc     = !empty( $comment->comment_post_ID ) ? get_post( $comment->comment_post_ID ) : false;

		if ( empty( $doc ) ) {
			return false;
		}

		// Only continue if this is a BP Docs post
		if ( $doc->post_type != bp_docs_get_post_type_name() ) {
			return;
		}

		$doc_id = ! empty( $doc->ID ) ? $doc->ID : false;

		if ( ! $doc_id ) {
			return false;
		}

		// Make sure that BP doesn't record this comment with its native functions
		remove_action( 'comment_post', 'bp_blogs_record_comment', 10, 2 );

		// Until better individual activity item privacy controls are available in BP,
		// comments will only be shown in the activity stream if "Who can read comments on
		// this doc?" is set to "Anyone", "Logged-in Users" or "Group members"
		$doc_settings = get_post_meta( $doc_id, 'bp_docs_settings', true );

		if ( ! empty( $doc_settings['read_comments'] ) && ! in_array( $doc_settings['read_comments'], array( 'anyone', 'loggedin', 'group-members' ) ) ) {
			return false;
		}

		// See if we're associated with a group
		$group_id = bp_docs_get_associated_group_id( $doc_id );

		if ( $group_id ) {
			$component = 'groups';
			$item = $group_id;
		} else {
			$component = bp_docs_get_slug();
			$item = 0;
		}

		// Set the action. Filterable so that other integration pieces can alter it
		$action       = '';
		$commenter    = get_user_by( 'email', $comment->comment_author_email );
		$commenter_id = !empty( $commenter->ID ) ? $commenter->ID : false;

		// Since BP Docs only allows member comments, the following should never happen
		if ( !$commenter_id ) {
			return false;
		}

		$user_link    = bp_core_get_userlink( $commenter_id );
		$doc_url      = bp_docs_get_doc_link( $doc_id );
		$comment_url  = $doc_url . '#comment-' . $comment->comment_ID;
		$comment_link = '<a href="' . $comment_url . '">' . $doc->post_title . '</a>';

		$action = sprintf( __( '%1$s commented on the doc %2$s', 'bp-docs' ), $user_link, $comment_link );

		$action	= apply_filters( 'bp_docs_comment_activity_action', $action, $user_link, $comment_link, $component, $item );

		// Set the type, to be used in activity filtering
		$type = 'bp_doc_comment';

		$hide_sitewide = bp_docs_hide_sitewide_for_doc( $doc_id );

		$args = array(
			'user_id'		=> $commenter_id,
			'action'		=> $action,
			'content'		=> $comment->comment_content,
			'primary_link'		=> $comment_url,
			'component'		=> $component,
			'type'			=> $type,
			'item_id'		=> $item, // Set to the group/user/etc id, for better consistency with other BP components
			'secondary_item_id'	=> $comment_id, // The id of the doc itself. Note: limitations in the BP activity API mean I don't get to store the doc_id, but at least it can be abstracted from the comment_id
			'recorded_time'		=> bp_core_current_time(),
			'hide_sitewide'		=> apply_filters( 'bp_docs_hide_sitewide', $hide_sitewide, $comment, $doc, $item, $component ) // Filtered to allow plugins and integration pieces to dictate
		);

		do_action( 'bp_docs_before_comment_activity_save', $args );

		$activity_id = bp_activity_add( apply_filters( 'bp_docs_comment_activity_args', $args ) );

		do_action( 'bp_docs_after_comment_activity_save', $activity_id, $args );

		return $activity_id;
	}

	/**
	 * Filter the location of the comments.php template
	 *
	 * This function uses a little trick to make sure that the comments.php file can be
	 * overridden by child themes, yet still has a fallback in the plugin folder.
	 *
	 * If you find this annoying, I have provided a filter for your convenience.
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 *
	 * @param str $path The path (STYLESHEETPATH . $file) from comments_template()
	 * @return str The path of the preferred template
	 */
	function comments_template( $path ) {
		if ( ! bp_docs_is_existing_doc() )
			return $path;

		$original_path = $path;

		if ( ! $path = locate_template( 'docs/single/comments.php' ) ) {
			$path = BP_DOCS_INSTALL_PATH . 'includes/templates/docs/single/comments.php';
		}

		return apply_filters( 'bp_docs_comment_template_path', $path, $original_path );
	}

	/**
	 * Prevents comment notification emails from being sent on BP Docs comments
	 *
	 * For the moment, I'm shutting off WP's native email notifications on BP Docs comments.
	 * They are better handled as part of the BP activity stream. This maneuver requires a
	 * trick: when a comment is posted on a BP Doc type post, I hijack the get_option() call
	 * for comments_notify and return 0 (rather than false, which would not stop the real
	 * get_option operation from running).
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 *
	 * @param int $comment_id ID number of the new comment being posted
	 */
	function check_comment_type( $comment_id ) {
		global $bp;

		$comment = get_comment( $comment_id );
		$post = get_post( $comment->comment_post_ID );

		if ( $bp->bp_docs->post_type_name == $post->post_type ) {
			add_filter( 'pre_option_comments_notify', create_function( false, 'return 0;' ) );
		}
	}

	/**
	 * METHODS RELATED TO BUDDYPRESS ACTIVITY
	 */

	/**
	 * Adds BP Docs options to activity filter dropdowns
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function activity_filter_options() {
		?>

		<option value="bp_doc_created"><?php _e( 'Show New Docs', 'bp-docs' ); ?></option>
		<option value="bp_doc_edited"><?php _e( 'Show Doc Edits', 'bp-docs' ); ?></option>
		<option value="bp_doc_comment"><?php _e( 'Show Doc Comments', 'bp-docs' ); ?></option>

		<?php
	}

	/**
	 * Posts an activity item on doc save
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 *
	 * @param obj $query The query object created in BP_Docs_Query and passed to the
	 *     bp_docs_doc_saved filter
	 * @return int $activity_id The id number of the activity created
	 */
	function post_activity( $query ) {
		global $bp;

		// todo: exception for autosave?

		if ( !bp_is_active( 'activity' ) )
			return false;

		$doc_id	= !empty( $query->doc_id ) ? $query->doc_id : false;

		if ( !$doc_id )
			return false;

		$last_editor	= get_post_meta( $doc_id, 'bp_docs_last_editor', true );

		// Throttle 'doc edited' posts. By default, one per user per hour
		if ( !$query->is_new_doc ) {
			// Look for an existing activity item corresponding to this user editing
			// this doc
			$already_args = array(
				'max'		=> 1,
				'sort'		=> 'DESC',
				'show_hidden'	=> 1, // We need to compare against all activity
				'filter'	=> array(
					'user_id'	=> $last_editor,
					'action'	=> 'bp_doc_edited', // BP bug. 'action' is type
					'secondary_id'	=> $doc_id // We don't really care about the item_id for these purposes (it could have been changed)
				),
			);

			$already_activity = bp_activity_get( $already_args );

			// If any activity items are found, compare its date_recorded with time() to
			// see if it's within the allotted throttle time. If so, don't record the
			// activity item
			if ( !empty( $already_activity['activities'] ) ) {
				$date_recorded 	= $already_activity['activities'][0]->date_recorded;
				$drunix 	= strtotime( $date_recorded );
				if ( time() - $drunix <= apply_filters( 'bp_docs_edit_activity_throttle_time', 60*60 ) )
					return;
			}
		}

		$doc = get_post( $doc_id );

		// Set the action. Filterable so that other integration pieces can alter it
		$action 	= '';
		$user_link 	= bp_core_get_userlink( $last_editor );
		$doc_url	= bp_docs_get_doc_link( $doc_id );
		$doc_link	= '<a href="' . $doc_url . '">' . $doc->post_title . '</a>';

		if ( $query->is_new_doc ) {
			$action = sprintf( __( '%1$s created the doc %2$s', 'bp-docs' ), $user_link, $doc_link );
		} else {
			$action = sprintf( __( '%1$s edited the doc %2$s', 'bp-docs' ), $user_link, $doc_link );
		}

		$action	= apply_filters( 'bp_docs_activity_action', $action, $user_link, $doc_link, $query->is_new_doc, $query );

		$hide_sitewide = bp_docs_hide_sitewide_for_doc( $doc_id );

		// Get a canonical name for the component. This is a nightmare because of the way
		// the current component and root slug relate in BP 1.3+
		$component = bp_current_component();

		// Temp
		if ( !$component ) {
			$component = $this->id;
		}

		// This is only temporary! This item business needs to be component-neutral
		$item = isset( $bp->groups->current_group->id ) ? $bp->groups->current_group->id : false;

		// Set the type, to be used in activity filtering
		$type = $query->is_new_doc ? 'bp_doc_created' : 'bp_doc_edited';

		$args = array(
			'user_id'		=> $last_editor,
			'action'		=> $action,
			'primary_link'		=> $doc_url,
			'component'		=> $component,
			'type'			=> $type,
			'item_id'		=> $query->item_id, // Set to the group/user/etc id, for better consistency with other BP components
			'secondary_item_id'	=> $doc_id, // The id of the doc itself
			'recorded_time'		=> bp_core_current_time(),
			'hide_sitewide'		=> apply_filters( 'bp_docs_hide_sitewide', $hide_sitewide, false, $doc, $item, $component ) // Filtered to allow plugins and integration pieces to dictate
		);

		do_action( 'bp_docs_before_activity_save', $args );

		$activity_id = bp_activity_add( apply_filters( 'bp_docs_activity_args', $args ) );

		do_action( 'bp_docs_after_activity_save', $activity_id, $args );

		return $activity_id;
	}

	/**
	 * Delete activity associated with a Doc
	 *
	 * Run on transition_post_status, to catch deletes from all locations
	 *
	 * @since 1.3
	 *
	 * @param string $new_status
	 * @param string $old_status
	 * @param obj WP_Post object
	 */
	public function delete_doc_activity( $new_status, $old_status, $post ) {
		if ( ! bp_is_active( 'activity' ) ) {
			return;
		}

		if ( bp_docs_get_post_type_name() != $post->post_type ) {
			return;
		}

		if ( 'trash' != $new_status ) {
			return;
		}


		$activities = bp_activity_get(
			array(
				'filter' => array(
					'secondary_id' => $post->ID,
					'component' => 'docs',
				),
			)
		);

		foreach ( (array) $activities['activities'] as $activity ) {
			bp_activity_delete( array( 'id' => $activity->id ) );
		}
	}

	/**
	 * MISCELLANEOUS
	 */

	/**
	 * Display the proper permalink for Docs
	 *
	 * This function filters 'post_type_link', which in turn powers get_permalink() and related
	 * functions.
	 *
	 * BuddyPress Docs has a completely flat architecture for URLs, where
	 * parent slugs never appear in the URL (as they do in the case of WP
	 * pages). So we reconstruct the link completely.
	 *
	 * @package BuddyPress Docs
	 * @since 1.1.8
	 *
	 * @param str $link The permalink
	 * @param obj $post The post object
	 * @param bool $leavename
	 * @param bool $sample See get_post_permalink() for an explanation of these two params
	 * @return str $link The filtered permalink
	 */
	function filter_permalinks( $link, $post, $leavename, $sample ) {
		if ( bp_docs_get_post_type_name() == $post->post_type ) {
			$link = trailingslashit( bp_docs_get_archive_link() . $post->post_name );
		}

		return html_entity_decode( $link );
	}

	/**
	 * Repsect disable_blogforum_replies
	 *
	 * BuddyPress allows you to disable activity commenting on items related to blog posts or
	 * to forums, content types that have their own reply/comment mechanisms. Since BuddyPress
	 * Docs are similar in this respect, they should respect this setting as well.
	 *
	 * In the future, I may add a separate toggle for this. I may also build a filter that
	 * redirects the Comment/Reply link so that it goes to the Doc's Comment section, or so that
	 * this setting reflects the individual Doc's can_comment settings. (For now, this would
	 * require too many additional queries.)
	 *
	 * This function filters bp_activity_can_comment, which was introduced in BP 1.5. It is
	 * therefore not backward compatible with BP < 1.5.
	 *
	 * @package BuddyPress_Docs
	 * @since 1.1.17
	 *
	 * @param bool $can_comment Whether the current user can comment. Comes from
	 *             bp_activity_can_comment()
	 * @return bool $can_comment
	 */
	function activity_can_comment( $can_comment ) {
		global $activities_template;

		if ( 'bp_doc_created' == bp_get_activity_action_name() ||
		     'bp_doc_edited' == bp_get_activity_action_name() ||
		     'bp_doc_comment' == bp_get_activity_action_name()
		   ) {
		   	// Flip the 'disable'
			$can_comment = !(bool)$activities_template->disable_blogforum_replies;
		}

		return apply_filters( 'bp_docs_activity_can_comment', $can_comment );
	}

	/**
	 * Handles doc filters from a form post and translates to $_GET arguments before redirect
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function handle_filters() {
		$redirect_url = apply_filters( 'bp_docs_handle_filters', bp_docs_get_item_docs_link() );

		bp_core_redirect( $redirect_url );
	}

	/**
	 * AJAX handler for remove_edit_lock option
	 *
	 * This function is called when a user is editing a Doc and clicks a link to leave the page
	 *
	 * @package BuddyPress Docs
	 * @since 1.1
	 */
	function remove_edit_lock() {
		$doc_id = isset( $_POST['doc_id'] ) ? $_POST['doc_id'] : false;

		if ( !$doc_id )
			return false;

		delete_post_meta( $doc_id, '_edit_lock' );
	}

	/**
	 * Sets the includes URL for use when loading scripts and styles
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function set_includes_url() {
		$this->includes_url = plugins_url() . '/' . BP_DOCS_PLUGIN_SLUG . '/includes/';
	}

	/**
	 * Add a bp-docs class to bp-docs pages
	 *
	 * @since 1.3
	 */
	function body_class( $classes ) {
		if ( bp_docs_is_docs_component() ) {
			$classes[] = 'bp-docs';
		}

		return $classes;
	}

	/**
	 * When on a global directory, get terms for the tag cloud
	 *
	 * @since 1.4
	 */
	public function get_item_terms( $terms ) {
		global $wpdb, $bp;

		// Only on global directories
		if ( ! bp_docs_is_global_directory() ) {
			return $terms;
		}

		// Get list of docs the user has access to
		$item_ids = bp_docs_get_doc_ids_accessible_to_current_user();

		// Pass to wp_get_object_terms()
		$terms = wp_get_object_terms( $item_ids, array( $bp->bp_docs->docs_tag_tax_name ) );

		// Reformat
		$terms_array = array();
		foreach ( $terms as $t ) {
			$terms_array[ $t->slug ] = $t->count;
		}

		unset( $items, $terms );

		return $terms_array;
	}

	public static function filter_type( $types ) {
		$types[] = array(
			'slug' => 'search',
			'title' => __( 'Search', 'bp-docs' ),
			'query_arg' => 's',
		);
		return $types;
	}

	public static function filter_markup() {
		$has_search = ! empty( $_GET['s'] );
		?>
		<div id="docs-filter-section-search" class="docs-filter-section<?php if ( $has_search ) : ?> docs-filter-section-open<?php endif ?>">
			<form action="" method="get">
				<input name="s" value="<?php the_search_query() ?>">
				<input name="search_submit" type="submit" value="<?php _e( 'Search', 'bp-docs' ) ?>" />
			</form>
		</div>
		<?php
	}

	/**
	 * Loads JavaScript
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function enqueue_scripts() {
		wp_register_script( 'bp-docs-js', plugins_url( BP_DOCS_PLUGIN_SLUG . '/includes/js/bp-docs.js' ), array( 'jquery' ) );

		// This is for edit/create scripts
		if ( bp_docs_is_doc_edit()
		     ||
		     bp_docs_is_doc_create()
		     || ( !empty( $this->query->current_view )
		           &&
		           ( 'edit' == $this->query->current_view || 'create' == $this->query->current_view )
		        )
		   ) {
			require_once( ABSPATH . '/wp-admin/includes/post.php' );
			wp_enqueue_script( 'common' );
			wp_enqueue_script( 'jquery-color' );
			wp_enqueue_script( 'editor' );
			wp_enqueue_script( 'utils' );

			wp_register_script( 'bp-docs-idle-js', plugins_url( BP_DOCS_PLUGIN_SLUG . '/includes/js/idle.js' ), array( 'jquery', 'bp-docs-js' ) );
			wp_enqueue_script( 'bp-docs-idle-js' );

			wp_register_script( 'jquery-colorbox', plugins_url( BP_DOCS_PLUGIN_SLUG . '/lib/js/colorbox/jquery.colorbox-min.js' ), array( 'jquery' ) );
			wp_enqueue_script( 'jquery-colorbox' );
			// Edit mode requires bp-docs-js to be dependent on TinyMCE, so we must
			// reregister bp-docs-js with the correct dependencies
			wp_deregister_script( 'bp-docs-js' );
			wp_register_script( 'bp-docs-js', plugins_url( BP_DOCS_PLUGIN_SLUG . '/includes/js/bp-docs.js' ), array( 'jquery', 'editor' ) );

			wp_register_script( 'word-counter', site_url() . '/wp-admin/js/word-count.js', array( 'jquery' ) );

			wp_enqueue_script( 'bp-docs-edit-validation', plugins_url( BP_DOCS_PLUGIN_SLUG . '/includes/js/edit-validation.js' ), array( 'jquery' ) );
		}

		// Only load our JS on the right sorts of pages. Generous to account for
		// different item types
		if ( in_array( BP_DOCS_SLUG, $this->slugstocheck ) || bp_docs_is_single_doc() || bp_docs_is_global_directory() || bp_docs_is_doc_create() ) {
			wp_enqueue_script( 'bp-docs-js' );
			wp_enqueue_script( 'comment-reply' );
			wp_localize_script( 'bp-docs-js', 'bp_docs', array(
				'upload_title' => __( 'Upload File', 'bp-docs' ),
				'upload_button' => __( 'OK', 'bp-docs' ),
				'still_working'	=> __( 'Still working?', 'bp-docs' ),
			) );
		}
	}

	/**
	 * Loads styles
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function enqueue_styles() {
		global $bp;

		// Load the main CSS only on the proper pages
		if ( in_array( BP_DOCS_SLUG, $this->slugstocheck ) || bp_docs_is_docs_component() ) {
			wp_enqueue_style( 'bp-docs-css', $this->includes_url . 'css/screen.css' );
		}

		if ( bp_docs_is_doc_edit() || bp_docs_is_doc_create() ) {
			wp_enqueue_style( 'bp-docs-edit-css', $this->includes_url . 'css/edit.css' );
			wp_enqueue_style( 'thickbox' );
		}
	}

}
