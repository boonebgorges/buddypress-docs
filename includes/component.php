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
	/**
	 * WP_Query object for the docs query.
	 *
	 * @var WP_Query
	 */
	public $doc_query;

	/**
	 * Docs slug.
	 *
	 * @var string
	 */
	public $doc_slug;

	/**
	 * Docs tag tax name.
	 *
	 * @var string
	 */
	public $docs_tag_tax_name;

	/**
	 * History addon object.
	 *
	 * @var BP_Docs_History
	 */
	public $history;

	/**
	 * Attachments addon object.
	 *
	 * @var BP_Docs_Attachments
	 */
	public $attachments;

	/**
	 * Users integration object.
	 *
	 * @var BP_Docs_Users_Integration
	 */
	public $users_integration;

	/**
	 * Groups integration object.
	 *
	 * @var BP_Docs_Groups_Integration
	 */
	public $groups_integration;

	/**
	 * Item type.
	 *
	 * @var string
	 */
	public $item_type;

	/**
	 * Current doc lock.
	 *
	 * @var bool
	 */
	public $current_doc_lock;

	var $submitted_data = array();

	var $post_type_name;
	var $associated_tax_name;
	public $associated_item_tax_name;
	var $access_tax_name;
	var $comment_access_tax_name;

	public $slugstocheck = array();
	var $query;
	var $includes_url;

	var $current_view;
	var $slug_defined_in_wp_config = array();

	/**
	 * Constructor
	 *
	 * @since 1.2
	 */
	function __construct() {
		global $bp;

		parent::start(
			'bp_docs',
			__( 'BuddyPress Docs', 'buddypress-docs' ),
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

		if ( bp_is_active( 'activity' ) ) {
			require( BP_DOCS_INCLUDES_PATH . 'activity.php' );
		}

		add_action( 'bp_parse_query', array( $this, 'set_up_post_query_globals' ) );

		add_action( 'bp_actions', array( &$this, 'catch_page_load' ), 1 );

		$this->attachments = new BP_Docs_Attachments();
//		add_action( 'wp', array( $this, 'setup_attachments' ), 1 );

		// Get submitted form data out of the cookie
		add_action( 'bp_actions', array( $this, 'submitted_form_data' ) );

		/**
		 * Methods related to comment behavior
		 */

		// Redirect to the correct place after a comment
		add_action( 'comment_post_redirect', array( &$this, 'comment_post_redirect' ), 99, 2 );

		// Doc comments are often from trusted members, so approve them pretty often.
		add_action( 'pre_comment_approved', array( $this, 'approve_doc_comments' ), 999, 2 );

		// Filter the location of the comments template to allow it to be included with
		// the plugin
		add_filter( 'comments_template', array( $this, 'comments_template' ) );

		add_filter( 'post_type_link', array( &$this, 'filter_permalinks' ), 10, 4 );

		// Keep comment notifications from being sent
		add_filter( 'comment_post', array( $this, 'check_comment_type' ) );

		// Force comments_open to obey Doc-specific settings.
		add_filter( 'comments_open', array( $this, 'comments_open' ), 10, 2 );

		// Add the Search filter markup
		add_filter( 'bp_docs_filter_types', array( $this, 'filter_type' ) );
		add_filter( 'bp_docs_filter_sections', array( $this, 'filter_markup' ) );

		// Determine whether the directory view is filtered by a keyword search.
		add_filter( 'bp_docs_is_directory_view_filtered', array( $this, 'is_directory_view_filtered' ), 10, 2 );

		/**
		 * MISC
		 */

		add_filter( 'bp_core_get_directory_page_ids', array( $this, 'remove_bp_page' ) );

		// Respect $activities_template->disable_blogforum_replies
		add_filter( 'bp_activity_can_comment',	array( $this, 'activity_can_comment'	) );

		// Add body class
		add_filter( 'bp_get_the_body_class', array( $this, 'body_class' ) );

		// Global directory tags
		add_filter( 'bp_docs_taxonomy_get_item_terms', array( $this, 'get_item_terms' ) );

		add_action( 'bp_docs_init',             array( $this, 'set_includes_url' 	) );
		add_action( 'wp_enqueue_scripts',       array( $this, 'enqueue_scripts' 	) );
		add_action( 'wp_print_styles',          array( $this, 'enqueue_styles' 		) );

		// Set the "last directory viewed" cookie when viewing the main docs directory.
		add_action( 'bp_actions', array( $this, 'set_directory_cookie' ) );

		// Add the parent and child theme names to the body class when on a BP Docs page.
		add_filter( 'body_class', array( $this, 'filter_body_class' ) );
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
	function setup_globals( $args = array() ) {
		global $bp_docs;

		// Set up the $globals array to be passed along to parent::setup_globals()
		$globals = array(
			'slug'                  => bp_docs_get_docs_slug(),
			'root_slug'             => isset( $bp->pages->{$this->id}->slug ) ? $bp->pages->{$this->id}->slug : bp_docs_get_docs_slug(),
			'has_directory'         => false, // Set to false if not required
			'notification_callback' => 'bp_docs_format_notifications',
			'search_string'         => __( 'Search Docs...', 'buddypress-docs' ),
		);

		// Let BP_Component::setup_globals() do its work.
		parent::setup_globals( $globals );

		// Stash tax and post type names in the $bp global for use in template tags
		$this->post_type_name		= $bp_docs->post_type_name;
		$this->associated_item_tax_name = $bp_docs->associated_item_tax_name;
		$this->access_tax_name          = $bp_docs->access_tax_name;
		$this->comment_access_tax_name  = $bp_docs->comment_access_tax_name;

		// This info is loaded here because it needs to happen after BP core globals are
		// set up
		$this->slugstocheck 	= bp_action_variables() ? bp_action_variables() : array();
		$this->slugstocheck[] 	= bp_current_component();
		$this->slugstocheck[] 	= bp_current_action();
	}

	/**
	 * Sets up globals that depend on BP's query parsing.
	 *
	 * @since 2.2.0 Broken out from setup_globals() to support load order in BuddyPress 12.0.
	 *
	 * @return void
	 */
	public function set_up_post_query_globals() {
		$this->set_current_item_type();
		$this->set_current_view();
	}

	/**
	 * Sets up Docs menu under My Account toolbar
	 *
	 * @since 1.3
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {
		global $bp;

		$wp_admin_nav = array();

		if ( is_user_logged_in() ) {

			$title = bp_docs_get_user_tab_name();

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
				'title'  => __( 'Started By Me', 'buddypress-docs' ),
				'href'   => bp_docs_get_mydocs_started_link(),
			);

			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-edited',
				'title'  => __( 'Edited By Me', 'buddypress-docs' ),
				'href'   => bp_docs_get_mydocs_edited_link(),
			);

			$wp_admin_nav[] = array(
				'parent' => 'my-account-' . $this->id,
				'id'     => 'my-account-' . $this->id . '-create',
				'title'  => __( 'Create New Doc', 'buddypress-docs' ),
				'href'   => bp_docs_get_create_link(),
			);

		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Get previously submitted form data out of the cookie, and stash.
	 *
	 * @since 1.8
	 */
	public function submitted_form_data() {
		if ( isset( $_COOKIE['bp-docs-submit-data'] ) ) {
			$this->submitted_data = json_decode( stripslashes( $_COOKIE['bp-docs-submit-data'] ) );
			setcookie( 'bp-docs-submit-data', '', time() - 24*60*60, '/' );
		}
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
	function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		$main_nav = array(
			'name' 		      => bp_docs_get_user_tab_name(),

			// Disabled count for now. See https://github.com/boonebgorges/buddypress-docs/issues/261
			//'name' 		      => sprintf( __( 'Docs <span>%d</span>', 'bp-docs' ), bp_docs_get_doc_count( bp_displayed_user_id(), 'user' ) ),
			'slug' 		      => bp_docs_get_docs_slug(),
			'position' 	      => 80,
			'screen_function'     => array( &$this, 'template_loader' ),
			'default_subnav_slug' => BP_DOCS_STARTED_SLUG
		);

		$parent_url = bp_docs_get_user_docs_url( bp_displayed_user_id() );

		$mydocs_label = bp_is_my_profile() ? __( 'My Docs ', 'buddypress-docs' ) : sprintf( __( '%s&#8217;s Docs', 'buddypress-docs' ), bp_get_user_firstname( bp_get_displayed_user_fullname() ) );

		$sub_nav[] = array(
			'name'            => bp_is_my_profile() ? __( 'Started By Me', 'buddypress-docs' ) : sprintf( __( 'Started By %s', 'buddypress-docs' ), bp_get_user_firstname() ),
			'slug'            => BP_DOCS_STARTED_SLUG,
			'parent_url'      => $parent_url,
			'parent_slug'     => bp_docs_get_docs_slug(),
			'screen_function' => array( &$this, 'template_loader' ),
			'position'        => 10
		);

		$sub_nav[] = array(
			'name'            => bp_is_my_profile() ? __( 'Edited By Me', 'buddypress-docs' ) : sprintf( __( 'Edited By %s', 'buddypress-docs' ), bp_get_user_firstname() ),
			'slug'            => BP_DOCS_EDITED_SLUG,
			'parent_url'      => $parent_url,
			'parent_slug'     => bp_docs_get_docs_slug(),
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
	 * @since 1.0-beta
	 * @deprecated No longer used since 1.2
	 */
	function do_query() {
		_deprecated_function( __METHOD__, '1.2' );
	}

	/**
	 * Catches page loads, determines what to do, and sends users on their merry way
	 *
	 * @since 1.0-beta
	 * @todo This needs a ton of cleanup
	 */
	function catch_page_load() {
		global $bp;

		if ( ! empty( $_POST['doc-edit-submit'] ) || ! empty( $_POST['doc-edit-submit-continue'] ) ) {

			$doc_id = false;
			if ( isset( $_POST['doc-id'] ) ) {
				$doc_id = absint( $_POST['doc-id'] );
			}

			$current_doc = bp_docs_get_current_doc();
			if ( $current_doc ) {
				// Don't allow editing if there's a mismatch.
				if ( $doc_id && $doc_id !== $current_doc->ID ) {
					return;
				}

				$doc_id = $current_doc->ID;
			}

			// Legacy.
			check_admin_referer( 'bp_docs_save' );

			if ( $doc_id ) {
				check_admin_referer( 'bp_docs_edit_' . (string) $doc_id, 'bp_docs_edit_nonce' );
			}

			$result = bp_docs_save_doc_via_post();

			bp_core_add_message( $result['message'], $result['message_type'] );
			bp_core_redirect( trailingslashit( $result['redirect_url'] ) );
		}

		if ( !empty( $_POST['docs-filter-submit'] ) ) {
			$this->handle_filters();
		}

		// If this is the edit screen, ensure that the user can edit the
		// doc before querying, and redirect if necessary
		if ( bp_docs_is_doc_edit() ) {
			if ( current_user_can( 'bp_docs_edit' ) ) {
				$doc = bp_docs_get_current_doc();

				// The user can edit, so we check for edit locks
				// Because we're not using WP autosave at the moment, ensure that
				// the lock interval always returns as in process
				add_filter( 'wp_check_post_lock_window', 'time' );

				if ( $doc ) {
					$lock = bp_docs_check_post_lock( $doc->ID );

					if ( $lock ) {
						bp_core_add_message( sprintf( __( 'This doc is currently being edited by %s. To prevent overwrites, you cannot edit until that user has finished. Please try again in a few minutes.', 'buddypress-docs' ), bp_core_get_user_displayname( $lock ) ), 'error' );

						// Redirect back to the non-edit view of this document
						bp_core_redirect( bp_docs_get_doc_link( $doc->ID ) );
						bp_core_redirect( $group_permalink . $bp->bp_docs->slug . '/' . $doc_slug );
					}
				}
			} else {
				if ( function_exists( 'bp_core_no_access' ) && !is_user_logged_in() ) {
					bp_core_no_access();
				}

				// The user does not have edit permission. Redirect.
				bp_core_add_message( __( 'You do not have permission to edit the doc.', 'buddypress-docs' ), 'error' );

				// Redirect back to the non-edit view of this document
				bp_core_redirect( bp_docs_get_doc_link( $doc->ID ) );
				die();
			}
		}

		if ( bp_docs_is_doc_create() ) {
			if ( ! current_user_can( 'bp_docs_create' ) ) {
				// The user does not have edit permission. Redirect.
				if ( function_exists( 'bp_core_no_access' ) && !is_user_logged_in() )
					bp_core_no_access();

				bp_core_add_message( __( 'You do not have permission to create a Doc in this group.', 'buddypress-docs' ), 'error' );

				// Redirect back to the Doc list view
				bp_core_redirect( bp_docs_get_group_docs_url( groups_get_current_group() ) );
				die();
			}
		}

		if ( !empty( $bp->bp_docs->current_view ) && 'history' == $bp->bp_docs->current_view ) {
			if ( ! current_user_can( 'bp_docs_view_history' ) ) {
				// The user does not have edit permission. Redirect.
				if ( function_exists( 'bp_core_no_access' ) && !is_user_logged_in() )
					bp_core_no_access();

				bp_core_add_message( __( 'You do not have permission to view this Doc\'s history.', 'buddypress-docs' ), 'error' );

				$doc = bp_docs_get_current_doc();

				$redirect = bp_docs_get_doc_link( $doc->ID );

				// Redirect back to the Doc list view
				bp_core_redirect( $redirect );
				die();
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
				delete_post_meta( $doc->ID, '_bp_docs_last_pinged' );

				bp_core_add_message( __( 'Lock successfully removed', 'buddypress-docs' ) );
				bp_core_redirect( bp_docs_get_doc_link( $doc->ID ) );
				die();
			}
		}

		// Cancel edit
		// Have to have a catcher for this so the edit lock can be removed
		if ( !empty( $_GET['bpd_action'] ) && $_GET['bpd_action'] == 'cancel_edit' ) {
			$doc = bp_docs_get_current_doc();

			// Todo: get this into a proper method as well, blech
			delete_post_meta( $doc->ID, '_bp_docs_last_pinged' );

			bp_core_redirect( bp_docs_get_doc_link( $doc->ID ) );
			die();
		}

		// Todo: get this into a proper method
		if ( bp_docs_is_doc_read() && ! empty( $_GET['delete'] ) ) {

			check_admin_referer( 'bp_docs_delete' );

			if ( current_user_can( 'bp_docs_manage' ) ) {
				$force_delete = false;
				if ( ! empty( $_GET['force_delete'] ) ) {
					$force_delete = true;
				}

				$delete_doc_id = get_queried_object_id();

				if ( bp_docs_trash_doc( $delete_doc_id, $force_delete ) ) {
					bp_core_add_message( __( 'Doc successfully deleted!', 'buddypress-docs' ) );
				} else {
					bp_core_add_message( __( 'Could not delete doc.', 'buddypress-docs' ) );
				}
			} else {
				bp_core_add_message( __( 'You do not have permission to delete that doc.', 'buddypress-docs' ), 'error' );
			}

			// Send the user back to the most recently viewed directory if possible.
			if ( isset( $_COOKIE[ 'bp-docs-last-docs-directory' ] ) && filter_var( $_COOKIE[ 'bp-docs-last-docs-directory' ], FILTER_VALIDATE_URL) ) {
				$delete_redirect = $_COOKIE[ 'bp-docs-last-docs-directory' ];
			} else {
				$delete_redirect = home_url( bp_docs_get_docs_slug() );
			}

			bp_core_redirect( $delete_redirect );
			die();
		}

		if ( bp_docs_is_doc_read() && ! empty( $_GET['untrash'] ) && ! empty( $_GET['doc_id'] ) ) {
			check_admin_referer( 'bp_docs_untrash' );

			$untrash_doc_id = absint( $_GET['doc_id'] );

			if ( current_user_can( 'bp_docs_manage', $untrash_doc_id ) ) {
				if ( bp_docs_untrash_doc( $untrash_doc_id ) ) {
					bp_core_add_message( __( 'Doc successfully removed from Trash!', 'buddypress-docs' ) );
				} else {
					bp_core_add_message( __( 'Could not remove Doc from Trash.', 'buddypress-docs' ) );
				}
			} else {
				bp_core_add_message( __( 'You do not have permission to remove that Doc from the Trash.', 'buddypress-docs' ), 'error' );
			}

			bp_core_redirect( bp_docs_get_doc_link( $untrash_doc_id ) );
			die();
		}

		if ( bp_docs_is_doc_read() && ! empty( $_GET[ BP_DOCS_UNLINK_FROM_GROUP_SLUG ] ) && ! empty( $_GET['doc_id'] ) && ! empty( $_GET['group_id'] ) ) {
			check_admin_referer( 'bp_docs_unlink_from_group' );

			$unlink_doc_id = absint( $_GET['doc_id'] );
			$unlink_group_id = absint( $_GET['group_id'] );

			if ( current_user_can( 'bp_docs_dissociate_from_group', $unlink_group_id ) ) {
				if ( bp_docs_unlink_from_group( $unlink_doc_id, $unlink_group_id ) ) {
					bp_core_add_message( __( 'Doc successfully removed from the group', 'buddypress-docs' ) );
				} else {
					bp_core_add_message( __( 'Could not remove Doc from the group.', 'buddypress-docs' ) );
				}
			} else {
				bp_core_add_message( __( 'You do not have permission to remove that Doc from this group.', 'buddypress-docs' ), 'error' );
			}
			bp_core_redirect( bp_docs_get_group_docs_url( $unlink_group_id ) );
			die();
		}
	}

	/**
	 * METHODS RELATED TO DOC COMMENTS
	 */

	/**
	 * Approve Doc comments as necessary.
	 *
	 * Docs handles its own comment permissions,
	 * so we override WP's value in some instances.
	 *
	 * @since 1.3.3
	 * @param mixed $approved The approval status. Values: 1, 0, 'spam' or WP_Error.
	 * @param array $commentdata Comment data.
	 * @return mixed $approved
	 */
	public function approve_doc_comments( $approved, $commentdata ) {
		$post = get_post( $commentdata['comment_post_ID'] );

		/**
		 * Maybe force comment approval. Only act if:
		 * the approval status is currently 0 (not approved, but not spam or a WP_Error)
		 * and the comment is on a BP Doc,
		 * the user is logged in
		 * the user can post comments on this particular doc.
		 */
		if ( $approved === 0
			&& bp_docs_get_post_type_name() === $post->post_type
			&& bp_loggedin_user_id()
			&& bp_docs_user_can( 'post_comments', bp_loggedin_user_id(), $post->ID )
		) {
			$approved = 1;
		}

		return $approved;
	}

	/**
	 * Filters the comment_post_direct URL so that the user gets sent back to the true
	 * comment URL after posting
	 *
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
	 * @since 1.0-beta
	 *
	 * @param obj $comment_id The id of the comment that's just been saved.
	 * @return int $activity_id The id number of the activity created
	 */
	function post_comment_activity( $comment_id ) {
		return bp_docs_post_comment_activity( $comment_id );
	}

	/**
	 * Filter the location of the comments.php template
	 *
	 * This function uses a little trick to make sure that the comments.php file can be
	 * overridden by child themes, yet still has a fallback in the plugin folder.
	 *
	 * If you find this annoying, I have provided a filter for your convenience.
	 *
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
	 * @since 1.0-beta
	 *
	 * @param int $comment_id ID number of the new comment being posted
	 */
	function check_comment_type( $comment_id ) {
		global $bp;

		$comment = get_comment( $comment_id );
		$post = get_post( $comment->comment_post_ID );

		if ( $bp->bp_docs->post_type_name == $post->post_type ) {
			add_filter( 'pre_option_comments_notify', '__return_zero' );
		}
	}

	/**
	 * Force comments_open status to obey Doc-specific settings.
	 *
	 * @since 1.8.6
	 *
	 * @param bool $open    Whether the current post is open for comments.
	 * @param int  $post_id ID of the post.
	 * @return bool
	 */
	public function comments_open( $open, $post_id ) {
		$post = get_post( $post_id );
		if ( ! ( $post instanceof WP_Post ) || bp_docs_get_post_type_name() !== $post->post_type ) {
			return $open;
		}

		return current_user_can( 'bp_docs_post_comments', $post_id );
	}

	/**
	 * Adds BP Docs options to activity filter dropdowns
	 *
	 * @since 1.0-beta
	 */
	function activity_filter_options() {
		return bp_docs_activity_filter_options();
	}

	/**
	 * Posts an activity item on doc save
	 *
	 * @since 1.0-beta
	 *
	 * @param obj $query The query object created in BP_Docs_Query and passed to the
	 *     bp_docs_doc_saved filter
	 * @return int $activity_id The id number of the activity created
	 */
	function post_activity( $query ) {
		return bp_docs_post_activity( $query );
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
		return bp_docs_delete_doc_activity( $new_status, $old_status, $post );
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
	 * @since 1.0-beta
	 */
	function handle_filters() {
		$redirect_url = apply_filters( 'bp_docs_handle_filters', bp_docs_get_item_docs_link() );

		bp_core_redirect( $redirect_url );
	}

	/**
	 * Sets the includes URL for use when loading scripts and styles
	 *
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

		if ( bp_docs_is_doc_trashed() ) {
			$classes[] = 'trashed-doc';
		}

		if ( wp_is_mobile() ) {
			$classes[] = 'mobile';
		}

		if ( bp_docs_is_doc_edit() ) {
			$classes[] = 'bp-docs-edit';
		}

		if ( bp_docs_is_doc_create() ) {
			$classes[] = 'bp-docs-create';
		}

		return array_unique( $classes );
	}

	/**
	 * When on a global directory, get terms for the tag cloud
	 *
	 * @since 1.4
	 */
	public function get_item_terms( $terms ) {
		global $wpdb, $bp;

		// Only on global directory and mygroups view
		if ( ! bp_docs_is_global_directory() && ! bp_docs_is_mygroups_directory() ) {
			return $terms;
		}

		// Get list of docs the user has access to
		$item_ids = bp_docs_get_doc_ids_accessible_to_current_user();

		// Pass to wp_get_object_terms()
		$terms = wp_get_object_terms( $item_ids, array( $bp->bp_docs->docs_tag_tax_name ) );

		// Reformat
		$terms_array = array();
		foreach ( $terms as $t ) {
			$terms_array[ $t->slug ] = array(
				'count' => $t->count,
				'name' => $t->name,
			);
		}

		unset( $item_ids, $terms );

		return $terms_array;
	}

	public static function filter_type( $types ) {
		$types[] = array(
			'slug' => 'search',
			'title' => __( 'Search', 'buddypress-docs' ),
			'query_arg' => 's',
		);
		return $types;
	}

	public static function filter_markup() {
		$has_search = ! empty( $_GET['s'] );

		$form_action = bp_get_requested_url();
		$form_action = remove_query_arg(
			array(
				'search_submit',
				's',
				'paged',
			),
			$form_action
		);
		$form_action = preg_replace( '|page/[0-9]+/|', '', $form_action );

		?>
		<div id="docs-filter-section-search" class="docs-filter-section<?php if ( $has_search ) : ?> docs-filter-section-open<?php endif ?>">
			<form action="<?php echo esc_url( $form_action ); ?>" method="get">
				<label for="docs-search" class="screen-reader-text"><?php echo esc_html_e( 'Search', 'buddypress-docs' ); ?></label>
				<input id="docs-search" name="s" value="<?php the_search_query() ?>">
				<input name="search_submit" type="submit" value="<?php _e( 'Search', 'buddypress-docs' ) ?>" />
				<?php do_action( 'bp_docs_directory_filter_search_form' ) ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Determine whether the directory view is filtered by a keyword search.
	 *
	 * @since 1.9.0
	 *
	 * @param bool  $is_filtered Is the current directory view filtered?
 	 * @param array $exclude Array of filter types to ignore.
	 *
	 * @return bool $is_filtered
	 */
	public function is_directory_view_filtered( $is_filtered, $exclude ) {
		// If this filter is excluded, stop now.
		if ( in_array( 's', $exclude ) ) {
			return $is_filtered;
		}

		if ( ! empty( $_GET['s'] ) ) {
			$is_filtered = true;
		}
	    return $is_filtered;
	}

	/**
	 * Loads JavaScript
	 *
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
			wp_register_script(
				'bp-docs-js',
				plugins_url( BP_DOCS_PLUGIN_SLUG . '/includes/js/bp-docs.js' ),
				array( 'jquery', 'editor' ),
				'',
				true
			);

			wp_register_script( 'word-counter', site_url() . '/wp-admin/js/word-count.js', array( 'jquery' ) );

			wp_enqueue_script( 'bp-docs-edit-validation', plugins_url( BP_DOCS_PLUGIN_SLUG . '/includes/js/edit-validation.js' ), array( 'jquery', 'bp-docs-js' ) );
		}

		// Only load our JS on the right sorts of pages. Generous to account for
		// different item types
		if ( in_array( bp_docs_get_docs_slug(), $this->slugstocheck ) || bp_docs_is_single_doc() || bp_docs_is_global_directory() || bp_docs_is_mygroups_directory() || bp_docs_is_group_docs() || bp_docs_is_doc_create() || bp_docs_is_user_docs() ) {
			wp_enqueue_script( 'bp-docs-js' );
			wp_enqueue_script( 'heartbeat' );
			wp_enqueue_script( 'comment-reply' );

			$submitted_data = isset( buddypress()->bp_docs->submitted_data ) ? buddypress()->bp_docs->submitted_data : null;

			$strings = array(
				'and_x_more'        => __( 'and %d more', 'buddypress-docs' ),
				'failed_submission' => ! empty( $submitted_data ) ? 1 : 0,
				'show_all_tags'     => __( 'show all tags', 'buddypress-docs' ),
				'show_fewer_tags'   => __( 'show fewer tags', 'buddypress-docs' ),
				'still_working'	    => __( 'Still working?', 'buddypress-docs' ),
				'upload_title'      => __( 'Upload File', 'buddypress-docs' ),
				'upload_button'     => __( 'OK', 'buddypress-docs' ),
			);

			if ( bp_docs_is_doc_edit() ) {
				$strings['pulse'] = bp_docs_heartbeat_pulse();
			}
			wp_localize_script( 'bp-docs-js', 'bp_docs', $strings );

			$config = [
				'tagCloudCount' => bp_docs_get_tags_truncate_count(),
			];

			wp_add_inline_script(
				'bp-docs-js',
				'const bpDocsConfig = ' . json_encode( $config ) . ';',
				'before'
			);

			do_action( 'bp_docs_enqueue_scripts' );
		}
	}

	/**
	 * Loads styles
	 *
	 * @since 1.0-beta
	 */
	function enqueue_styles() {
		global $bp;

		// Load the main CSS only on the proper pages
		if ( in_array( bp_docs_get_docs_slug(), $this->slugstocheck ) || bp_docs_is_docs_component() ) {
			if ( is_rtl() ) {
				wp_enqueue_style( 'bp-docs-css', $this->includes_url . 'css-rtl/screen.css' );
			} else {
				wp_enqueue_style( 'bp-docs-css', $this->includes_url . 'css/screen.css' );
			}
		}

		if ( bp_docs_is_doc_edit() || bp_docs_is_doc_create() ) {
			if ( is_rtl() ) {
				wp_enqueue_style( 'bp-docs-edit-css', $this->includes_url . 'css-rtl/edit.css' );
			} else {
				wp_enqueue_style( 'bp-docs-edit-css', $this->includes_url . 'css/edit.css' );
			}
			wp_enqueue_style( 'thickbox' );
		}
	}

	/**
	 * Renew the last directory cookie if the user is viewing the main docs library.
	 *
	 * @since 1.9.0
	 */
	public function	set_directory_cookie() {
		global $wp;

		if ( bp_docs_is_global_directory() ) {
			bp_docs_set_last_docs_directory_cookie();
		}
	}

	/**
	 * Add the parent and child theme names to the body class when on a BP Docs page.
	 *
	 * @since 1.9.0
	 *
	 * @param array $classes An array of body classes.
	 */
	public function filter_body_class( $classes ) {
		if ( bp_docs_is_docs_component() ) {
			$classes[] = 'bp-docs-body-theme-' . get_stylesheet();
			$classes[] = 'bp-docs-body-theme-' . get_template();
		}
		return $classes;
	}
}
