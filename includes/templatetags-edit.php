<?php

/**
 * This file contains the template tags used on the Docs edit and create screens. They are
 * separated out so that they don't need to be loaded all the time.
 *
 * @package BuddyPress Docs
 */
 
/**
 * Echoes the output of bp_docs_get_edit_doc_title()
 *
 * @package BuddyPress Docs
 * @since 1.0
 */
function bp_docs_edit_doc_title() {
	echo bp_docs_get_edit_doc_title();
}
	/**
	 * Returns the title of the doc currently being edited, when it exists
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @return string Doc title
	 */
	function bp_docs_get_edit_doc_title() {
		global $bp;
		
		if ( empty( $bp->bp_docs->current_post ) || empty( $bp->bp_docs->current_post->post_title ) ) {
			$title = '';
		} else {
			$title = $bp->bp_docs->current_post->post_title;
		}
			
		return apply_filters( 'bp_docs_get_edit_doc_title', $title );
	}

/**
 * Echoes the output of bp_docs_get_edit_doc_content()
 *
 * @package BuddyPress Docs
 * @since 1.0
 */
function bp_docs_edit_doc_content() {
	echo bp_docs_get_edit_doc_content();
}
	/**
	 * Returns the content of the doc currently being edited, when it exists
	 *
	 * @package BuddyPress Docs
	 * @since 1.0
	 *
	 * @return string Doc content
	 */
	function bp_docs_get_edit_doc_content() {
		global $bp;
		
		if ( empty( $bp->bp_docs->current_post ) || empty( $bp->bp_docs->current_post->post_content ) ) {
			$content = '';
		} else {
			$content = $bp->bp_docs->current_post->post_content;
		}
			
		return apply_filters( 'bp_docs_get_edit_doc_content', $content );
	}

/**
 * Display post tags form fields. Based on WP core's post_tags_meta_box()
 *
 * @package BuddyPress Docs
 * @since 1.0
 *
 * @param object $post
 */
function bp_docs_post_tags_meta_box($post) {
	global $bp;
	
	require_once(ABSPATH . '/wp-admin/includes/taxonomy.php');
        		
	$defaults = array('taxonomy' => 'post_tag');
	if ( !isset($box['args']) || !is_array($box['args']) )
		$args = array();
	else
		$args = $box['args'];
	extract( wp_parse_args($args, $defaults), EXTR_SKIP );
	
	$tax_name = esc_attr($taxonomy);
	$taxonomy = get_taxonomy($taxonomy);
	$disabled = !current_user_can($taxonomy->cap->assign_terms) ? 'disabled="disabled"' : '';
?>
<div class="tagsdiv" id="<?php echo $tax_name; ?>">
	<div class="jaxtag">
	<div class="nojs-tags hide-if-js">
	<p><?php echo $taxonomy->labels->add_or_remove_items; ?></p>
	<textarea name="<?php echo "doc[$tax_name]"; ?>" rows="3" cols="20" class="the-tags" id="tax-input-<?php echo $tax_name; ?>" <?php echo $disabled; ?>><?php echo get_terms_to_edit( $bp->bp_docs->current_post->ID, $tax_name ); // textarea_escaped by esc_attr() ?></textarea></div>
 	<?php if ( current_user_can($taxonomy->cap->assign_terms) ) : ?>
	<div class="ajaxtag hide-if-no-js">
		<label class="screen-reader-text" for="new-tag-<?php echo $tax_name; ?>"><?php echo $box['title']; ?></label>
		<div class="taghint"><?php echo $taxonomy->labels->add_new_item; ?></div>
		<p><input type="text" id="new-tag-<?php echo $tax_name; ?>" name="newtag[<?php echo $tax_name; ?>]" class="newtag form-input-tip" size="16" autocomplete="off" value="" />
		<input type="button" class="button tagadd" value="<?php esc_attr_e('Add'); ?>" tabindex="3" /></p>
	</div>
	<p class="howto"><?php echo esc_attr( $taxonomy->labels->separate_items_with_commas ); ?></p>
	<?php endif; ?>
	</div>
	<div class="tagchecklist"></div>
</div>
<?php if ( current_user_can($taxonomy->cap->assign_terms) ) : ?>
<p class="hide-if-no-js"><a href="#titlediv" class="tagcloud-link" id="link-<?php echo $tax_name; ?>"><?php echo $taxonomy->labels->choose_from_most_used; ?></a></p>
<?php endif; ?>
<?php
}


