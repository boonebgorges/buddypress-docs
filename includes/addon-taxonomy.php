<?php

/**
 * This file contains the functions used to enable tags and categories for docs.
 * Separated into this file so that the feature can be turned off.
 *
 * @package BuddyPress Docs
 */

/**
 * This class sets up the interface and back-end functions needed to use post tags with BP Docs.
 *
 * @package BuddyPress Docs
 * @since 1.0
 */
class BP_Docs_Taxonomy {
	var $taxonomies;
	var $current_filters;
	
	/**
	 * PHP 4 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function bp_docs_query() {
		$this->__construct();
	}

	/**
	 * PHP 5 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */	
	function __construct() {
		// Make sure that the bp_docs post type supports our post taxonomies
		add_filter( 'bp_docs_post_type_args', 	array( $this, 'register_with_post_type' ) );
	
		// Hook into post saves to save any taxonomy terms. 
		add_action( 'bp_docs_doc_saved', 	array( $this, 'save_post' ) );
		
		// When a doc is deleted, take its terms out of the local taxonomy
		add_action( 'bp_docs_before_doc_delete', array( $this, 'delete_post' ) );
		
		// Display a doc's terms on its single doc page
		add_action( 'bp_docs_single_doc_meta', 	array( $this, 'show_terms' ) );
		
		// Modify the main tax_query in the doc loop
		add_filter( 'bp_docs_tax_query', 	array( $this, 'modify_tax_query' ) );
		
		// Add the Tags column to the docs loop
		add_filter( 'bp_docs_loop_additional_th', array( $this, 'tags_th' ) );
		add_filter( 'bp_docs_loop_additional_td', array( $this, 'tags_td' ) );
		
		// Filter the message in the docs info header
		add_filter( 'bp_docs_info_header_message', array( $this, 'info_header_message' ), 10, 2 );
		
		// Add the tags filter markup
		add_filter( 'bp_docs_filter_markup',	array( $this, 'filter_markup' ) );
		
		// Adds filter arguments to a URL
		add_filter( 'bp_docs_handle_filters',	array( $this, 'handle_filters' ) );
	}
	
	/**
	 * Registers the post taxonomies with the bp_docs post type
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @param array The $bp_docs_post_type_args array created in BP_Docs::register_post_type()
	 * @return array $args The modified parameters
	 */	
	function register_with_post_type( $args ) {
		$this->taxonomies = array( /* 'category', */ 'post_tag' );
	
		// Todo: make this fine-grained for tags and/or categories
		$args['taxonomies'] = array( 'post_tag' );
		
		//$args['taxonomies'] = array( 'category', 'post_tag' );
		
		return $args;		
	}
	
	/**
	 * Saves post taxonomy terms to a doc when saved from the front end
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @param object $query The query object created by BP_Docs_Query
	 * @return int $post_id Returns the doc's post_id on success
	 */	
	function save_post( $query ) {
		
		foreach( $this->taxonomies as $tax_name ) {
			
			if ( $tax_name == 'category' )
				$tax_name = 'post_category';
		
			// Separate out the terms
			$terms = !empty( $_POST[$tax_name] ) ? explode( ',', $_POST[$tax_name] ) : array();
			
			// Strip whitespace from the terms
			foreach ( $terms as $key => $term ) {
				$terms[$key] = trim( $term );
			}
			
			$tax = get_taxonomy( $tax_name );
			
			// Hierarchical terms like categories have to be handled differently, with
			// term IDs rather than the term names themselves
			if ( !empty( $tax->hierarchical ) ) {
				$term_ids = array();
				foreach( $terms as $term ) {
					$parent = 0;
					$term_ids[] = term_exists( $term, $tax_id, $parent );
				}
			}
			
			wp_set_post_terms( $query->doc_id, $terms, $tax_name );
			
			// Store these terms in the item term cache, to be used for tag clouds etc
			$this->cache_terms_for_item( $terms, $query->doc_id );
		}
	}
	
