<?php

class BP_Docs_Wikitext {
	/**
	 * PHP 4 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.2
	 */
	function bp_docs_wikitext() {
		$this->__construct();
	}

	/**
	 * PHP 5 constructor
	 *
	 * @package BuddyPress Docs
	 * @since 1.2
	 */
	function __construct() {
		add_filter( 'the_content', array( $this, 'bracket_links' ) );
	}

	/**
	 * Detects wiki-style bracket linking
	 *
	 * @package BuddyPress Docs
	 * @since 1.2
	 */
	function bracket_links( $content ) {
		$pattern = '|\[\[([a-zA-Z\s0-9]+?)\]\]|';

		$content = preg_replace( $pattern, '$1', $content );

		// Put matches into an array

		// For each member of the array, see if there's a page match

		// If so, return a link to the page

		// otherwise create a link to the edit page, and make the link red

		//var_dump( $content );

		return $content;
	}

}

?>