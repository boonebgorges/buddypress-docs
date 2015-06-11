<div id="buddypress">

<?php include( apply_filters( 'bp_docs_header_template', bp_docs_locate_template( 'docs-header.php' ) ) ) ?>

<?php if ( current_user_can( 'bp_docs_manage_folders' ) && bp_docs_is_folder_manage_view() ) : ?>
	<?php bp_locate_template( 'docs/manage-folders.php', true ) ?>
<?php else : ?>

	<?php $breadcrumb_markup = bp_docs_get_directory_breadcrumb() ?>
	<?php if ( strip_tags( $breadcrumb_markup ) ) : ?>
		<h2 class="directory-title">
			<?php echo $breadcrumb_markup ?>
		</h2>
	<?php endif; ?>

	<div class="docs-info-header">
		<?php bp_docs_info_header() ?>
	</div>

	<?php if ( bp_docs_enable_folders_for_current_context() ) : ?>
		<div class="folder-action-links">
			<?php if ( current_user_can( 'bp_docs_manage_folders' ) ) : ?>
				<div class="manage-folders-link">
					<a href="<?php bp_docs_manage_folders_url() ?>"><?php _e( 'Manage Folders', 'bp-docs' ) ?></a>
				</div>
			<?php endif ?>

			<div class="toggle-folders-link hide-if-no-js">
				<a href="#" class="toggle-folders" id="toggle-folders-hide"><?php _e( 'Hide Folders', 'bp-docs' ) ?></a>
				<a href="#" class="toggle-folders" id="toggle-folders-show"><?php _e( 'Show Folders', 'bp-docs' ) ?></a>
			</div>
		</div>
	<?php endif; ?>

	<div class="docs-list">

	<?php if ( bp_docs_enable_folders_for_current_context() ) : ?>
		<?php /* The '..' row */ ?>
		<?php if ( ! empty( $_GET['folder'] ) ) : ?>
			<tr class="folder-row">
				<?php /* Just to keep things even */ ?>
				<?php if ( bp_docs_enable_attachments() ) : ?>
					<td class="attachment-clip-cell">
						<?php bp_docs_attachment_icon() ?>
					</td>
				<?php endif ?>

				<td colspan=10>
					<i class="genericon genericon-category"></i><a href="<?php echo esc_url( bp_docs_get_parent_folder_url() ) ?>"><?php _ex( '..', 'up one folder', 'bp-docs' ) ?></a>
				</td>
			</tr>
		<?php endif ?>

		<?php if ( ! isset( $_GET['bpd_tag'] ) ) : ?>
			<?php foreach ( bp_docs_get_folders() as $folder ) : ?>
				<tr class="folder-row">
					<?php /* Just to keep things even */ ?>
					<?php if ( bp_docs_enable_attachments() ) : ?>
						<td class="attachment-clip-cell">
							<?php bp_docs_attachment_icon() ?>
						</td>
					<?php endif ?>

					<td colspan=10>
						<i class="genericon genericon-category"></i><a href="<?php echo esc_url( bp_docs_get_folder_url( $folder->ID ) ) ?>"><?php echo esc_html( $folder->post_title ) ?></a>
					</td>
				</tr>
			<?php endforeach ?>
		<?php endif; ?>
	<?php endif; /* bp_docs_enable_folders_for_current_context() */ ?>

	<?php $has_docs = false ?>
	<?php if ( bp_docs_has_docs() ) : ?>
		<?php $has_docs = true ?>
		<?php while ( bp_docs_has_docs() ) : bp_docs_the_doc() ?>
			<div <?php bp_docs_doc_row_classes( get_the_ID() ); ?>>
				<?php if ( bp_docs_enable_attachments() ) : ?>
					<div class="attachment-clip-cell">
						<?php bp_docs_attachment_icon() ?>
					</div>
				<?php endif ?>

				<h3 class="doc-title">
					<i class="genericon genericon-document"></i><a href="<?php bp_docs_doc_link() ?>"><?php the_title() ?></a> <?php bp_docs_doc_trash_notice(); ?>
				</h3>

				<?php if ( bp_docs_get_excerpt_length() ) : ?>
					<div class="doc-excerpt">
						<?php the_excerpt() ?>
					</div>
				<?php endif ?>

				<?php do_action( 'bp_docs_loop_after_doc_excerpt' ) ?>

				<div class="doc-author-meta">
					<span class="doc-created-meta">
						<?php $author_id = get_the_author_meta( 'ID' );
						      printf(
							  __( 'Created %s by %s', 'bp-docs' ),
							  get_the_date(),
							  sprintf( '<a href="%s" title="%s">%s</a>', esc_url( bp_core_get_user_domain( $author_id ) ), esc_html( bp_core_get_user_displayname( $author_id ) ), esc_html( bp_core_get_user_displayname( $author_id ) ) )
						      ); ?>
					</span>

					<?php $modified_id = get_post_meta( get_the_ID(), 'bp_docs_last_editor', true ) ?>
					<?php if ( $modified_id && get_the_date( 'U' ) !== get_the_modified_date( 'U' ) ) : ?>
						<span class="doc-modified-meta">
							<?php printf(
								  ' &middot; ' . __( 'Edited %s by %s', 'bp-docs' ),
								  get_the_modified_date(),
								  sprintf( '<a href="%s" title="%s">%s</a>', esc_url( bp_core_get_user_domain( $modified_id ) ), esc_html( bp_core_get_user_displayname( $modified_id ) ), esc_html( bp_core_get_user_displayname( $modified_id ) ) )
							      ); ?>
						</span>
					<?php endif; ?>
				</div><!-- .doc-author-meta -->

				<?php do_action( 'bp_docs_loop_after_doc_meta', get_the_ID() ) ?>

				<div class="row-actions">
					<?php bp_docs_doc_action_links() ?>
				</div>

				<div class="bp-docs-attachment-drawer" id="bp-docs-attachment-drawer-<?php echo get_the_ID() ?>">
					<?php bp_docs_doc_attachment_drawer() ?>
				</div>
			</div>
		<?php endwhile ?>
	<?php endif ?>

	</div><!-- #docs-list -->

	<?php if ( $has_docs ) : ?>
		<div id="bp-docs-pagination">
			<div id="bp-docs-pagination-count">
				<?php printf( __( 'Viewing %1$s-%2$s of %3$s docs', 'bp-docs' ), bp_docs_get_current_docs_start(), bp_docs_get_current_docs_end(), bp_docs_get_total_docs_num() ) ?>
			</div>

			<div id="bp-docs-paginate-links">
				<?php bp_docs_paginate_links() ?>
			</div>
		</div>
	<?php endif; ?>
<?php endif; ?>
</div><!-- /#buddypress -->
