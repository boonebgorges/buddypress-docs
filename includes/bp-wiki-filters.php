<?php
 add_filter( 'bp_wiki_get_item_name', 'wp_filter_kses', 1 );

/**
 * In your save() method in 'bp-wiki-classes.php' you will have 'before save' filters on
 * values. You should use these filters to attach the wp_filter_kses() function to them.
 */

 add_filter( 'wiki_data_fieldname1_before_save', 'wp_filter_kses', 1 );
 add_filter( 'wiki_data_fieldname2_before_save', 'wp_filter_kses', 1 );

/**
 * Filters for the template files.  These hook into bp_wiki_load_template_file to retrieve them files from
 * the current theme dir if the files are present, or fall back to the plugin dir theme files if not.
 */

// Directory access required so using template_file function
add_filter( 'bp_wiki_locate_edit_group_page', 'bp_wiki_load_template_file' );
add_filter( 'bp_wiki_locate_group_wiki_admin', 'bp_wiki_load_template_file' );
add_filter( 'bp_wiki_locate_group_wiki_create', 'bp_wiki_load_template_file' );
add_filter( 'bp_wiki_locate_group_wiki_comment_form', 'bp_wiki_load_template_file' );
add_filter( 'bp_wiki_locate_group_wiki_comments', 'bp_wiki_load_template_file' );
add_filter( 'bp_wiki_locate_group_wiki_comments_entry', 'bp_wiki_load_template_file' );
add_filter( 'bp_wiki_locate_view_group_index', 'bp_wiki_load_template_file' );
add_filter( 'bp_wiki_locate_view_group_page', 'bp_wiki_load_template_file' );
add_filter( 'bp_wiki_locate_view_group_revision', 'bp_wiki_load_template_file' );
add_filter( 'bp_wiki_locate_view_group_discussion', 'bp_wiki_load_template_file' );
add_filter( 'bp_wiki_locate_view_site_directory', 'bp_wiki_load_template_file' );
add_filter( 'bp_wiki_locate_view_site_page', 'bp_wiki_load_template_file' );

// URL access required so using template_url function
add_filter( 'bp_wiki_locate_group_css', 'bp_wiki_load_template_url' );
add_filter( 'bp_wiki_locate_group_wiki_title_image', 'bp_wiki_load_template_url' );
add_filter( 'bp_wiki_locate_group_wiki_page_image', 'bp_wiki_load_template_url' );
add_filter( 'bp_wiki_locate_group_wiki_revisions_image', 'bp_wiki_load_template_url' );
add_filter( 'bp_wiki_locate_group_wiki_comments_image', 'bp_wiki_load_template_url' );
?>