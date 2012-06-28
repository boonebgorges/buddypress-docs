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

	$('#bp-docs-group-enable').click(function(){
		$('#group-doc-options').slideToggle(400);
	});
},(jQuery));

function bp_docs_load_idle() {
	if(jQuery('#doc-form').length != 0 && jQuery('#existing-doc-id').length != 0 ) {
		// For testing
		//setIdleTimeout(1000 * 3); // 25 minutes until the popup (ms * s * min)
		//setAwayTimeout(1000 * 10); // 30 minutes until the autosave
		
		/* Set away timeout for quasi-autosave */
		setIdleTimeout(1000 * 60 * 25); // 25 minutes until the popup (ms * s * min)
		setAwayTimeout(1000 * 60 * 30); // 30 minutes until the autosave
		document.onIdle = function() {
			jQuery.colorbox({
				inline: true,
				href: "#still_working_content",
				width: "50%",
				height: "50%"
			});
		}
		document.onAway = function() {	
			jQuery.colorbox.close();
			var is_auto = '<input type="hidden" name="is_auto" value="1">';
			jQuery('#doc-form').append(is_auto);
			jQuery('#doc-edit-submit').click();
		}

		/* Remove the edit lock when the user clicks away */
		jQuery("a").click(function(){
			var doc_id = $("#existing-doc-id").val();
			var data = {action:'remove_edit_lock', doc_id:doc_id};
			jQuery.ajax({
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
}

function bp_docs_kitchen_sink(ed) {
	var adv_button = jQuery('#' + ed.editorContainer).find('a.mce_wp_adv');
	if ( 0 != adv_button.length ) {
		jQuery(adv_button).on('click',function(e){
			var sec_rows = jQuery(adv_button).closest('table.mceToolbar').siblings('table.mceToolbar');
			jQuery(sec_rows).each(function(k,row){
				if ( !jQuery(row).hasClass('mceToolbarRow2') ) {
					jQuery(row).toggle();
				}
			});
		});
	}
}