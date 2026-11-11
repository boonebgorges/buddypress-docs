<?php

/**
 * Validation callbacks
 *
 * @since 1.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verifies permissions for a validation AJAX request.
 *
 * Callers must verify the nonce first. Dies with a 403 if the request is not allowed.
 *
 * @since 2.2.8
 *
 * @param int $doc_id   ID of the Doc being edited. 0 when creating a new Doc.
 * @param int $group_id ID of the group being requested. 0 for none.
 * @return void
 */
function bp_docs_val_check_request( $doc_id, $group_id ) {
	if ( $doc_id ) {
		$can_access_doc = current_user_can( 'bp_docs_edit', $doc_id );
	} else {
		$can_access_doc = current_user_can( 'bp_docs_create' );
	}

	if ( ! $can_access_doc ) {
		wp_die( '', '', 403 );
	}

	if ( ! $group_id || current_user_can( 'bp_docs_associate_with_group', $group_id ) ) {
		return;
	}

	// Editors may view details for the group the Doc is already associated with,
	// even if they are not themselves able to associate Docs with it.
	if ( $doc_id && (int) bp_docs_get_associated_group_id( $doc_id ) === $group_id ) {
		return;
	}

	wp_die( '', '', 403 );
}

function bp_docs_val_refresh_access_settings() {
	check_ajax_referer( 'bp_docs_validation', 'nonce' );

	$doc_id   = isset( $_POST['doc_id'] ) ? intval( $_POST['doc_id'] ) : 0;
	$group_id = isset( $_POST['group_id'] ) ? intval( $_POST['group_id'] ) : 0;

	bp_docs_val_check_request( $doc_id, $group_id );

	bp_docs_doc_settings_markup( $doc_id, $group_id );

	die();
}
add_action( 'wp_ajax_refresh_access_settings', 'bp_docs_val_refresh_access_settings' );

function bp_docs_val_refresh_associated_group() {
	check_ajax_referer( 'bp_docs_validation', 'nonce' );

	$doc_id   = isset( $_POST['doc_id'] ) ? intval( $_POST['doc_id'] ) : 0;
	$group_id = isset( $_POST['group_id'] ) ? intval( $_POST['group_id'] ) : 0;

	bp_docs_val_check_request( $doc_id, $group_id );

	bp_docs_associated_group_summary( $group_id );

	die();
}
add_action( 'wp_ajax_refresh_associated_group', 'bp_docs_val_refresh_associated_group' );
