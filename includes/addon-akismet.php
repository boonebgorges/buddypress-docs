<?php

/**
 * Scan doc content using the Akismet spam-filtering service.
 *
 * @since 2.1.0
 */
class BP_Docs_Akismet {

	/**
	 * Constructor.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
	}

	/**
	 * Add action and filter hooks to add Akismet functionality.
	 *
	 * @since 2.1.0
	 */
	public function add_hooks() {
		// Check docs against the Akismet service.
		add_filter( 'bp_docs_post_args_before_save', array( $this, 'check_for_spam'), 10, 3 );
	}

	/**
	 * Check if the activity item is spam or ham.
	 *
	 * @since 2.1.0
	 *
	 * @see http://akismet.com/development/api/
	 * @todo Auto-delete old spam?
	 *
	 * @param array  $r    The parameters to be used in wp_insert_post().
	 * @param object $this The BP_Docs_Query object.
	 * @param array  $args The passed and filtered parameters for the doc
	 *                     about to be saved.
	 */
	public function check_for_spam( $save_args, $bdq_object, $passed_args ) {
		// Build data package for Akismet.
		$akismet_package = $this->build_akismet_data_package( $save_args, $passed_args );

		// Check with Akismet to see if this is spam.
		$akismet_response = $this->send_akismet_request( $akismet_package, 'check', 'spam' );

		/*
		 * Spam.
		 * Note that Akismet returns a true/false response as a string value.
		 */
		if ( 'true' === $akismet_response['bp_docs_as_result'] ) {
			/**
			 * Fires after a doc has been identified as spam, but before officially being marked as spam.
			 *
			 * @since 2.1.0
			 *
			 * @param array  $doc              The parameters to be used in wp_insert_post().
			 * @param array  $akismet_response Array of activity data for item including
			 *                                 Akismet check results data.
			 * @param int    $author_id        The ID of the author who posted the spam.
			 */
			do_action( 'bp_docs_akismet_spam_caught', $save_args, $akismet_response, $passed_args['author_id'] );

			// Set the post status as "pending" if it isn't already.
			$save_args['post_status'] = 'bp_docs_pending';
		}

		return $save_args;
	}

	/**
	 * Build a data package for the Akismet service to inspect.
	 *
	 * @since 2.1.0
	 *
	 * @see http://akismet.com/development/api/#comment-check
	 *
	 * @param BP_Doc $activity Doc post data to be checked.
	 * @param array  $args     Arguments calculated in the save() method.
	 * @return array $package
	 */
	public function build_akismet_data_package( $doc_args, $args ) {
		$userdata = get_userdata( $args['author_id'] );

		$package = array(
			'akismet_comment_nonce' => 'inactive',
			'comment_author'        => $userdata->display_name,
			'comment_author_email'  => $userdata->user_email,
			'comment_author_url'    => bp_core_get_userlink( $userdata->ID, false, true),
			'comment_content'       => $doc_args['post_content'],
			'comment_type'          => 'bp_doc_created',
			'permalink'             => site_url( bp_docs_get_docs_slug() ) . '/' . $doc_args['post_name'],
			'user_ID'               => $userdata->ID,
			'user_role'             => Akismet::get_user_roles( $userdata->ID ),
		);

		/**
		 * Get the nonce if the new activity was submitted through the edit form.
		 * This helps Akismet ensure that the update was a valid form submission.
		 */
		if ( ! empty( $_POST['_wpnonce'] ) ) {
			$package['akismet_comment_nonce'] = wp_verify_nonce( $_POST['_wpnonce'], 'bp_docs_save' ) ? 'passed' : 'failed';
		}

		/**
		 * Filters activity data before being sent to Akismet to inspect.
		 *
		 * @since 2.1.0
		 *
		 * @param array $package  Array of activity data for Akismet to inspect.
		 * @param array $doc_args Doc data for doc about to be saved.
		 */
		return apply_filters( 'bp_docs_akismet_build_akismet_data_package', $package, $doc_args );
	}

	/**
	 * Contact Akismet to check if this is spam or ham.
	 *
	 * This is based on the BuddyPress approach to passing
	 * activity items to Akismet.
	 *
	 * @since 2.1.0
	 *
	 * @param array  $package  Packet of information to submit to Akismet.
	 * @param string $check    "check" or "submit".
	 * @param string $spam     "spam" or "ham".
	 * @return array $activity_data Activity data, with Akismet data added.
	 */
	public function send_akismet_request( $package, $check = 'check', $spam = 'spam' ) {
		$query_string = $path = '';

		$package['blog']         = bp_get_option( 'home' );
		$package['blog_charset'] = bp_get_option( 'blog_charset' );
		$package['blog_lang']    = get_locale();
		$package['referrer']     = $_SERVER['HTTP_REFERER'];
		$package['user_agent']   = bp_core_current_user_ua();
		$package['user_ip']      = bp_core_current_user_ip();

		if ( Akismet::is_test_mode() ) {
			$package['is_test'] = true;
		}

		// Keys to ignore.
		$ignore = array( 'HTTP_COOKIE', 'HTTP_COOKIE2', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED_HOST', 'HTTP_MAX_FORWARDS', 'HTTP_X_FORWARDED_SERVER', 'REDIRECT_STATUS', 'SERVER_PORT', 'PATH', 'PHP_AUTH_PW', 'DOCUMENT_ROOT', 'SERVER_ADMIN', 'QUERY_STRING', 'PHP_SELF', 'argv', 'argc', 'SCRIPT_FILENAME', 'SCRIPT_NAME' );
		// Loop through _SERVER args and remove whitelisted keys.
		foreach ( $_SERVER as $key => $value ) {
			// Key should not be ignored.
			if ( ! in_array( $key, $ignore ) && is_string( $value ) ) {
				$package[$key] = $value;
			}
		}

		if ( 'check' == $check ) {
			$path = 'comment-check';
		} elseif ( 'submit' == $check ) {
			$path = 'submit-' . $spam;
		}

		// Send to Akismet.
		add_filter( 'akismet_ua', array( $this, 'buddypress_docs_ua' ) );
		$response = Akismet::http_post( http_build_query( $package ), $path );
		remove_filter( 'akismet_ua', array( $this, 'buddypress_docs_ua' ) );

		// Get the response.
		if ( ! empty( $response[1] ) && ! is_wp_error( $response[1] ) ) {
			$package['bp_docs_as_result'] = $response[1];
		} else {
			$package['bp_docs_as_result'] = false;
		}

		return $package;
	}

	/**
	 * Filters user agent when sending to Akismet to add BuddyPress Docs info.
	 *
	 * @since 2.1.0
	 *
	 * @param string $user_agent User agent string, as generated by Akismet.
	 * @return string $user_agent Modified user agent string.
	 */
	public function buddypress_docs_ua( $user_agent ) {
		// Prepend our application to the front
		$user_agent = 'BuddyPress Docs/' . constant( 'BP_DOCS_VERSION' ) . ' | ' . $user_agent;
		return $user_agent;
	}
}
