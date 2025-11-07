<?php

/**
 * AJAX handlers for attachments
 *
 * @since 1.4
 */

function bp_docs_attachment_item_markup_cb() {
	$attachment_id = isset( $_POST['attachment_id'] ) ? intval( $_POST['attachment_id'] ) : 0;
	$markup = bp_docs_attachment_item_markup( $attachment_id );
	wp_send_json_success( $markup );
}
add_action( 'wp_ajax_doc_attachment_item_markup', 'bp_docs_attachment_item_markup_cb' );

/**
 * Ajax handler to create dummy doc on creation
 *
 * @since 1.4
 */
function bp_docs_create_dummy_doc() {
	$group_id = null;
	if ( ! empty( $_POST['group_slug'] ) ) {
		$group_slug = sanitize_text_field( wp_unslash( $_POST['group_slug'] ) );
		$group_id   = BP_Groups_Group::group_exists( $group_slug );

		if ( ! $group_id ) {
			wp_send_json_error( __( 'Group does not exist.', 'bp-docs' ) );
		}

		if ( ! current_user_can( 'bp_docs_associate_with_group', $group_id ) ) {
			wp_send_json_error( __( 'You do not have permission to create a document in this group.', 'bp-docs' ) );
		}
	}

	add_filter( 'wp_insert_post_empty_content', '__return_false' );
	$doc_id = wp_insert_post( array(
		'post_type' => bp_docs_get_post_type_name(),
		'post_status' => 'auto-draft',
	) );
	remove_filter( 'wp_insert_post_empty_content', '__return_false' );

	if ( ! $doc_id || is_wp_error( $doc_id ) ) {
		wp_send_json_error( __( 'Could not create document.', 'bp-docs' ) );
	}

	if ( $doc_id && $group_id ) {
		bp_docs_set_associated_group_id( $doc_id, $group_id );
	}

	wp_send_json_success( array( 'doc_id' => $doc_id ) );
}
add_action( 'wp_ajax_bp_docs_create_dummy_doc', 'bp_docs_create_dummy_doc' );
