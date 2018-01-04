<?php

/**
 * Functionality related to the association of docs to specific users
 *
 * @package BuddyPress_Docs
 * @subpackage Users
 * @since 1.2
 */

class BP_Docs_Users_Integration {
	function __construct() {
		// Filter some properties of the query object
		add_filter( 'bp_docs_get_item_type',    array( &$this, 'get_item_type' ) );
		add_filter( 'bp_docs_get_current_view', array( &$this, 'get_current_view' ), 10, 2 );
		add_filter( 'bp_docs_this_doc_slug',    array( &$this, 'get_doc_slug' ) );

		// Add the approriate navigation item for single docs
		add_action( 'wp', array( &$this, 'setup_single_doc_subnav' ), 1 );

		// These functions are used to keep the user's Doc count up to date
		add_filter( 'bp_docs_doc_saved',   array( $this, 'update_doc_count' )  );
		add_filter( 'bp_docs_doc_deleted', array( $this, 'update_doc_count' ) );

		// Taxonomy helpers
		add_filter( 'bp_docs_taxonomy_get_item_terms', 	array( &$this, 'get_user_terms' ) );

		// On user Doc directories, modify the pagination base so that pagination works within the directory.
		add_filter( 'bp_docs_page_links_base_url', 		array( $this, 'filter_bp_docs_page_links_base_url' ), 10, 2 );

		// Set the "last directory viewed" cookie when a user is viewing her docs directory.
		add_action( 'bp_actions', array( $this, 'set_directory_cookie' ) );
	}

	/**
	 * Check to see whether the query object's item type should be 'user'
	 *
	 * @package BuddyPress_Docs
	 * @since 1.2
	 *
	 * @param str $type
	 * @return str $type
	 */
	function get_item_type( $type ) {
		if ( bp_is_user() ) {
			$type = 'user';
		}

		return $type;
	}

	/**
	 * Sets up the current view when viewing a user page
	 *
	 * @since 1.2
	 */
	function get_current_view( $view, $item_type ) {
		global $wp_rewrite;

		if ( $item_type == 'user' ) {
			$current_action = bp_current_action();
			if ( empty( $current_action )
				|| in_array( $current_action, array( BP_DOCS_STARTED_SLUG, BP_DOCS_EDITED_SLUG ) ) ) {
				// An empty $bp->action_variables[0] means that you're looking at a list.
				// A url like members/terry/docs/started/page/3 also means you're looking at a list.
				$view = 'list';
			} else if ( $current_action == BP_DOCS_CATEGORY_SLUG ) {
				// Category view
				$view = 'category';
			} else if ( $current_action == BP_DOCS_CREATE_SLUG ) {
				// Create new doc
				$view = 'create';
			} else if ( !bp_action_variable( 0 ) ) {
				// $bp->action_variables[1] is the slug for this doc. If there's no
				// further chunk, then we're attempting to view a single item
				$view = 'single';
			} else if ( bp_is_action_variable( BP_DOCS_EDIT_SLUG, 0 ) ) {
				// This is an edit page
				$view = 'edit';
			} else if ( bp_is_action_variable( BP_DOCS_DELETE_SLUG, 0 ) ) {
				// This is an delete request
				$view = 'delete';
			} else if ( bp_is_action_variable( BP_DOCS_HISTORY_SLUG, 0 ) ) {
				// This is a history request
				$view = 'history';
			}
		}

		return $view;
	}

	/**
	 * Set the doc slug when we are viewing a user doc
	 *
	 * @package BuddyPress_Docs
	 * @since 1.2
	 */
	function get_doc_slug( $slug ) {
		global $bp;

		if ( bp_is_user() ) {
			// Doc slug can't be my-docs or create
			if ( !in_array( bp_current_action(), array( 'my-docs', 'create' ) ) ) {
				$slug = bp_current_action();
			}
		}

		// Cache in the $bp global
		$bp->bp_docs->doc_slug = $slug;

		return $slug;
	}

