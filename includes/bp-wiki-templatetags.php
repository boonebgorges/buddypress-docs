<?php

/**
 * Returns the default page privacy level for a given group
 *
 * @since 1.1.0
 *
 * @return string
 */
function bp_wiki_group_default_page_privacy() {
	global $bp;
	
	if ( empty( $bp->groups->current_group->id ) )
		return false;
	
	// Avoid the extra database call if you can
	// Not loading this at setup_globals because it's not needed on most pages
	if ( !empty( $bp->groups->current_group->default_wiki_page_privacy ) ) {
		$privacy = $bp->groups->current_group->default_wiki_page_privacy;
	} else {
		$privacy = groups_get_groupmeta( $bp->groups->current_group->id, 'bp_wiki_group_wiki_default_page_privacy' );
		$bp->groups->current_group->default_wiki_page_privacy = $privacy;
	}
	
	// When no default privacy has been set, it should match the group privacy setting
	if ( empty( $privacy ) ) {
		if ( !empty( $bp->groups->current_group->status ) && 'public' != $bp->groups->current_group->status ) {
			$privacy = 'member-only';
		} else {
			$privacy = 'public';
		}
	}
	
	return apply_filters( 'bp_wiki_group_default_page_privacy', $privacy, $bp->groups->current_group->id );
}

?>