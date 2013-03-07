<?php

class BP_Docs_Attachments {
	protected $doc_id;
	protected $is_private;

	function __construct() {
		add_action( 'template_redirect', array( $this, 'catch_attachment_request' ), 20 );
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

	/**
	 * Catches bp-attachment requests and serves attachmens if appropriate
	 *
	 * @since 1.4
	 */
	function catch_attachment_request() {
		// Proof of concept only!
		// Must send better headers
		// Must send dynamic headers
		// Must do everything much better than this
		if ( ! empty( $_GET['bp-attachment'] ) ) {

			$fn = $_GET['bp-attachment'];

			// Sanity check - don't do anything if this is not a Doc
			if ( ! bp_docs_is_existing_doc() ) {
				return;
			}

			if ( ! $this->filename_is_safe( $fn ) ) {
				wp_die( __( 'File not found.', 'bp-docs' ) );
			}

			$uploads = wp_upload_dir();
			$filepath = $uploads['path'] . DIRECTORY_SEPARATOR . $fn;

			if ( ! file_exists( $filepath ) ) {
				wp_die( __( 'File not found.', 'bp-docs' ) );
			}

			$headers = $this->generate_headers( $filepath );

			// @todo Support xsendfile?
			// @todo Better to send header('Location') instead?
			//       Generate symlinks like Drupal. Needs FollowSymLinks
			foreach( $headers as $name => $field_value ) {
				@header("{$name}: {$field_value}");
			}

			readfile( $filepath );
		}
	}

	/**
	 * Attempts to customize upload_dir with our attachment paths
	 *
	 * @since 1.4
	 */
	function filter_upload_dir( $uploads ) {
		if ( ! $this->get_doc_id() ) {
			return $uploads;
		}

		if ( ! $this->get_is_private() ) {
			return $uploads;
		}

		$uploads = $this->mod_upload_dir( $uploads );

		return $uploads;
	}

	// @todo Create mode
	function get_is_private() {
//		if ( is_null( $this->is_private ) ) {
			$doc_id = $this->get_doc_id();
			$doc_settings = (array) get_post_meta( $doc_id, 'bp_docs_settings', true );
			$this->is_private = isset( $doc_settings['read'] ) && 'anyone' !== $doc_settings['read'];
//		}

		return $this->is_private;
	}

	function get_doc_id() {
//		if ( is_null( $this->doc_id ) ) {
			// @todo What about Create?
			if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
				if ( bp_docs_is_existing_doc() ) {
					$this->doc_id = get_queried_object_id();
				}
			} else {
				// In order to check if this is a doc, must check ajax referer
				$this->doc_id = $this->get_doc_id_from_url( wp_get_referer() );
			}
//		}

		return $this->doc_id;
	}

	// @todo create mode
	function get_doc_id_from_url( $url ) {
		$doc_id = null;
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

	/**
	 * Check to see whether a filename is safe
	 *
	 * This is used to sanitize file paths passed via $_GET params
	 *
	 * @since 1.4
	 * @param string $filename Filename to validate
	 * @return bool
	 */
	public static function filename_is_safe( $filename ) {
		// WP's core function handles most sanitization
		if ( $filename !== sanitize_file_name( $filename ) ) {
			return false;
		}

		// No leading dots
		if ( 0 === strpos( $filename, '.' ) ) {
			return false;
		}

		// No directory walking means no slashes
		$filename_parts = pathinfo( $filename );
		if ( $filename_parts['basename'] !== $filename ) {
			return false;
		}

		// Check filetype
		$ft = wp_check_filetype( $filename );
		if ( empty( $ft['ext'] ) ) {
			return false;
		}

		return true;
	}

	public static function generate_headers( $filename ) {
		// Disable compression
		@apache_setenv( 'no-gzip', 1 );
		@ini_set( 'zlib.output_compression', 'Off' );

		// @todo Make this more configurable
		$headers = wp_get_nocache_headers();

		// Content-Disposition
		$filename_parts = pathinfo( $filename );
		$headers['Content-Disposition'] = 'attachment; filename="' . $filename_parts['basename'] . '"';

		// Content-Type
		$filetype = wp_check_filetype( $filename );
		$headers['Content-Type'] = $filetype['type'];

		// Content-Length
		$filesize = filesize( $filename );
		$headers['Content-Length'] = $filesize;

		return $headers;
	}
}