	/**
	 * When looking at a single doc, this adds the appropriate subnav item.
	 *
	 * Other navigation items are added in BP_Docs_Component. We add this item here because it's
	 * not until later in the load order when we can be certain whether we're viewing a
	 * single Doc.
	 *
	 * @since 1.2
	 */
	function setup_single_doc_subnav() {
		global $bp;

		if ( bp_is_user() && !empty( $bp->bp_docs->current_view ) && in_array( $bp->bp_docs->current_view, array( 'single', 'edit', 'history', 'delete' ) ) ) {
			$doc = bp_docs_get_current_doc();

			if ( !empty( $doc ) ) {
				bp_core_new_subnav_item( array(
					'name'            => $doc->post_title,
					'slug'            => $doc->post_name,
					'parent_url'      => trailingslashit( bp_loggedin_user_domain() . bp_docs_get_docs_slug() ),
					'parent_slug'     => bp_docs_get_docs_slug(),
					'screen_function' => array( $bp->bp_docs, 'template_loader' ),
					'position'        => 30,
					'user_has_access' => true, // todo
				) );
			}
		}
	}

	/**
	 * Update's a user's Doc count
	 *
	 * @since 1.2
	 */
	function update_doc_count() {
		bp_docs_update_doc_count( bp_loggedin_user_id(), 'user' );
	}

	/**
	 * Gets the list of terms used by a user's docs
	 *
	 * At the moment, this method (and the next one) assumes that you want the terms of the
	 * displayed user. At some point, that should be abstracted a bit.
	 *
	 * @package BuddyPress_Docs
	 * @subpackage Users
	 * @since 1.2
	 *
	 * @return array $terms
	 */
	function get_user_terms( $terms = array() ) {
		global $wpdb;

		if ( ! bp_is_user() ) {
			return $terms;
		}

		$query_args = array(
			'post_type'         => bp_docs_get_post_type_name(),
			'update_meta_cache' => false,
			'update_term_cache' => true,
			'showposts'         => '-1',
			'posts_per_page'    => '-1',
		);

		if ( bp_docs_is_edited_by() ) {
			$query_args['post__in'] = BP_Docs_Query::get_edited_by_post_ids_for_user( bp_displayed_user_id() );
			$query_args['post_status'] = array( 'publish' );
		} else if ( bp_docs_is_started_by() ) {
			$query_args['author'] = bp_displayed_user_id();
			$query_args['post_status'] = array( 'publish', 'trash', 'bp_docs_pending' );
		} else {
			// Just in case
			$query_args['post__in'] = array( 0 );
		}

		$user_doc_query = new WP_Query( $query_args );

		$terms = array();
		foreach ( $user_doc_query->posts as $p ) {
			$p_terms = wp_get_post_terms( $p->ID, buddypress()->bp_docs->docs_tag_tax_name );
			foreach ( $p_terms as $p_term ) {
				if ( ! isset( $terms[ $p_term->slug ] ) ) {
					$terms[ $p_term->slug ] = array(
						'name' => $p_term->name,
						'posts' => array(),
					);
				}

				if ( ! in_array( $p->ID, $terms[ $p_term->slug ]['posts'] ) ) {
					$terms[ $p_term->slug ]['posts'][] = $p->ID;
				}
			}
		}

		foreach ( $terms as &$t ) {
			$t['count'] = count( $t['posts'] );
		}

		if ( empty( $terms ) ) {
			$terms = array();
		}

		return apply_filters( 'bp_docs_taxonomy_get_user_terms', $terms );
	}

	/**
	 * Saves the list of terms used by a user's docs
	 *
	 * No longer used.
	 *
	 * @package BuddyPress_Docs
	 * @subpackage Users
	 * @since 1.2
	 *
	 * @param array $terms The terms to be saved to usermeta
	 */
	function save_user_terms( $terms ) {}

	/**
	 * On user Doc directories, modify the pagination base so that pagination
	 * works within the directory.
	 *
	 * @package BuddyPress_Docs
	 * @subpackage Users
	 * @since 1.9.0
	 */
	public function filter_bp_docs_page_links_base_url( $base_url, $wp_rewrite_pag_base  ) {
		if ( bp_is_user() ) {
			$current_action = bp_current_action();
			if ( $current_action == BP_DOCS_STARTED_SLUG ) {
				$base_url = user_trailingslashit( bp_docs_get_displayed_user_docs_started_link() . $wp_rewrite_pag_base . '/%#%/' );
			} elseif ( $current_action == BP_DOCS_EDITED_SLUG ) {
				$base_url = user_trailingslashit( bp_docs_get_displayed_user_docs_edited_link()  . $wp_rewrite_pag_base . '/%#%/' );
			}
		}
		return $base_url;
	}

