<?php

class BP_Docs_Wikitext {

	/**
	 * PHP 5 constructor
	 *
	 * @since 1.2
	 */
	public function __construct() {
		add_filter( 'the_content', array( $this, 'bracket_links' ) );
	}

	/**
	 * Detects wiki-style bracket linking
	 *
	 * @since 1.2
	 */
	function bracket_links( $content ) {
		// Don't do this on a non-Doc
		if ( ! bp_docs_is_existing_doc() ) {
			return $content;
		}

		// Find the text enclosed in double brackets.
		// Letters, numbers, spaces, parentheses, pipes
		$pattern = '|\[\[([a-zA-Z\s0-9\-\(\)\|]+?)\]\]|';
		$content = preg_replace_callback( $pattern, array( $this, 'process_bracket_content' ), $content );

		return $content;
	}

	/**
	 * Callback function for replacing bracketed links with the proper links
	 *
	 * If a page is found, a link to the page is produced. Otherwise a link to the create page
	 * is produced, with the create_title flag.
	 *
	 * @since 1.2
	 *
	 * @param array $match A single match passed from preg_replace_callback()
	 * @return str A formatted link
	 */
	function process_bracket_content( $match ) {
		// Check for a pipe
		if ( $pipe_pos = strpos( $match[1], '|' ) ) {
			// If one is found, then the link text will be different from
			// the page name
			$link_text = substr( $match[1], $pipe_pos + 1 );
			$link_page = substr( $match[1], 0, $pipe_pos );
		} else {
			// If no pipe is found, set the link text and link page the same
			$link_text = $link_page = $match[1];
		}

		/*
		 * Look for a published Doc with this title. Docs access protection
		 * applies, so Docs the current user cannot read are not matched.
		 */
		$query_args = array(
			'post_type'              => bp_docs_get_post_type_name(),
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'orderby'                => 'ID',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		$docs = get_posts( array_merge( $query_args, array( 'title' => $link_page ) ) );

		// If none were found, do the same query with page slugs. 'post_name__in'
		// is used instead of 'name' because singular queries skip the access
		// protection tax_query.
		if ( empty( $docs ) ) {
			$docs = get_posts( array_merge( $query_args, array( 'post_name__in' => array( sanitize_title_with_dashes( $link_page ) ) ) ) );
		}

		// Filter the docs. This will be used to exclude docs that do not belong to a group
		$docs = apply_filters( 'bp_docs_bracket_matches', $docs );

		if ( !empty( $docs ) ) {
			// If we have a result, create a link to that page
			// There might be more than one result. I guess we take the first one
			$doc 	   = $docs[0];

			$permalink = get_permalink( $doc );
			$class 	   = 'existing-doc';
		} else {
			// If no result is found, create a link to the edit page
			$permalink = add_query_arg( 'create_title', urlencode( $link_page ), bp_docs_get_create_link() );
			$class	   = 'nonexistent-doc';
		}

		return apply_filters( 'bp_docs_bracket_link', '<a href="' . esc_url( $permalink ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $link_text ) . '</a>' );
	}

}
