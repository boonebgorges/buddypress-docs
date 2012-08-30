<?php

/**
 * Miscellaneous utility functions
 *
 * @package BuddyPress_Docs
 * @since 1.2
 */


/**
 * Return the bp_doc post type name
 *
 * @package BuddyPress_Docs
 * @since 1.2
 *
 * @return str The name of the bp_doc post type
 */
function bp_docs_get_post_type_name() {
	global $bp;

	return $bp->bp_docs->post_type_name;
}

/**
 * Return the associated_item taxonomy name
 *
 * @package BuddyPress_Docs
 * @since 1.2
 */
function bp_docs_get_associated_item_tax_name() {
	global $bp;

	return $bp->bp_docs->associated_item_tax_name;
}

/**
 * Utility function to get and cache the current doc
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 *
 * @return obj Current doc
 */
function bp_docs_get_current_doc() {
	global $bp, $post;

	if ( empty( $bp->bp_docs->doc_slug ) )
		return false;

	$doc = false;

	if ( empty( $bp->bp_docs->current_post ) ) {

		if ( bp_docs_has_docs( array( 'doc_slug' => $bp->bp_docs->doc_slug ) ) ) {
			while ( bp_docs_has_docs() ) {
				bp_docs_the_doc();
				$doc = $bp->bp_docs->current_post = $post;
				break;
			}
		}

	} else {
		$doc = $bp->bp_docs->current_post;
	}

	return $doc;
}


/**
 * Get an item_id based on a taxonomy term slug
 *
 * BuddyPress Docs are associated with groups and users through a taxonomy called
 * bp_docs_associated_item. Terms belonging to this taxonomy have slugs that look like this
 * (since 1.2):
 *   4-user
 *   103-group
 * (where 4-user corresponds to the user with the ID 4, and 103 is the group with group_id 103).
 * If you have a term slug, you can use this function to parse the item id out of it. Note that
 * it will return 0 if you pass a slug that belongs to a different item type.
 *
 * @since 1.2
 * @param str $term_slug The 'slug' property of a WP term object
 * @param str $item_type 'user', 'group', or your custom item type
 * @return mixed Returns false if you don't pass in the proper parameters.
 *		 Returns 0 if you pass a slug that does not correspond to your item_type
 *	         Returns an int (the unique item_id) if successful
 */
function bp_docs_get_associated_item_id_from_term_slug( $term_slug = '', $item_type = '' ) {
	if ( !$term_slug || !$item_type ) {
		return false;
	}

	$item_id = 0;

	// The item_type should be hidden in the slug
	$slug_array = explode( '-', $term_slug );

	if ( isset( $slug_array[1] ) && $item_type == $slug_array[1] ) {
		$item_id = $slug_array[0];
	}

	return apply_filters( 'bp_docs_get_associated_item_id_from_term_slug', $item_id, $term_slug, $item_type );
}

/**
 * Get the term_id for the associated_item term corresponding to a item_id
 *
 * Will create it if it's not found
 *
 * @since 1.2
 *
 * @param int $item_id Such as the group_id or user_id
 * @param str $item_type Such as 'user' or 'group' (slug of the parent term)
 * @param str $item_name Optional. This is the value that will be used to describe the term in the
 *    Dashboard.
 * @return int $item_term_id
 */
function bp_docs_get_item_term_id( $item_id, $item_type, $item_name = '' ) {
	global $bp;

	if ( empty( $item_id ) ) {
		return;
	}

	$item_term = term_exists( $item_id . '-' . $item_type, $bp->bp_docs->associated_item_tax_name );

	// If the item term doesn't exist, then create it
	if ( empty( $item_term ) ) {
		// Set up the arguments for creating the term. Filter this to set your own
		$item_term_args = apply_filters( 'bp_docs_item_term_values', array(
			'description' => $item_name,
			'slug'        => $item_id . '-' . $item_type
		) );

		// Create the item term
		if ( !$item_term = wp_insert_term( $item_id, $bp->bp_docs->associated_item_tax_name, $item_term_args ) )
			return false;
	}

	return apply_filters( 'bp_docs_get_item_term_id', $item_term['term_id'], $item_id, $item_type, $item_name );
}

/**
 * Get the absolute path of a given template.
 *
 * Looks first for a template in [theme-dir]/docs/, and falls back on the provided templates.
 *
 * Ideally, I would not need this function. But WP's locate_template() plays funny with directory
 * paths, and bp_core_load_template() does not have an option that will let you locate but not load
 * the found template.
 *
 * @package BuddyPress Docs
 * @since 1.0.5
 *
 * @param str $template This string should be of the format 'edit-docs.php'. Ie, you need '.php',
 *                      but you don't need the leading '/docs/'
 * @return str $template_path The absolute path of the located template file.
 */
function bp_docs_locate_template( $template = '', $load = false, $require_once = true ) {
	if ( empty( $template ) )
		return false;

	// Try to load custom templates first
	$stylesheet_path = STYLESHEETPATH . '/docs/';
	$template_path   = TEMPLATEPATH . '/docs/';

	if ( file_exists( $stylesheet_path . $template ) )
		$template_path = $stylesheet_path . $template;
	elseif ( file_exists( $template_path . $template ) )
		$template_path = $template_path . $template;
	else
		$template_path = BP_DOCS_INCLUDES_PATH . 'templates/docs/' . $template;

	$template_path = apply_filters( 'bp_docs_locate_template', $template_path, $template );

	if ( $load ) {
		load_template( $template_path, $require_once );
	} else {
		return $template_path;
	}
}

