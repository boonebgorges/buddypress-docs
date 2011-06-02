<div class="item-list-tabs no-ajax" id="subnav" role="navigation">
	<ul>
		<?php bp_docs_group_tabs() ?>
	</ul>
</div><!-- .item-list-tabs -->

<?php if ( bp_docs_is_existing_doc() ) : ?>

	<h2><?php the_title() ?></h2> 

	<div class="doc-tabs">
		<ul>
			<li<?php if ( 'single' == bp_docs_current_view() ) : ?> class="current"<?php endif ?>>	
				<a href="<?php echo bp_docs_get_group_doc_permalink() ?>"><?php _e( 'Read', 'bp-docs' ) ?></a> 
			</li>
			
			<?php if ( bp_docs_current_user_can( 'edit' ) ) : ?>
				<li<?php if ( 'edit' == bp_docs_current_view() ) : ?> class="current"<?php endif ?>>	
					<a href="<?php echo bp_docs_get_group_doc_permalink() . '/' . BP_DOCS_EDIT_SLUG ?>"><?php _e( 'Edit', 'bp-docs' ) ?></a> 
				</li>
			<?php endif ?>
			
			<?php if ( bp_docs_current_user_can( 'view_history' ) ) : ?>
				<li<?php if ( 'history' == bp_docs_current_view() ) : ?> class="current"<?php endif ?>>	
					<a href="<?php echo bp_docs_get_group_doc_permalink() . '/' . BP_DOCS_HISTORY_SLUG ?>"><?php _e( 'History', 'bp-docs' ) ?></a> 
				</li>
			<?php endif ?>
		</ul>
	</div>

<?php elseif ( 'create' == bp_docs_current_view() ) : ?>
	
	<h2><?php _e( 'New Doc', 'bp-docs' ); ?></h2>

<?php endif ?>
</h2> 
