<?php
/**
 * BuddyPress Docs Recent Docs Widget
 * Based on WP's "Recent Posts" widget.
 *
 * @package BuddyPressDocs
 * @since 1.9.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the recent docs widget.
 *
 * @since 1.9.0
 */
function bp_docs_register_recent_docs_widget() {
	register_widget( 'BP_Docs_Widget_Recent_Docs' );
}
add_action( 'widgets_init', 'bp_docs_register_recent_docs_widget' );

/**
 * Core class used to implement a Recent Docs widget.
 *
 * @since 1.9.0
 *
 * @see WP_Widget
 */
class BP_Docs_Widget_Recent_Docs extends WP_Widget {

	/**
	 * Sets up a new Recent Docs widget instance.
	 *
	 * @since 1.9.0
	 * @access public
	 */
	public function __construct() {
		$widget_ops = array(
			// Use the class `widget_recent_entries` to inherit WP Recent Posts widget styling.
			'classname' => 'widget_recent_entries widget_recent_bp_docs',
			'description' => __( 'Displays the most recent BuddyPress Docs that the visitor can read.' ) );
		parent::__construct( 'widget_recent_bp_docs', _x( '(BuddyPress Docs) Recent Docs', 'widget name', 'buddypress-docs' ), $widget_ops);
		$this->alt_option_name = 'widget_recent_bp_docs';
	}

	/**
	 * Outputs the content for the current Recent Docs widget instance.
	 *
	 * @since 1.9.0
	 * @access public
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance Settings for the current Recent Docs widget instance.
	 */
	public function widget( $args, $instance ) {
		$bp = buddypress();

		// Store the existing doc_query, so ours is made from scratch.
		$temp_doc_query = isset( $bp->bp_docs->doc_query ) ? $bp->bp_docs->doc_query : null;
		$bp->bp_docs->doc_query = null;

		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : __( 'Recent BuddyPress Docs', 'buddypress-docs' );

		/* This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;
		if ( ! $number ) {
			$number = 5;
		}
		$show_date = isset( $instance['show_date'] ) ? $instance['show_date'] : false;

		$doc_args = array(
			'posts_per_page' => $number,
			'post_status'    => array( 'publish' ),
			'group_id'       => null,
			'folder_id'      => null,
		);

		/**
		 * Limit to docs associated with the current group
		 * if the widget has been set to be group context aware
		 * and we're in a group.
		 */
		if ( isset( $instance['group_aware'] )
			&& $instance['group_aware']
			&& bp_is_group() ) {
			$doc_args['group_id'] = bp_get_current_group_id();
		}

		/**
		 * Limit to docs associated with the displayed user
		 * if the widget has been set to be user context aware
		 * and we're viewing a user's profile.
		 */
		if ( isset( $instance['user_profile_aware'] )
			&& $instance['user_profile_aware']
			&& bp_is_user() ) {
			$doc_args['author_id'] = bp_displayed_user_id();
		}

		/**
		 * Filters the args passed to `bp_docs_has_docs()` in the Recent Docs widget.
		 *
		 * @since 2.1.0
		 *
		 * @param array {
		 *     @type int    $posts_per_page
		 *     @type string $post_status
		 *     @type int    $group_id
		 *     @type int    $folder_id
		 * }
		 */
		$doc_args = apply_filters( 'bp_docs_widget_query_args', $doc_args );

		if ( bp_docs_has_docs( $doc_args ) ) :
			echo $args['before_widget'];
			if ( $title ) {
				echo $args['before_title'] . $title . $args['after_title'];
			} ?>
			<ul>
			<?php while ( bp_docs_has_docs() ) : bp_docs_the_doc(); ?>
				<li>
					<a href="<?php the_permalink(); ?>"><?php get_the_title() ? the_title() : the_ID(); ?></a>
				<?php if ( $show_date ) : ?>
					<span class="post-date"><?php echo get_the_date(); ?></span>
				<?php endif; ?>
				</li>
			<?php endwhile; ?>
		</ul>
		<?php echo $args['after_widget'];

		endif;

		wp_reset_postdata();

		// Restore the main doc_query; obliterate our secondary loop arguments.
		$bp->bp_docs->doc_query = $temp_doc_query;
	}

	/**
	 * Handles updating the settings for the current Recent Docs widget instance.
	 *
	 * @since 1.9.0
	 * @access public
	 *
	 * @param array $new_instance New settings for this instance as input by the user via
	 *                            WP_Widget::form().
	 * @param array $old_instance Old settings for this instance.
	 * @return array Updated settings to save.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance              = $old_instance;
		$instance['title']     = sanitize_text_field( $new_instance['title'] );
		$instance['number']    = (int) $new_instance['number'];
		$instance['show_date'] = isset( $new_instance['show_date'] ) ? (bool) $new_instance['show_date'] : false;
		$instance['group_aware'] = isset( $new_instance['group_aware'] );
		$instance['user_profile_aware'] = isset( $new_instance['user_profile_aware'] );
		return $instance;
	}

	/**
	 * Outputs the settings form for the Recent Docs widget.
	 *
	 * @since 1.9.0
	 * @access public
	 *
	 * @param array $instance Current settings.
	 */
	public function form( $instance ) {
		$title       = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number      = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$show_date   = isset( $instance['show_date'] ) ? (bool) $instance['show_date'] : false;
		$group_aware = isset( $instance['group_aware'] ) ? (bool) $instance['group_aware'] : false;
		$user_profile_aware = isset( $instance['user_profile_aware'] ) ? (bool) $instance['user_profile_aware'] : false;
		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'buddypress-docs' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of docs to show:', 'buddypress-docs' ); ?></label>
		<input class="tiny-text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" step="1" min="1" value="<?php echo $number; ?>" size="3" /></p>

		<p><input class="checkbox" type="checkbox"<?php checked( $show_date ); ?> id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Display post date?', 'buddypress-docs' ); ?></label></p>

		<p><input class="checkbox" type="checkbox"<?php checked( $group_aware ); ?> id="<?php echo $this->get_field_id( 'group_aware' ); ?>" name="<?php echo $this->get_field_name( 'group_aware' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'group_aware' ); ?>"><?php _e( 'When used within a group, limit docs to the current group&rsquo;s docs.', 'buddypress-docs' ); ?></label></p>

		<p><input class="checkbox" type="checkbox"<?php checked( $user_profile_aware ); ?> id="<?php echo $this->get_field_id( 'user_profile_aware' ); ?>" name="<?php echo $this->get_field_name( 'user_profile_aware' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'user_profile_aware' ); ?>"><?php _e( 'When used within a user profile, limit docs to the displayed user&rsquo;s docs.', 'buddypress-docs' ); ?></label></p>
		<?php
	}
}
