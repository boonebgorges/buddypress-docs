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
 * @since 1.0-beta
 */

/**
 * This class filters a number of key values from BP Docs's core to work in BP groups
 *
 * Most of the methods in this class filter the output of dummy methods in the BP_Query class,
 * providing values that are group-specific. Things have been done this way to allow for future
 * integration with different kinds of BP items, like users.
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 */
class BP_Docs_Groups_Integration {
	/**
	 * PHP 4 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function bp_docs_groups_integration() {
		$this->__construct();
	}

	/**
	 * PHP 5 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
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
		
		// Filter the activity actions for group docs-related activity
		add_filter( 'bp_docs_activity_action',		array( $this, 'activity_action' ), 10, 5 );
		add_filter( 'bp_docs_comment_activity_action',	array( $this, 'comment_activity_action' ), 10, 5 );
		
		// Filter the activity hide_sitewide parameter to respect group privacy levels
		add_filter( 'bp_docs_hide_sitewide',		array( $this, 'hide_sitewide' ) );
	}
	
	/**
	 * Check to see whether the query object's item type should be 'groups'
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
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
	 * @since 1.0-beta
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
	 * @since 1.0-beta
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
	 * @since 1.0-beta
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
	 * @since 1.0-beta
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
	 * @since 1.0-beta
	 *
	 * @param bool $user_can The default perms passed from bp_docs_user_can_edit()
	 * @param str $action At the moment, 'edit', 'manage', 'create'
	 * @param int $user_id The user id whose perms are being tested
	 */	
	function user_can( $user_can, $action, $user_id ) {
		global $bp, $post;
		
		$user_can = false;
		
		if ( empty( $post->ID ) )
			$post = !empty( $bp->bp_docs->current_post ) ? $bp->bp_docs->current_post : false;
		
		if ( !empty( $post->ID ) ) {
			$doc_settings = get_post_meta( $post->ID, 'bp_docs_settings', true );
	
			// Manage settings don't always get set on doc creation, so we need a default
			if ( empty( $doc_settings['manage'] ) )
				$doc_settings['manage'] = 'creator';
		}

		$group_id =  $bp->groups->current_group->id;
		
		switch ( $action ) {
			case 'create' :
				$group_settings = groups_get_groupmeta( $group_id, 'bp-docs' );
				
				if ( !empty( $group_settings['can-create'] ) ) {
					switch ( $group_settings['can-create'] ) {
						case 'admin' :
							if ( groups_is_user_admin( $user_id, $group_id ) )
								$user_can = true;
							break;
						case 'mod' :
							if ( groups_is_user_mod( $user_id, $group_id ) )
								$user_can = true;
							break;
						case 'member' :
						default :
							if ( groups_is_user_member( $user_id, $group_id ) )
								$user_can = true;
							break;
					}
				}
				
				break;
				
			case 'edit' : 
			default :
				// Group admins and mods always get to edit
				if ( groups_is_user_admin( $user_id, $group_id ) || groups_is_user_mod( $user_id, $group_id ) ) {
					$user_can = true;
				} else {
					switch ( $doc_settings[$action] ) {
						case 'anyone' :
							$user_can = true;
							break;
							
						case 'creator' :
							if ( $post->post_author == $user_id )
								$user_can = true;
							break;
						
						case 'group-members' :
							if ( groups_is_user_member( $user_id, $bp->groups->current_group->id ) )
								$user_can = true;
							break;
						
						case 'no-one' :
						default :
							break; // In other words, other types return false
					}
				}
				
				break;
		}
		
		return $user_can;
	}
	
