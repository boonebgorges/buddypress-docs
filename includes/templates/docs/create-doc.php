
<?php include( apply_filters( 'bp_docs_header_template', $template_path . 'docs-header.php' ) ) ?>

<form action="" method="post">

	Title:
	<input name="title" id="doc-title" value="" /> 
	
	<br />
	<?php the_editor( false ); ?>
	
	
	<input type="submit" value="<?php _e( 'Save', 'bp-docs' ) ?>" name="doc-edit-submit" />
	
	<a href="<?php bp_docs_group_doc_permalink() ?>">Cancel</a> 

</form> 