<?php

class BP_Docs_BP_Integration {
	/**
	 * PHP 4 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function bp_docs() {
		$this->construct();
	}

	/**
	 * PHP 5 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */	
	function __construct() {
		add_action( 'bp_setup_globals', array( $this, 'setup_globals' ) );
		
		// Todo: Only fire this if you actually need it for a given group
		bp_register_group_extension( 'BP_Docs_Group_Extension' );
	}
	
	/**
	 * Stores some handy information in the $bp global
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */	
	function setup_globals() {
		global $bp;
		
		$bp->bp_docs->format_notification_function = 'bp_docs_format_notifications';
		$bp->bp_docs->slug = BP_DOCS_SLUG;
	
		// Todo: You only need this if you need top level access: example.com/docs
		/* Register this in the active components array */
		//$bp->active_components[ $bp->wiki->slug ] = $bp->wiki->id;
	}
}

class BP_Docs_Group_Extension extends BP_Group_Extension {	

	var $visibility = 'public';
	var $enable_nav_item = true;

	function bp_docs_group_extension() {
		$this->name = 'My Group Extension';
		$this->slug = 'my-group-extension';

		$this->create_step_position = 21;
		$this->nav_item_position = 31;
	}

	function create_screen() {
		if ( !bp_is_group_creation_step( $this->slug ) )
			return false;
		?>

		<p>The HTML for my creation step goes here.</p>

		<?php
		wp_nonce_field( 'groups_create_save_' . $this->slug );
	}

	function create_screen_save() {
		global $bp;

		check_admin_referer( 'groups_create_save_' . $this->slug );

		/* Save any details submitted here */
		groups_update_groupmeta( $bp->groups->new_group_id, 'my_meta_name', 'value' );
	}

	function edit_screen() {
		if ( !bp_is_group_admin_screen( $this->slug ) )
			return false; ?>

		<h2><?php echo attribute_escape( $this->name ) ?></h2>

		<p>Edit steps here</p>
		<input type=&quot;submit&quot; name=&quot;save&quot; value=&quot;Save&quot; />

		<?php
		wp_nonce_field( 'groups_edit_save_' . $this->slug );
	}

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

	function display() {
		/* Use this function to display the actual content of your group extension when the nav item is selected */
	}

	function widget_display() { ?>
		<div class=&quot;info-group&quot;>
			<h4><?php echo attribute_escape( $this->name ) ?></h4>
			<p>
				You could display a small snippet of information from your group extension here. It will show on the group
				home screen.
			</p>
		</div>
		<?php
	}
}

?>