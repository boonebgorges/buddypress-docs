<?php
/*
Plugin Name: BuddyPress Docs
Network: true
*/

/* Only load the component if BuddyPress is loaded and initialized. */
function bp_docs_init() {
	require( dirname( __FILE__ ) . '/bp-docs.php' );
	$bp_docs = new BP_Docs;
}
add_action( 'bp_include', 'bp_docs_init' );
?>