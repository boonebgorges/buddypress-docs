<div id="buddypress">

<?php include( apply_filters( 'bp_docs_header_template', bp_docs_locate_template( 'docs-header.php' ) ) ) ?>

<div class="docs-info-header">
	<?php bp_docs_info_header() ?>
</div>

<?php if ( bp_docs_is_folder_tree_view() ) : ?>
	<?php bp_locate_template( 'docs/docs-loop-tree.php', true ) ?>
<?php elseif ( bp_docs_is_folder_manage_view() ) : ?>
	<?php bp_locate_template( 'docs/manage-folders.php', true ) ?>
<?php else : ?>
	<?php bp_locate_template( 'docs/docs-loop-list.php', true ) ?>
<?php endif; ?>

</div><!-- /#buddypress -->
