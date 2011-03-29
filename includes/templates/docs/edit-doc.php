
<style type="text/css">
/**
 * Editor Screens
 */
#editor-toolbar { margin: 10px 0 0; }
#editorcontainer {
    border: 1px solid #DDD;
    border-collapse: separate;
    -moz-border-radius: 6px 6px 0 0;
    -webkit-border-top-right-radius: 6px;
    -webkit-border-top-left-radius: 6px;
    -khtml-border-top-right-radius: 6px;
    -khtml-border-top-left-radius: 6px;
    border-top-right-radius: 6px;
    border-top-left-radius: 6px;
    clear: both;
}
#editorcontainer table *,
#mce_fullscreen_container table * { padding: 0; }
#editorcontainer textarea {
    width: 99%; border: none;
    border-radius: 0; -webkit-border-radius: 0; -moz-border-radius: 0; margin-top: -1px; }
#edButtonHTML,
#edButtonPreview {
    background-color: #F1F1F1; padding: 2px 10px;
    -moz-border-radius-topleft: 5px; -moz-border-radius-topright: 5px;
    -webkit-border-top-left-radius: 5px; -webkit-border-top-right-radius: 5px;
    border-top-left-radius: 5px; border-top-right-radius: 5px;
    float: right; margin-right: 5px; cursor: pointer; border: 1px solid #DDD;
    border-bottom: 0; margin-bottom: -1px; color: #999; }
#edButtonHTML { margin-right: 10px; }
#edButtonHTML.active,
#edButtonPreview.active {
    color: #444; cursor: pointer; font-weight: bold; background-color: #EEE; border-bottom: 1px solid #EEE; }
#ed_toolbar {
    clear: both; background-color: #EEE; padding: 2px; border: 1px solid #DDD;
    border-radius: 5px 5px 0 0; -webkit-border-radius: 5px 5px 0 0; -moz-border-radius: 5px 5px 0 0; }
#ed_toolbar input { padding: 2px 4px; margin: 1px; }
.mceToolbar { padding: 2px; background: #EEE; }
#mce_fullscreen_container .mceToolbar { padding: 4px 2px; background: #EEE; }
#editorcontainer .mceStatusbar { border-top: 1px solid #DDD; background: #EEE; }
#editorcontainer .mceStatusbar div { padding: 2px; }
#mce_fullscreen_container .mceStatusbar { border-top: 1px solid #DDD; background: #EEE; }
#mce_fullscreen_container .mceStatusbar div { padding: 2px; }
#editorcontainer .mceButton,
#editorcontainer .mceListBox td a,
#editorcontainer .mceSplitButton td a { border: 1px solid #CACACA; color: #444; }
#mce_fullscreen_container .mceButton,
#mce_fullscreen_container .mceListBox td a,
#mce_fullscreen_container .mceSplitButton td a { border: 1px solid #CACACA; color: #444; }
.mceMenu,
.mceColorSplitMenu { border: 1px solid #CACACA; background-color: #FFF; }
.mceMenuItem a { color: #444; cursor: pointer; }
.mceMenuItem:hover { background: #EEE; }
.zerosize { display: none; }

#mceModalBlocker { background: #000; }
#mce_fullscreen_container { background: #fff; }

/* Media Buttons */
#media-toolbar { padding: 4px 4px 0; float: left; }
#media-toolbar a { margin: 0 5px; }

/* Modal windows */
.clearlooks2 .mceFocus .mceTop .mceLeft {
    background: #444;
    border-left: 1px solid #999;
    border-top: 1px solid #999;
    -moz-border-radius: 4px 0 0 0;
    -webkit-border-top-left-radius: 4px;
    -khtml-border-top-left-radius: 4px;
    border-top-left-radius: 4px;
}

.clearlooks2 .mceFocus .mceTop .mceRight {
    background: #444;
    border-right: 1px solid #999;
    border-top: 1px solid #999;
    border-top-right-radius: 4px;
    -khtml-border-top-right-radius: 4px;
    -webkit-border-top-right-radius: 4px;
    -moz-border-radius: 0 4px 0 0;
}

.clearlooks2 .mceMiddle .mceLeft {
    background: #f1f1f1;
    border-left: 1px solid #999;
}

.clearlooks2 .mceMiddle .mceRight {
    background: #f1f1f1;
    border-right: 1px solid #999;
}

.clearlooks2 .mceBottom {
    background: #f1f1f1;
    border-bottom: 1px solid #999;
}

.clearlooks2 .mceBottom .mceLeft {
    background: #f1f1f1;
    border-bottom: 1px solid #999;
    border-left: 1px solid #999;
}

.clearlooks2 .mceBottom .mceCenter {
    background: #f1f1f1;
    border-bottom: 1px solid #999;
}

.clearlooks2 .mceBottom .mceRight {
    background: #f1f1f1;
    border-bottom: 1px solid #999;
    border-right: 1px solid #999;
}

.clearlooks2 .mceFocus .mceTop span {
    color: #e5e5e5;
}

.clearlooks2 .mceMiddle .mceLeft,
.clearlooks2 .mceMiddle .mceRight { background: #EEE !important; }
</style>

<?php include( apply_filters( 'bp_docs_header_template', $template_path . 'docs-header.php' ) ) ?>

<?php
include_once ABSPATH . '/wp-admin/includes/media.php' ;
require_once ABSPATH . '/wp-admin/includes/post.php' ;
wp_tiny_mce();

?>
<form action="" method="post" class="standard-form" id="doc-form">
    <div class="doc-header">
        <h4><?php _e( 'New Doc', 'bpsp' ); ?></h4>
    </div>
    <div class="doc-content-wrapper">
        <div id="doc-content-title">
            <input type="text" id="doc-title" name="doc[title]" class="long" value="<?php bp_docs_edit_doc_title() ?>" />
        </div>
        <div id="doc-content-textarea">
            <div id="editor-toolbar">
                <div id="media-toolbar">
                    <?php /* echo bpsp_media_buttons(); */ ?>
                </div>
                <?php the_editor( bp_docs_get_edit_doc_content(), 'doc[content]' ); ?>
            </div>
        </div>
        
        <div id="doc-meta">
        	<?php
        	
        	bp_docs_post_tags_meta_box();
        	/* bp_docs_post_categories_meta_box(); */
        	?>
        </div>
        
        <div id="new-assignment-content-options">
            <input type="hidden" id="new-assignment-post-object" name="assignment[object]" value="group"/>
            <input type="hidden" id="new-assignment-post-in" name="assignment[group_id]" value="<?php echo $group_id; ?>">
            <?php echo $nonce ? $nonce: ''; ?>
            <input type="submit" name="doc-edit-submit" id="new-assignment-submit" value="<?php _e( 'Save', 'bp-docs' ) ?>">
            <div class="alignright submits">
                <?php if( $delete_nonce ): ?>
                    <a href="<?php echo $delete_nonce; ?>" class="action alert"><?php _e( 'Delete Assignment', 'bpsp' ); ?></a>
                <?php endif; ?>
                <a href="<?php bp_docs_group_doc_permalink() ?>" class="action safe"><?php _e( 'Cancel/Go back', 'bpsp' ); ?></a>
            </div>
        </div>
    </div>
</form>
<script type="text/javascript" >
    var tb_closeImage = "/wp-includes/js/thickbox/tb-close.png";
</script>

