jQuery(document).ready(function($){
	/* When the filter toggle is clicked, show the tags. Todo: abstract for other filters */
	$('.tag-filter-toggle').click(function(){
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
		$('ul.filter-tags-list').slideToggle(400);
		
		return false;
	});
	
},(jQuery));