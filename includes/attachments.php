<?php

class BP_Docs_Attachments {
	function __construct() {
		add_action( 'bp_actions', array( $this, 'catch_attachment_request' ), 0 );
		add_filter( 'upload_dir', array( $this, 'filter_upload_dir' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Workflow:
	 *
	 * - upload_dir is filtered on Docs to head to doc-specific directory
	 * - When doc directory is created, create a default .htaccess file (todo). Don't have to do this for anyone-can-read Docs
	 * - Must have script for recreating .htaccess files when privacy level changes; bulk changes when slug changed, etc
	 * - When files are requested in the directories, redirect to <doc url>?bp-attachment=filename.ext
	 * - Privacy protection is automatically handled because you're viewing under the Doc URL
	 * - Sanitize filename.ext. Can probably remove all slashes. Can probably also do a file_exists() check within that single directory to be sure?
	 * - Dynamically determine headers and readfile
	 */

	/**
	 * .htaccess format:
	 *
	 *
	 * RewriteEngine On
	 * RewriteBase /wpmaster/poops/foo10/
	 *
	 * RewriteRule (.+) ?bp-attachment=$1 [R=302,NC]
	 */

	function catch_attachment_request() {
		// Proof of concept only!
		// This is massively unsecure
		// Must send better headers
		// Must send dynamic headers
		// Must do everything much better than this
		if ( ! empty( $_GET['bp-attachment'] ) ) {
			$uploads = wp_upload_dir();
			header( 'Content-type: image/jpeg' );
//			var_dump( $uploads['path'] . '/' . $_GET['bp-attachment'] );
			readfile( $uploads['path'] . '/' . $_GET['bp-attachment'] );
		}
	}

	function filter_upload_dir( $uploads ) {

		$this->doc_id = 0;

		// @todo What about Create?
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			if ( bp_docs_is_existing_doc() ) {
				$this->doc_id = get_queried_object_id();
			}
		} else {
			// In order to check if this is a doc, must check ajax referer
			$this->doc_id = $this->get_doc_id_from_url( wp_get_referer() );
		}

		if ( ! $this->doc_id ) {
			return $uploads;
		}

		// Should do this earlier
		$maybe_doc = get_post( $this->doc_id );
		$is_doc = bp_docs_get_post_type_name() == $maybe_doc->post_type;

		if ( $is_doc ) {
			$uploads = $this->mod_upload_dir( $uploads );
		}

		return $uploads;
	}

	function get_doc_id_from_url( $url ) {
		$doc_id = 0;
		$url = untrailingslashit( $url );
		$edit_location = strrpos( $url, BP_DOCS_EDIT_SLUG );
		if ( false !== $edit_location && BP_DOCS_EDIT_SLUG == substr( $url, $edit_location ) ) {
			$doc_id = url_to_postid( substr( $url, 0, $edit_location - 1 ) );
		}
		return $doc_id;
	}

	function mod_upload_dir( $uploads ) {
		$subdir = DIRECTORY_SEPARATOR . 'bp-attachments' . DIRECTORY_SEPARATOR . $this->doc_id;

		$uploads['subdir'] = $subdir;
		$uploads['path'] = $uploads['basedir'] . $subdir;
		$uploads['url'] = $uploads['baseurl'] . '/bp-attachments/' . $this->doc_id;

		return $uploads;
	}

	function enqueue_scripts() {
		if ( bp_docs_is_doc_edit() ) {
			wp_enqueue_script( 'bp-docs-attachments', plugins_url( 'buddypress-docs/includes/js/attachments.js' ), array( 'media-editor', 'media-views' ), false, true );
		}
	}

	public static function filename_is_safe( $filename ) {
		// WP's core function handles most sanitization
		if ( $filename !== sanitize_file_name( $filename ) ) {
			return false;
		}

		// No directory walking means no slashes
		if ( false !== strpos( $filename, '/' ) ) {
			return false;
		}

		// No leading dots
		if ( 0 === strpos( $filename, '.' ) ) {
			return false;
		}

		return true;
	}
}
