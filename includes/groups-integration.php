<?php

class BP_Docs_Groups_Integration {
	/**
	 * PHP 4 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function bp_docs_groups_integration() {
		$this->__construct();
	}

	/**
	 * PHP 5 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */	
	function __construct() {
		bp_register_group_extension( 'BP_Docs_Group_Extension' );
		
		// Filter some properties of the query object
		add_filter( 'bp_docs_get_item_type', 		array( $this, 'get_item_type' ) );
		add_filter( 'bp_docs_get_current_view', 	array( $this, 'get_current_view' ), 10, 2 );
		add_filter( 'bp_docs_this_doc_slug',		array( $this, 'get_doc_slug' ) );
		
		// Taxonomy helpers
		add_filter( 'bp_docs_taxonomy_get_item_terms', 	array( $this, 'get_group_terms' ) );
		add_action( 'bp_docs_taxonomy_save_item_terms', array( $this, 'save_group_terms' ) );
		
		add_filter( 'bp_docs_user_can_edit',		array( $this, 'user_can_edit' ), 10, 2 );
	}
	
	/**
	 * Check to see whether the query object's item type should be 'groups'
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */	
	function get_item_type( $type ) {
		global $bp;
		
		// BP 1.2/1.3 compatibility
		$is_group_component = function_exists( 'bp_is_current_component' ) ? bp_is_current_component( 'groups' ) : $bp->current_component == $bp->groups->slug;
		
		if ( $is_group_component ) {
			$type = 'group';
		}
		
		return $type;
	}
	
	/**
	 * Set the doc slug when we are viewing a group doc
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */	
	function get_doc_slug( $slug ) {
		global $bp;
		
		// BP 1.2/1.3 compatibility
		$is_group_component = function_exists( 'bp_is_current_component' ) ? bp_is_current_component( 'groups' ) : $bp->current_component == $bp->groups->slug;
		
		if ( $is_group_component ) {
			if ( !empty( $bp->action_variables[0] ) )
				$slug = $bp->action_variables[0];
		}
		
		// Cache in the $bp global
		$bp->bp_docs->doc_slug = $slug;
		
		return $slug;
	}
	
	/**
	 * Get the current view type when the item type is 'group'
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */	
	function get_current_view( $view, $item_type ) {
		global $bp;
		
		if ( $item_type == 'group' ) {
			if ( empty( $bp->action_variables[0] ) ) {
				// An empty $bp->action_variables[0] means that you're looking at a list
				$view = 'list';
			} else if ( $bp->action_variables[0] == BP_DOCS_CATEGORY_SLUG ) {
				// Category view
				$view = 'category';
			} else if ( $bp->action_variables[0] == BP_DOCS_CREATE_SLUG ) {
				// Create new doc
				$view = 'create';
			} else if ( empty( $bp->action_variables[1] ) ) {
				// $bp->action_variables[1] is the slug for this doc. If there's no
				// further chunk, then we're attempting to view a single item
				$view = 'single';
			} else if ( !empty( $bp->action_variables[1] ) && $bp->action_variables[1] == BP_DOCS_EDIT_SLUG ) {
				// This is an edit page
				$view = 'edit';
			}
		}
		
		return $view;
	}
	
	/**
	 * Gets the list of terms used by a group's docs
	 *
	 * At the moment, this method (and the next one) assumes that you want the terms of the
	 * current group. At some point, that should be abstracted a bit.
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @return array $terms
	 */	
	function get_group_terms() {
		global $bp;
		
		if ( ! empty( $bp->groups->current_group->id ) ) {
			$terms = groups_get_groupmeta( $bp->groups->current_group->id, 'bp_docs_terms' );
			
			if ( empty( $terms ) )
				$terms = array();
		}
		
		return apply_filters( 'bp_docs_taxonomy_get_group_terms', $terms );
	}
	
	/**
	 * Saves the list of terms used by a group's docs
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @param array $terms The terms to be saved to groupmeta
	 */	
	function save_group_terms( $terms ) {
		global $bp;
		
		if ( ! empty( $bp->groups->current_group->id ) ) {
			groups_update_groupmeta( $bp->groups->current_group->id, 'bp_docs_terms', $terms );
		}
	}
	
	/**
	 * Determine whether a user can edit the group doc is question
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @param bool $can_edit The default perms passed from bp_docs_user_can_edit()
	 * @param bool $user_id The user id whose perms are being tested
	 */	
	function user_can_edit( $can_edit, $user_id ) {
		global $bp;
		
		// For now, we're going to open up edit access for every member of the group
		if ( groups_is_user_member( $user_id, $bp->groups->current_group->id ) )
			$can_edit = true;
		
		return $can_edit;
	}
}

class BP_Docs_Group_Extension extends BP_Group_Extension {	

	// Todo: make this configurable
	var $visibility = 'public';
	var $enable_nav_item = true;

	/**
	 * Constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function bp_docs_group_extension() {
		$this->name = __( 'Docs', 'bp-docs' );
		$this->slug = BP_DOCS_SLUG;

		$this->create_step_position = 45;
		$this->nav_item_position = 45;
		
		//$group_link = bp_get_group_permalink();
		//$group_slug = bp_get_group_slug();
		
	}

	/**
	 * Determines what shows up on the BP Docs panel of the Create process
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function create_screen() {
		if ( !bp_is_group_creation_step( $this->slug ) )
			return false;
		?>

		<p>The HTML for my creation step goes here.</p>

		<?php
		wp_nonce_field( 'groups_create_save_' . $this->slug );
	}

	/**
	 * Runs when the create screen is saved
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	
	function create_screen_save() {
		global $bp;

		check_admin_referer( 'groups_create_save_' . $this->slug );

		/* Save any details submitted here */
		groups_update_groupmeta( $bp->groups->new_group_id, 'my_meta_name', 'value' );
	}

	/**
	 * Determines what shows up on the BP Docs panel of the Group Admin
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function edit_screen() {
		if ( !bp_is_group_admin_screen( $this->slug ) )
			return false; ?>

		<h2><?php echo attribute_escape( $this->name ) ?></h2>

		<p>Edit steps here</p>
		<input type=&quot;submit&quot; name=&quot;save&quot; value=&quot;Save&quot; />

		<?php
		wp_nonce_field( 'groups_edit_save_' . $this->slug );
	}

	/**
	 * Runs when the admin panel is saved
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	
	function edit_screen_save() {
		global $bp;

		if ( !isset( $_POST['save'] ) )
			return false;

		check_admin_referer( 'groups_edit_save_' . $this->slug );

		/* Insert your edit screen save code here */

		/* To post an error/success message to the screen, use the following */
		if ( !$success )
			bp_core_add_message( __( 'There was an error saving, please try again', 'buddypress' ), 'error' );
		else
			bp_core_add_message( __( 'Settings saved successfully', 'buddypress' ) );

		bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . '/admin/' . $this->slug );
	}

	/**
	 * Loads the display template
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function display() {
		global $bp_docs;
		
		$bp_docs->bp_integration->query->load_template();
	}

	/**
	 * Dummy function that must be overridden by this extending class, as per API
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	
	function widget_display() { }
}

?>