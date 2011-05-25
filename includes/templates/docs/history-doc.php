<?php if ( have_posts() ) : while ( have_posts() ) : the_post() ?>

<?php include( apply_filters( 'bp_docs_header_template', bp_docs_locate_template( 'docs-header.php' ) ) ) ?>

<div class="doc-content">
	<?php $args = array(
		'format' => 'form-table'
	) ?>
	<?php bp_docs_list_post_revisions( get_the_ID(), $args ) ?>
	
	<?php the_content() ?>
</div>

<div class="doc-meta">
	<?php do_action( 'bp_docs_single_doc_meta' ) ?>
</div>

<?php comments_template( '/docs/comments.php' ) ?>

<?php endwhile; endif ?>
