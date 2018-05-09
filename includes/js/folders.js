( function( $ ) {
	var date,
		doc_id,
		current_folder,
		move_to_folder,
		folder_tab_name = '',
		hover_element = '',
		hover_time = '',
		$associated_group_selector,
		$doc_clone,
		$doctable,
		$editing_folder,
		hover_element,
		fetching_folder_contents = false;

	$( document ).ready( function() {
		// Show/hide new folder details
		update_create_new_folder_details_visibility( $( 'input[name=existing-or-new-folder]' ) );
		$( 'input[name=existing-or-new-folder]' ).on( 'change', function() {
			update_create_new_folder_details_visibility( $( this ) );
		} );

		// Initialize whether the Folders section should be displayed.
		update_folder_metabox_display();

		// Change associated group, change available folders
		$associated_group_selector = $( '#associated_group_id' );
		/*
		 * Refresh the type selector only when $associated_group_selector.val() is defined.
		 * This prevents the refresh from running and failing on the "manage folders" screen.
		 */
		if ( typeof $associated_group_selector.val() !== 'undefined' && $associated_group_selector.val().length ) {
			update_new_folder_selectors_for_group( $associated_group_selector.val() );
		}
		update_folder_tab_title();
		$associated_group_selector.on( 'change', function() {
			update_folder_metabox_display();
			update_folder_selector( $( this ).val() );
			update_new_folder_selectors_for_group( $( this ).val() );

			update_folder_tab_title();
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

		// Hide folders on list view.
		$doctable = $( 'table.doctable' );
		$( '.toggle-folders' ).on( 'click', function( e ) {
			e.preventDefault();
			toggle_folder_list();
		} );

		init_doc_drag();

		if ( $( '.folder-row-name' ).length > 0 ) {
			set_folder_related_colspans();
			$( window ).resize( function() {
				set_folder_related_colspans();
			} );
		}

		/*
		 * Expand folders to show contents on click.
		 * Contents are fetched via an AJAX request.
		 */
		$( '.doctable' ).on( 'click', '.toggle-folder', function( e ) {
			e.preventDefault();
			maybe_populate_folder_contents( $( this ) );

			// Closely duplicate the normal bp-docs toggle-link behavior.
			var $toggleable = $( this ).closest( '.toggleable' ),
				$state_icon = $( this ).find('.genericon-collapse, .genericon-expand').first();

			if ( $toggleable.hasClass( 'toggle-open' ) ) {
				$toggleable.removeClass( 'toggle-open' ).addClass( 'toggle-closed' );
				$state_icon.removeClass( 'genericon-collapse' ).addClass( 'genericon-expand' );
			} else {
				$toggleable.removeClass( 'toggle-closed' ).addClass( 'toggle-open' );
				$state_icon.removeClass( 'genericon-expand' ).addClass( 'genericon-collapse' );
			}

		} );
	} );

	/**
	 * Folder functionality only applies to groups, so only display meta box
	 * if a group is selected.
	 */
	function update_folder_metabox_display() {
		if ( BP_Docs_Folders.force_metabox || ( $( '#associated_group_id' ).length && $( '#associated_group_id' ).val().length ) ) {
			$( '#doc-folders' ).show();
		} else {
			$( '#doc-folders' ).hide();
		}
	}

	/**
	 * Toggle the visibility of the new folder details fields based on radio button selection.
	 */
	function update_create_new_folder_details_visibility( $radio ) {
		if ( 'new' == $( 'input[name=existing-or-new-folder]:checked' ).val() ) {
			$( '#new-folder-block .selector-content' ).show();
		} else {
			$( '#new-folder-block .selector-content' ).hide();
		}
	}

	/**
	 * Update the list of available folders when changing associated groups
	 */
	function update_folder_selector( group_id ) {
		if ( $( '#existing-doc-id' ).length ) {
			doc_id = $( '#existing-doc-id' ).val();
		} else {
			doc_id = 0;
		}

		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: {
				doc_id: doc_id,
				action: 'bp_docs_update_folders',
				group_id: group_id
			},
			success: function( response ) {
				$( '#bp-docs-folder' ).replaceWith( response );
			}

		} );
	}

	/**
	 * Update the 'Folders' label for the Folders section when the Associated Group setting is changed
	 */
	function update_folder_tab_title() {
		if ( ! $associated_group_selector.length ) {
			return;
		}

		if ( $associated_group_selector.val().length ) {
			folder_tab_name = BP_Docs_Folders.folders_tab_label_groups;
		} else {
			folder_tab_name = BP_Docs_Folders.folders_tab_label;
		}

		$( '#doc-folders .toggle-title' ).html( folder_tab_name );
	}

	/**
	 * Update the list of folders and corresponding folder type when changing associated groups.
	 */
	function update_new_folder_selectors_for_group( group_id ) {
		if ( '' == group_id ) {
			group_id = 'global';
		}

		// Refresh the available types
		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'bp_docs_update_folder_type_for_group',
				group_id: group_id
			},
			success: function( response ) {
				$( '#new-folder-type' ).fadeOut();
				$( '#new-folder-type' ).replaceWith( response ).fadeIn();
				update_parent_folder_selector( group_id );
			}
		} );

	}

	/**
	 * Update the Parent selector when the Type selector is changed.
	 */
	function update_parent_folder_selector( $type_selector ) {
		var folder_type, $folder_parent;

		// Gah.
		if ( 'undefined' == typeof $type_selector ) {
			return;
		} else if ( 'string' == typeof $type_selector ) {
			folder_type = $type_selector;
			$folder_parent = $( '.folder-parent' );
		} else {
			folder_type = $type_selector.val();
			$folder_parent = $type_selector.siblings( '.folder-parent' );
		}

		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'bp_docs_update_parent_folders',
				folder_type: folder_type
			},
			success: function( response ) {
				$folder_parent.fadeOut( function() {
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
				parent_id: $parent_selector.val(),
				group_id: $( '#associated_group_id' ).val(),
				type_selector_name: $parent_selector.siblings( '.folder-type' ).attr( 'name' )
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
	 * Set up draggable/droppable
	 */
	function init_doc_drag() {
		// Add in-folder data attribute to each doc.
		$( ".doctable" ).each( function(i,e){
			var in_folder = $( this ).data( "folder-id" );
			$( this ).find("> tbody > .doc-in-folder").data( "in-folder", in_folder );
		} );

		$( '.doc-in-folder' ).draggable({
			helper: 'clone',
			revert: 'invalid',
			start: function( event, ui ) {
				$( event.target ).addClass( "draggable-in-flux" );
				$( '.failed-drop' ).removeClass( 'failed-drop' );
				$( '.successful-drop' ).removeClass( 'successful-drop' );
			},
			stop: function( event, ui ) {
				$( event.target ).removeClass( "draggable-in-flux" );
			}
		});

		// Code for table view
		$( '.doctable' ).droppable( {
		    accept: ".doc-in-folder",
			drop: function( event, ui ) {
				doc_id = ui.draggable.data( "doc-id" );
				current_folder = ui.draggable.data( "in-folder" );
				move_to_folder = $(event.target).data( "folder-id" );

				// Drops to the same folder don't require the API call.
				if ( move_to_folder === current_folder ) {
					process_doc_drop_table_failure( event, ui )
					return false;
				} else {
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
							if ( response.success ) {
								process_doc_drop_table( event, ui );
							} else {
								process_doc_drop_table_failure( event, ui )
							}
						},
						error: function( response ) {
							process_doc_drop_table_failure( event, ui )
						}
					} );
				}
			},
			greedy: true, // Don't bubble up in nested folder lists
			over: function( event, ui ) {
				$( event.target ).addClass( 'hover' );
			},
			out: function( event, ui ) {
				$( event.target ).removeClass( 'hover' );
			}

		} );

		// Code for tree view
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

	function toggle_folder_list() {
		if ( ! $doctable ) {
			return;
		}

		if ( $( '#toggle-folders-hide' ).is( ':visible' ) ) {
			$doctable.find( 'tr.folder-row' ).hide();
			$( '#toggle-folders-hide' ).hide();
			$( '#toggle-folders-show' ).show();
		} else {
			$doctable.find( 'tr.folder-row' ).show();
			$( '#toggle-folders-hide' ).show();
			$( '#toggle-folders-show' ).hide();
		}
	}

	function process_doc_drop_table( event, ui ) {
		// Provide visual feedback that the drop was successful.
		$( event.target ).addClass( "successful-drop" );

		// Create a clone of original for appending
		$doc_clone = ui.draggable.clone();

		// Remove the inline positioning styles
		$doc_clone.removeAttr( 'style' );
		$doc_clone.css( "position", "relative" );

		// Add to the new folder list
		var folder_id = $( event.target ).data("folder-id");

		// Some cases:
		// for subfolders that have the meta-info row
		// another for "no results" tables or the top-level folder
		if ( $( event.target ).find(" > tbody > tr.folder-meta-info").length ) {
			$( event.target ).find(" > tbody > tr.folder-meta-info").before( $doc_clone );
		} else {
			$( event.target ).find(" > tbody > tr.no-docs-row").before( $doc_clone );
		}

	    $doc_clone.data( 'in-folder', folder_id );

		// Remove the original
		ui.draggable.remove();

		// Remove all hover classes, just in case
		$( '.hover' ).removeClass( 'hover' );

		// Update the "no docs" message in each table
		$( ".doctable" ).each( function() {
			var has_contents = $( this ).find(" > tbody > tr.folder-row, > tbody > tr.doc-in-folder").length ? true : false;
			$( this ).find(" > tbody > tr.no-docs-row").toggleClass( "hide", has_contents );
		});

		// Reinit draggables
		init_doc_drag();
	}

	function process_doc_drop_table_failure( event, ui ) {
		// Provide visual feedback that the drop failed.
		$( event.target ).addClass( "failed-drop" );

		// Remove the inline positioning styles
		ui.draggable.removeAttr( 'style' );
		ui.draggable.css( "position", "relative" );

		// Remove all hover classes, just in case
		$( '.hover' ).removeClass( 'hover' );
	}

	function process_doc_drop( event, ui ) {
		// Create a clone of original for appending
		$doc_clone = ui.draggable.clone();

		// Remove the inline positioning styles
		$doc_clone.removeAttr( 'style' );

		// Add to the new folder list
		$( event.target ).children( '.docs-in-folder' ).append( $doc_clone ).removeClass( 'empty' );

		// If this was the last item in the source list, add the empty class
		if ( 0 == ui.draggable.siblings( '.doc-in-folder' ).length ) {
			ui.draggable.closest( '.docs-in-folder' ).addClass( 'empty' );
		}

		// Remove the original
		ui.draggable.remove();

		// Remove all hover classes, just in case
		$( '.hover' ).removeClass( 'hover' );

		// Reinit draggables
		init_doc_drag();
	}

	/**
	 * Set the folder name and folder meta info colspans to the number of available visible cells.
	 */
	function set_folder_related_colspans() {
		var colcount = 0;

		$doctable.find( 'tr > th' ).each( function( k, v ) {
			var $v = $( v );
			if ( ! $v.hasClass( 'attachment-clip-cell' ) && $v.is( ':visible' ) ) {
				colcount++;
			}
		} );

		// Set the colspan of the toggle and of the folder meta footer.
		$( '.folder-row-name, .folder-meta-info-statement' ).attr( 'colspan', colcount );
	}

	/**
	 * Fetch the first set of results in a folder,
	 * if the folder isn't already populated.
	 */
	function maybe_populate_folder_contents( anchor ) {
		var container = $( anchor ).closest( '.toggleable' ).find( '.toggle-content.folder-loop' ).first();

		// If the folder content has already been populated, do nothing.
		if ( $.trim( container.text() ).length ) {
			return;
		}

		// Do not continue if we are currently fetching a set of results.
		if ( fetching_folder_contents !== false ) {
			return;
		}
		fetching_folder_contents = true;
		container.addClass( 'loading' );

		// Make the AJAX request and populate the list.
		$.ajax( {
			url: ajaxurl,
			type: 'GET',
			data: {
				folder: $( anchor ).data( 'folder-id' ),
				group_id: $( '#directory-group-id' ).val(),
				user_id: $( '#directory-user-id' ).val(),
				action: 'bp_docs_get_folder_content',
			},
			success: function( response ) {
				$( container ).html( response );
				set_folder_related_colspans();
				fetching_folder_contents = false;
				container.removeClass( 'loading' );
				// Reinitialize the doc draggable interface.
				init_doc_drag();
			}

		} );

	}
} )( jQuery )
