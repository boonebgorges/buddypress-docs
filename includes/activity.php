<?php

/**
 * Functionality related to bp-activity
 *
 * @since 1.7
 */

/**
 * Post an activity item when a comment is posted to a doc.
 *
 * @since 1.0-beta
 *
 * @param int $comment_id The comment ID.
 * @return int $activity_id The id number of the activity created
 */
function bp_docs_post_comment_activity( $comment_id ) {
	if ( empty( $comment_id ) ) {
		return false;
	}

	$comment = get_comment( $comment_id );
	$doc     = !empty( $comment->comment_post_ID ) ? get_post( $comment->comment_post_ID ) : false;

	if ( empty( $doc ) ) {
		return false;
	}

	// Only continue if this is a BP Docs post
	if ( $doc->post_type != bp_docs_get_post_type_name() ) {
		return;
	}

	$doc_id = ! empty( $doc->ID ) ? $doc->ID : false;

	if ( ! $doc_id ) {
		return false;
	}

	// See if we're associated with a group
	$group_id = bp_is_active( 'groups' ) ? bp_docs_get_associated_group_id( $doc_id ) : 0;

	if ( $group_id ) {
		$component = 'groups';
		$item = $group_id;
	} else {
		$component = 'bp_docs';
		$item = 0;
	}

	// Set the action. Filterable so that other integration pieces can alter it
	$action       = '';
	$commenter    = get_user_by( 'email', $comment->comment_author_email );
	$commenter_id = !empty( $commenter->ID ) ? $commenter->ID : false;

	// Since BP Docs only allows member comments, the following should never happen
	if ( !$commenter_id ) {
		return false;
	}

	$user_link    = bp_core_get_userlink( $commenter_id );
	$doc_url      = bp_docs_get_doc_link( $doc_id );
	$comment_url  = $doc_url . '#comment-' . $comment->comment_ID;
	$comment_link = '<a href="' . $comment_url . '">' . $doc->post_title . '</a>';

	$action = sprintf( __( '%1$s commented on the doc %2$s', 'buddypress-docs' ), $user_link, $comment_link );

	$action	= apply_filters( 'bp_docs_comment_activity_action', $action, $user_link, $comment_link, $component, $item );

	// Set the type, to be used in activity filtering
	$type = 'bp_doc_comment';

	$hide_sitewide = bp_docs_hide_sitewide_for_doc( $doc_id );

	$args = array(
		'user_id'		=> $commenter_id,
		'action'		=> $action,
		'content'		=> $comment->comment_content,
		'primary_link'		=> $comment_url,
		'component'		=> $component,
		'type'			=> $type,
		'item_id'		=> $item, // Set to the group/user/etc id, for better consistency with other BP components
		'secondary_item_id'	=> $comment_id, // The id of the doc itself. Note: limitations in the BP activity API mean I don't get to store the doc_id, but at least it can be abstracted from the comment_id
		'recorded_time'		=> bp_core_current_time(),
		'hide_sitewide'		=> apply_filters( 'bp_docs_hide_sitewide', $hide_sitewide, $comment, $doc, $item, $component ) // Filtered to allow plugins and integration pieces to dictate
	);

	do_action( 'bp_docs_before_comment_activity_save', $args );

	$activity_id = bp_activity_add( apply_filters( 'bp_docs_comment_activity_args', $args ) );

	do_action( 'bp_docs_after_comment_activity_save', $activity_id, $args );

	return $activity_id;
}
/**
 * Catch comments that are moving from moderation to approved status.
 * This hook is a dynamic hook: comment_{$new_status}_{$comment->comment_type}
 * where $comment->comment_type is an empty string in the case of standard comments.
 */
add_action( 'comment_approved_', 'bp_docs_post_comment_activity', 8 );

/**
 * Pass new comments to our activity creation function, if they are approved.
 *
 * @since 2.2
 *
 * @param int $comment_id         The comment ID.
 * @param mixed $comment_approved 1 if the comment is approved, 0 if not, 'spam' if spam.
 * @return int $activity_id The id number of the activity created
 */
