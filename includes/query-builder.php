<?php
/**
 * @package BuddyPressDocs
 */

/**
 * Main BuddyPress Docs query class.
 */
class BP_Docs_Query {
	var $post_type_name;
	var $associated_item_tax_name;

	var $item_type;
	var $item_id;
	var $item_name;
	var $item_slug;

	var $doc_id;
	var $doc_slug;

	var $current_view;

	var $term_id;
	var $item_type_term_id;
	var $user_term_id;

	var $is_new_doc;

	var $query_args;
	var $query;

	/**
	 * Pre-save revision.
	 *
	 * Can be used by action callbacks to determine whether various pieces of content have changed.
	 *
	 * @since 1.9.1
	 *
	 * @var WP_Post
	 */
	public $previous_revision;

	/**
	 * PHP 5 constructor
	 *
	 * @since 1.0-beta
	 */
	function __construct( $args = array() ) {
		global $bp;

		$this->post_type_name 		= $bp->bp_docs->post_type_name;
		$this->associated_item_tax_name	= $bp->bp_docs->associated_item_tax_name;

		// Get the item slug, if there is one available
		$this->doc_slug = $this->get_doc_slug();

		$defaults = array(
			'doc_id'	 => array(),     // Array or comma-separated string
			'doc_slug'	 => $this->doc_slug, // String
			'group_id'	 => null,     // Array or comma-separated string
			'parent_id'	 => 0,		 // int
			'author_id'	 => array(),     // Array or comma-separated string
			'folder_id'      => null,
			'edited_by_id'   => array(),     // Array or comma-separated string
			'tags'		 => array(),     // Array or comma-separated string
			'order'		 => 'ASC',       // ASC or DESC
			'orderby'	 => 'modified',  // 'modified', 'title', 'author', 'created'
			'paged'		 => 1,
			'posts_per_page' => 10,
			'search_terms'   => '',
			'status'         => 'publish',
		);
		$r = wp_parse_args( $args, $defaults );

		$this->query_args = $r;

	}

	/**
	 * Gets the item type of the item you're looking at - e.g 'group', 'user'.
	 *
	 * @since 1.0-beta
	 *
	 * @return str $view The current item type
	 */
	function get_item_type() {
		_deprecated_function( __METHOD__, '1.2' );
	}

	/**
	 * Gets the doc slug as represented in the URL
	 *
	 * @since 1.0-beta
	 *
	 * @return str $view The current doc slug
	 */
	function get_doc_slug() {
		$slug = '';

		$obj = get_queried_object();
		if ( isset( $obj->post_name ) ) {
			$slug = $obj->post_name;
		}

		return apply_filters( 'bp_docs_this_doc_slug', $slug, $this );
	}

	/**
	 * Gets the item id of the item (eg group, user) associated with the page you're on.
	 *
	 * @since 1.0-beta
	 *
	 * @return str $view The current item type
	 */
	function setup_item() {
		global $bp;

		if ( empty( $this->item_type ) )
			return false;

		$id = '';
		$name = '';
		$slug = '';

		switch ( $this->item_type ) {
			case 'group' :
				if ( bp_is_active( 'groups' ) && bp_is_group() ) {
					$group = groups_get_current_group();
					$id    = $group->id;
					$name  = $group->name;
					$slug  = $group->slug;
				}
				break;
			case 'user' :
				if ( bp_is_user() ) {
					$id   = bp_displayed_user_id();
					$name = bp_get_displayed_user_fullname();
					$slug = bp_get_displayed_user_username();
				}
				break;
		}

		// Todo: abstract into groups. Will be a pain
		$this->item_id 		= apply_filters( 'bp_docs_get_item_id', $id );
		$this->item_name 	= apply_filters( 'bp_docs_get_item_name', $name );
		$this->item_slug 	= apply_filters( 'bp_docs_get_item_slug', $slug );

		// Put some stuff in $bp
		$bp->bp_docs->current_item	= $this->item_id;
	}

