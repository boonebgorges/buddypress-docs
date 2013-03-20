<?php bp_docs_media_buttons( 'doc_content' ) ?>

<li id="doc-attachments">
<?php foreach ( bp_docs_get_doc_attachments() as $attachment ) : ?>
	<ul>
		<a href="<?php echo esc_url( $attachment->guid ); ?>"><?php echo esc_html( $attachment->post_title ); ?></a>
	</ul>
<?php endforeach; ?>
</li>
