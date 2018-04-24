<?php

class BP_Docs_Access_Query {
	protected $user_id;
	protected $tax_query = array();
	protected $user_groups = array();
	protected $levels = array();
	protected $comment_levels = array();
	protected $comment_tax_query = array();

	public static function init( $user_id = 0 ) {
		static $instance;

		if ( empty( $instance[$user_id] ) ) {
			$instance[$user_id] = new BP_Docs_Access_Query( $user_id );
		}

		return $instance[$user_id];
	}

	public function __construct( $user_id = 0 ) {
		$this->user_id = intval( $user_id );
		$this->set_up_levels();
		$this->prepare_tax_query();
		$this->prepare_comment_tax_query();
	}

	protected function set_up_levels() {
		// Everyone can see 'anyone' docs
		$this->levels[] = bp_docs_get_access_term_anyone();
		$this->comment_levels[] = bp_docs_get_comment_access_term_anyone();

		// Logged-in users
		// Note that we're not verifying that the user actually exists
		// For now this kind of check is up to whoever's instantiating
		if ( $this->user_id != 0 ) {
			$this->levels[] = bp_docs_get_access_term_loggedin();
			$this->comment_levels[] = bp_docs_get_comment_access_term_loggedin();

			if ( bp_is_active('groups') ) {

				$this->set_up_user_groups();

				if ( isset($this->user_groups['groups']) ) {
					foreach ( $this->user_groups['groups'] as $member_group ) {
						$this->levels[] = bp_docs_get_access_term_group_member( $member_group );
						$this->comment_levels[] = bp_docs_get_comment_access_term_group_member( $member_group );
					}
				}

				// admins-mods
				if ( isset($this->user_groups['admin_mod_of']) ) {
					foreach ( $this->user_groups['admin_mod_of'] as $adminmod_group ) {
						$this->levels[] = bp_docs_get_access_term_group_adminmod( $adminmod_group );
						$this->comment_levels[] = bp_docs_get_comment_access_term_group_adminmod( $adminmod_group );
					}
				}
			}

			// no-one
			// creator
			// @todo What's the difference?
			$this->levels[] = bp_docs_get_access_term_user( $this->user_id );
			$this->comment_levels[] = bp_docs_get_comment_access_term_user( $this->user_id );
		}
	}

	protected function prepare_tax_query() {
		$this->tax_query[] = array(
			'terms'    => $this->levels,
			'taxonomy' => bp_docs_get_access_tax_name(),
			'field'    => 'slug',
			'operator' => 'IN',
		);
	}

	protected function prepare_comment_tax_query() {
		$this->comment_tax_query[] = array(
			'terms'    => $this->comment_levels,
			'taxonomy' => bp_docs_get_comment_access_tax_name(),
			'field'    => 'slug',
			'operator' => 'IN',
		);
	}

	/**
	 * Get a list of a user's groups, as well as those groups of which
	 * the user is an admin or mod
	 *
	 * @since 1.2
	 */
	protected function set_up_user_groups() {
		$groups                      = BP_Groups_Member::get_group_ids( $this->user_id );
		$this->user_groups['groups'] = $groups['groups'];

		$admin_groups                      = BP_Groups_Member::get_is_admin_of( $this->user_id );
		$mod_groups                        = BP_Groups_Member::get_is_mod_of( $this->user_id );
		$this->user_groups['admin_mod_of'] = array_merge( wp_list_pluck( $admin_groups['groups'], 'id' ), wp_list_pluck( $mod_groups['groups'], 'id' ) );
	}

	/**
	 * Returns the tax_query param for the WP_Query args
	 *
	 * @since 1.2
	 * @return array
	 */
	public function get_tax_query() {
		// bp_moderate users can see anything, so no query needed
		if ( user_can( $this->user_id, 'bp_moderate' ) ) {
			return array();
		}

		return $this->tax_query;
	}


	/**
	 * Returns the tax_query param for the WP_Query args
	 *
	 * @since 2.0
	 * @return array
	 */
	public function get_comment_tax_query() {
		// bp_moderate users can see anything, so no query needed
		if ( user_can( $this->user_id, 'bp_moderate' ) ) {
			return array();
		}

		return $this->comment_tax_query;
	}