	/**
	 * Gets the id of the taxonomy term associated with the item
	 *
	 * @since 1.0-beta
	 *
	 * @return str $view The current item type
	 */
	function setup_terms() {
		global $bp;

		$this->term_id = bp_docs_get_item_term_id( $this->item_id, $this->item_type, $this->item_name );

		if ( bp_is_user() ) {
			// If this is a User Doc, then the user_term_id is the same as the term_id
			$this->user_term_id = $this->term_id;
		} else {
			$this->user_term_id = bp_docs_get_item_term_id( $this->item_id, 'user',  bp_get_loggedin_user_fullname() );
		}
	}

	/**
	 * Gets the current view, based on the page you're looking at.
	 *
	 * Filter 'bp_docs_get_current_view' to extend to different components.
	 *
	 * @since 1.0-beta
	 *
	 * @param str $item_type Defaults to the object's item type
	 * @return str $view The current view. Core values: edit, single, list, category
	 */
	function get_current_view( $item_type = false ) {
		_deprecated_function( __METHOD__, '1.2' );
	}

	function get_wp_query() {
		global $bp, $wpdb;

		// Set up the basic args
		$wp_query_args = array(
			'post_type'  => $this->post_type_name,
			'tax_query'  => array(),
			'meta_query' => array(),
		);

		// Skip everything else if this is a single doc query
		if ( $doc_id = (int)$this->query_args['doc_id'] ) {
			$wp_query_args['ID'] = $doc_id;
		} else if ( $doc_slug = $this->query_args['doc_slug'] ) {
			$wp_query_args['name'] = $doc_slug;
		} else {

			// Only call this here to reduce database calls on other pages
			$this->setup_terms();

			// 'orderby' generally passes through, except for in 'most_active' queries
			if ( 'most_active' == $this->query_args['orderby'] ) {
				$wp_query_args['orderby']  = 'meta_value_num';
				$wp_query_args['meta_key'] = 'bp_docs_revision_count';
			} else {
				$wp_query_args['orderby']  = $this->query_args['orderby'];
			}

			// Pagination and order args carry over directly
			foreach ( array( 'order', 'paged', 'posts_per_page' ) as $key ) {
				$wp_query_args[$key] = $this->query_args[$key];
			}

			// Only add a search parameter if it's been passed
			if ( !empty( $this->query_args['search_terms'] ) ) {
				$wp_query_args['s'] = $this->query_args['search_terms'];

				// For attachments, search separately and then append to WP's default search handling.
				if ( bp_docs_enable_attachments() ) {
					$attachment_match_doc_ids = $this->search_docs_for_attachment_matches( $this->query_args['search_terms'] );

					if ( $attachment_match_doc_ids ) {
						$this->attachment_match_doc_ids = $attachment_match_doc_ids;
						add_filter( 'posts_search', array( $this, 'attachment_filename_search_filter' ) );
					}
				}
			}

			// If an author_id param has been passed, pass it directly to WP_Query
			if ( ! empty( $this->query_args['author_id'] ) ) {
				$wp_query_args['author'] = implode( ',', wp_parse_id_list( $this->query_args['author_id'] ) );
			}

			// If this is the user's "started by me" library, we'll include trashed and pending posts
			// Any edit to a trashed post restores it to status 'publish'
			if ( ! empty( $this->query_args['author_id'] ) && $this->query_args['author_id'] == get_current_user_id()  ) {
				$wp_query_args['post_status'] = array( 'publish', 'trash', 'bp_docs_pending' );
			}

			// If an edited_by_id param has been passed, get a set
			// of post ids that have revisions authored by that user
			if ( ! empty( $this->query_args['edited_by_id'] ) ) {
				$wp_query_args['post__in'] = $this->get_edited_by_post_ids();
			}

			// Access queries are handled at pre_get_posts, using bp_docs_general_access_protection()

			// Set the taxonomy query. Filtered so that plugins can alter the query
			// Filtering by groups and folders also happens in this way
			$wp_query_args['tax_query'] = apply_filters( 'bp_docs_tax_query', $wp_query_args['tax_query'], $this );

			if ( !empty( $this->query_args['parent_id'] ) ) {
				$wp_query_args['post_parent'] = $this->query_args['parent_id'];
			}
		}

		// Filter these arguments just before they're sent to WP_Query
		// Devs: This allows you to send any custom parameter you'd like, and modify the
		// query appropriately
		$wp_query_args = apply_filters( 'bp_docs_pre_query_args', $wp_query_args, $this );

		$this->query = new WP_Query( $wp_query_args );

		return $this->query;
	}

