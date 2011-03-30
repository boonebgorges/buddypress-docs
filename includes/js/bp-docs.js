jQuery(document).ready(function($){
	/* Swap the 'filter by tag' text with a dummy link and hide tag list on load */
	if ( $('p#filter-by-tag').length != false ) {
		var c = $('p#filter-by-tag').html();
		var cl = '<a href="#" class="doc-filter-toggle tag-filter-toggle">' + c + ' +</a>';
		$('p#filter-by-tag').html(cl);
		
		/* Hide tags */
		$('ul.filter-tags-list').toggle();
	}
	
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
	});
	
},(jQuery));