<?php if ( have_posts() ) : while ( have_posts() ) : the_post() ?>

<?php include( apply_filters( 'bp_docs_header_template', bp_docs_locate_template( 'docs-header.php' ) ) ) ?>

<?php if ( bp_docs_is_doc_edit_locked() && bp_docs_current_user_can( 'edit' ) ) : ?>
	<div class="toggleable doc-is-locked">
		<span class="toggle-switch" id="toggle-doc-is-locked"><?php _e( 'Locked', 'bp-docs' ) ?> <span class="hide-if-no-js description"><?php _e( '(click for more info)', 'bp-docs' ) ?></span></span>
		<div class="toggle-content">
			<p><?php printf( __( 'This doc is currently being edited by %1$s. In order to prevent edit conflicts, only one user can edit a doc at a time.', 'bp-docs' ), bp_docs_get_current_doc_locker_name() ) ?></p>

			<?php if ( is_super_admin() || bp_group_is_admin() ) : ?>
				<p><?php printf( __( 'Please try again in a few minutes. Or, as an admin, you can <a href="%s">force cancel</a> the edit lock.', 'bp-docs' ), bp_docs_get_force_cancel_edit_lock_link() ) ?></p>
			<?php else : ?>
				<p><?php _e( 'Please try again in a few minutes.', 'bp-docs' ) ?></p>
			<?php endif ?>
		</div>
	</div>

	<?php bp_docs_inline_toggle_js() ?>
<?php endif ?>

<div class="doc-content">
	<?php the_content() ?>
</div>

<div class="doc-meta">
	<?php do_action( 'bp_docs_single_doc_meta' ) ?>
</div>

<?php comments_template( '/docs/comments.php' ) ?>

<?php endwhile; ?>

<?php else : ?>

	<p><?php _e( 'No Doc by this name exists.', 'bp-docs' ) ?></p>

	<p><?php printf( __( '<a href="%1$s">View all Docs in this group</a> or <a href="%2$s">create a new Doc</a>.', 'bp-docs' ), bp_docs_get_item_docs_link(), bp_docs_get_item_docs_link() . 'create' ) ?></p>

<?php endif; ?>

