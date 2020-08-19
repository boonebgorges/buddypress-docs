<?php

/**
 * Shortcode for outputting recent docs, similar to the Recent Docs Widget,
 * but as a shortcode.
 *
 * @package BuddyPressDocs
 * @since 2.3
 */

function bp_docs_recent_docs_shortcode_handler( $atts ) {
    $a = shortcode_atts( array(
        'number'        => 5,
        'show_date'     => false,
        'author_id'     => null,
        'group_id'      => null,
        'folder_id'     => null,
        'context_aware' => false,
    ), $atts );

	$doc_args = array(
		'posts_per_page' => $a['number'],
		'post_status'    => array( 'publish' ),
		'author_id'      => $a['author_id'],
		'group_id'       => $a['group_id'],
		'folder_id'      => $a['folder_id'],
	);

	/**
	 * Limit to docs associated with the current context
	 * if the shortcode has been set to be context aware
	 * and we're in a group or viewing a user's profile
	 * and a group or user hasn't been specified.
	 */
	if ( isset( $a['context_aware'] ) && $a['context_aware'] ) {
		if ( bp_is_user() && ! $doc_args['author_id'] ) {
			$doc_args['author_id'] = bp_displayed_user_id();
		}
		if ( bp_is_group() && ! $doc_args['group_id'] ) {
			$doc_args['group_id'] = bp_get_current_group_id();
		}
	}

	/**
	 * Filters the args passed to `bp_docs_has_docs()` in the Recent Docs shortcode.
	 *
	 * @since 2.3.0
	 *
	 * @param array {
	 *     @type int    $posts_per_page
	 *     @type string $post_status
	 *     @type int    $author_id
	 *     @type int    $group_id
	 *     @type int    $folder_id
	 * }
	 */
	$doc_args = apply_filters( 'bp_docs_shortcode_query_args', $doc_args );

	$bp = buddypress();

	// Store the existing doc_query, so ours is made from scratch.
	$temp_doc_query = isset( $bp->bp_docs->doc_query ) ? $bp->bp_docs->doc_query : null;
	$bp->bp_docs->doc_query = null;

	// Prepare the arguments array to pass to the template.
	$loop_args = array(
		'show_date' => $a['show_date'],
		'has_docs'  => false,
	);

	ob_start();

	if ( bp_docs_has_docs( $doc_args ) ) :
		$loop_args['has_docs'] = true;
		bp_docs_locate_template( 'shortcode-loop.php', true, false, $loop_args );
	else :
	 	bp_docs_locate_template( 'shortcode-loop.php', true, false, $loop_args );
	endif;

	$retval = ob_get_clean();

	wp_reset_postdata();

	// Restore the main doc_query; obliterate our secondary loop arguments.
	$bp->bp_docs->doc_query = $temp_doc_query;

	return $retval;
}
add_shortcode( 'bp_docs_recent_docs', 'bp_docs_recent_docs_shortcode_handler' );
