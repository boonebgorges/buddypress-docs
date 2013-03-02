<?php

$GLOBALS['wp_tests_options'] = array(
    'active_plugins' => array(
	basename( dirname( dirname( __FILE__ ) ) ) . '/loader.php',
	'buddypress/bp-loader.php',
    ),
);

require getenv( 'WP_TESTS_DIR' ) . '/includes/bootstrap.php';

// bootstrap BP
// @todo work with the proper BP test suite
require( BP_PLUGIN_DIR . 'bp-core/admin/bp-core-schema.php' );
$components = array( 'groups' => 1, 'activity' => 1 );
bp_core_install( $components );
bp_update_option( 'bp-active-components', $components );
bp_core_add_page_mappings( $components, 'delete' );

// include our testcase
require( dirname(__FILE__) . '/bp-docs-testcase.php' );
