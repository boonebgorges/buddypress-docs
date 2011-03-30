jQuery(document).ready(function($){
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
	}

	/* On some setups, it helps TinyMCE to load if we fire the switchEditors event on load */
	switchEditors.go('doc[content]', 'tinymce');
	
	/* Disabled until I build a proper set of options */
	/*
	$('#bp-docs-group-enable').click(function(){
		$('#group-doc-options').slideToggle(400);
	});
	*/
	
},(jQuery));