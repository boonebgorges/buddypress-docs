
<?php include( apply_filters( 'bp_docs_header_template', $template_path . 'docs-header.php' ) ) ?>

<?php if ( have_posts() ) : ?>

	<ul>
	<?php while ( have_posts() ) : the_post() ?>
		<li>
			<h2><a href="<?php bp_docs_group_doc_permalink() ?>"><?php the_title() ?></a></h2>
			<?php the_excerpt() ?>
		</li>
	<?php endwhile ?>
	</ul>

<?php endif ?>
