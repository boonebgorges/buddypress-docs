<?php
/*
Plugin Name: BuddyPress Docs
Plugin URI: http://github.com/boonebgorges/buddypress-docs
Description: Adds collaborative Docs to BuddyPress
Version: 1.0-beta
Author: Boone B Gorges
Author URI: http://boonebgorges.com
Licence: GPLv3
Network: true
*/

/*
This is beta software.
Don't use it yet.

Seriously!
*/

/**
 * Loads BP Docs files only if BuddyPress is present
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 */
function bp_docs_init() {
	global $bp_docs;
	
	require( dirname( __FILE__ ) . '/bp-docs.php' );
	$bp_docs = new BP_Docs;
}
add_action( 'bp_include', 'bp_docs_init' );
?>
