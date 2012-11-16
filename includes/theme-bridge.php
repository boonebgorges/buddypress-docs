<?php

/**
 * The functions in this file are used to load template files in the non-BP sections of BP Docs
 *
 * It's likely that these functions will be removed at some point in the future, when BuddyPress
 * has better versions of the functionality I'm after.
 *
 * Much of this file is based on bbPress 2.x.
 *
 * @since 1.2
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Possibly intercept the template being loaded
 *
 * Listens to the 'template_include' filter and waits for a BP Docs post_type
 * to appear. When one is found, we look to see whether the current theme provides
 * its own version of the template; otherwise we fall back on the template shipped
 * with BuddyPress Docs.
 *
 * @since 1.2
 *
 * @param string $template
 *
 * @return string The path to the template file that is being used
 */
function bp_docs_template_include( $template = '' ) {

	if ( bp_docs_is_single_doc() && ( $new_template = bp_docs_locate_template( 'single-bp_doc.php' ) ) ) :

	elseif ( bp_docs_is_doc_create() && ( $new_template = bp_docs_locate_template( 'single-bp_doc.php' ) ) ) :

	elseif ( is_post_type_archive( bp_docs_get_post_type_name() ) && $new_template = bp_docs_locate_template( 'archive-bp_doc.php' ) ) :

	endif;

	// Custom template file exists
	$template = !empty( $new_template ) ? $new_template : $template;

	return apply_filters( 'bp_docs_template_include', $template );
}
add_filter( 'template_include', 'bp_docs_template_include' );
