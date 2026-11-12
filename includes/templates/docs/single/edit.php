<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$doc_id = 0;
$current_doc = bp_docs_get_current_doc();
if ( $current_doc ) {
	$doc_id = $current_doc->ID;
}

$bp_docs_do_theme_compat = is_buddypress() && bp_docs_do_theme_compat( 'single/edit.php' );
if ( ! $bp_docs_do_theme_compat ) : ?>
<div id="buddypress" class="<?php bp_docs_buddypress_container_class(); ?>">
<?php endif; ?>

<div class="<?php bp_docs_container_class(); ?>">
	<?php include( bp_docs_locate_template( 'single/sidebar.php' ) ) ?>

	<?php include( apply_filters( 'bp_docs_header_template', bp_docs_locate_template( 'docs-header.php' ) ) ) ?>

	<?php do_action( 'template_notices' ) ?>

	<div class="doc-content">

	<form action="" method="post" class="standard-form" id="doc-form">
	    <div class="doc-header">
		<?php if ( bp_docs_is_existing_doc() ) : ?>
			<input type="hidden" id="existing-doc-id" value="<?php echo esc_attr( $doc_id );; ?>" />
		<?php endif ?>
	    </div>
	    <div class="doc-content-wrapper">
		<div id="doc-content-title">
			<label for="doc-title"><?php esc_html_e( 'Title', 'buddypress-docs' ) ?></label>
			<input type="text" id="doc-title" name="doc[title]" class="long" value="<?php bp_docs_edit_doc_title() ?>" />
		</div>

		<?php if ( bp_docs_is_existing_doc() ) : ?>
			<div id="doc-content-permalink">
				<label for="doc-permalink"><?php esc_html_e( 'Permalink', 'buddypress-docs' ) ?></label>
				<code><?php echo esc_html( bp_docs_get_archive_link() ); ?></code><input type="text" id="doc-permalink" name="doc[permalink]" class="long" value="<?php bp_docs_edit_doc_slug() ?>" />
			</div>
		<?php endif ?>

		<?php do_action( 'bp_docs_before_doc_edit_content', $doc_id ) ?>

		<div id="doc-content-textarea">
			<label id="content-label" for="doc_content"><?php esc_html_e( 'Content', 'buddypress-docs' ) ?></label>
			<div id="editor-toolbar">
				<?php
					$wp_editor_args = apply_filters( 'bp_docs_wp_editor_args', array(
						'media_buttons' => false,
						'dfw'           => false,
					) );
					wp_editor( bp_docs_get_edit_doc_content(), 'doc_content', $wp_editor_args );
				?>
			</div>
		</div>

		<?php do_action( 'bp_docs_after_doc_edit_content', $doc_id ) ?>

		<?php if ( bp_docs_enable_attachments() ) : ?>
			<div id="doc-attachments">
				<label for="insert-media-button"><?php esc_html_e( 'Attachments', 'buddypress-docs' ) ?></label>
				<?php include ( bp_docs_locate_template( 'single/attachments.php' ) ) ?>
			</div>
		<?php endif ?>

		<div id="doc-meta">
			<?php do_action( 'bp_docs_opening_meta_box', $doc_id ) ?>

			<?php if ( bp_is_active( 'groups' ) && current_user_can( 'bp_docs_manage', $doc_id ) && apply_filters( 'bp_docs_allow_associated_group', true ) ) : ?>
				<?php do_action( 'bp_docs_before_assoc_groups_meta_box', $doc_id ); ?>

				<div id="doc-associated-group" class="doc-meta-box">
					<div class="toggleable <?php bp_docs_toggleable_open_or_closed_class( 'associated-group-meta-box' ) ?>">
						<p class="toggle-switch" id="associated-group-toggle">
							<span class="hide-if-js toggle-link-no-js"><?php esc_html_e( 'Associated Group', 'buddypress-docs' ) ?></span>
							<a class="hide-if-no-js toggle-link" id="associated-toggle-link" href="#"><span class="show-pane plus-or-minus"></span><?php esc_html_e( 'Associated Group', 'buddypress-docs' ) ?></a>
						</p>

						<div class="toggle-content">
							<table class="toggle-table" id="toggle-table-associated-group">
								<?php bp_docs_doc_associated_group_markup() ?>
							</table>
						</div>
					</div>
				</div>

				<?php do_action( 'bp_docs_after_assoc_groups_meta_box', $doc_id ); ?>
			<?php endif ?>

			<?php if ( current_user_can( 'bp_docs_manage', $doc_id ) && apply_filters( 'bp_docs_allow_access_settings', true ) ) : ?>
				<?php do_action( 'bp_docs_before_access_settings_meta_box', $doc_id ) ?>

				<div id="doc-settings" class="doc-meta-box">
					<div class="toggleable <?php bp_docs_toggleable_open_or_closed_class( 'access-meta-box' ) ?>">
						<p class="toggle-switch" id="settings-toggle">
							<span class="hide-if-js toggle-link-no-js"><?php esc_html_e( 'Access', 'buddypress-docs' ) ?></span>
							<a class="hide-if-no-js toggle-link" id="settings-toggle-link" href="#"><span class="show-pane plus-or-minus"></span><?php esc_html_e( 'Access', 'buddypress-docs' ) ?></a>
						</p>

						<div class="toggle-content">
							<table class="toggle-table" id="toggle-table-settings">
								<?php bp_docs_doc_settings_markup() ?>
							</table>
						</div>
					</div>
				</div>

				<?php do_action( 'bp_docs_after_access_settings_meta_box', $doc_id ) ?>
			<?php endif ?>

			<?php do_action( 'bp_docs_before_tags_meta_box', $doc_id ) ?>

			<div id="doc-tax" class="doc-meta-box">
				<div class="toggleable <?php bp_docs_toggleable_open_or_closed_class( 'tags-meta-box' ) ?>">
					<p id="tags-toggle-edit" class="toggle-switch">
						<span class="hide-if-js toggle-link-no-js"><?php esc_html_e( 'Tags', 'buddypress-docs' ) ?></span>
						<a class="hide-if-no-js toggle-link" id="tags-toggle-link" href="#"><span class="show-pane plus-or-minus"></span><?php esc_html_e( 'Tags', 'buddypress-docs' ) ?></a>
					</p>

					<div class="toggle-content">
						<table class="toggle-table" id="toggle-table-tags">
							<tr>
								<td class="desc-column">
									<label for="bp_docs_tag"><?php esc_html_e( 'Tags are words or phrases that help to describe and organize your Docs.', 'buddypress-docs' ) ?></label>
									<span class="description"><?php echo wp_kses( __( 'Separate tags with commas (for example: <em>orchestra, snare drum, piccolo, Brahms</em>)', 'buddypress-docs' ), array( 'em' => array() ) ) ?></span>
								</td>

								<td>
									<?php bp_docs_post_tags_meta_box() ?>
								</td>
							</tr>
						</table>
					</div>
				</div>
			</div>

			<?php do_action( 'bp_docs_after_tags_meta_box', $doc_id ) ?>

			<?php if ( apply_filters( 'bp_docs_allow_parent_doc_setting', true, $doc_id ) ) : ?>
				<?php do_action( 'bp_docs_before_parent_meta_box', $doc_id ) ?>

				<div id="doc-parent" class="doc-meta-box">
					<div class="toggleable <?php bp_docs_toggleable_open_or_closed_class( 'parent-meta-box' ) ?>">
						<p class="toggle-switch" id="parent-toggle">
							<span class="hide-if-js toggle-link-no-js"><?php esc_html_e( 'Parent', 'buddypress-docs' ) ?></span>
							<a class="hide-if-no-js toggle-link" id="parent-toggle-link" href="#"><span class="show-pane plus-or-minus"></span><?php esc_html_e( 'Parent', 'buddypress-docs' ) ?></a>
						</p>

						<div class="toggle-content">
							<table class="toggle-table" id="toggle-table-parent">
								<tr>
									<td class="desc-column">
										<label for="parent_id"><?php esc_html_e( 'Select a parent for this Doc.', 'buddypress-docs' ) ?></label>

										<span class="description"><?php esc_html_e( '(Optional) Assigning a parent Doc means that a link to the parent will appear at the bottom of this Doc, and a link to this Doc will appear at the bottom of the parent.', 'buddypress-docs' ) ?></span>
									</td>

									<td class="content-column">
										<?php bp_docs_edit_parent_dropdown() ?>
									</td>
								</tr>
							</table>
						</div>
					</div>
				</div>
				<?php do_action( 'bp_docs_after_parent_meta_box', $doc_id ) ?>
			<?php endif; ?>

			<?php do_action( 'bp_docs_closing_meta_box', $doc_id ) ?>
		</div>

		<div style="clear: both"> </div>

		<div id="doc-submit-options">

			<?php wp_nonce_field( 'bp_docs_save' ) ?>

			<input type="hidden" id="doc_id" name="doc_id" value="<?php echo esc_attr( $doc_id ); ?>" />
			<input type="submit" name="doc-edit-submit" id="doc-edit-submit" value="<?php esc_attr_e( 'Save', 'buddypress-docs' ) ?>"> <input type="submit" name="doc-edit-submit-continue" id="doc-edit-submit-continue" value="<?php esc_attr_e( 'Save and Continue Editing', 'buddypress-docs' ) ?>" /> <a href="<?php bp_docs_cancel_edit_link() ?>" class="action safe confirm"><?php esc_html_e( 'Cancel', 'buddypress-docs' ); ?></a>

			<?php if ( bp_docs_is_existing_doc() ) : ?>
				<?php if ( current_user_can( 'bp_docs_manage', $doc_id ) ) : ?>
					<?php bp_docs_delete_doc_button() ?>
				<?php endif ?>
			<?php endif ?>
		</div>


		<div style="clear: both"> </div>
	    </div>
	</form>

	</div><!-- .doc-content -->

	<?php /* Important - do not remove. Needed for autosave stuff */ ?>
	<div style="display:none;">
	<div id="still_working_content" name="still_working_content">
		<br />
		<h3><?php esc_html_e( 'Are you still there?', 'buddypress-docs' ) ?></h3>

		<p><?php esc_html_e( 'In order to prevent overwriting content, only one person can edit a given doc at a time. For that reason, you must periodically ensure the system that you\'re still actively editing. If you are idle for more than 30 minutes, your changes will be auto-saved, and you\'ll be sent out of Edit mode so that others can access the doc.', 'buddypress-docs' ) ?></p>

		<a href="#" onclick="jQuery.colorbox.close(); return false" class="button"><?php esc_html_e( 'I\'m still editing!', 'buddypress-docs' ) ?></a>
	</div>
	</div>

</div><!-- .bp-docs -->

<?php if ( ! $bp_docs_do_theme_compat ) : ?>
</div><!-- /#buddypress -->
<?php endif; ?>
