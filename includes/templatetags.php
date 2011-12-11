<?php

if ( !function_exists( 'bp_is_root_blog' ) ) :
	/**
	 * Is this BP_ROOT_BLOG?
	 *
	 * I'm hoping to have this function in BP 1.3 core, but just in case, here's a
	 * conditionally-loaded version. Checks against $wpdb->blogid, which provides greater
	 * support for switch_to_blog()
	 *
	 * @package BuddyPress Docs
	 * @since 1.0.4
	 *
	 * @return bool $is_root_blog Returns true if this is BP_ROOT_BLOG. Always true on non-MS
	 */
	function bp_is_root_blog() {
		global $wpdb;

		$is_root_blog = true;

		if ( is_multisite() && $wpdb->blogid != BP_ROOT_BLOG )
			$is_root_blog = false;

		return apply_filters( 'bp_is_root_blog', $is_root_blog );
	}
endif;


/**
 * Determine whether you are viewing a BuddyPress Docs page
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 *
 * @return bool
 */
function bp_docs_is_bp_docs_page() {
	global $bp;

	$is_bp_docs_page = false;

	// This is intentionally ambiguous and generous, to account for BP Docs is different
	// components. Probably should be cleaned up at some point
	if ( $bp->bp_docs->slug == bp_current_component() || $bp->bp_docs->slug == bp_current_action() )
		$is_bp_docs_page = true;

	return apply_filters( 'bp_docs_is_bp_docs_page', $is_bp_docs_page );
}


/**
 * Returns true if the current page is a BP Docs edit or create page (used to load JS)
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 *
 * @returns bool
 */
function bp_docs_is_wiki_edit_page() {
	global $bp;

	$item_type = BP_Docs_Query::get_item_type();
	$current_view = BP_Docs_Query::get_current_view( $item_type );

	return apply_filters( 'bp_docs_is_wiki_edit_page', $is_wiki_edit_page );
}


/**
 * Echoes the output of bp_docs_get_info_header()
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 */
function bp_docs_info_header() {
	echo bp_docs_get_info_header();
}
	/**
	 * Get the info header for a list of docs
	 *
	 * Contains things like tag filters
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 *
	 * @param int $doc_id optional The post_id of the doc
	 * @return str Permalink for the group doc
	 */
	function bp_docs_get_info_header() {
		$filters = bp_docs_get_current_filters();

		// Set the message based on the current filters
		if ( empty( $filters ) ) {
			$message = __( 'You are viewing <strong>all</strong> docs.', 'bp-docs' );
		} else {
			$message = array();

			$message = apply_filters( 'bp_docs_info_header_message', $message, $filters );

			$message = implode( "\n", $message );

			// We are viewing a subset of docs, so we'll add a link to clear filters
			$message .= ' - ' . sprintf( __( '<strong><a href="%s" title="View All Docs">View All Docs</a></strong>', 'bp_docs' ), bp_docs_get_item_docs_link() );
		}

		?>

		<p class="currently-viewing"><?php echo $message ?></p>

		<form action="<?php bp_docs_item_docs_link() ?>" method="post">

			<div class="docs-filters">
				<?php do_action( 'bp_docs_filter_markup' ) ?>
			</div>

			<div class="clear"> </div>

			<?php /*
			<input class="button" id="docs-filter-submit" name="docs-filter-submit" value="<?php _e( 'Submit', 'bp-docs' ) ?>" type="submit" />
			*/ ?>

		</form>


		<?php
	}

/**
 * Filters the output of the doc list header for search terms
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 *
 * @return array $filters
 */
function bp_docs_search_term_filter_text( $message, $filters ) {
	if ( !empty( $filters['search_terms'] ) ) {
		$message[] = sprintf( __( 'You are searching for docs containing the term <em>%s</em>', 'bp-docs' ), esc_html( $filters['search_terms'] ) );
	}

	return $message;
}
add_filter( 'bp_docs_info_header_message', 'bp_docs_search_term_filter_text', 10, 2 );

