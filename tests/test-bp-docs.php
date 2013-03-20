<?php

class BP_Docs_Tests extends WP_UnitTestCase {

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

	function tearDown() {
		wp_set_current_user( $this->old_current_user );
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

	/**
	 * Make sure doc activity is deleted when the doc is deleted
	 */
	function test_delete_activity_on_doc_deletion() {
		$doc_id = $this->factory->doc->create();

		$activity_args = array(
			'component' => 'docs',
			'item_id' => 1,
			'secondary_item_id' => $doc_id,
			'type' => 'bp_doc_edited',
		);

		$activity_id = $this->factory->activity->create( $activity_args );

		$activity_args2 = array(
			'component' => 'docs',
			'item_id' => 1,
			'secondary_item_id' => $doc_id,
			'type' => 'bp_doc_created',
		);

		$activity_id2 = $this->factory->activity->create( $activity_args2 );

		// Now delete using the api method
		bp_docs_trash_doc( $doc_id );

		$activities = bp_activity_get(
			array(
				'filter' => array(
					'secondary_id' => $doc_id,
					'component' => 'docs',
				),
			)
		);

		$this->assertEquals( $activities['activities'], array() );
	}

	/**
	 * see #286
	 */
	function test_change_group_association() {
		$group = $this->factory->group->create();
		$group2 = $this->factory->group->create();

		$doc_id = $this->factory->doc->create( array( 'group' => $group->id ) );

		bp_docs_set_associated_group_id( $doc_id, $group2->id );

		$this->assertEquals( bp_docs_get_associated_group_id( $doc_id ), $group2->id );
	}

	function test_set_group_association_on_create() {
		$doc_id = $this->factory->doc->create( array( 'group' => $group->id ) );

		$group = $this->factory->group->create();
		$permalink = get_permalink( $doc_id );
		$this->go_to( $permalink );

		$_POST['associated_group_id'] = $group->id;
		//unset( $_POST['associated_group_id'] );

		// We need this dummy $_POST data to make the save go through. Ugh
		$doc = $this->factory->doc->get_object_by_id( $doc_id );
		$_POST['doc_content'] = $doc->post_content;
		$_POST['doc']['title'] = $doc->post_title;

		$query = new BP_Docs_Query;
		$query->save();

		$maybe_group_id = bp_docs_get_associated_group_id( $doc_id );

		$this->assertEquals( $group->id, $maybe_group_id );
	}

	function test_delete_group_association() {
		$group = $this->factory->group->create();
		$doc_id = $this->factory->doc->create( array( 'group' => $group->id ) );
		$permalink = get_permalink( $doc_id );
		$this->go_to( $permalink );

		// Just to be sure
		unset( $_POST['associated_group_id'] );

		// We need this dummy $_POST data to make the save go through. Ugh
		$doc = $this->factory->doc->get_object_by_id( $doc_id );
		$_POST['doc_content'] = $doc->post_content;
		$_POST['doc']['title'] = $doc->post_title;

		$query = new BP_Docs_Query;
		$query->save();

		$maybe_group_id = bp_docs_get_associated_group_id( $doc_id );

		$this->assertFalse( (bool) $maybe_group_id );
	}

	function test_bp_docs_get_doc_link() {
		// rewrite - @todo This stinks
		global $wp_rewrite;
		$GLOBALS['wp_rewrite']->init();
		flush_rewrite_rules();

		$doc_id = $this->factory->doc->create( array( 'post_name' => 'foo' ) );
		$this->assertEquals( bp_docs_get_doc_link( $doc_id ), 'http://example.org/docs/foo/' );

		// Set a parent to make sure it still works
		$doc_id2 = $this->factory->doc->create();
		wp_update_post( array( 'ID' => $doc_id, 'post_parent' => $doc_id2 ) );
		$this->assertEquals( bp_docs_get_doc_link( $doc_id ), 'http://example.org/docs/foo/' );

	}

}