function bp_docs_post_comment_activity_if_approved( $comment_id, $comment_approved ) {
	if ( 1 === $comment_approved ) {
		bp_docs_post_comment_activity( $comment_id );
	}
}
add_action( 'comment_post', 'bp_docs_post_comment_activity_if_approved', 8, 2 );

/**
 * Post an activity item on doc save.
 *
 * @since 1.0-beta
 *
 * @param obj $query The query object created in BP_Docs_Query and passed to the
 *        bp_docs_doc_saved filter
 * @return int $activity_id The id number of the activity created
 */
function bp_docs_post_activity( $query ) {
	global $bp;

	// todo: exception for autosave?

	$doc_id	= !empty( $query->doc_id ) ? $query->doc_id : false;

	if ( !$doc_id )
		return false;

	$last_editor	= get_post_meta( $doc_id, 'bp_docs_last_editor', true );

	// Throttle 'doc edited' posts. By default, one per user per hour
	if ( !$query->is_new_doc ) {
		// Look for an existing activity item corresponding to this user editing
		// this doc
		$already_args = array(
			'max'		=> 1,
			'sort'		=> 'DESC',
			'show_hidden'	=> 1, // We need to compare against all activity
			'filter'	=> array(
				'user_id'	=> $last_editor,
				'action'	=> 'bp_doc_edited', // BP bug. 'action' is type
				'secondary_id'	=> $doc_id // We don't really care about the item_id for these purposes (it could have been changed)
			),
		);

		$already_activity = bp_activity_get( $already_args );

		// If any activity items are found, compare its date_recorded with time() to
		// see if it's within the allotted throttle time. If so, don't record the
		// activity item
		if ( !empty( $already_activity['activities'] ) ) {
			$date_recorded 	= $already_activity['activities'][0]->date_recorded;
			$drunix 	= strtotime( $date_recorded );
			if ( time() - $drunix <= apply_filters( 'bp_docs_edit_activity_throttle_time', 60*60 ) ) {
				return;
			}
		}
	}

	$doc = get_post( $doc_id );

	// Don't create activity if the Doc title or content hasn't changed.
	if ( ! $query->is_new_doc && ( $query->previous_revision instanceof WP_Post ) && $doc->post_title === $query->previous_revision->post_title && $doc->post_content === $query->previous_revision->post_content ) {
		return;
	}

	// Don't create activity if the post is not "publish" status.
	if ( 'publish' != $doc->post_status ) {
		return;
	}

	// Set the action. Filterable so that other integration pieces can alter it
	$action 	= '';
	$user_link 	= bp_core_get_userlink( $last_editor );
	$doc_url	= bp_docs_get_doc_link( $doc_id );
	$doc_link	= '<a href="' . $doc_url . '">' . $doc->post_title . '</a>';

	if ( $query->is_new_doc ) {
		$action = sprintf( __( '%1$s created the doc %2$s', 'buddypress-docs' ), $user_link, $doc_link );
	} else {
		$action = sprintf( __( '%1$s edited the doc %2$s', 'buddypress-docs' ), $user_link, $doc_link );
	}

	$action	= apply_filters( 'bp_docs_activity_action', $action, $user_link, $doc_link, $query->is_new_doc, $query );

	$hide_sitewide = bp_docs_hide_sitewide_for_doc( $doc_id );

	$component = 'bp_docs';

	// This is only temporary! This item business needs to be component-neutral
	$item = isset( $bp->groups->current_group->id ) ? $bp->groups->current_group->id : false;

	// Set the type, to be used in activity filtering
	$type = $query->is_new_doc ? 'bp_doc_created' : 'bp_doc_edited';

	$args = array(
		'user_id'		=> $last_editor,
		'action'		=> $action,
		'primary_link'		=> $doc_url,
		'component'		=> $component,
		'type'			=> $type,
		'item_id'		=> $query->item_id, // Set to the group/user/etc id, for better consistency with other BP components
		'secondary_item_id'	=> $doc_id, // The id of the doc itself
		'recorded_time'		=> bp_core_current_time(),
		'hide_sitewide'		=> apply_filters( 'bp_docs_hide_sitewide', $hide_sitewide, false, $doc, $item, $component ) // Filtered to allow plugins and integration pieces to dictate
	);

	do_action( 'bp_docs_before_activity_save', $args );

	$activity_id = bp_activity_add( apply_filters( 'bp_docs_activity_args', $args, $query ) );

	do_action( 'bp_docs_after_activity_save', $activity_id, $args );

	return $activity_id;
}
add_action( 'bp_docs_doc_saved', 'bp_docs_post_activity' );

