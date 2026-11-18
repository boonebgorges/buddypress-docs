<?php

/**
 * AJAX handlers for attachments
 *
 * @since 1.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ajax handler to fetch the markup for a newly uploaded attachment.
 *
 * @since 1.4
 * @since 2.2.8 Requires a nonce, and the attachment must belong to a Doc the user can edit.
 */
function bp_docs_attachment_item_markup_cb() {
	check_ajax_referer( 'bp_docs_attachments', 'nonce' );

	$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
	$attachment    = $attachment_id ? get_post( $attachment_id ) : null;

	if (
		! $attachment
		|| 'attachment' !== $attachment->post_type
		|| bp_docs_get_post_type_name() !== get_post_type( $attachment->post_parent )
		|| ! current_user_can( 'bp_docs_edit', $attachment->post_parent )
	) {
		wp_send_json_error( '', 403 );
	}

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
	check_ajax_referer( 'bp_docs_attachments', 'nonce' );

	if ( ! current_user_can( 'bp_docs_create' ) ) {
		wp_send_json_error( __( 'You do not have permission to create a document.', 'buddypress-docs' ) );
	}

	$group_id = null;
	if ( ! empty( $_POST['group_slug'] ) ) {
		$group_slug = sanitize_text_field( wp_unslash( $_POST['group_slug'] ) );
		$group_id   = BP_Groups_Group::group_exists( $group_slug );

		if ( ! $group_id ) {
			wp_send_json_error( __( 'Group does not exist.', 'buddypress-docs' ) );
		}

		if ( ! current_user_can( 'bp_docs_associate_with_group', $group_id ) ) {
			wp_send_json_error( __( 'You do not have permission to create a document in this group.', 'buddypress-docs' ) );
		}
	}

	add_filter( 'wp_insert_post_empty_content', '__return_false' );
	$doc_id = wp_insert_post( array(
		'post_type' => bp_docs_get_post_type_name(),
		'post_status' => 'auto-draft',
	) );
	remove_filter( 'wp_insert_post_empty_content', '__return_false' );

	if ( ! $doc_id || is_wp_error( $doc_id ) ) {
		wp_send_json_error( __( 'Could not create document.', 'buddypress-docs' ) );
	}

	if ( $doc_id && $group_id ) {
		bp_docs_set_associated_group_id( $doc_id, $group_id );
	}

	// Private to its creator until the first save applies the real settings.
	// Without an access term, the Doc (and its attachments) would be hidden
	// from the creator as well.
	bp_docs_update_doc_access( $doc_id, 'creator' );

	wp_send_json_success( array( 'doc_id' => $doc_id ) );
}
add_action( 'wp_ajax_bp_docs_create_dummy_doc', 'bp_docs_create_dummy_doc' );
