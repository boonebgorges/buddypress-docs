<?php

/**
 * @group folders
 */
class BP_Docs_Folders_Tests extends BP_Docs_TestCase {
	/**
	 * @group bp_docs_get_folder_term_slug
	 */
	public function test_bp_docs_get_folder_term_slug() {
		$this->assertSame( 'bp_docs_doc_in_folder_0', bp_docs_get_folder_term_slug( 'rchrchcrh' ) );
		$this->assertSame( 'bp_docs_doc_in_folder_0', bp_docs_get_folder_term_slug( 0 ) );
		$this->assertSame( 'bp_docs_doc_in_folder_9', bp_docs_get_folder_term_slug( 9 ) );
	}

	/**
	 * @group bp_docs_get_folder_term
	 */
	public function test_bp_docs_get_folder_term_nonexistent_folder_id() {
		$this->assertFalse( bp_docs_get_folder_term( 12345 ) );
	}

	/**
	 * @group bp_docs_get_folder_term
	 */
	public function test_bp_docs_get_folder_term_not_a_folder_post_type() {
		$folder_id = $this->factory->post->create( array(
			'post_type' => 'post',
		) );

		$this->assertFalse( bp_docs_get_folder_term( $folder_id ) );
	}

	/**
	 * @group bp_docs_get_folder_term
	 */
	public function test_bp_docs_get_folder_term_existing_term() {
		$folder_id = $this->factory->post->create( array(
			'post_type' => 'bp_docs_folder',
		) );

		$term_id = $this->factory->term->create( array(
			'taxonomy' => 'bp_docs_doc_in_folder',
			'slug' => 'bp_docs_doc_in_folder_' . $folder_id,
		) );

		$this->assertSame( $term_id, bp_docs_get_folder_term( $folder_id ) );
	}

	/**
	 * @group bp_docs_get_folder_term
	 */
	public function test_bp_docs_get_folder_term_term_created() {
		$folder_id = $this->factory->post->create( array(
			'post_type' => 'bp_docs_folder',
		) );

		$created_term_id = bp_docs_get_folder_term( $folder_id );

		$term = get_term_by( 'id', $created_term_id, 'bp_docs_doc_in_folder' );

		$this->assertSame( $created_term_id, $term->term_id );
	}

	/**
	 * @group bp_docs_add_doc_to_folder
	 */
	public function test_bp_docs_add_doc_to_folder_nonexistent_doc_id() {
		$folder_id = $this->factory->post->create( array(
			'post_type' => 'bp_docs_folder',
		) );

		$this->assertFalse( bp_docs_add_doc_to_folder( 4567, $folder_id ) );
	}

	/**
	 * @group bp_docs_add_doc_to_folder
	 */
	public function test_bp_docs_add_doc_to_folder_not_a_doc_post_type() {
		$doc_id = $this->factory->post->create( array(
			'post_type' => 'post',
		) );

		$folder_id = $this->factory->post->create( array(
			'post_type' => 'bp_docs_folder',
		) );

		$this->assertFalse( bp_docs_add_doc_to_folder( $doc_id, $folder_id ) );
	}

	/**
	 * @group bp_docs_add_doc_to_folder
	 */
	public function test_bp_docs_add_doc_to_folder_nonexistent_folder_id() {
		$doc_id = $this->factory->doc->create();

		$this->assertFalse( bp_docs_add_doc_to_folder( $doc_id, 4557 ) );
	}

	/**
	 * @group bp_docs_add_doc_to_folder
	 */
	public function test_bp_docs_add_doc_to_folder_not_a_folder_post_type() {
		$doc_id = $this->factory->doc->create();

		$folder_id = $this->factory->post->create( array(
			'post_type' => 'post',
		) );

		$this->assertFalse( bp_docs_add_doc_to_folder( $doc_id, $folder_id ) );
	}

	/**
	 * @group bp_docs_add_doc_to_folder
	 */
	public function test_bp_docs_add_doc_to_folder_success() {
		$doc_id = $this->factory->doc->create();

		$folder_id = $this->factory->post->create( array(
			'post_type' => 'bp_docs_folder',
		) );

		$this->assertTrue( bp_docs_add_doc_to_folder( $doc_id, $folder_id ) );

		// double check
		$terms = wp_get_object_terms( $doc_id, 'bp_docs_doc_in_folder' );

		$this->assertSame( array( 'bp_docs_doc_in_folder_' . $folder_id ), wp_list_pluck( $terms, 'slug' ) );
	}

	/**
	 * @group bp_docs_add_doc_to_folder
	 */
	public function test_bp_docs_add_doc_to_folder_failure_when_already_in_folder() {
		$doc_id = $this->factory->doc->create();

		$folder_id = $this->factory->post->create( array(
			'post_type' => 'bp_docs_folder',
		) );

		$this->assertTrue( bp_docs_add_doc_to_folder( $doc_id, $folder_id ) );
		$this->assertFalse( bp_docs_add_doc_to_folder( $doc_id, $folder_id ) );
	}
}
