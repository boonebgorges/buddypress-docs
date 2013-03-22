window.wp = window.wp || {};

(function($){
	var doc_id;

	var BP_Docs_Upload = Backbone.View.extend({
		el: $('body'), 	
		
		events: {
			'click .add-attachment': 'render' 
		},

		initialize: function() {
			this.uploader = new wp.media.view.UploaderWindow({
				controller: this,
				uploader: {
					dropzone:  this.modal ? this.modal.$el : this.$el,
					container: this.$el
				}
			});
		},

		render: function() {
			$(this.el).append('<div class="uploader-window"></div>');
			$('div.uploader-window').css('opacity', 1);
			this.uploader.show();
			return this;
		}
	});

	wp.bp_docs_upload = new BP_Docs_Upload();

	// Uploading files
	var file_frame;

	$('.add-attachment').live('click', function( event ){

		event.preventDefault();

		// If the media frame already exists, reopen it.
		if ( file_frame ) {
			file_frame.open();
			return;
		}

		// Create the media frame.
		file_frame = wp.media.frames.file_frame = wp.media({
			title: jQuery( this ).data( 'uploader_title' ),
			button: {
				text: jQuery( this ).data( 'uploader_button_text' ),
			},
			multiple: false  // Set to true to allow multiple files to be selected
		});

		file_frame.open();
	});

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
				file_frame.close();
			}
		} );
	};

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
