/**
 * wikiGroupFrontendPageCreate()
 *
 * Creates a new wiki page with the default group page settings
 */
function wikiGroupFrontendPageCreate() {
	
	var wiki_page_title = jQuery('#bp-wiki-frontend-new-page').val();
	
	jQuery('#wiki-frontend-page-create-button').removeAttr('onclick');
	
	if ( wiki_page_title ) {
	
		jQuery.post(ajaxurl, {
			action:'bp_wiki_group_frontend_page_create',
			'cookie':encodeURIComponent(document.cookie),
			'wiki_page_title':wiki_page_title
		}, function(response) {  
			jQuery(response).appendTo('#bp-wiki-group-page-index');
		});
		
	}
	
	jQuery('#bp-wiki-frontend-new-page').removeAttr('value');
	
	jQuery('#wiki-frontend-page-create-button').bind('click', function(){wikiGroupFrontendPageCreate();return false;});

}


/**
 * wikiGroupPageEditTitleStart()
 *
 * Loads a textarea for editing of the page summary text
 */
function wikiGroupPageEditTitleStart(){

	var wiki_page_id = jQuery('#wiki_page_id').html();
	
	jQuery('#wiki-group-page-edit-title-button').empty();
	
	jQuery.post(ajaxurl, {
		action:'bp_wiki_group_page_title_show_editor',
		'cookie':encodeURIComponent(document.cookie),
		'wiki_page_id':wiki_page_id
	}, function(response) {  
		var input_text_box = '<input type="text" id="wiki-page-title-textbox" value="'+response+'"></input>';
		jQuery('#wiki-page-title-text').html(input_text_box);
		jQuery().focus('#wiki-page-summary-textbox');
		jQuery.post(ajaxurl, {
			action:'bp_wiki_group_page_title_button_editing',
			'cookie':encodeURIComponent(document.cookie),
			'wiki_page_id':wiki_page_id
		}, function(response) { 
			jQuery('#wiki-group-page-edit-title-button').html(response);
		});
	});
}


/**
 * wikiGroupPageEditTitleSave()
 *
 * Saves a page edit
 */
function wikiGroupPageEditTitleSave(){

	var wiki_page_id = jQuery('#wiki_page_id').html();
	
	var wiki_page_title = jQuery('#wiki-page-title-textbox').val();
	
	jQuery('#wiki-group-page-edit-title-button').empty();
	
	jQuery.post(ajaxurl, {
		action:'bp_wiki_group_page_title_save_editor',
		'cookie':encodeURIComponent(document.cookie),
		'wiki_page_id':wiki_page_id,
		'wiki_page_title':wiki_page_title
	}, function(response) {  
		jQuery('#wiki-page-title-text').html(response);
		jQuery.post(ajaxurl, {
			action:'bp_wiki_group_page_title_button_viewing',
			'cookie':encodeURIComponent(document.cookie),
			'wiki_page_id':wiki_page_id
		}, function(response) { 
			jQuery('#wiki-group-page-edit-title-button').html(response);
		});
	});
}

 
 

/**
 * wikiGroupPageEditStart()
 *
 * Loads the tinymce files in preperation for editing of a page
 */
function wikiGroupPageEditStart(){

	var wiki_page_id = jQuery('#wiki_page_id').html();
	
	jQuery('#wiki-group-page-edit-page-button').empty();
	
	jQuery.post(ajaxurl, {
		action:'bp_wiki_group_page_article_show_editor',
		'cookie':encodeURIComponent(document.cookie),
		'wiki_page_id':wiki_page_id
	}, function(response) {  
		jQuery('#wiki-page-content-box').html(response);
		tinyMCE.execCommand('mceAddControl', false, 'wiki-page-content-box');
			jQuery.post(ajaxurl, {
				action:'bp_wiki_group_page_content_title_button_editing',
				'cookie':encodeURIComponent(document.cookie),
				'wiki_page_id':wiki_page_id
			}, function(response) { 
				jQuery('#wiki-group-page-edit-page-button').html(response);
			});
	});
}

 
 
/**
 * wikiGroupPageEditSave()
 *
 * Saves a page edit
 */
function wikiGroupPageEditSave(){

	var wiki_page_id = jQuery('#wiki_page_id').html();
	
	tinyMCE.execCommand('mceRemoveControl', false, 'wiki-page-content-box');
	var wiki_page_content = jQuery('#wiki-page-content-box').html();
	
	jQuery('#wiki-group-page-article-title-buttons').empty();
	
	jQuery.post(ajaxurl, {
		action:'bp_wiki_group_page_save_editor',
		'cookie':encodeURIComponent(document.cookie),
		'wiki_page_id':wiki_page_id,
		'wiki_page_content':wiki_page_content
	}, function(response) {  
		jQuery('#wiki-page-content-box').html(response);
			jQuery.post(ajaxurl, {
				action:'bp_wiki_group_page_content_title_buttons_viewing',
				'cookie':encodeURIComponent(document.cookie),
				'wiki_page_id':wiki_page_id
			}, function(response) { 
				jQuery('#wiki-group-page-edit-page-button').html(response);
			});
	});
}

 
 
 
/**
 * AUTO LOADED FUNCTIONS
 *
 * Loaded on jquery doc ready
 */