	/**
	 *
	 */
	public static function get_edited_by_post_ids_for_user( $editor_ids ) {
		$editor_ids = wp_parse_id_list( $editor_ids );
		$post_ids = array();

		foreach ( $editor_ids as $editor_id ) {
			// @todo - Not sure how this will scale
			$posts = get_posts( array(
				'author'                 => $editor_id,
				'post_status'            => array( 'inherit', 'publish' ),
				'post_type'              => array( 'revision', bp_docs_get_post_type_name() ),
				'posts_per_page'         => -1,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			) );

			$this_author_post_ids = array();
			foreach ( $posts as $post ) {
				if ( 'revision' === $post->post_type ) {
					$this_author_post_ids[] = $post->post_parent;
				} else {
					$this_author_post_ids[] = $post->ID;
				}
			}
			$post_ids = array_merge( $post_ids, $this_author_post_ids );
		}

		// If the list is empty (the users haven't edited any Docs yet)
		// force 0 so that no items are shown
		if ( empty( $post_ids ) ) {
			$post_ids = array( 0 );
		}

		// @todo Might be faster to let the dupes through and let MySQL optimize
		return array_unique( $post_ids );
	}

	/**
	 *
	 */
	function get_edited_by_post_ids() {
		return self::get_edited_by_post_ids_for_user( $this->query_args['edited_by_id'] );
	}

	/**
	 */
	function get_access_tax_query() {
		$bp_docs_access_query = new BP_Docs_Access_Query( bp_loggedin_user_id() );
		return $bp_docs_access_query->get_tax_query();
	}

	/**
	 * Used to be use to build the query_posts() query. Now handled by get_wp_query() and
	 * bp_docs_has_docs()
	 *
	 * @since 1.0-beta
	 * @deprecated 1.2
	 */
	function build_query() {
		_deprecated_function( __FUNCTION__, '1.2', 'No longer used. See bp_docs_has_docs() and BP_Docs_Query::get_wp_query().' );
	}

	/**
	 * Fires the WP query and loads the appropriate template
	 *
	 * @since 1.0-beta
	 */
	function load_template() {
		global $bp, $post;

		// Docs are stored on the root blog
		if ( !bp_is_root_blog() )
			switch_to_blog( BP_ROOT_BLOG );

		switch ( $this->current_view ) {
			case 'create' :
				// Todo: Make sure the user has permission to create

				/**
				 * Load the template tags for the edit screen
				 */
				if ( !function_exists( 'wp_tiny_mce' ) ) {
					$this->define_wp_tiny_mce();
				}

				require_once( BP_DOCS_INCLUDES_PATH . 'templatetags-edit.php' );

				$template = 'edit-doc.php';
				break;
			case 'list' :

				$template = 'docs-loop.php';
				break;
			case 'category' :
				// Check to make sure the category exists
				// If not, redirect back to list view with error
				// Otherwise, get args based on category ID
				// Then load the loop template
				break;
			case 'single' :
			case 'edit' :
			case 'delete' :
			case 'history' :

				// If this is the edit screen, we won't really be able to use a
				// regular have_posts() loop in the template, so we'll stash the
				// post in the $bp global for the edit-specific template tags
				if ( $this->current_view == 'edit' ) {
					if ( bp_docs_has_docs() ) : while ( bp_docs_has_docs() ) : bp_docs_the_doc();
						$bp->bp_docs->current_post = $post;

						// Set an edit lock
						wp_set_post_lock( $post->ID );
					endwhile; endif;

					/**
					 * Load the template tags for the edit screen
					 */
					require_once( BP_DOCS_INCLUDES_PATH . 'templatetags-edit.php' );
				}

				switch ( $this->current_view ) {
					case 'single' :
						$template = 'single/index.php';
						break;
					case 'edit' :
						$template = 'single/edit.php';
						break;
					case 'history' :
						$template = 'single/history.php';
						break;

				}
				// Todo: Maybe some sort of error if there is no edit permission?

				break;
		}

		// Only register on the root blog
		if ( !bp_is_root_blog() )
			restore_current_blog();

		$template_path = bp_docs_locate_template( $template );

		if ( !empty( $template ) )
			include( apply_filters( 'bp_docs_template', $template_path, $this ) );
	}

