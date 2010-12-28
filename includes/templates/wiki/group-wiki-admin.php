<?php
global $bp;
?>

<div class="checkbox">

	<label><input type="checkbox" value="1" id="groupwiki-enable-wiki" name="groupwiki-enable-wiki" <?php if ( groups_get_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_enabled' ) == 'yes' ) echo 'checked="1"'; ?> onclick="jQuery('#wiki-create-fields, #wiki-create-fields-instructions').toggleClass('wiki-hidden');"/> <?php _e( 'Enable group wiki', 'bp-wiki' ); ?></label>	

	<hr>

</div>

<p id="wiki-create-fields-instructions" <?php if ( groups_get_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_enabled' ) == 'yes' ) echo 'class="wiki-hidden"'; ?>><?php _e( 'Wiki creation options will be displayed once you have ticked to enable the group wiki.', 'bp-wiki' ); ?></p>

<div id="wiki-create-fields" <?php if ( groups_get_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_enabled' ) == 'no' ) echo 'class="wiki-hidden"'; ?>>

<div id="bp-wiki-group-admin-pages-list">

	<ul class="wiki-group-admin-heading">

		<li class="wiki-page-order"><?php _e( '#' ); ?></li>
		<li class="wiki-page-title"><?php _e( 'Page Title' ); ?></li>
		<li class="wiki-page-privacy"><?php _e( 'Privacy' ); ?></li>
		<li class="wiki-page-editing"><?php _e( 'Editing' ); ?></li>
		<li class="wiki-page-commenting"><?php _e( 'Comments' ); ?></li>
		<li class="wiki-page-enabled"><?php _e( 'Show' ); ?></li>
		<li class="wiki-page-delete"></li>

	</ul>

	<div style="clear:both;"></div>

	<?php
	// Get the page ids for this group's wiki pages
	$group_wiki_page_ids_array = array();
	$group_wiki_page_ids_array = maybe_unserialize( groups_get_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_page_ids' ) );

	// Build the table rows
	if ( $group_wiki_page_ids_array ) {

		foreach ( $group_wiki_page_ids_array as $key => $group_wiki_page_id ) {

			// Get the page in question
			$wiki_page = get_post( $group_wiki_page_id );

			// Get current options for each wiki page.  
			$wiki_page_id = $wiki_page->ID;
			$wiki_page_order = $wiki_page->menu_order;
			$wiki_page_title = $wiki_page->post_title;
			$privacy_settings = get_post_meta( $wiki_page->ID, 'wiki_view_access', true );
			$edit_settings = get_post_meta( $wiki_page->ID, 'wiki_edit_access', true );
			$comment_settings = $wiki_page->comment_status;
			$page_enabled = get_post_meta( $wiki_page->ID, 'wiki_page_visible', true );

			// Creates a row for each wiki page with current options selected 
			$alt_style = '';

			if( $key % 2 ) {
				$alt_style = ' alt-wiki-admin-ul';
			}
			?>

			<ul id="wiki-page-item-<?php echo $wiki_page->ID; ?>" class="wiki-group-admin-list-page<?php echo $alt_style; ?>">
			
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
					<?php 
					if ( $comment_settings == 'open' ) {
						echo '<input type="checkbox" value="yes" id="wiki-page-comments-on[]" name="wiki-page-comments-on[]" onclick="wikiGroupAdminFakeTickboxCommentsDisable(this);" checked="1"/>';
					} else {
						echo '<input type="hidden" value="no" id="wiki-page-comments-on[]" name="wiki-page-comments-on[]" /><input type="checkbox" value="" id="dummy" name="dummy" onclick="wikiGroupAdminFakeTickboxCommentsEnable(this);"/>';
					}
					?>
				</li>

				<li class="wiki-page-enabled wiki-group-admin-check-box">
					<?php 
					if ( $page_enabled == 'yes' ) {
						echo '<input type="checkbox" value="yes" id="wiki-page-visible[]" name="wiki-page-visible[]" onclick="wikiGroupAdminFakeTickboxPageDisable(this);" checked="1"/>';
					} else {
						echo '<input type="hidden" value="no" id="wiki-page-visible[]" name="wiki-page-visible[]" /><input type="checkbox" value="" id="dummy" name="dummy" onclick="wikiGroupAdminFakeTickboxPageEnable(this);"/>';
					}
					?>
				</li>

				<li class="wiki-page-delete">

					<button class="wiki" onclick="if (confirm('Are you sure?')){jQuery(this).attr('disabled=1');wikiGroupAdminPageDelete(<?php echo $wiki_page->ID ?>);}return false;"><?php _e( 'X' ); ?></button>

				</li>

			</ul>
			<?php
		}
	}
?>

</div>

<div style="clear:both;"></div>

	<h4><?php _e( 'Create Additional Pages', 'bp-wiki' ); ?></h4>	

	<input type="textarea" id="wiki-page-title-create" class="wiki-page-title-input" value=""/> 

	<button id="bp-wiki-group-admin-page-create-button" class="wiki" onclick="wikiGroupAdminPageCreate();return false;"><?php _e( 'Create', 'bp-wiki' ); ?></button>

	<br/>
	<br/>

	<h4><?php _e( 'Default Page Privacy', 'bp-wiki' ); ?></h4>

	<p><?php _e( 'Please select the default privacy levels for your wiki pages.  Any new pages created will default to these privacy settings.', 'bp-wiki' ); ?></p>

	<input type="radio" name="wiki-privacy" value="public" <?php if ( bp_wiki_group_default_page_privacy() == 'public' ) echo 'checked="1"'; ?>/>

		<strong><?php _e( 'Public', 'bp-wiki' ); ?></strong>
		<?php _e( ' - Wiki pages are viewable to all site visitors.', 'bp-wiki' ); ?><br/>

	<input type="radio" name="wiki-privacy" value="member-only" <?php if ( bp_wiki_group_default_page_privacy() == 'member-only' ) echo 'checked="1"'; ?>/>

		<strong><?php _e( 'Private', 'bp-wiki' ); ?></strong>
		<?php _e( ' - Wiki pages may only be viewed by members of the group.', 'bp-wiki' ); ?><br/>

	<br/>

	<h4><?php _e( 'Default Page Editing Privileges', 'bp-wiki' ); ?></h4>

	<p><?php _e( 'Please select the default edit privileges for your members.   Any new pages created will default to these privacy settings.', 'bp-wiki' ); ?></p>

	<input type="radio" name="wiki-edit-rights" value="all-members" <?php if ( groups_get_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_default_edit_rights' ) == 'all-members' ) echo 'checked="1"'; ?>/>

	<strong><?php _e( 'All Members', 'bp-wiki' ); ?></strong><?php _e( ' - All members of the group can edit wiki pages.', 'bp-wiki' ); ?><br/>

	<input type="radio" name="wiki-edit-rights" value="moderator-only" <?php if ( groups_get_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_default_edit_rights' ) == 'moderator-only' ) echo 'checked="1"'; ?>/>

	<strong><?php _e( 'Moderators', 'bp-wiki' ); ?></strong><?php _e( ' - Only group moderators (and admins) can edit wiki pages .', 'bp-wiki' ); ?><br/>

	<input type="radio" name="wiki-edit-rights" value="admin-only" <?php if ( groups_get_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_default_edit_rights' ) == 'admin-only' ) echo 'checked="1"'; ?>/>

	<strong><?php _e( 'Administrators', 'bp-wiki' ); ?></strong><?php _e( ' - Only group admins can edit wiki pages.', 'bp-wiki' ); ?><br/>

	<br/> 

	<h4><?php _e( 'Wiki Group Homepage Text', 'bp-wiki' ); ?></h4>

	<p><?php _e( 'This text will appear on the wiki homepage for your group.  You can edit this later via the group admin area.  (350 character limit)', 'bp-wiki' ); ?></p>

	<textarea id="wiki-homepage-summary" name="wiki-homepage-summary" maxlength="350"><?php echo groups_get_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_index_text' ); ?></textarea>

	<br/>

	<h4><?php _e( 'Member Page Creation', 'bp-wiki' ); ?></h4>	

	<div class="checkbox">
		<label>
			<input type="checkbox" id="groupwiki-member-page-create" name="groupwiki-member-page-create" value="yes" <?php if ( groups_get_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_member_page_create' ) == 'yes' ) echo 'checked="1"'; ?>
			/>

			<?php _e( 'Click here to allow all group members to create pages in the group wiki homepage.', 'bp-wiki' ); ?>
			
		</label>	

	</div>	

</div>

<br/>