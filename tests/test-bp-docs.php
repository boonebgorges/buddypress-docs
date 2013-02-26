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
}