/**
 * Delete activity associated with a Doc
 *
 * Run on transition_post_status, to catch deletes from all locations
 *
 * @since 1.3
 *
 * @param string $new_status
 * @param string $old_status
 * @param obj WP_Post object
 */
function bp_docs_delete_doc_activity( $new_status, $old_status, $post ) {
	if ( ! bp_is_active( 'activity' ) ) {
		return;
	}

	if ( bp_docs_get_post_type_name() != $post->post_type ) {
		return;
	}

	/*
	 * Only continue the activity deletion process
	 * if the doc is being switched to a non-public status.
	 */
	if ( ! in_array( $new_status, array( 'trash', 'bp_docs_pending', 'draft' ) ) ) {
		return;
	}

	$activities = bp_activity_get(
		array(
			'filter' => array(
				'secondary_id' => $post->ID,
				'component' => 'docs',
			),
		)
	);

	foreach ( (array) $activities['activities'] as $activity ) {
		bp_activity_delete( array( 'id' => $activity->id ) );
	}
}
add_action( 'transition_post_status', 'bp_docs_delete_doc_activity', 10, 3 );

/**
 * Register BP Docs activity actions.
 *
 * @since 1.7.0
 */
function bp_docs_register_activity_actions() {
	bp_activity_set_action(
		'bp_docs',
		'bp_doc_created',
		__( 'Created a Doc', 'buddypress-docs' ),
		'bp_docs_format_activity_action_bp_doc_created'
	);

	bp_activity_set_action(
		'bp_docs',
		'bp_doc_edited',
		__( 'Edited a Doc', 'buddypress-docs' ),
		'bp_docs_format_activity_action_bp_doc_edited'
	);

	bp_activity_set_action(
		'bp_docs',
		'bp_doc_comment',
		__( 'Commented on a Doc', 'buddypress-docs' ),
		'bp_docs_format_activity_action_bp_doc_comment'
	);
}
add_action( 'bp_register_activity_actions', 'bp_docs_register_activity_actions' );

/**
 * Format 'bp_doc_created' activity actions.
 *
 * @since 1.7.0
 *
 * @param string $action Activity action.
 * @param object $activity Activity object.
 * @return string
 */
function bp_docs_format_activity_action_bp_doc_created( $action, $activity ) {
	if ( empty( $activity->secondary_item_id ) ) {
		return $action;
	}

	$doc = get_post( $activity->secondary_item_id );
	if ( ! $doc ) {
		return $action;
	}

	$user_link = bp_core_get_userlink( $activity->user_id );

	$doc_url = bp_docs_get_doc_link( $activity->secondary_item_id );
	$doc_link = sprintf( '<a href="%s">%s</a>', $doc_url, $doc->post_title );

	$action = sprintf( __( '%1$s created the doc %2$s', 'buddypress-docs' ), $user_link, $doc_link );

	return $action;
}

/**
 * Format 'bp_doc_edited' activity actions.
 *
 * @since 1.7.0
 *
 * @param string $action Activity action.
 * @param object $activity Activity object.
 * @return string
 */
