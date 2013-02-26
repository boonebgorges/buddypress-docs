<?php /* Subnavigation on user pages is handled by BP's core functions */ ?>
<?php if ( !bp_is_user() ) : ?>
	<div class="item-list-tabs no-ajax" id="subnav" role="navigation">
		<?php bp_docs_tabs() ?>
	</div><!-- .item-list-tabs -->
<?php endif ?>

<?php if ( bp_docs_is_existing_doc() ) : ?>

	<div id="bp-docs-single-doc-header">
		<?php if ( ! bp_docs_is_theme_compat_active() ) : ?>
			<h2 class="doc-title"><?php the_title() ?></h2>
		<?php endif ?>

		<?php do_action( 'bp_docs_single_doc_header_fields' ) ?>
	</div>

	<div class="doc-tabs">
		<ul>
			<li<?php if ( bp_docs_is_doc_read() ) : ?> class="current"<?php endif ?>>
				<a href="<?php bp_docs_doc_link() ?>"><?php _e( 'Read', 'bp-docs' ) ?></a>
			</li>

			<?php if ( bp_docs_current_user_can( 'edit' ) ) : ?>
				<li<?php if ( bp_docs_is_doc_edit() ) : ?> class="current"<?php endif ?>>
					<a href="<?php bp_docs_doc_edit_link() ?>"><?php _e( 'Edit', 'bp-docs' ) ?></a>
				</li>
			<?php endif ?>

			<?php do_action( 'bp_docs_header_tabs' ) ?>
		</ul>
	</div>

<?php elseif ( bp_docs_is_doc_create() ) : ?>

	<h2><?php _e( 'New Doc', 'bp-docs' ); ?></h2>

<?php endif ?>
