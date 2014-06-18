( function( $ ) {
	$( document ).ready( function() {
		$( '#associated_group_id' ).on( 'change', function() {
			update_folder_selector( $( this ).val() );
		} );

		$( '#new-folder-type' ).on( 'change', function() {
			update_parent_folder_selector( $( this ).val() );
		} );
	} );

	function update_folder_selector( group_id ) {
		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: {
				doc_id: $( '#existing-doc-id' ).val(),
				action: 'bp_docs_update_folders',
				group_id: group_id
			},
			success: function( response ) {
				$( '#bp-docs-folder' ).replaceWith( response );
			}

		} );
	}

	function update_parent_folder_selector( folder_type ) {
		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'bp_docs_update_parent_folders',
				folder_type: folder_type
			},
			success: function( response ) {
				$( '#new-folder-parent' ).replaceWith( response );
			}

		} );
	}
} )( jQuery )
