<?php

/**
 * Determine whether you are viewing a BuddyPress Docs page
 *
 * @package BuddyPress Docs
 * @since 1.0
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


/**
 * Determine whether the current user can do something the current doc
 *
 * @package BuddyPress Docs
 * @since 1.0
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
 * Determine whether a given user can edit a given doc
 *
 * @package BuddyPress Docs
 * @since 1.0
 *
 * @param int $user_id Optional. Unique user id for the user being tested. Defaults to logged-in ID
 * @param int $doc_id Optional. Unique doc id. Defaults to doc currently being viewed
 */
function bp_docs_user_can( $action = 'edit', $user_id = false, $doc_id = false ) {
	global $bp;
	
	if ( !$user_id )
		$user_id	= bp_loggedin_user_id();
	
	$user_can = false;
	
	if ( $user_id ) {
		if ( is_super_admin() ) {
			// Super admin always gets to edit. What a big shot
			$user_can = true;
		} else {
			// Post authors always get to whatever they want
			if ( get_the_author_meta( 'ID' ) == $user_id ) {
				$user_can = true;
			}
			
			// Filter this so that groups-integration and other plugins can give their
			// own rules. Done inside the conditional so that plugins don't have to
			// worry about the is_super_admin() check
			$user_can = apply_filters( 'bp_docs_user_can', $user_can, $action, $user_id );
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
 * @since 1.0
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
			
			/* Replace the static toggle text with a link */
			var toggleid = type + '-toggle-link';
			
			jQuery(ts).html('<a href="#" id="' + toggleid + '" class="toggle-link">' + jQuery(ts).html() + ' +</a>');
			
			/* Hide the toggleable area */
			jQuery(this).children('.toggle-content').toggle();	
		});
		
	</script>
	<?php
}

function bp_docs_doc_settings_markup() {
	$doc_settings = get_post_meta( get_the_ID(), 'bp_docs_settings', true );
	
	// For now, I'll hand off the creation of settings to individual integration pieces
	do_action( 'bp_docs_doc_settings_markup', $doc_settings );
}

?>