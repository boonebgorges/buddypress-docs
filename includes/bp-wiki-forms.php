<?php

function bp_wiki_group_page_save_editor() {
	global $bp;

	if ( !empty( $_POST['wiki_page_action'] ) && $_POST['wiki_page_action'] == 'edit-group-page-save' ) {

		$wiki_page_id = $_POST['wiki_page_id'];
		$wiki_page_content = $_POST['wiki_page_content_box'];
		$old_wiki_page = get_post( $wiki_page_id );

		if ( bp_wiki_can_edit_wiki_page( $wiki_page_id ) ) {

			$wiki_post = array();
			$wiki_post['ID'] = $wiki_page_id;
			$wiki_post['post_content'] = $wiki_page_content;
			$wiki_post['post_author'] = $bp->loggedin_user->id;
			$wiki_post['post_excerpt'] = substr( strip_tags( $wiki_page_content ), 0, 175 );
			wp_update_post( $wiki_post );

			// Record activity for this page edit
			$wiki_page = get_post( $wiki_page_id );

			if ( BP_WIKI_ACTIVITY_STREAM_METHOD == 'concise' ) {

				$activity_content = bp_wiki_diff_concise( $old_wiki_page->post_content, $wiki_page->post_content );

			} elseif ( BP_WIKI_ACTIVITY_STREAM_METHOD == 'full' ) {

				$activity_content = bp_wiki_diff_full( $old_wiki_page->post_content, $wiki_page->post_content );

			} elseif ( BP_WIKI_ACTIVITY_STREAM_METHOD == 'minimal' ) {

				$activity_content = bp_wiki_diff_minimal( $old_wiki_page->post_content, $wiki_page->post_content );

			}

			$name = '<a href="' . $bp->loggedin_user->domain . '">' . $bp->loggedin_user->fullname . '</a>';

			if ( get_post_meta( $wiki_post_id, 'wiki_view_access', true ) == 'member-only' ) {

				$is_page_private = true;

			} else {

				$is_page_private = false;

			}

			$group = new BP_Groups_Group( $bp->groups->current_group->id, false, false );
			$action = $name . ' ' . __( 'edited the' ) . ' <a href="' . bp_wiki_get_group_page_url( $bp->groups->current_group->id, $wiki_page_id ) . '">' . $wiki_page->post_title . '</a> ' . __( 'page in the group' ) . ' <a href="' . bp_get_group_permalink( $group ) . '">' . attribute_escape( $group->name ) . '</a>';
			$activity_details = array(
				'id' => false,
				'user_id' => $bp->loggedin_user->id,
				'action' => $action,
				'content' => $activity_content,
				'primary_link' => bp_wiki_get_group_page_url( $bp->groups->current_group->id, $wiki_post_id ),
				'component' => 'groups',
				'type' => 'wiki_group_page_edit',
				'item_id' => $bp->groups->current_group->id,
				'secondary_item_id' => $wiki_post_id,
				'hide_sitewide' => $is_page_private
			);
			bp_wiki_record_activity( $activity_details );

			bp_core_add_message( __( 'Page saved successfully', 'bp-wiki' ) );	

			bp_core_redirect( bp_wiki_get_group_page_url( $bp->groups->current_group->id, $wiki_page_id ) );	

		} else {

			bp_core_add_message( __( 'Page not saved', 'bp-wiki' ), 'error' );	

			bp_core_redirect( bp_wiki_get_group_page_url( $bp->groups->current_group->id, $wiki_page_id ) );	
		}

	}

}
add_action( 'init', 'bp_wiki_group_page_save_editor' );

function bp_wiki_group_page_create() {
	global $bp;

	if ( !empty( $_POST['wiki_page_action'] ) && $_POST['wiki_page_action'] == 'new-group-page-create' ) {

		$wiki_page_content = $_POST['wiki_page_content_box'];
		$wiki_page_title = $_POST['wiki-page-title-textbox'];

		if ( bp_wiki_user_can_create_group_page() && bp_wiki_post_slug_not_banned( bp_wiki_slugified_title( $wiki_page_title ) ) ) {
			// Create the page
			$wiki_post_ids_array = array();
			$wiki_post_ids_array = maybe_unserialize( groups_get_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_page_ids' ) );
			// Increase the menu order to account for previously created posts
			$menu_order_offset = count( $wiki_post_ids_array );
			// Create post object
			$wiki_post = array(
				'post_content' => $wiki_page_content, 
				'post_excerpt' => substr( strip_tags( $wiki_page_content ), 0, 175 ), 
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
				$action = $name . ' ' . __( 'created the' ) . ' <a href="' . bp_wiki_get_group_page_url( $bp->groups->current_group->id, $wiki_post_id ) . '">' . $wiki_page->post_title . '</a> ' . __( 'page' ) . ' in the group <a href="' . bp_get_group_permalink( $group ) . '">' . attribute_escape( $group->name ) . '</a>';
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
				bp_core_add_message( __( 'New page created.', 'bp-wiki' ) );	
				bp_core_redirect( bp_wiki_get_group_page_url( $bp->groups->current_group->id, $wiki_post_id ) );		

			}

		} else {

			bp_core_add_message( __( 'Page not saved', 'bp-wiki' ), 'error' );	

			bp_core_redirect( bp_wiki_get_group_page_url( $bp->groups->current_group->id, $wiki_post_id ) );	

		}

	}

}
add_action( 'init', 'bp_wiki_group_page_create' );

?>