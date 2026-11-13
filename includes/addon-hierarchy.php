<?php

/**
 * This file contains the functions used to enable hierarchical docs.
 * Separated into this file so that the feature can be turned off.
 *
 * @package BuddyPressDocs
 */

class BP_Docs_Hierarchy {
	var $parent;
	var $children;

	/**
	 * PHP 5 constructor
	 *
	 * @since 1.0-beta
	 */
	function __construct() {
		add_action( 'parse_query', array( $this, 'parse_query' ) );

		// Make sure that the bp_docs post type supports our post taxonomies
		add_filter( 'bp_docs_post_type_args', array( $this, 'register_with_post_type' ) );

		// Set parent_id for posts saved from the front end
		add_filter( 'bp_docs_get_parent_id_via_post', array( $this, 'set_parent_id_via_post' ), 10, 2 );

		// Display a doc's parent on its single doc page
		add_action( 'bp_docs_single_doc_meta', array( $this, 'show_parent' ) );

		// Display a doc's children on its single doc page
		add_action( 'bp_docs_single_doc_meta', array( $this, 'show_children' ) );

		// When a Doc is deleted, mark its children as parentless
		add_action( 'bp_docs_doc_deleted', array( $this, 'deleted_parent' ) );
	}

	/**
	 * Ensure that child Docs have the query parsed correctly.
	 *
	 * Docs are registered as a hierarchical post type, but Doc permalinks are
	 * flat (see BP_Docs_Component::filter_permalinks()). For hierarchical
	 * post types, WP_Query treats the post type query var as a full path
	 * and resolves it with get_page_by_path(), which fails for child Docs
	 * because the URL does not contain the ancestors' slugs.
	 *
	 * Doc slugs are unique across all hierarchies (see
	 * bp_docs_force_unique_slugs()), so we clear the post type query var
	 * and let WP_Query look the Doc up by 'name' alone.
	 *
	 * @since 1.8.5
	 * @since 2.2.8 Rewritten to avoid a direct database query.
	 *
	 * @param WP_Query $q Query object.
	 */
	public function parse_query( $q ) {
		$pt = bp_docs_get_post_type_name();
		if ( $pt !== $q->get( 'post_type' ) ) {
			return;
		}

		$pt_object = get_post_type_object( $pt );
		if ( ! $pt_object || ! $pt_object->query_var ) {
			return;
		}

		$requested = $q->get( $pt_object->query_var );

		// Leave full paths (parent/child) to core, which resolves them correctly.
		if ( ! $requested || false !== strpos( $requested, '/' ) ) {
			return;
		}

		$q->set( $pt_object->query_var, '' );
		$q->set( 'name', $requested );
	}

	/**
	 * Registers the post taxonomies with the bp_docs post type
	 *
	 * @since 1.0-beta
	 *
	 * @param array The $bp_docs_post_type_args array created in BP_Docs::register_post_type()
	 * @return array $args The modified parameters
	 */
	function register_with_post_type( $args ) {
		$args['hierarchical'] = true;

		return $args;
	}

	/**
	 * Set parent_id for posts saved from the front end
	 *
	 * The submitted value is read (after nonce verification) by
	 * bp_docs_save_doc_via_post() and passed in here.
	 *
	 * @since 1.0-beta
	 * @since 2.2.8 Added the `$posted_parent_id` parameter. No longer reads from $_POST.
	 *
	 * @param int $parent_id        Default parent ID.
	 * @param int $posted_parent_id Parent ID submitted with the Doc form.
	 * @return int
	 */
	function set_parent_id_via_post( $parent_id, $posted_parent_id = 0 ) {
		$posted_parent_id = absint( $posted_parent_id );

		if ( ! $posted_parent_id ) {
			return $parent_id;
		}

		// The parent must be a Doc that the current user is able to read.
		if ( bp_docs_get_post_type_name() !== get_post_type( $posted_parent_id ) ) {
			return $parent_id;
		}

		if ( ! current_user_can( 'bp_docs_read', $posted_parent_id ) ) {
			return $parent_id;
		}

		return $posted_parent_id;
	}

	/**
	 * Display a link to the doc's parent
	 *
	 * @since 1.0-beta
	 */
	function show_parent() {
		global $post, $wp_query;

		$html = '';
		$parent = false;

		if ( ! empty( $post->post_parent ) ) {
			$parent = get_post( $post->post_parent );

			// Only show a parent Doc that the current user can read.
			if ( ! $parent || bp_docs_get_post_type_name() !== $parent->post_type || ! current_user_can( 'bp_docs_read', $parent->ID ) ) {
				$parent = false;
			}

			if ( ! empty( $parent->ID ) ) {
				$parent_url = bp_docs_get_doc_link( $parent->ID );
				$parent_title = $parent->post_title;

				$html = '<p>' . esc_html__( 'Parent: ', 'buddypress-docs' ) . sprintf( '<a href="%s" title="%s">%s</a>', esc_url( $parent_url ), esc_attr( $parent_title ), esc_html( $parent_title ) );
			}
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo apply_filters( 'bp_docs_hierarchy_show_parent', $html, $parent );
	}

	/**
	  * Display links to the doc's children
	  *
	  * @since 1.0
	  */
	function show_children() {
		global $bp, $wp_query, $post;

		// Get the child posts
		$child_posts_args = array(
			'post_type'	=> $bp->bp_docs->post_type_name,
			'post_parent'	=> get_the_ID()
		);

		$child_posts = new WP_Query( $child_posts_args );

		// Workaround for WP funniness
		$wp_query_stash = $wp_query;
		$post_stash	= $post;

		// Assemble the link data out of the query
		$child_data = array();
		if ( $child_posts->have_posts() ) {
			while ( $child_posts->have_posts() ) {
				$child_posts->the_post();

				$child_id = get_the_ID();
				$child_data[$child_id] = array(
					'post_name' => get_the_title(),
					'post_link' => bp_docs_get_doc_link( $child_id )
				);
			}
		}

		// Workaround for WP funniness
		$wp_query = $wp_query_stash;
		$post     = $post_stash;

		$child_data = apply_filters( 'bp_docs_hierarchy_child_data', $child_data );

		// Create the HTML
		$html = '';
		if ( !empty( $child_data ) ) {
			$html .= '<p>' . esc_html__( 'Children: ', 'buddypress-docs' );

			$children_html = array();
			foreach( $child_data as $child ) {
				$children_html[] = '<a href="' . esc_url( $child['post_link'] ) . '">' . esc_html( $child['post_name'] ) . '</a>';
			}

			$html .= implode( ', ', $children_html );

			$html .= '</p>';
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo apply_filters( 'bp_docs_hierarchy_show_children', $html, $child_data );
	}

	/**
	 * When a Doc is deleted, mark its children as having no parent
	 *
	 * @since 1.5
	 * @param array $args The $delete_args array from bp_docs_trash_dac()
	 */
	public function deleted_parent( $args ) {
		if ( ! isset( $args['ID'] ) ) {
			return;
		}

		$children = get_children( array(
			'post_parent' => $args['ID'],
			'post_type' => bp_docs_get_post_type_name(),
		), ARRAY_A );

		foreach ( $children as $cid => $child ) {
			wp_update_post( array(
				'ID' => $child['ID'],
				'post_parent' => 0,
			) );
		}
	}
}
