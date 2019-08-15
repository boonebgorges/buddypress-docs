<?php
/*
Plugin Name: BuddyPress Docs
Plugin URI: http://github.com/boonebgorges/buddypress-docs
Description: Adds collaborative Docs to BuddyPress
Version: 2.1.3
Author: Boone B Gorges, David Cavins
Author URI: http://boone.gorg.es
Text Domain: buddypress-docs
Domain Path: /languages/
Licence: GPLv3
*/

/*
It's on like Donkey Kong
*/

define( 'BP_DOCS_VERSION', '2.1.3' );

/*
 * BuddyPress Docs introduces a lot of overhead. Unless otherwise specified,
 * don't load the plugin on subsites of an MS install
 */
if ( ! defined( 'BP_DOCS_LOAD_ON_NON_ROOT_BLOG' ) ) {
	define( 'BP_DOCS_LOAD_ON_NON_ROOT_BLOG', false );
}

/**
 * Loads BP Docs files only if BuddyPress is present
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 */
function bp_docs_init() {
	global $bp_docs;

	if ( is_multisite() && ! bp_is_root_blog() && ( ! BP_DOCS_LOAD_ON_NON_ROOT_BLOG ) ) {
		return;
	}

	require dirname( __FILE__ ) . '/bp-docs.php';
	$bp_docs = new BP_Docs();
}
add_action( 'bp_include', 'bp_docs_init' );
