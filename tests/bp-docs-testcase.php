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

		$this->old_current_user = get_current_user_id();
		$this->set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );
	}

	public function tearDown() {
		parent::tearDown();
		$this->set_current_user( $this->old_current_user );
	}

	/**
	 * WP's core tests use wp_set_current_user() to change the current
	 * user during tests. BP caches the current user differently, so we
	 * have to do a bit more work to change it
	 *
	 * @global BuddyPres $bp
	 */
	function set_current_user( $user_id ) {
		global $bp;
		$bp->loggedin_user->id = $user_id;
		wp_set_current_user( $user_id );
	}
}
