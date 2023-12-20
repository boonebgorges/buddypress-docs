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
 * This function does two different things, depending on whether you're using BP
 * 1.7's theme compatibility feature.
 *  - If so, the function runs the 'bp_setup_theme_compat' hook, which tells BP
 *    to run the theme compat layer
 *  - If not, the function checks to see which page you intend to be looking at
 *    and loads the correct top-level bp-docs template
 *
 * The theme compatibility feature kicks in automatically for users running BP
 * 1.7+. If you are running 1.7+, but you do not want theme compat running for
 * a given Docs template type (archive, single, create), you can filter
 * 'bp_docs_do_theme_compat' and return false. This should only be done in the
 * case of legacy templates; if you're customizing new top-level templates for
 * Docs, you may put a file called plugin-buddypress-docs.php into the root of
 * your theme.
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

	$do_theme_compat = bp_docs_do_theme_compat();

	if ( $do_theme_compat ) {

		do_action( 'bp_setup_theme_compat' );

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
 * Should we do theme compatibility?
 *
 * Do it whenever it's available in BuddyPress (whether enabled or not for the
 * theme more generally)
 *
 * @since 1.5.6
 *
 * @return bool
 */
function bp_docs_do_theme_compat( $template = false ) {
	if ( ! class_exists( 'BP_Theme_Compat' ) ) {
		return false;
	}

	// Pre-theme-compat templates are not available for user tabs, so we
	// force theme compat in these cases
	if ( bp_is_user() ) {
		return true;
	}

	return apply_filters( 'bp_docs_do_theme_compat', true, $template );
}

/**
 * Tell BP to enqueue its 'community' assets.
 *
 * Since version 12.0, BuddyPress does not enqueue its CSS and JS assets on
 * non-BuddyPress pages. This includes CPT pages like those used by Docs.
 *
 * @since 2.2.0
 *
 * @param bool $enqueue Whether to enqueue the assets.
 * @return bool
 */
function bp_docs_enqueue_community_assets( $enqueue ) {
	if ( bp_docs_is_docs_component() ) {
		// Return `false` because the filter asks whether assets should *only* be
		// enqueued on BP pages. We want to say no, enqueue them elsewhere too.
		return false;
	}

	return $enqueue;
}
add_filter( 'bp_enqueue_assets_in_bp_pages_only', 'bp_docs_enqueue_community_assets' );

/**
 * Theme Compat
 *
 * @since 1.3
 */
class BP_Docs_Theme_Compat {
	/**
	 * Single content template.
	 *
	 * @var string
	 */
	public $single_content_template;

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

		$is_docs = bp_docs_is_docs_component();

		if ( bp_is_active( 'groups' ) && bp_is_group() && bp_is_current_action( buddypress()->bp_docs->slug ) ) {
			$is_docs = true;
		}

		// Bail if not looking at the docs component
		if ( ! $is_docs ) {
			return;
		}

		add_filter( 'bp_get_template_stack', array( $this, 'add_plugin_templates_to_stack' ) );

		add_filter( 'bp_get_buddypress_template', array( $this, 'query_templates' ) );

		add_filter( 'bp_use_theme_compat_with_current_theme', 'bp_docs_do_theme_compat' );

		if ( bp_docs_is_global_directory() || bp_docs_is_mygroups_directory() ) {

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

				// Necessary as of BP 1.9.2
				remove_action( 'bp_replace_the_content', 'bp_theme_compat_toggle_is_page', 9999 );
			}

			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'single_dummy_post' ) );
			add_filter( 'bp_replace_the_content',                    array( $this, 'single_content'    ) );

		} else if ( bp_docs_is_doc_create() ) {
			add_action( 'bp_template_include_reset_dummy_post_data', array( $this, 'create_dummy_post' ) );
			add_filter( 'bp_replace_the_content',                    array( $this, 'create_content'    ) );
		}

		/**
		 * Fires after the BuddyPress Docs theme compatibility layer has initialized.
		 *
		 * @since 1.9.4
		 *
		 * @param BP_Docs_Theme_Compat $theme_compat
		 */
		do_action( 'bp_docs_setup_theme_compat', $this );
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

	/**
	 * Add our custom top-level query template to the top of the query
	 * template stack
	 *
	 * This ensures that users can provide a Docs-specific template at the
	 * top-level of the rendering stack
	 *
	 * @since 1.3
	 */
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
		bp_docs_theme_compat_reset_post( 'directory' );
	}

	/**
	 * Filter the_content with the docs index template part
	 *
	 * @since 1.3
	 */
	public function directory_content() {
		return bp_buffer_template_part( 'docs/docs-loop', null, false );
	}

	/** Single ****************************************************************/

	/**
	 * We're not setting a dummy post for our post type, but we do need to
	 * activate theme compat
	 *
	 * @todo This seems very wrong. Figure it out
	 *
	 * @since 1.3
	 */
	public function single_dummy_post() {
		bp_set_theme_compat_active();
	}

	/**
	 * Filter the_content with the single doc template part
	 *
	 * @since 1.3
	 */
	public function single_content() {
		return bp_buffer_template_part( $this->single_content_template, null, false );
	}

	/** Create ****************************************************************/

	/**
	 * Update the global $post with dummy data regarding doc creation
	 *
	 * @since 1.3
	 */
	public function create_dummy_post() {
		bp_docs_theme_compat_reset_post( 'create' );
	}

	/**
	 * Filter the_content with the doc creation template part
	 *
	 * @since 1.3
	 */
	public function create_content() {
		return bp_buffer_template_part( 'docs/single/edit', null, false );
	}
}
new BP_Docs_Theme_Compat();