	/**
	 * Creates the markup for the group-specific doc settings
	 *
	 * In the future I'll try to get the markup out of here. Sorry, themers.
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 *
	 * @param array $doc_settings Passed along to reduce lookups
	 */	
	function doc_settings_markup( $doc_settings ) {
		global $bp;
		
		// Only add these settings if we're in the group component
		
		// BP 1.2/1.3 compatibility
		$is_group_component = function_exists( 'bp_is_current_component' ) ? bp_is_current_component( 'groups' ) : $bp->current_component == $bp->groups->slug;
		
		if ( $is_group_component ) {
			// Get the current values
			$edit 		= !empty( $doc_settings['edit'] ) ? $doc_settings['edit'] : 'group-members';
			
			$post_comments 	= !empty( $doc_settings['post_comments'] ) ? $doc_settings['post_comments'] : 'group-members';
			
			// Read settings have a different default value for public groups
			if ( !empty( $doc_settings['read_comments'] ) ) {
				$read_comments = $doc_settings['read_comments'];
			} else {
				$read_comments = bp_group_is_visible() ? 'anyone' : 'group-members';
			}
			
			$manage 	= !empty( $doc_settings['manage'] ) ? $doc_settings['manage'] : 'creator';
			
			// Set the text of the 'creator only' label
			if ( !empty( $bp->bp_docs->current_post->post_author ) && $bp->bp_docs->current_post->post_author != bp_loggedin_user_id() ) {
				$creator_text = sprintf( __( 'Doc creator only (%s)', 'bp-docs' ), bp_core_get_user_displayname( $bp->bp_docs->current_post->post_author ) );
			} else {
				$creator_text = __( 'Doc creator only (that\'s you!)', 'bp-docs' );
			}
		
			?>
			
			<?php /* EDITING */ ?>
			<tr>
				<td class="desc-column">
					<label for="settings[edit]"><?php _e( 'Who can edit this doc?', 'bp-docs' ) ?></label>
				</td>
				
				<td class="content-column">
					<input name="settings[edit]" type="radio" value="group-members" <?php checked( $edit, 'group-members' ) ?>/> <?php _e( 'All members of the group', 'bp-docs' ) ?><br />
			
					<input name="settings[edit]" type="radio" value="creator" <?php checked( $edit, 'creator' ) ?>/> <?php echo esc_html( $creator_text ) ?><br />
			
					<?php if ( bp_group_is_admin() || bp_group_is_mod() ) : ?>
						<input name="settings[edit]" type="radio" value="admins-mods" <?php checked( $edit, 'admins-mods' ) ?>/> <?php _e( 'Only admins and mods of this group', 'bp-docs' ) ?><br />
					<?php endif ?>
				</td>
			</tr>
			
			<?php /* POSTING COMMENTS */ ?>
			<tr>
				<td class="desc-column">
					<label for="settings[post_comments]"><?php _e( 'Who can <em>post</em> comments on this doc?', 'bp-docs' ) ?></label>
				</td>
				
				<td class="content-column">
					<input name="settings[post_comments]" type="radio" value="group-members" <?php checked( $post_comments, 'group-members' ) ?>/> <?php _e( 'All members of the group', 'bp-docs' ) ?><br />
					
					<?php if ( bp_group_is_admin() || bp_group_is_mod() ) : ?>
						<input name="settings[post_comments]" type="radio" value="admins-mods" <?php checked( $post_comments, 'admins-mods' ) ?>/> <?php _e( 'Only admins and mods of this group', 'bp-docs' ) ?><br />
					<?php endif ?>
					
					<input name="settings[post_comments]" type="radio" value="no-one" <?php checked( $post_comments, 'no-one' ) ?>/> <?php _e( 'No one', 'bp-docs' ) ?><br />
				</td>
			</tr>
			
			<?php /* READING COMMENTS */ ?>
			<tr>
				<td class="desc-column">
					<label for="settings[read_comments]"><?php _e( 'Who can <em>read</em> comments on this doc?', 'bp-docs' ) ?></label>
				</td>
				
				<td class="content-column">
					<?php if ( bp_docs_current_group_is_public() ) : ?>				
						<input name="settings[read_comments]" type="radio" value="anyone" <?php checked( $read_comments, 'anyone' ) ?>/> <?php _e( 'Anyone', 'bp-docs' ) ?><br />
					<?php endif ?>
					
					<input name="settings[read_comments]" type="radio" value="group-members" <?php checked( $read_comments, 'group-members' ) ?>/> <?php _e( 'All members of the group', 'bp-docs' ) ?><br />
					
					<?php if ( bp_group_is_admin() || bp_group_is_mod() ) : ?>
						<input name="settings[read_comments]" type="radio" value="admins-mods" <?php checked( $read_comments, 'admins-mods' ) ?>/> <?php _e( 'Only admins and mods of this group', 'bp-docs' ) ?><br />
					<?php endif ?>
					
					<input name="settings[read_comments]" type="radio" value="no-one" <?php checked( $read_comments, 'no-one' ) ?>/> <?php _e( 'No one', 'bp-docs' ) ?><br />
				</td>
			</tr>
			
			<?php
		}
	}
	
