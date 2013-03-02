<?php

class BP_Docs_TestCase extends WP_UnitTestCase {

	function setUp() {
		parent::setUp();

		// @todo Temporary implementation. For now I'm shipping the BP
		// factory with the plugin

		require_once( dirname(__FILE__) . '/bp-factory.php' );
		$this->factory->activity = new BP_UnitTest_Factory_For_Activity( $this->factory );
		$this->factory->group = new BP_UnitTest_Factory_For_Group( $this->factory );

		require_once( dirname(__FILE__) . '/factory.php' );
		$this->factory->doc = new BP_Docs_UnitTest_Factory_For_Doc( $this->factory );
	}

}
