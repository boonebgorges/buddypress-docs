<div class="checkbox">
	<label>		<input type="checkbox" value="1" id="groupwiki-enable-wiki" name="groupwiki-enable-wiki" onclick="jQuery('#wiki-create-fields, #wiki-create-fields-instructions').toggleClass('wiki-hidden');"/> <?php _e( 'Enable group wiki', 'bp-wiki' ); ?>	</label>		<hr>
</div><p id="wiki-create-fields-instructions"><?php _e( 'Wiki creation options will be displayed once you have ticked to enable the group wiki.', 'bp-wiki' ); ?></p><div id="wiki-create-fields" class="wiki-hidden">
	<h3><?php _e( 'Wiki Group Homepage Text', 'bp-wiki' ); ?></h3>	<p><?php _e( 'This text will appear on the wiki homepage for your group.  You can edit this later via the group admin area.  (350 character limit)', 'bp-wiki' ); ?></p>	<textarea id="wiki-homepage-summary" name="wiki-homepage-summary" maxlength="350"><?php _e( 'This is the homepage for your group wiki.  The below table contains links to the indivdual pages available to you.' ); ?></textarea>	<h3><?php _e( 'Default Page Privacy', 'bp-wiki' ); ?></h3>	<p><?php _e( 'Please select the default privacy levels for your wiki pages.  These are separate to your group privacy settings and you can later apply settings to individual pages through the group admin menu.', 'bp-wiki' ); ?></p>
	<input type="radio" name="wiki-privacy" value="public" checked="checked"/>
	  <strong><?php _e( 'Public', 'bp-wiki' ); ?></strong>	  	  <?php _e( ' - Wiki pages are viewable to all site visitors.', 'bp-wiki' ); ?><br/>
	<input type="radio" name="wiki-privacy" value="member-only" />
	  <strong><?php _e( 'Private', 'bp-wiki' ); ?></strong>	  	  <?php _e( ' - Wiki pages may only be viewed by members of the group.', 'bp-wiki' ); ?><br/>	  
	<br/>
	<h3><?php _e( 'Default Page Editing Privileges', 'bp-wiki' ); ?></h3>
	<p><?php _e( 'Please select the default edit privileges for your members.  Once again, this can be further fine tuned on a page-by-page basis later in the group admin menu.', 'bp-wiki' ); ?></p>
	<input type="radio" name="wiki-edit-rights" value="all-members" checked="checked"/>
	<strong><?php _e( 'All Members', 'bp-wiki' ); ?></strong>		<?php _e( ' - All members of the group can edit wiki pages.', 'bp-wiki' ); ?><br/>
	<input type="radio" name="wiki-edit-rights" value="moderator-only" />
	<strong><?php _e( 'Moderators', 'bp-wiki' ); ?></strong>		<?php _e( ' - Only group moderators (and admins) can edit wiki pages .', 'bp-wiki' ); ?><br/>		<input type="radio" name="wiki-edit-rights" value="admin-only" />		<strong><?php _e( 'Administrators', 'bp-wiki' ); ?></strong>	<?php _e( ' - Only group admins can edit wiki pages.', 'bp-wiki' ); ?><br/>
	<br/> 	<h3><?php _e( 'Initial Page Creation', 'bp-wiki' ); ?></h3>	
	<p><?php _e( 'This section allows you to create some initial pages for your group wiki.  Click Add Another to create additional pages and X to remove a page.', 'bp-wiki' ); ?><br/>
	<ol id="wiki-page-titles">
		<li class="wiki-page-title-input">
			<input type="textarea" id="wiki-page-title[]" name="wiki-page-title[]" class="wiki-page-title-input" value=""/> 			<button class="wiki" onclick="jQuery(this).parent().remove();return false;"><?php _e( 'X', 'bp-wiki' ); ?></button>
		</li>
	</ol>
	<button class="wiki" onclick="jQuery('#wiki-list-item-clone-source').children().clone().appendTo('#wiki-page-titles');return false;"><?php _e( 'Add Another', 'bp-wiki' ); ?></button>
	<br/>	<br/>
	<h3><?php _e( 'Member Page Creation', 'bp-wiki' ); ?></h3>	
	<div class="checkbox">
		<label><input type="checkbox" id="groupwiki-member-page-create" name="groupwiki-member-page-create"/>
		<?php _e( 'Click here to allow all group members to create pages in the group wiki homepage.', 'bp-wiki' ); ?>
		</label>	
	</div>	
	<div id="wiki-list-item-clone-source" class="wiki-hidden">
		<li class="wiki-page-title-input">			<input type="textarea" id="wiki-page-title[]" name="wiki-page-title[]" class="wiki-page-title-input" value=""/> 
			<button class="wiki" onclick="jQuery(this).parent().remove();return false;"><?php _e( 'X', 'bp-wiki' ); ?></button>
		</li>	</div>
</div>
<br/>