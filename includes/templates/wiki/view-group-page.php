<?php
global $bp;
echo bp_wiki_group_breadcrumbs();
// Echo our breadcrumbs for navecho bp_wiki_group_breadcrumbs();
// Get wiki page based on slug$wiki_page = bp_wiki_get_page_from_slug( $bp->action_variables[0] );
if ( bp_wiki_can_view_wiki_page( $wiki_page->ID ) ) {
	/* Wiki Page Summary */	?>	<div class="wiki-hidden" id="wiki_page_id" /><?php echo $wiki_page->ID; ?></div>
	<div class="wiki-group-page-title-bar">
		<span id="wiki-page-title-image"><img src="<?php echo apply_filters( 'bp_wiki_locate_group_wiki_title_image', 'images/wiki-title.png' ); ?>" class="wiki-group-page-title-image" alt="Wiki Title"/></span>
			<span id="wiki-page-title-text"><?php echo $wiki_page->post_title; ?></span>
		<?php
			if ( bp_wiki_can_edit_wiki_page( $wiki_page->ID ) ) {
				?>				
				<span id="wiki-group-page-edit-title-button">					
					<button class="wiki" onclick="wikiGroupPageEditTitleStart();return false;"><?php _e( 'Edit Title', 'bp-wiki' ); ?></button>				
				</span>
				<div id="wiki-group-page-edit-page-button">					
					<a class="button" href="<?php echo bp_wiki_get_group_page_url( $bp->groups->current_group->id, $wiki_page->ID ); ?>/edit"><?php _e( 'Edit Page', 'bp-wiki' ); ?></a>				
				</div>
				<?php
			}
		?>
	</div>	
	<div id="wiki-page-content-box" class="wiki-group-page-content">
		<?php 		
		if ( $wiki_page->post_content != '' ) {						
			echo apply_filters( 'the_content', $wiki_page->post_content );
		} else {
			echo '<p>' . __( 'No content has been added to this page yet.' ) . '</p>';
			if ( bp_wiki_can_edit_wiki_page( $wiki_page->ID ) ) {
				echo '<p>' . __( 'You can edit this page by clicking on the button(s) to the right of the page title.' ) . '</p>';
			}
		}				
		?>	
	</div>	
	<?php
	
	/*  Wiki Revisions */
	if ( WP_POST_REVISIONS == 1 ) {
		?>		<!--<div class="wiki-group-page-title-bar">			<img src="<?php echo apply_filters( 'bp_wiki_locate_group_wiki_revisions_image', 'images/wiki-revisions.png' ); ?>" class="wiki-group-page-title-image"/>
			<?php _e( 'Revisions' ); ?>
			<div id="wiki-group-page-revisions-title-buttons" class="wiki-group-page-buttons">
				<a href="http://blah.com" class="wiki"><span class="wiki"><?php _e( 'View Revision History' ); ?></span></a>
			</div>
		</div>
		<div class="wiki-group-page-content">		</div>-->		
	<?php
	}
	
	/* Wiki Comments */	
	if ( $wiki_page->comment_status == 'open' ) {	?>		<div class="wiki-group-page-title-bar">
			<img src="<?php echo apply_filters( 'bp_wiki_locate_group_wiki_comments_image', 'images/wiki-comments.gif' ); ?>" class="wiki-group-page-title-image"/>
			<?php _e( 'Comments' ); ?>
			<div id="wiki-group-page-comments-title-buttons" class="wiki-group-page-title-buttons">
			</div>
		</div>
		<div class="wiki-group-page-content">		
			<?php 
			if ( is_user_logged_in() && bp_group_is_member() ) {
				require_once( apply_filters( 'bp_wiki_locate_group_wiki_comment_form', 'activity/comment_form.php' ) );
			} 
			?>

			<div class="activity single-group">
				<?php 
				require_once( apply_filters( 'bp_wiki_locate_group_wiki_comments', 'activity/comments.php' ) ); 
				?>
			</div>
		</div>
		<?php	}
} else {
	?>
	<div id="message" class="warning">		<p><?php _e( 'You do not have access to view this wiki page.' ); ?></p>	</div>	<?php
}
?>