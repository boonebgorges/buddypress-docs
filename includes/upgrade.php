<?php

/**
 * Upgrade functions
 *
 * @since 1.2
 */

function bp_docs_upgrade_check() {
	$upgrades        = array();
	$current_version = BP_DOCS_VERSION;
	$old_version     = bp_get_option( 'bp_docs_version' );

	if ( ! $old_version ) {
		$old_version = '1.1';
	}

	if ( version_compare( $old_version, '1.2', '<' ) ) {
		$upgrades[] = '1.2';
	}

	return $upgrades;
}

function bp_docs_upgrade_init() {
	if ( ! is_admin() || ! current_user_can( 'bp_moderate' ) ) {
		return;
	}

	$upgrades = bp_docs_upgrade_check();

	if ( ! empty( $upgrades ) ) {
		add_action( 'admin_notices', 'bp_docs_upgrade_notice' );
		bp_docs_upgrade_menu();
	}
}
add_action( 'admin_menu', 'bp_docs_upgrade_init' );

function bp_docs_upgrade_notice() {
	global $pagenow;

	if (
		'edit.php' == $pagenow &&
		isset( $_GET['post_type'] ) &&
		bp_docs_get_post_type_name() == $_GET['post_type'] &&
		isset( $_GET['page'] ) &&
		'bp-docs-upgrade' == $_GET['page']
	   ) {
		return;
	}

	?>
	<div class="message error">
		<p><?php _e( 'Thanks for updating BuddyPress Docs. We need to run a few quick operations before your new Docs is ready to use.', 'bp-docs' ) ?></p>
		<p><strong><a href="<?php echo admin_url( 'edit.php?post_type=bp_doc&page=bp-docs-upgrade' ) ?>"><?php _e( 'Click here to start the upgrade.', 'bp-docs' ) ?></a></strong></p>
	</div>
	<?php
}

function bp_docs_upgrade_menu() {
	add_submenu_page(
		'edit.php?post_type=' . bp_docs_get_post_type_name(),
		__( 'BuddyPress Docs Upgrade', 'bp-docs' ),
		__( 'Upgrade', 'bp-docs' ),
		'bp_moderate',
		'bp-docs-upgrade',
		'bp_docs_upgrade_render'
	);
}

function bp_docs_upgrade_render() {
	$url_base = admin_url( 'edit.php?post_type=' . bp_docs_get_post_type_name() . '&page=bp-docs-upgrade' );

	if ( isset( $_GET['do_upgrade'] ) && 1 == $_GET['do_upgrade'] ) {
		$status = 'upgrade';
	} else if ( isset( $_GET['success'] ) && 1 == $_GET['success'] ) {
		$status = 'complete';
	} else {
		$status = 'none';
	}

	?>
	<div class="wrap">
		<h2><?php _e( 'BuddyPress Docs Upgrade', 'bp-docs' ) ?></h2>

		<?php if ( 'none' == $status ) : ?>
			<?php
				$url = add_query_arg( 'do_upgrade', '1', $url_base );
				$url = wp_nonce_url( $url, 'bp-docs-upgrade' );
			?>

			<p><?php _e( 'Thanks for updating BuddyPress Docs. We need to run a few quick operations before your new Docs is ready to use.', 'bp-docs' ) ?></p>

			<a class="button primary" href="<?php echo $url ?>"><?php _e( 'Start the upgrade', 'bp-docs' ) ?></a>

		<?php elseif ( 'upgrade' == $status ) : ?>

			<?php check_admin_referer( 'bp-docs-upgrade' ) ?>

			<p><?php _e( 'Migrating...', 'bp-docs' ) ?></p>

			<?php
				$upgrade_status = bp_get_option( 'bp_docs_upgrade' );
				$message        = isset( $upgrade_status['message'] ) ? $upgrade_status['message'] : '';
				$refresh_url    = isset( $upgrade_status['refresh_url'] ) ? $upgrade_status['refresh_url'] : $url_base;
				//$refresh_url = add_query_arg( 'do_upgrade', '1', $url_base );
				//$refresh_url = wp_nonce_url( $refresh_url, 'bp-docs-upgrade' );
			?>

			<p><?php echo esc_html( $message ) ?></p>

			<?php bp_docs_do_upgrade() ?>

			<script type='text/javascript'>
				<!--
				function nextpage() {
					location.href = "<?php echo $refresh_url ?>";
				}
				setTimeout( "nextpage()", 1000 );
				//-->
			</script>

		<?php elseif ( 'complete' == $status ) : ?>

			<p><?php printf( __( 'Migration complete! <a href="%s">Dashboard</a>', 'bp-docs' ), admin_url() ) ?></p>

		<?php endif ?>
	</div>
	<?php
}

/**
 * Upgrade class
 */
function bp_docs_do_upgrade() {
	$upgrade_status = bp_get_option( 'bp_docs_upgrade' );
	if ( '' == $upgrade_status ) {
		$upgrade_status = array(
			'upgrades'    => array(),
			'refresh_url' => '',
			'message'     => '',
		);
		$upgrades = bp_docs_upgrade_check();

		foreach ( $upgrades as $upgrade ) {
			$func = 'bp_docs_upgrade_' . str_replace( '.', '_', $upgrade );
			if ( function_exists( $func ) ) {
				$upgrade_status['upgrades'][ $func ] = array(
					'last' => '',
					'total' => '',
				);
			}
		}
	}

	// Grab the next available upgrade
	foreach ( $upgrade_status['upgrades'] as $ufunc => $udata ) {
		$the_ufunc = $ufunc;
		$the_udata = $udata;
		break;
	}

	if ( isset( $ufunc ) && isset( $udata ) ) {
		$new_udata = call_user_func( $ufunc );
		var_dump( $new_udata );
	} else {
		// We're done
	}
}

//////////////////////////////////////////////////
//
//  1.2
//  - 'read' settings mapped onto taxonomy term
//  - associated group tax terms change
//
//////////////////////////////////////////////////
function bp_docs_upgrade_1_2() {

}

