<?php

/**
 * Dashboard functions for BuddyPress Docs
 *
 * @package BuddyPress Docs
 * @since 1.1.8
 */

class BP_Docs_Admin {
	/**
	 * PHP 4 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.1.8
	 */
	function bp_docs_admin() {
		$this->__construct();
	}

	/**
	 * PHP 5 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.1.8
	 */	
	function __construct() {	
		// Replace the Dashboard widget
		if ( !defined( BP_DOCS_REPLACE_RECENT_COMMENTS_DASHBOARD_WIDGET ) || !BP_DOCS_REPLACE_RECENT_COMMENTS_DASHBOARD_WIDGET ) {
			add_action( 'wp_dashboard_setup', array( $this, 'replace_recent_comments_dashboard_widget' ) );
		}
	}
	
	function replace_recent_comments_dashboard_widget() {
		global $wp_meta_boxes;
		
		// Find the recent comments widget
		foreach ( $wp_meta_boxes['dashboard'] as $context => $widgets ) {
			if ( !empty( $widgets ) && !empty( $widgets['core'] ) && is_array( $widgets['core'] ) && array_key_exists( 'dashboard_recent_comments', $widgets['core'] ) ) {
				// Take note of the context for when we add our widget
				$drc_widget_context = $context;				
				
				// Store the widget so that we have access to its information
				$drc_widget = $widgets['core']['dashboard_recent_comments'];
				
				// Store the array keys, so that we can reorder things later
				$widget_order = array_keys( $widgets['core'] );
	
				// Remove the core widget
				remove_meta_box( 'dashboard_recent_comments', 'dashboard', $drc_widget_context );
				
				// No need to continue the loop
				break;
			}
		}
	
		// If we couldn't find the recent comments widget, it must have been removed. We'll
		// assume this means we shouldn't add our own
		if ( empty( $drc_widget ) )
			return;
		
		// Set up and add our widget
		$recent_comments_title = __( 'Recent Comments' );
		
		// Add our widget in the same location			
		wp_add_dashboard_widget( 'dashboard_recent_comments_bp_docs', $recent_comments_title, array( $this, 'wp_dashboard_recent_comments' ), 'wp_dashboard_recent_comments_control' );
		
		// Restore the previous widget order. File this under "good citizenship"
		$wp_meta_boxes['dashboard'][$context]['core']['dashboard_recent_comments'] = $wp_meta_boxes['dashboard'][$context]['core']['dashboard_recent_comments_bp_docs'];
		
		unset( $wp_meta_boxes['dashboard'][$context]['core']['dashboard_recent_comments_bp_docs'] );
		
		// In order to inherit the styles, we're going to spoof the widget ID. Sadness
		$wp_meta_boxes['dashboard'][$context]['core']['dashboard_recent_comments']['id'] = 'dashboard_recent_comments';
	}
	
	/**
	 * Replicates WP's native recent comments dashboard widget.
	 *
	 * @package BuddyPress Docs
	 * @since 1.1.8
	 */
	function wp_dashboard_recent_comments() {
		global $wpdb, $bp;
	
		if ( current_user_can('edit_posts') )
			$allowed_states = array('0', '1');
		else
			$allowed_states = array('1');
	
		// Select all comment types and filter out spam later for better query performance.
		$comments = array();
		$start = 0;
	
		$widgets = get_option( 'dashboard_widget_options' );
		$total_items = isset( $widgets['dashboard_recent_comments'] ) && isset( $widgets['dashboard_recent_comments']['items'] )
			? absint( $widgets['dashboard_recent_comments']['items'] ) : 5;
	
		while ( count( $comments ) < $total_items && $possible = $wpdb->get_results( "SELECT c.*, p.post_type AS comment_post_post_type FROM $wpdb->comments c LEFT JOIN $wpdb->posts p ON c.comment_post_ID = p.ID WHERE p.post_status != 'trash' ORDER BY c.comment_date_gmt DESC LIMIT $start, 50" ) ) {
	
			foreach ( $possible as $comment ) {			
				if ( count( $comments ) >= $total_items )
					break;
				
				// Is the user allowed to read this doc?
				if ( $bp->bp_docs->post_type_name == $comment->comment_post_post_type && !bp_docs_user_can( 'read', get_current_user_ID(), $comment->comment_post_ID ) )
					continue;
				
				if ( in_array( $comment->comment_approved, $allowed_states ) && current_user_can( 'read_post', $comment->comment_post_ID ) )
					$comments[] = $comment;
			}
	
			$start = $start + 50;
		}
	
		if ( $comments ) :
	?>
	
			<div id="the-comment-list" class="list:comment">
	<?php
			foreach ( $comments as $comment )
				_wp_dashboard_recent_comments_row( $comment );
	?>
	
			</div>
	
	<?php
			if ( current_user_can('edit_posts') ) { ?>
				<?php _get_list_table('WP_Comments_List_Table')->views(); ?>
	<?php	}
	
			wp_comment_reply( -1, false, 'dashboard', false );
			wp_comment_trashnotice();
	
		else :
	?>
	
		<p><?php _e( 'No comments yet.' ); ?></p>
	
	<?php
		endif; // $comments;
	}
}
$bp_docs_admin = new BP_Docs_Admin;

?>