function bp_docs_format_activity_action_bp_doc_edited( $action, $activity ) {
	if ( empty( $activity->secondary_item_id ) ) {
		return $action;
	}

	$doc = get_post( $activity->secondary_item_id );
	if ( ! $doc ) {
		return $action;
	}

	$user_link = bp_core_get_userlink( $activity->user_id );

	$doc_url = bp_docs_get_doc_link( $activity->secondary_item_id );
	$doc_link = sprintf( '<a href="%s">%s</a>', $doc_url, $doc->post_title );

	$action = sprintf( __( '%1$s edited the doc %2$s', 'buddypress-docs' ), $user_link, $doc_link );

	return $action;
}

/**
 * Format 'bp_doc_comment' activity actions.
 *
 * @since 1.7.0
 *
 * @param string $action Activity action.
 * @param object $activity Activity object.
 * @return string
 */
function bp_docs_format_activity_action_bp_doc_comment( $action, $activity ) {
	$comment = get_comment( $activity->secondary_item_id );
	if ( ! $comment || ! $comment->comment_post_ID ) {
		return $action;
	}

	$doc = get_post( $comment->comment_post_ID );
	if ( ! $doc ) {
		return $action;
	}

	$user_link = bp_core_get_userlink( $activity->user_id );

	$doc_url = bp_docs_get_doc_link( $doc->ID );
	$comment_url = $doc_url . '#comment-' . $comment->comment_ID;
	$doc_link = sprintf( '<a href="%s">%s</a>', $comment_url, $doc->post_title );

	$action = sprintf( __( '%1$s commented on the doc %2$s', 'buddypress-docs' ), $user_link, $doc_link );

	return $action;
}
/**
 * Fetch data related to Docs at the beginning of an activity loop.
 *
 * This reduces database overhead during the activity loop.
 *
 * @since 1.7.0
 *
 * @param array $activities Array of activity items.
 * @return array
 */
function bp_docs_prefetch_activity_object_data( $activities ) {
	if ( empty( $activities ) ) {
		return $activities;
	}

	$doc_ids = array();
	$doc_comment_ids = array();

	foreach ( $activities as $activity ) {
		if ( 'bp_docs' !== $activity->component ) {
			continue;
		}

		// Doc ID stored in different places
		if ( 'bp_doc_created' === $activity->type || 'bp_doc_edited' === $activity->type ) {
			$doc_ids[] = $activity->secondary_item_id;
		} else if ( 'bp_doc_comment' === $activity->type ) {
			$doc_comment_ids[] = $activity->secondary_item_id;
		}
	}

	// We've got to get the comments. Don't know of an easy way to do this
	// using the WP API
	if ( ! empty( $doc_comment_ids ) ) {
		global $wpdb;
		$doc_comment_ids_sql = implode( ',', wp_parse_id_list( $doc_comment_ids ) );
		$comment_post_ids = $wpdb->get_col( "SELECT comment_post_ID FROM {$wpdb->comments} WHERE comment_ID IN ({$doc_comment_ids_sql})" );
		$doc_ids = array_unique( array_merge( $doc_ids, $comment_post_ids ) );
	}

	if ( ! empty( $doc_ids ) ) {
		// prime post caches
		// using the private function because the public functions
		// weren't caching correctly. @todo fix this
		_prime_post_caches( $doc_ids, false, false );
	}
}
add_filter( 'bp_activity_prefetch_object_data', 'bp_docs_prefetch_activity_object_data' );

/**
 * Adds BP Docs options to activity filter dropdowns
 *
 * @since 1.0-beta
 */
function bp_docs_activity_filter_options() {
	if ( function_exists( 'bp_is_group' ) && bp_is_group() && ! bp_docs_is_docs_enabled_for_group( bp_get_current_group_id() ) ) {
		return;
	}

	?>

	<option value="bp_doc_created"><?php _e( 'New Docs', 'buddypress-docs' ); ?></option>
	<option value="bp_doc_edited"><?php _e( 'Doc Edits', 'buddypress-docs' ); ?></option>
	<option value="bp_doc_comment"><?php _e( 'Doc Comments', 'buddypress-docs' ); ?></option>

	<?php
}