	/**
	 * Filters the activity action of 'doc created/edited' activity to include the group name
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 * 
	 * @param str $action The original action text created in BP_Docs_BP_Integration::post_activity()
	 * @param str $user_link An HTML link to the user profile of the editor
	 * @param str $doc_link An HTML link to the doc 
	 * @param bool $is_new_doc True if it's a newly created doc, false if it's an edit
	 * @param obj $query The query object from BP_Docs_Query
	 * @return str $action The filtered action text
	 */
	function activity_action( $action, $user_link, $doc_link, $is_new_doc, $query ) {
		if ( $query->item_type == 'group' ) {
			$group 		= new BP_Groups_Group( $query->item_id );
			$group_url	= bp_get_group_permalink( $group );
			$group_link	= '<a href="' . $group_url . '">' . $group->name . '</a>';
			
			if ( $is_new_doc ) {
				$action = sprintf( __( '%1$s created the doc %2$s in the group %3$s', 'bp-docs' ), $user_link, $doc_link, $group_link );
			} else {
				$action = sprintf( __( '%1$s edited the doc %2$s in the group %3$s', 'bp-docs' ), $user_link, $doc_link, $group_link );
			}
		}
		
		return $action;
	}
	
	
	/**
	 * Filters the activity action of 'new doc comment' activity to include the group name
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 * 
	 * @param str $action The original action text created in BP_Docs_BP_Integration::post_activity()
	 * @param str $user_link An HTML link to the user profile of the editor
	 * @param str $comment_link An HTML link to the comment anchor in the doc
	 * @param str $component The canonical component name ('groups', 'profile', etc)
	 * @param int $item The id of the item (in this case, the group to which the doc belongs)
	 * @return str $action The filtered action text
	 */
	function comment_activity_action( $action, $user_link, $comment_link, $component, $item ) {
		if ( 'groups' == $component ) {
			$group		= new BP_Groups_Group( $item );
			$group_url	= bp_get_group_permalink( $group );
			$group_link	= '<a href="' . $group_url . '">' . $group->name . '</a>';
						
			$action 	= sprintf( __( '%1$s commented on the doc %2$s in the group %3$s', 'bp-docs' ), $user_link, $comment_link, $group_link );
		}
		
		return $action;
	}
	
	/**
	 * Filter the hide_sitewide variable to ensure that hidden/private group activity is hidden
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @param bool $hide_sitewide
	 * @return bool $hide_sitewide
	 */
	function hide_sitewide( $hide_sitewide ) {
		global $bp;
		
		$group_status = !empty( $bp->groups->current_group->status ) ? $bp->groups->current_group->status : 'public';
		
		// BuddyPress only supports three statuses by default. I'll err on the side of
		// caution, and let plugin authors use the filter provided.
		if ( 'public' != $group_status ) {
			$hide_sitewide = true;
		}
		
		return apply_filters( 'bp_docs_groups_hide_sitewide', $hide_sitewide, $group_status );
	}
}