/**
 * Resets the global $post object to a dummy post.
 *
 * @since 2.2.1
 *
 * @param string $type 'create' or 'directory'.
 * @return void
 */
function bp_docs_theme_compat_reset_post( $type ) {
	$post_args = [
		'ID'             => 0,
		'post_date'      => 0,
		'post_content'   => '',
		'post_type'      => bp_docs_get_post_type_name(),
		'post_status'    => 'publish',
		'is_archive'     => true,
		'comment_status' => 'closed'
	];

	if ( 'create' === $type ) {
		$post_args['post_title']  = __( 'Create a Doc', 'buddypress-docs' );
		$post_args['post_author'] = get_current_user_id();
	} elseif ( 'directory' === $type ) {
		$post_args['post_title']  = bp_docs_get_docs_directory_title();
		$post_args['post_author'] = 0;
	}

	bp_theme_compat_reset_post( $post_args );
}

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

/**
 * Provides a stub block template for the Docs directory and create pages.
 *
 * On block themes, the Docs directory and create pages are powered by the
 * archive.html template. In many block themes, including WP default themes,
 * archive.html shows only an excerpt from the posts, rather than the full
 * content. This breaks our theme compatibility technique, which requires
 * that the untruncated content returned by the 'the_content' filter be
 * displayed on the CPT archive.
 *
 * As a workaround, and to provide maximal compatibility with the rest of the
 * theme, we detect whether the archive template shows only excerpts. If so,
 * we provide a "stub" template that is a copy of the archive template, but
 * with the wp:post-excerpt block replaced by wp:post-content.
 *
 * @since 2.2.1
 *
 * @param array $templates Array of block templates.
 * @param array $query     Query arguments.
 * @return array
 */
function bp_docs_provide_block_template_for_docs_content( $templates, $query ) {
	// We are only concerned with the Docs directory, ie the bp_doc archive.
	if ( empty( $query['slug__in'] ) || ! in_array( 'archive-bp_doc', $query['slug__in'], true ) ) {
		return $templates;
	}

	// If an archive-bp_doc template was found, use it.
	$has_archive_bp_doc_template = false;
	foreach ( $templates as $template ) {
		if ( 'archive-bp_doc' === $template->slug ) {
			$has_archive_bp_doc_template = true;
			return $templates;
		}
	}

	if ( bp_docs_is_doc_create() ) {
		bp_docs_theme_compat_reset_post( 'create' );
	} else {
		bp_docs_theme_compat_reset_post( 'directory' );
	}

	// Render the top template.
	$rendered = do_blocks( $templates[0]->content );

	// If the rendered HTML contains a .bp-docs-container element, no need for further processing
	if ( false !== strpos( $rendered, 'bp-docs-container' ) ) {
		return $templates;
	}

	// We will be targeting post excerpt blocks. Bail early if none are found in the markup.
	if ( false === strpos( $rendered, 'wp-block-post-excerpt' ) ) {
		return $templates;
	}

	// Find the most deeply nested block whose rendered content contains a post excerpt block.
	$blocks                   = parse_blocks( $templates[0]->content );
	$block_containing_excerpt = bp_docs_find_closest_ancestor_of_excerpt( $blocks );

	if ( null === $block_containing_excerpt ) {
		return $templates;
	}

	$new_template_content = '';
	if ( 'core/post-excerpt' === $block_containing_excerpt['blockName'] ) {
		$new_template_content = str_replace( 'wp:post-excerpt', 'wp:post-content', $templates[0]->content );
	} elseif ( 'core/pattern' === $block_containing_excerpt['blockName'] ) {
		/*
		 * Some themes use a pattern to render the archive's post-template.
		 * Expand the pattern and check for a post-excerpt there.
		 */
		$registry = WP_Block_Patterns_Registry::get_instance();
		$pattern  = $registry->get_registered( $block_containing_excerpt['attrs']['slug'] );

		if ( $pattern ) {
			$new_pattern_content = str_replace( 'wp:post-excerpt', 'wp:post-content', $pattern['content'] );
			$new_template_content = str_replace( serialize_block( $block_containing_excerpt ), $new_pattern_content, $templates[0]->content );
		}
	}

	if ( $new_template_content ) {
		$new_template          = clone $templates[0];
		$new_template->slug    = 'archive-bp_doc';
		$new_template->title   = __( 'Docs Directory', 'buddypress-docs' );
		$new_template->content = $new_template_content;

		// Add the new template to the top of the list.
		array_unshift( $templates, $new_template );
	}

	return $templates;
}
add_filter( 'get_block_templates', 'bp_docs_provide_block_template_for_docs_content', 10, 2 );

/**
 * Finds the most deeply nested block whose rendered content contains a post excerpt block.
 *
 * @since 2.2.1
 *
 * @param array $blocks Array of blocks.
 * @return array|null
 */
function bp_docs_find_closest_ancestor_of_excerpt( $blocks ) {
	foreach ( $blocks as $block ) {
		$rendered = render_block( $block );

		if ( false !== strpos( $rendered, 'wp-block-post-excerpt' ) ) {
			// If the block contains 'post-excerpt' and has no inner blocks,
            // it is the closest ancestor.
            if ( empty( $block['innerBlocks'] ) ) {
                return $block;
            }

            // If the block has inner blocks, recursively search them.
            $innerAncestor = bp_docs_find_closest_ancestor_of_excerpt( $block['innerBlocks'] );
            if ( null !== $innerAncestor ) {
                return $innerAncestor;
            }

            // If no inner block is a valid ancestor, return the current block.
            return $block;
        }
	}

	return null;
}
