<?php

/**
 * BuddyPress Docs Directory
 *
 * @package BuddyPress_Docs
 * @since 1.2
 */

?>

<?php get_header( 'buddypress' ); ?>

	<?php do_action( 'bp_before_directory_docs_page' ); ?>

	<div id="content">
		<div class="padder">

		<?php do_action( 'bp_before_directory_docs' ); ?>

		<?php include( bp_docs_locate_template( 'docs-loop.php' ) ) ?>

		<?php do_action( 'bp_after_directory_docs' ); ?>

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php do_action( 'bp_after_directory_docs_page' ); ?>

<?php get_sidebar( 'buddypress' ); ?>
<?php get_footer( 'buddypress' ); ?>