/**
 * Get the filters currently being applied to the doc list
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 *
 * @return array $filters
 */
function bp_docs_get_current_filters() {
	$filters = array();

	// First check for tag filters
	if ( !empty( $_REQUEST['bpd_tag'] ) ) {
		// The bpd_tag argument may be comma-separated
		$tags = explode( ',', urldecode( $_REQUEST['bpd_tag'] ) );

		foreach( $tags as $tag ) {
			$filters['tags'][] = $tag;
		}
	}

	// Now, check for search terms
	if ( !empty( $_REQUEST['s'] ) ) {
		$filters['search_terms'] = urldecode( $_REQUEST['s'] );
	}

	return apply_filters( 'bp_docs_get_current_filters', $filters );
}

/**
 * Echoes the output of bp_docs_get_doc_link()
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 */
function bp_docs_doc_link( $doc_id ) {
	echo bp_docs_get_doc_link( $doc_id );
}
	/**
	 * Get the doc's permalink
	 *
	 * For the moment, this returns the URL of the first item associated with the doc. If you
	 * extend BuddyPress Docs so that items can be associated with multiple groups, you'll need
	 * to change the way this function works.
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 *
	 * @param int $doc_id
	 * @return str URL of the doc
	 */
	function bp_docs_get_doc_link( $doc_id ) {
		global $bp;

		if ( empty( $doc_id ) )
			return false;

		// Get the associated item
		$ass_item 	= wp_get_post_terms( $doc_id, $bp->bp_docs->associated_item_tax_name );

		if ( empty( $ass_item ) )
			return false;

		// Get the associated item's doc link
		// Default to 'group' for now. Todo: abstract (will take another query for tax parent)
		$item_docs_link	= bp_docs_get_item_docs_link( array( 'item_id' => $ass_item[0]->name, 'item_type' => 'group' ) );

		$post		= get_post( $doc_id );

		return apply_filters( 'bp_docs_get_doc_link', $item_docs_link . $post->post_name );
	}

