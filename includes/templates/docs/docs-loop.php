
<?php include( apply_filters( 'bp_docs_header_template', $template_path . 'docs-header.php' ) ) ?>

<div class="docs-info-header">
	<?php bp_docs_info_header() ?>
</div>

<?php if ( have_posts() ) : ?>
	<table class="doctable">

	<thead>
		<tr valign="bottom">
			<th scope="column"> </th>
			<th scope="column"><?php _e( 'Title', 'bpsp' ); ?></th>
			<th scope="column"><?php _e( 'Author', 'bpsp' ); ?></th>
			<th scope="column"><?php _e( 'Created', 'bpsp' ); ?></th>
			<th scope="column"><?php _e( 'Last Edited', 'bpsp' ); ?></th>
			<th scope="column"><?php _e( 'Tags', 'bpsp' ); ?></th>
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
				<?php the_author() ?>
			</td>
			
			<td class="date-cell created-date-cell"> 
				<?php echo get_the_date() ?>
			</td>
			
			<td class="date-cell edited-date-cell"> 
				<?php echo get_the_modified_date() ?>
			</td>
			
			<td class="tags-cell">
				tags
			</td>
			
			
		</tr>
	<?php endwhile ?>        
        </tbody>


	</table>
<?php endif ?>
