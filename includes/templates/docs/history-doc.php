<?php if ( have_posts() ) : while ( have_posts() ) : the_post() ?>

<?php include( apply_filters( 'bp_docs_header_template', bp_docs_locate_template( 'docs-header.php' ) ) ) ?>

<div class="doc-content">

<table class="form-table ie-fixed">
	<col class="th" />

	<?php if ( 'diff' == bp_docs_history_action() ) : ?>
	<tr id="revision">
		<th scope="row"></th>
		<th scope="col" class="th-full">
			<span class="alignleft"><?php printf( __( 'Older: %s' ), bp_docs_history_post_revision_field( 'left', 'post_title' ) ); ?></span>
			<span class="alignright"><?php printf( __( 'Newer: %s' ), bp_docs_history_post_revision_field( 'right', 'post_title' ) ); ?></span>
		</th>
	</tr>
	<?php endif ?>

	<?php foreach ( _wp_post_revision_fields() as $field => $field_title ) : ?>
		<?php if ( 'diff' == bp_docs_history_action() ) : ?>
			<tr id="revision-field-<?php echo $field; ?>">
				<th scope="row"><?php echo esc_html( $field_title ); ?></th>
				<td><div class="pre"><?php echo wp_text_diff( bp_docs_history_post_revision_field( 'left', $field ), bp_docs_history_post_revision_field( 'right', $field ) ) ?></div></td>
			</tr>
		<?php else : ?>
			<tr id="revision-field-<?php echo $field; ?>">
				<th scope="row"><?php echo esc_html( $field_title ); ?></th>
				<td><div class="pre"><?php echo bp_docs_history_post_revision_field( false, $field ) ?></div></td>
			</tr>
		
		<?php endif ?>

	<?php endforeach ?>

	<?php if ( 'diff' == bp_docs_history_action() && bp_docs_history_revisions_are_identical() ) : ?>
		<tr><td colspan="2"><div class="updated"><p><?php _e( 'These revisions are identical.' ); ?></p></div></td></tr>
	<?php endif ?>

</table>

<br class="clear" />

<h3><?php _e( 'Revision History', 'bp-docs' ) ?></h3>
	
	<?php bp_docs_list_post_revisions( get_the_ID() ) ?>
	
</div>

<div class="doc-meta">
	<?php do_action( 'bp_docs_single_doc_meta' ) ?>
</div>

<?php comments_template( '/docs/comments.php' ) ?>

<?php endwhile; endif ?>
