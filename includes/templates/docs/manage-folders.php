<?php if ( ! empty( $_GET['delete-folder'] ) ) : ?>

	<h3><?php _e( 'Delete Folder', 'buddypress-docs' ) ?></h3>
	<?php $folder_to_delete = get_post( $_GET['delete-folder'] ); ?>

	<?php if ( ! is_a( $folder_to_delete, 'WP_Post' ) || 'bp_docs_folder' !== $folder_to_delete->post_type ) : ?>
		<p><?php _e( 'This does not appear to be a valid folder.', 'buddypress-docs' ) ?></p>
	<?php else : ?>
		<form action="<?php echo bp_get_requested_url() ?>" method="post">

		<p>
			<?php printf(
				__( 'You are about to delete the folder %s.', 'buddypress-docs' ),
				sprintf( '<a href="%s">%s</a>', esc_url( bp_docs_get_folder_url( $folder_to_delete->ID ) ), esc_html( $folder_to_delete->post_title ) )
			) ?>
		</p>

		<p>
			<?php _e( '<strong>WARNING:</strong> Deleting a folder will also delete all Docs and subfolders in the folder. This action cannot be undone. To preserve your content, remove everything from this folder before deleting it.', 'buddypress-docs' ) ?>
		</p>

		<p>
			<input type="checkbox" value="1" name="delete-confirm" /> <?php _e( 'I understand the consequences and wish to continue deleting this folder.', 'buddypress-docs' ) ?>
		</p>

		<p>
			<input type="submit" name="bp-docs-delete-folder-submit" class="button-primary" value="<?php _e( 'Delete', 'buddypress-docs' ) ?>" />
			<a href="<?php echo remove_query_arg( 'delete-folder', bp_get_requested_url() ) ?>"><?php _e( 'Cancel', 'buddypress-docs' ) ?></a>
			<?php wp_nonce_field( 'bp-docs-delete-folder-' . $folder_to_delete->ID ) ?>
		</p>

		</form>
	<?php endif ?>


<?php else : ?>

<?php $folders = bp_docs_get_folders( array(
	'display' => 'flat',
	'parent_id' => null,
) ); ?>
<?php $walker = new BP_Docs_Folder_Manage_Walker(); ?>

<?php $f = $walker->walk( $folders, 10, array( 'foo' => 'bar' ) ); ?>

<h3><?php _e( 'Manage Existing Folders', 'buddypress-docs' ) ?></h3>
<ul class="docs-folder-manage">
	<?php echo $f ?>
</ul>

<hr />

<div class="create-new-folder">
	<form method="post" action="">
		<h3><?php _e( 'Create New Folder', 'buddypress-docs' ) ?></h3>
		<?php bp_docs_create_new_folder_markup( array(
			'folder_type_include_all_groups' => true,
		) ) ?>

		<?php wp_nonce_field( 'bp-docs-create-folder', 'bp-docs-create-folder-nonce' ) ?>
		<input type="submit" name="bp-docs-create-folder-submit" value="<?php _e( 'Create', 'buddypress-docs' ) ?>" />
	</form>
</div>

<?php endif ?>
