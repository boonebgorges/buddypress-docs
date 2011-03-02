jQuery(document).ready(function($){
	/* When a toggle is clicked, show the toggle-content */
	$('.toggle-link').click(function(){
		// Swap the +/- in the link
		var c = $(this).html();
		var pom = c.substr( c.length - 1, 1 );
		
		if ( pom == '+' ) {
			var mop = '-';
		} else {
			var mop = '+';
		}
		
		$(this).html( c.substr( 0, c.length - 1 ) + mop );
		
		// Finally, slide the tags up or down
		$(this).parent().parent().children('.toggle-content').slideToggle(400);
		
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

	
	/* Disabled until I build a proper set of options */
	/*
	$('#bp-docs-group-enable').click(function(){
		$('#group-doc-options').slideToggle(400);
	});
	*/
	
},(jQuery));