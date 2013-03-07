window.wp = window.wp || {};

(function($){
	var doc_id;

	doc_id = wp.media.model.settings.post.id;

	if ( 0 == doc_id ) {
		options = {
			success: function( response ) {
				wp.media.model.settings.post.id = response.doc_id;
				$('input#doc_id').after('<input type="hidden" name="auto_draft_id" value="'+response.doc_id+'" />');
			}
		};
		wp.media.ajax( 'bp_docs_create_dummy_doc', options );
	}
})(jQuery);
