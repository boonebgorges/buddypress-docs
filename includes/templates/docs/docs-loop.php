
<?php include( apply_filters( 'bp_docs_header_template', $template_path . 'docs-header.php' ) ) ?>

<div class="docs-info-header">
	<?php bp_docs_info_header() ?>
</div>

<?php /* Have to put this here rather than in the external stylesheet to account for slow loading pages. I feel guilty about it, but you know what, bite me. */ ?>
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

<?php if ( have_posts() ) : ?>
	<table class="doctable">

	<thead>
		<tr valign="bottom">
			<th scope="column"> </th>
			
			<th scope="column" class="title-cell<?php bp_docs_is_current_orderby_class( 'title' ) ?>">
				<a href="<?php bp_docs_order_by_link( 'title' ) ?>"><?php _e( 'Title', 'bpsp' ); ?></a>
			</th>
			
			<th scope="column" class="author-cell<?php bp_docs_is_current_orderby_class( 'author' ) ?>">
				<a href="<?php bp_docs_order_by_link( 'author' ) ?>"><?php _e( 'Author', 'bpsp' ); ?></a>
			</th>
			
			<th scope="column" class="created-date-cell<?php bp_docs_is_current_orderby_class( 'created' ) ?>">
				<a href="<?php bp_docs_order_by_link( 'created' ) ?>"><?php _e( 'Created', 'bpsp' ); ?></a>
			</th>
			
			<th scope="column" class="edited-date-cell<?php bp_docs_is_current_orderby_class( 'edited' ) ?>">
				<a href="<?php bp_docs_order_by_link( 'edited' ) ?>"><?php _e( 'Last Edited', 'bpsp' ); ?></a>
			</th>
			
			<?php do_action( 'bp_docs_loop_additional_th' ) ?>
		</tr>
        </thead>
        
        <tbody>
	<?php while ( have_posts() ) : the_post() ?>
		<tr>
			<td> </td>
			
			<td class="title-cell">
				<a href="<?php bp_docs_group_doc_permalink() ?>"><?php the_title() ?></a>
				
				<?php the_excerpt() ?>
			</td>
			
			<td class="author-cell">
				<a href="<?php echo bp_core_get_user_domain( get_the_author_meta( 'ID' ) ) ?>" title="<?php echo bp_core_get_user_displayname( get_the_author_meta( 'ID' ) ) ?>"><?php echo bp_core_get_user_displayname( get_the_author_meta( 'ID' ) ) ?></a>
			</td>
			
			<td class="date-cell created-date-cell"> 
				<?php echo get_the_date() ?>
			</td>
			
			<td class="date-cell edited-date-cell"> 
				<?php echo get_the_modified_date() ?>
			</td>
			
			<?php do_action( 'bp_docs_loop_additional_td' ) ?>		
			
		</tr>
	<?php endwhile ?>        
        </tbody>


	</table>
<?php endif ?>
