<?php

/** * bp_wiki_add_js() * * This function will enqueue the components javascript file, so that you can make * use of any javascript you bundle with your component within your interface screens. */
function bp_wiki_add_js() {
	global $bp;	// Group wiki js
	if ( $bp->current_action == 'wiki' ) {
		wp_enqueue_script( 'utils' ); 
        wp_enqueue_script( 'tinymce_wiki', BP_WIKI_PLUGIN_URL . '/includes/js/tiny_mce/tiny_mce.js' );
        wp_enqueue_script( 'group_wiki', BP_WIKI_PLUGIN_URL . '/includes/js/group-wiki.js' );
	}
	// Group wiki admin js	
	if ( $bp->current_action == 'admin' && $bp->action_variables[0] == 'wiki' ) {
        wp_enqueue_script( 'group_wiki', BP_WIKI_PLUGIN_URL . '/includes/js/group-wiki-admin.js' );
	}
}
add_action( 'init', 'bp_wiki_add_js', 1 );
function bp_wiki_add_group_css() {
	global $bp;
	if ( $bp->current_component == 'groups' ) {
		wp_register_style( 'wiki-stylesheet', apply_filters( 'bp_wiki_locate_group_css', 'css/group-style.css' ) );		
		wp_enqueue_style( 'wiki-stylesheet' );
	}
}
add_action( 'wp_print_styles', 'bp_wiki_add_group_css', 1 );
?>