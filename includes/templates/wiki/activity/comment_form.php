<form action="<?php bp_wiki_activity_post_form_action(); ?>" method="post" id="whats-new-form" name="whats-new-form">

	<div id="whats-new-avatar">
		<a href="<?php echo bp_loggedin_user_domain(); ?>">
			<?php bp_loggedin_user_avatar( 'width=' . BP_AVATAR_THUMB_WIDTH . '&height=' . BP_AVATAR_THUMB_HEIGHT ) ?>
		</a>
	</div>

	<div id="whats-new-content">
		<div id="whats-new-textarea">
			<textarea name="whats-new" id="whats-new" cols="50" rows="10"><?php if ( isset( $_GET['r'] ) ) : ?>@<?php echo esc_attr( $_GET['r'] ) ?> <?php endif; ?></textarea>
		</div>

		<div id="whats-new-options">
			<input type="hidden" name="bp_wiki_comment_form" id="bp_wiki_comment_form" value="yes">
			<input type="hidden" name="bp_wiki_page_id" id="bp_wiki_page_id" value="<?php echo $wiki_page->ID; ?>">
			<div id="whats-new-submit">
				<span class="ajax-loader"></span> &nbsp;
				<input type="submit" name="bp-wiki-comment-submit" id="bp-wiki-comment-submit" value="<?php _e( 'Post Comment', 'bp-wiki' ) ?>" />
			</div>
		</div>
	</div>

	<?php wp_nonce_field( 'post_update', '_wpnonce_post_update' ); ?>

</form>
