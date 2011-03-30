<?php

/**
 * Builds the subnav for the Docs group tab
 *
 * This method is copied from bp_group_admin_tabs(), which itself is a hack for the fact that BP
 * has no native way to register subnav items on a group tab. Component subnavs (for user docs) will
 * be properly registered with bp_core_new_subnav_item()
 *
 * @package BuddyPress Docs
 * @since 1.0
 *
 * @param obj $group optional The BP group object.
 *
 */
function bp_docs_group_tabs( $group = false ) {
	global $bp, $groups_template, $post, $bp_version;
	
	if ( !$group )
		$group = ( $groups_template->group ) ? $groups_template->group : $bp->groups->current_group;
	
	// BP 1.2 - 1.3 support
	$groups_slug = !empty( $bp->groups->root_slug ) ? $bp->groups->root_slug : $bp->groups->slug;

?>
	<li<?php if ( $bp->bp_docs->current_view == 'list' ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->root_domain . '/' . $groups_slug ?>/<?php echo $group->slug ?>/<?php echo $bp->bp_docs->slug ?>/"><?php _e( 'View Docs', 'bp-docs' ) ?></a></li>

	<?php /* Todo: can this user create items? */ ?>
	<li<?php if ( 'create' == $bp->bp_docs->current_view ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->root_domain . '/' . $groups_slug ?>/<?php echo $group->slug ?>/<?php echo $bp->bp_docs->slug ?>/create"><?php _e( 'New Doc', 'bp-docs' ) ?></a></li>
	
	
	<?php if ( $bp->bp_docs->current_view == 'single' || $bp->bp_docs->current_view == 'edit' ) : ?>
		<li<?php if ( 'single' == $bp->bp_docs->current_view ) : ?> class="current"<?php endif; ?>><a href="<?php echo $bp->root_domain . '/' . $groups_slug ?>/<?php echo $group->slug ?>/<?php echo $bp->bp_docs->slug ?>/<?php echo $post->post_name ?>"><?php the_title() ?></a></li>		
	<?php endif ?>
	
<?php
}

/**
 * Returns true if the current page is a BP Docs edit or create page (used to load JS)
 *
 * @package BuddyPress Docs
 * @since 1.0
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
 * Echoes the output of bp_docs_get_group_doc_permalink()
 *
 * @package BuddyPress Docs
 * @since 1.0
 */
function bp_docs_group_doc_permalink() {
	echo bp_docs_get_group_doc_permalink();
}
	/**
	 * Returns a link to a specific document in a group
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @param int $doc_id optional The post_id of the doc
	 * @return str Permalink for the group doc
	 */
	function bp_docs_get_group_doc_permalink( $doc_id = false ) {
		global $post, $bp;
		
		$group_permalink = bp_get_group_permalink();
		
		if ( $doc_id )
			$post = get_post( $doc_id );

		if ( !empty( $post->post_name ) )
			$doc_slug = $post->post_name;
		else
			return false;
			
		return apply_filters( 'bp_docs_get_doc_permalink', $group_permalink . $bp->bp_docs->slug . '/' . $doc_slug );
	}

/**
 * Echoes the output of bp_docs_get_info_header()
 *
 * @package BuddyPress Docs
 * @since 1.0
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
	 * @since 1.0
	 *
	 * @param int $doc_id optional The post_id of the doc
	 * @return str Permalink for the group doc
	 */
	function bp_docs_get_info_header() {
		$filters = bp_docs_get_current_filters();
		
		// Set the message based on the current filters
		if ( empty( $filters ) ) {
			$message = __( 'You are viewing all docs.', 'bp-docs' );	
		} else {
			$message = array();
		
			$message = apply_filters( 'bp_docs_info_header_message', $message, $filters );
			
			$message = implode( "\n", $message );
			
			// We are viewing a subset of docs, so we'll add a link to clear filters
			$message .= ' - ' . sprintf( __( '<strong><a href="%s" title="View All Docs">View All Docs</a></strong>', 'bp_docs' ), bp_docs_get_item_docs_link() );
		}
		
		?>
		
		<p><?php echo $message ?></p>
		
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
 * Get the filters currently being applied to the doc list
 *
 * @package BuddyPress Docs
 * @since 1.0
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
	
	return apply_filters( 'bp_docs_get_current_filters', $filters );
}

/**
 * Get an archive link for a given tag
 *
 * Optional arguments:
 *  - 'tag' 	The tag linked to. This one is required
 *  - 'type' 	'html' returns a link; anything else returns a URL
 *
 * @package BuddyPress Docs
 * @since 1.0
 *
 * @param array $args Optional arguments
 * @return array $filters
 */
function bp_docs_get_tag_link( $args = array() ) {
	global $bp;
	
	$defaults = array(
		'tag' 	=> false,
		'type' 	=> 'html'
	);
	
	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	$item_docs_url = bp_docs_get_item_docs_link();
	
	$url = apply_filters( 'bp_docs_get_tag_link_url', add_query_arg( 'bpd_tag', urlencode( $tag ), $item_docs_url ), $args, $item_docs_url );
	
	if ( $type != 'html' )
		return apply_filters( 'bp_docs_get_tag_link_url', $url, $tag, $type );
	
	$html = '<a href="' . $url . '" title="' . sprintf( __( 'Docs tagged %s', 'bp-docs' ), esc_attr( $tag ) ) . '">' . esc_html( $tag ) . '</a>';
	
	return apply_filters( 'bp_docs_get_tag_link', $html, $url, $tag, $type );	
}

/**
 * Echoes the output of bp_docs_get_item_docs_link()
 *
 * @package BuddyPress Docs
 * @since 1.0
 */
function bp_docs_item_docs_link() {
	echo bp_docs_get_item_docs_link();
}
	/**
	 * Get the link to the docs section of an item
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
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
					$group = new BP_Groups_Group;
				
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
 * @since 1.0
 *
 * @return str $new_order Either desc or asc
 */
function bp_docs_get_sort_order( $orderby = 'edited' ) {

	$new_order	= false;

	// We only want a non-default order if we are currently ordered by this $orderby
	// The default order is Last Edited, so we must account for that
	$current_orderby	= !empty( $_GET['orderby'] ) ? $_GET['orderby'] : apply_filters( 'bp_docs_default_sort_order', 'edited' );
	
	if ( $orderby == $current_orderby ) {
		$current_order 	= empty( $_GET['order'] ) || 'ASC' == $_GET['order'] ? 'ASC' : 'DESC';
		
		$new_order	= 'ASC' == $current_order ? 'DESC' : 'ASC';
	}
	
	return apply_filters( 'bp_docs_get_sort_order', $new_order );
}

/**
 * Echoes the output of bp_docs_get_order_by_link()
 *
 * @package BuddyPress Docs
 * @since 1.0
 *
 * @param str $orderby The order_by item: title, author, created, edited, etc
 */
function bp_docs_order_by_link( $orderby = 'edited' ) {
	echo bp_docs_get_order_by_link( $orderby );
}
	/**
	 * Get the URL for the sortable column header links
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @param str $orderby The order_by item: title, author, created, edited, etc
	 * @return str The URL with args attached
	 */
	function bp_docs_get_order_by_link( $orderby = 'edited' ) {
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
 * @since 1.0
 *
 * @param str $orderby The order_by item: title, author, created, edited, etc
 */
function bp_docs_is_current_orderby_class( $orderby = 'edited' ) {
	// Get the current orderby column
	$current_orderby	= !empty( $_GET['orderby'] ) ? $_GET['orderby'] : apply_filters( 'bp_docs_default_sort_order', 'edited' );
	
	// Does the current orderby match the $orderby parameter?
	$is_current_orderby 	= $current_orderby == $orderby ? true : false;
	
	$class = '';
	// If this is indeed the current orderby, we need to get the asc/desc class as well
	if ( $is_current_orderby ) {
		$class = ' current-orderby';
		
		if ( !empty( $_GET['order'] ) && 'DESC' == $_GET['order'] )
			$class .= ' desc';
		else
			$class .= ' asc';
	}
	
	echo apply_filters( 'bp_docs_is_current_orderby', $class, $is_current_orderby, $current_orderby );
}

?>