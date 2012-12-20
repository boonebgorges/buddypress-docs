jQuery(document).ready(function($){
	// To do on pageload
	bpdv_refresh_access_settings();

	// Binders
	$('#associated_group_id').on('change',function(){ bpdv_refresh_access_settings(); });
	$('#associated_group_id').on('change',function(){ bpdv_refresh_associated_group(); });
},(jQuery));

function bpdv_refresh_access_settings() {
	var assoc_group = jQuery('#associated_group_id').val();
	var doc_id = jQuery('#doc_id').val();
	jQuery.ajax({
		type: 'POST',
		url: ajaxurl,
		data: {
			'action': 'refresh_access_settings',
			'group_id': assoc_group,
			'doc_id': doc_id
		},
		success: function(r) {
			jQuery('#toggle-table-settings tbody').html(r);
		}
	});
}

function bpdv_refresh_associated_group() {
	var assoc_group = jQuery('#associated_group_id').val();
	var doc_id = jQuery('#doc_id').val();
	jQuery.ajax({
		type: 'POST',
		url: ajaxurl,
		data: {
			'action': 'refresh_associated_group',
			'group_id': assoc_group,
			'doc_id': doc_id
		},
		success: function(r) {
			var ags = jQuery('#associated_group_summary');
			jQuery(ags).slideUp('fast', function(){
				jQuery(ags).html(r);
				jQuery(ags).slideDown('fast');
			});
		}
	});
}
