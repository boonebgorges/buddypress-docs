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
	 * @group bp_docs_get_folder_in_group_term_slug
	 */
	public function test_bp_docs_get_folder_in_group_term_slug() {
		$this->assertSame( 'bp_docs_folder_in_group_0', bp_docs_get_folder_in_group_term_slug( 'rchrchcrh' ) );
		$this->assertSame( 'bp_docs_folder_in_group_0', bp_docs_get_folder_in_group_term_slug( 0 ) );
		$this->assertSame( 'bp_docs_folder_in_group_9', bp_docs_get_folder_in_group_term_slug( 9 ) );
	}

	/**
	 * @user bp_docs_get_folder_in_user_term_slug
	 */
	public function test_bp_docs_get_folder_in_user_term_slug() {
		$this->assertSame( 'bp_docs_folder_in_user_0', bp_docs_get_folder_in_user_term_slug( 'rchrchcrh' ) );
		$this->assertSame( 'bp_docs_folder_in_user_0', bp_docs_get_folder_in_user_term_slug( 0 ) );
		$this->assertSame( 'bp_docs_folder_in_user_9', bp_docs_get_folder_in_user_term_slug( 9 ) );
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
	 * @group bp_docs_get_folder_in_item_term
	 */
	public function test_bp_docs_get_folder_in_item_term_group_does_not_exist() {
		$this->assertFalse( bp_docs_get_folder_in_item_term( 1234, 'group' ) );
	}

	/**
	 * @group bp_docs_get_folder_in_item_term
	 */
	public function test_bp_docs_get_folder_in_item_term_user_does_not_exist() {
		$this->assertFalse( bp_docs_get_folder_in_item_term( 1234, 'user' ) );
	}

	/**
	 * @group bp_docs_get_folder_in_item_term
	 */
	public function test_bp_docs_get_folder_in_item_term_group_existing_term() {
		$g = $this->factory->group->create();

		$term_id = $this->factory->term->create( array(
			'taxonomy' => 'bp_docs_folder_in_group',
			'slug' => 'bp_docs_folder_in_group_' . $g,
		) );

		$this->assertSame( $term_id, bp_docs_get_folder_in_item_term( $g, 'group' ) );
	}

	/**
	 * @group bp_docs_get_folder_in_item_term
	 */
	public function test_bp_docs_get_folder_in_item_term_user_existing_term() {
		$u = $this->factory->user->create();

		$term_id = $this->factory->term->create( array(
			'taxonomy' => 'bp_docs_folder_in_user',
			'slug' => 'bp_docs_folder_in_user_' . $u,
		) );

		$this->assertSame( $term_id, bp_docs_get_folder_in_item_term( $u, 'user' ) );
	}

	/**
	 * @group bp_docs_get_folder_in_item_term
	 */
	public function test_bp_docs_get_folder_in_item_term_group_term_created() {
		$g = $this->factory->group->create();

		$created_term_id = bp_docs_get_folder_in_item_term( $g, 'group' );

		$term = get_term_by( 'id', $created_term_id, 'bp_docs_folder_in_group' );

		$this->assertSame( $created_term_id, $term->term_id );
	}

	/**
	 * @group bp_docs_get_folder_in_item_term
	 */
	public function test_bp_docs_get_folder_in_item_term_user_term_created() {
		$u = $this->factory->user->create();

		$created_term_id = bp_docs_get_folder_in_item_term( $u, 'user' );

		$term = get_term_by( 'id', $created_term_id, 'bp_docs_folder_in_user' );

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
	public function test_bp_docs_add_doc_to_folder_no_append() {
		$doc_id = $this->factory->doc->create();

		$f1 = $this->factory->post->create( array(
			'post_type' => 'bp_docs_folder',
		) );
		$f2 = $this->factory->post->create( array(
			'post_type' => 'bp_docs_folder',
		) );

		bp_docs_add_doc_to_folder( $doc_id, $f1 );
		$this->assertTrue( bp_docs_add_doc_to_folder( $doc_id, $f2 ) );

		// double check
		$terms = wp_get_object_terms( $doc_id, 'bp_docs_doc_in_folder' );

		$this->assertSame( array( 'bp_docs_doc_in_folder_' . $f2 ), wp_list_pluck( $terms, 'slug' ) );
	}

	/**
	 * @group bp_docs_add_doc_to_folder
	 */
	public function test_bp_docs_add_doc_to_folder_append() {
		$doc_id = $this->factory->doc->create();

		$f1 = $this->factory->post->create( array(
			'post_type' => 'bp_docs_folder',
		) );
		$f2 = $this->factory->post->create( array(
			'post_type' => 'bp_docs_folder',
		) );

		bp_docs_add_doc_to_folder( $doc_id, $f1 );
		$this->assertTrue( bp_docs_add_doc_to_folder( $doc_id, $f2, true ) );

		// double check
		$terms = wp_get_object_terms( $doc_id, 'bp_docs_doc_in_folder' );

		$this->assertSame( array( 'bp_docs_doc_in_folder_' . $f1, 'bp_docs_doc_in_folder_' . $f2 ), wp_list_pluck( $terms, 'slug' ) );
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

	/**
	 * @group bp_docs_remove_doc_from_folder
	 */
	public function test_bp_docs_remove_doc_from_folder() {
		$doc_id = $this->factory->doc->create();

		$folder_id = $this->factory->post->create( array(
			'post_type' => 'bp_docs_folder',
		) );

		$this->assertTrue( bp_docs_add_doc_to_folder( $doc_id, $folder_id ) );
		$this->assertTrue( bp_docs_remove_doc_from_folder( $doc_id, $folder_id ) );
		$this->assertEquals( false, bp_docs_get_doc_folder( $doc_id ) );
	}

	/**
	 * @group bp_docs_create_folder
	 */
	public function test_bp_docs_create_folder_empty_name() {
		$this->assertFalse( bp_docs_create_folder( array() ) );
	}

	/**
	 * @group bp_docs_create_folder
	 */
	public function test_bp_docs_create_folder_nonexistent_parent() {
		$this->assertFalse( bp_docs_create_folder( array(
			'name' => 'Foo',
			'parent' => 1234,
		) ) );
	}

	/**
	 * @group bp_docs_create_folder
	 */
	public function test_bp_docs_create_folder_success_global() {
		$folder_id = bp_docs_create_folder( array(
			'name' => 'Test',
		) );

		$this->assertNotEmpty( $folder_id );

		// double check
		$folder = get_post( $folder_id );

		$this->assertSame( 'Test', $folder->post_title );
		$this->assertSame( 'bp_docs_folder', $folder->post_type );
	}

	/**
	 * @group bp_docs_create_folder
	 */
	public function test_bp_docs_create_folder_success_parent() {
		$f1 = bp_docs_create_folder( array(
			'name' => 'Test',
		) );

		$f2 = bp_docs_create_folder( array(
			'name' => 'Child',
			'parent' => $f1,
		) );

		$this->assertNotEmpty( $f2 );

		// double check
		$folder = get_post( $f2 );

		$this->assertSame( 'Child', $folder->post_title );
		$this->assertSame( 'bp_docs_folder', $folder->post_type );
		$this->assertSame( $f1, $folder->post_parent );
	}

	/**
	 * @group bp_docs_create_folder
	 */
	public function test_bp_docs_create_folder_group_id_group_does_not_exist() {
		$folder_id = bp_docs_create_folder( array(
			'name' => 'Test',
			'group_id' => 1234
		) );

		$this->assertFalse( $folder_id );
	}

	/**
	 * @group bp_docs_create_folder
	 */
	public function test_bp_docs_create_folder_success_group_id() {
		$g = $this->factory->group->create();

		$folder_id = bp_docs_create_folder( array(
			'name' => 'Test',
			'group_id' => $g,
		) );

		$this->assertNotEmpty( $folder_id );

		// double check
		$folder = get_post( $folder_id );

		$this->assertSame( 'Test', $folder->post_title );
		$this->assertSame( 'bp_docs_folder', $folder->post_type );

		$terms = wp_get_object_terms( $folder_id, 'bp_docs_folder_in_group' );
		$this->assertSame( array( 'bp_docs_folder_in_group_' . $g ), wp_list_pluck( $terms, 'slug' ) );
	}

	/**
	 * @group bp_docs_create_folder
	 */
	public function test_bp_docs_create_folder_success_user_id() {
		$u = $this->factory->user->create();

		$folder_id = bp_docs_create_folder( array(
			'name' => 'Test',
			'user_id' => $u,
		) );

		$this->assertNotEmpty( $folder_id );

		// double check
		$folder = get_post( $folder_id );

		$this->assertSame( 'Test', $folder->post_title );
		$this->assertSame( 'bp_docs_folder', $folder->post_type );

		$terms = wp_get_object_terms( $folder_id, 'bp_docs_folder_in_user' );
		$this->assertSame( array( 'bp_docs_folder_in_user_' . $u ), wp_list_pluck( $terms, 'slug' ) );
	}

	/**
	 * @group bp_docs_get_folders
	 */
	public function test_bp_docs_get_folders_should_hit_cache() {
		global $wpdb;

		$f1 = bp_docs_create_folder( array(
			'name' => 'Foo',
		) );

		$f2 = bp_docs_create_folder( array(
			'name' => 'Bar',
		) );

		$folders = bp_docs_get_folders();
		$this->assertSame( array( $f2, $f1 ), wp_list_pluck( $folders, 'ID' ) );

		$num_queries = $wpdb->num_queries;
		$folders = bp_docs_get_folders();
		$this->assertSame( array( $f2, $f1 ), wp_list_pluck( $folders, 'ID' ) );
		$this->assertSame( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @group bp_docs_get_folders
	 */
	public function test_bp_docs_get_folders_flat() {
		$f1 = bp_docs_create_folder( array(
			'name' => 'Foo',
		) );

		$f2 = bp_docs_create_folder( array(
			'name' => 'Bar',
		) );

		$folders = bp_docs_get_folders();
		$this->assertSame( array( $f2, $f1 ), wp_list_pluck( $folders, 'ID' ) );
	}

	/**
	 * @group bp_docs_get_folders
	 */
	public function test_bp_docs_get_folders_tree() {
		$f1 = bp_docs_create_folder( array(
			'name' => 'EE',
		) );

		$f2 = bp_docs_create_folder( array(
			'name' => 'DD',
			'parent' => $f1,
		) );

		$f3 = bp_docs_create_folder( array(
			'name' => 'CC',
		) );

		$f4 = bp_docs_create_folder( array(
			'name' => 'BB',
			'parent' => $f2,
		) );

		$f5 = bp_docs_create_folder( array(
			'name' => 'AA',
			'parent' => $f2,
		) );

		$folders = bp_docs_get_folders();

		$f1_obj = get_post( $f1 );
		$f2_obj = get_post( $f2 );
		$f3_obj = get_post( $f3 );
		$f4_obj = get_post( $f4 );
		$f5_obj = get_post( $f5 );

//		$f5_obj->children = array();
//		$f4_obj->children = array();
//		$f3_obj->children = array();
		$f2_obj->children = array(
			$f5 => $f5_obj,
			$f4 => $f4_obj,
		);
/*		$f1_obj->children = array(
			$f2 => $f2_obj,
		); */

		$expected = array(
			$f3_obj,
			$f1_obj,
		);

		$this->assertEquals( $expected, $folders );
	}

	/**
	 * @group bp_docs_get_folders
	 */
	public function test_bp_docs_get_folders_group() {
		$g = $this->factory->group->create();
		$f1 = bp_docs_create_folder( array(
			'name' => 'Test',
			'group_id' => $g,
		) );
		$f2 = bp_docs_create_folder( array(
			'name' => 'Test',
		) );

		$folders = bp_docs_get_folders( array(
			'group_id' => $g,
		) );

		$expected = array(
			$f1,
		);

		$this->assertSame( $expected, wp_list_pluck( $folders, 'ID' ) );
	}

	/**
	 * @group bp_docs_get_folders
	 */
	public function test_bp_docs_get_folders_user() {
		$u = $this->factory->user->create();
		$f1 = bp_docs_create_folder( array(
			'name' => 'Test',
			'user_id' => $u,
		) );
		$f2 = bp_docs_create_folder( array(
			'name' => 'Test',
		) );

		$folders = bp_docs_get_folders( array(
			'user_id' => $u,
		) );

		$expected = array(
			$f1,
		);

		$this->assertSame( $expected, wp_list_pluck( $folders, 'ID' ) );
	}

	/**
	 * @group bp_docs_get_folders
	 */
	public function test_bp_docs_get_folders_excluding_group_and_users() {
		$g = $this->factory->group->create();
		$u = $this->factory->user->create();
		$f1 = bp_docs_create_folder( array(
			'name' => 'Test',
			'group_id' => $g,
		) );
		$f2 = bp_docs_create_folder( array(
			'name' => 'Test',
		) );
		$f3 = bp_docs_create_folder( array(
			'name' => 'Test',
			'user_id' => $u,
		) );

		$folders = bp_docs_get_folders();

		$expected = array(
			$f2,
		);

		$this->assertSame( $expected, wp_list_pluck( $folders, 'ID' ) );
	}

	/**
	 * @group bp_docs_get_folders
	 */
	public function test_bp_docs_get_folders_force_all_folders() {
		$g = $this->factory->group->create();
		$u = $this->factory->user->create();
		$f1 = bp_docs_create_folder( array(
			'name' => 'Test1',
			'group_id' => $g,
		) );
		$f2 = bp_docs_create_folder( array(
			'name' => 'Test2',
		) );
		$f3 = bp_docs_create_folder( array(
			'name' => 'Test3',
			'user_id' => $u,
		) );

		$folders = bp_docs_get_folders( array(
			'force_all_folders' => true,
		) );

		$expected = array(
			$f1,
			$f2,
			$f3,
		);

		$this->assertSame( $expected, wp_list_pluck( $folders, 'ID' ) );
	}

	/**
	 * @group bp_docs_get_folder_group
	 */
	public function test_bp_docs_get_folder_group() {
		$g = $this->factory->group->create();
		$f = bp_docs_create_folder( array(
			'name' => 'foo',
			'group_id' => $g,
		) );

		$this->assertSame( $g, bp_docs_get_folder_group( $f ) );
	}

	/**
	 * @group bp_docs_get_folder_group
	 */
	public function test_bp_docs_get_folder_group_should_hit_primed_cache() {
		global $wpdb;

		$g = $this->factory->group->create();
		$f = bp_docs_create_folder( array(
			'name' => 'foo',
			'group_id' => $g,
		) );

		$this->assertSame( $g, bp_docs_get_folder_group( $f ) );

		$num_queries = $wpdb->num_queries;
		$this->assertSame( $g, bp_docs_get_folder_group( $f ) );
		$this->assertSame( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @group bp_docs_get_folder_user
	 */
	public function test_bp_docs_get_folder_user() {
		$u = $this->factory->user->create();
		$f = bp_docs_create_folder( array(
			'name' => 'foo',
			'user_id' => $u,
		) );

		$this->assertSame( $u, bp_docs_get_folder_user( $f ) );
	}

	/**
	 * @group bp_docs_get_folder_user
	 */
	public function test_bp_docs_get_folder_user_should_hit_primed_cache() {
		global $wpdb;

		$u = $this->factory->user->create();
		$f = bp_docs_create_folder( array(
			'name' => 'foo',
			'user_id' => $u,
		) );

		$this->assertSame( $u, bp_docs_get_folder_user( $f ) );

		$num_queries = $wpdb->num_queries;
		$this->assertSame( $u, bp_docs_get_folder_user( $f ) );
		$this->assertSame( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @group bp_docs_get_doc_folder
	 */
	public function test_bp_docs_get_doc_folder() {
		$d = $this->factory->doc->create();
		$f = bp_docs_create_folder( array(
			'name' => 'foo',
		) );
		bp_docs_add_doc_to_folder( $d, $f );

		$this->assertSame( $f, bp_docs_get_doc_folder( $d ) );
	}

	/**
	 * @group bp_docs_delete_folder_contents
	 */
	public function test_bp_docs_delete_folder_contents() {
		$f1 = bp_docs_create_folder( array(
			'name' => 'foo',
		) );
		$f1_d1 = $this->factory->doc->create();
		$f1_d2 = $this->factory->doc->create();
		bp_docs_add_doc_to_folder( $f1_d1, $f1 );
		bp_docs_add_doc_to_folder( $f1_d2, $f1 );

		$f2 = bp_docs_create_folder( array(
			'name' => 'foo',
			'parent' => $f1,
		) );
		$f2_d1 = $this->factory->doc->create();
		$f2_d2 = $this->factory->doc->create();
		bp_docs_add_doc_to_folder( $f2_d1, $f2 );
		bp_docs_add_doc_to_folder( $f2_d2, $f2 );

		$f3 = bp_docs_create_folder( array(
			'name' => 'foo',
			'parent' => $f1,
		) );
		$f3_d1 = $this->factory->doc->create();
		$f3_d2 = $this->factory->doc->create();
		bp_docs_add_doc_to_folder( $f3_d1, $f3 );
		bp_docs_add_doc_to_folder( $f3_d2, $f3 );

		$f4 = bp_docs_create_folder( array(
			'name' => 'foo',
			'parent' => $f2,
		) );
		$f4_d1 = $this->factory->doc->create();
		$f4_d2 = $this->factory->doc->create();
		bp_docs_add_doc_to_folder( $f4_d1, $f4 );
		bp_docs_add_doc_to_folder( $f4_d2, $f4 );

		$this->assertTrue( bp_docs_delete_folder_contents( $f1 ) );

		$f1_term = bp_docs_get_folder_term( $f1 );
		$f1_docs = get_posts( array(
			'post_type' => bp_docs_get_post_type_name(),
			'tax_query' => array(
				array(
					'taxonomy' => 'bp_docs_doc_in_folder',
					'field' => 'term_id',
					'terms' => $f1_term,
				),
			),
			'update_meta_cache' => false,
			'update_term_cache' => false,
		) );
		$this->assertSame( array(), $f1_docs );

		$f2_term = bp_docs_get_folder_term( $f2 );
		$f2_docs = get_posts( array(
			'post_type' => bp_docs_get_post_type_name(),
			'tax_query' => array(
				array(
					'taxonomy' => 'bp_docs_doc_in_folder',
					'field' => 'term_id',
					'terms' => $f2_term,
				),
			),
			'update_meta_cache' => false,
			'update_term_cache' => false,
		) );
		$this->assertSame( array(), $f2_docs );

		$f3_term = bp_docs_get_folder_term( $f3 );
		$f3_docs = get_posts( array(
			'post_type' => bp_docs_get_post_type_name(),
			'tax_query' => array(
				array(
					'taxonomy' => 'bp_docs_doc_in_folder',
					'field' => 'term_id',
					'terms' => $f3_term,
				),
			),
			'update_meta_cache' => false,
			'update_term_cache' => false,
		) );
		$this->assertSame( array(), $f3_docs );

		$f4_term = bp_docs_get_folder_term( $f4 );
		$f4_docs = get_posts( array(
			'post_type' => bp_docs_get_post_type_name(),
			'tax_query' => array(
				array(
					'taxonomy' => 'bp_docs_doc_in_folder',
					'field' => 'term_id',
					'terms' => $f4_term,
				),
			),
			'update_meta_cache' => false,
			'update_term_cache' => false,
		) );
		$this->assertSame( array(), $f4_docs );

		$this->assertSame( array(), bp_docs_get_folders( array( 'parent_id' => $f1 ) ) );
		$this->assertSame( array(), bp_docs_get_folders( array( 'parent_id' => $f2 ) ) );
	}
}
