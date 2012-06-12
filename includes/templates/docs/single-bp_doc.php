<?php

/**
 * BuddyPress Docs single Doc
 *
 * @package BuddyPress_Docs
 * @since 1.2
 */

global $wp_query;
var_dump( $wp_query->query_vars );

?>

<?php get_header( 'buddypress' ); ?>

	<?php do_action( 'bp_before_single_doc_page' ); ?>

	<div id="content">
		<div class="padder">

		<?php do_action( 'bp_before_single_doc' ); ?>

		<?php include( bp_docs_locate_template( 'single/index.php' ) ) ?>

		<?php do_action( 'bp_after_single_doc' ); ?>

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php do_action( 'bp_after_single_doc_page' ); ?>

<?php get_sidebar( 'buddypress' ); ?>
<?php get_footer( 'buddypress' ); ?>

