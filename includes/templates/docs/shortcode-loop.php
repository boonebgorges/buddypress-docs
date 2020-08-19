<?php
// Set defaults from arguments passed to template.
$args = wp_parse_args(
    $args,
    array(
        'show_date' => false,
        'has_docs'  => false,
    )
);
if ( $args['has_docs']  ) :
?>
	<ul>
		<?php while ( bp_docs_has_docs() ) : bp_docs_the_doc(); ?>
			<li>
				<a href="<?php the_permalink(); ?>"><?php get_the_title() ? the_title() : the_ID(); ?></a>
			<?php if ( $args['show_date'] ) : ?>
				<span class="post-date"><?php echo get_the_date(); ?></span>
			<?php endif; ?>
			</li>
		<?php endwhile; ?>
	</ul>
<?php
else :
?>
	<p class="no-results"><?php _e( 'Sorry, no docs were found.', 'buddypress-docs' ); ?></p>
<?php
endif;