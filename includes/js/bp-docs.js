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
	
	/* Disabled until I build a proper set of options */
	/*
	$('#bp-docs-group-enable').click(function(){
		$('#group-doc-options').slideToggle(400);
	});
	*/
	
},(jQuery));