/**
 * Echoes the output of bp_docs_get_item_docs_link()
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 */
function bp_docs_item_docs_link() {
	echo bp_docs_get_item_docs_link();
}
	/**
	 * Get the link to the docs section of an item
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 *
	 * @return array $filters
	 */
	function bp_docs_get_item_docs_link( $args = array() ) {
		global $bp;

		// Defaulting to groups for now
		$defaults = array(
			'item_id'	=> !empty( $bp->groups->current_group->id ) ? $bp->groups->current_group->id : false,
			'item_type'	=> !empty( $bp->groups->current_group->id ) ? 'group' : false
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		if ( !$item_id || !$item_type )
			return false;

		switch ( $item_type ) {
			case 'group' :
				if ( !$group = $bp->groups->current_group )
					$group = groups_get_group( array( 'group_id' => $item_id ) );

				$base_url = bp_get_group_permalink( $group );
				break;
		}

		return apply_filters( 'bp_docs_get_item_docs_link', $base_url . $bp->bp_docs->slug . '/', $base_url, $r );
	}

/**
 * Get the sort order for sortable column links
 *
 * Detects the current sort order and returns the opposite
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 *
 * @return str $new_order Either desc or asc
 */
function bp_docs_get_sort_order( $orderby = 'modified' ) {

	$new_order	= false;

	// We only want a non-default order if we are currently ordered by this $orderby
	// The default order is Last Edited, so we must account for that
	$current_orderby	= !empty( $_GET['orderby'] ) ? $_GET['orderby'] : apply_filters( 'bp_docs_default_sort_order', 'modified' );

	if ( $orderby == $current_orderby ) {
		// Default sort orders are different for different fields
		if ( empty( $_GET['order'] ) ) {
			// If no order is explicitly stated, we must provide one.
			// It'll be different for date fields (should be DESC)
			if ( 'modified' == $current_orderby || 'date' == $current_orderby )
				$current_order = 'DESC';
			else
				$current_order = 'ASC';
		} else {
			$current_order = $_GET['order'];
		}

		$new_order = 'ASC' == $current_order ? 'DESC' : 'ASC';
	}

	return apply_filters( 'bp_docs_get_sort_order', $new_order );
}

/**
 * Echoes the output of bp_docs_get_order_by_link()
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 *
 * @param str $orderby The order_by item: title, author, created, edited, etc
 */
function bp_docs_order_by_link( $orderby = 'modified' ) {
	echo bp_docs_get_order_by_link( $orderby );
}
	/**
	 * Get the URL for the sortable column header links
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 *
	 * @param str $orderby The order_by item: title, author, created, modified, etc
	 * @return str The URL with args attached
	 */
	function bp_docs_get_order_by_link( $orderby = 'modified' ) {
		$args = array(
			'orderby' 	=> $orderby,
			'order'		=> bp_docs_get_sort_order( $orderby )
		);

		return apply_filters( 'bp_docs_get_order_by_link', add_query_arg( $args ), $orderby, $args );
	}

/**
 * Echoes current-orderby and order classes for the column currently being ordered by
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 *
 * @param str $orderby The order_by item: title, author, created, modified, etc
 */
function bp_docs_is_current_orderby_class( $orderby = 'modified' ) {
	// Get the current orderby column
	$current_orderby	= !empty( $_GET['orderby'] ) ? $_GET['orderby'] : apply_filters( 'bp_docs_default_sort_order', 'modified' );

	// Does the current orderby match the $orderby parameter?
	$is_current_orderby 	= $current_orderby == $orderby ? true : false;

	$class = '';

	// If this is indeed the current orderby, we need to get the asc/desc class as well
	if ( $is_current_orderby ) {
		$class = ' current-orderby';

		if ( empty( $_GET['order'] ) ) {
			// If no order is explicitly stated, we must provide one.
			// It'll be different for date fields (should be DESC)
			if ( 'modified' == $current_orderby || 'date' == $current_orderby )
				$class .= ' desc';
			else
				$class .= ' asc';
		} else {
			$class .= 'DESC' == $_GET['order'] ? ' desc' : ' asc';
		}
	}

	echo apply_filters( 'bp_docs_is_current_orderby', $class, $is_current_orderby, $current_orderby );
}


/**
 * Determine whether the current user can do something the current doc
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 *
 * @param str $action The cap being tested
 * @return bool $user_can
 */
function bp_docs_current_user_can( $action = 'edit' ) {
	global $bp;

	// Check to see whether the value has been cached in the global
	if ( isset( $bp->bp_docs->current_user_can[$action] ) ) {
		$user_can = 'yes' == $bp->bp_docs->current_user_can[$action] ? true : false;
	} else {
		$user_can = bp_docs_user_can( $action, bp_loggedin_user_id() );
	}

	// Stash in the $bp global to reduce future lookups
	$bp->bp_docs->current_user_can[$action] = $user_can ? 'yes' : 'no';

	return apply_filters( 'bp_docs_current_user_can', $user_can );
}

/**
 * Determine whether a given user can do something with a given doc
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 *
 * @param str $action Optional. The action being queried. Eg 'edit', 'read_comments', 'manage'
 * @param int $user_id Optional. Unique user id for the user being tested. Defaults to logged-in ID
 * @param int $doc_id Optional. Unique doc id. Defaults to doc currently being viewed
 */
function bp_docs_user_can( $action = 'edit', $user_id = false, $doc_id = false ) {
	global $bp, $post;

	if ( !$user_id )
		$user_id = bp_loggedin_user_id();

	// Only certain actions are checked against doc_ids
	$need_doc_ids_actions = apply_filters( 'bp_docs_need_doc_ids_actions', array( 'edit', 'manage', 'view_history', 'read' ) );

	if ( in_array( $action, $need_doc_ids_actions ) ) {
		if ( !$doc_id ) {
			if ( !empty( $post->ID ) ) {
				$doc_id = $post->ID;
			} else {
				$doc = bp_docs_get_current_doc();
				$doc_id = $doc->ID;
			}
		}
	} else {
		$doc_id = false;
	}

	$user_can = false;

	if ( $user_id ) {
		if ( is_super_admin() ) {
			// Super admin always gets to edit. What a big shot
			$user_can = true;
		} else {
			// Filter this so that groups-integration and other plugins can give their
			// own rules. Done inside the conditional so that plugins don't have to
			// worry about the is_super_admin() check
			$user_can = apply_filters( 'bp_docs_user_can', $user_can, $action, $user_id, $doc_id );
		}
	}

	return $user_can;
}

/**
 * Prints the inline toggle setup script
 *
 * Ideally, I would put this into an external document; but the fact that it is supposed to hide
 * content immediately on pageload means that I didn't want to wait for an external script to
 * load, much less for document.ready. Sorry.
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 */
function bp_docs_inline_toggle_js() {
	?>
	<script type="text/javascript">
		/* Swap toggle text with a dummy link and hide toggleable content on load */
		var togs = jQuery('.toggleable');

		jQuery(togs).each(function(){
			var ts = jQuery(this).children('.toggle-switch');

			/* Get a unique identifier for the toggle */
			var tsid = jQuery(ts).attr('id').split('-');
			var type = tsid[0];

			/* Append the static toggle text with a '+' sign and linkify */
			var toggleid = type + '-toggle-link';
			var plus = '<span class="plus-or-minus">+</span>';

			jQuery(ts).html('<a href="#" id="' + toggleid + '" class="toggle-link">' + plus + jQuery(ts).html() + '</a>');

			/* Hide the toggleable area */
			jQuery(this).children('.toggle-content').toggle();
		});

	</script>
	<?php
}

/**
 * A hook for intregration pieces to insert their settings markup
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 */
function bp_docs_doc_settings_markup() {
	$doc = bp_docs_get_current_doc();

	$doc_settings = !empty( $doc->ID ) ? get_post_meta( $doc->ID, 'bp_docs_settings', true ) : array();

	// For now, I'll hand off the creation of settings to individual integration pieces
	do_action( 'bp_docs_doc_settings_markup', $doc_settings );
}

/**
 * Outputs the links that appear under each Doc in the Doc listing
 *
 * @package BuddyPress Docs
 */
function bp_docs_doc_action_links() {
	$links 		= array();

	$links[] 	= '<a href="' . bp_docs_get_group_doc_permalink() . '">' . __( 'Read', 'bp-docs' ) . '</a>';

	if ( bp_docs_user_can( 'edit', bp_loggedin_user_id() ) )
		$links[] 	= '<a href="' . bp_docs_get_group_doc_permalink() . '/' . BP_DOCS_EDIT_SLUG . '">' . __( 'Edit', 'bp-docs' ) . '</a>';

	if ( bp_docs_user_can( 'view_history', bp_loggedin_user_id() ) )
		$links[] 	= '<a href="' . bp_docs_get_group_doc_permalink() . '/' . BP_DOCS_HISTORY_SLUG . '">' . __( 'History', 'bp-docs' ) . '</a>';

	echo implode( ' &#124; ', $links );
}

function bp_docs_current_group_is_public() {
	global $bp;

	if ( !empty( $bp->groups->current_group->status ) && 'public' == $bp->groups->current_group->status )
		return true;

	return false;
}

/**
 * Utility function to get and cache the current doc
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 *
 * @return obj Current doc
 */
function bp_docs_get_current_doc() {
	global $bp;

	if ( empty( $bp->bp_docs->doc_slug ) )
		return false;

	if ( empty( $bp->bp_docs->current_post ) ) {

		$posts = get_posts( array(
			'post_type' => $bp->bp_docs->post_type_name,
			'name' => $bp->bp_docs->doc_slug
		) );

		if ( empty( $posts ) )
			return false;

		$doc = $posts[0];

		$bp->bp_docs->current_post = $posts[0];

	} else {
		$doc = $bp->bp_docs->current_post;
	}

	return $doc;
}

/**
 * Return the bp_doc post type name
 *
 * @package BuddyPress Docs
 * @since 1.2
 *
 * @return str The name of the bp_doc post type
 */
function bp_docs_get_post_type_name() {
	global $bp;

	return $bp->bp_docs->post_type_name;
}

/**
 * Get the lock status of a doc
 *
 * The function first tries to get the lock status out of $bp. If it has to look it up, it
 * stores the data in $bp for future use.
 *
 * @package BuddyPress Docs
 * @since 1.0-beta-2
 *
 * @param int $doc_id Optional. Defaults to the doc currently being viewed
 * @return int Returns 0 if there is no lock, otherwise returns the user_id of the locker
 */
function bp_docs_is_doc_edit_locked( $doc_id = false ) {
	global $bp, $post;

	// Try to get the lock out of $bp first
	if ( isset( $bp->bp_docs->current_doc_lock ) ) {
		$is_edit_locked = $bp->bp_docs->current_doc_lock;
	} else {
		$is_edit_locked = 0;

		if ( empty( $doc_id ) )
			$doc_id = !empty( $post->ID ) ? $post->ID : false;

		if ( $doc_id ) {
			// Make sure that wp-admin/includes/post.php is loaded
			if ( !function_exists( 'wp_check_post_lock' ) )
				require_once( ABSPATH . 'wp-admin/includes/post.php' );

			// Because we're not using WP autosave at the moment, ensure that
			// the lock interval always returns as in process
			add_filter( 'wp_check_post_lock_window', create_function( false, 'return time();' ) );

			$is_edit_locked = wp_check_post_lock( $doc_id );
		}

		// Put into the $bp global to avoid extra lookups
		$bp->bp_docs->current_doc_lock = $is_edit_locked;
	}

	return apply_filters( 'bp_docs_is_doc_edit_locked', $is_edit_locked, $doc_id );
}

/**
 * Echoes the output of bp_docs_get_current_doc_locker_name()
 *
 * @package BuddyPress Docs
 * @since 1.0-beta-2
 */
function bp_docs_current_doc_locker_name() {
	echo bp_docs_get_current_doc_locker_name();
}
	/**
	 * Get the name of the user locking the current document, if any
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta-2
	 *
	 * @return string $locker_name The full name of the locking user
	 */
	function bp_docs_get_current_doc_locker_name() {
		$locker_name = '';

		$locker_id = bp_docs_is_doc_edit_locked();

		if ( $locker_id )
			$locker_name = bp_core_get_user_displayname( $locker_id );

		return apply_filters( 'bp_docs_get_current_doc_locker_name', $locker_name, $locker_id );
	}

/**
 * Echoes the output of bp_docs_get_force_cancel_edit_lock_link()
 *
 * @package BuddyPress Docs
 * @since 1.0-beta-2
 */
function bp_docs_force_cancel_edit_lock_link() {
	echo bp_docs_get_force_cancel_edit_lock_link();
}
	/**
	 * Get the URL for canceling the edit lock on the current doc
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta-2
	 *
	 * @return string $cancel_link href for the cancel edit lock link
	 */
	function bp_docs_get_force_cancel_edit_lock_link() {
		global $post;

		$doc_id = !empty( $post->ID ) ? $post->ID : false;

		if ( !$doc_id )
			return false;

		$doc_permalink = bp_docs_get_doc_link( $doc_id );

		$cancel_link = wp_nonce_url( add_query_arg( 'bpd_action', 'cancel_edit_lock', $doc_permalink ), 'bp_docs_cancel_edit_lock' );

		return apply_filters( 'bp_docs_get_force_cancel_edit_lock_link', $cancel_link, $doc_permalink );
	}

/**
 * Echoes the output of bp_docs_get_cancel_edit_link()
 *
 * @package BuddyPress Docs
 * @since 1.0-beta-2
 */
function bp_docs_cancel_edit_link() {
	echo bp_docs_get_cancel_edit_link();
}
	/**
	 * Get the URL for canceling out of Edit mode on a doc
	 *
	 * This used to be a straight link back to non-edit mode, but something fancier is needed
	 * in order to detect the Cancel and to remove the edit lock.
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta-2
	 *
	 * @return string $cancel_link href for the cancel edit link
	 */
	function bp_docs_get_cancel_edit_link() {
		global $bp, $post;

		$doc_id = !empty( $bp->bp_docs->current_post->ID ) ? $bp->bp_docs->current_post->ID : false;

		if ( !$doc_id )
			return false;

		$doc_permalink = bp_docs_get_doc_link( $doc_id );

		$cancel_link = add_query_arg( 'bpd_action', 'cancel_edit', $doc_permalink );

		return apply_filters( 'bp_docs_get_cancel_edit_link', $cancel_link, $doc_permalink );
	}

/**
 * Echoes the output of bp_docs_get_delete_doc_link()
 *
 * @package BuddyPress Docs
 * @since 1.0.1
 */
function bp_docs_delete_doc_link() {
	echo bp_docs_get_delete_doc_link();
}
	/**
	 * Get the URL to delete the current doc
	 *
	 * @package BuddyPress Docs
	 * @since 1.0.1
	 *
	 * @return string $delete_link href for the delete doc link
	 */
	function bp_docs_get_delete_doc_link() {
		global $bp, $post;

		$doc_id = !empty( $bp->bp_docs->current_post->ID ) ? $bp->bp_docs->current_post->ID : false;

		if ( !$doc_id )
			return false;

		$doc_permalink = bp_docs_get_doc_link( $doc_id );

		$delete_link = wp_nonce_url( $doc_permalink . '/' . BP_DOCS_DELETE_SLUG, 'bp_docs_delete' );

		return apply_filters( 'bp_docs_get_delete_doc_link', $delete_link, $doc_permalink );
	}

/**
 * Echo the pagination links for the doc list view
 *
 * @package BuddyPress Docs
 * @since 1.0-beta-2
 */
function bp_docs_paginate_links() {
	global $wp_query;

	$cur_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;

        $page_links_total = $wp_query->max_num_pages;

        $page_links = paginate_links( array(
		'base' 		=> add_query_arg( 'paged', '%#%' ),
		'format' 	=> '',
		'prev_text' 	=> __('&laquo;'),
		'next_text' 	=> __('&raquo;'),
		'total' 	=> $page_links_total,
		'current' 	=> $cur_page
        ));

        echo apply_filters( 'bp_docs_paginate_links', $page_links );
}

/**
 * Get the start number for the current docs view (ie "Viewing *5* - 8 of 12")
 *
 * Here's the math: Subtract one from the current page number; multiply times posts_per_page to get
 * the last post on the previous page; add one to get the start for this page.
 *
 * @package BuddyPress Docs
 * @since 1.0-beta-2
 *
 * @return int $start The start number
 */
function bp_docs_get_current_docs_start() {
	global $wp_query;

	$paged = !empty( $wp_query->query_vars['paged'] ) ? $wp_query->query_vars['paged'] : 1;

	$posts_per_page = !empty( $wp_query->query_vars['posts_per_page'] ) ? $wp_query->query_vars['posts_per_page'] : 10;

	$start = ( ( $paged - 1 ) * $posts_per_page ) + 1;

	return apply_filters( 'bp_docs_get_current_docs_start', $start );
}

/**
 * Get the end number for the current docs view (ie "Viewing 5 - *8* of 12")
 *
 * Here's the math: Multiply the posts_per_page by the current page number. If it's the last page
 * (ie if the result is greater than the total number of docs), just use the total doc count
 *
 * @package BuddyPress Docs
 * @since 1.0-beta-2
 *
 * @return int $end The start number
 */
function bp_docs_get_current_docs_end() {
	global $wp_query;

	$paged = !empty( $wp_query->query_vars['paged'] ) ? $wp_query->query_vars['paged'] : 1;

	$posts_per_page = !empty( $wp_query->query_vars['posts_per_page'] ) ? $wp_query->query_vars['posts_per_page'] : 10;

	$end = $paged * $posts_per_page;

	if ( $end > bp_docs_get_total_docs_num() )
		$end = bp_docs_get_total_docs_num();

	return apply_filters( 'bp_docs_get_current_docs_end', $end );
}

/**
 * Get the total number of found docs out of $wp_query
 *
 * @package BuddyPress Docs
 * @since 1.0-beta-2
 *
 * @return int $total_doc_count The start number
 */
function bp_docs_get_total_docs_num() {
	global $wp_query;

	$total_doc_count = !empty( $wp_query->found_posts ) ? $wp_query->found_posts : 0;

	return apply_filters( 'bp_docs_get_total_docs_num', $total_doc_count );
}

/**
 * Display a Doc's comments
 *
 * This function was introduced to make sure that the comment display callback function can be
 * filtered by site admins. Originally, wp_list_comments() was called directly from the template
 * with the callback bp_dtheme_blog_comments, but this caused problems for sites not running a
 * child theme of bp-default.
 *
 * Filter bp_docs_list_comments_args to provide your own comment-formatting function.
 *
 * @package BuddyPress Docs
 * @since 1.0.5
 */
function bp_docs_list_comments() {
	$args = array();

	if ( function_exists( 'bp_dtheme_blog_comments' ) )
		$args['callback'] = 'bp_dtheme_blog_comments';

	$args = apply_filters( 'bp_docs_list_comments_args', $args );

	wp_list_comments( $args );
}

/**
 * Are we looking at an existing doc?
 *
 * @package BuddyPress Docs
 * @since 1.0-beta
 *
 * @return bool True if it's an existing doc
 */
function bp_docs_is_existing_doc() {
	global $bp;

	if ( empty( $bp->bp_docs->current_post ) )
		$bp->bp_docs->current_post = bp_docs_get_current_doc();

	if ( empty( $bp->bp_docs->current_post ) )
		return false;

	return true;
}

/**
 * What's the current view?
 *
 * @package BuddyPress Docs
 * @since 1.1
 *
 * @return str $current_view The current view
 */
function bp_docs_current_view() {
	global $bp;

	$view = !empty( $bp->bp_docs->current_view ) ? $bp->bp_docs->current_view : false;

	return apply_filters( 'bp_docs_current_view', $view );
}

/**
 * Get the absolute path of a given template.
 *
 * Looks first for a template in [theme-dir]/docs/, and falls back on the provided templates.
 *
 * Ideally, I would not need this function. But WP's locate_template() plays funny with directory
 * paths, and bp_core_load_template() does not have an option that will let you locate but not load
 * the found template.
 *
 * @package BuddyPress Docs
 * @since 1.0.5
 *
 * @param str $template This string should be of the format 'edit-docs.php'. Ie, you need '.php',
 *                      but you don't need the leading '/docs/'
 * @return str $template_path The absolute path of the located template file.
 */
function bp_docs_locate_template( $template = '' ) {
	if ( empty( $template ) )
		return false;

	// Try to load custom templates first
	$stylesheet_path = STYLESHEETPATH . '/docs/';

	if ( file_exists( $stylesheet_path . $template ) )
		$template_path = $stylesheet_path . $template;
	else
		$template_path = BP_DOCS_INCLUDES_PATH . 'templates/docs/' . $template;

	return apply_filters( 'bp_docs_locate_template', $template_path, $template );
}
?>