	/**
	 * Renew the last directory cookie if the user is viewing her docs library.
	 *
	 * @since 1.9.0
	 */
	public function	set_directory_cookie() {
		global $wp;

		if ( bp_docs_is_user_docs() ) {
			bp_docs_set_last_docs_directory_cookie();
		}
	}

}

/**
 * Get the name to show in the users tab
 *
 * @since 1.5
 * @return string
 */
function bp_docs_get_user_tab_name() {
	$name = get_option( 'bp-docs-user-tab-name' );
	if ( empty( $name ) ) {
		$name = __( 'Docs', 'buddypress-docs' );
	}
	return apply_filters( 'bp_docs_get_user_tab_name', $name );
}

/**
 * Add user information to directory breadcrumbs.
 *
 * @since 1.9.0
 *
 * @param array $crumbs
 * @return array
 */
function bp_docs_user_directory_breadcrumb( $crumbs ) {
	if ( bp_is_user() ) {
		$user_crumbs = array(
			sprintf(
				'<a href="%s">%s</a>',
				bp_displayed_user_domain() . bp_docs_get_slug() . '/',
				sprintf( _x( '%s&#8217;s Docs', 'user Docs directory breadcrumb', 'buddypress-docs' ), esc_html( bp_get_displayed_user_fullname() ) )
			),
		);

		switch ( bp_current_action() ) {
			case 'started' :
				$user_crumbs[] = sprintf(
					'<a href="%s">%s</a>',
					bp_docs_get_mydocs_started_link(),
					__( 'Started By Me', 'buddypress-docs' )
				);

				break;

			case 'edited' :
				$user_crumbs[] = sprintf(
					'<a href="%s">%s</a>',
					bp_docs_get_mydocs_edited_link(),
					__( 'Edited By Me', 'buddypress-docs' )
				);

				break;
		}

		$crumbs = array_merge( $user_crumbs, $crumbs );
	}

	return $crumbs;
}
add_filter( 'bp_docs_directory_breadcrumb', 'bp_docs_user_directory_breadcrumb', 2 );

/**
 * Add user information to individual Doc breadcrumbs.
 *
 * Hooked very late to ensure it's the first item on the list.
 *
 * @since 1.9.0
 *
 * @param array $crumbs
 * @return array
 */
function bp_docs_user_single_breadcrumb( $crumbs, $doc = null ) {

	if ( is_a( $doc, 'WP_Post' ) ) {
		$doc_id = $doc->ID;
	} else if ( bp_docs_is_existing_doc() ) {
		$doc_id = get_queried_object_id();
	}

	if ( bp_docs_enable_folders() && ! empty( $doc_id ) ) {
		$folder_id = bp_docs_get_doc_folder( $doc_id );

		if ( $folder_id ) {
			$user_id = bp_docs_get_folder_user( $folder_id );
		}
	}

	if ( ! empty( $user_id ) ) {
		$user_crumbs = array(
			sprintf(
				'<a href="%s">%s&#8217;s Docs</a>',
				bp_core_get_user_domain( $user_id ) . bp_docs_get_slug() . '/',
				bp_core_get_user_displayname( $user_id )
			),
		);

		$crumbs = array_merge( $user_crumbs, $crumbs );
	}

	return $crumbs;
}
add_action( 'bp_docs_doc_breadcrumbs', 'bp_docs_user_single_breadcrumb', 99, 2 );

/**
 * Add breadcrumbs to Started and Edited screens.
 */
function bp_docs_user_breadcrumbs_in_doc_list() {
	if ( ! bp_docs_is_started_by() && ! bp_docs_is_edited_by() ) {
		return;
	} ?>

	<div class="list-item-breadcrumb">
		<?php bp_docs_the_breadcrumb( array(
			'include_doc' => false,
			'doc_id'      => get_the_ID(),
		) ) ?>
	</div><?php
}
add_action( 'bp_docs_loop_after_doc_excerpt', 'bp_docs_user_breadcrumbs_in_doc_list' );
