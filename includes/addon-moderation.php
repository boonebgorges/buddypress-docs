<?php

/**
 * Add a BP Docs-specific "pending" status to use in a moderation workflow.
 *
 * @since 2.1.0
 */
class BP_Docs_Moderation {

	public $pending_status = 'bp_docs_pending';

	/**
	 * Constructor.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
	}

	/**
	 * Add hooks for moderation functionality.
	 *
	 * @since 2.1.0
	 */
	public function add_hooks() {
		// Register a custom status that's a lot like the built-in "pending" status.
		add_action( 'bp_docs_init', array( $this, 'register_docs_pending_status' ) );

		add_filter( 'display_post_states', array( $this, 'add_moderated_label' ), 10, 2 );
	}

	/**
	 * Register custom status for pending BP Docs.
	 *
	 * @since 2.1.0
	 */
	public function register_docs_pending_status() {
		$args = array(
			'label'                     => _x( 'Awaiting Moderation', 'General name of pending doc status', 'buddypress-docs' ),
			'label_count'               => _n_noop( 'Awaiting Moderation (%s)',  'Awaiting Moderation (%s)', 'buddypress-docs' ),
			'public'                    => current_user_can( 'bp_moderate' ),
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'exclude_from_search'       => true,
		);
		register_post_status( $this->pending_status, $args );
	}

	/**
	 * In the BP Docs list in wp-admin, add a label to posts that require moderation.
	 *
	 * @since 2.1.0
	 *
	 * @param array   $post_states An array of post display states.
	 * @param WP_Post $post        The current post object.
	 */
	function add_moderated_label( $post_states, $post ) {
		if ( $this->pending_status == $post->post_status ) {
			$post_states[] = __( 'Awaiting Moderation', 'buddypress-docs' );
		}
		return $post_states;
	}
}