/**
 * Implementation of BP_Group_Extension
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
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
	 * @since 1.0-beta
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
	 * @since 1.0-beta
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
	 * @since 1.0-beta
	 */
	
	function create_screen_save() {
		global $bp;
		
		check_admin_referer( 'groups_create_save_' . $this->slug );

		$success = $this->settings_save( $bp->groups->new_group_id );
	}

	/**
	 * Determines what shows up on the BP Docs panel of the Group Admin
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
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
	 * @since 1.0-beta
	 */
	function edit_screen_save() {
		global $bp;

		if ( !isset( $_POST['save'] ) )
			return false;

		check_admin_referer( 'groups_edit_save_' . $this->slug );

		$success = $this->settings_save();
		
		/* To post an error/success message to the screen, use the following */
		if ( !$success )
			bp_core_add_message( __( 'There was an error saving, please try again', 'buddypress' ), 'error' );
		else
			bp_core_add_message( __( 'Settings saved successfully', 'buddypress' ) );

		bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/' . $this->slug );
	}

	/**
	 * Saves group settings. Called from edit_screen_save() and create_screen_save()
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function settings_save( $group_id = false ) {
		$success = false;
		
		if ( !$group_id )
			$group_id = $this->maybe_group_id;
		
		$settings = !empty( $_POST['bp-docs'] ) ? $_POST['bp-docs'] : array();

		if ( groups_update_groupmeta( $group_id, 'bp-docs', $settings ) )
			$success = true;
			
		return $success;
	}

	/**
	 * Admin markup used on the edit and create admin panels
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function admin_markup() {
		
		if ( bp_is_group_creation_step( $this->slug ) ) {
			// Default settings
			$settings = array(
				'group-enable'	=> 0,
				'can-create' 	=> 'member'
			);
		} else {		
			$settings = groups_get_groupmeta( $this->maybe_group_id, 'bp-docs' );
		}
		
		$group_enable = empty( $settings['group-enable'] ) ? false : true;
		
		$can_create = empty( $settings['can-create'] ) ? false : $settings['can-create'];
		
		?>
		
		<h2><?php _e( 'BuddyPress Docs', 'bp-docs' ) ?></h2>
		
		<p><?php _e( 'BuddyPress Docs is a powerful tool for collaboration with members of your group. A cross between document editor and wiki, BuddyPress Docs allows you to co-author and co-edit documents with your fellow group members, which you can then sort and tag in a way that helps your group to get work done.', 'bp-docs' ) ?></p>
		
		<p>
			 <label for="bp-docs[group-enable]"> <input type="checkbox" name="bp-docs[group-enable]" id="bp-docs-group-enable" value="1" <?php checked( $group_enable, true ) ?> /> Enable BuddyPress Docs for this group</label>
		</p>
		
		<div id="group-doc-options" <?php if ( !$group_enable ) : ?>class="hidden"<?php endif ?>>
			<h3>Options</h3>
			
			<table class="group-docs-options">
				<tr>
					<td class="label">
						<label for="bp-docs[can-create-admins]"><?php _e( 'Minimum role to create new Docs:', 'bp-docs' ) ?>
					</td>
					
					<td>
						<select name="bp-docs[can-create]">
							<option value="admin" <?php selected( $can_create, 'admin' ) ?> /><?php _e( 'Group admin', 'bp-docs' ) ?></option>
							<option value="mod" <?php selected( $can_create, 'mod' ) ?> /><?php _e( 'Group moderator', 'bp-docs' ) ?></option>
							<option value="member" <?php selected( $can_create, 'member' ) ?> /><?php _e( 'Group member', 'bp-docs' ) ?></option>						
						</select>
					</td>
				</tr>
			
			</table>
		</div>
		
		<?php
	}

	/**
	 * Determine whether the group nav item should show up for the current user
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
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
	 * @since 1.0-beta
	 */
	function display() {
		global $bp_docs;
		
		$bp_docs->bp_integration->query->load_template();
	}

	/**
	 * Dummy function that must be overridden by this extending class, as per API
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
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
 * @since 1.0-beta
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

	<?php if ( bp_docs_current_user_can( 'create' ) ) : ?>
		<li<?php if ( 'create' == $bp->bp_docs->current_view ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->root_domain . '/' . $groups_slug ?>/<?php echo $group->slug ?>/<?php echo $bp->bp_docs->slug ?>/create"><?php _e( 'New Doc', 'bp-docs' ) ?></a></li>
	<?php endif ?>	
	
	<?php if ( $bp->bp_docs->current_view == 'single' || $bp->bp_docs->current_view == 'edit' ) : ?>
		<li class="current"><a href="<?php echo $bp->root_domain . '/' . $groups_slug ?>/<?php echo $group->slug ?>/<?php echo $bp->bp_docs->slug ?>/<?php echo $post->post_name ?>"><?php the_title() ?></a></li>		
	<?php endif ?>
	
<?php
}

/**
 * Echoes the output of bp_docs_get_group_doc_permalink()
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 */
function bp_docs_group_doc_permalink() {
	echo bp_docs_get_group_doc_permalink();
}
	/**
	 * Returns a link to a specific document in a group
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
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