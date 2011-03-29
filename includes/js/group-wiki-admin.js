/* JS for the group wiki admin screen */

function wikiGroupAdminPageDelete( wiki_page_id ){

	jQuery.post(ajaxurl, {
		action:'bp_wiki_group_admin_page_delete',
		'cookie':encodeURIComponent(document.cookie),
		'wiki_page_id':wiki_page_id
	}, function(response) {  
		jQuery('#wiki-page-item-'+wiki_page_id).remove();
	});
}

/**
 * wikiGroupAdminFakeTickboxPageEnable()
 *
 * toggles a hidden value for unchecked tickboxes so that they have a value when submitting
 */
function wikiGroupAdminFakeTickboxPageEnable(element) {
	
	jQuery(element).parent().html('<input type="checkbox" value="yes" id="wiki-page-visible[]" name="wiki-page-visible[]" onclick="wikiGroupAdminFakeTickboxPageDisable(this);" checked="1"/>');
}

/**
 * wikiGroupAdminFakeTickboxPageDisable()
 *
 * toggles a hidden value for unchecked tickboxes so that they have a value when submitting
 */
function wikiGroupAdminFakeTickboxPageDisable(element) {
	
	jQuery(element).parent().html('<input type="hidden" value="no" id="wiki-page-visible[]" name="wiki-page-visible[]" /><input type="checkbox" value="" id="dummy" name="dummy" onclick="wikiGroupAdminFakeTickboxPageEnable(this);"/>');
}


/**
 * wikiGroupAdminFakeTickboxCommentsEnable()
 *
 * toggles a hidden value for unchecked tickboxes so that they have a value when submitting
 */
function wikiGroupAdminFakeTickboxCommentsEnable(element) {
	
	jQuery(element).parent().html('<input type="checkbox" value="yes" id="wiki-page-comments-on[]" name="wiki-page-comments-on[]" onclick="wikiGroupAdminFakeTickboxCommentsDisable(this);" checked="1"/>');
}

/**
 * wikiGroupAdminFakeTickboxCommentsDisable()
 *
 * toggles a hidden value for unchecked tickboxes so that they have a value when submitting
 */
function wikiGroupAdminFakeTickboxCommentsDisable(element) {
	
	jQuery(element).parent().html('<input type="hidden" value="no" id="wiki-page-comments-on[]" name="wiki-page-comments-on[]" /><input type="checkbox" value="" id="dummy" name="dummy" onclick="wikiGroupAdminFakeTickboxCommentsEnable(this);"/>');
}


/**
 * wikiGroupAdminPageCreate()
 *
 * Creates a new wiki page with the default group page settings
 */
function wikiGroupAdminPageCreate() {
	
	var wiki_page_title = jQuery('#wiki-page-title-create').val();
	
	jQuery('#bp-wiki-group-admin-page-create-button').removeAttr('onclick');
	
	if ( wiki_page_title ) {
	
		jQuery.post(ajaxurl, {
			action:'bp_wiki_group_admin_page_create',
			'cookie':encodeURIComponent(document.cookie),
			'wiki_page_title':wiki_page_title
		}, function(response) {  
			jQuery(response).appendTo('#bp-wiki-group-admin-pages-list');
		});
		
	}
	
	jQuery('#wiki-page-title-create').removeAttr('value');
	
	jQuery('#bp-wiki-group-admin-page-create-button').bind('click', function(){wikiGroupAdminPageCreate();return false;});

}