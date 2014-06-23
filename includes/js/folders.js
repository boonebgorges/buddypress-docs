( function( $ ) {
	var date,
		doc_id,
		hover_element = '',
		hover_time = '',
		$doc_clone,
		$hover_element;
	$( document ).ready( function() {
		$( '#associated_group_id' ).on( 'change', function() {
			update_folder_selector( $( this ).val() );
		} );

		$( '#new-folder-type' ).on( 'change', function() {
			update_parent_folder_selector( $( this ).val() );
		} );

		$( '.docs-folder-tree li' ).on( 'click', function( event ) {
			if ( ! $( this ).hasClass( 'doc-in-folder' ) ) {
				toggle_folder_class( $( this ) );
				return false;
			} else {
				// Don't let the click bubble up
				event.stopPropagation();
				return true;
			}
		} );

		init_doc_drag();
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

	function toggle_folder_class( $target ) {
		if ( $target.hasClass( 'folder-closed' ) ) {
			$target.removeClass( 'folder-closed' ).addClass( 'folder-open' );
		} else {
			$target.removeClass( 'folder-open' ).addClass( 'folder-closed' );
		}
	}

	function init_doc_drag() {
		$( '.doc-in-folder' ).draggable( {
			revert: 'invalid'
		} );

		$( '.docs-folder-tree li.folder' ).droppable( {
			accept: '.doc-in-folder',
			drop: function( event, ui ) {
				doc_id = ui.draggable.data( 'doc-id' );
				$.ajax( {
					url: ajaxurl,
					type: 'POST',
					data: {
						doc_id: doc_id,
						folder_id: $( event.target ).data( 'folder-id' ),
						action: 'bp_docs_process_folder_drop',
						nonce: ui.draggable.find( '#bp-docs-folder-drop-nonce-' + doc_id ).val()
					},
					success: function( response ) {
						if ( 1 == response ) {
							process_doc_drop( event, ui );
						} else {
							ui.draggable.removeAttr( 'style' );
						}
					}
				} );
			},
			greedy: true, // Don't bubble up in nested folder lists
			over: function( event, ui ) {
				$( event.target ).addClass( 'hover' );

				// When hovering on closed folder for 1.5 seconds, open it
				date = new Date();
				hover_time = date.getTime();
				hover_element = event.target;

				setTimeout( function() {
					if ( '' !== hover_time && '' !== hover_element ) {
						$hover_element = $( hover_element );

						if ( $hover_element.hasClass( 'folder' ) && $hover_element.hasClass( 'folder-closed' ) ) {
							toggle_folder_class( $hover_element );
							hover_time = '';
							hover_element = '';
							$( '.hover' ).removeClass( 'hover' );
						}
					}
				}, 1500 );
			},
			out: function( event, ui ) {
				$( event.target ).removeClass( 'hover' );
				if ( event.target == hover_element ) {
					hover_time = '';
					hover_element = '';
				}
			}
		} );

	}

	function process_doc_drop( event, ui ) {
		// Create a clone of original for appending
		$doc_clone = ui.draggable.clone();

		// Remove the inline positioning styles
		$doc_clone.removeAttr( 'style' );

		// Add to the new folder list
		$( event.target ).children( '.docs-in-folder' ).append( $doc_clone );

		// Remove the original
		ui.draggable.remove();

		// Remove all hover classes, just in case
		$( '.hover' ).removeClass( 'hover' );

		// Reinit draggables
		init_doc_drag();
	}
} )( jQuery )
