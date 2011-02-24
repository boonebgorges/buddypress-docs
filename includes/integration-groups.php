<?php
/**
 * integration-groups.php
 *
 * This file contains functions that BP Docs to integrate into the BuddyPress Groups component.
 * That includes:
 *   - a class that filters default values to be group-specific etc (BP_Docs_Groups_Integration)
 *   - an implementation of the BP Groups Extension API, for hooking into group nav, etc *   
 *     (BP_Docs_Group_Extension)
 *   - template tags that are specific to the groups component
 *
 * @package BuddyPress Docs
 * @since 1.0
 */

/**
 * This class filters a number of key values from BP Docs's core to work in BP groups
 *
 * Most of the methods in this class filter the output of dummy methods in the BP_Query class,
 * providing values that are group-specific. Things have been done this way to allow for future
 * integration with different kinds of BP items, like users.
 *
 * @package BuddyPress Docs
 * @since 1.0
 */
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
		
		// Filter the core user_can_edit function for group-specific functionality
		add_filter( 'bp_docs_user_can',			array( $this, 'user_can' ), 10, 3 );
		
		// Add group-specific settings to the doc settings box
		add_filter( 'bp_docs_doc_settings_markup',	array( $this, 'doc_settings_markup' ) );
	}
	
	/**
	 * Check to see whether the query object's item type should be 'groups'
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @param str $type
	 * @return str $type
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
			} else if ( !empty( $bp->action_variables[1] ) && $bp->action_variables[1] == BP_DOCS_DELETE_SLUG ) {
				// This is an edit page
				$view = 'delete';
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
	 * @param bool $user_can The default perms passed from bp_docs_user_can_edit()
	 * @param str $action At the moment, 'edit' or 'manage'
	 * @param int $user_id The user id whose perms are being tested
	 */	
	function user_can( $user_can, $action, $user_id ) {
		global $bp, $post;
		
		// Sometimes the post hasn't been loaded early enough, groan
		if ( empty( $post->ID ) ) {
			$posts = get_posts( array( 'post_type' => $bp->bp_docs->post_type_name, 'name' => $bp->bp_docs->doc_slug ) );
			$post = $posts[0];
		}
		
		$doc_settings = get_post_meta( get_the_ID(), 'bp_docs_settings', true );
		
		$group_id =  $bp->groups->current_group->id;
		
		// Group admins and mods always get to edit
		if ( groups_is_user_admin( $user_id, $group_id ) || groups_is_user_mod( $user_id, $group_id ) ) {
			$user_can = true;
		} else {			
			switch ( $doc_settings[$action] ) {
				case 'me' :
					if ( get_the_author_meta( 'ID' ) == $user_id )
						$user_can = true;
					break;
				
				case 'group-members' :
				default :
					if ( groups_is_user_member( $user_id, $bp->groups->current_group->id ) )
						$user_can = true;
					break;
			}
		}
		
		return $user_can;
	}
	
	function doc_settings_markup( $doc_settings ) {
		// Only add these settings if we're in the group component
		
		// BP 1.2/1.3 compatibility
		$is_group_component = function_exists( 'bp_is_current_component' ) ? bp_is_current_component( 'groups' ) : $bp->current_component == $bp->groups->slug;
		
		if ( $is_group_component ) {
			$edit = !empty( $doc_settings['edit'] ) ? $doc_settings['edit'] : 'group-members';
			$manage = !empty( $doc_settings['manage'] ) ? $doc_settings['manage'] : 'me';
		
			?>
			<label for="settings[edit]"><?php _e( 'Allow the following members to edit this doc:', 'bp-docs' ) ?></label>
			
			<input name="settings[edit]" type="radio" value="me" <?php checked( $edit, 'me' ) ?>/> <?php _e( 'Just me', 'bp-docs' ) ?><br />
			<input name="settings[edit]" type="radio" value="group-members" <?php checked( $edit, 'group-members' ) ?>/> <?php _e( 'All members of the group', 'bp-docs' ) ?><br />
			
			<?php /* Not sure this is necessary, so leaving out for the moment */ ?>
			<?php /*
			
			<label for="settings[manage]"><?php _e( 'Allow the following members to manage this doc:', 'bp-docs' ) ?></label>
			
			<input name="settings[manage]" type="radio" value="me" <?php checked( $manage, 'me' ) ?>/> <?php _e( 'Just me', 'bp-docs' ) ?><br />
			<input name="settings[manage]" type="radio" value="group-members" <?php checked( $manage, 'group-members' ) ?>/> <?php _e( 'All members of the group', 'bp-docs' ) ?><br />
			<span class="description"><?php _e( '"Managing" users can change doc settings and delete the doc.', 'bp-docs' ) ?></span><br /><br />
			
			<span class="description"><?php _e( '<strong>Note:</strong> Group admins and mods, as well as site administrators, can edit and manage docs regardless of settings.', 'bp-docs' ) ?></span>
			
			*/ ?>
			
			<?php
		}
	}
}