/**
 * Determine whether the current user can do something the current doc
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 *
 * @param str $action The cap being tested
 * @return bool $user_can
 */
function bp_docs_current_user_can( $action = 'edit' ) {
	global $bp;

	// Check to see whether the value has been cached in the global
	if ( isset( $bp->bp_docs->current_user_can[$action] ) ) {
		$user_can = 'yes' == $bp->bp_docs->current_user_can[$action] ? true : false;
	} else {
		$user_can = bp_docs_user_can( $action, bp_loggedin_user_id() );
	}

	// Stash in the $bp global to reduce future lookups
	$bp->bp_docs->current_user_can[$action] = $user_can ? 'yes' : 'no';

	return apply_filters( 'bp_docs_current_user_can', $user_can, $action );
}

/**
 * Determine whether a given user can do something with a given doc
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 *
 * @param str $action Optional. The action being queried. Eg 'edit', 'read_comments', 'manage'
 * @param int $user_id Optional. Unique user id for the user being tested. Defaults to logged-in ID
 * @param int $doc_id Optional. Unique doc id. Defaults to doc currently being viewed
 */
function bp_docs_user_can( $action = 'edit', $user_id = false, $doc_id = false ) {
	global $bp, $post;

	if ( !$user_id )
		$user_id = bp_loggedin_user_id();

	// Only certain actions are checked against doc_ids
	$need_doc_ids_actions = apply_filters( 'bp_docs_need_doc_ids_actions', array( 'edit', 'manage', 'view_history', 'read' ) );

	$doc_id = false;

	if ( in_array( $action, $need_doc_ids_actions ) ) {
		if ( !$doc_id ) {
			if ( !empty( $post->ID ) ) {
				$doc_id = $post->ID;
			} else {
				$doc = bp_docs_get_current_doc();
				if ( isset( $doc->ID ) ) {
					$doc_id = $doc->ID;
				}
			}
		}
	}

	$user_can = false;

	if ( $user_id ) {
		if ( is_super_admin() ) {
			// Super admin always gets to edit. What a big shot
			$user_can = true;
		} else {
			// Filter this so that groups-integration and other plugins can give their
			// own rules. Done inside the conditional so that plugins don't have to
			// worry about the is_super_admin() check
			$user_can = apply_filters( 'bp_docs_user_can', $user_can, $action, $user_id, $doc_id );
		}
	}

	return $user_can;
}

/**
 * Update the Doc count for a given item
 *
 * @since 1.2
 */
function bp_docs_update_doc_count( $item_id = 0, $item_type = '' ) {
	global $bp;

	$doc_count = 0;
	$docs_args = array( 'doc_slug' => '' );

	switch ( $item_type ) {
		case 'group' :
			$docs_args['user_id']  = '';
			$docs_args['group_id'] = $item_id;
			break;

		case 'user' :
			$docs_args['user_id']  = $item_id;
			$docs_args['group_id'] = '';
			break;

		default :
			$docs_args['user_id']  = '';
			$docs_args['group_id'] = '';
			break;
	}

	$query = new BP_Docs_Query( $docs_args );
	$query->get_wp_query();
	if ( $query->query->have_posts() ) {
		$doc_count = $query->query->found_posts;
	}

	// BP has a stupid bug that makes it delete groupmeta when it equals 0. We'll save
	// a string instead of zero to work around this
	if ( !$doc_count )
		$doc_count = '0';

	// Save the count
	switch ( $item_type ) {
		case 'group' :
			groups_update_groupmeta( $item_id, 'bp-docs-count', $doc_count );
			break;

		case 'user' :
			update_user_meta( $item_id, 'bp_docs_count', $doc_count );
			break;

		default :
			bp_update_option( 'bp_docs_count', $doc_count );
			break;
	}

	return $doc_count;
}

/**
 * Is this the BP Docs component?
 */
function bp_docs_is_docs_component() {
	$retval = false;

	$p = get_queried_object();

	if ( is_post_type_archive( bp_docs_get_post_type_name() ) ) {
		$retval = true;
	} else if ( isset( $p->post_type ) && bp_docs_get_post_type_name() == $p->post_type ) {
		$retval = true;
	}

	return $retval;
}

/**
 * Get the Doc settings array
 *
 * This will prepopulate many of the required settings, for cases where the settings have not
 * yet been saved for this Doc.
 *
 * @param int $doc_id
 * @return array
 */
function bp_docs_get_doc_settings( $doc_id = 0 ) {
	$doc_settings = array();

	$q = get_queried_object();
	if ( !$doc_id && isset( $q->ID ) ) {
		$doc_id = $q->ID;
	}

	$saved_settings = get_post_meta( $doc_id, 'bp_docs_settings', true );
	if ( !is_array( $saved_settings ) ) {
		$saved_settings = array();
	}


	$default_settings = array(
		'read'          => 'anyone',
		'edit'          => 'loggedin',
		'read_comments' => 'anyone',
		'post_comments' => 'anyone',
		'view_history'  => 'anyone'
	);

	$doc_settings = wp_parse_args( $saved_settings, $default_settings );

	return apply_filters( 'bp_docs_get_doc_settings', $doc_settings, $doc_id, $default_settings );
}

function bp_docs_define_tiny_mce() {
	BP_Docs_Query::define_wp_tiny_mce();
}

?>
