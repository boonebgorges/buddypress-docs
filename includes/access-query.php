<?php

class BP_Docs_Access_Query {
	protected $user_id;
	protected $tax_query = array();
	protected $user_groups = array();

	public function __construct( $user_id = 0 ) {
		$this->user_id = intval( $user_id );;
		$this->prepare_tax_query();
	}

	protected function prepare_tax_query() {
		// bp_moderate users can see anything, so no query needed
		if ( user_can( $this->user_id, 'bp_moderate' ) ) {
			return array();
		}

		// Everyone can see 'anyone' docs
		$levels = array( bp_docs_get_access_term_anyone() );

		// Logged-in users
		// Note that we're not verifying that the user actually exists
		// For now this kind of check is up to whoever's instantiating
		if ( $this->user_id != 0 ) {
			$levels[] = bp_docs_get_access_term_loggedin();

			$this->set_up_user_groups();

			// group-members
			foreach ( $this->user_groups['groups'] as $member_group ) {
				$levels[] = bp_docs_get_access_term_group_member( $member_group );
			}

			// admins-mods
			foreach ( $this->user_groups['admin_mod_of'] as $adminmod_group ) {
				$levels[] = bp_docs_get_access_term_group_adminmod( $adminmod_group );
			}

			// no-one
			// creator
			// @todo What's the difference?
			$levels[] = bp_docs_get_access_term_user( $this->user_id );
		}

		$this->tax_query[] = array(
			'terms'    => $levels,
			'taxonomy' => bp_docs_get_access_tax_name(),
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
		return $this->tax_query;
	}
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
	$bp_docs_access_query = new BP_Docs_Access_Query( bp_loggedin_user_id() );

	$tax_query = $query->get( 'tax_query' );
	if ( ! $tax_query ) {
		$tax_query = array();
	}

	$query->set( 'tax_query', array_merge( $tax_query, $bp_docs_access_query->get_tax_query() ) );
}
add_action( 'pre_get_posts', 'bp_docs_general_access_protection' );