/**
 * Implementation of BP_Group_Extension
 *
 * @package BuddyPress Docs
 * @since 1.0
 */
class BP_Docs_Group_Extension extends BP_Group_Extension {

	var $group_enable;
	var $settings;
	
	var $visibility;
	var $enable_nav_item;
	var $enable_create_step;
	
	// This is so I can get a reliable group id even during group creation
	var $maybe_group_id;

	/**
	 * Constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function bp_docs_group_extension() {
		global $bp;
		
		$this->name 			= __( 'Docs', 'bp-docs' );
		$this->slug 			= BP_DOCS_SLUG;

		$this->enable_create_step	= true;
		$this->create_step_position 	= 45;
		$this->nav_item_position 	= 45;
		
		if ( !empty( $bp->groups->current_group->id ) )
			$this->maybe_group_id	= $bp->groups->current_group->id;
		else if ( !empty( $bp->groups->new_group_id ) )
			$this->maybe_group_id	= $bp->groups->new_group_id;
		else
			$this->maybe_group_id	= false;
			
		// Load the bp-docs setting for the group, for easy access
		$this->settings			= groups_get_groupmeta( $this->maybe_group_id, 'bp-docs' );
		$this->group_enable		= !empty( $this->settings['group-enable'] ) ? true : false;
		
		$this->visibility		= 'public';
		$this->enable_nav_item		= $this->enable_nav_item();
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
		
		$this->admin_markup();
		
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

		$settings = !empty( $_POST['bp-docs'] ) ? $_POST['bp-docs'] : array();
		groups_update_groupmeta( $bp->groups->new_group_id, 'bp-docs', $settings );
	}

	/**
	 * Determines what shows up on the BP Docs panel of the Group Admin
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function edit_screen() {
		if ( !bp_is_group_admin_screen( $this->slug ) )
			return false; 
		
		$this->admin_markup();
		
		// On the edit screen, we have to provide a save button
		?>
		<p>
			<input type="submit" value="<?php _e( 'Save Changes', 'bp-docs' ) ?>" id="save" name="save" />
		</p>
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

		$success = false;

		$settings = !empty( $_POST['bp-docs'] ) ? $_POST['bp-docs'] : array();

		if ( groups_update_groupmeta( $this->maybe_group_id, 'bp-docs', $settings ) )
			$success = true;

		/* To post an error/success message to the screen, use the following */
		if ( !$success )
			bp_core_add_message( __( 'There was an error saving, please try again', 'buddypress' ), 'error' );
		else
			bp_core_add_message( __( 'Settings saved successfully', 'buddypress' ) );

		bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/' . $this->slug );
	}

	/**
	 * Admin markup used on the edit and create admin panels
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function admin_markup() {
		$settings 	= groups_get_groupmeta( $this->maybe_group_id, 'bp-docs' );
		
		$group_enable 	= empty( $settings['group-enable'] ) ? false : true;
		
		?>
		
		<h2><?php _e( 'BuddyPress Docs', 'bp-docs' ) ?></h2>
		
		<p><?php _e( 'BuddyPress Docs is a powerful tool for collaboration with members of your group. A cross between document editor and wiki, BuddyPress Docs allows you to co-author and co-edit documents with your fellow group members, which you can then sort and tag in a way that helps your group to get work done.', 'bp-docs' ) ?></p>
		
		<p>
			 <label for="bp-docs[group-enable]"> <input type="checkbox" name="bp-docs[group-enable]" id="bp-docs-group-enable" value="1" <?php checked( $group_enable, true ) ?> /> Enable BuddyPress Docs for this group</label>
		</p>
		
		<div id="group-doc-options" <?php if ( !$group_enable ) : ?>class="hidden"<?php endif ?>>
			<?php /* Disabled until I get a chance to build options */ ?>
		</div>
		
		<?php
	}

	/**
	 * Determine whether the group nav item should show up for the current user
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function enable_nav_item() {
		global $bp;
		
		$enable_nav_item = false;
		
		// The nav item should only be enabled when BP Docs is enabled for the group
		if ( $this->group_enable ) {
			if ( !empty( $bp->groups->current_group->status ) && $status = $bp->groups->current_group->status ) {
				// Docs in public groups are publicly viewable.
				if ( 'public' == $status ) {
					$enable_nav_item = true;
				} else if ( groups_is_user_member( bp_loggedin_user_id(), $bp->groups->current_group->id ) ) {
					// Docs in private or hidden groups visible only to members
					$enable_nav_item = true;
				}
			}
			
			// Super admin override
			if ( is_super_admin() )
				$enable_nav_item = true;
		}			

		return apply_filters( 'bp_docs_groups_enable_nav_item', $enable_nav_item );
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


/**************************
 * TEMPLATE TAGS
 **************************/
 
/**
 * Builds the subnav for the Docs group tab
 *
 * This method is copied from bp_group_admin_tabs(), which itself is a hack for the fact that BP
 * has no native way to register subnav items on a group tab. Component subnavs (for user docs) will
 * be properly registered with bp_core_new_subnav_item()
 *
 * @package BuddyPress Docs
 * @since 1.0
 *
 * @param obj $group optional The BP group object.
 */
function bp_docs_group_tabs( $group = false ) {
	global $bp, $groups_template, $post, $bp_version;
	
	if ( !$group )
		$group = ( $groups_template->group ) ? $groups_template->group : $bp->groups->current_group;
	
	// BP 1.2 - 1.3 support
	$groups_slug = !empty( $bp->groups->root_slug ) ? $bp->groups->root_slug : $bp->groups->slug;

?>
	<li<?php if ( $bp->bp_docs->current_view == 'list' ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->root_domain . '/' . $groups_slug ?>/<?php echo $group->slug ?>/<?php echo $bp->bp_docs->slug ?>/"><?php _e( 'View Docs', 'bp-docs' ) ?></a></li>

	<?php /* Todo: can this user create items? */ ?>
	<li<?php if ( 'create' == $bp->bp_docs->current_view ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->root_domain . '/' . $groups_slug ?>/<?php echo $group->slug ?>/<?php echo $bp->bp_docs->slug ?>/create"><?php _e( 'New Doc', 'bp-docs' ) ?></a></li>
	
	
	<?php if ( $bp->bp_docs->current_view == 'single' || $bp->bp_docs->current_view == 'edit' ) : ?>
		<li<?php if ( 'single' == $bp->bp_docs->current_view ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->root_domain . '/' . $groups_slug ?>/<?php echo $group->slug ?>/<?php echo $bp->bp_docs->slug ?>/<?php echo $post->post_name ?>"><?php the_title() ?></a></li>		
	<?php endif ?>
	
<?php
}

/**
 * Echoes the output of bp_docs_get_group_doc_permalink()
 *
 * @package BuddyPress Docs
 * @since 1.0
 */
function bp_docs_group_doc_permalink() {
	echo bp_docs_get_group_doc_permalink();
}
	/**
	 * Returns a link to a specific document in a group
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @param int $doc_id optional The post_id of the doc
	 * @return str Permalink for the group doc
	 */
	function bp_docs_get_group_doc_permalink( $doc_id = false ) {
		global $post, $bp;
		
		$group			= $bp->groups->current_group;
		$group_permalink 	= bp_get_group_permalink( $group );

		if ( $doc_id )
			$post = get_post( $doc_id );

		if ( !empty( $post->post_name ) )
			$doc_slug = $post->post_name;
		else
			return false;
			
		return apply_filters( 'bp_docs_get_doc_permalink', $group_permalink . $bp->bp_docs->slug . '/' . $doc_slug );
	}


?>