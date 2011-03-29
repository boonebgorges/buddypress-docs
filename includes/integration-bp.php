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
		add_action( 'bp_init', 			array( $this, 'do_query' 		), 90 );
		
		add_action( 'bp_setup_globals', 	array( $this, 'setup_globals' 		) );
		
		if ( bp_is_active( 'groups' ) ) {
			require_once( BP_DOCS_INCLUDES_PATH . 'integration-groups.php' );
			$this->groups_integration = new BP_Docs_Groups_Integration;
		}
		
		add_action( 'wp', 			array( $this, 'catch_page_load' 	), 1 );
		
		add_action( 'comment_post_redirect', 	array( $this, 'comment_post_redirect' 	), 99, 2 );
		
		// Hook the activity function
		add_action( 'bp_docs_doc_saved',	array( $this, 'post_activity' 		) );
		
		// Filter the location of the comments template to allow it to be included with
		// the plugin
		add_filter( 'comments_template',	array( $this, 'comments_template'	) );
		
		add_action( 'bp_loaded', 		array( $this, 'set_includes_url' 	) );
		add_action( 'init', 			array( $this, 'enqueue_scripts' 	) );
		add_action( 'wp_print_styles', 		array( $this, 'enqueue_styles' 		) );
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
			bp_core_add_message( __( 'You do not have permission to edit the doc.', 'bp-docs' ), 'error' );
			
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
					'name' 		=> $bp->action_variables[0],
					'post_type' 	=> $bp->bp_docs->post_type_name
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
	 * Filters the comment_post_direct URL so that the user gets sent back to the true
	 * comment URL after posting
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
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
	 * Posts an activity item on doc save
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
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
			'hide_sitewide'		=> apply_filters( 'bp_docs_hide_sitewide', false ) // Filtered to allow plugins and integration pieces to dictate
		);
		
		do_action( 'bp_docs_after_activity_save', $args );
		
		$activity_id = bp_activity_add( apply_filters( 'bp_docs_activity_args', $args ) );
		
		do_action( 'bp_docs_after_activity_save', $activity_id, $args );
		
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
	 * @since 1.0
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
				$path = BP_DOCS_INSTALL_PATH . 'includes' . DIRECTORY_SEPARATOR . 'templates' . $file;
		}
		
		return apply_filters( 'bp_docs_comment_template_path', $path, $original_path );
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
			wp_enqueue_script( 'comment-reply' );
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
			if ( function_exists( 'add_thickbox' ) ) 
				add_thickbox();
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