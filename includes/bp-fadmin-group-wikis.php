<?php

if ( defined( 'BP_FADMIN_IS_INSTALLED' ) ) {

	function bp_fadmin_setup_nav_group_wiki() {
		global $bp;


		/* Create sub nav item for this component */
		bp_core_new_subnav_item( array(
			'name' => __( 'Group Wiki', 'bp-wiki' ),
			'slug' => 'group-wiki',
			'parent_slug' => $bp->fadmin->slug,
			'parent_url' => $bp->loggedin_user->domain . $bp->fadmin->slug . '/',
			'screen_function' => 'bp_fadmin_screen_group_wiki',
			'position' => 60
		) );

	}
	add_action( 'wp', 'bp_fadmin_setup_nav_group_wiki', 2 );
	add_action( 'admin_menu', 'bp_fadmin_setup_nav_group_wiki', 2 );




	function bp_fadmin_screen_group_wiki() {
		global $bp;

		do_action( 'bp_fadmin_screen_group_wiki' );

		if ( isset( $_POST['group_id'] ) ) {
			
			$feedback = process_fadmin_group_wiki_screen_form();
			
		}
		
		add_action( 'bp_template_title', 'bp_fadmin_screen_group_wiki_title' );
		add_action( 'bp_template_content', 'bp_fadmin_screen_group_wiki_content' );

		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	function bp_fadmin_screen_group_wiki_title() {
		_e( 'Group Wiki Management', 'bp-wiki' );
	}

	function bp_fadmin_screen_group_wiki_content() {
		global $bp;
		
		?>
		<h4><?php _e( 'Welcome to Group Wiki Management', 'bp-wiki' ); ?></h4>

		<p><?php _e( 'This screen provides group wiki administration options that span all of the group wikis you have admin rights to.  ', 'bp-wiki' ); ?></p>
		
		<?php 
		
		
		$adminable_group_wikis = array();
		// I know it's realy bad to run the loop twice but I cba recoding this just now.  I'll fix it up later
		if ( bp_has_groups( 'per_page=999' ) ) :
			while ( bp_groups() ) : bp_the_group(); 
				if ( groups_get_groupmeta( bp_get_group_id(), 'bp_wiki_group_wiki_enabled' ) == 'yes' && groups_is_user_admin( $bp->loggedin_user->id, bp_get_group_id() ) )
				{	
					$group_details = new stdClass();
					$group_details->id = bp_get_group_id();
					$group_details->name = bp_get_group_name();
					$adminable_group_wikis[] = $group_details;
				}
			endwhile;
		endif;
		
		if ( bp_has_groups( 'per_page=999' ) ) : ?>

			<ul id="groups-list" class="item-list">
			<?php 
			while ( bp_groups() ) : bp_the_group(); 
				
				if ( groups_get_groupmeta( bp_get_group_id(), 'bp_wiki_group_wiki_enabled' ) == 'yes' && groups_is_user_admin( $bp->loggedin_user->id, bp_get_group_id() ) ) 
				{	
					?>
					<li>
					
						<div class="item">
							
							<div class="item-title"><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a></div>
								
							<form action="<?php echo $bp->loggedin_user->domain . $bp->fadmin->slug . '/group-wiki/'; ?>" method="post" name="group-wiki-page-options-form">
							
							<input type="hidden" name="group_id" value="<?php bp_group_id(); ?>"/>
							
							<?php _e( 'Change Setting', 'bp-wiki'); ?>:
								<select name="page-setting">
									<option value="blank" selected="selected"><?php _e( 'Select a value', 'bp-wiki'); ?></option>
									<option value="public-view"><?php _e( 'Public View', 'bp-wiki'); ?></option>
									<option value="member-view"><?php _e( 'Member View', 'bp-wiki'); ?></option>
									<option value="member-only"><?php _e( 'Member Edit', 'bp-wiki'); ?></option>
									<option value="mod-only"><?php _e( 'Mod Edit', 'bp-wiki'); ?></option>
									<option value="admin-only"><?php _e( 'Admin Edit', 'bp-wiki'); ?></option>
									<option value="comments-on"><?php _e( 'Comments On', 'bp-wiki'); ?></option>
									<option value="comments-off"><?php _e( 'Comments Off', 'bp-wiki'); ?></option>
									<option value="page-hide"><?php _e( 'Hide', 'bp-wiki'); ?></option>
									<option value="page-show"><?php _e( 'Show', 'bp-wiki'); ?></option>
									<option value="delete"><?php _e( 'Delete', 'bp-wiki'); ?></option>
								</select>
								
								&nbsp;&nbsp;
								
							<?php _e( 'Move Page', 'bp-wiki'); ?>:
								<select name="move-page">
									<option value="blank" selected="selected"><?php _e( 'Select a value', 'bp-wiki'); ?></option>
									<?php
									if ( $adminable_group_wikis ) {
										foreach ( $adminable_group_wikis as $group_wiki ) {
											if ( $group_wiki->id != bp_get_group_id() ) {
												?>
												
												<option value="<?php echo $group_wiki->id; ?>"><?php echo $group_wiki->name; ?></option>
												<?php
											}
										}
									}
									?>
								</select>
								
								&nbsp;&nbsp;
								
								<button type="submit" value="Save"><?php _e( 'Save', 'bp-wiki'); ?></button>
							</p>
								
							<p>
							<?php
							// apologies for styling in the html...trying to avoid using a css sheet as i'm in a rush
							// once again, something to sort out later
							$group_wiki_page_ids_array = maybe_unserialize( groups_get_groupmeta( bp_get_group_id(), 'bp_wiki_group_wiki_page_ids' ) );

							if ( $group_wiki_page_ids_array ) {
								echo '<table>';
								$alt_row = 1;
								foreach ( (array)$group_wiki_page_ids_array as $key => $group_wiki_page_id ) {
									
									if ( $alt_row % 2 == 0 ) {
									
										$alt_tag = 'background-color:#EBEBEB;';
									} else {
										$alt_tag = '';
									}
																	
									$wiki_page = get_post( $group_wiki_page_id );	
									
									echo '<tr style="' . $alt_tag .'"><td style="width:50px;"><input type="checkbox" name="page_id[]" value="' . $group_wiki_page_id . '" /></td>';
									echo '<td><a href="' . bp_wiki_get_group_page_url( bp_get_group_id(), $group_wiki_page_id ) . '">' . $wiki_page->post_title . '</a></td></tr>';
									$alt_row++;
								}
								echo '</table>';
							}
							?>
							</p>
							</form>
						</div>

					<div class="clear"></div>
					
					</li>
					<?php
				}
			endwhile; 
			?>
			</ul>

		<?php else: ?>

			<div id="message" class="info">
				<p><?php _e( 'There were no group wikis found.', 'bp-wiki' ) ?></p>
			</div>

		<?php endif; 		
	
	}
		
		
	function process_fadmin_group_wiki_screen_form() {
		global $bp;
		
		$group_id = $_POST['group_id'];
		$page_setting = $_POST['page-setting'];
		$move_page_group = $_POST['move-page'];
		$page_ids = $_POST['page_id'];
		
		// process page moving
		if ( $page_ids && $move_page_group != 'blank' ) {
		
			// only do this if user is an admin of both groups
			if ( groups_is_user_admin( $bp->loggedin_user->id, $group_id ) &&
				 groups_is_user_admin( $bp->loggedin_user->id, $move_page_group ) ) {
				
				$old_originating_group_page_ids = maybe_unserialize( groups_get_groupmeta( $group_id, 'bp_wiki_group_wiki_page_ids' ) );
				$old_receiving_group_page_ids = maybe_unserialize( groups_get_groupmeta( $move_page_group, 'bp_wiki_group_wiki_page_ids' ) );
				
				foreach ( $page_ids as $this_page_id ) {
				
					$to_delete_record = array_search( $this_page_id, $old_originating_group_page_ids );
					unset( $old_originating_group_page_ids[$to_delete_record] );
					
					$wiki_page = get_post( $this_page_id );
					$wiki_page->post_name = $move_page_group . '-' . bp_wiki_remove_group_id_from_page_slug( $wiki_page->post_name, $group_id );
					wp_update_post( $wiki_page );
					
				}
				
				$updated_originating_group_page_ids = array_values( $old_originating_group_page_ids );
				groups_update_groupmeta ( $group_id, 'bp_wiki_group_wiki_page_ids', maybe_serialize( $updated_originating_group_page_ids ) );
				$updated_receiving_group_page_ids = array_merge( $page_ids, $old_receiving_group_page_ids );
				groups_update_groupmeta ( $move_page_group, 'bp_wiki_group_wiki_page_ids', maybe_serialize( $updated_receiving_group_page_ids ) );

				bp_core_add_message( __( 'Settings saved successfully', 'bp-wiki' ) );
				bp_core_redirect( $bp->fadmin->slug );
			
			} else {

				bp_core_add_message( __( 'Settings not saved: you must have admin rights in both groups', 'bp-wiki' ), 'error' );
				bp_core_redirect( $bp->fadmin->slug );
			}
			
		} else {
		
			// only do this if user is an admin of the group
			if ( groups_is_user_admin( $bp->loggedin_user->id, $group_id ) ) {
			
				switch ( $page_setting ) {
				
					case 'public-view':
						foreach ( $page_ids as $this_page_id ) {			
							update_post_meta( $this_page_id, 'wiki_view_access', 'public' );
						}
						break;
					
					case 'member-view':
						foreach ( $page_ids as $this_page_id ) {			
							update_post_meta( $this_page_id , 'wiki_view_access' , 'member-only' );
						}
						break;
					
					case 'member-only':						
						foreach ( $page_ids as $this_page_id ) {			
							update_post_meta( $this_page_id , 'wiki_edit_access' , 'all-members' );
						}
						break;

					
					case 'mod-only':				
						foreach ( $page_ids as $this_page_id ) {			
							update_post_meta( $this_page_id , 'wiki_edit_access' , 'moderator-only' );
						}
						break;
					
					case 'admin-only':
						foreach ( $page_ids as $this_page_id ) {			
							update_post_meta( $this_page_id , 'wiki_edit_access' , 'admin-only' );
						}
						break;
					
					case 'comments-on':
						foreach ( $page_ids as $this_page_id ) {	
							$wiki_post = array();
							$wiki_post['ID'] = $this_page_id;
							$wiki_post['comment_status'] = 'open';
							remove_action('pre_post_update', 'wp_save_post_revision'); // No revision for this update
							wp_update_post( $wiki_post );
						}
						break;
					
					case 'comments-off':
						foreach ( $page_ids as $this_page_id ) {	
							$wiki_post = array();
							$wiki_post['ID'] = $this_page_id;
							$wiki_post['comment_status'] = 'closed';
							remove_action('pre_post_update', 'wp_save_post_revision'); // No revision for this update
							wp_update_post( $wiki_post );
						}
						break;
					
					case 'page-hide':
						foreach ( $page_ids as $this_page_id ) {			
							update_post_meta( $this_page_id, 'wiki_page_visible', 'no' );
						}
						break;
					
					case 'page-show':
						foreach ( $page_ids as $this_page_id ) {			
							update_post_meta( $this_page_id, 'wiki_page_visible', 'yes' );
						}
						break;
					
					case 'delete':
						foreach ( $page_ids as $this_page_id ) {			
							wp_delete_post( $this_page_id, true );
							$wiki_post_ids_array = array();
							$wiki_post_ids_array = maybe_unserialize( groups_get_groupmeta( $group_id, 'bp_wiki_group_wiki_page_ids' ) );
							if ( $wiki_post_ids_array ) {
								foreach ( $wiki_post_ids_array as $key => $wiki_post_id ) {
									if ( $wiki_post_id == $this_page_id ) {
										unset( $wiki_post_ids_array[$key] );
									}
								}
							}
							groups_update_groupmeta( $group_id, 'bp_wiki_group_wiki_page_ids', maybe_serialize( array_values( $wiki_post_ids_array ) ) );
						}
						break;
					
				}
				
				if ( $page_setting != 'blank' ) {
				
					bp_core_add_message( __( 'Settings saved successfully', 'bp-wiki' ) );
					bp_core_redirect( $bp->fadmin->slug );
				
				}
				
			} else {
			
				bp_core_add_message( __( 'No changes made as you are not an admin of that group', 'bp-wiki' ), 'error' );
				bp_core_redirect( $bp->fadmin->slug );
				
			}
		
		}
		
	}
	
	
	function bp_fadmin_register_group_wikis( $fadmin_extensions ) {
	
		$this_extension = new stdClass;
		$this_extension->name = __( 'Group Wiki', 'bp-fadmin');
		$this_extension->slug = 'group-wiki';
		$this_extension->description = __( 'Movement of wiki pages between groups and ability to set wiki page attributes en mass.', 'bp-wiki');
		
		$fadmin_extensions[] = $this_extension;
		
		return $fadmin_extensions;
	}
	add_filter( 'bp_fadmin_register_extension', 'bp_fadmin_register_group_wikis' );
	
}

?>