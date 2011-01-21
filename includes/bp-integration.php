<?php

class BP_Docs_BP_Integration {	
	var $includes_url;
	
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
		add_action( 'bp_init', array( $this, 'do_query' ), 90 );
		
		add_action( 'bp_setup_globals', array( $this, 'setup_globals' ) );
		
		if ( bp_is_active( 'groups' ) ) {
			require_once( BP_DOCS_INCLUDES_PATH . 'groups-integration.php' );
			$this->groups_integration = new BP_Docs_Groups_Integration;
		}
		
		add_action( 'wp', array( $this, 'catch_form_submits' ), 1 );
		
		add_action( 'bp_loaded', array( $this, 'set_includes_url' ) );
		add_action( 'init', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_print_styles', array( $this, 'enqueue_styles' ) );
	}
	
	/**
	 * Loads the Docs query.
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
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
	 * Sets the includes URL for use when loading scripts and styles
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function set_includes_url() {
		$this->includes_url = plugins_url() . '/buddypress-docs/includes/';
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
	
	/**
	 * Loads styles
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function enqueue_styles() {
		if ( !empty( $this->query->current_view ) && ( 'edit' == $this->query->current_view || 'create' == $this->query->current_view ) ) {
			wp_enqueue_style('thickbox');
			wp_enqueue_style( 'bpd-edit-css', $this->includes_url . 'css' . DIRECTORY_SEPARATOR . 'edit.css' );
		}
	}
}

?>