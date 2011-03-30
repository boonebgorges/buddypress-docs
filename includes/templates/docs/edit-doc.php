
<?php include( apply_filters( 'bp_docs_header_template', $template_path . 'docs-header.php' ) ) ?>

<?php
// No media support at the moment. Want to integrate with something like BP Group Documents
// include_once ABSPATH . '/wp-admin/includes/media.php' ;

require_once ABSPATH . '/wp-admin/includes/post.php' ;
wp_tiny_mce();

?>
<form action="" method="post" class="standard-form" id="doc-form">
    <div class="doc-header">
        <h4><?php _e( 'New Doc', 'bpsp' ); ?></h4>
    </div>
    <div class="doc-content-wrapper">
        <div id="doc-content-title">
		<label for="doc[title]"><?php _e( 'Title', 'bp-docs' ) ?></label>        	
		<input type="text" id="doc-title" name="doc[title]" class="long" value="<?php bp_docs_edit_doc_title() ?>" />
        </div>
        <div id="doc-content-textarea">
		<label id="content-label" for="doc[content]"><?php _e( 'Content', 'bp-docs' ) ?></label>        
		<div id="editor-toolbar">
			<?php /* No media support for now
			<div id="media-toolbar">
			    <?php  echo bpsp_media_buttons(); ?>
			</div>
			*/ ?>
			<?php the_editor( bp_docs_get_edit_doc_content(), 'doc[content]' ); ?>
		</div>
        </div>
        
        <div id="doc-meta">
        	<div id="doc-tax" class="doc-meta-box">
			<?php bp_docs_post_tags_meta_box();
			/* bp_docs_post_categories_meta_box(); */ ?>
        	</div>
		
		<div id="doc-parent" class="doc-meta-box">
			<div class="toggleable">
				<label class="toggle-switch" id="parent-toggle" for="parent_id"><?php _e( 'Parent', 'bp-docs' ) ?></label>
	
				<div class="toggle-content">
					<?php bp_docs_edit_parent_dropdown() ?>
				</div>
			</div>
		</div>
		
		<div id="doc-settings" class="doc-meta-box">
			<div class="toggleable">
				<p class="toggle-switch" id="settings-toggle"><?php _e( 'Settings', 'bp-docs' ) ?></p>
	
				<div class="toggle-content">
					<?php bp_docs_doc_settings_markup() ?>
				</div>
			</div>
		</div>
        </div>
        
        <div style="clear: both"> </div>
        
        <div id="doc-submit-options">
        
        	<?php wp_nonce_field( 'bp_docs_save' ) ?>
        
		<input type="submit" name="doc-edit-submit" id="doc-edit-submit" value="<?php _e( 'Save', 'bp-docs' ) ?>"> <a href="<?php bp_docs_group_doc_permalink() ?>" class="action safe"><?php _e( 'Cancel', 'bp-docs' ); ?></a>
            
            	<?php if ( bp_docs_current_user_can( 'manage' ) ) : ?><a class="delete-doc-button" href="<?php echo wp_nonce_url( bp_docs_get_group_doc_permalink() . '/' . BP_DOCS_DELETE_SLUG, 'bp_docs_delete' ) ?>">Delete</a><?php endif ?>
        </div>
        
        
        <div style="clear: both"> </div>
    </div>
</form>

<?php bp_docs_inline_toggle_js() ?>

<script type="text/javascript" >
    var tb_closeImage = "/wp-includes/js/thickbox/tb-close.png";
</script>

