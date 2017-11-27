<?php $folders = bp_docs_get_folders( 'display=flat' ); ?>
<?php $walker = new BP_Docs_Folder_Walker(); ?>

<?php $f = $walker->walk( $folders, 10, array( 'foo' => 'bar' ) ); ?>

<?php if ( bp_docs_current_user_can( 'manage_folders' ) ) : ?>
	<a id="manage-folders-link" href="<?php echo add_query_arg( 'view', 'manage', remove_query_arg( 'view', bp_get_requested_url() ) ) ?>"><?php _e( 'Manage Folders', 'buddypress-docs' ) ?></a>
<?php endif ?>

<div style="clear:both"></div>

<ul class="docs-folder-tree">
	<?php echo $f ?>
</ul>