/**
 * Wrapper for activity filter dropdown hooks to avoid polluting global scope.
 *
 * @since 1.7.0
 */
function bp_docs_load_activity_filter_options() {
	// Add BP Docs activity types to the activity filter dropdown
	$dropdowns = apply_filters( 'bp_docs_activity_filter_locations', array(
		'bp_activity_filter_options',
		'bp_group_activity_filter_options',
		'bp_member_activity_filter_options'
	) );
	foreach( $dropdowns as $hook ) {
		add_action( $hook, 'bp_docs_activity_filter_options' );
	}
}
add_action( 'bp_screens', 'bp_docs_load_activity_filter_options', 1 );

/**
 * Access protection in the activity feed.
 * Users should not see activity related to docs to which they do not have access.
 *
 * @since 2.0
 * @since 2.1.2 Avoids filtering when activity scope doesn't include Docs-related items.
 *
 * @param $where_conditions
 */
function bp_docs_access_protection_for_activity_feed( $where_conditions, $r ) {
	$is_docs_query = true;

	/*
	 * Err on the side of caution: it's a Docs query if the string appears anywhere,
	 * or if component + type are empty - ie, no restrictions on query.
	 */
	$contains_docs = false;
	$has_type      = false;
	$has_component = false;

	$exclude_clauses = array( 'excluded_types', 'spam_sql', 'hidden_sql' );

	foreach ( $where_conditions as $condition_type => $condition ) {
		if ( in_array( $condition_type, $exclude_clauses, true ) ) {
			continue;
		}

		if ( false !== strpos( $condition, 'bp_doc' ) ) {
			$contains_docs = true;
		}

		if ( false !== strpos( $condition, 'component' ) ) {
			$has_component = true;
		}

		if ( false !== strpos( $condition, 'type' ) ) {
			$has_type = true;
		}
	}

	if ( ! $contains_docs && ( $has_type || $has_component ) ) {
		return $where_conditions;
	}

	$bp_docs_access_query  = bp_docs_access_query();
	$protected_doc_ids     = $bp_docs_access_query->get_doc_ids();
	$protected_comment_ids = $bp_docs_access_query->get_comment_ids();

	// Docs and their commments are protected independently.
	if ( ! $protected_doc_ids && ! $protected_comment_ids ) {
		return $where_conditions;
	}

	/*
	 * DeMorgan says: ! ( A & B ) == ( ! A || ! B )
	 * For bp_doc_created and bp_doc_edited, the secondary_item_id is the doc_id.
	 * For bp_doc_comment, the secondary_item_id is the comment ID.
	 */
	$activity_query = new BP_Activity_Query( array(
		'relation' => 'AND',
		array(
			'relation' => 'OR',
			array(
				'column' => 'type',
				'value' => array( 'bp_doc_created', 'bp_doc_edited' ),
				'compare' => 'NOT IN',
			),
			array(
				'column' => 'secondary_item_id',
				'value' => $protected_doc_ids,
				'compare' => 'NOT IN',
			),
		),
		array(
			'relation' => 'OR',
			array(
				'column' => 'type',
				'value' => array( 'bp_doc_comment' ),
				'compare' => 'NOT IN',
			),
			array(
				'column' => 'secondary_item_id',
				'value' => $protected_comment_ids,
				'compare' => 'NOT IN',
			),
		),
	) );
	$aq_sql = $activity_query->get_sql();
	if ( $aq_sql ) {
		$where_conditions[] = $aq_sql;
	}
	return $where_conditions;
}
add_filter( 'bp_activity_get_where_conditions', 'bp_docs_access_protection_for_activity_feed', 10, 2 );

/**
 * Keep some activity items out of Group Email Subscription "all activity" emails.
 * Users should not see activity related to docs to which they do not have access.
 *
 * @since 2.0
 *
 * @param bool $allow        Whether to send the email
 * @param bool $activity_obj The BP_Activity_Activity object
 * @param int  $user_id      The email recipient's user ID
 *
 * @return bool $send_it Whether to send the email
 */
