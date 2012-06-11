<?php

/**
 * BuddyPress Docs single Doc
 *
 * @package BuddyPress_Docs
 * @since 1.2
 */

?>

<?php get_header( 'buddypress' ); ?>

	<?php do_action( 'bp_before_single_doc_page' ); ?>

	<div id="content">
		<div class="padder">

		<?php do_action( 'bp_before_single_doc' ); ?>

		<h3><?php _e( 'DDD', 'buddypress' ); ?></h3>

		<?php include( bp_docs_locate_template( 'single/index.php' ) ) ?>

		<?php do_action( 'bp_after_single_doc' ); ?>

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php do_action( 'bp_after_single_doc_page' ); ?>

<?php get_sidebar( 'buddypress' ); ?>
<?php get_footer( 'buddypress' ); ?>

