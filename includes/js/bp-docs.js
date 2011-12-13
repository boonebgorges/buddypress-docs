jQuery(document).ready(function($){
	/* Unhide JS content */
	$('.hide-if-no-js').show();

	/* When a toggle is clicked, show the toggle-content */
	$('.toggle-link').click(function(){
		// Traverse for some items
		var toggleable = $(this).parents('.toggleable');
		var tc = $(toggleable).find('.toggle-content');
		var ts = $(toggleable).find('.toggle-switch');
		var pom = $(this).find('.plus-or-minus');

		// Toggle the active-content class
		if($(tc).hasClass('active-content')){
			$(tc).removeClass('active-content');
		}else{
			$(tc).addClass('active-content');
		}

		// Toggle the active-switch class
		if($(ts).hasClass('active-switch')){
			$(ts).removeClass('active-switch');
		}else{
			$(ts).addClass('active-switch');
		}

		// Slide the tags up or down
		$(tc).slideToggle(400, function(){
			// Swap the +/- in the link
			var c = $(pom).html();

			if ( c == '+' ) {
				var mop = '-';
			} else {
				var mop = '+';
			}

			$(pom).html(mop);
		});

		return false;
	});

	if($('#doc-form').length != 0 && $('#existing-doc-id').length != 0 ) {
		/* Set away timeout for quasi-autosave */
		setIdleTimeout(1000 * 60 * 25); // 25 minutes until the popup (ms * s * min)
		setAwayTimeout(1000 * 60 * 30); // 30 minutes until the autosave
		document.onIdle = function() {
			tb_show(bp_docs.still_working, '#TB_inline?height=300&width=300&inlineId=still_working_content');
		}
		document.onAway = function() {
			tb_remove();
			var is_auto = '<input type="hidden" name="is_auto" value="1">';
			$('#doc-form').append(is_auto);
			$('#doc-edit-submit').click();
		}

		/* Remove the edit lock when the user clicks away */
		$("a").click(function(){
			var doc_id = $("#existing-doc-id").val();
			var data = {action:'remove_edit_lock', doc_id:doc_id};
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				async: false,
				timeout: 10000,
				dataType:'json',
				data: data,
				success: function(response){
					return true;
				},
				complete: function(){
					return true;
				}
			});
		});
	}

	$('#bp-docs-group-enable').click(function(){
		$('#group-doc-options').slideToggle(400);
	});

	 // Components that use editor
	var components = [ 'doc' ];

	// Cycle through all the components and try to find the editor IDs
	$(components).each( function(i,c) {
		var title_id = $("*[name='" + c + "\\[title\\]']").attr('id');
		var content_id = $("*[name='" + c + "\\[content\\]']").attr('id');

		// Try to update the fullscreen variable settings
		if ( typeof title_id != 'undefined' )
		    fullscreen.settings.title_id = title_id;
		if ( typeof content_id != 'undefined' )
		    fullscreen.settings.editor_id = content_id;
	})

	// Try to check for content_id, wp-fullscreen fails here
	$("#wp-fullscreen-body").one("mousemove", function(){
		var content_elem = document.getElementById( fullscreen.settings.editor_id );
		var editor_mode = $(content_elem).is(':hidden') ? 'tinymce' : 'html';
		fullscreen.switchmode(editor_mode);
	});

	// Delete the loader, it won't load anyway
	$('#wp-fullscreen-save img').remove();
},(jQuery));