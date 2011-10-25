<?php

/**
 * Table of Contents functionality
 *
 * Based on WP Table of Contents http://wordpress.org/extend/plugins/wp-table-of-contents/
 * Thanks, ahmet-kaya!
 *
 * @package BuddyPress_Docs
 * @since 1.2
 */


class BP_Docs_TOC {
	var $toc_markup;
	var $toc_array;
	
	/**
	 * Constructor class
	 * 
	 * @package BuddyPress_Docs
	 * @since 1.2
	 */
	function __construct() {
		add_filter( 'the_content', array( $this, 'generate_toc' ), 1 );
		add_filter( 'the_content', array( $this, 'prepend_toc' ) );
	}
	
	function generate_toc( $content, $i = 1 ) {		
		$x 		= 1;
		
		$this->recurse_toc( $content, $i );
		
		return $content;
	}
	
	function recurse_toc( $content, $i ) {
		
		$tag 		= 'h' . $i;
		$tag_key  	= 'h' . $i . '_content';
		
		if ( $this->toc_array[$tag] = wp_icindekiler_bol( $content, $tag ) ) {
			$this->toc_markup .= '<ol>';
			foreach ( $this->toc_array[$tag]["1"] as $baslik ) {
				$this->toc_markup .= "<li><a href=\"#".wp_duzenle($baslik);
				$baslik = htmlentities(trim(strip_tags($baslik)), ENT_QUOTES, "UTF-8");
				
				$this->toc_markup .= "\" title=\"$baslik\"><small>$baslik</small></a>\n";
				
				$this->toc_array[$tag_key][$j] = wp_icindekiler_icerik($content, $tag);
				
				$this->generate_toc($this->toc_array[$tag_key][$x], $x + 1);
				
				$this->toc_markup .= '</li>';
				
				$j++;
				$x++;
			}
			$this->toc_markup .= '</ol>';
		}
		
	}
	
	function prepend_toc( $content ) {
		return $this->toc_markup . $content;
	}


}

function wp_duzenle ( $content ) {
	return sanitize_title_with_dashes($content);
}
function wp_icindekiler_bol( $content, $tag ) {
	if ( substr_count( $content, $tag ) < 1 ) 
		return false; 
		
	$regex = '/<' . $tag . '.*>(.*)<\/' . $tag . '>/Us';
	preg_match_all( $regex, $content, $matches );
	
	return $matches;
}

function wp_icindekiler_icerik ( $content, $tag ) {
	$regex = '/<' . $tag . '.*>(.*)<\/' . $tag . '>/Us';
	return preg_split ( $regex, $content );
}

/**
 * Displays a Doc's TOC
 *
 * @package BuddyPress_Docs
 * @since 1.2
 */
function bp_docs_table_of_contents() {
	
	wp_icindekiler_ ( get_the_content(), 2 );
	if ( $output != '' ) {
		$output = "
			<div class=\"wp_table_of_contents\" id=\"wp_icindekiler\">
			<strong>Contents</strong> [<a class=\"goster_gizle\" onMouseOver=\"style.cursor='pointer'\" title=\"Show contents &amp; hide\"><small>show/hide</small></a>]
			<div class=\"wp_icindekiler_icerik\">$output</div></div>
		";
	}
	echo $output;
}

?>