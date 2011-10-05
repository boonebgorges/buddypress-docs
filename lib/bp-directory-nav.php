<?php

/**
 * Directory nav API for BuddyPress
 *
 * @package BuddyPress_Docs
 * @since 1.2
 */

function bp_directory_nav_shuffle( $r, $args, $defaults ) {
	global $bp;

	extract( $r );

	if ( isset( $show_on ) && is_array( $show_on ) ) {

		// First, setup the Directory nav
		if ( in_array( 'directory', $show_on ) ) {

			// Cast the directory_nav as array if necessary
			if ( !isset( $bp->bp_directory_nav ) ) {
				$bp->bp_directory_nav = array();
			}

			// Move the nav item over to bp_directory_nav
			$bp->bp_directory_nav[$slug] = $bp->bp_nav[$slug];

			// We'll need to reset the link for the directory item
			$bp->bp_directory_nav[$slug]['link'] = isset( $link ) ? $link : trailingslashit( bp_get_root_domain() . '/' . $slug );
		}

		// Next, unset the Profile nav if necessary
		if ( !in_array( 'profile', $show_on ) ) {
			unset( $bp->bp_nav[$slug] );
		}
	}
}
add_action( 'bp_core_new_nav_item', 'bp_directory_nav_shuffle', 10, 3 );

function bp_get_directory_nav() {
	global $bp;

	// Loop through each navigation item
	foreach( (array) $bp->bp_directory_nav as $nav_item ) {
		// If the current component matches the nav item id, then add a highlight CSS class.
		if ( $bp->active_components[$bp->current_component] == $nav_item['css_id'] )
			$selected = ' class="current selected"';
		else
			$selected = '';

		// echo out the final list item
		echo apply_filters_ref_array( 'bp_get_loggedin_user_nav_' . $nav_item['css_id'], array( '<li id="li-nav-' . $nav_item['css_id'] . '" ' . $selected . '><a id="dir-' . $nav_item['css_id'] . '" href="' . $nav_item['link'] . '">' . $nav_item['name'] . '</a></li>', &$nav_item ) );
	}
}

?>