	/**
	 * Handles taxonomy cleanup when a post is deleted
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @param int $doc_id
	 */	
	function delete_post( $doc_id ) {
		// Terms for the item (group, user, etc)
		$item_terms 	= $this->get_item_terms();
		// Terms for the doc
		$doc_terms	= wp_get_post_terms( $doc_id, 'post_tag' );

		foreach( $doc_terms as $doc_term ) {
			$term_name = $doc_term->name;
			
			// If the term is currently used (should always be true - this is a
			// sanity check)
			if ( !empty( $item_terms[$term_name] ) ) {
				// Get the array key of the entry corresponding to the doc
				// being deleted
				$key	= array_search( $doc_id, $item_terms[$term_name] );
				
				// If found, unset that item, and renumber the array
				if ( $key !== false ) {
					unset( $item_terms[$term_name][$key] );
					$item_terms[$term_name] = array_values( $item_terms[$term_name] );
				}
			}
		}
		
		$this->save_item_terms( $item_terms );
	}
	
	/**
	 * Shows a doc's taxonomy terms
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */	
	function show_terms() {
	 	foreach( $this->taxonomies as $tax_name ) {
	 		$tagtext 	= array();
	 		$tags 		= wp_get_post_terms( get_the_ID(), $tax_name );
	 		
	 		foreach( $tags as $tag ) {
	 			$tagtext[] = bp_docs_get_tag_link( array( 'tag' => $tag->name ) );
	 		}	 		
	 		
	 		echo sprintf( __( 'Tags: %s', 'bp-docs' ), implode( ', ', $tagtext ) ); 
	 	}
	}
	
	/**
	 * Store taxonomy terms and their use count for a given item
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @param array $terms The terms submitted in the most recent save
	 * @param int $doc_id The unique id of the doc
	 */	
	function cache_terms_for_item( $terms = array(), $doc_id ) {
		$existing_terms = $this->get_item_terms();
		
		// First, make sure that each submitted term is recorded
		foreach ( $terms as $term ) {
			if ( empty( $existing_terms[$term] ) || ! is_array( $existing_terms[$term] ) )
				$existing_terms[$term] = array();
			
			if ( ! in_array( $doc_id, $existing_terms[$term] ) )
				$existing_terms[$term][] = $doc_id;
		}
		
		// Then, loop through to see if any existing terms have been deleted
		foreach ( $existing_terms as $existing_term => $docs ) {
			// If the existing term is not in the list of submitted terms...
			if ( ! in_array( $existing_term, $terms ) ) {
				// ... check to see whether the current doc is listed under that
				// term. If so, that indicates that the term has been removed from
				// the doc
				$key = array_search( $doc_id, $docs );
				if ( $key !== false ) {
					unset( $docs[$key] );
				}
			}
			
			// Reset the array keys for the term's docs
			$docs = array_values( $docs );
			
			if ( empty( $docs ) ) {
				// If there are no more docs associated with the term, we can remove
				// it from the array
				unset( $existing_terms[$existing_term] );
			} else {
				// Othewise, store the docs back in the existing terms array
				$existing_terms[$existing_term] = $docs;
			}
		}
		
		// Save the terms back to the item
		$this->save_item_terms( $existing_terms );
	}
	
	/**
	 * Gets the list of terms used by an item's docs
	 *
	 * This is a dummy function that allows specific item types to hook in their own methods
	 * for retrieving metadata (groups_update_groupmeta(), get_user_meta(), etc)
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @return array $terms The item's terms
	 */	
	function get_item_terms() {
		$terms = array();
		
		return apply_filters( 'bp_docs_taxonomy_get_item_terms', $terms );
	}
	
	/**
	 * Save list of terms used by an item's docs
	 *
	 * Just a dummy hook for the moment, for the integration modules to hook into
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @return array $terms The item's terms
	 */	
	function save_item_terms( $terms ) {
		do_action( 'bp_docs_taxonomy_save_item_terms', $terms );
	}
	
	/**
	 * Modifies the tax_query on the doc loop to account for doc tags
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @return array $terms The item's terms
	 */	
	function modify_tax_query( $tax_query ) {

		// Check for the existence tag filters in the request URL
		if ( !empty( $_REQUEST['bpd_tag'] ) ) {
			// The bpd_tag argument may be comma-separated
			$tags = explode( ',', urldecode( $_REQUEST['bpd_tag'] ) );
		
			// Clean up the tag input
			foreach( $tags as $key => $value ) {
				$tags[$key] = esc_attr( $value );
			}
		
			$tax_query[] = array(
				'taxonomy'	=> 'post_tag',
				'terms'		=> $tags,
				'field'		=> 'slug'
			);
			
			if ( !empty( $_REQUEST['bool'] ) && $_REQUEST['bool'] == 'and' )
				$tax_query['operator'] = 'AND';
		}
		
		return apply_filters( 'bp_docs_modify_tax_query_for_tax', $tax_query );
	}

