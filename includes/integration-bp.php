<?php

class BP_Docs_BP_Integration {
	var $includes_url;
	var $slugstocheck;

	/**
	 * PHP 4 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function bp_docs_bp_integration() {
		$this->__construct();
	}

	/**
	 * PHP 5 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function __construct() {
		add_action( 'bp_init', 			array( $this, 'do_query' 		), 90 );

		add_action( 'bp_setup_globals', 	array( $this, 'setup_globals' 		) );

		if ( bp_is_active( 'groups' ) ) {
			require_once( BP_DOCS_INCLUDES_PATH . 'integration-groups.php' );
			$this->groups_integration = new BP_Docs_Groups_Integration;
		}

		add_action( 'wp', 			array( $this, 'catch_page_load' 	), 1 );

		add_action( 'comment_post_redirect', 	array( $this, 'comment_post_redirect' 	), 99, 2 );

		// Add BP Docs activity types to the activity filter dropdown
		$dropdowns = apply_filters( 'bp_docs_activity_filter_locations', array(
			'bp_activity_filter_options',
			'bp_group_activity_filter_options',
			'bp_member_activity_filter_options'
		) );
		foreach( $dropdowns as $hook ) {
			add_action( $hook, array( $this, 'activity_filter_options' ) );
		}

		// Hook the create/edit activity function
		add_action( 'bp_docs_doc_saved',	array( $this, 'post_activity' 		) );

		// Doc comments are always from trusted members (for the moment), so approve them
		add_action( 'pre_comment_approved',	create_function( '', 'return 1;' 	), 999 );

		// Hook the doc comment activity function
		add_action( 'comment_post',		array( $this, 'post_comment_activity'	), 8 );

		// Filter the location of the comments template to allow it to be included with
		// the plugin
		add_filter( 'comments_template',	array( $this, 'comments_template'	) );

		// Keep comment notifications from being sent
		add_filter( 'comment_post', 		array( $this, 'check_comment_type' 	) );

		// Make sure that comment links are correct. Can't use $wp_rewrite bc of assoc items
		add_filter( 'post_type_link',		array( $this, 'filter_permalinks'	), 10, 4 );
		
		// Respect $activities_template->disable_blogforum_replies
		add_filter( 'bp_activity_can_comment',	array( $this, 'activity_can_comment'	) );

		// AJAX handler for removing the edit lock when a user clicks away from Edit mode
		add_action( 'wp_ajax_remove_edit_lock', array( $this, 'remove_edit_lock'        ) );

		add_action( 'bp_loaded', 		array( $this, 'set_includes_url' 	) );
		add_action( 'init', 			array( $this, 'enqueue_scripts' 	) );
		add_action( 'wp_print_styles', 		array( $this, 'enqueue_styles' 		) );
	}

	/**
	 * Loads the Docs query.
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function do_query() {
		$this->query = new BP_Docs_Query;
	}

	/**
	 * Stores some handy information in the $bp global
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
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
	 * @since 1.0-beta
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
		if ( !empty( $bp->bp_docs->current_view ) && 'edit' == $bp->bp_docs->current_view ) {
			if ( bp_docs_current_user_can( 'edit' ) ) {
				$doc = bp_docs_get_current_doc();

				// The user can edit, so we check for edit locks
				// Because we're not using WP autosave at the moment, ensure that
				// the lock interval always returns as in process
				add_filter( 'wp_check_post_lock_window', create_function( false, 'return time();' ) );

				$lock = wp_check_post_lock( $doc->ID );

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

		if ( !empty( $bp->bp_docs->current_view ) && 'create' == $bp->bp_docs->current_view ) {
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
		if ( $bp->bp_docs->current_view == 'delete' ) {
			check_admin_referer( 'bp_docs_delete' );

			if ( bp_docs_current_user_can( 'manage' ) ) {

				$the_doc_args = array(
					'name' 		=> $bp->action_variables[0],
					'post_type' 	=> $bp->bp_docs->post_type_name
				);

				$the_docs = get_posts( $the_doc_args );
				$doc_id = $the_docs[0]->ID;

				do_action( 'bp_docs_before_doc_delete', $doc_id );

				$delete_args = array(
					'ID'		=> $doc_id,
					'post_status'	=> 'trash'
				);

				wp_update_post( $delete_args );

				do_action( 'bp_docs_doc_deleted', $delete_args );

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
	 * @since 1.0-beta
	 */
	function handle_filters() {
		$redirect_url = apply_filters( 'bp_docs_handle_filters', bp_docs_get_item_docs_link() );

		bp_core_redirect( $redirect_url );
	}

