<?php

class BP_Docs_Test_Functions extends WP_UnitTestCase {
	function test_bp_docs_locate_template() {
		$f = function_exists( 'bp_core_load_template' );
		$this->assertTrue( $f );
	}
}
