( function( $ ) {
	$( document ).ready( function() {
		$( '#associated_group_id' ).on( 'change', function() {
			update_folder_selector( $( this ).val() );
		} );

		$( '#new-folder-type' ).on( 'change', function() {
			update_parent_folder_selector( $( this ).val() );
		} );

		$( '.docs-folder-tree li' ).on( 'click', function() {
			toggle_folder_class( this );
			return false;
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

	function toggle_folder_class( target ) {
		if ( $( target ).hasClass( 'folder-closed' ) ) {
			$( target ).removeClass( 'folder-closed' ).addClass( 'folder-open' );
		} else {
			$( target ).removeClass( 'folder-open' ).addClass( 'folder-closed' );
		}
	}
} )( jQuery )
