<?php /* Subnavigation on user pages is handled by BP's core functions */ ?>
<?php if ( !bp_is_user() ) : ?>
	<div class="item-list-tabs no-ajax" id="subnav" role="navigation">
		<ul>
			<?php bp_docs_tabs() ?>
		</ul>
	</div><!-- .item-list-tabs -->
<?php endif ?>

<?php if ( bp_docs_is_existing_doc() ) : ?>

	<h2><?php the_title() ?></h2>

	<div class="doc-tabs">
		<ul>
			<li<?php if ( bp_docs_is_doc_read() ) : ?> class="current"<?php endif ?>>
				<a href="<?php the_permalink() ?>"><?php _e( 'Read', 'bp-docs' ) ?></a>
			</li>

			<?php if ( bp_docs_current_user_can( 'edit' ) ) : ?>
				<li<?php if ( bp_docs_is_doc_edit() ) : ?> class="current"<?php endif ?>>
					<a href="<?php echo get_permalink() . BP_DOCS_EDIT_SLUG ?>"><?php _e( 'Edit', 'bp-docs' ) ?></a>
				</li>
			<?php endif ?>

			<?php do_action( 'bp_docs_header_tabs' ) ?>
		</ul>
	</div>

<?php elseif ( 'create' == bp_docs_current_view() ) : ?>

	<h2><?php _e( 'New Doc', 'bp-docs' ); ?></h2>

<?php endif ?>
