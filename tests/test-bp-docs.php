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

	function test_bp_docs_is_existing_doc() {
		$doc_id = $this->factory->doc->create();
		$this->go_to( get_permalink( $doc_id ) );
		$this->assertTrue( bp_docs_is_existing_doc() );

		$post_id = $this->factory->post->create();
		$this->go_to( get_permalink( $post_id ) );
		$this->assertFalse( bp_docs_is_existing_doc() );

		// Fake that we're pre-query
		global $wp_query;
		$wpq = $wp_query;
		$wp_query = null;
		$this->assertFalse( bp_docs_is_existing_doc() );
		$wp_query = $wpq;
	}
}


