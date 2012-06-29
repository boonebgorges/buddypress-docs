<?php

class BP_Docs_Attachments {
	
	function __construct() {
		global $bp;
	
		// Hook into post saves to save any taxonomy terms. 
		add_action( 'bp_docs_doc_saved', array( $this, 'save_post' ) );
		add_action('bp_docs_loop_additional_th',array($this,'add_loop_th'));
		add_action('bp_docs_loop_additional_td',array($this,'add_loop_td')); 
		add_action('bp_docs_header_tabs',array($this,'add_print_doc_tab'));
		$bp->bp_docs->history =& $this;
	}
	
	function save_post($query){
		/*
		 * vardump and query in buddypress-docs/includes/addon-attachments.php 18object(BP_Docs_Query)#263 (12) { ["post_type_name"]=> string(6) "bp_doc" ["associated_item_tax_name"]=> string(23) "bp_docs_associated_item" ["item_type"]=> string(5) "group" ["item_id"]=> string(2) "23" ["item_name"]=> string(24) "Thenumber Public Library" ["item_slug"]=> string(19) "libraries/thenumber" ["doc_id"]=> int(239) ["doc_slug"]=> string(8) "testdoc1" ["current_view"]=> string(4) "edit" ["term_id"]=> string(2) "25" ["item_type_term_id"]=> string(1) "5" ["is_new_doc"]=> bool(false) } 
		 */
		/*echo "vardump and query in buddypress-docs/includes/addon-attachments.php 18";
		var_dump($query);
		ECHO "<br>REQ:";
		var_dump($_REQUEST);
		ECHO "<br>FILES:";
		var_dump($_FILES);
		phpinfo();
		die;*/
		if(!empty($_FILES)){
			/*echo "handle file upload in bp-docs/../atacchemnts, attach to post {$query->doc_id}<br>";
			var_dump($_FILES);*/
			require_once('wp-admin/includes/file.php');
			require_once('wp-admin/includes/media.php');
			require_once('wp-admin/includes/image.php');
			foreach($_FILES as $file_id=>$file){
				$uploaded_attachment_id=media_handle_upload($file_id,$query->doc_id);
			}
			/*echo "uploaded attachment id: $uploaded_attachment_id";
			echo "and die";
			die;*/
		}
		
		$attachments=get_children(array('post_parent'=>$query->doc_id,'post_type'=>'attachment'));
		/*echo "<br>attachments:";
		var_dump($attachments);
		echo "<br>";
		var_dump($_REQUEST);
		die;*/
		
		require_once('wp-includes/post.php');
		foreach($attachments as $attachment){
			if(isset($_REQUEST['bp-docs-existing-attachment-'.$attachment->ID])){
				wp_trash_post($attachment->ID);
				//echo "delete post ".$attachment->ID;
			}
		}
		
	}
	
	function add_loop_th(){
		echo "<th>Attachments</th>";
	}
	function add_loop_td(){
		global $post;
		$attachments=get_children(array('post_parent'=>$post->ID,'post_type'=>'attachment'));
		echo "<td class='date-cell edited-date-cell'>".count($attachments)."</td>";
	}
	function add_print_doc_tab(){
		echo "<li><a onClick='window.print();return false;' href>Print Document</a></li>";
	}
}