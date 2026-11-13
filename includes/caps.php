<?php

/**
 * BuddyPress Docs capabilities and roles
 *
 * Inspired by bbPress 2.0
 *
 * @package BuddyPress_Docs
 * @subpackage Caps
 * @since 1.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Map our caps to WP's
 *
 * @since 1.2
 *
 * @param array $caps Capabilities for meta capability
 * @param string $cap Capability name
 * @param int $user_id User id
 * @param mixed $args Arguments passed to map_meta_cap filter
 * @return array Actual capabilities for meta capability
 */
function bp_docs_map_meta_caps( $caps, $cap, $user_id, $args ) {
	global $post, $wp_post_types;

	// No need to continue if BuddyPress Docs hasn't been initialized
	$pt = bp_docs_get_post_type_name();
	if ( empty( $pt ) ) {
		return $caps;
	}

	switch ( $cap ) {
		case 'bp_docs_create' :
			// Reset all caps. We bake from scratch
			$caps = array();

			// Should never get here if there's no user
			if ( ! $user_id ) {
				$caps[] = 'do_not_allow';

				// All logged-in users can create
			} else {
				$caps[] = 'exist';
			}

			break;

		// Special cases: logged-in users only.
		case 'bp_docs_edit' :
		case 'bp_docs_manage' :
			if ( ! $user_id ) {
				return array( 'do_not_allow' );
			}

			// Fall through.

		case 'bp_docs_read' :
		case 'bp_docs_view_history' :
		case 'bp_docs_read_comments' :
		case 'bp_docs_post_comments' :
			// Reset all caps. We bake from scratch
			$caps = array();

			$doc = bp_docs_get_doc_for_caps( $args );

			// An explicit ID that does not belong to a Doc is never allowed.
			// Only the "no Doc yet" case below (eg the Create screen) falls back to 'read'.
			if ( ! empty( $args[0] ) && empty( $doc ) ) {
				$caps = array( 'do_not_allow' );
				break;
			}

			if ( empty( $doc ) || ( $doc instanceof WP_Post && 0 === $doc->ID && bp_docs_get_post_type_name() === $doc->post_type ) ) {
				return array( 'read' );
			}

			// Special case: view_history requires post revisions
			// @todo Move this to addon-history
			if ( 'bp_docs_view_history' === $cap && ! wp_revisions_enabled( $doc ) ) {
				return array( 'do_not_allow' );
			}

			// Admins can do everything
			if ( user_can( $user_id, 'bp_moderate' ) ) {
				return array( 'exist' );
			}

			// Anyone has caps for their own docs.
			if ( (int) $user_id === (int) $doc->post_author ) {
				return array( 'exist' );
			}

			// A Doc still being created (see bp_docs_create_dummy_doc()) has no
			// settings yet, so the defaults would apply. It belongs to its
			// creator alone until it is first saved. Returning here also keeps
			// group-based settings from granting access through the filter.
			if ( 'auto-draft' === $doc->post_status ) {
				return array( 'do_not_allow' );
			}

			$doc_settings = bp_docs_get_doc_settings( $doc->ID );

			// Caps are stored without the 'bp_docs_' prefix,
			// mostly for legacy reasons
			$cap_name = substr( $cap, 8 );

			switch ( $doc_settings[ $cap_name ] ) {
				case 'anyone' :
					$caps[] = 'exist';

					// Anonymous comment posting should respect site-wide setting.
					if ( 'bp_docs_post_comments' === $cap_name && get_option( 'comment_registration' ) ) {
						$caps = array();
					}
					break;

				case 'loggedin' :
					if ( ! $user_id ) {
						$caps[] = 'do_not_allow';
					} else {
						$caps[] = 'exist';
					}

					break;

				case 'creator' :
					if ( (int) $user_id === (int) $doc->post_author ) {
						$caps[] = 'exist';
					} else {
						$caps[] = 'do_not_allow';
					}

					break;

				case 'no-one' :
				default :
					$caps[] = 'do_not_allow';
					break;

				// Group-specific caps get passed to filter
			}

			break;
	}

	return apply_filters( 'bp_docs_map_meta_caps', $caps, $cap, $user_id, $args );
}
add_filter( 'map_meta_cap', 'bp_docs_map_meta_caps', 10, 4 );

