<?php $folders = bp_docs_get_folders( 'display=flat' ); ?>
<?php $walker = new BP_Docs_Folder_Walker(); ?>

<?php $f = $walker->walk( $folders, 10, array( 'foo' => 'bar' ) ); ?>

<ul>
	<?php echo $f ?>
</ul>
