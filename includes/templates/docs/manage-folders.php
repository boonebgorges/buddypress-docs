<?php $folders = bp_docs_get_folders( array(
	'display' => 'flat',
	'parent_id' => null,
) ); ?>
<?php $walker = new BP_Docs_Folder_Manage_Walker(); ?>

<?php $f = $walker->walk( $folders, 10, array( 'foo' => 'bar' ) ); ?>

<h3><?php _e( 'Manage Existing Folders', 'bp-docs' ) ?></h3>
<ul class="docs-folder-manage">
	<?php echo $f ?>
</ul>

<hr />

<div class="create-new-folder">
	<form method="post" action="">
		<h3><?php _e( 'Create New Folder', 'bp-docs' ) ?></h3>
		<?php bp_docs_create_new_folder_markup() ?>

		<?php wp_nonce_field( 'bp-docs-create-folder', 'bp-docs-create-folder-nonce' ) ?>
		<input type="submit" name="bp-docs-create-folder-submit" value="<?php _e( 'Create', 'bp-docs' ) ?>" />
	</form>
</div>
