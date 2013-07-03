<div id="buddypress">

	<?php include( bp_docs_locate_template( 'single/sidebar.php' ) ) ?>

	<?php include( apply_filters( 'bp_docs_header_template', bp_docs_locate_template( 'docs-header.php' ) ) ) ?>

	<?php
	// No media support at the moment. Want to integrate with something like BP Group Documents
	// include_once ABSPATH . '/wp-admin/includes/media.php' ;

	if ( !function_exists( 'wp_editor' ) ) {
		require_once ABSPATH . '/wp-admin/includes/post.php' ;
		wp_tiny_mce();
	}

	?>

	<?php do_action( 'template_notices' ) ?>

	<div class="doc-content">

	<div id="idle-warning" style="display:none">
		<p><?php _e( 'You have been idle for <span id="idle-warning-time"></span>', 'bp-docs' ) ?></p>
	</div>

	<form action="" method="post" class="standard-form" id="doc-form">
	    <div class="doc-header">
		<?php if ( bp_docs_is_existing_doc() ) : ?>
			<input type="hidden" id="existing-doc-id" value="<?php the_ID() ?>" />
		<?php endif ?>
	    </div>
	    <div class="doc-content-wrapper">
		<div id="doc-content-title">
			<label for="doc[title]"><?php _e( 'Title', 'bp-docs' ) ?></label>
			<input type="text" id="doc-title" name="doc[title]" class="long" value="<?php bp_docs_edit_doc_title() ?>" />
		</div>

		<?php if ( bp_docs_is_existing_doc() ) : ?>
			<div id="doc-content-permalink">
				<label for="doc[permalink]"><?php _e( 'Permalink', 'bp-docs' ) ?></label>
				<code><?php echo trailingslashit( bp_get_root_domain() ) . BP_DOCS_SLUG . '/' ?></code><input type="text" id="doc-permalink" name="doc[permalink]" class="long" value="<?php bp_docs_edit_doc_slug() ?>" />
			</div>
		<?php endif ?>

		<div id="doc-content-textarea">
			<label id="content-label" for="doc_content"><?php _e( 'Content', 'bp-docs' ) ?></label>
			<div id="editor-toolbar">
				<?php
					if ( function_exists( 'wp_editor' ) ) {
						wp_editor( bp_docs_get_edit_doc_content(), 'doc_content', array(
							'media_buttons' => false,
							'dfw'		=> false
						) );
					} else {
						the_editor( bp_docs_get_edit_doc_content(), 'doc_content', 'doc[title]', false );
					}
				?>
			</div>
		</div>

		<?php if ( apply_filters( 'bp_docs_enable_attachments', true ) ) : ?>
			<div id="doc-attachments">
				<label for="insert-media-button"><?php _e( 'Attachments', 'bp-docs' ) ?></label>
				<?php include ( bp_docs_locate_template( 'single/attachments.php' ) ) ?>
			</div>
		<?php endif ?>

		<div id="doc-meta">
			<?php if ( bp_is_active( 'groups' ) && bp_docs_current_user_can( 'manage' ) && apply_filters( 'bp_docs_allow_associated_group', true ) ) : ?>
				<div id="doc-associated-group" class="doc-meta-box">
					<div class="toggleable">
						<p class="toggle-switch" id="associated-group-toggle"><?php _e( 'Associated Group', 'bp-docs' ) ?></p>

						<div class="toggle-content">
							<table class="toggle-table" id="toggle-table-associated-group">
								<?php bp_docs_doc_associated_group_markup() ?>
							</table>
						</div>
					</div>
				</div>
			<?php endif ?>

			<?php if ( bp_docs_current_user_can( 'manage' ) && apply_filters( 'bp_docs_allow_access_settings', true ) ) : ?>
				<div id="doc-settings" class="doc-meta-box">
					<div class="toggleable">
						<p class="toggle-switch" id="settings-toggle"><?php _e( 'Access', 'bp-docs' ) ?></p>

						<div class="toggle-content">
							<table class="toggle-table" id="toggle-table-settings">
								<?php bp_docs_doc_settings_markup() ?>
							</table>
						</div>
					</div>
				</div>
			<?php endif ?>

			<div id="doc-tax" class="doc-meta-box">
				<div class="toggleable">
					<p id="tags-toggle-edit" class="toggle-switch"><?php _e( 'Tags', 'bp-docs' ) ?></p>

					<div class="toggle-content">
						<table class="toggle-table" id="toggle-table-tags">
							<tr>
								<td class="desc-column">
									<label for="bp_docs_tag"><?php _e( 'Tags are words or phrases that help to describe and organize your Docs.', 'bp-docs' ) ?></label>
									<span class="description"><?php _e( 'Separate tags with commas (for example: <em>orchestra, snare drum, piccolo, Brahms</em>)', 'bp-docs' ) ?></span>
								</td>

								<td>
									<?php bp_docs_post_tags_meta_box() ?>
								</td>
							</tr>
						</table>
					</div>
				</div>
			</div>

			<div id="doc-parent" class="doc-meta-box">
				<div class="toggleable">
					<p class="toggle-switch" id="parent-toggle"><?php _e( 'Parent', 'bp-docs' ) ?></p>

					<div class="toggle-content">
						<table class="toggle-table" id="toggle-table-parent">
							<tr>
								<td class="desc-column">
									<label for="parent_id"><?php _e( 'Select a parent for this Doc.', 'bp-docs' ) ?></label>

									<span class="description"><?php _e( '(Optional) Assigning a parent Doc means that a link to the parent will appear at the bottom of this Doc, and a link to this Doc will appear at the bottom of the parent.', 'bp-docs' ) ?></span>
								</td>

								<td class="content-column">
									<?php bp_docs_edit_parent_dropdown() ?>
								</td>
							</tr>
						</table>
					</div>
				</div>
			</div>
		</div>

		<div style="clear: both"> </div>

		<div id="doc-submit-options">

			<?php wp_nonce_field( 'bp_docs_save' ) ?>

			<?php $doc_id = bp_docs_is_existing_doc() ? get_the_ID() : 0 ?>
			<input type="hidden" id="doc_id" name="doc_id" value="<?php echo $doc_id ?>" />
			<input type="submit" name="doc-edit-submit" id="doc-edit-submit" value="<?php _e( 'Save', 'bp-docs' ) ?>"> <a href="<?php bp_docs_cancel_edit_link() ?>" class="action safe"><?php _e( 'Cancel', 'bp-docs' ); ?></a>

			<?php if ( bp_docs_is_existing_doc() ) : ?>
				<?php if ( bp_docs_current_user_can( 'manage' ) ) : ?><a class="delete-doc-button confirm" href="<?php bp_docs_delete_doc_link() ?>"><?php _e( 'Delete', 'bp-docs' ) ?></a><?php endif ?>
			<?php endif ?>
		</div>


		<div style="clear: both"> </div>
	    </div>
	</form>

	</div><!-- .doc-content -->

	<?php bp_docs_inline_toggle_js() ?>

	<?php if ( !function_exists( 'wp_editor' ) ) : ?>
	<script type="text/javascript">
	jQuery(document).ready(function($){
		/* On some setups, it helps TinyMCE to load if we fire the switchEditors event on load */
		if ( typeof(switchEditors) == 'object' ) {
			if ( !$("#edButtonPreview").hasClass('active') ) {
				switchEditors.go('doc_content', 'tinymce');
			}
		}
	},(jQuery));
	</script>
	<?php endif ?>

	<?php /* Important - do not remove. Needed for autosave stuff */ ?>
	<div style="display:none;">
	<div id="still_working_content" name="still_working_content">
		<br />
		<h3><?php _e( 'Are you still there?', 'bp-docs' ) ?></h3>

		<p><?php _e( 'In order to prevent overwriting content, only one person can edit a given doc at a time. For that reason, you must periodically ensure the system that you\'re still actively editing. If you are idle for more than 30 minutes, your changes will be auto-saved, and you\'ll be sent out of Edit mode so that others can access the doc.', 'bp-docs' ) ?></p>

		<a href="#" onclick="jQuery.colorbox.close(); return false" class="button"><?php _e( 'I\'m still editing!', 'bp-docs' ) ?></a>
	</div>
	</div>

</div><!-- /#buddypress -->
