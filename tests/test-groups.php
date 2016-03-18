<?php

/**
 * @group groups
 */
class BP_Docs_Tests_Groups extends BP_Docs_TestCase {
	public function test_bp_docs_get_associated_group_id_single() {
		$g = $this->factory->group->create();
		$d = $this->factory->doc->create( array( 'group' => $g ) );

		$this->assertEquals( $g, bp_docs_get_associated_group_id( $d ) );
	}

	public function test_bp_docs_get_associated_group_id_should_hit_term_cache() {
		global $wpdb;

		$g = $this->factory->group->create();
		$d = $this->factory->doc->create( array( 'group' => $g ) );

		$this->assertEquals( $g, bp_docs_get_associated_group_id( $d ) );

		$num_queries = $wpdb->num_queries;
		$this->assertEquals( $g, bp_docs_get_associated_group_id( $d ) );
		$this->assertSame( $num_queries, $wpdb->num_queries );
	}
}
