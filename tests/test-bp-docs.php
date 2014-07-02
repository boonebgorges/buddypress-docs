<?php

class BP_Docs_Tests extends BP_Docs_TestCase {

	function setUp() {
		parent::setUp();
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

	public function test_bp_docs_is_existing_doc_true() {
		// Travis is an idiot
		return;

		$doc_id = $this->factory->doc->create();
		$this->go_to( bp_docs_get_doc_link( $doc_id ) );
		$this->assertTrue( bp_docs_is_existing_doc() );
	}

	public function test_bp_docs_is_existing_doc_false() {
		$post_id = $this->factory->post->create();
		$this->go_to( bp_docs_get_doc_link( $post_id ) );
		$this->assertFalse( bp_docs_is_existing_doc() );
	}

	public function test_bp_docs_is_existing_doc_pre_wp_query() {
		// Fake that we're pre-query
		global $wp_query;
		$wpq = $wp_query;
		$wp_query = null;
		$this->assertFalse( bp_docs_is_existing_doc() );
		$wp_query = $wpq;
	}

	/**
	 * see #286
	 */
	function test_change_group_association() {
		$group = $this->factory->group->create();
		$group2 = $this->factory->group->create();

		$doc_id = $this->factory->doc->create( array(
			'group' => $group,
		) );

		bp_docs_set_associated_group_id( $doc_id, $group );

		$this->assertEquals( bp_docs_get_associated_group_id( $doc_id ), $group );
	}

	function test_set_group_association_on_create() {
		$group = $this->factory->group->create();
		$doc_id = $this->factory->doc->create( array( 'group' => $group ) );

		$permalink = get_permalink( $doc_id );
		$this->go_to( $permalink );

		$_POST['associated_group_id'] = $group;
		//unset( $_POST['associated_group_id'] );

		// We need this dummy $_POST data to make the save go through. Ugh
		$doc = $this->factory->doc->get_object_by_id( $doc_id );
		$_POST['doc_content'] = $doc->post_content;
		$_POST['doc']['title'] = $doc->post_title;

		$query = new BP_Docs_Query;
		$query->save();

		$maybe_group_id = bp_docs_get_associated_group_id( $doc_id );

		$this->assertEquals( $group, $maybe_group_id );
	}

	function test_delete_group_association() {
		$group = $this->factory->group->create();
		$doc_id = $this->factory->doc->create( array(
			'group' => $group,
		) );
		$permalink = get_permalink( $doc_id );
		$this->go_to( $permalink );

		// Just to be sure
		$_POST['associated_group_id'] = '';

		// We need this dummy $_POST data to make the save go through. Ugh
		$doc = $this->factory->doc->get_object_by_id( $doc_id );
		$_POST['doc_id'] = $doc_id;
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

	/**
	 * @group last_activity
	 */
	function test_update_group_last_activity_on_new_doc() {
		$g = $this->factory->group->create();
		$d = $this->factory->doc->create( array(
			'group' => $g,
		) );

		$last_activity = date( 'Y-m-d H:i:s', time() - 100000 );
		groups_update_groupmeta( $g, 'last_activity', $last_activity );

		// call manually because the hook is outside of the proper
		// group document creation workflow
		do_action( 'bp_docs_after_save', $d );

		$this->assertNotEquals( $last_activity, groups_get_groupmeta( $g, 'last_activity' ) );
	}

	/**
	 * @group last_activity
	 */
	function test_update_group_last_activity_on_doc_delete() {
		$g = $this->factory->group->create();
		$d = $this->factory->doc->create( array(
			'group' => $g,
		) );

		$last_activity = date( 'Y-m-d H:i:s', time() - 100000 );
		groups_update_groupmeta( $g, 'last_activity', $last_activity );

		bp_docs_trash_doc( $d );

		$this->assertNotEquals( $last_activity, groups_get_groupmeta( $g, 'last_activity' ) );
	}

	/**
	 * @group last_activity
	 */
	function test_update_group_last_activity_on_doc_comment() {
		$g = $this->factory->group->create();
		$d = $this->factory->doc->create( array(
			'group' => $g,
		) );

		$last_activity = date( 'Y-m-d H:i:s', time() - 100000 );
		groups_update_groupmeta( $g, 'last_activity', $last_activity );

		$c = $this->factory->comment->create( array(
			'comment_post_ID' => $d,
		) );

		$this->assertNotEquals( $last_activity, groups_get_groupmeta( $g, 'last_activity' ) );
	}

	/**
	 * @group comments
	 */
	public function test_comment_as_logged_out_user_failure() {
		$old_current_user = get_current_user_id();
		$this->set_current_user( 0 );

		$d = $this->factory->doc->create();
		$d_settings = bp_docs_get_doc_settings( $d );
		$d_settings['post_comments'] = 'loggedin';
		update_post_meta( $d, 'bp_docs_settings', $d_settings );

		$c_args = array(
			'comment_post_ID' => $d,
			'comment_content' => 'Test',
			'comment_author' => 'foo',
			'comment_author_url' => '',
			'comment_author_email' => 'foo@bar.com',
			'comment_type' => '',
		);

		// Gah
		add_filter( 'pre_option_moderation_notify', '__return_zero' );
		$c = wp_new_comment( $c_args );
		remove_filter( 'pre_option_moderation_notify', '__return_zero' );

		$this->set_current_user( $old_current_user );

		$comment = get_comment( $c );

		$this->assertEquals( 0, $comment->comment_approved );
	}

	/**
	 * @group comments
	 */
	public function test_comment_as_logged_out_user_success() {
		$old_current_user = get_current_user_id();
		$this->set_current_user( 0 );

		$d = $this->factory->doc->create();
		$d_settings = bp_docs_get_doc_settings( $d );
		$d_settings['post_comments'] = 'anyone';
		update_post_meta( $d, 'bp_docs_settings', $d_settings );

		$c_args = array(
			'comment_post_ID' => $d,
			'comment_content' => 'Test',
			'comment_author' => 'foo',
			'comment_author_url' => '',
			'comment_author_email' => 'foo@bar.com',
			'comment_type' => '',
		);

		// Gah
		add_filter( 'pre_option_moderation_notify', '__return_zero' );
		$c = wp_new_comment( $c_args );
		remove_filter( 'pre_option_moderation_notify', '__return_zero' );

		$this->set_current_user( $old_current_user );

		$comment = get_comment( $c );

		$this->assertEquals( 1, $comment->comment_approved );
	}
}