	/**
	 * Fetch a list of Doc IDs that are forbidden for the user
	 *
	 * @since 1.2.8
	 */
	public function get_doc_ids() {
		// Check the cache first.
		$last_changed = wp_cache_get( 'last_changed', 'bp_docs_nonpersistent' );
		if ( false === $last_changed ) {
			$last_changed = microtime();
			wp_cache_set( 'last_changed', $last_changed, 'bp_docs_nonpersistent' );
		}

		$cache_key = 'bp_docs_forbidden_docs_for_user_' . $this->user_id . '_' . $last_changed;
		$cached = wp_cache_get( $cache_key, 'bp_docs_nonpersistent' );
		if ( false !== $cached ) {
			return $cached;
		} else {
			remove_action( 'pre_get_posts', 'bp_docs_general_access_protection', 28 );

			$tax_query = $this->get_tax_query();
			foreach ( $tax_query as &$tq ) {
				$tq['operator'] = "NOT IN";
			}

			// If the tax_query is empty, no docs are forbidden
			if ( empty( $tax_query ) ) {
				$protected_doc_ids = array( 0 );
			} else {
				$forbidden_fruit = new WP_Query( array(
					'post_type' => bp_docs_get_post_type_name(),
					'posts_per_page' => -1,
					'nopaging' => true,
					'tax_query' => $tax_query,
					'update_post_term_cache' => false,
					'update_post_meta_cache' => false,
					'no_found_rows' => 1,
					'fields' => 'ids',
				) );
				if ( $forbidden_fruit->posts ) {
					$protected_doc_ids = $forbidden_fruit->posts;
				} else {
					/*
					 * If no results are returned, we save a 0 value to avoid the
					 * post__in => array() fetches everything problem.
					 */
				 	$protected_doc_ids = array( 0 );
				}
			}

			add_action( 'pre_get_posts', 'bp_docs_general_access_protection', 28 );

			// Set the cache to avoid duplicate requests.
			wp_cache_set( $cache_key, $protected_doc_ids, 'bp_docs_nonpersistent' );

			return $protected_doc_ids;
		}
	}

	/**
	 * Fetch a list of Doc IDs whose comments the user cannot read.
	 *
	 * @since 1.2.8
	 */
	public function get_restricted_comment_doc_ids() {
		// Check the cache first.
		$last_changed = wp_cache_get( 'last_changed', 'bp_docs_nonpersistent' );
		if ( false === $last_changed ) {
			$last_changed = microtime();
			wp_cache_set( 'last_changed', $last_changed, 'bp_docs_nonpersistent' );
		}

		$cache_key = 'bp_docs_forbidden_comment_docs_for_user_' . $this->user_id . '_' . $last_changed;
		$cached = wp_cache_get( $cache_key, 'bp_docs_nonpersistent' );
		if ( false !== $cached ) {
			return $cached;
		} else  {
			remove_action( 'pre_get_posts', 'bp_docs_general_access_protection', 28 );

			$tax_query = $this->get_comment_tax_query();
			foreach ( $tax_query as &$tq ) {
				$tq['operator'] = "NOT IN";
			}

			// If the tax_query is empty, no docs are forbidden
			if ( empty( $tax_query ) ) {
				$restricted_comment_doc_ids = array( 0 );
			} else {
				$forbidden_fruit = new WP_Query( array(
					'post_type' => bp_docs_get_post_type_name(),
					'posts_per_page' => -1,
					'nopaging' => true,
					'tax_query' => $tax_query,
					'update_post_term_cache' => false,
					'update_post_meta_cache' => false,
					'no_found_rows' => 1,
					'fields' => 'ids',
				) );
				if ( $forbidden_fruit->posts ) {
					$restricted_comment_doc_ids = $forbidden_fruit->posts;
				} else {
					/*
					 * If no results are returned, we save a 0 value to avoid the
					 * post__in => array() fetches everything problem.
					 */
				 	$restricted_comment_doc_ids = array( 0 );
				}
			}

			add_action( 'pre_get_posts', 'bp_docs_general_access_protection', 28 );

			// Set the cache to avoid duplicate requests.
			wp_cache_set( $cache_key, $restricted_comment_doc_ids, 'bp_docs_nonpersistent' );

			return $restricted_comment_doc_ids;
		}
	}

	/**
	 * Fetch a list of Comment IDs that are forbidden for the user
	 *
	 * @since 2.0
	 */
	public function get_comment_ids() {
		/*
		 * Check the cache first.
		 * WordPress caches the 'last_changed' comment time, so let's use that.
		 */
		$last_changed = wp_cache_get( 'last_changed', 'comment' );
		if ( false === $last_changed ) {
			$last_changed = microtime();
			wp_cache_set( 'last_changed', $last_changed, 'comment' );
		}

		$cache_key = 'bp_docs_forbidden_comments_for_user_' . $this->user_id . '_' . $last_changed;
		$cached = wp_cache_get( $cache_key, 'bp_docs_nonpersistent' );
		if ( false !== $cached ) {
			return $cached;
		} else {
			remove_action( 'pre_get_comments', 'bp_docs_general_comment_protection' );

			$protected_comment_ids = get_comments( array(
				'post__in' => $this->get_restricted_comment_doc_ids(),
				'fields' => 'ids',
			) );
			if ( ! $protected_comment_ids ) {
				/*
				 * If no results are returned, we save a 0 value to avoid the
				 * post__in => array() fetches everything problem.
				 */
				$protected_comment_ids = array( 0 );
			}

			add_action( 'pre_get_comments', 'bp_docs_general_comment_protection' );

			// Set the cache to avoid duplicate requests.
			wp_cache_set( $cache_key, $protected_comment_ids, 'bp_docs_nonpersistent' );

			return $protected_comment_ids;
		}
	}
}

