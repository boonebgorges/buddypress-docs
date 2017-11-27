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
		$args = array(
			'title' => 'Test Doc',
			'content' => 'foo',
			'settings' => array( 'read' => 'anyone' ),
		);
		$q = new BP_Docs_Query();
		$result = $q->save( $args );

		$args = array(
			'title' => 'Test Doc',
			'content' => 'foo bar',
			'doc_id' => $result['doc_id'],
		);
		$q->save( $args );

		$found = bp_activity_get( array(
			'show_hidden'	=> 1,
			'filter'	=> array(
				'action' => 'bp_doc_edited',
				'secondary_id' => $result['doc_id'],
			),
		) );
		$this->assertNotEmpty( $found['activities'] );
	}

	public function test_edit_activity_should_be_created_for_changed_title() {
		$args = array(
			'title' => 'Test Doc',
			'content' => 'foo',
			'settings' => array( 'read' => 'anyone' ),
		);
		$q = new BP_Docs_Query();
		$result = $q->save( $args );

		$args = array(
			'title' => 'Test Doc Foo',
			'content' => 'foo',
			'doc_id' => $result['doc_id'],
		);
		$q->save( $args );

		$found = bp_activity_get( array(
			'show_hidden'	=> 1,
			'filter'	=> array(
				'action' => 'bp_doc_edited',
				'secondary_id' => $result['doc_id'],
			),
		) );

		$this->assertNotEmpty( $found['activities'] );
	}

	public function test_edit_activity_should_not_be_created_for_unchanged_revision() {
		$args = array(
			'title' => 'Test Doc',
			'content' => 'foo',
			'settings' => array( 'read' => 'anyone' ),
		);
		$q = new BP_Docs_Query();
		$result = $q->save( $args );

		$args = array(
			'title' => 'Test Doc',
			'content' => 'foo',
			'doc_id' => $result['doc_id'],
		);
		$q->save( $args );

		$found = bp_activity_get( array(
			'show_hidden'	=> 1,
			'filter'	=> array(
				'action' => 'bp_doc_edited',
				'secondary_id' =>$result['doc_id'],
			),
		) );

		$this->assertSame( array(), $found['activities'] );
	}

	public function filter_current_doc( $doc_id ) {
//		return $doc_id;
		return $this->current_doc;
	}
}