jQuery(document).ready( function() {

	/**
	 * tinyMCE.init
	 *
	 * Loads the tinymce files in preperation for editing of a page
	 */
	tinyMCE.init({
		mode : "none", 
		theme : "advanced",
		width : "100%", 
		height : "450px",
		plugins : "safari,inlinepopups,spellchecker,paste,wikimedia,fullscreen,wpeditimage,wpgallery,tabfocus,table,print,preview",
		theme_advanced_buttons1 : "bold,italic,underline,strikethrough ,separator,justifyleft,justifycenter,justifyright,justifyfull,separator,bullist,numlist,separator,outdent,indent,separator,fontsizeselect,formatselect,separator,forecolor,separator,pastetext,pasteword,separator,wikimedia",
		theme_advanced_buttons2 : "undo,redo,separator,link,unlink,separator,tablecontrols,separator,spellchecker,separator,code,separator,preview,print",
		theme_advanced_buttons3 : "",
		theme_advanced_blockformats : "p,h1,h2,h3,h4,h5,h6,pre",
		table_styles : "Header 1=header1;Header 2=header2;Header 3=header3",
		table_cell_styles : "Header 1=header1;Header 2=header2;Header 3=header3;Table Cell=tableCel1",
		table_row_styles : "Header 1=header1;Header 2=header2;Header 3=header3;Table Row=tableRow1",
		table_cell_limit : 100,
		table_row_limit : 30,
		table_col_limit : 10,
		spellchecker_languages : "+English=en",
		theme_advanced_toolbar_location : "top", 
		theme_advanced_toolbar_align : "left", 
		theme_advanced_statusbar_location : "bottom", 
		theme_advanced_resizing : "1", 
		theme_advanced_resize_horizontal : "", 
		extended_valid_elements : "a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]"
	});


	/**** Activity Posting ********************************************************/

	/* New posts */
	jQuery("input#bp-wiki-comment-submit").click( function() {
		var button = jQuery(this);
		var form = button.parent().parent().parent().parent();

		form.children().each( function() {
			if ( jQuery.nodeName(this, "textarea") || jQuery.nodeName(this, "input") )
				jQuery(this).attr( 'disabled', 'disabled' );
		});

		jQuery( 'form#' + form.attr('id') + ' span.ajax-loader' ).show();

		/* Remove any errors */
		jQuery('div.error').remove();
		button.attr('disabled','disabled');

		/* Default POST values */
		var object = '';
		var item_id = jQuery("#whats-new-post-in").val();
		var content = jQuery("textarea#whats-new").val();
		var bp_wiki_page_id = jQuery("#bp_wiki_page_id").val();
		var bp_wiki_comment_form = jQuery("#bp_wiki_comment_form").val();
		
		/* Set object for non-profile posts */
		if ( item_id > 0 ) {
			object = jQuery("#whats-new-post-object").val();
		}

		jQuery.post( ajaxurl, {
			action: 'post_update',
			'cookie': encodeURIComponent(document.cookie),
			'_wpnonce_post_update': jQuery("input#_wpnonce_post_update").val(),
			'content': content,
			'object': object,
			'item_id': item_id,
			'bp_wiki_page_id': bp_wiki_page_id,
			'bp_wiki_comment_form': bp_wiki_comment_form
		},
		function(response)
		{
			jQuery( 'form#' + form.attr('id') + ' span.ajax-loader' ).hide();

			form.children().each( function() {
				if ( jQuery.nodeName(this, "textarea") || jQuery.nodeName(this, "input") )
					jQuery(this).attr( 'disabled', '' );
			});

			/* Check for errors and append if found. */
			if ( response[0] + response[1] == '-1' ) {
				form.prepend( response.substr( 2, response.length ) );
				jQuery( 'form#' + form.attr('id') + ' div.error').hide().fadeIn( 200 );
				button.attr("disabled", '');
			} else {
				if ( 0 == jQuery("ul.activity-list").length ) {
					jQuery("div.error").slideUp(100).remove();
					jQuery("div#message").slideUp(100).remove();
					jQuery("div.activity").append( '<ul id="activity-stream" class="activity-list item-list">' );
				}

				jQuery("ul.activity-list").prepend(response);
				jQuery("ul.activity-list li:first").addClass('new-update');
				jQuery("li.new-update").hide().slideDown( 300 );
				jQuery("li.new-update").removeClass( 'new-update' );
				jQuery("textarea#whats-new").val('');

				/* Re-enable the submit button after 8 seconds. */
				setTimeout( function() { button.attr("disabled", ''); }, 8000 );
			}
		});

		return false;
	});
});