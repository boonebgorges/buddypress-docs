<?php

class BP_Docs_BP_Integration {	
	/**
	 * PHP 4 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function bp_docs_bp_integration() {
		$this->__construct();
	}

	/**
	 * PHP 5 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */	
	function __construct() {
		add_action( 'bp_loaded', array( $this, 'do_query' ) );
		
		add_action( 'bp_setup_globals', array( $this, 'setup_globals' ) );
		
		// Todo: Only fire this if you actually need it for a given group
		bp_register_group_extension( 'BP_Docs_Group_Extension' );
		
		add_action( 'wp', array( $this, 'catch_form_submits' ), 1 );
		
		add_action( 'wp_print_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_print_styles', array( $this, 'enqueue_styles' ) );
	}
	
	function do_query() {
		$this->query = new BP_Docs_Query;
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
	
		
	/**
	 * Catches incoming form submits and sends them on their merry way
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function catch_form_submits() {
		global $bp;
		
		if ( !empty( $_POST['doc-edit-submit'] ) ) {
			$this_doc = new BP_Docs_Query;
			$this_doc->save();
			//print_r( $this_doc ); die();
		}
	}
	
	/**
	 * Loads JavaScript
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */	
	function enqueue_scripts() {
		
		if ( !empty( $this->query->current_view ) && ( 'edit' == $this->query->current_view || 'create' == $this->query->current_view ) ) {
			require_once( ABSPATH . '/wp-admin/includes/post.php' );
			wp_enqueue_script( 'common' );
			wp_enqueue_script( 'jquery-color' );
			//wp_print_scripts('editor');
			if (function_exists('add_thickbox')) add_thickbox();
			//wp_print_scripts('media-upload');
			wp_admin_css();
			wp_enqueue_script('utils');
		}
	}
	
	function enqueue_styles() {
		wp_enqueue_style('thickbox');
	}
}

// Todo: this should probably be separated into a groups-only file
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