/**
 * Wrapper function for BP_Docs_Access_Query singleton
 *
 * @since 1.2.8
 */
function bp_docs_access_query() {
	return BP_Docs_Access_Query::init( bp_loggedin_user_id() );
}

/**
 * Keep private Docs out of primary WP queries
 *
 * By catching the query at pre_get_posts, we ensure that all queries are
 * filtered appropriately, whether they originate with BuddyPress Docs or not
 * (as in the case of search)
 *
 * @since 1.2.8
 */
function bp_docs_general_access_protection( $query ) {
	// Access is unlimited when viewing your own profile, or when the
	// current user is a site admin
	if ( bp_is_my_profile() || current_user_can( 'bp_moderate' ) ) {
		return;
	}

	// We only need to filter when BP Docs could possibly show up in the
	// results, so we check the post type, and bail if the post_type rules
	// out Docs to begin with
	$queried_post_type = $query->get( 'post_type' );
	$pt = bp_docs_get_post_type_name();
	$is_bp_doc_query = is_array( $queried_post_type ) ? in_array( $pt, $queried_post_type ) : $pt == $queried_post_type;

	$filter_query = false;

	// 'pagename' queries always fetch pages.
	if ( ! $queried_post_type && ! $query->get( 'pagename' ) ) {
		$filter_query = true;
	} elseif ( 'any' === $queried_post_type ) {
		$filter_query = true;
	} elseif ( $is_bp_doc_query ) {
		$filter_query = true;
	}

	if ( $filter_query ) {
		$bp_docs_access_query = bp_docs_access_query();

		if ( $pt == $queried_post_type ) {
			// Use a tax query if possible
			$tax_query = $query->get( 'tax_query' );
			if ( ! $tax_query ) {
				$tax_query = array();
			}

			$query->set( 'tax_query', array_merge( $tax_query, $bp_docs_access_query->get_tax_query() ) );

		} else {
			// When it's not a straight bp_doc query, a tax_query
			// approach won't work (because the taxonomy in
			// question only applies to bp_docs, and conditional
			// tax_query is not supported by WP). Instead, get a
			// list of off-limits Docs and pass to post__not_in
			$exclude = $bp_docs_access_query->get_doc_ids();

			if ( ! empty( $exclude ) ) {
				$not_in = $query->get( 'post__not_in' );
				$query->set( 'post__not_in', array_merge( (array) $not_in, $exclude ) );
			}

		}
	}
}
// Hooked at an oddball priority to avoid conflicts with nested actions and
// other plugins using 'pre_get_posts'. See https://github.com/boonebgorges/buddypress-docs/issues/425
add_action( 'pre_get_posts', 'bp_docs_general_access_protection', 28 );

/**
 * Keep comments that the user shouldn't see out of primary WP_Comment_Query queries.
 *
 * By catching the query at pre_get_comments, we ensure that all queries are
 * filtered appropriately.
 *
 * @since 2.0
 */
function bp_docs_general_comment_protection( $query ) {

	// Access is unlimited when the current user is a site admin.
	if ( current_user_can( 'bp_moderate' ) ) {
		return;
	}

	// Since we're using post__not_in, we have to be aware of post__in requests.
	$bp_docs_access_query = bp_docs_access_query();
	$restricted_comment_doc_ids = $bp_docs_access_query->get_restricted_comment_doc_ids();

	if ( ! empty( $restricted_comment_doc_ids ) ) {
		if ( ! empty( $query->query_vars['post__not_in'] ) ) {
			$query->query_vars['post__not_in'] = array_merge( (array) $query->query_vars['post__not_in'], $restricted_comment_doc_ids );
		} else {
			$query->query_vars['post__not_in'] = $restricted_comment_doc_ids;
		}
	}
}
add_action( 'pre_get_comments', 'bp_docs_general_comment_protection' );

/**
 * Reset the 'last_changed' cache incrementor when posts are updated.
 *
 * Big hammer, small nail.
 *
 * @since 2.0.0
 */
function bp_docs_cache_invalidate_last_changed_incrementor() {
	wp_cache_delete( 'last_changed', 'bp_docs_nonpersistent' );
}
add_action( 'save_post_bp_doc', 'bp_docs_cache_invalidate_last_changed_incrementor' );
add_action( 'trashed_post', 'bp_docs_cache_invalidate_last_changed_incrementor' );
add_action( 'set_object_terms', 'bp_docs_cache_invalidate_last_changed_incrementor' );
