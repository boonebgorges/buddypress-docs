<?php

class BP_Docs_Attachments {
	protected $doc_id;
	protected $override_doc_id;

	protected $is_private;
	protected $htaccess_path;

	function __construct() {
		add_action( 'template_redirect', array( $this, 'catch_attachment_request' ), 20 );
		add_filter( 'upload_dir', array( $this, 'filter_upload_dir' ) );
		add_action( 'bp_docs_doc_saved', array( $this, 'check_privacy' ) );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'maybe_create_htaccess' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'pre_get_posts', array( $this, 'filter_gallery_posts' ) );

		require( dirname( __FILE__ ) . '/attachments-ajax.php' );
	}

	/**
	 * @todo
	 *
	 * - Must have script for recreating .htaccess files when:
	 *	- top-level Docs slug changes
	 *	- single Doc slug changes
	 */

	/**
	 * Catches bp-attachment requests and serves attachmens if appropriate
	 *
	 * @since 1.4
	 */
	function catch_attachment_request() {
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
	 * @todo There's a quirk in wp_upload_dir() that will create the
	 *   attachment directory if it doesn't already exist. This normally
	 *   isn't a problem, because wp_upload_dir() is generally only called
	 *   when a credentialed user is logged in and viewing the admin side.
	 *   But the current code will force a folder to be created the first
	 *   time the doc is viewed at all. Not sure whether this merits fixing
	 *
	 * @since 1.4
	 */
	function filter_upload_dir( $uploads ) {
		if ( ! $this->get_doc_id() ) {
			return $uploads;
		}

		$uploads = $this->mod_upload_dir( $uploads );

		return $uploads;
	}

	/**
	 * After a Doc is saved, check to see whether any action is necessary
	 *
	 * @since 1.4
	 */
	public function check_privacy( $query ) {
		if ( empty( $query->doc_id ) ) {
			return;
		}

		$this->set_doc_id( $query->doc_id );

		if ( $this->get_is_private() ) {
			$this->create_htaccess();
		} else {
			$this->delete_htaccess();
		}
	}

	/**
	 * Delete an htaccess file for the current Doc
	 *
	 * @since 1.4
	 * @return bool
	 */
	public function delete_htaccess() {
		if ( ! $this->get_doc_id() ) {
			return false;
		}

		$path = $this->get_htaccess_path();
		if ( file_exists( $path ) ) {
			unlink( $path );
		}

		return true;
	}

	/**
	 * Creates an .htaccess file in the appropriate upload dir, if appropriate
	 *
	 * As a hack, we've hooked to wp_handle_upload_prefilter. We don't
	 * actually do anything with the passed value; we just need a place
	 * to hook in reliably before the file is written.
	 *
	 * @since 1.4
	 * @param $file
	 * @return $file
	 */
	public function maybe_create_htaccess( $file ) {
		if ( ! $this->get_doc_id() ) {
			return $file;
		}

		if ( ! $this->get_is_private() ) {
			return $file;
		}

		$this->create_htaccess();

		return $file;
	}

	/**
	 * Creates an .htaccess for a Doc upload directory, if it doesn't exist
	 *
	 * No check happens here to see whether an .htaccess is necessary. Make
	 * sure you check $this->get_is_private() before running.
	 *
	 * @since 1.4
	 */
	public function create_htaccess() {
		$htaccess_path = $this->get_htaccess_path();
		if ( file_exists( $htaccess_path ) ) {
			return false;
		}

		$rules = $this->generate_rewrite_rules();

		if ( ! empty( $rules ) ) {
			insert_with_markers( $htaccess_path, 'BuddyPress Docs', $rules );
		}
	}

	/**
	 * Generates a path to htaccess for the Doc in question
	 *
	 * @since 1.4
	 * @return string
	 */
	public function get_htaccess_path() {
		if ( empty( $this->htaccess_path ) ) {
			$upload_dir = wp_upload_dir();
			$this->htaccess_path = $upload_dir['path'] . DIRECTORY_SEPARATOR . '.htaccess';
		}

		return $this->htaccess_path;
	}

	/**
	 * Check whether the current Doc is private ('read' != 'anyone')
	 *
	 * @since 1.4
	 * @return bool
	 */
	public function get_is_private() {
//		if ( is_null( $this->is_private ) ) {
			$doc_id = $this->get_doc_id();
			$doc_settings = (array) get_post_meta( $doc_id, 'bp_docs_settings', true );
			$this->is_private = isset( $doc_settings['read'] ) && 'anyone' !== $doc_settings['read'];
//		}

		return $this->is_private;
	}

	/**
	 * Set a doc id for this object
	 *
	 * This is a hack that lets you manually bypass the doc sniffing in
	 * get_doc_id()
	 */
	public function set_doc_id( $doc_id ) {
		$this->override_doc_id = intval( $doc_id );
	}

	/**
	 * Attempt to auto-detect a doc ID
	 *
	 * Marked protected because it sucks and is subject to change
	 */
	protected function get_doc_id() {

		if ( ! empty( $this->override_doc_id ) ) {

			$this->doc_id = $this->override_doc_id;

		} else {

			if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
				if ( bp_docs_is_existing_doc() ) {
					$this->doc_id = get_queried_object_id();
				}
			} else {
				// AJAX
				if ( isset( $_REQUEST['query']['auto_draft_id'] ) ) {

					$this->doc_id = (int) $_REQUEST['query']['auto_draft_id'];

				} else if ( isset( $_REQUEST['action'] ) && 'upload-attachment' == $_REQUEST['action'] ) {

					$maybe_doc = get_post( $_REQUEST['post_id'] );
					if ( bp_docs_get_post_type_name() == $maybe_doc->post_type ) {
						$this->doc_id = $maybe_doc->ID;
					}

				} else {
					// In order to check if this is a doc, must check ajax referer
					$this->doc_id = $this->get_doc_id_from_url( wp_get_referer() );
				}
			}
		}

		return $this->doc_id;
	}

	/**
	 * Get a Doc's ID from its URL
	 *
	 * Note that this only works for existing Docs, because (duh) new Docs
	 * don't yet have a URL
	 *
	 * @since 1.4
	 * @param string $url
	 * @return int $doc_id
	 */
	public static function get_doc_id_from_url( $url ) {
		$doc_id = null;
		$url = untrailingslashit( $url );
		$edit_location = strrpos( $url, BP_DOCS_EDIT_SLUG );
		if ( false !== $edit_location && BP_DOCS_EDIT_SLUG == substr( $url, $edit_location ) ) {
			$doc_id = url_to_postid( substr( $url, 0, $edit_location - 1 ) );
		}
		return $doc_id;
	}

	/**
	 * Filter upload_dir to customize Doc upload locations
	 *
	 * @since 1.4
	 * @return array $uploads
	 */
	function mod_upload_dir( $uploads ) {
		$subdir = DIRECTORY_SEPARATOR . 'bp-attachments' . DIRECTORY_SEPARATOR . $this->doc_id;

		$uploads['subdir'] = $subdir;
		$uploads['path'] = $uploads['basedir'] . $subdir;
		$uploads['url'] = $uploads['baseurl'] . '/bp-attachments/' . $this->doc_id;

		return $uploads;
	}

	function enqueue_scripts() {
		if ( bp_docs_is_doc_edit() || bp_docs_is_doc_create() ) {
			wp_enqueue_script( 'bp-docs-attachments', plugins_url( 'buddypress-docs/includes/js/attachments.js' ), array( 'media-editor', 'media-views' ), false, true );
		}
	}

	/**
	 * Filter the posts query on attachment pages, to ensure that only the
	 * specific Doc's attachments show up in the Gallery
	 *
	 * Hooked to 'pre_get_posts'. Bail out if we're not in a Gallery
	 * request for a Doc
	 *
	 * @since 1.4
	 */
	public function filter_gallery_posts( $query ) {
		if ( ! defined( 'DOING_AJAX' || ! DOING_AJAX ) ) {
			return;
		}

		if ( ! isset( $_POST['action'] ) || 'query-attachments' !== $_POST['action'] ) {
			return;
		}

		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

		if ( ! $post_id ) {
			return;
		}

		$post = get_post( $post_id );

		if ( empty( $post ) || is_wp_error( $post ) || bp_docs_get_post_type_name() !== $post->post_type ) {
			return;
		}

		// Phew
		$query->set( 'post_parent', $_REQUEST['post_id'] );
	}

	/**
	 * Generates the rewrite rules to be put in .htaccess of the upload dir
	 *
	 * @since 1.4
	 * @return array $rules One per line, to be put together by insert_with_markers()
	 */
	public function generate_rewrite_rules() {
		$rules = array();
		$doc_id = $this->get_doc_id();

		if ( ! $doc_id ) {
			return $rules;
		}

		$url = bp_docs_get_doc_link( $doc_id );
		$url_parts = parse_url( $url );

		if ( ! empty( $url_parts['path'] ) ) {
			$rules = array(
				'RewriteEngine On',
				'RewriteBase ' . $url_parts['path'],
				'RewriteRule (.+) ?bp-attachment=$1 [R=302,NC]',
			);
		}

		return $rules;
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

	/**
	 * Generate download headers
	 *
	 * @since 1.4
	 * @param string $filename Full path to file
	 * @return array Headers in key=>value format
	 */
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

