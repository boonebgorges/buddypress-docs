<?php if ( have_posts() ) : while ( have_posts() ) : the_post() ?>

<?php include( apply_filters( 'bp_docs_header_template', $template_path . 'docs-header.php' ) ) ?>


Single doc content markup will go here


<?php endwhile; endif ?>