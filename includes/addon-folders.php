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
			'public' => false,
		) );

		if ( bp_is_active( 'groups' ) ) {
			register_taxonomy( 'bp_docs_folder_in_group', 'bp_docs_folder', array(
				'public' => false,
			) );
		}
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
 * @return bool True on success, false on failure.
 */
function bp_docs_add_doc_to_folder( $doc_id, $folder_id ) {
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
	$term_ids = ! empty( $existing_folders ) ? wp_parse_id_list( wp_list_pluck( $existing_folders, 'term_id' ) ) : array();
	$term_ids[] = $term_id;

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
		'name' => '',
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

	$post_args = array(
		'post_type'   => 'bp_docs_folder',
		'post_title'  => $r['name'],
		'post_status' => 'publish',
	);

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