	/**
	 * Markup for the Tags <th> on the docs loop
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */	
	
	function tags_th() {
		?>
		
		<th scope="column" class="tags-cell"><?php _e( 'Tags', 'bpsp' ); ?></th>
		
		<?php
	}
	
	/**
	 * Markup for the Tags <td> on the docs loop
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */	
	function tags_td() {
		$tags 		= wp_get_post_terms( get_the_ID(), 'post_tag' );
		$tagtext 	= array();
	
		foreach( $tags as $tag ) {
			$tagtext[] = bp_docs_get_tag_link( array( 'tag' => $tag->name ) );
		}
		
		?>
		
		<td class="tags-cell">
			<?php echo implode( ', ', $tagtext ) ?>
		</td>
	
		<?php
	}
	
	/**
	 * Modifies the info header message to account for current tags
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @param array $message An array of the messages explaining the current view
	 * @param array $filters The filters pulled out of the $_REQUEST global
	 *
	 * @return array $message The maybe modified message array
	 */
	function info_header_message( $message, $filters ) {
		$this->current_filters = $filters;
		
		if ( !empty( $filters['tags'] ) ) {
			$tagtext = array();
			
			foreach( $filters['tags'] as $tag ) {
				$tagtext[] = bp_docs_get_tag_link( array( 'tag' => $tag ) );
			}
			
			$message[] = sprintf( __( 'You are viewing docs with the following tags: %s', 'bp-docs' ), implode( ', ', $tagtext ) );  
		}
		
		return $message;
	}
	
	/**
	 * Creates the markup for the tags filter checkboxes on the docs loop
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function filter_markup() {
		$existing_terms = $this->get_item_terms();
		
		// No need to show the filter if there are no terms to show
		if ( empty( $existing_terms ) )
			return;
	
		?>
		
		<div class="docs-filter docs-filter-tags toggleable">
			<p id="tags-toggle" class="toggle-switch"><?php _e( 'Filter by tag', 'bp-docs' ) ?></p>
	
			<ul id="tags-list" class="toggle-content">
			<?php foreach( $existing_terms as $term => $posts ) : ?>
				
				<li>
				<a href="<?php echo bp_docs_get_tag_link( array( 'tag' => $term, 'type' => 'url' ) ) ?>" title="<?php echo esc_html( $term ) ?>"><?php echo esc_html( $term ) ?> <?php printf( __( '(%d)', 'bp-docs' ), count( $posts ) ) ?></a>
				
				<?php /* Going with tag cloud type fix for now */ ?>
				<?php /*
				
				<?php
				
				$checked = empty( $this->current_filters ) || ( !empty( $this->current_filters['tags'] ) && in_array( $term, $this->current_filters['tags'] ) ) ? true : false;
				
				?>
				<label for="filter_terms[<?php echo esc_attr( $term ) ?>]"> 
					<input type="checkbox" value="1" name="filter_terms[<?php echo esc_attr( $term ) ?>]" <?php checked( $checked ) ?>/>
					<?php echo esc_html( $term ) ?> <?php printf( __( '(%d)', 'bp-docs' ), count( $posts ) ) ?>
				</label>
				*/ ?>
				</li>
			
			<?php endforeach ?>
			</ul>
		</div>
		
		<?php
	}
	
	/**
	 * Handles doc filters from a form post and translates to $_GET arguments before redirect
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 */
	function handle_filters( $redirect_url ) {
		if ( !empty( $_POST['filter_terms'] ) ) {
			$tags = array();
			
			foreach( $_POST['filter_terms'] as $term => $value ) {
				$tags[] = urlencode( $term );
			}
			
			$tags = implode( ',', $tags );
			
			$redirect_url = add_query_arg( 'bpd_tag', $tags, $redirect_url );
		}
		
		return $redirect_url;
	}
}

/**************************
 * TEMPLATE TAGS
 **************************/
 
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

?>