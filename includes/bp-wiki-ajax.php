<?php

// should be moved to template tags

function bp_wiki_activity_post_form_action() {
	echo bp_wiki_get_activity_post_form_action();
}

	function bp_wiki_get_activity_post_form_action() {
		return apply_filters( 'bp_wiki_get_activity_post_form_action', site_url( BP_ACTIVITY_SLUG . '/post/' ) );
	}
// end of 'should be moved to template tags'

/* Group wiki admin page create */

function bp_wiki_group_admin_page_create() {

	global $bp;

	$wiki_page_title = $_POST['wiki_page_title'];

	if ( groups_is_user_admin( $bp->loggedin_user->id, $bp->groups->current_group->id ) ) {

		// Create the page
		$wiki_post_ids_array = array();

		$wiki_post_ids_array = maybe_unserialize( groups_get_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_page_ids' ) );

		// Increase the menu order to account for previously created posts
		$menu_order_offset = count( $wiki_post_ids_array );		

		// Check against banned names		
		if ( bp_wiki_post_slug_not_banned( bp_wiki_slugified_title( $wiki_page_title ) ) ) {			

			// Create post object
			$wiki_post = array(
				'post_content' => '', 
				'post_excerpt' => __( 'No content has been added to this page yet.' ), 
				'post_name' => $bp->groups->current_group->id . '-' . bp_wiki_slugified_title( $wiki_page_title ), 
				'post_status' => 'publish', 
				'post_title' => $wiki_page_title,
				'post_type' => 'wiki', // Custom post type - wiki
				'menu_order' => $menu_order_offset + 1 // Order page will appear in nav menu
			); 

			// Insert the post and return the post ID
			$wiki_post_id = wp_insert_post( $wiki_post );

			if ( $wiki_post_id > 0 ) {

				// Get the post from db so we can use teh most up to date values
				$wiki_page = get_post( $wiki_post_id );

				// Update the post meta for wiki page view/edit access
				$wiki_post_ids_array[] = $wiki_post_id;

				update_post_meta( $wiki_post_id , 'wiki_view_access' , groups_get_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_default_page_privacy' ) );

				update_post_meta( $wiki_post_id , 'wiki_edit_access' , groups_get_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_default_edit_rights' ) );

				update_post_meta( $wiki_post_id , 'wiki_page_visible' , 'yes' );

				groups_update_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_page_ids', $wiki_post_ids_array );

				// Record activity for this page creation
				$name = '<a href="' . $bp->loggedin_user->domain . '">' . $bp->loggedin_user->fullname . '</a>';

				if ( get_post_meta( $wiki_post_id, 'wiki_view_access', true ) == 'member-only' ) {
					$is_page_private = true;
				} else {
					$is_page_private = false;
				}

				$group = new BP_Groups_Group( $bp->groups->current_group->id, false, false );
				$action = $name . ' ' . __( 'created the' ) . ' <a href="' . bp_get_group_permalink( $group ) . '">' . attribute_escape( $group->name ) . '</a> ' . __( 'wiki page') . ' <a href="' . bp_wiki_get_group_page_url( $bp->groups->current_group->id, $wiki_post_id ) . '">' . $wiki_page->post_title . '</a>';

				$activity_details = array(
					'id' => false,
					'user_id' => $bp->loggedin_user->id,
					'action' => $action,
					'content' => '',
					'primary_link' => bp_wiki_get_group_page_url( $bp->groups->current_group->id, $wiki_post_id ),
					'component' => 'groups',
					'type' => 'wiki_group_page_create',
					'item_id' => $bp->groups->current_group->id,
					'secondary_item_id' => $wiki_post_id,
					'hide_sitewide' => $is_page_private
				);

				bp_wiki_record_activity( $activity_details );			

				// Echo out a row with the page details
				$privacy_settings = get_post_meta( $wiki_page->ID, 'wiki_view_access', true );
				$edit_settings = get_post_meta( $wiki_page->ID, 'wiki_edit_access', true );
				$comment_settings = $wiki_page->comment_status;
				$page_enabled = get_post_meta( $wiki_page->ID, 'wiki_page_visible', true );

				?>

				<ul id="wiki-page-item-<?php echo $wiki_page->ID; ?>" class="wiki-group-admin-list-page">

					<li class="wiki-hidden"><input id="wiki-group-page-id[]" name="wiki-group-page-id[]" value="<?php echo $wiki_page->ID; ?>"></input></li>

					<li class="wiki-page-order">></li>

					<li class="wiki-page-title"><?php echo $wiki_page_title; ?></li>

					<li class="wiki-page-privacy">

						<select id="wiki-group-admin-page-privacy[]" name="wiki-group-admin-page-privacy[]" class="wiki-group-admin-select-box">

							<option class="wiki-group-admin-select-box" value="public" <?php if ( $privacy_settings == 'public' ) echo ' selected="yes"'; ?>>

								<?php _e( 'Public' ); ?>

							</option>

							<option class="wiki-group-admin-select-box" value="member-only" <?php if ( $privacy_settings == 'member-only' ) echo ' selected="yes"'; ?>>

								<?php _e( 'Member Only' ); ?>

							</option>

						</select>

					</li>

					<li class="wiki-page-editing">

						<select id="wiki-group-admin-page-edit-rights[]" name="wiki-group-admin-page-edit-rights[]" class="wiki-group-admin-select-box">

							<option class="wiki-group-admin-select-box"	value="all-members" <?php if ( $edit_settings == 'all-members' ) echo ' selected="yes"'; ?>>

								<?php _e( 'All Members' ); ?>

							</option>

							<option class="wiki-group-admin-select-box"	value="moderator-only" <?php if ( $edit_settings == 'moderator-only' ) echo ' selected="yes"'; ?>>

								<?php _e( 'Moderator Only' ); ?>

							</option>

							<option class="wiki-group-admin-select-box"	value="admin-only" <?php if ( $edit_settings == 'admin-only' ) echo ' selected="yes"'; ?>>

								<?php _e( 'Admin Only' ); ?>

							</option>

						</select>

					</li>

					<li class="wiki-page-commenting wiki-group-admin-check-box">

						<input 

							type="checkbox" value="yes" id="wiki-page-comments-on[]" name="wiki-page-comments-on[]"

							<?php if ( $comment_settings == 'open' ) echo ' checked="1"'; ?>

						/>

					</li>
					
					<li class="wiki-page-enabled wiki-group-admin-check-box">

						<input 

							type="checkbox" value="yes" id="wiki-page-visible[]" name="wiki-page-visible[]"

							<?php if ( $page_enabled == 'yes' ) echo ' checked="1"'; ?>

						/>

					</li>

					<li class="wiki-page-delete">

						<button class="wiki" onclick="if (confirm('Are you sure?')){jQuery(this).attr('disabled=1');wikiGroupAdminPageDelete(<?php echo $wiki_page->ID ?>);}return false;"><?php _e( 'X' ); ?></button>

					</li>

				</ul>

				<?php						

				}

			}

	}

}
add_action( 'wp_ajax_bp_wiki_group_admin_page_create', 'bp_wiki_group_admin_page_create' );

/* Frontend page creation */
function bp_wiki_group_frontend_page_create() {

	global $bp;

	$wiki_page_title = $_POST['wiki_page_title'];

	// Check against banned names		

	if ( bp_wiki_post_slug_not_banned( bp_wiki_slugified_title( $wiki_page_title ) ) ) {

		if ( groups_get_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_member_page_create' ) 

			 && groups_is_user_member( $bp->loggedin_user->id, $bp->groups->current_group->id ) ) {

			// Create the page

			$wiki_post_ids_array = array();

			$wiki_post_ids_array = maybe_unserialize( groups_get_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_page_ids' ) );

			// Increase the menu order to account for previously created posts

			$menu_order_offset = count( $wiki_post_ids_array );

			// Create post object

			$wiki_post = array(
				'post_content' => '', 
				'post_excerpt' => __( 'A group wiki page.' ), 
				'post_name' => $bp->groups->current_group->id . '-' . bp_wiki_slugified_title( $wiki_page_title ), 
				'post_status' => 'publish', 
				'post_title' => $wiki_page_title,
				'post_type' => 'wiki', // Custom post type - wiki
				'menu_order' => $menu_order_offset + 1 // Order page will appear in nav menu
			); 

			// Insert the post and return the post ID
			$wiki_post_id = wp_insert_post( $wiki_post );

			if ( $wiki_post_id > 0 ) {
				
				// Get the post from db so we can use teh most up to date values
				$wiki_page = get_post( $wiki_post_id );

				// Update the post meta for wiki page view/edit access
				$wiki_post_ids_array[] = $wiki_post_id;

				update_post_meta( $wiki_post_id , 'wiki_view_access' , groups_get_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_default_page_privacy' ) );

				update_post_meta( $wiki_post_id , 'wiki_edit_access' , groups_get_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_default_edit_rights' ) );

				update_post_meta( $wiki_post_id , 'wiki_page_visible' , 'yes' );

				groups_update_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_page_ids', $wiki_post_ids_array );

				// Record activity for this page creation
				$name = '<a href="' . $bp->loggedin_user->domain . '">' . $bp->loggedin_user->fullname . '</a>';

				if ( get_post_meta( $wiki_post_id, 'wiki_view_access', true ) == 'member-only' ) {
					$is_page_private = true;
				} else {
					$is_page_private = false;
				}

				$group = new BP_Groups_Group( $bp->groups->current_group->id, false, false );

				$action = $name . ' ' . __( 'created the' ) . ' <a href="' . bp_get_group_permalink( $group ) . '">' . attribute_escape( $group->name ) . '</a> ' . __( 'wiki page') . ' <a href="' . bp_wiki_get_group_page_url( $bp->groups->current_group->id, $wiki_post_id ) . '">' . $wiki_page->post_title . '</a>';

				$activity_details = array(
					'id' => false,
					'user_id' => $bp->loggedin_user->id,
					'action' => $action,
					'content' => '',
					'primary_link' => bp_wiki_get_group_page_url( $bp->groups->current_group->id, $wiki_post_id ),
					'component' => 'groups',
					'type' => 'wiki_group_page_create',
					'item_id' => $bp->groups->current_group->id,
					'secondary_item_id' => $wiki_post_id,
					'hide_sitewide' => $is_page_private
				);

				bp_wiki_record_activity( $activity_details );

				// Echo the page out to be added to the page list

				$two_column = 'twocolumn';
				$alt = '';				

				if ( !( $menu_order_offset % 2 ) ) {
					$alt = ' alt';			
				}

				echo '<div class="bp-wiki-index-divider' . $alt . ' '. $two_column . '">';	

				echo '	<div class="bp-wiki-index-page-title"><a href="' . bp_wiki_get_group_page_url( $bp->groups->current_group->id, $group_wiki_page_id ) . '">' . $wiki_page->post_title . '</a></div>';

				echo '	<div class="bp-wiki-index-meta">Last edit by: ' . bp_core_get_userlink( $wiki_page->post_author ) . __( ' at ' ) . bp_wiki_to_wiki_date_format( $wiki_page->post_modified ) . '</div>';

				echo '	<div class="bp-wiki-index-excerpt">' . $wiki_page->post_excerpt . '</div>';

				echo '</div>';

			}

		}

	}

}
add_action( 'wp_ajax_bp_wiki_group_frontend_page_create', 'bp_wiki_group_frontend_page_create' );

/* Summary editing */
function bp_wiki_group_page_title_show_editor() {
	global $bp;

	$wiki_page_id = $_POST['wiki_page_id'];

	if ( bp_wiki_can_edit_wiki_page( $wiki_page_id ) ) {
		$wiki_page = get_post( $wiki_page_id );
		echo $wiki_page->post_title;
	}
}add_action( 'wp_ajax_bp_wiki_group_page_title_show_editor', 'bp_wiki_group_page_title_show_editor' );

function bp_wiki_group_page_title_button_editing() {
	global $bp;

	$wiki_page_id = $_POST['wiki_page_id'];

	if ( bp_wiki_can_edit_wiki_page( $wiki_page_id ) ) {
		?>
		<button class="wiki" onclick="wikiGroupPageEditTitleSave();return false;"><?php _e( 'Save Title' ); ?></button>
		<?php
	}
}add_action( 'wp_ajax_bp_wiki_group_page_title_button_editing', 'bp_wiki_group_page_title_button_editing' );

function bp_wiki_group_page_title_button_viewing() {
	global $bp;

	$wiki_page_id = $_POST['wiki_page_id'];

	if ( bp_wiki_can_edit_wiki_page( $wiki_page_id ) ) {
		?>
		<button class="wiki" onclick="wikiGroupPageEditTitleStart();return false;"><?php _e( 'Edit Title' ); ?></button>

		<?php
	}
}add_action( 'wp_ajax_bp_wiki_group_page_title_button_viewing', 'bp_wiki_group_page_title_button_viewing' );

function bp_wiki_group_page_title_save_editor() {
	global $bp;

	$wiki_page_id = $_POST['wiki_page_id'];
	$wiki_page_title = $_POST['wiki_page_title'];

	if ( bp_wiki_can_edit_wiki_page( $wiki_page_id ) ) {
		$wiki_post = array();
		$wiki_post['ID'] = $wiki_page_id;
		$wiki_post['post_title'] = $wiki_page_title;
		remove_action('pre_post_update', 'wp_save_post_revision'); // No revision for this update

		wp_update_post( $wiki_post );			

	}
	echo $wiki_post['post_title'];
}add_action( 'wp_ajax_bp_wiki_group_page_title_save_editor', 'bp_wiki_group_page_title_save_editor' );

/* Article editing */
function bp_wiki_group_page_article_show_editor() {
	global $bp;

	$wiki_page_id = $_POST['wiki_page_id'];

	if ( bp_wiki_can_edit_wiki_page( $wiki_page_id ) ) {
		$wiki_page = get_post( $wiki_page_id );
		echo apply_filters( 'the_editor', $wiki_page->post_content );
	}
}add_action( 'wp_ajax_bp_wiki_group_page_article_show_editor', 'bp_wiki_group_page_article_show_editor' );

function bp_wiki_group_page_content_title_button_editing() {
	global $bp;

	$wiki_page_id = $_POST['wiki_page_id'];

	if ( bp_wiki_can_edit_wiki_page( $wiki_page_id ) ) {
		?>
		<button class="wiki" onclick="wikiGroupPageEditSave();return false;"><?php _e( 'Save Page' ); ?></button>
		<?php
	}
}add_action( 'wp_ajax_bp_wiki_group_page_content_title_button_editing', 'bp_wiki_group_page_content_title_button_editing' );

function bp_wiki_group_page_content_title_button_viewing() {
	global $bp;

	$wiki_page_id = $_POST['wiki_page_id'];

	if ( bp_wiki_can_edit_wiki_page( $wiki_page_id ) ) {
		?>
		<button class="wiki" onclick="wikiGroupPageEditStart();return false;"><?php _e( 'Edit Page' ); ?></button>
		<?php
	}
}add_action( 'wp_ajax_bp_wiki_group_page_content_title_button_viewing', 'bp_wiki_group_page_content_title_button_viewing' );

/* Group wiki admin functions */
function bp_wiki_group_admin_page_delete() {
	global $bp;

	$wiki_page_id = $_POST['wiki_page_id'];	
		// Do some variable prep - need to get these values now as page won't exist later
	$wiki_page_title = get_post( $wiki_page_id )->post_title;		

	if ( get_post_meta( $wiki_post_id, 'wiki_view_access', true ) == 'member-only' ) {
			$is_page_private = true;
		} else {
			$is_page_private = false;
		}

	if ( is_site_admin() || groups_is_user_admin( $bp->loggedin_user->id, $bp->groups->current_group->id ) ) {

		wp_delete_post( $wiki_page_id, true );

		$wiki_post_ids_array = array();
		
		$wiki_post_ids_array = maybe_unserialize( groups_get_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_page_ids' ) );

		if ( $wiki_post_ids_array ) {

			foreach ( $wiki_post_ids_array as $key => $wiki_post_id ) {

				if ( $wiki_post_id == $wiki_page_id ) {

					unset( $wiki_post_ids_array[$key] );

				}

			}

		}

		groups_update_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_page_ids', $wiki_post_ids_array );

		// Record activity for this page delete
		$name = '<a href="' . $bp->loggedin_user->domain . '">' . $bp->loggedin_user->fullname . '</a>';

		$group = new BP_Groups_Group( $bp->groups->current_group->id, false, false );

		$action = $name . ' ' . __( 'deleted the' ) . ' <a href="' . bp_get_group_permalink( $group ) . '">' . attribute_escape( $group->name ) . '</a> ' . __( 'wiki page' ) . ' <a href="#">' . $wiki_page_title . '</a>';

		$activity_details = array(
			'id' => false,
			'user_id' => $bp->loggedin_user->id,
			'action' => $action,
			'content' => '',
			'primary_link' => bp_get_group_permalink( $group ),
			'component' => 'groups',
			'type' => 'wiki_group_page_delete',
			'item_id' => $bp->groups->current_group->id,
			'secondary_item_id' => false,
			'hide_sitewide' => $is_page_private
		);

		bp_wiki_record_activity( $activity_details );

	}

}
add_action( 'wp_ajax_bp_wiki_group_admin_page_delete', 'bp_wiki_group_admin_page_delete' );




/* Activity stream used for comments.  These are stolen from the buddypress parent theme ajax file with added mods to work for wiki comments*/

/* AJAX update posting */
function bp_wiki_post_update() {
	global $bp;

	/* Check the nonce */
	check_admin_referer( 'post_update', '_wpnonce_post_update' );

	if (  $_POST['bp_wiki_comment_form'] = 'yes' ) {
		
		remove_action( 'wp_ajax_post_update', 'bp_dtheme_post_update' );
		
		if ( !is_user_logged_in() ) {
			echo '-1';
			return false;
		}

		if ( empty( $_POST['content'] ) ) {
			echo '-1<div id="message" class="error"><p>' . __( 'Please enter some content to post.', 'buddypress' ) . '</p></div>';
			return false;
		}
		
		if ( !bp_wiki_can_comment( $_POST['bp_wiki_page_id'] ) ) {
			echo '-1<div id="message" class="error"><p>' . __( 'You cannot do that.', 'bp-wiki' ) . '</p></div>';
		}
		
		if ( get_post_meta( $_POST['bp_wiki_page_id'], 'wiki_view_access', true ) == 'member-only' ) {
			$hide_sitewide = true;
		} else {
			$hide_sitewide = false;
		}
		
		$wiki_page = get_post( $_POST['bp_wiki_page_id'] );
		
		$args = array(
			'id' => false,
			'user_id' => $bp->loggedin_user->id,
			'action' => bp_core_get_userlink( $bp->loggedin_user->id ) . __( ' commented on the ', 'bp-wiki' ) . '<a href="' . bp_wiki_get_group_page_url( $bp->groups->current_group->id, $_POST['bp_wiki_page_id'] ) . '">' . $wiki_page->post_title . '</a> ' . __( ' wiki page.', 'bp-wiki' ),
			'content' => $_POST['content'],
			'primary_link' => bp_core_get_userlink( $bp->loggedin_user->id, false, true ),
			'component' => $bp->groups->id,
			'type' => 'wiki_group_page_comment',
			'item_id' => $bp->groups->current_group->id,
			'secondary_item_id' => $_POST['bp_wiki_page_id'],
			'recorded_time' => gmdate( "Y-m-d H:i:s" ),
			'hide_sitewide' => $hide_sitewide
		);
		
		$activity_id = bp_wiki_record_activity( $args );
		
		if ( !$activity_id ) {
			echo '<div id="message" class="error"><p>' . __( 'There was a problem posting your update, please try again.', 'buddypress' ) . '</p></div>';
			return false;
		}

		if ( bp_has_activities ( 'include=' . $activity_id ) ) : ?>
			<?php while ( bp_activities() ) : bp_the_activity(); ?>
				<?php require_once( apply_filters( 'bp_wiki_locate_group_wiki_comments_entry', 'activity/comments_entry.php' ) ); ?>
			<?php endwhile; ?>
		 <?php endif;
		 
	}
}
add_action( 'wp_ajax_post_update', 'bp_wiki_post_update', 5 );


/* AJAX delete an activity */
function bp_wiki_delete_activity() {
	global $bp;

	/* Check the nonce */
	check_admin_referer( 'bp_activity_delete_link' );

	if ( !is_user_logged_in() ) {
		echo '-1';
		return false;
	}

	$activity = new BP_Activity_Activity( $_POST['id'] );
	
	if ( $activity->type == 'wiki_group_page_comment' ) {	
	
		remove_action( 'wp_ajax_delete_activity', 'bp_dtheme_delete_activity' );
		
		/* Check access */
		if ( !is_super_admin() && $activity->user_id != $bp->loggedin_user->id && 
			 !groups_is_user_admin( $bp->loggedin_user->id, $activity->item_id ) )
			return false;

		if ( empty( $_POST['id'] ) || !is_numeric( $_POST['id'] ) )
			return false;

		/* Call the action before the delete so plugins can still fetch information about it */
		do_action( 'bp_activity_action_delete_activity', $_POST['id'], $activity->user_id );

		if ( !bp_activity_delete( array( 'id' => $_POST['id'], 'user_id' => $activity->user_id ) ) ) {
			echo '-1<div id="message" class="error"><p>' . __( 'There was a problem when deleting. Please try again.', 'buddypress' ) . '</p></div>';
			return false;
		}

		return true;
	}
}
add_action( 'wp_ajax_delete_activity', 'bp_wiki_delete_activity', 5 );
