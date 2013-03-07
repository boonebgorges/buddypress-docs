<?php

$GLOBALS['wp_tests_options'] = array(
    'active_plugins' => array(
	basename( dirname( dirname( __FILE__ ) ) ) . '/loader.php',
	'buddypress/bp-loader.php',
    ),
);

require getenv( 'BP_TESTS_DIR' ) . '/includes/bootstrap.php';

// include our testcase
require( dirname(__FILE__) . '/bp-docs-testcase.php' );
