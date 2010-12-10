<script type="text/javascript">
jQuery(document).ready( function() {
	tinyMCE.execCommand('mceAddControl', false, 'wiki_page_content_box');
});
</script>

<?php
global $bp;

// Echo our breadcrumbs for nav
echo bp_wiki_group_breadcrumbs( $bp_wiki_group_new_wiki_page );

// If we're editing a previously created page
if ( !$bp_wiki_group_new_wiki_page ) {

	// Get wiki page based on slug
	$wiki_page = bp_wiki_get_page_from_slug( $bp->action_variables[0] );

	if ( bp_wiki_can_edit_wiki_page( $wiki_page->ID ) ) {

		/* Wiki Page Summary */
		?>
		
		<form id="wiki-group-page-edit" action="<?php echo bp_wiki_get_group_page_url( $bp->groups->current_group->id, $wiki_page->ID ); ?>" method="post">

			<?php wp_nonce_field( 'wiki-group-page-edit' ); ?>

			<input type="hidden" id="wiki_page_id" name="wiki_page_id" value="<?php echo $wiki_page->ID; ?>"/>

			<input type="hidden" id="wiki_page_action" name="wiki_page_action" value="edit-group-page-save"/>

			<div class="wiki-group-page-title-bar">

				<span id="wiki-page-title-image"><img src="<?php echo apply_filters( 'bp_wiki_locate_group_wiki_title_image', 'images/wiki-title.png' ); ?>" class="wiki-group-page-title-image" alt="Wiki Title"/></span>

					<span id="wiki-page-title-text"><?php echo $wiki_page->post_title; ?></span>

				<?php

					if ( bp_wiki_can_edit_wiki_page( $wiki_page->ID ) ) {
						?>
						<span id="wiki-group-page-edit-title-button">

							<button class="wiki" onclick="wikiGroupPageEditTitleStart();return false;"><?php _e( 'Edit Title', 'bp-wiki' ); ?></button>

						</span>

						<div id="wiki-group-page-edit-page-button">
						
							<button class="wiki" onclick="jQuery('#wiki-group-page-edit').submit;"><?php _e( 'Save Page' ); ?></button>

						</div>
						<?php
					}
				?>
			</div>

			<textarea id="wiki_page_content_box" name="wiki_page_content_box" class="wiki-group-page-content">
				<?php echo apply_filters( 'the_editor', $wiki_page->post_content ); ?>
			</textarea>

		</form>
		<?php
	} else {
		?>
		<div id="message" class="warning">

			<p><?php _e( 'You do not have access to edit this page.', 'bp-wiki' ); ?></p>

		</div>
		<?php
	}

} else {

	// Creating a new page
	$wiki_group = new BP_Groups_Group( $bp->groups->current_group->id, false, false );	 	

	?>

	<div id="message" class="info">

		<p>
		<?php 
		if ( $bp->action_variables[0] != 'new' ) {

			echo __( 'That page does not currently exist.', 'bp-wiki' ) . '<br/>'; 

		}

		_e( 'Please fill in the fields below if you would like to create a new page.', 'bp-wiki' ); 
		?>
		</p>

	</div>

	<form id="wiki-group-page-edit" action="<?php echo bp_get_group_permalink( $wiki_group ) . $action_slug = $bp->current_action; ?>" method="post">

		<?php wp_nonce_field( 'wiki-group-page-create' ); ?>

		<input type="hidden" id="wiki_page_action" name="wiki_page_action" value="new-group-page-create"/>

		<div class="wiki-group-page-title-bar">

			<span id="wiki-page-title-image"><img src="<?php echo apply_filters( 'bp_wiki_locate_group_wiki_title_image', 'images/wiki-title.png' ); ?>" class="wiki-group-page-title-image" alt="Wiki Title"/></span>

				<span id="wiki-page-title-text"><input type="text" id="wiki-page-title-textbox" name="wiki-page-title-textbox" value="<?php if ( $bp->action_variables[0] != 'new' ) echo $bp->action_variables[0]; ?>"/></span>

				<div id="wiki-group-page-edit-page-button">

					<button class="wiki" onclick="jQuery('#wiki-group-page-edit').submit;"><?php _e( 'Save Page' ); ?></button>

				</div>

		</div>

		<textarea id="wiki_page_content_box" name="wiki_page_content_box" class="wiki-group-page-content" ></textarea>

	</form>
	<?php	
}

?>