	/**
	 * Saves a doc.
	 *
	 * This method handles saving for both new and existing docs. It detects the
	 * difference by looking for the presence of $this->doc_slug
	 *
	 * @since 1.0-beta
	 *
	 * @param array $passed_args {
	 *	      @type int    $doc_id ID of the doc, if it already exists.
	 *	      @type string $title Doc title.
	 *	      @type string $content Doc content.
	 *	      @type string $permalink Optional. Permalink will be calculated if
	 *                     if not specified.
	 *	      @type int    $author_id ID of the user submitting the changes.
	 *	      @type int    $group_id ID of the associated group, if any.
	 *                     Special cases: Passing "null" leaves current group
	 *                     associations intact. Passing 0 will unset existing
	 *                     group associations.
	 *	      @type bool   $is_auto Is this an autodraft?
	 *	      @type array  $taxonomies Taxonomy terms to apply to the doc.
	 *                     Use the form: array( $tax_name => (array) $terms ).
	 *	      @type array  $settings Doc access settings. Of the form:
	 *                     array( 'read' => 'group-members',
	 *                            'edit' => 'admins-mods',
	 *                            'read_comments' => 'group-members',
	 *                            'post_comments' => 'group-members',
	 *                            'view_history' => 'creator' )
	 *	      @type int    $parent_id    The ID of the parent doc, if applicable.
	 *	      @type string $save_context How this doc is being saved.
	 *	      @type string $redirect_to  Target mode to return to. 'single' or 'edit'.
	 *        }
	 * @return array {
	 *		  @type string $message_type Type of message, success or error.
	 *		  @type string $message Text of message to display to user.
	 *		  @type string $redirect_url URL to use for redirect after save.
	 *		  @type int    $doc_id ID of the updated doc, if applicable.
	 *        }
	 */
	function save( $passed_args = array() ) {
		global $wp_rewrite;
		$bp = buddypress();

		// Sensible defaults
		$defaults = array(
			'doc_id' 		=> 0,
			'title'			=> '',
			'content' 		=> '',
			'permalink'		=> '',
			'author_id'		=> bp_loggedin_user_id(),
			'group_id'		=> null,
			'is_auto'		=> 0,
			'taxonomies'	=> array(),
			'settings'		=> array(),
			'parent_id'		=> 0,
			'save_context'  => 'direct',
			'redirect_to'   => 'single',
		);

		$args = wp_parse_args( $passed_args, $defaults );

		// bbPress plays naughty with revision saving
		add_action( 'pre_post_update', 'wp_save_post_revision' );

		// Get the required taxonomy items associated with the group. We only run this
		// on a save because it requires extra database hits.
		$this->setup_terms(); // @TODO: Not sure what this is doing

		// Set up the default value for the result message
		$result = array(
			'error'    => false,
			'message'  => __( 'Unknown error. Please try again.', 'buddypress-docs' ),
			'redirect' => 'create'
		);

		/**
		 * Filters the default results array based on the passed args.
		 * Returning $result['error'] = true will prevent the doc from being saved.
		 *
		 * @since 2.0.0
		 *
		 * @param array $result The default results array.
		 * @param array $args   The parameters for the doc about to be saved.
		 */
		$result = apply_filters( 'bp_docs_filter_result_before_save', $result, $args );

		// Is this a new doc?
		$this->is_new_doc = ( 0 === $args['doc_id'] || 'auto-draft' === get_post_status( $args['doc_id'] ) );

		if ( true === $result['error'] ) {
			/*
			 * An extension has reported an error. Do not save.
			 * Extension should also provide error message information.
			 */
		} elseif ( empty( $args['title'] ) ) {
			// The title field is required
			$result['message'] = __( 'The title field is required.', 'buddypress-docs' );
			$result['redirect'] = $this->is_new_doc ? 'create' : 'edit';
		} else {
			// Use the passed permalink if it exists, otherwise create one
			if ( ! empty( $args['permalink'] ) ) {
				$slug = sanitize_title( $args['permalink'] );
			} else {
				$slug = sanitize_title( $args['title'] );
			}

			$r = array(
				'ID'           => absint( $args['doc_id'] ),
				'post_type'    => $this->post_type_name,
				'post_title'   => $args['title'],
				'post_name'    => $slug,
				'post_content' => $args['content'],
				'post_status'  => 'publish',
				'post_parent'  => $args['parent_id']
			);

			if ( $this->is_new_doc ) {
				// Save the author for new docs.
				$r['post_author'] = $args['author_id'];
			} else {
				// Save pre-update post data, for comparison by callbacks.
				$this->previous_revision = get_post( $args['doc_id'] );

				// If this post is "pending," leave it pending.
				if ( $this->previous_revision->post_status === 'bp_docs_pending' ) {
					$r['post_status'] = 'bp_docs_pending';
				}
			}

			/**
			 * Fires before the doc has been saved.
			 *
			 * @since 2.1.0
			 *
			 * @param array  $r    The parameters to be used in wp_insert_post().
			 * @param object $this The BP_Docs_Query object.
			 * @param array  $args The passed and filtered parameters for the doc
			 *                     about to be saved.
			 */
			$r = apply_filters( 'bp_docs_post_args_before_save', $r, $this, $args );

			if ( $this->is_new_doc ) {
				$this->doc_id = wp_insert_post( $r );
			} else {
				$this->doc_id = wp_update_post( $r );
			}

			if ( ! $this->doc_id ) {
				// Failed to save. Set error message.
				if ( $this->is_new_doc  ) {
					$result['message'] = __( 'There was an error when creating the doc.', 'buddypress-docs' );
					$result['redirect'] = 'create';
				} else {
					$result['message'] = __( 'There was an error when saving the doc.', 'buddypress-docs' );
					$result['redirect'] = 'edit';
				}
			} else {
				// Successful save.
				$the_doc = get_post( $this->doc_id );
				$this->doc_slug = $the_doc->post_name;

				// Save the last editor id. We'll use this to create an activity item.
				update_post_meta( $this->doc_id, 'bp_docs_last_editor', $args['author_id'] );

				// Make sure the current user is added as one of the authors
				// @TODO: Is this still used?
				wp_set_post_terms( $this->doc_id, $this->user_term_id, $this->associated_item_tax_name, true );

				// Update taxonomies if necessary.
				if ( ! empty( $args['taxonomies'] ) ) {
					foreach ( $args['taxonomies'] as $tax_name => $terms ) {
						wp_set_post_terms( $this->doc_id, $terms, $tax_name );
					}
				}

				// Increment the revision count
				$revision_count = 0;
				$revisions = wp_get_post_revisions( $this->doc_id );
				if ( $revisions ) {
					$revision_count = count( $revisions );
				}
				update_post_meta( $this->doc_id, 'bp_docs_revision_count', $revision_count );

				/**
				 * Fires after the doc has been successfully saved.
				 *
				 * @since 2.0.0
				 *
				 * @param int   $id   The ID of the recently saved doc.
				 * @param array $args The passed and filtered parameters for the doc
				 *                    that was just saved.
				 */
				do_action( 'bp_docs_after_successful_save', $this->doc_id, $args );

				// Set successful save message.
				if ( $this->is_new_doc ) {
					// New doc saved.
					$result['message'] = __( 'Doc successfully created!', 'buddypress-docs' );
				} elseif ( $args['is_auto'] ) {
					// Doc update was an autosave.
					$result['message'] = __( 'You idled a bit too long while in Edit mode. In order to allow others to edit the doc you were working on, your changes have been autosaved. Click the Edit button to return to Edit mode.', 'buddypress-docs' );
				} else {
					// Existing Doc updated.
					$result['message'] = __( 'Doc successfully edited!', 'buddypress-docs' );
				}

				if ( 'edit' === $args['redirect_to'] ) {
					$result['redirect'] = 'edit';
				} else {
					$result['redirect'] = 'single';
				}

				$message_type = 'success';

				// Save settings. We append the notice message if necessary.
				$access_setting_message = bp_docs_save_doc_access_settings( $this->doc_id, $args['author_id'], $args['settings'], $this->is_new_doc );
				if ( $access_setting_message ) {
					$result['message'] = $result['message'] . ' ' . $access_setting_message;
				}
			}
		}

		// Provide a custom hook for plugins and optional components.
		// WP's default save_post isn't enough, because we need something that fires
		// only when we save from the front end (for things like taxonomies, which
		// the WP admin handles automatically)
		do_action( 'bp_docs_doc_saved', $this );

		/**
		 * Fires after the doc has been saved.
		 *
		 * @since 2.0.0
		 *
		 * @param int   $id   The ID of the recently saved doc.
		 * @param array $args The passed and filtered parameters for the doc
		 *                    that was just saved.
		 */
		do_action( 'bp_docs_after_save', $this->doc_id, $args );

		if ( ! isset( $message_type ) ) {
			$message_type = $result['redirect'] == 'single' ? 'success' : 'error';
		}

		// Stuff data into a cookie so it can be accessed on next page load
		if ( 'error' === $message_type ) {
			setcookie( 'bp-docs-submit-data', json_encode( $args ), time() + 30, '/' );
		}

		$redirect_base = trailingslashit( bp_get_root_domain() );
		if ( $wp_rewrite->using_index_permalinks() ) {
			$redirect_base .= 'index.php/';
		}

		$redirect_url = apply_filters( 'bp_docs_post_save_redirect_base', trailingslashit( $redirect_base . bp_docs_get_docs_slug() ) );

		if ( $result['redirect'] == 'single' ) {
			$redirect_url .= $this->doc_slug;
		} elseif ( $result['redirect'] == 'edit' ) {
			$redirect_url .= $this->doc_slug . '/' . BP_DOCS_EDIT_SLUG;
		} elseif ( $result['redirect'] == 'create' ) {
			$redirect_url .= BP_DOCS_CREATE_SLUG;
		}

		$retval = array(
			'message_type' 	=> $message_type,
			'message' 		=> $result['message'],
			'redirect_url' 	=> $redirect_url,
			'doc_id' 		=> $this->doc_id,
		);

		return $retval;
	}

