<?php
// Set defaults from arguments passed to template.
// $args won't exist in WP <5.5
if ( ! isset( $args ) ) {
	$args = array();
}
$args = wp_parse_args(
    $args,
    array(
        'show_date' => false,
        'has_docs'  => false,
        'class'     => '',
    )
);
$classes    = preg_split( '/[,\s]+/', $args['class'] );
$classes[]  = 'bp-docs-recent-docs';
$classes    = array_map( 'sanitize_html_class', array_unique( $classes ) );
$class_list = implode( ' ', $classes );
?>
<div class="<?php echo $class_list; ?>">
	<?php
	if ( $args['has_docs']  ) :
	?>
		<ul class="bp-docs-recent-docs-list">
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
		<p class="no-results"><?php esc_html_e( 'Sorry, no docs were found.', 'buddypress-docs' ); ?></p>
	<?php
	endif;
	?>
</div>