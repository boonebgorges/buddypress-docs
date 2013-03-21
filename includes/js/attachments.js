window.wp = window.wp || {};

(function($){
	var doc_id;

	// Upload handler. Sends attached files to the list
	wp.Uploader.prototype.success = function(r) {
		$.ajax( ajaxurl, { 
			type: 'POST',
			data: {
				'action': 'doc_attachment_item_markup',
				'attachment_id': r.id,
				
			},
			success: function(s) {
				$('#doc-attachments').append(s.data);
			}
		} );
	};

	console.log(wp);

	doc_id = wp.media.model.settings.post.id;

	if ( 0 == doc_id ) {
		options = {
			success: function( response ) {
				wp.media.model.settings.post.id = response.doc_id;
				$('input#doc_id').val(response.doc_id);
				wp.media.model.Query.defaultArgs.auto_draft_id = response.doc_id;
			}
		};
		wp.media.ajax( 'bp_docs_create_dummy_doc', options );
	}
})(jQuery);