	/**
	 * In WP 3.3, wp_tiny_mce() was deprecated, with its JS loading handled by the_editor. So we just
	 * provide a dummy function, for backward template support.
	 *
	 * @since 1.1.19
	 */
	function define_wp_tiny_mce() {
		function wp_tiny_mce() {
			return;
		}
	}

	/**
	 * Filters post search to include Docs with attachments whose filename matches search term.
	 *
	 * @since 2.1.0
	 *
	 * @param string $search Search string, as generated by WP_Query.
	 * @return string Modified SQL clause.
	 */
	public function attachment_filename_search_filter( $search ) {
		global $wpdb;

		remove_filter( 'posts_clauses', array( $this, 'attachment_filename_search_filter' ) );

		if ( ! $search || empty( $this->attachment_match_doc_ids ) ) {
			return $search;
		}

		$raw_search = preg_replace( '/^\s*AND/', '', $search );
		$doc_ids    = implode( ',', array_map( 'intval', $this->attachment_match_doc_ids ) );
		$search     = " AND ( $wpdb->posts.ID IN ({$doc_ids}) OR $raw_search )";

		return $search;
	}

	/**
	 * Fetch the IDs of Docs with attachments whose filenames match a search term.
	 *
	 * @since 2.1.0
	 *
	 * @param string $search_term Search term.
	 * @return array
	 */
	protected function search_docs_for_attachment_matches( $search_term ) {
		global $wpdb;

		$last_changed = wp_cache_get( 'last_changed', 'posts' );
		$cache_key = md5( 'bp_docs_attachment_search_' . $search_term . $last_changed );
		$cached = wp_cache_get( $cache_key, 'posts' );
		if ( false === $cached ) {
			$like = '%' . $wpdb->esc_like( $search_term ) . '%';

			$doc_ids = $wpdb->get_col( $wpdb->prepare( "SELECT d.ID FROM {$wpdb->posts} d JOIN {$wpdb->posts} a ON ( d.ID = a.post_parent ) LEFT JOIN {$wpdb->postmeta} pm ON ( a.ID = pm.post_id AND pm.meta_key = '_wp_attached_file' ) WHERE d.post_type = %s AND a.post_type = 'attachment' AND pm.meta_value LIKE %s", bp_docs_get_post_type_name(), $like ) );

			wp_cache_set( $cache_key, $doc_ids, $cached );
		} else {
			$doc_ids = $cached;
		}

		return array_map( 'intval', $doc_ids );
	}
}