/**
 * Map WordPress's own post capabilities for Docs and Folders onto Docs permissions.
 *
 * Docs and Folders are registered with the default 'post' capability type, so
 * without this, core checks such as 'edit_post' (used by XML-RPC, comment
 * editing and others) would follow site roles: a site Editor could read and
 * change any Doc. Docs' own settings decide instead.
 *
 * - Docs (and their revisions): 'read_post' requires 'bp_docs_read'.
 *   'edit_post', 'delete_post' and 'publish_post' require 'bp_moderate'.
 *   Docs are edited, deleted and moderated through the Docs interface, which
 *   checks the Docs caps directly; core paths (XML-RPC, wp-admin comment
 *   editing) would bypass Docs' own validation of settings, parents and
 *   group association.
 * - Folders: 'edit_post', 'delete_post' and 'publish_post' require
 *   'bp_moderate', for the same reason.
 * - Attachments of a Doc: 'read_post' requires 'bp_docs_read' on the Doc;
 *   'edit_post' and 'delete_post' require 'bp_docs_edit' on the Doc, as for
 *   managing attachments in the Docs interface.
 *
 * Runs before BP_Docs_Attachments::map_meta_cap() (priority 10), which grants
 * 'edit_post' for the Doc being uploaded to.
 *
 * @since 2.2.8
 *
 * @param array  $caps    Required primitive caps.
 * @param string $cap     Capability being checked.
 * @param int    $user_id User ID.
 * @param array  $args    Capability arguments; the post ID is $args[0].
 * @return array
 */
function bp_docs_map_core_post_caps( $caps, $cap, $user_id, $args ) {
	if ( ! in_array( $cap, array( 'edit_post', 'delete_post', 'publish_post', 'read_post' ), true ) || empty( $args[0] ) ) {
		return $caps;
	}

	$post = get_post( $args[0] );
	if ( ! $post ) {
		return $caps;
	}

	// Attachments of a Doc follow the Doc.
	if ( 'attachment' === $post->post_type && $post->post_parent && bp_docs_get_post_type_name() === get_post_type( $post->post_parent ) ) {
		$attachment_caps = array(
			'read_post'   => 'bp_docs_read',
			'edit_post'   => 'bp_docs_edit',
			'delete_post' => 'bp_docs_edit',
		);

		if ( isset( $attachment_caps[ $cap ] ) ) {
			return map_meta_cap( $attachment_caps[ $cap ], $user_id, $post->post_parent );
		}

		return $caps;
	}

	// Revisions follow their parent for editing and reading. Deleting a
	// revision is left to core, which doesn't allow it.
	if ( 'revision' === $post->post_type && $post->post_parent ) {
		if ( 'delete_post' === $cap ) {
			return $caps;
		}

		$post = get_post( $post->post_parent );
		if ( ! $post ) {
			return $caps;
		}
	}

	$is_doc    = bp_docs_get_post_type_name() === $post->post_type;
	$is_folder = 'bp_docs_folder' === $post->post_type;

	if ( ! $is_doc && ! $is_folder ) {
		return $caps;
	}

	if ( 'read_post' === $cap ) {
		return $is_doc ? map_meta_cap( 'bp_docs_read', $user_id, $post->ID ) : $caps;
	}

	return map_meta_cap( 'bp_moderate', $user_id );
}
add_filter( 'map_meta_cap', 'bp_docs_map_core_post_caps', 9, 4 );

/**
 * Capabilities for the taxonomies that Docs manages internally.
 *
 * Group association, access levels and folder membership are set only by
 * Docs' own code, which doesn't check these caps. Locking them keeps core
 * paths such as XML-RPC from assigning the terms directly.
 *
 * @since 2.2.8
 *
 * @return array
 */
function bp_docs_internal_taxonomy_capabilities() {
	return array(
		'manage_terms' => 'do_not_allow',
		'edit_terms'   => 'do_not_allow',
		'delete_terms' => 'do_not_allow',
		'assign_terms' => 'do_not_allow',
	);
}

/**
 * Load up the doc to check against for meta cap mapping
 *
 * @since 1.2
 *
 * @param array $args The $args argument passed by the map_meta_cap filter. May be empty
 * @return obj $doc
 */
function bp_docs_get_doc_for_caps( $args = array() ) {
	global $post;

	$doc_id = 0;
	$doc    = null;
	if ( isset( $args[0] ) ) {
		$doc_id = $args[0];
		$doc    = get_post( $doc_id );
	} elseif ( isset( $post->ID ) ) {
		$doc = $post;
	}

	if ( ! is_a( $doc, 'WP_Post' ) || bp_docs_get_post_type_name() !== $doc->post_type ) {
		$doc = null;
	}

	return apply_filters( 'bp_docs_get_doc_for_caps', $doc, $args );
}

/**
 * Utility function for checking whether a doc's permission settings are set to Custom, and that
 * a given user has access to the doc for that particular action.
 *
 * @since 1.2
 *
 * @param
 */
function bp_docs_user_has_custom_access( $user_id, $doc_settings, $key ) {
	// Default to true, so that if it's not set to 'custom', you pass through
	$has_access = true;

	if ( isset( $doc_settings[ $key ] ) && 'custom' === $doc_settings[ $key ] && is_array( $doc_settings[ $key ] ) ) {
		$user_ids   = array_map( 'intval', $doc_settings[ $key ] );
		$has_access = in_array( (int) $user_id, $user_ids, true );
	}

	return $has_access;
}
