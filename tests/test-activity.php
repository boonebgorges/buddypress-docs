<?php

/**
 * @group activity
 */
class BP_Docs_Tests_Activity extends BP_Docs_TestCase {
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
}
