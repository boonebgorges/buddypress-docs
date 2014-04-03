<?php

/**
 * @group permissions
 */
class BP_Docs_Tests_Permissions extends BP_Docs_TestCase {
	/**
	 * @group bp_docs_user_can
	 */
	function test_loggedout_user_cannot_create() {
		$this->assertFalse( bp_docs_user_can( 'create', 0 ) );
	}

	/**
	 * @group bp_docs_user_can
	 */
	function test_loggedin_user_can_create() {
		$this->assertTrue( bp_docs_user_can( 'create', 4 ) );
	}
}