/**
 * Display post categories form fields.
 *
 * @since 2.6.0
 *
 * @param object $post
 */
function bp_docs_post_categories_meta_box( $post, $box ) {
	$defaults = array('taxonomy' => 'category');
	if ( !isset($box['args']) || !is_array($box['args']) )
		$args = array();
	else
		$args = $box['args'];
	extract( wp_parse_args($args, $defaults), EXTR_SKIP );
	$tax = get_taxonomy($taxonomy);

	?>
	<div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">
		<ul id="<?php echo $taxonomy; ?>-tabs" class="category-tabs">
			<li class="tabs"><a href="#<?php echo $taxonomy; ?>-all" tabindex="3"><?php echo $tax->labels->all_items; ?></a></li>
			<li class="hide-if-no-js"><a href="#<?php echo $taxonomy; ?>-pop" tabindex="3"><?php _e( 'Most Used' ); ?></a></li>
		</ul>

		<div id="<?php echo $taxonomy; ?>-pop" class="tabs-panel" style="display: none;">
			<ul id="<?php echo $taxonomy; ?>checklist-pop" class="categorychecklist form-no-clear" >
				<?php $popular_ids = wp_popular_terms_checklist($taxonomy); ?>
			</ul>
		</div>

		<div id="<?php echo $taxonomy; ?>-all" class="tabs-panel">
			<?php
            $name = ( $taxonomy == 'category' ) ? 'post_category' : 'tax_input[' . $taxonomy . ']';
            echo "<input type='hidden' name='{$name}[]' value='0' />"; // Allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
            ?>
			<ul id="<?php echo $taxonomy; ?>checklist" class="list:<?php echo $taxonomy?> categorychecklist form-no-clear">
				<?php wp_terms_checklist($post->ID, array( 'taxonomy' => $taxonomy, 'popular_cats' => $popular_ids ) ) ?>
			</ul>
		</div>
	<?php if ( current_user_can($tax->cap->edit_terms) ) : ?>
			<div id="<?php echo $taxonomy; ?>-adder" class="wp-hidden-children">
				<h4>
					<a id="<?php echo $taxonomy; ?>-add-toggle" href="#<?php echo $taxonomy; ?>-add" class="hide-if-no-js" tabindex="3">
						<?php
							/* translators: %s: add new taxonomy label */
							printf( __( '+ %s' ), $tax->labels->add_new_item );
						?>
					</a>
				</h4>
				<p id="<?php echo $taxonomy; ?>-add" class="category-add wp-hidden-child">
					<label class="screen-reader-text" for="new<?php echo $taxonomy; ?>"><?php echo $tax->labels->add_new_item; ?></label>
					<input type="text" name="new<?php echo $taxonomy; ?>" id="new<?php echo $taxonomy; ?>" class="form-required form-input-tip" value="<?php echo esc_attr( $tax->labels->new_item_name ); ?>" tabindex="3" aria-required="true"/>
					<label class="screen-reader-text" for="new<?php echo $taxonomy; ?>_parent">
						<?php echo $tax->labels->parent_item_colon; ?>
					</label>
					<?php wp_dropdown_categories( array( 'taxonomy' => $taxonomy, 'hide_empty' => 0, 'name' => 'new'.$taxonomy.'_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => '&mdash; ' . $tax->labels->parent_item . ' &mdash;', 'tab_index' => 3 ) ); ?>
					<input type="button" id="<?php echo $taxonomy; ?>-add-submit" class="add:<?php echo $taxonomy ?>checklist:<?php echo $taxonomy ?>-add button category-add-sumbit" value="<?php echo esc_attr( $tax->labels->add_new_item ); ?>" tabindex="3" />
					<?php wp_nonce_field( 'add-'.$taxonomy, '_ajax_nonce-add-'.$taxonomy, false ); ?>
					<span id="<?php echo $taxonomy; ?>-ajax-response"></span>
				</p>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

?>