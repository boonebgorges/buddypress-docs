<?php if ( have_posts() ) : while ( have_posts() ) : the_post() ?>

<?php include( apply_filters( 'bp_docs_header_template', $template_path . 'docs-header.php' ) ) ?>

<form action="" method="post">

	Title:
	<input name="title" id="doc-title" value="<?php the_title() ?>" /> 
	
	<br />
	
	<textarea name="content" id="theEditor"><?php echo get_the_content() ?></textarea>
	
	<input type="submit" value="<?php _e( 'Save Changes', 'bp-docs' ) ?>" name="doc-edit-submit" />
	
	<a href="<?php bp_docs_group_doc_permalink() ?>">Cancel</a> 

</form>

<?php endwhile; endif ?>