	/**
	 * Sets the includes URL for use when loading scripts and styles
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function set_includes_url() {
		$this->includes_url = plugins_url() . '/buddypress-docs/includes/';
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
	 * Adds BP Docs options to activity filter dropdowns
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function activity_filter_options() {
		?>

		<option value="bp_doc_created"><?php _e( 'Show New Docs', 'buddypress' ); ?></option>
		<option value="bp_doc_edited"><?php _e( 'Show Doc Edits', 'buddypress' ); ?></option>
		<option value="bp_doc_comment"><?php _e( 'Show Doc Comments', 'buddypress' ); ?></option>

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

		// Get a canonical name for the component. This is a nightmare because of the way
		// the current component and root slug relate in BP 1.3+
		if ( function_exists( 'bp_is_current_component' ) ) {
			foreach ( $bp->active_components as $comp => $value ) {
				if ( bp_is_current_component( $comp ) ) {
					$component = $comp;
					break;
				}
			}
		} else {
			$component = bp_current_component();
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
			'hide_sitewide'		=> apply_filters( 'bp_docs_hide_sitewide', false, false, $doc, $item, $component ) // Filtered to allow plugins and integration pieces to dictate
		);

		do_action( 'bp_docs_before_activity_save', $args );

		$activity_id = bp_activity_add( apply_filters( 'bp_docs_activity_args', $args ) );

		do_action( 'bp_docs_after_activity_save', $activity_id, $args );

		return $activity_id;
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

		if ( !bp_is_active( 'activity' ) )
			return false;

		if ( empty( $comment_id ) )
			return false;

		$comment 	= get_comment( $comment_id );
		$doc 		= !empty( $comment->comment_post_ID ) ? get_post( $comment->comment_post_ID ) : false;

		if ( empty( $doc ) )
			return false;

		// Only continue if this is a BP Docs post
		if ( $doc->post_type != $bp->bp_docs->post_type_name )
			return;

		$doc_id 	= !empty( $doc->ID ) ? $doc->ID : false;

		if ( !$doc_id )
			return false;

		// Make sure that BP doesn't record this comment with its native functions
		remove_action( 'comment_post', 'bp_blogs_record_comment', 10, 2 );

		// Until better individual activity item privacy controls are available in BP,
		// comments will only be shown in the activity stream if "Who can read comments on
		// this doc?" is set to "Anyone" or "Group members"
		$doc_settings	= get_post_meta( $doc_id, 'bp_docs_settings', true );

		if ( !empty( $doc_settings['read_comments'] ) && ( 'admins-mods' == $doc_settings['read_comments'] || 'no-one' == $doc_settings['read_comments'] ) )
			return false;

		// Get the associated item for this doc. Todo: abstract to standalone function
		$items = wp_get_post_terms( $doc_id, $bp->bp_docs->associated_item_tax_name );

		// It's possible that there will be more than one item; for now, post only to the
		// first one. Todo: make this extensible
		$item = !empty( $items[0]->name ) ? $items[0]->name : false;

		// From the item, we can obtain the component (the parent tax of the item tax)
		if ( !empty( $items[0]->parent ) ) {
			$parent = get_term( (int)$items[0]->parent, $bp->bp_docs->associated_item_tax_name );

			// For some reason, I named them singularly. So we have to canonicalize
			switch ( $parent->slug ) {
				case 'user' :
					$component = 'profile';
					break;

				case 'group' :
				default	:
					$component = 'groups';
					break;
			}
		}

		// Set the action. Filterable so that other integration pieces can alter it
		$action 	= '';
		$commenter	= get_user_by( 'email', $comment->comment_author_email );
		$commenter_id	= !empty( $commenter->ID ) ? $commenter->ID : false;

		// Since BP Docs only allows member comments, the following should never happen
		if ( !$commenter_id )
			return false;

		$user_link 	= bp_core_get_userlink( $commenter_id );
		$doc_url	= bp_docs_get_doc_link( $doc_id );
		$comment_url	= $doc_url . '#comment-' . $comment->comment_ID;
		$comment_link	= '<a href="' . $comment_url . '">' . $doc->post_title . '</a>';

		$action = sprintf( __( '%1$s commented on the doc %2$s', 'bp-docs' ), $user_link, $comment_link );

		$action	= apply_filters( 'bp_docs_comment_activity_action', $action, $user_link, $comment_link, $component, $item );

		// Set the type, to be used in activity filtering
		$type = 'bp_doc_comment';

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
			'hide_sitewide'		=> apply_filters( 'bp_docs_hide_sitewide', false, $comment, $doc, $item, $component ) // Filtered to allow plugins and integration pieces to dictate
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
		if ( !bp_docs_is_bp_docs_page() )
			return $path;

		$original_path = $path;

		if ( !file_exists( $path ) ) {
			$file = str_replace( STYLESHEETPATH, '', $path );

			if ( file_exists( TEMPLATEPATH . $file ) )
				$path = TEMPLATEPATH .  $file;
			else
				$path = BP_DOCS_INSTALL_PATH . 'includes/templates' . $file;
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
	 * Display the proper permalink for Docs
	 *
	 * This function filters 'post_type_link', which in turn powers get_permalink() and related
	 * functions.
	 *
	 * In brief, the purpose is to make sure that Doc permalinks point to the proper place.
	 * Ideally I would use a rewrite rule to accomplish this, but it's impossible to write
	 * regex that will be able to tell which group/user a Doc should be associated with.
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
		global $bp;

		if ( $bp->bp_docs->post_type_name == $post->post_type ) {
			$link = bp_docs_get_doc_link( $post->ID );
		}

		return $link;
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
	 * Loads JavaScript
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function enqueue_scripts() {
		wp_register_script( 'bp-docs-js', plugins_url( 'buddypress-docs/includes/js/bp-docs.js' ), array( 'jquery' ) );

		// This is for edit/create scripts
		if ( !empty( $this->query->current_view ) && ( 'edit' == $this->query->current_view || 'create' == $this->query->current_view ) ) {
			require_once( ABSPATH . '/wp-admin/includes/post.php' );
			wp_enqueue_script( 'common' );
			wp_enqueue_script( 'jquery-color' );
			wp_enqueue_script( 'editor' );
			if ( function_exists( 'add_thickbox' ) )
				add_thickbox();
			wp_enqueue_script( 'utils' );
			wp_enqueue_script( 'autosave' );

			wp_register_script( 'bp-docs-idle-js', plugins_url( 'buddypress-docs/includes/js/idle.js' ), array( 'jquery', 'bp-docs-js' ) );
			wp_enqueue_script( 'bp-docs-idle-js' );

			// Edit mode requires bp-docs-js to be dependent on TinyMCE, so we must
			// reregister bp-docs-js with the correct dependencies
			wp_deregister_script( 'bp-docs-js' );
			wp_register_script( 'bp-docs-js', plugins_url( 'buddypress-docs/includes/js/bp-docs.js' ), array( 'jquery', 'editor' ) );

			wp_register_script( 'word-counter', site_url() . '/wp-admin/js/word-count.js', array( 'jquery' ) );
		}

		// Only load our JS on the right sorts of pages. Generous to account for
		// different item types
		if ( in_array( BP_DOCS_SLUG, $this->slugstocheck ) ) {
			wp_enqueue_script( 'bp-docs-js' );
			wp_enqueue_script( 'comment-reply' );
			wp_localize_script( 'bp-docs-js', 'bp_docs', array(
				'still_working'		=> __( 'Still working?', 'bp-docs' )
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
		if ( in_array( BP_DOCS_SLUG, $this->slugstocheck ) ) {
			wp_enqueue_style( 'bp-docs-css', $this->includes_url . 'css/bp-docs.css' );
		}

		if ( !empty( $this->query->current_view ) && ( 'edit' == $this->query->current_view || 'create' == $this->query->current_view ) ) {
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_style( 'bpd-edit-css', $this->includes_url . 'css/edit.css' );
			wp_enqueue_style( 'bp-docs-fullscreen-css', $this->includes_url . 'css/fullscreen.css' );
		}
	}
}

?>
