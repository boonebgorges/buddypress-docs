<table class="doctable">
<tbody>
<?php if ( bp_docs_enable_folders_for_current_context() ) : ?>
	<?php if ( bp_docs_include_folders_in_loop_view() ) : ?>
		<?php foreach ( bp_docs_get_folders() as $folder ) :
			$folder_title = esc_html( $folder->post_title );
			$folder_id    = $folder->ID;
			$folder_url   = esc_url( bp_docs_get_folder_url( $folder_id ) );
		?>
			<tr class="folder-row">
				<?php /* Just to keep things even */ ?>
				<?php if ( bp_docs_enable_attachments() ) : ?>
					<td class="attachment-clip-cell">
						<?php bp_docs_attachment_icon() ?>
					</td>
				<?php endif ?>

				<td class="folder-row-name" colspan=10>
					<div class="toggleable <?php bp_docs_toggleable_open_or_closed_class(); ?>">
						<span class="folder-toggle-link toggle-link-js"><a class="toggle-folder" id="expand-folder-<?php echo $folder->ID; ?>" data-folder-id="<?php echo $folder->ID; ?>" href="<?php echo esc_url( bp_docs_get_folder_url( $folder->ID ) ) ?>"><span class="hide-if-no-js"><?php bp_docs_genericon( 'expand', $folder->ID ); ?></span><?php bp_docs_genericon( 'category', $folder->ID ); ?><?php echo esc_html( $folder->post_title ) ?></a></span>
						<div class="toggle-content folder-loop"></div>
					</div>
				</td>
			</tr>
		<?php endforeach ?>
	<?php endif; ?>
<?php endif; /* bp_docs_enable_folders_for_current_context() */ ?>

<?php if ( bp_docs_has_docs() ) : ?>
	<?php while ( bp_docs_has_docs() ) : bp_docs_the_doc() ?>
		<tr<?php bp_docs_doc_row_classes(); ?>>
			<?php if ( bp_docs_enable_attachments() ) : ?>
				<td class="attachment-clip-cell">
					<?php bp_docs_attachment_icon() ?>
				</td>
			<?php endif ?>

			<td class="title-cell">
				<?php bp_docs_genericon( 'document', get_the_ID() ); ?><a href="<?php bp_docs_doc_link() ?>"><?php the_title() ?></a> <?php bp_docs_doc_trash_notice(); ?>

				<?php if ( bp_docs_get_excerpt_length() ) : ?>
					<div class="doc-excerpt">
						<?php the_excerpt() ?>
					</div>
				<?php endif ?>

				<?php do_action( 'bp_docs_loop_after_doc_excerpt' ) ?>

				<div class="row-actions">
					<?php bp_docs_doc_action_links() ?>
				</div>

				<div class="bp-docs-attachment-drawer" id="bp-docs-attachment-drawer-<?php echo get_the_ID() ?>">
					<?php bp_docs_doc_attachment_drawer() ?>
				</div>
			</td>

			<?php if ( ! bp_docs_is_started_by() ) : ?>
				<td class="author-cell">
					<a href="<?php echo bp_core_get_user_domain( get_the_author_meta( 'ID' ) ) ?>" title="<?php echo bp_core_get_user_displayname( get_the_author_meta( 'ID' ) ) ?>"><?php echo bp_core_get_user_displayname( get_the_author_meta( 'ID' ) ) ?></a>
				</td>
			<?php endif; ?>

			<td class="date-cell created-date-cell">
				<?php echo get_the_date() ?>
			</td>

			<td class="date-cell edited-date-cell">
				<?php echo get_the_modified_date() ?>
			</td>

			<?php do_action( 'bp_docs_loop_additional_td' ) ?>
		</tr>
	<?php endwhile ?>

	<tr class="folder-meta-info">
			<?php if ( bp_docs_enable_attachments() ) : ?>
				<td class="attachment-clip-cell">
					<?php bp_docs_attachment_icon() ?>
				</td>
			<?php endif ?>
			<td class="folder-meta-info-statement" colspan=10>
				<?php printf( __( 'Viewing %1$s-%2$s of %3$s docs in this folder.', 'bp-docs' ), bp_docs_get_current_docs_start(), bp_docs_get_current_docs_end(), bp_docs_get_total_docs_num() ) ?> <br/>
				<a href="<?php echo esc_url( bp_docs_get_folder_url( $_GET['folder'] ) ); ?>"><?php printf( __( 'View all docs in <strong>%s</strong>.', 'bp-docs' ), get_the_title( $_GET['folder'] ) ); ?></a>
			</td>
		</tr>
<?php else: ?>
	<tr><td><p class="no-docs"><?php _e( 'There are no docs for this view.', 'bp-docs' ); ?></p></td></tr>
<?php endif; ?>
</tbody>
</table>