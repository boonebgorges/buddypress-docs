<?php

/**
 * Add a BP Docs-specific "pending" status to use in a moderation workflow.
 *
 * @since 2.1.0
 */
class BP_Docs_Moderation {

	/**
	 * Our pending post status.
	 *
	 * @var string
	 */
	public $pending_status = 'bp_docs_pending';

	/**
	 * Our internal post type.
	 *
	 * @var string
	 */
	protected $post_type = '';

	/**
	 * Constructor.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		$this->post_type = $GLOBALS['bp_docs']->post_type_name;
	}

	/**
	 * Add hooks for moderation functionality.
	 *
	 * @since 2.1.0
	 */
	public function add_hooks() {
		// Register status on our post type admin page.
		add_action( 'current_screen', array( $this, 'register_status_in_admin_area' ) );

		// Register status when querying for our post type.
		add_action( 'pre_get_posts', array( $this, 'register_status_during_wp_query' ) );

		add_filter( 'display_post_states', array( $this, 'add_moderated_label' ), 10, 2 );
	}

	/**
	 * Only register post status if we are on our post type admin page.
	 *
	 * @since X
	 *
	 * @param WP_Screen $screen Current screen instance.
	 */
	public function register_status_in_admin_area( $screen ) {
		// If not on our post type, bail.
		if ( $this->post_type !== $screen->post_type ) {
			return;
		}

		// Register our post status.
		$this->register_docs_pending_status();
	}

	/**
	 * Only register post status if we are querying for our post type.
	 *
	 * @since X
	 *
	 * @param WP_Query $q WP_Query instance
	 */
	public function register_status_during_wp_query( $q ) {
		// If not on our post type, bail.
		if ( ! in_array( $this->post_type, (array) $q->get( 'post_type' ) ) ) {
			return;
		}

		// Register our post status.
		$this->register_docs_pending_status();
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
