<?php

/**
 * Folder functionality.
 *
 * @since 1.8
 */
class BP_Docs_Folders {

	/**
	 * Constructor.
	 *
	 * @since 1.8
	 */
	public function __construct() {
		$this->register_post_type();
		$this->register_taxonomies();

		add_action( 'bp_docs_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'bp_docs_enqueue_scripts_edit', array( $this, 'enqueue_assets' ) );

		add_filter( 'bp_docs_user_can', array( $this, 'user_can' ), 10, 4 );
	}

	/**
	 * Register the Folder post type.
	 *
	 * This add-on is loaded after 'init', so we call it directly from the
	 * constructor.
	 *
	 * @since 1.8
	 */
	public function register_post_type() {
		$labels = array(
			'name' => __( 'BuddyPress Docs Folders', 'bp-docs' ),
			'singular_name' => __( 'BuddyPress Docs Folder', 'bp-docs' ),
			'menu_name' => __( 'Docs Folders', 'bp-docs' ),
			'name_admin_bar' => __( 'Docs Folder', 'bp-docs' ),
		);

		register_post_type( 'bp_docs_folder', array(
			'labels' => $labels,
			'public' => true,
			'hierarchical' => true,
		) );
	}

	/**
	 * Register the Folders taxonomies.
	 *
	 * - bp_docs_doc_in_folder contains relationships between Docs and
	 *   Folders
	 * - bp_docs_folder_in_user contains relationships between Folders
	 *   and Users
	 * - bp_docs_folder_in_group contains relationships between Folders
	 *   and Groups
	 *
	 * @since 1.8
	 */
	public function register_taxonomies() {
		register_taxonomy( 'bp_docs_doc_in_folder', bp_docs_get_post_type_name(), array(
			'public' => false,
		) );

		register_taxonomy( 'bp_docs_folder_in_user', 'bp_docs_folder', array(
			'public' => true,
		) );

		if ( bp_is_active( 'groups' ) ) {
			register_taxonomy( 'bp_docs_folder_in_group', 'bp_docs_folder', array(
				'public' => true,
			) );
		}
	}

	/**
	 * Enqueue CSS and JS assets.
	 *
	 * @since 1.8
	 */
	public function enqueue_assets() {
		wp_register_script( 'bp-docs-chosen', plugins_url() . '/buddypress-docs/lib/js/chosen/chosen.jquery.min.js', array( 'jquery' ) );

		$js_requirements = array(
			'jquery',
			'bp-docs-chosen',
			'jquery-ui-draggable',
			'jquery-ui-droppable',
		);

		wp_enqueue_script( 'bp-docs-folders', plugins_url() . '/buddypress-docs/includes/js/folders.js', $js_requirements );

		wp_register_style( 'bp-docs-chosen', plugins_url() . '/buddypress-docs/lib/css/chosen/chosen.min.css' );
		wp_enqueue_style( 'bp-docs-folders', plugins_url() . '/buddypress-docs/includes/css/folders.css', array( 'bp-docs-chosen' ) );
	}

	/**
	 * @since 1.8
	 */
	public function user_can( $user_can, $action, $user_id, $doc_id ) {
		$folders_caps = array(
			'create_global_folder',
			'create_personal_folder',
		);

		if ( ! in_array( $action, $folders_caps ) ) {
			return $user_can;
		}

		return $user_can;

	}
}

/** Utility functions ********************************************************/

/**
 * Concatenate a slug for bp_docs_doc_in_folder terms, based on folder ID.
 *
 * @param int $folder_id
 * @return string
 */
function bp_docs_get_folder_term_slug( $folder_id ) {
	return 'bp_docs_doc_in_folder_' . intval( $folder_id );
}

/**
 * Concatenate a slug for bp_docs_folder_in_group term, based on group ID.
 *
 * @param int $group_id
 * @return string
 */
function bp_docs_get_folder_in_group_term_slug( $group_id ) {
	return 'bp_docs_folder_in_group_' . intval( $group_id );
}

/**
 * Concatenate a slug for bp_docs_folder_in_user term, based on user ID.
 *
 * @param int $user_id
 * @return string
 */
function bp_docs_get_folder_in_user_term_slug( $user_id ) {
	return 'bp_docs_folder_in_user_' . intval( $user_id );
}

/**
 * Get the bp_docs_doc_in_folder term for a given folder.
 *
 * @param int $folder_id
 * @return int|bool $term_id False on failure, term id on success.
 */
function bp_docs_get_folder_term( $folder_id ) {
	$folder = get_post( $folder_id );

	if ( is_wp_error( $folder ) || empty( $folder ) || 'bp_docs_folder' !== $folder->post_type ) {
		return false;
	}

	$term_slug = bp_docs_get_folder_term_slug( $folder_id );

	$term_id = false;
	$term = get_term_by( 'slug', $term_slug , 'bp_docs_doc_in_folder' );

	// Doesn't exist, so we must create
	if ( empty( $term ) || is_wp_error( $term ) ) {
		$new_term = wp_insert_term( $term_slug, 'bp_docs_doc_in_folder', array(
			'slug' => $term_slug,
		) );

		if ( ! is_wp_error( $new_term ) ) {
			$term_id = intval( $new_term['term_id'] );
		}
	} else {
		$term_id = intval( $term->term_id );
	}

	return $term_id;
}

/**
 * Get a term for a folder-in-x relationship.
 *
 * @param int $item_id
 * @param string $item_type 'user' or 'group'
 * @return int $term_id
 */
function bp_docs_get_folder_in_item_term( $item_id, $item_type ) {
	switch ( $item_type ) {
		case 'group' :
			if ( ! bp_is_active( 'groups' ) ) {
				return false;
			}

			$group = groups_get_group( array(
				'group_id' => $item_id,
			) );

			if ( empty( $group->id ) ) {
				return false;
			}

			$term_slug = bp_docs_get_folder_in_group_term_slug( $group->id );
			$taxonomy = 'bp_docs_folder_in_group';

			break;

		case 'user' :
			$user = new WP_User( $item_id );

			if ( empty( $user ) || is_wp_error( $user ) || empty( $user->ID ) ) {
				return false;
			}

			$term_slug = bp_docs_get_folder_in_user_term_slug( $user->ID );
			$taxonomy = 'bp_docs_folder_in_user';

			break;
	}

	$term_id = false;
	$term = get_term_by( 'slug', $term_slug , $taxonomy );

	// Doesn't exist, so we must create
	if ( empty( $term ) || is_wp_error( $term ) ) {
		$new_term = wp_insert_term( $term_slug, $taxonomy, array(
			'slug' => $term_slug,
		) );

		if ( ! is_wp_error( $new_term ) ) {
			$term_id = intval( $new_term['term_id'] );
		}
	} else {
		$term_id = intval( $term->term_id );
	}

	return $term_id;
}

/**
 * Add a Doc to a Folder.
 *
 * @since 1.8
 *
 * @param int $doc_id
 * @param int $folder_id
 * @param bool $append Whether to append to existing folders, or replace.
 *        Default: false (replace).
 * @return bool True on success, false on failure.
 */
function bp_docs_add_doc_to_folder( $doc_id, $folder_id, $append = false ) {
	$doc = get_post( $doc_id );

	if ( is_wp_error( $doc ) || empty( $doc ) || bp_docs_get_post_type_name() !== $doc->post_type ) {
		return false;
	}

	$folder = get_post( $folder_id );

	if ( is_wp_error( $folder ) || empty( $folder ) || 'bp_docs_folder' !== $folder->post_type ) {
		return false;
	}

	$term_id = bp_docs_get_folder_term( $folder_id );

	// misc error
	if ( ! $term_id ) {
		return false;
	}

	$existing_folders = wp_get_object_terms( $doc_id, 'bp_docs_doc_in_folder' );

	// Return false if already in folder
	foreach ( $existing_folders as $existing_folder ) {
		if ( $term_id === $existing_folder->term_id ) {
			return false;
		}
	}

	// Merge new folder into old ones
	if ( $append ) {
		$term_ids = ! empty( $existing_folders ) ? wp_parse_id_list( wp_list_pluck( $existing_folders, 'term_id' ) ) : array();
		$term_ids[] = $term_id;
	} else {
		$term_ids = array( $term_id );
	}

	return (bool) wp_set_object_terms( $doc_id, $term_ids, 'bp_docs_doc_in_folder' );
}

/**
 * Create a Folder.
 *
 * @since 1.8
 *
 * @param array $args {
 *     Array of parameters.
 *     @type string $name Name of the folder, for display in the interface.
 *     @type int $user_id Optional. ID of the user that the folder is limited
 *           to.
 *     @type int $group_id Optional. ID of the group that the folder is
 *           limited to.
 * }
 * @return int|bool ID of the newly created folder on success, false on failure.
 */
function bp_docs_create_folder( $args ) {
	$r = wp_parse_args( $args, array(
		'folder_id' => null,
		'name' => '',
		'parent' => null,
		'group_id' => null,
		'user_id' => null,
	) );

	if ( empty( $r['name'] ) ) {
		return false;
	}

	// Validate group ID
	if ( ! empty( $r['group_id'] ) ) {
		if ( ! bp_is_active( 'groups' ) ) {
			return false;
		}

		$r['group_id'] = intval( $r['group_id'] );

		$group = groups_get_group( array(
			'group_id' => $r['group_id'],
		) );

		if ( empty( $group->id ) ) {
			return false;
		}
	}

	// Validate post parent
	if ( ! empty( $r['parent'] ) ) {
		$r['parent'] = intval( $r['parent'] );

		$maybe_parent = get_post( $r['parent'] );
		if ( ! is_a( $maybe_parent, 'WP_Post' ) ) {
			return false;
		}
	}

	$post_args = array(
		'post_type'   => 'bp_docs_folder',
		'post_title'  => $r['name'],
		'post_status' => 'publish',
		'post_parent' => $r['parent'],
	);

	if ( ! empty( $r['folder_id'] ) ) {
		$post_args['ID'] = intval( $r['folder_id'] );
	}

	$folder_id = wp_insert_post( $post_args );

	// If a group ID was passed, associate with group
	if ( ! empty( $r['group_id'] ) ) {
		$group_term = bp_docs_get_folder_in_item_term( $r['group_id'], 'group' );
		wp_set_object_terms( $folder_id, $group_term, 'bp_docs_folder_in_group' );
	} else if ( ! empty( $r['user_id'] ) ) {
		$user_term = bp_docs_get_folder_in_item_term( $r['user_id'], 'user' );
		wp_set_object_terms( $folder_id, $user_term, 'bp_docs_folder_in_user' );
	}

	if ( is_wp_error( $folder_id ) ) {
		return false;
	} else {
		return intval( $folder_id );
	}
}

/**
 * Get a list of folders.
 *
 * @since 1.8
 *
 * @param array $args {
 *     List of arguments.
 *     @type int $group_id Optional. ID of the group.
 *     @type int $user_id Optional. ID of the user.
 *     @type string $display Optional. Format of return value. 'tree' to
 *           display as hierarchical tree, 'flat' to return flat list. Default:
 *           'tree'.
 *     @type bool $force_all_folders Optional. Set to 'true' to include all
 *           folders, 'false' to exclude folders associated with users or
 *           groups (ie, include only "global" folders). Default: false.
 * }
 * @return array
 */
function bp_docs_get_folders( $args = array() ) {
	$group_id = null;
	if ( bp_is_active( 'groups' ) && bp_is_group() ) {
		$group_id = bp_get_current_group_id();
	}

	$user_id = null;
	if ( bp_is_user() ) {
		$user_id = bp_displayed_user_id();
	}

	$r = wp_parse_args( $args, array(
		'group_id' => $group_id,
		'user_id' => $user_id,
		'display' => 'tree',
		'force_all_folders' => false,
	) );

	$post_args = array(
		'post_type' => 'bp_docs_folder',
		'orderby' => 'title',
		'order' => 'ASC',
		'posts_per_page' => '-1',
		'tax_query' => array(),
	);

	// @todo support for multiple items
	if ( ! empty( $r['group_id'] ) ) {
		$post_args['tax_query'][] = array(
			'taxonomy' => 'bp_docs_folder_in_group',
			'terms' => array( bp_docs_get_folder_in_group_term_slug( $r['group_id'] ) ),
			'field' => 'slug',
		);
	} else if ( ! empty( $r['user_id'] ) ) {
		$post_args['tax_query'][] = array(
			'taxonomy' => 'bp_docs_folder_in_user',
			'terms' => array( bp_docs_get_folder_in_user_term_slug( $r['user_id'] ) ),
			'field' => 'slug',
		);

	// Must exclude all user and group folders
	// @todo Find better way to do this
	} else if ( empty( $r['force_all_folders'] ) ) {
		$object_folders = get_terms(
			array(
				'bp_docs_folder_in_group',
				'bp_docs_folder_in_user',
			),
			array(
				'hide_empty' => false,
				'fields' => 'ids',
			)
		);

		if ( empty( $object_folders ) ) {
			$object_folders = array( 0 );
		}

		$post_args['tax_query'][] = array(
			'taxonomy' => 'bp_docs_folder_in_group',
			'terms' => wp_parse_id_list( $object_folders ),
			'field' => 'term_id',
			'operator' => 'NOT IN',
		);

		$post_args['tax_query'][] = array(
			'taxonomy' => 'bp_docs_folder_in_user',
			'terms' => wp_parse_id_list( $object_folders ),
			'field' => 'term_id',
			'operator' => 'NOT IN',
		);
	}

	$folders = get_posts( $post_args );

	// Poor-man's walker
	if ( 'tree' === $r['display'] ) {
		$ref = $tree = array();

		// Populate top-level items first
		foreach ( $folders as $folder_index => $folder ) {
			if ( empty( $folder->post_parent ) ) {
				$folder->children = array();

				// Place in the top level of the tree
				$tree[ $folder->ID ] = $folder;

				// Set the reference index
				$ref[ $folder->ID ] =& $tree[ $folder->ID ];

				// Remove from original folder index
				unset( $folders[ $folder_index ] );
			}
		}

		while ( ! empty( $folders ) ) {
			foreach ( $folders as $folder_index => $folder ) {
				// Nested
				if ( ! empty( $folder->post_parent ) && isset( $ref[ $folder->post_parent ] ) ) {
					$folder->children = array();

					// Place in the tree
					$children = $ref[ $folder->post_parent ]->children;
					$children[ $folder->ID ] = $folder;
					$ref[ $folder->post_parent ]->children = $children;

					// Set the reference index
					$ref[ $folder->ID ] =& $ref[ $folder->post_parent ]->children[ $folder->ID ];

					// Remove from original folders array
					unset( $folders[ $folder_index ] );
				}
			}
		}

		$folders = $tree;
	}

	return $folders;
}

/** "Action" functions *******************************************************/

/**
 * Save folder selections on Doc save.
 *
 * @since 1.8
 *
 * @param int $doc_id
 * @return bool True on success, false on failure.
 */
function bp_docs_save_folder_selection( $doc_id ) {
	$retval = false;

	if ( empty( $_POST['existing-or-new-folder'] ) ) {
		return;
	}

	switch ( $_POST['existing-or-new-folder'] ) {
		case 'existing' :
			if ( isset( $_POST['bp-docs-folder'] ) ) {
				$folder_id = intval( $_POST['bp-docs-folder'] );
				$retval = bp_docs_add_doc_to_folder( $doc_id, $folder_id );
			}

			break;

		case 'new' :
			$folder_name = trim( stripslashes( $_POST['new-folder'] ) );

			if ( empty( $folder_name ) ) {
				bp_core_add_message( __( 'You must provide a folder name.', 'bp-docs' ), 'error' );
			} else {
				$folder_args = array(
					'name' => $folder_name,
				);

				// Parent
				$folder_args['parent'] = ! empty( $_POST['new-folder-parent'] ) ? intval( $_POST['new-folder-parent'] ) : null;

				// Type
				$folder_type = stripslashes( $_POST['new-folder-type'] );

				if ( 'global' === $folder_type ) {
					// Nothing to do
				} else if ( 'me' === $folder_type ) {
					$folder_args['user_id'] = bp_loggedin_user_id();
				} else if ( is_numeric( $folder_type ) ) {
					// This is a group
					$folder_args['group_id'] = intval( $folder_type );
				}

				// Create the folder
				$folder_id = bp_docs_create_folder( $folder_args );

				// Add the doc to the folder
				$retval = bp_docs_add_doc_to_folder( $doc_id, $folder_id );
			}

			break;
	}

	return $retval;
}
add_action( 'bp_docs_after_save', 'bp_docs_save_folder_selection' );

/** AJAX Handlers ************************************************************/

/**
 * Update folders based on group selections.
 *
 * @since 1.8
 */
function bp_docs_update_folders_cb() {
	if ( ! isset( $_POST['doc_id'] ) || ! isset( $_POST['group_id'] ) ) {
		die( '-1' );
	}

	$doc_id = intval( $_POST['doc_id'] );
	$group_id = intval( $_POST['group_id'] );

	bp_docs_folder_selector( array(
		'group_id' => $group_id,
		'doc_id' => $doc_id,
	) );

	die();

}
add_action( 'wp_ajax_bp_docs_update_folders', 'bp_docs_update_folders_cb' );

/**
 * Update parent folder selector based on folder type.
 *
 * @since 1.8
 */
function bp_docs_update_parent_folders_cb() {
	if ( ! isset( $_POST['folder_type'] ) ) {
		die( '-1' );
	}

	$folder_type = stripslashes( $_POST['folder_type'] );

	$selector_args = array(
		'id' => 'new-folder-parent',
		'name' => 'new-folder-parent',
	);

	if ( 'global' === $folder_type ) {
		// Nothing to do
	} else if ( 'me' === $folder_type ) {
		$selector_args['user_id'] = bp_loggedin_user_id();
	} else if ( is_numeric( $folder_type ) ) {
		// This is a group
		$selector_args['group_id'] = intval( $folder_type );
	}

	bp_docs_folder_selector( $selector_args );

	die();

}
add_action( 'wp_ajax_bp_docs_update_parent_folders', 'bp_docs_update_parent_folders_cb' );

/**
 * Process folder drops.
 *
 * @since 1.8
 */
function bp_docs_process_folder_drop_cb() {
	if ( empty( $_POST['doc_id'] ) ) {
		die( '-1' );
	}

	$doc_id = intval( $_POST['doc_id'] );

	$nonce = isset( $_POST['nonce'] ) ? stripslashes( $_POST['nonce'] ) : '';

	if ( ! wp_verify_nonce( $nonce, 'bp-docs-folder-drop-' . $doc_id ) ) {
		die( '-1' );
	}

	// @todo This needs testing with group admins, etc
	if ( ! bp_docs_user_can( 'manage', bp_loggedin_user_id(), $doc_id ) ) {
		die( '-1' );
	}

	$folder_id = isset( $_POST['folder_id'] ) ? intval( $_POST['folder_id'] ) : 0;

	// @todo Need to do permission tests for these folders
	if ( empty( $folder_id ) ) {
		die( '-1' );
	}

	$success = bp_docs_add_doc_to_folder( $doc_id, $folder_id );

	if ( $success ) {
		die( '1' );
	} else {
		die( '-1' );
	}
}
add_action( 'wp_ajax_bp_docs_process_folder_drop', 'bp_docs_process_folder_drop_cb' );

/**
 * Process folder edits.
 *
 * (not an AJAX callback, but close enough)
 */
function bp_docs_process_folder_edit_cb() {
	if ( ! bp_docs_is_docs_component() && ! bp_is_current_action( 'docs' ) ) {
		return;
	}

	if ( ! bp_docs_is_folder_manage_view() ) {
		return;
	}

	if ( empty( $_POST['folder-id'] ) ) {
		return;
	}

	$folder_id = intval( $_POST['folder-id'] );

	$nonce = isset( $_POST['bp-docs-edit-folder-nonce-' . $folder_id] ) ? stripslashes( $_POST['bp-docs-edit-folder-nonce-' . $folder_id] ) : '';

	$redirect_url = bp_get_requested_url();

	if ( ! wp_verify_nonce( $nonce, 'bp-docs-edit-folder-' . $folder_id ) ) {
		bp_core_add_message( __( 'There was a problem editing that folder. Please try again.', 'bp-docs' ), 'error' );
		bp_core_redirect( $redirect_url );
		die();
	}

	$parent = isset( $_POST['folder-parent-' . $folder_id] ) ? intval( $_POST['folder-parent-' . $folder_id] ) : '';

	$edit_args = array(
		'folder_id' => $folder_id,
		'name'      => stripslashes( $_POST['folder-name-' . $folder_id] ),
	);

	if ( ! empty( $parent ) ) {
		$edit_args['parent'] = $parent;
	}

	// @todo permissions checks!!
	$success = bp_docs_create_folder( $edit_args );

	if ( ! empty( $success ) && ! is_wp_error( $success ) ) {
		bp_core_add_message( __( 'Folder successfully updated.', 'bp-docs' ), 'success' );
	} else {
		bp_core_add_message( __( 'There was a problem editing that folder. Please try again.', 'bp-docs' ), 'error' );
	}

	bp_core_redirect( $redirect_url );
	die();
}
add_action( 'bp_actions', 'bp_docs_process_folder_edit_cb' );

/** Template functions *******************************************************/

/**
 * Is this 'tree' view?
 *
 * @since 1.8
 *
 * @return bool
 */
function bp_docs_is_folder_tree_view() {
	if ( isset( $_GET['view'] ) && 'tree' === $_GET['view'] ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Is this 'manage' view?
 *
 * @since 1.8
 *
 * @return bool
 */
function bp_docs_is_folder_manage_view() {
	if ( isset( $_GET['view'] ) && 'manage' === $_GET['view'] ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Fetch <select> box for folder selection.
 *
 * @since 1.8
 *
 * @param array $args {
 *     Array of optional arguments.
 *     @type int $group_id Include folders of a given group.
 *     @type int $user_id Include folders of a given user.
 *     @type int $selected ID of the selected folder.
 * }
 * @return string
 */
function bp_docs_folder_selector( $args = array() ) {
	$r = wp_parse_args( $args, array(
		'name' => 'bp-docs-folder',
		'id' => 'bp-docs-folder',
		'group_id' => null,
		'user_id' => null,
		'selected' => null,
		'doc_id' => null,
		'echo' => true,
	) );

	// If no manual 'selected' value is passed, try to infer it from the
	// current context
	if ( is_null( $r['selected'] ) ) {
		if ( ! is_null( $r['doc_id'] ) ) {
			$doc_id = intval( $r['doc_id'] );
		} else {
			$maybe_doc = get_queried_object();
			if ( isset( $maybe_doc->post_type ) && bp_docs_get_post_type_name() === $maybe_doc->post_type ) {
				$doc_id = $maybe_doc->ID;
			}
		}

		if ( ! empty( $doc_id ) ) {
			$maybe_folders = wp_get_object_terms( $doc_id, array(
				'bp_docs_doc_in_folder',
			) );

			// Take the first one
			if ( ! empty( $maybe_folders ) ) {
				$r['selected'] = substr( $maybe_folders[0]->slug, 22 );
			}
		}
	}

	// @todo Do we *always* want the global folders?
	$types = array(
		'global' => array(
			'label' => __( 'Global', 'bp-docs' ),
			'folders' => bp_docs_get_folders( array(
				'display' => 'flat',
			) ),
		),
	);

	if ( ! empty( $r['group_id'] ) ) {
		$group = groups_get_group( array(
			'group_id' => $r['group_id'],
		) );

		if ( ! empty( $group->name ) ) {
			$types['group'] = array(
				'label' => $group->name,
				'folders' => bp_docs_get_folders( array(
					'display' => 'flat',
					'group_id' => $r['group_id'],
				) ),
			);
		}
	}

	if ( ! empty( $r['user_id'] ) ) {
		$user = new WP_User( $r['user_id'] );

		if ( ! empty( $user->ID ) ) {
			$label = $r['user_id'] === bp_loggedin_user_id() ? __( 'My Folders', 'bp-docs' ) : bp_core_get_user_displayname( $r['user_id'] );
			$types['user'] = array(
				'label' => $label,
				'folders' => bp_docs_get_folders( array(
					'display' => 'flat',
					'user_id' => $r['user_id'],
				) ),
			);
		}
	}

	$walker = new BP_Docs_Folder_Dropdown_Walker();

	// Global only
	if ( 1 === count( $types ) ) {
		$options = $walker->walk( $types['global']['folders'], 10, array( 'selected' => $r['selected'] ) );

	// If there is more than one folder type (global + user or group),
	// organize into <optgroup>
	} else {
		$options = '';
		foreach ( $types as $type ) {
			if ( empty( $type['folders'] ) ) {
				continue;
			}

			$options .= sprintf( '<optgroup label="%s">', esc_attr( $type['label'] ) );
			$options .= $walker->walk( $type['folders'], 10, array( 'selected' => $r['selected'] ) );
			$options .= '</optgroup>';
		}
	}

	$options = '<option value="">' . __( ' - Select a folder - ', 'bp-docs' ) . '</option>' . $options;
	$retval = sprintf( '<select name="%s" id="%s" class="chosen-select">', esc_attr( $r['name'] ), esc_attr( $r['id'] ) ) . $options . '</select>';

	if ( false === $r['echo'] ) {
		return $retval;
	} else {
		echo $retval;
	}
}

/**
 * Add the meta box to the edit page.
 *
 * @since 1.8
 */
function bp_docs_folders_meta_box() {

	$associated_group_id = bp_docs_get_associated_group_id( get_the_ID() );

	?>

	<div id="doc-folders" class="doc-meta-box">
		<div class="toggleable">
			<p id="folders-toggle-edit" class="toggle-switch"><?php _e( 'Folders', 'bp-docs' ) ?></p>

			<div class="toggle-content">
				<table class="toggle-table" id="toggle-table-tags">
					<tr>
						<td class="desc-column">
							<label for="bp_docs_tag"><?php _e( 'Select a folder for this Doc.', 'bp-docs' ) ?></label>
						</td>

						<td>
							<div class="existing-or-new-selector">
								<input type="radio" name="existing-or-new-folder" id="use-existing-folder" value="existing" checked="checked" />
								<label for="use-existing-folder" class="radio-label"><?php _e( 'Use an existing folder', 'bp-docs' ) ?></label><br />
								<div class="selector-content">
									<?php bp_docs_folder_selector( array(
										'name' => 'bp-docs-folder',
										'id' => 'bp-docs-folder',
										'group_id' => $associated_group_id,
									) ) ?>
								</div>
							</div>

							<div class="existing-or-new-selector" id="new-folder-block">
								<input type="radio" name="existing-or-new-folder" id="create-new-folder" value="new" />
								<label for="create-new-folder" class="radio-label"><?php _e( 'Create a new folder', 'bp-docs' ) ?></label>
								<div class="selector-content">

									<label for="new-folder"><?php _e( 'Name:', 'bp-docs' ) ?></label> <input name="new-folder" id="new-folder" />

									<label for="new-folder-type"><?php _e( 'Folder type:' ) ?></label>
									<select name="new-folder-type" id="new-folder-type">
										<?php if ( bp_docs_current_user_can( 'create_global_folder' ) ) : ?>
											<option value="global" selected="selected"><?php _e( 'Global', 'bp-docs' ) ?></option>
										<?php endif ?>

										<?php if ( bp_docs_current_user_can( 'create_personal_folder' ) ) : ?>
											<option value="me"><?php _e( 'Limited to me', 'bp-docs' ) ?></option>
										<?php endif ?>

										<optgroup label="<?php esc_attr_e( 'Group-specific', 'bp-docs' ) ?>">
										<?php bp_docs_associated_group_dropdown( array(
											'options_only' => true,
										) ) ?>

										</optgroup>

									</select>

									<label for="new-folder-parent"><?php _e( 'Parent (optional):', 'bp-docs' ) ?></label>
									<?php bp_docs_folder_selector( array(
										'name' => 'new-folder-parent',
										'id' => 'new-folder-parent',
										'selected' => false,
									) ) ?>
								</div><!-- .selector-content -->
							</div>
						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>

	<?php
}
add_action( 'bp_docs_before_tags_meta_box', 'bp_docs_folders_meta_box' );

/**
 * Create dropdown <option> values for BP Docs Folders.
 *
 * @since 1.8
 * @uses Walker
 */
class BP_Docs_Folder_Dropdown_Walker extends Walker {
	/**
	 * @see Walker::$tree_type
	 * @since 1.8
	 * @var string
	 */
	var $tree_type = 'bp_docs_folder';

	/**
	 * @see Walker::$db_fields
	 * @since 1.8
	 * @var array
	 */
	var $db_fields = array( 'parent' => 'post_parent', 'id' => 'ID' );

	/**
	 * @see Walker::start_el()
	 * @since 1.8
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $page Page data object.
	 * @param int $depth Depth of page. Used for padding.
	 * @param int $current_page Page ID.
	 * @param array $args
	 */
	function start_el( &$output, $page, $depth = 0, $args = array(), $current_page = 0 ) {
		$pad = str_repeat('&nbsp;', $depth * 3);

		$output .= "\t<option class=\"level-$depth\" value=\"$page->ID\"";
		if ( $page->ID == $args['selected'] )
			$output .= ' selected="selected"';
		$output .= '>';

		$title = $page->post_title . '&nbsp;';
		if ( '' === $title ) {
			$title = sprintf( __( '#%d (no title)' ), $page->ID );
		}

		$output .= $pad . esc_html( $title );
		$output .= "</option>\n";
	}
}

/**
 * Get a folder tree for the Manage screen.
 *
 * @since 1.8
 * @uses Walker
 */
class BP_Docs_Folder_Manage_Walker extends Walker {
	/**
	 * @see Walker::$tree_type
	 * @since 1.8
	 * @var string
	 */
	var $tree_type = 'bp_docs_folder';

	/**
	 * @see Walker::$db_fields
	 * @since 1.8
	 * @var array
	 */
	var $db_fields = array( 'parent' => 'post_parent', 'id' => 'ID' );

	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat( "\t", $depth );
		$output .= "\n$indent<ul class='children'>\n";
	}

	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}

	/**
	 * @see Walker::start_el()
	 * @since 1.8
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $page Page data object.
	 * @param int $depth Depth of page. Used for padding.
	 * @param int $current_page Page ID.
	 * @param array $args
	 */
	public function start_el( &$output, $page, $depth = 0, $args = array(), $current_page = 0 ) {
		$folder_group_terms = wp_get_object_terms( $page->ID, 'bp_docs_folder_in_group' );
		$group_id = null;
		if ( ! empty( $folder_group_terms ) ) {
			$group_id = intval( substr( $folder_group_terms[0]->slug, 24 ) );
		}

		$folder_user_terms = wp_get_object_terms( $page->ID, 'bp_docs_folder_in_user' );
		$user_id = null;
		if ( ! empty( $folder_user_terms ) ) {
			$user_id = intval( substr( $folder_user_terms[0]->slug, 23 ) );
		}

		$parent_selector = bp_docs_folder_selector( array(
			'name'     => 'folder-parent-' . $page->ID,
			'id'       => 'folder-parent-' . $page->ID,
			'selected' => $page->post_parent,
			'group_id' => $group_id,
			'echo'     => false,
		) );

		$group_selector = bp_docs_associated_group_dropdown( array(
			'options_only' => true,
			'selected'     => $group_id,
			'echo'         => false,
		) );

		// @todo break into separate template function
		$type_selector  = '<select name="folder-type-' . $page->ID . '" id="folder-type-' . $page->ID . '">';
		$type_is_global = empty( $user_id ) && empty( $group_id );
		$type_selector .=   '<option ' . selected( $type_is_global, true, false ) . ' value="global">' . __( 'Global', 'bp-docs' ) . '</option>';
		$type_selector .=   '<option ' . selected( $page->ID, $user_id, false ) . ' value="me">' . __( 'Limited to me', 'bp-docs' ) . '</option>';

		$type_selector .=   '<optgroup label="' . __( 'Group-specific', 'bp-docs' ) . '">';
		$type_selector .=     $group_selector;
		$type_selector .=   '</optgroup>';
		$type_selector .= '</select>';


		$output .= sprintf(
			'
<li class="folder" data-folder-id="%d">
	<div class="folder-info">
		<h4>%s</h4>
		<div class="folder-details folder-details-closed">
			<form method="post" action="">
				<label for="folder-name-%d">%s</label> <input id="folder-name-%d" name="folder-name-%d" value="%s" />
				<div style="clear:both;"></div>
				<label for="folder-parent-%d">%s</label> %s
				<div style="clear:both;"></div>
				<label for="folder-type-%d">%s</label> %s
				<div style="clear:both;"></div>
				<input type="hidden" class="folder-id" name="folder-id" value="%d" />
				%s
				<input type="submit" value="%s" class="primary-button" />
			</form>
		</div>
	</div>',
			intval( $page->ID ), // data-folder-id
			esc_html( $page->post_title ), // h4
			intval( $page->ID ), // for="folder-name-%d"
			__( 'Name', 'bp-docs' ), // label text
			intval( $page->ID ), // id="folder-name-%d"
			intval( $page->ID ), // name="folder-name-%d"
			esc_attr( $page->post_title ), // input value
			intval( $page->ID ), // for="folder-parent-%d"
			__( 'Parent', 'bp-docs' ), // label text
			$parent_selector, // parent dropdown
			intval( $page->ID ), // for="folder-type-%d"
			__( 'Type', 'bp-docs' ), // type label text
			$type_selector, // type dropdown
			intval( $page->ID ), // hidden input value
			wp_nonce_field( 'bp-docs-edit-folder-' . $page->ID, 'bp-docs-edit-folder-nonce-' . $page->ID, false, false ), // nonce
			__( 'Save Changes', 'bp-docs' )
		);
	}

	public function end_el( &$output, $page, $depth = 0, $args = array(), $current_page = 0 ) {
		$output .= '</li>';
		return;
		// Get the docs belonging to this folder
		$folder_term = bp_docs_get_folder_term( $page->ID );

		$folder_docs = get_posts( array(
			'post_type' => bp_docs_get_post_type_name(),
			'tax_query' => array(
				array(
					'taxonomy' => 'bp_docs_doc_in_folder',
					'field' => 'term_id',
					'terms' => $folder_term,
				),
			),
		) );

		if ( ! empty( $folder_docs ) ) {
			$output .= sprintf( '<ul class="docs-in-folder" id="docs-in-folder-%d">', $page->ID );

			foreach ( $folder_docs as $folder_doc ) {
				$output .= sprintf(
					'<li class="doc-in-folder" id="doc-in-folder-%d" data-doc-id="%d"><i class="genericon genericon-document"></i><a href="%s">%s</a>%s</li>',
					$folder_doc->ID,
					$folder_doc->ID,
					get_permalink( $folder_doc ),
					esc_html( $folder_doc->post_title ),
					wp_nonce_field( 'bp-docs-folder-drop-' . $folder_doc->ID, 'bp-docs-folder-drop-nonce-' . $folder_doc->ID, false, false )
				);
			}

			$output .= '</ul>';
		}

		$output .= '</li>';
	}
}
