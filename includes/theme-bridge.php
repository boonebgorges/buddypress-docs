<?php

/**
 * The functions in this file are used to load template files in the non-BP
 * sections of BP Docs
 *
 * Uses BP's theme compatibility layer, when it's available
 *
 * @since 1.2
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Possibly intercept the template being loaded
 *
 * Listens to the 'template_include' filter and waits for a BP Docs post_type
 * to appear. When one is found, we look to see whether the current theme provides
 * its own version of the template; otherwise we fall back on the template shipped
 * with BuddyPress Docs.
 *
 * @since 1.2
 *
 * @param string $template
 *
 * @return string The path to the template file that is being used
 */
function bp_docs_template_include( $template = '' ) {

	if ( ! bp_docs_is_docs_component() ) {
		return $template;
	}

	$do_theme_compat = class_exists( 'BP_Theme_Compat' ) && apply_filters( 'bp_docs_do_theme_compat', true, $template );

	if ( $do_theme_compat ) {

		do_action( 'bp_setup_theme_compat' );
		$template = str_replace( 'archive', 'page', $template );

	} else {

		if ( bp_docs_is_single_doc() && ( $new_template = bp_docs_locate_template( 'single-bp_doc.php' ) ) ) :

		elseif ( bp_docs_is_doc_create() && ( $new_template = bp_docs_locate_template( 'single-bp_doc.php' ) ) ) :

		elseif ( is_post_type_archive( bp_docs_get_post_type_name() ) && $new_template = bp_docs_locate_template( 'archive-bp_doc.php' ) ) :

		endif;

		$template = !empty( $new_template ) ? $new_template : $template;
	}

	return apply_filters( 'bp_docs_template_include', $template );
}
add_filter( 'template_include', 'bp_docs_template_include', 6 );

/**
 * Theme Compat
 *
 * @since 1.3
 */
class BP_Docs_Theme_Compat {

	/**
	 * Setup the members component theme compatibility
	 *
	 * @since 1.3
	 */
	public function __construct() {
		add_action( 'bp_setup_theme_compat', array( $this, 'is_docs' ) );
	}

	/**
	 * Are we looking at something that needs docs theme compatability?
	 *
	 * @since 1.3
	 */
	public function is_docs() {

		// Bail if not looking at the docs component
		if ( ! bp_docs_is_docs_component() )
			return;

		add_filter( 'bp_get_template_stack', array( $this, 'add_plugin_templates_to_stack' ) );

		add_filter( 'bp_get_buddypress_template', array( $this, 'query_templates' ) );

		if ( bp_docs_is_global_directory() ) {

			bp_update_is_directory( true, 'docs' );
			do_action( 'bp_docs_screen_index' );

			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'directory_dummy_post' ) );
			add_filter( 'bp_replace_the_content', array( $this, 'directory_content' ) );

		} else if ( bp_docs_is_existing_doc() ) {

			if ( bp_docs_is_doc_history() ) {
				$this->single_content_template = 'docs/single/history';
				add_filter( 'bp_force_comment_status', '__return_false' );
			} else if ( bp_docs_is_doc_edit() ) {
				$this->single_content_template = 'docs/single/edit';
				add_filter( 'bp_force_comment_status', '__return_false' );
			} else {
				$this->single_content_template = 'docs/single/index';
				add_filter( 'bp_docs_allow_comment_section', '__return_false' );
			}

			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'single_dummy_post' ) );
			add_filter( 'bp_replace_the_content',                    array( $this, 'single_content'    ) );

		} else if ( bp_docs_is_doc_create() ) {
			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'create_dummy_post' ) );
			add_filter( 'bp_replace_the_content',                    array( $this, 'create_content'    ) );
		}
	}

	/**
	 * Add the plugin's template location to the stack
	 *
	 * Docs provides its own templates for fallback support with any theme
	 *
	 * @since 1.3
	 */
	function add_plugin_templates_to_stack( $stack ) {
		$stack[] = BP_DOCS_INCLUDES_PATH . 'templates';
		return $stack;
	}

	function query_templates( $templates ) {
		$templates = array_merge( array( 'plugin-buddypress-docs.php' ), $templates );
		return $templates;
	}

	/** Directory *************************************************************/

	/**
	 * Update the global $post with directory data
	 *
	 * @since 1.3
	 */
	public function directory_dummy_post() {
		bp_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => __( 'BuddyPress Docs', 'buddypress' ),
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'bp_docs',
			'post_status'    => 'publish',
			'is_archive'     => true,
			'comment_status' => 'closed'
		) );
	}

	/**
	 * Filter the_content with the members index template part
	 *
	 * @since BuddyPress (1.7)
	 */
	public function directory_content() {
		bp_buffer_template_part( 'docs/docs-loop' );
	}

	/** Single ****************************************************************/

	/**
	 * Update the global $post with the displayed user's data
	 *
	 * @since BuddyPress (1.7)
	 */
	public function single_dummy_post() {
		bp_set_theme_compat_active();
	}

	/**
	 * Filter the_content with the members' single home template part
	 *
	 * @since BuddyPress (1.7)
	 */
	public function single_content() {
		bp_buffer_template_part( $this->single_content_template );
		return ' ';
	}

	/** Create ****************************************************************/

	/**
	 * Update the global $post with the displayed user's data
	 *
	 * @since BuddyPress (1.7)
	 */
	public function create_dummy_post() {
		bp_theme_compat_reset_post( array(
			'ID'             => 0,
			'post_title'     => __( 'Create a Doc', 'buddypress' ),
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'bp_docs',
			'post_status'    => 'publish',
			'is_archive'     => true,
			'comment_status' => 'closed'
		) );
	}

	/**
	 * Filter the_content with the members' single home template part
	 *
	 * @since BuddyPress (1.7)
	 */
	public function create_content() {
		bp_buffer_template_part( 'docs/single/edit' );
	}
}
new BP_Docs_Theme_Compat();

/**
 * Wrapper function for bp_is_theme_compat_active()
 *
 * Needed for backward compatibility with BP < 1.7
 *
 * @since 1.3
 * @return bool
 */
function bp_docs_is_theme_compat_active() {
	$is_active = false;

	if ( function_exists( 'bp_is_theme_compat_active' ) ) {
		$is_active = bp_is_theme_compat_active();
	}

	return $is_active;
}
