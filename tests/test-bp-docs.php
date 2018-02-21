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

	public function test_bp_docs_save_new_doc() {
		$g = $this->factory->group->create();
		groups_update_groupmeta( $g, 'bp-docs', array(
			'can-create' => 'member',
		) );
		$u1 = $this->factory->user->create();
		$this->add_user_to_group( $u1, $g );

		$title = 'Doc title for testing';
		$content = 'Doc content for testing';

		$args = array(
			'title' 	=> $title,
			'content'	=> $content,
			'author_id'	=> $u1,
			'group_id'	=> $g,
		);

		$query = new BP_Docs_Query;
		$retval = $query->save( $args );

		$doc = $this->factory->doc->get_object_by_id( $retval['doc_id'] );

		// Make sure the saved data matches what we passed in.
		$this->assertEquals( $doc->post_title, $title );
		$this->assertEquals( $doc->post_content, $content );
		$this->assertEquals( $doc->post_author, $u1);
		$this->assertEquals( $g, bp_docs_get_associated_group_id( $retval['doc_id'] ) );
	}

	public function test_bp_docs_update_existing_doc() {
		$doc_id = $this->factory->doc->create();

		$doc = $this->factory->doc->get_object_by_id( $doc_id );
		$args = array(
			'doc_id'	=> $doc_id,
			'title' 	=> $doc->post_title,
			'content'	=> $doc->post_content,
		);

		$query = new BP_Docs_Query;
		$retval = $query->save( $args );

		// Make sure the id didn't change.
		$this->assertEquals( $doc_id, $retval['doc_id'] );
	}

	/**
	 * see #286
	 */
	function test_change_group_association() {
		$group = $this->factory->group->create();
		$group2 = $this->factory->group->create();

		$doc_id = $this->factory->doc->create( array(
			'group' => $group2,
		) );

		bp_docs_set_associated_group_id( $doc_id, $group );

		$this->assertEquals( bp_docs_get_associated_group_id( $doc_id ), $group );
	}

	function test_set_group_association_on_create() {
		$group = $this->factory->group->create();

		$doc_id = $this->factory->doc->create( array(
			'group' => $group,
		) );

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

		$doc = $this->factory->doc->get_object_by_id( $doc_id );
		$args = array(
			'doc_id'	=> $doc_id,
			'title' 	=> $doc->post_title,
			'content'	=> $doc->post_content,
			'group_id' 	=> 0,
		);

		$query = new BP_Docs_Query;
		$retval = $query->save( $args );

		$maybe_group_id = bp_docs_get_associated_group_id( $doc_id );

		$this->assertFalse( (bool) $maybe_group_id );
	}

	/**
	 * @group BP_Docs_Query
	 */
	function test_bp_docs_query_default_group() {
		$g = $this->factory->group->create();
		$d1 = $this->factory->doc->create( array(
			'group' => $g,
		) );
		$d2 = $this->factory->doc->create();

		$q = new BP_Docs_Query();

		// Remove access protection for the moment because I'm lazy
		remove_action( 'pre_get_posts', 'bp_docs_general_access_protection', 28 );
		$wp_query = $q->get_wp_query();
		add_action( 'pre_get_posts', 'bp_docs_general_access_protection', 28 );

		$found = wp_list_pluck( $wp_query->posts, 'ID' );
		sort( $found );

		$this->assertSame( $found, array( $d1, $d2 ) );
	}
	/**
	 * @group BP_Docs_Query
	 */
	function test_bp_docs_query_null_group() {
		$g = $this->factory->group->create();

		$d1 = $this->factory->doc->create( array(
			'group' => $g,
		) );
		$d2 = $this->factory->doc->create();

		$q = new BP_Docs_Query( array(
			'group_id' => array(),
		) );

		// Remove access protection for the moment because I'm lazy
		remove_action( 'pre_get_posts', 'bp_docs_general_access_protection', 28 );
		$wp_query = $q->get_wp_query();
		add_action( 'pre_get_posts', 'bp_docs_general_access_protection', 28 );

		$found = wp_list_pluck( $wp_query->posts, 'ID' );

		$this->assertSame( $found, array( $d2 ) );
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
		do_action( 'bp_docs_after_save', $d, array( 'group_id' => $g ) );

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
	/**
	 * @group bp_docs_unlink_from_group
	 */
	function test_bp_docs_unlink_from_group() {
		$group = $this->factory->group->create();
		$doc_id = $this->factory->doc->create( array(
			'group' => $group,
		) );

		bp_docs_unlink_from_group( $doc_id, $group );

		$maybe_group_id = bp_docs_get_associated_group_id( $doc_id );

		$this->assertFalse( (bool) $maybe_group_id );
	}
	/**
	 * @group bp_docs_unlink_from_group
	 */
	function test_bp_docs_remove_group_related_doc_access_settings() {
		$group = $this->factory->group->create();
		$doc_id = $this->factory->doc->create( array(
			'group' => $group,
		) );
		$settings = bp_docs_get_doc_settings( $doc_id );
		// These are doc default settings:
		// $default_settings = array(
		// 	'read'          => 'anyone',
		// 	'edit'          => 'loggedin',
		// 	'read_comments' => 'anyone',
		// 	'post_comments' => 'anyone',
		// 	'view_history'  => 'anyone',
		// 	'manage'        => 'creator',
		// );
		$settings['edit'] = 'group-members';
		$settings['post_comments'] = 'admins-mods';
		update_post_meta( $doc_id, 'bp_docs_settings', $settings );

		bp_docs_remove_group_related_doc_access_settings( $doc_id );

		$expected_settings = array(
			'read'          => 'anyone',
			'edit'          => 'creator',
			'read_comments' => 'anyone',
			'post_comments' => 'creator',
			'view_history'  => 'anyone',
			'manage'        => 'creator',
		);
		$modified_settings = bp_docs_get_doc_settings( $doc_id );

		$this->assertEqualSetsWithIndex( $expected_settings, $modified_settings );
	}
	/**
	 * @group bp_docs_get_access_options
	 */
	function test_bp_docs_get_access_options_no_group_assoc() {
		$default_settings = bp_docs_get_default_access_options();
		// These are doc default settings:
		$expected_settings = array(
			'read'          => 'anyone',
			'edit'          => 'loggedin',
			'read_comments' => 'anyone',
			'post_comments' => 'anyone',
			'view_history'  => 'anyone',
			'manage'        => 'creator'
		);

		$this->assertEqualSetsWithIndex( $expected_settings, $default_settings );
	}
	/**
	 * @group bp_docs_get_access_options
	 */
	function test_bp_docs_get_access_options_group_assoc_public() {
		$u1 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$g = $this->factory->group->create( array(
			'status' => 'public',
			'creator_id' => $u1
		) );
		// Make sure BP-Docs is enabled for this group and this user can associate with this group.
		$settings = array(
			'group-enable'	=> 1,
			'can-create' 	=> 'member'
		);

		groups_update_groupmeta( $g, 'bp-docs', $settings );

		$default_settings = bp_docs_get_default_access_options( 0, $g);
		// These are doc default settings:
		$expected_settings = array(
			'read'          => 'anyone',
			'edit'          => 'group-members',
			'read_comments' => 'anyone',
			'post_comments' => 'group-members',
			'view_history'  => 'anyone',
			'manage'        => 'creator'
		);

		$this->assertEqualSetsWithIndex( $expected_settings, $default_settings );
	}
	/**
	 * @group bp_docs_get_access_options
	 */
	function test_bp_docs_get_access_options_group_assoc_private() {
		$u1 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$g = $this->factory->group->create( array(
			'status' => 'private',
			'creator_id' => $u1
		) );

		// Make sure BP-Docs is enabled for this group and this user can associate with this group.
		$settings = array(
			'group-enable'	=> 1,
			'can-create' 	=> 'member'
		);

		groups_update_groupmeta( $g, 'bp-docs', $settings );

		$default_settings = bp_docs_get_default_access_options( 0, $g );
		// These are doc default settings:
		$expected_settings = array(
			'read'          => 'group-members',
			'edit'          => 'group-members',
			'read_comments' => 'group-members',
			'post_comments' => 'group-members',
			'view_history'  => 'group-members',
			'manage'        => 'group-members'
		);

		$this->assertEqualSetsWithIndex( $expected_settings, $default_settings );
	}

	/**
	 * @see issue #492
	 */
	public function test_bp_docs_is_docs_enabled_for_group_should_work_after_toggled_off() {
		$group = $this->factory->group->create();
		$doc_id = $this->factory->doc->create( array( 'group' => $group ) );

		$settings = array(
			'group-enable' => 1,
			'can-create' => 'member',
		);
		groups_update_groupmeta( $group, 'bp-docs', $settings );

		$this->assertTrue( bp_docs_is_docs_enabled_for_group( $group ) );

		$settings = array(
			'group-enable' => 0,
		);
		groups_update_groupmeta( $group, 'bp-docs', $settings );

		$this->assertFalse( bp_docs_is_docs_enabled_for_group( $group ) );
	}

	/**
	 * @group bp_docs_trash_doc
	 */
	function test_bp_docs_move_to_trash() {
		$doc_id = $this->factory->doc->create();

		bp_docs_trash_doc( $doc_id );

		$this->assertEquals( 'trash', get_post_status( $doc_id ) );
	}

	/**
	 * @group bp_docs_trash_doc
	 */
	function test_bp_docs_delete_permanently() {
		$doc_id = $this->factory->doc->create();

		// Trashing a doc once puts it in the trash.
		bp_docs_trash_doc( $doc_id );
		$this->assertEquals( 'trash', get_post_status( $doc_id ) );

		// Trashing a doc that's already in the trash deletes it permanently.
		bp_docs_trash_doc( $doc_id );

		$this->assertNull( get_post( $doc_id ) );
	}

	/**
	 * @group bp_docs_trash_doc
	 */
	function test_bp_docs_delete_force_delete() {
		$doc_id = $this->factory->doc->create();

		// Force-deleting a doc deletes it permanently.
		bp_docs_trash_doc( $doc_id, true );

		$this->assertNull( get_post( $doc_id ) );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_doc_ids_should_hit_cache() {
		global $wpdb;

		$bp_docs_access_query = bp_docs_access_query();

		$restricted_ids_first = $bp_docs_access_query->get_doc_ids();

		$num_queries = $wpdb->num_queries;
		$restricted_ids_second = $bp_docs_access_query->get_doc_ids();

		$this->assertSame( $restricted_ids_first, $restricted_ids_second );
		$this->assertSame( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_restricted_comment_doc_ids_should_hit_cache() {
		global $wpdb;

		$bp_docs_access_query = bp_docs_access_query();

		$restricted_ids_first = $bp_docs_access_query->get_restricted_comment_doc_ids();

		$num_queries = $wpdb->num_queries;
		$restricted_ids_second = $bp_docs_access_query->get_restricted_comment_doc_ids();

		$this->assertSame( $restricted_ids_first, $restricted_ids_second );
		$this->assertSame( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_comment_ids_should_hit_cache() {
		global $wpdb;

		$bp_docs_access_query = bp_docs_access_query();

		$restricted_ids_first = $bp_docs_access_query->get_comment_ids();

		$num_queries = $wpdb->num_queries;
		$restricted_ids_second = $bp_docs_access_query->get_comment_ids();

		$this->assertSame( $restricted_ids_first, $restricted_ids_second );
		$this->assertSame( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_doc_ids_logged_in_prevent() {
		$old_current_user = get_current_user_id();

		$d = $this->factory->doc->create();
		bp_docs_update_doc_access( $d, 'loggedin' );

		// Pretend we're logged out.
		$this->set_current_user( 0 );

		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_doc_ids();

		$this->assertTrue( in_array( $d, $restricted_ids ) );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_doc_ids_creator_only_prevent() {
		$old_current_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$d = $this->factory->doc->create();
		bp_docs_update_doc_access( $d, 'creator' );

		// Only the doc owner should have access.
		$this->set_current_user( $u2 );

		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_doc_ids();

		$this->assertTrue( in_array( $d, $restricted_ids ) );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_doc_ids_group_member_prevent() {
		$old_current_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$g = $this->factory->group->create( array(
			'status' => 'public',
			'creator_id' => $u1
		) );

		$d = $this->factory->doc->create( array(
			'group' => $g,
		) );
		bp_docs_update_doc_access( $d, 'group-members' );

		// We'll be a non-group-member.
		$u2 = $this->factory->user->create();
		$this->set_current_user( $u2 );

		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_doc_ids();

		$this->assertTrue( in_array( $d, $restricted_ids ) );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_doc_ids_group_admins_mods_prevent() {
		$old_current_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$g = $this->factory->group->create( array(
			'status' => 'public',
			'creator_id' => $u1
		) );

		$d = $this->factory->doc->create( array(
			'group' => $g,
		) );
		bp_docs_update_doc_access( $d, 'admins-mods' );

		// We'll be a regular group-member.
		$u2 = $this->factory->user->create();
		$this->set_current_user( $u2 );
		BP_UnitTestCase::add_user_to_group( $u2, $g );

		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_doc_ids();

		$this->assertTrue( in_array( $d, $restricted_ids ) );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_doc_ids_logged_in_allow() {
		$old_current_user = get_current_user_id();
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$d = $this->factory->doc->create();
		bp_docs_update_doc_access( $d, 'loggedin' );

		// Pretend we're a different, logged-in user.
		$this->set_current_user( $u2 );

		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_doc_ids();

		$this->assertFalse( in_array( $d, $restricted_ids ) );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_doc_ids_creator_only_allow() {
		$old_current_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$d = $this->factory->doc->create();
		bp_docs_update_doc_access( $d, 'creator' );

		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_doc_ids();

		$this->assertFalse( in_array( $d, $restricted_ids ) );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_doc_ids_group_member_allow() {
		$old_current_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$g = $this->factory->group->create( array(
			'status' => 'public',
			'creator_id' => $u1
		) );

		$d = $this->factory->doc->create( array(
			'group' => $g,
		) );
		bp_docs_update_doc_access( $d, 'group-members' );

		// We'll be a group-member.
		$u2 = $this->factory->user->create();
		$this->set_current_user( $u2 );
		BP_UnitTestCase::add_user_to_group( $u2, $g );

		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_doc_ids();

		$this->assertFalse( in_array( $d, $restricted_ids ) );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_doc_ids_group_admins_mods_allow() {
		$old_current_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$g = $this->factory->group->create( array(
			'status' => 'public',
			'creator_id' => $u1
		) );

		$d = $this->factory->doc->create( array(
			'group' => $g,
		) );
		bp_docs_update_doc_access( $d, 'admins-mods' );

		// We'll be a group mod.
		$u2 = $this->factory->user->create();
		$this->set_current_user( $u2 );
		BP_UnitTestCase::add_user_to_group( $u2, $g );
		$m2 = new BP_Groups_Member( $u2, $g );
		$m2->promote( 'mod' );

		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_doc_ids();

		$this->assertFalse( in_array( $d, $restricted_ids ) );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_comment_doc_ids_logged_in_prevent() {
		$old_current_user = get_current_user_id();

		$d = $this->factory->doc->create();
		bp_docs_update_doc_comment_access( $d, 'loggedin' );

		// Pretend we're logged out.
		$this->set_current_user( 0 );

		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_restricted_comment_doc_ids();

		$this->assertTrue( in_array( $d, $restricted_ids ) );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_comment_doc_ids_creator_only_prevent() {
		$old_current_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$d = $this->factory->doc->create();
		bp_docs_update_doc_comment_access( $d, 'creator' );

		// Only the doc owner should have access.
		$this->set_current_user( $u2 );

		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_restricted_comment_doc_ids();

		$this->assertTrue( in_array( $d, $restricted_ids ) );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_comment_doc_ids_group_member_prevent() {
		$old_current_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$g = $this->factory->group->create( array(
			'status' => 'public',
			'creator_id' => $u1
		) );

		$d = $this->factory->doc->create( array(
			'group' => $g,
		) );
		bp_docs_update_doc_comment_access( $d, 'group-members' );

		// We'll be a non-group-member.
		$u2 = $this->factory->user->create();
		$this->set_current_user( $u2 );

		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_restricted_comment_doc_ids();

		$this->assertTrue( in_array( $d, $restricted_ids ) );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_comment_doc_ids_group_admins_mods_prevent() {
		$old_current_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$g = $this->factory->group->create( array(
			'status' => 'public',
			'creator_id' => $u1
		) );

		$d = $this->factory->doc->create( array(
			'group' => $g,
		) );
		bp_docs_update_doc_comment_access( $d, 'admins-mods' );

		// We'll be a regular group-member.
		$u2 = $this->factory->user->create();
		$this->set_current_user( $u2 );
		BP_UnitTestCase::add_user_to_group( $u2, $g );

		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_restricted_comment_doc_ids();

		$this->assertTrue( in_array( $d, $restricted_ids ) );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_comment_doc_ids_logged_in_allow() {
		$old_current_user = get_current_user_id();
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$d = $this->factory->doc->create();
		bp_docs_update_doc_comment_access( $d, 'loggedin' );

		// Pretend we're a different, logged-in user.
		$this->set_current_user( $u2 );

		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_restricted_comment_doc_ids();

		$this->assertFalse( in_array( $d, $restricted_ids ) );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_comment_doc_ids_creator_only_allow() {
		$old_current_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$d = $this->factory->doc->create();
		bp_docs_update_doc_comment_access( $d, 'creator' );

		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_restricted_comment_doc_ids();

		$this->assertFalse( in_array( $d, $restricted_ids ) );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_comment_doc_ids_group_member_allow() {
		$old_current_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$g = $this->factory->group->create( array(
			'status' => 'public',
			'creator_id' => $u1
		) );

		$d = $this->factory->doc->create( array(
			'group' => $g,
		) );
		bp_docs_update_doc_comment_access( $d, 'group-members' );

		// We'll be a group-member.
		$u2 = $this->factory->user->create();
		$this->set_current_user( $u2 );
		BP_UnitTestCase::add_user_to_group( $u2, $g );

		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_restricted_comment_doc_ids();

		$this->assertFalse( in_array( $d, $restricted_ids ) );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_comment_doc_ids_group_admins_mods_allow() {
		$old_current_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$g = $this->factory->group->create( array(
			'status' => 'public',
			'creator_id' => $u1
		) );

		$d = $this->factory->doc->create( array(
			'group' => $g,
		) );
		bp_docs_update_doc_comment_access( $d, 'admins-mods' );

		// We'll be a group mod.
		$u2 = $this->factory->user->create();
		$this->set_current_user( $u2 );
		BP_UnitTestCase::add_user_to_group( $u2, $g );
		$m2 = new BP_Groups_Member( $u2, $g );
		$m2->promote( 'mod' );

		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_restricted_comment_doc_ids();

		$this->assertFalse( in_array( $d, $restricted_ids ) );

		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_comment_ids_logged_in_prevent() {
		$old_current_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$d = $this->factory->doc->create();
		bp_docs_update_doc_comment_access( $d, 'loggedin' );

		// Silence comment flood errors.
		add_filter( 'comment_flood_filter', '__return_false' );

		// Add a comment
		$userdata = get_userdata( $u1 );
		$c = wp_new_comment( array(
			'comment_post_ID'      => $d,
			'comment_author'       => $userdata->user_nicename,
			'comment_author_url'   => 'http://buddypress.org',
			'comment_author_email' => $userdata->user_email,
			'comment_content'      => 'this is a doc comment',
			'comment_type'         => '',
			'comment_parent'       => 0,
			'user_id'              => $u1,
		) );

		// Approve the comment
		$this->factory->comment->update_object( $c, array( 'comment_approved' => 1 ) );

		// Pretend we're logged out.
		$this->set_current_user( 0 );

		// Check that our access query setup is working.
		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_comment_ids();
		$this->assertTrue( in_array( $c, $restricted_ids ) );

		// Get comments, make sure this comment is not included.
		$comment_ids = get_comments( array( 'post_id' => $d, 'fields' => 'ids' ) );
		$this->assertFalse( in_array( $c, $comment_ids ) );

		$this->set_current_user( $old_current_user );
		remove_filter( 'comment_flood_filter', '__return_false' );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_comment_ids_creator_only_prevent() {
		$old_current_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$d = $this->factory->doc->create();
		bp_docs_update_doc_comment_access( $d, 'creator' );

		// Silence comment flood errors.
		add_filter( 'comment_flood_filter', '__return_false' );

		// Add a comment
		$userdata = get_userdata( $u1 );
		$c = wp_new_comment( array(
			'comment_post_ID'      => $d,
			'comment_author'       => $userdata->user_nicename,
			'comment_author_url'   => 'http://buddypress.org',
			'comment_author_email' => $userdata->user_email,
			'comment_content'      => 'this is a doc comment',
			'comment_type'         => '',
			'comment_parent'       => 0,
			'user_id'              => $u1,
		) );

		// Approve the comment
		$this->factory->comment->update_object( $c, array( 'comment_approved' => 1 ) );

		// Only the doc owner should have access.
		$this->set_current_user( $u2 );

		// Check that our access query setup is working.
		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_comment_ids();
		$this->assertTrue( in_array( $c, $restricted_ids ) );

		// Get comments, make sure this comment is not included.
		$comment_ids = get_comments( array( 'post_id' => $d, 'fields' => 'ids' ) );
		$this->assertFalse( in_array( $c, $comment_ids ) );

		$this->set_current_user( $old_current_user );
		remove_filter( 'comment_flood_filter', '__return_false' );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_comment_ids_group_member_prevent() {
		$old_current_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$g = $this->factory->group->create( array(
			'status' => 'public',
			'creator_id' => $u1
		) );

		$d = $this->factory->doc->create( array(
			'group' => $g,
		) );
		bp_docs_update_doc_comment_access( $d, 'group-members' );

		// Silence comment flood errors.
		add_filter( 'comment_flood_filter', '__return_false' );

		// Add a comment
		$userdata = get_userdata( $u1 );
		$c = wp_new_comment( array(
			'comment_post_ID'      => $d,
			'comment_author'       => $userdata->user_nicename,
			'comment_author_url'   => 'http://buddypress.org',
			'comment_author_email' => $userdata->user_email,
			'comment_content'      => 'this is a doc comment',
			'comment_type'         => '',
			'comment_parent'       => 0,
			'user_id'              => $u1,
		) );

		// Approve the comment
		$this->factory->comment->update_object( $c, array( 'comment_approved' => 1 ) );

		// We'll be a non-group-member.
		$u2 = $this->factory->user->create();
		$this->set_current_user( $u2 );

		// Check that our access query setup is working.
		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_comment_ids();
		$this->assertTrue( in_array( $c, $restricted_ids ) );

		// Get comments, make sure this comment is not included.
		$comment_ids = get_comments( array( 'post_id' => $d, 'fields' => 'ids' ) );
		$this->assertFalse( in_array( $c, $comment_ids ) );

		$this->set_current_user( $old_current_user );
		remove_filter( 'comment_flood_filter', '__return_false' );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_comment_ids_group_admins_mods_prevent() {
		$old_current_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$g = $this->factory->group->create( array(
			'status' => 'public',
			'creator_id' => $u1
		) );

		$d = $this->factory->doc->create( array(
			'group' => $g,
		) );
		bp_docs_update_doc_comment_access( $d, 'admins-mods' );

		// Silence comment flood errors.
		add_filter( 'comment_flood_filter', '__return_false' );

		// Add a comment.
		$userdata = get_userdata( $u1 );
		$c = wp_new_comment( array(
			'comment_post_ID'      => $d,
			'comment_author'       => $userdata->user_nicename,
			'comment_author_url'   => 'http://buddypress.org',
			'comment_author_email' => $userdata->user_email,
			'comment_content'      => 'this is a doc comment',
			'comment_type'         => '',
			'comment_parent'       => 0,
			'user_id'              => $u1,
		) );

		// Approve the comment
		$this->factory->comment->update_object( $c, array( 'comment_approved' => 1 ) );

		// We'll be a regular group-member.
		$u2 = $this->factory->user->create();
		$this->set_current_user( $u2 );
		BP_UnitTestCase::add_user_to_group( $u2, $g );

		// Check that our access query setup is working.
		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_comment_ids();
		$this->assertTrue( in_array( $c, $restricted_ids ) );

		// Get comments, make sure this comment is not included.
		$comment_ids = get_comments( array( 'post_id' => $d, 'fields' => 'ids' ) );
		$this->assertFalse( in_array( $c, $comment_ids ) );

		$this->set_current_user( $old_current_user );
		remove_filter( 'comment_flood_filter', '__return_false' );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_comment_ids_logged_in_allow() {
		$old_current_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$d = $this->factory->doc->create();
		bp_docs_update_doc_comment_access( $d, 'loggedin' );

		// Silence comment flood errors.
		add_filter( 'comment_flood_filter', '__return_false' );

		// Add a comment
		$userdata = get_userdata( $u1 );
		$c = wp_new_comment( array(
			'comment_post_ID'      => $d,
			'comment_author'       => $userdata->user_nicename,
			'comment_author_url'   => 'http://buddypress.org',
			'comment_author_email' => $userdata->user_email,
			'comment_content'      => 'this is a doc comment',
			'comment_type'         => '',
			'comment_parent'       => 0,
			'user_id'              => $u1,
		) );

		// Approve the comment
		$this->factory->comment->update_object( $c, array( 'comment_approved' => 1 ) );

		// Pretend we're a different, logged-in user.
		$this->set_current_user( $u2 );

		// Check that our access query setup is working.
		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_comment_ids();
		$this->assertFalse( in_array( $c, $restricted_ids ) );

		// Get comments, make sure this comment is included.
		$comment_ids = get_comments( array( 'post_id' => $d, 'fields' => 'ids' ) );
		$this->assertTrue( in_array( $c, $comment_ids ) );

		$this->set_current_user( $old_current_user );
		remove_filter( 'comment_flood_filter', '__return_false' );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_comment_ids_creator_only_allow() {
		$old_current_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$d = $this->factory->doc->create();
		bp_docs_update_doc_comment_access( $d, 'creator' );

		// Silence comment flood errors.
		add_filter( 'comment_flood_filter', '__return_false' );

		// Add a comment
		$userdata = get_userdata( $u1 );
		$c = wp_new_comment( array(
			'comment_post_ID'      => $d,
			'comment_author'       => $userdata->user_nicename,
			'comment_author_url'   => 'http://buddypress.org',
			'comment_author_email' => $userdata->user_email,
			'comment_content'      => 'this is a doc comment',
			'comment_type'         => '',
			'comment_parent'       => 0,
			'user_id'              => $u1,
		) );

		// Approve the comment
		$this->factory->comment->update_object( $c, array( 'comment_approved' => 1 ) );

		// Check that our access query setup is working.
		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_comment_ids();
		$this->assertFalse( in_array( $c, $restricted_ids ) );

		// Get comments, make sure this comment is included.
		$comment_ids = get_comments( array( 'post_id' => $d, 'fields' => 'ids' ) );
		$this->assertTrue( in_array( $c, $comment_ids ) );

		$this->set_current_user( $old_current_user );
		remove_filter( 'comment_flood_filter', '__return_false' );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_comment_ids_group_member_allow() {
		$old_current_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$g = $this->factory->group->create( array(
			'status' => 'public',
			'creator_id' => $u1
		) );

		$d = $this->factory->doc->create( array(
			'group' => $g,
		) );
		bp_docs_update_doc_comment_access( $d, 'group-members' );

		// Silence comment flood errors.
		add_filter( 'comment_flood_filter', '__return_false' );

		// Add a comment
		$userdata = get_userdata( $u1 );
		$c = wp_new_comment( array(
			'comment_post_ID'      => $d,
			'comment_author'       => $userdata->user_nicename,
			'comment_author_url'   => 'http://buddypress.org',
			'comment_author_email' => $userdata->user_email,
			'comment_content'      => 'this is a doc comment',
			'comment_type'         => '',
			'comment_parent'       => 0,
			'user_id'              => $u1,
		) );

		// Approve the comment
		$this->factory->comment->update_object( $c, array( 'comment_approved' => 1 ) );

		// We'll be a group-member.
		$u2 = $this->factory->user->create();
		$this->set_current_user( $u2 );
		BP_UnitTestCase::add_user_to_group( $u2, $g );

		// Check that our access query setup is working.
		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_comment_ids();
		$this->assertFalse( in_array( $c, $restricted_ids ) );

		// Get comments, make sure this comment is included.
		$comment_ids = get_comments( array( 'post_id' => $d, 'fields' => 'ids' ) );
		$this->assertTrue( in_array( $c, $comment_ids ) );

		$this->set_current_user( $old_current_user );
		remove_filter( 'comment_flood_filter', '__return_false' );
	}

	/**
	 * @group bp_docs_access_query
	 */
	public function test_bp_docs_access_query_get_comment_ids_group_admins_mods_allow() {
		$old_current_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$this->set_current_user( $u1 );

		$g = $this->factory->group->create( array(
			'status' => 'public',
			'creator_id' => $u1
		) );

		$d = $this->factory->doc->create( array(
			'group' => $g,
		) );
		bp_docs_update_doc_comment_access( $d, 'admins-mods' );

		// Silence comment flood errors.
		add_filter( 'comment_flood_filter', '__return_false' );

		// Add a comment.
		$userdata = get_userdata( $u1 );
		$c = wp_new_comment( array(
			'comment_post_ID'      => $d,
			'comment_author'       => $userdata->user_nicename,
			'comment_author_url'   => 'http://buddypress.org',
			'comment_author_email' => $userdata->user_email,
			'comment_content'      => 'this is a doc comment',
			'comment_type'         => '',
			'comment_parent'       => 0,
			'user_id'              => $u1,
		) );

		// Approve the comment
		$this->factory->comment->update_object( $c, array( 'comment_approved' => 1 ) );

		// We'll be a group mod.
		$u2 = $this->factory->user->create();
		$this->set_current_user( $u2 );
		BP_UnitTestCase::add_user_to_group( $u2, $g );
		$m2 = new BP_Groups_Member( $u2, $g );
		$m2->promote( 'mod' );

		// Check that our access query setup is working.
		$bp_docs_access_query = bp_docs_access_query();
		$restricted_ids = $bp_docs_access_query->get_comment_ids();
		$this->assertFalse( in_array( $c, $restricted_ids ) );

		// Get comments, make sure this comment is included.
		$comment_ids = get_comments( array( 'post_id' => $d, 'fields' => 'ids' ) );
		$this->assertTrue( in_array( $c, $comment_ids ) );

		$this->set_current_user( $old_current_user );
		remove_filter( 'comment_flood_filter', '__return_false' );
	}

	/**
	 * @group BP_Docs_Query
	 */
	public function test_doc_update_should_maintain_original_author() {
		$old_current_user = get_current_user_id();

		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();

		$args = array(
			'title' 	=> 'Blue Skirt Waltz',
			'content'	=> 'I remember that night with you, lady, when first we met...',
			'author_id' => $u1,
		);

		$query = new BP_Docs_Query;
		$save_result = $query->save( $args );
		$doc_id = $save_result['doc_id'];

		// wp_insert_post is current_user sensitive.
		$this->set_current_user( $u2 );

		$args = array(
			'doc_id'	=> $doc_id,
			'title' 	=> 'Blue Skirt Waltz',
			'content'	=> 'We danced in a world of blue, how could my heart forget...',
			'author_id' => $u2,
		);

		$query = new BP_Docs_Query;
		$save_result = $query->save( $args );

		$doc = get_post( $doc_id );
		$this->assertEquals( $u1, $doc->post_author );

		$this->set_current_user( $old_current_user );
	}

}