function bp_docs_filter_bp_ass_send_activity_notification_for_user( $allow, $activity_obj, $user_id ) {
	return bp_docs_allow_activity_item_visibility( $allow, $activity_obj, $user_id );
}
add_filter( 'bp_ass_send_activity_notification_for_user', 'bp_docs_filter_bp_ass_send_activity_notification_for_user', 10, 3 );

/**
 * Keep some activity items out of Group Email Subscription "digest" emails.
 * Users should not see activity related to docs to which they do not have access.
 *
 * @since 2.0
 *
 * @param bool $allow       Whether to include this activity item.
 * @param bool $activity_id ID of the activity item.
 * @param int  $user_id     The email recipient's user ID
 *
 * @return bool $send_it Whether to send the email
 */
function bp_docs_filter_ass_digest_record_activity_allow( $allow, $activity_id, $user_id ) {
	$activity_obj = new BP_Activity_Activity( $activity_id );
	return bp_docs_allow_activity_item_visibility( $allow, $activity_obj, $user_id );
}
add_filter( 'ass_digest_record_activity_allow', 'bp_docs_filter_ass_digest_record_activity_allow', 10, 3 );

/**
 * Should this user be allowed to see this activity object?
 * Users should not see activity related to docs to which they do not have access.
 *
 * @since 2.0
 *
 * @param bool $allow        Should the user be able to see this activity item?
 * @param bool $activity_obj The BP_Activity_Activity object
 * @param int  $user_id      The user ID
 *
 * @return bool $allow Whether to allow this activity item to be accessible.
 */
function bp_docs_allow_activity_item_visibility( $allow, $activity_obj, $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = bp_loggedin_user_id();
	}

	switch ( $activity_obj->type ) {
		case 'bp_doc_created':
		case 'bp_doc_edited':
			$bp_docs_access_query = BP_Docs_Access_Query::init( $user_id );
			$protected_doc_ids    = $bp_docs_access_query->get_doc_ids();

			// For bp_doc_created and bp_doc_edited, the secondary_item_id is the doc_id.
			if ( in_array( $activity_obj->secondary_item_id, $protected_doc_ids ) ) {
				$allow = false;
			}
			break;

		case 'bp_doc_comment':
			$bp_docs_access_query  = BP_Docs_Access_Query::init( $user_id );
			$protected_comment_ids = $bp_docs_access_query->get_comment_ids();

			// For bp_doc_comment, the secondary_item_id is the comment ID.
			if ( in_array( $activity_obj->secondary_item_id, $protected_comment_ids ) ) {
				$allow = false;
			}
			break;

		default:
			// Do nothing.
			break;
	}

	return $allow;
}

/**
 * Modify the AJAX query string to enable filtering of activity stream.
 *
 * @since 1.9.5
 *
 * @param string $qs The query string for the BuddyPress loop
 * @param string $object The current object for the query string
 * @return string The modified query string
 */
function bp_docs_activity_filter_querystring( $qs, $object ) {

	// bail if not an activity object
	if ( $object != 'activity' ) return $qs;

	// parse query string into an array
	$r = wp_parse_args( $qs );

	// bail if no type is set
	if ( empty( $r['type'] ) ) return $qs;

	// define activity types
	$types = array( 'bp_doc_created', 'bp_doc_edited', 'bp_doc_comment' );

	// bail if not a type that we're looking for
	if ( ! in_array( $r['type'], $types ) ) {
		return $qs;
	}

	// add the types if they don't exist
	foreach( $types AS $type ) {
		if ( $type === $r['type'] ) {
			if ( ! isset( $r['action'] ) OR false === strpos( $r['action'], $type ) ) {
				// 'action' filters activity items by the 'type' column
				$r['action'] = $type;
			}
		}
	}

	// 'type' isn't used anywhere internally
	unset( $r['type'] );

	// return a querystring
	return build_query( $r );

}
add_filter( 'bp_ajax_querystring', 'bp_docs_activity_filter_querystring', 20, 2 );
