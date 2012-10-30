jQuery(document).ready(function($){
	// To do on pageload
	bpdv_refresh_access_settings();

	// Binders
	$('#associated_group_id').on('change',function(){ bpdv_refresh_access_settings(); });
},(jQuery));

function bpdv_refresh_access_settings() {
	var assoc_group = jQuery('#associated_group_id').val();
	jQuery.ajax({
		type: 'POST',
		url: ajaxurl,
		data: {
			'action': 'refresh_access_settings',
			'group_id': assoc_group		
		},
		success: function(r) {
			jQuery('#toggle-table-settings tbody').html(r);
		}
	});
}
