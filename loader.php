<?php
/*
Plugin Name: BuddyPress Wiki Component
Plugin URI: http://wordpress.org/extend/plugins/bp-wiki/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=A9NEGJEZR23H4
Description: Enables site and group wiki functionality within a Buddypress install.
Version: 1.0.0
Revision Date: September 06, 2010
Requires at least: WP 3.0.1, BuddyPress 1.2.5.2
Tested up to: WP 3.0.2, BuddyPress 1.2.6
License: AGPL http://www.fsf.org/licensing/licenses/agpl-3.0.html
Author: David Cartwright
Author URI: http://namoo.co.uk
Site Wide Only: true
*/

 

/* Only load the component if BuddyPress is loaded and initialized. */
function bp_wiki_init() {

	require( dirname( __FILE__ ) . '/includes/bp-wiki-core.php' );

}
add_action( 'bp_init', 'bp_wiki_init' );
?>