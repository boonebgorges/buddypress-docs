<?php

/**
 * @group activity
 */
class BP_Docs_Tests_Activity extends BP_Docs_TestCase {
	protected $current_doc;

	/**
	 * @ticket 536
	 */
	public function test_edited_action_should_bail_when_doc_id_is_0() {
		global $post;

		$old_post = $post;
		$post = $this->factory->post->create();

		$activity = new stdClass();
		$activity->secondary_item_id = 0;

		$found = bp_docs_format_activity_action_bp_doc_edited( 'foo', $activity );

		$post = $old_post;

		$this->assertSame( 'foo', $found );
	}

	/**
	 * @ticket 536
	 */
	public function test_created_action_should_bail_when_doc_id_is_0() {
		global $post;

		$old_post = $post;
		$post = $this->factory->post->create();

		$activity = new stdClass();
		$activity->secondary_item_id = 0;

		$found = bp_docs_format_activity_action_bp_doc_created( 'foo', $activity );

		$post = $old_post;

		$this->assertSame( 'foo', $found );
	}

	/**
	 * @ticket 536
	 */
	public function test_comment_action_should_bail_when_doc_id_is_0() {
		global $post;

		$old_post = $post;
		$post = $this->factory->post->create();

		$comment_id = $this->factory->comment->create( array(
			'comment_post_ID' => 0,
		) );

		$activity = new stdClass();
		$activity->secondary_item_id = $comment_id;

		$found = bp_docs_format_activity_action_bp_doc_comment( 'foo', $activity );

		$post = $old_post;

		$this->assertSame( 'foo', $found );
	}

	public function test_edit_activity_should_be_created_for_changed_content() {
		// We have to do unholy things to make this testable.
		$old_post = $_POST;

		$doc = $this->factory->doc->create( array(
			'post_content' => 'foo',
			'post_title' => 'Test Doc',
			'post_name' => 'test-doc',
		) );

		$this->current_doc = get_post( $doc );
		add_filter( 'bp_docs_get_current_doc', array( $this, 'filter_current_doc' ) );

		$_POST = array(
			'doc' => array( 'title' => 'Test Doc' ),
			'doc_content' => 'foo bar',
			'ID' => $doc,
		);

		$q = new BP_Docs_Query();
		$q->doc_slug = 'test-doc';
		$q->save();

		remove_filter( 'bp_docs_get_current_doc', array( $this, 'filter_current_doc' ) );
		$_POST = $old_post;

		$found = bp_activity_get( array(
			'show_hidden'	=> 1,
			'filter'	=> array(
				'action' => 'bp_doc_edited',
				'secondary_id' => $doc,
			),
		) );

		$this->assertNotEmpty( $found['activities'] );
	}

	public function test_edit_activity_should_be_created_for_changed_title() {
		// We have to do unholy things to make this testable.
		$old_post = $_POST;

		$doc = $this->factory->doc->create( array(
			'post_content' => 'foo',
			'post_title' => 'Test Doc',
			'post_name' => 'test-doc',
		) );

		$this->current_doc = get_post( $doc );
		add_filter( 'bp_docs_get_current_doc', array( $this, 'filter_current_doc' ) );

		$_POST = array(
			'doc' => array( 'title' => 'Test Doc Foo' ),
			'doc_content' => 'foo',
			'ID' => $doc,
		);

		$q = new BP_Docs_Query();
		$q->doc_slug = 'test-doc';
		$q->save();

		remove_filter( 'bp_docs_get_current_doc', array( $this, 'filter_current_doc' ) );
		$_POST = $old_post;

		$found = bp_activity_get( array(
			'show_hidden'	=> 1,
			'filter'	=> array(
				'action' => 'bp_doc_edited',
				'secondary_id' => $doc,
			),
		) );

		$this->assertNotEmpty( $found['activities'] );
	}

	public function test_edit_activity_should_not_be_created_for_unchanged_revision() {
		// We have to do unholy things to make this testable.
		$old_post = $_POST;

		$doc = $this->factory->doc->create( array(
			'post_content' => 'foo',
			'post_title' => 'Test Doc',
			'post_name' => 'test-doc',
		) );

		$this->current_doc = get_post( $doc );
		add_filter( 'bp_docs_get_current_doc', array( $this, 'filter_current_doc' ) );

		$_POST = array(
			'doc' => array( 'title' => 'Test Doc' ),
			'doc_content' => 'foo',
			'ID' => $doc,
		);

		$q = new BP_Docs_Query();
		$q->doc_slug = 'test-doc';
		$q->save();

		remove_filter( 'bp_docs_get_current_doc', array( $this, 'filter_current_doc' ) );
		$_POST = $old_post;

		$found = bp_activity_get( array(
			'show_hidden'	=> 1,
			'filter'	=> array(
				'action' => 'bp_doc_edited',
				'secondary_id' => $doc,
			),
		) );

		$this->assertSame( array(), $found['activities'] );
	}

	public function filter_current_doc( $doc_id ) {
//		return $doc_id;
		return $this->current_doc;
	}
}
