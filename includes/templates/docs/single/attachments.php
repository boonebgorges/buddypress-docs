<?php bp_docs_media_buttons( 'doc_content' ) ?>

<ul id="doc-attachments-ul">
<?php foreach ( bp_docs_get_doc_attachments() as $attachment ) : ?>
	<?php echo bp_docs_attachment_item_markup( $attachment->ID ) ?>
<?php endforeach; ?>
</ul>
