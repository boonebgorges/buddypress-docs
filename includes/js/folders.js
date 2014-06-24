( function( $ ) {
	var date,
		doc_id,
		hover_element = '',
		hover_time = '',
		$doc_clone,
		$editing_folder,
		$hover_element;

	$( document ).ready( function() {
		// Change associated group, change available folders
		$( '#associated_group_id' ).on( 'change', function() {
			update_folder_selector( $( this ).val() );
		} );

		// Change folder type, change available parents
		$( '.folder-type' ).on( 'change', function() {
			update_parent_folder_selector( $( this ) );
		} );

		// Change folder parent, change/disable folder type
		$( '.folder-parent' ).on( 'change', function() {
			update_folder_type_selector( $( this ) );
		} );

		// Tree view folder accordion
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

		// Manage folder accordions
		$( '.docs-folder-manage li.folder h4 > span > a' ).on( 'click', function( event ) {
			toggle_folder_edit_class( $( this ) );
			return false;
		} );

		init_doc_drag();
	} );

	/**
	 * Update the list of available folders when changing associated groups
	 */
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

	/**
	 * Update the Parent selector when the Type selector is changed.
	 */
	function update_parent_folder_selector( $type_selector ) {
		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'bp_docs_update_parent_folders',
				folder_type: $type_selector.val()
			},
			success: function( response ) {
				$type_selector.siblings( '.folder-parent' ).fadeOut( function() {
					$( this ).replaceWith( response ).fadeIn();
				} );
			}

		} );
	}

	/**
	 * When the Parent selector is updated, update the Type field to match, and disable it.
	 *
	 * This is because the Type must match the type of the parent.
	 */
	function update_folder_type_selector( $parent_selector ) {
		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'bp_docs_update_folder_type',
				parent_id: $parent_selector.val()
			},
			success: function( response ) {
				$parent_selector.siblings( '.folder-type' ).fadeOut( function() {
					$( this ).replaceWith( response ).fadeIn();

					// Disable if a selection was made
					if ( 0 != $parent_selector.val().length ) {
						$parent_selector.siblings( '.folder-type' ).prop( 'disabled', true );
					} else {
						$parent_selector.siblings( '.folder-type' ).prop( 'disabled', false );
					}
				} );
			}

		} );
	}

	/**
	 * Toggle folder-open/folder-closed (tree view)
	 */
	function toggle_folder_class( $target ) {
		if ( $target.hasClass( 'folder-closed' ) ) {
			$target.removeClass( 'folder-closed' ).addClass( 'folder-open' );
		} else {
			$target.removeClass( 'folder-open' ).addClass( 'folder-closed' );
		}
	}

	/**
	 * Toggle folder-edit-closed/folder-edit-open (manage-folders view)
	 *
	 * $target is the clicked link
	 */
	function toggle_folder_edit_class( $target ) {
		$editing_folder = $target.closest( '.folder' );

		if ( $editing_folder.hasClass( 'folder-edit-closed' ) ) {
			$editing_folder.removeClass( 'folder-edit-closed' ).addClass( 'folder-edit-open' );
		} else {
			$editing_folder.removeClass( 'folder-edit-open' ).addClass( 'folder-edit-closed' );
		}
	}

	/**
	 * Set up draggable/droppable for tree view
	 */
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
