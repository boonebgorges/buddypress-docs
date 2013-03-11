<div id="buddypress"> <!-- This is going to conflict with #buddypress provided by BP theme compat -->

<?php include( apply_filters( 'bp_docs_header_template', bp_docs_locate_template( 'docs-header.php' ) ) ) ?>
<!-- Match typical BP output -->
<div class="docs-info-header pagination">
	<div class="doc-search">
		<form action="" method="get">
			<input name="s" value="<?php the_search_query() ?>">
			<input name="search_submit" type="submit" value="<?php _e( 'Search', 'bp-docs' ) ?>" />
		</form>
	</div>

	<?php bp_docs_info_header() ?>
</div>

<?php bp_docs_inline_toggle_js() ?>

<?php if ( bp_docs_has_docs() ) : ?>
	<table class="doctable">

	<thead>
		<tr valign="bottom">
			<th scope="column"> </th>

			<th scope="column" class="title-cell sortable<?php bp_docs_is_current_orderby_class( 'title' ) ?>">
				<a href="<?php bp_docs_order_by_link( 'title' ) ?>"><?php _e( 'Title', 'bp-docs' ); ?><span class="sorting-indicator"></span></a>
			</th>

			<th scope="column" class="author-cell sortable<?php bp_docs_is_current_orderby_class( 'author' ) ?>">
				<a href="<?php bp_docs_order_by_link( 'author' ) ?>"><?php _e( 'Author', 'bp-docs' ); ?><span class="sorting-indicator"></span></a>
			</th>

			<th scope="column" class="created-date-cell sortable<?php bp_docs_is_current_orderby_class( 'created' ) ?>">
				<a href="<?php bp_docs_order_by_link( 'created' ) ?>"><?php _e( 'Created', 'bp-docs' ); ?><span class="sorting-indicator"></span></a>
			</th>

			<th scope="column" class="edited-date-cell sortable<?php bp_docs_is_current_orderby_class( 'modified' ) ?>">
				<a href="<?php bp_docs_order_by_link( 'modified' ) ?>"><?php _e( 'Last Edited', 'bp-docs' ); ?><span class="sorting-indicator"></span></a>
			</th>

			<?php do_action( 'bp_docs_loop_additional_th' ) ?>
		</tr>
        </thead>

        <tbody>
	<?php while ( bp_docs_has_docs() ) : bp_docs_the_doc() ?>
		<tr>
			<td> </td>

			<td class="title-cell">
				<a href="<?php bp_docs_doc_link() ?>"><?php the_title() ?></a>

				<?php the_excerpt() ?>

				<div class="row-actions">
					<?php bp_docs_doc_action_links() ?>
				</div>
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

	<div id="bp-docs-pagination">
		<div id="bp-docs-pagination-count">
			<?php printf( __( 'Viewing %1$s-%2$s of %3$s docs', 'bp-docs' ), bp_docs_get_current_docs_start(), bp_docs_get_current_docs_end(), bp_docs_get_total_docs_num() ) ?>
		</div>

		<div id="bp-docs-paginate-links">
			<?php bp_docs_paginate_links() ?>
		</div>
	</div>

<?php else: ?>

        <?php if ( bp_docs_current_user_can( 'create' ) ) : ?>
                <p class="no-docs"><?php printf( __( 'There are no docs for this view. Why not <a href="%s">create one</a>?', 'bp-docs' ), bp_docs_get_create_link() ) ?>
	<?php else : ?>
		<p class="no-docs"><?php _e( 'There are no docs for this view.', 'bp-docs' ) ?></p>
        <?php endif ?>

<?php endif ?>

</div><!-- /#buddypress -->
