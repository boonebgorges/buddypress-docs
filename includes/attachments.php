<?php

class BP_Docs_Attachments {
	function __construct() {
		add_filter( 'upload_dir', array( $this, 'filter_upload_dir' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	function filter_upload_dir( $uploads ) {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return $uploads;
		}

		// In order to check if this is a doc, must check ajax referer
		$this->doc_id = $this->get_doc_id_from_url( wp_get_referer() );

		if ( ! $this->doc_id ) {
			return $uploads;
		}

		$maybe_doc = get_post( $this->doc_id );
		$is_doc = bp_docs_get_post_type_name() == $maybe_doc->post_type;

		if ( $is_doc ) {
			$uploads = $this->mod_upload_dir( $uploads );
		}

		return $uploads;
	}

	function get_doc_id_from_url( $url ) {
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
}
