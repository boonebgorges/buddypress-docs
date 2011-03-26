<?php
global $bp;
echo bp_wiki_group_breadcrumbs();
// Get the group url
$wiki_group = new BP_Groups_Group( $bp->groups->current_group->id, false, false );	 	
$group_url = bp_get_group_permalink( $wiki_group );
?>
<div class="bp-wiki-index-text">
	<p><?php echo groups_get_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_index_text' ); ?></p>
	<?php
	if ( bp_wiki_user_can_create_group_page() ) {?>
		<a class="button" href="<?php echo $group_url . BP_WIKI_GROUP_WIKI_SLUG; ?>/new"><?php _e( 'Create New Page', 'bp-wiki' ); ?></a>
	<?php
	}
	?>
</div>
<div id="bp-wiki-group-page-index">
<?php
$two_column = 'twocolumn';
$group_wiki_page_ids_array = maybe_unserialize( groups_get_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_page_ids' ) );
if ( $group_wiki_page_ids_array == '' ) {
	$no_pages_found = true;
} else {
	$no_pages_found = false;
}
// For each of those pages, check if current user can view based on group settings/membership
foreach ( (array)$group_wiki_page_ids_array as $key => $group_wiki_page_id ) {
	if ( bp_wiki_can_view_wiki_page( $group_wiki_page_id ) ) {
	
		$can_view_any_pages = true;
		$wiki_page = get_post( $group_wiki_page_id );	
		$alt = '';				
		if ( !( $key % 2 ) ) $alt = ' alt';			
	
		echo '<div class="bp-wiki-index-divider' . $alt . ' '. $two_column . '">';	
		echo '	<div class="bp-wiki-index-page-title"><a href="' . bp_wiki_get_group_page_url( $bp->groups->current_group->id, $group_wiki_page_id ) . '">' . $wiki_page->post_title . '</a></div>';
		echo '	<div class="bp-wiki-index-excerpt">' . $wiki_page->post_excerpt . '</div>';
		echo '	<div class="bp-wiki-index-meta">Last edit by: ' . bp_core_get_userlink( $wiki_page->post_author ) . __( ' at ' ) . bp_wiki_to_wiki_date_format( $wiki_page->post_modified );
		// only show edit link if is group member and has edit privileges for this page. 
		if ( bp_wiki_can_edit_wiki_page( $group_wiki_page_id ) ) {
			echo ' - <a href="' . bp_wiki_get_group_page_url( $bp->groups->current_group->id, $group_wiki_page_id ) . '/edit">'.__('Edit', 'bp-wiki').'</a>';
		}
		echo '</div></div>';
	}
}
echo '</div>';
if ( $no_pages_found ) {
	echo '<div id="message" class="warning"><p>' .__( 'There are currently no pages in this group.', 'bp-wiki' ) . '</p></div>';
} elseif ( !$can_view_any_pages ) {
	echo '<div id="message" class="warning"><p>' .__( 'You do not have access to view any of the pages of this group.', 'bp-wiki' ) . '</p></div>';
}
?>