/* global wp, tinymce */
/**
 * The table builder's content (F25 R17-R33; Tasks T4-T8; deviation 151): what is added and removed, and every cell's
 * value.
 *
 * - Add Product and Add Group (toolbar or the empty canvas) open their pickers (product-search.js); a choice adds it and
 *   the picker stays open, marking what the table already has "Added".
 * - Add Row / Add Column adds an empty one; Add field (+) and "Click to add a Field" open "Add Field" (field-dialog.js)
 *   on the library of fields not in the table.
 * - Removing a product, a field or a row asks first (Lasso's words; a row's question says its values are kept).
 * - Values: typed boxes save 500 ms after their last change, or at once before any other write (pending); a rating is
 *   kept between 1 and 5 while typing; the image opens the media library; the price opens one rich editor in place;
 *   the description opens "Description Editor". After a save the canvas element is looked up again, as a redraw may
 *   have replaced it.
 */
( function () {
	'use strict';

	var tb = window.affibloomTableBuilder;
	if ( ! tb || ! tb.id ) {
		return;
	}
	var page = tb.page;
	var region = tb.region;
	var path = tb.path;

	// Which product and field an element of the canvas stands for, and that element again after a redraw.
	var cellOf = function ( element ) {
		return {
			product: element.closest( '[data-table-product]' ).getAttribute( 'data-table-product' ),
			field: element.closest( '[data-table-field]' ).dataset.tableField,
		};
	};
	var fieldIn = function ( cell ) {
		return region.querySelector( '[data-table-product="' + cell.product + '"] [data-table-field="' + cell.field + '"]' );
	};
	tb.cellOf = cellOf;
	tb.fieldIn = fieldIn;

	var putValue = function ( cell, value, done ) {
		tb.enqueue( {
			value: true,
			send: function ( current ) {
				return wp.apiFetch( { path: path + '/values/' + cell.product + '/' + cell.field, method: 'PUT', data: { revision: current, value: value } } );
			},
			done: done,
		} );
	};
	tb.putValue = putValue;

	// Typed values: 500 ms after the last change, or at once before another write.
	var waiting = {};
	var sendTyped = function ( key ) {
		var entry = waiting[ key ];
		if ( ! entry ) {
			return;
		}
		delete waiting[ key ];
		window.clearTimeout( entry.timer );
		putValue( entry.cell, entry.value, function ( data ) {
			// The stored value (a rating rounded, empty lines dropped) shows once the box is left.
			var field = fieldIn( entry.cell );
			var input = field && field.querySelector( '[data-table-value]' );
			if ( input && document.activeElement !== input && input.value === entry.value ) {
				input.value = data.value;
			}
		} );
	};
	tb.pending( function () {
		Object.keys( waiting ).forEach( sendTyped );
	} );
	region.addEventListener( 'input', function ( event ) {
		var input = event.target.closest( '[data-table-value]' );
		if ( ! input ) {
			return;
		}
		if ( input.hasAttribute( 'data-table-rating' ) ) {
			if ( parseFloat( input.value ) > 5 ) {
				input.value = '5';
			}
			input.parentNode.querySelector( '[data-table-stars]' ).style.setProperty( '--rating', String( parseFloat( input.value ) || 0 ) );
		}
		var cell = cellOf( input );
		var key = cell.product + '-' + cell.field;
		if ( waiting[ key ] ) {
			window.clearTimeout( waiting[ key ].timer );
		}
		waiting[ key ] = {
			cell: cell,
			value: input.value,
			timer: window.setTimeout( function () {
				sendTyped( key );
			}, 500 ),
		};
	} );

	// The products and groups the table has, for the pickers' "Added".
	var productsIn = function () {
		return Array.prototype.map.call( region.querySelectorAll( '.affibloom-table-cell--head' ), function ( head ) {
			return parseInt( head.getAttribute( 'data-table-product' ), 10 );
		} );
	};

	// Add Product (R17, R18): the picker stays open; a product the table has is "Added".
	var picker = document.getElementById( 'affibloom-table-add-product' );
	var search = picker.querySelector( 'input[type="search"]' );
	var searched = false;
	var finder = window.affibloomProductSearch( {
		modal: picker,
		input: search,
		results: picker.querySelector( '[data-picker-results]' ),
		pages: picker.querySelector( '[data-picker-pages]' ),
		error: picker.querySelector( '[data-picker-error]' ),
		perPage: 6,
		button: function ( item ) {
			var has = -1 !== productsIn().indexOf( item.id );
			return { label: has ? picker.dataset.addedLabel : picker.dataset.chooseLabel, disabled: has };
		},
		choose: function ( item, button ) {
			button.disabled = true;
			tb.enqueue( {
				send: function ( current ) {
					return wp.apiFetch( { path: path + '/products', method: 'POST', data: { revision: current, product: item.id } } );
				},
				done: function () {
					button.textContent = picker.dataset.addedLabel;
					button.setAttribute( 'aria-label', button.textContent + ': ' + item.name );
				},
				failed: function ( text ) {
					button.disabled = false;
					finder.problem( text );
				},
			} );
		},
		// "Search URLs" becomes "Search <n> URLs" once the library's size is known.
		loaded: function ( data, term ) {
			if ( '' === term ) {
				search.setAttribute( 'placeholder', picker.dataset.searchMany.replace( '%s', Number( data.total ).toLocaleString() ) );
			}
		},
	} );
	picker.addEventListener( 'affibloom:open', function () {
		finder.search( ! searched );
		searched = true;
	} );

	// Add Group (R24, D6): every published product of the group not yet in the table.
	var groupPicker = document.getElementById( 'affibloom-table-add-group' );
	var groupSearch = groupPicker.querySelector( 'input[type="search"]' );
	var groupsSearched = false;
	var groupFinder = window.affibloomProductSearch( {
		modal: groupPicker,
		input: groupSearch,
		results: groupPicker.querySelector( '[data-picker-results]' ),
		pages: groupPicker.querySelector( '[data-picker-pages]' ),
		error: groupPicker.querySelector( '[data-picker-error]' ),
		path: '/affibloom/v1/groups',
		perPage: 6,
		picture: false,
		detail: function ( item ) {
			return groupPicker.dataset.links.replace( '%s', Number( item.count ).toLocaleString() );
		},
		button: function () {
			return { label: groupPicker.dataset.chooseLabel, disabled: false };
		},
		choose: function ( item, button ) {
			button.disabled = true;
			tb.enqueue( {
				send: function ( current ) {
					return wp.apiFetch( { path: path + '/products', method: 'POST', data: { revision: current, group: item.id } } );
				},
				done: function () {
					button.textContent = groupPicker.dataset.addedLabel;
					button.setAttribute( 'aria-label', button.textContent + ': ' + item.name );
				},
				failed: function ( text ) {
					button.disabled = false;
					groupFinder.problem( text );
				},
			} );
		},
		loaded: function ( data, term ) {
			if ( '' === term ) {
				groupSearch.setAttribute( 'placeholder', groupPicker.dataset.searchMany.replace( '%s', Number( data.total ).toLocaleString() ) );
			}
		},
	} );
	groupPicker.addEventListener( 'affibloom:open', function () {
		groupFinder.search( ! groupsSearched );
		groupsSearched = true;
	} );

	// Add Field (R26-R28): the library of fields the table does not have.
	var fieldModal = document.getElementById( 'affibloom-field-modal' );
	var fieldGroup = '';
	var fieldsInTable = function () {
		var ids = [];
		region.querySelectorAll( '[data-table-field]' ).forEach( function ( field ) {
			if ( -1 === ids.indexOf( field.dataset.tableField ) ) {
				ids.push( field.dataset.tableField );
			}
		} );
		return ids;
	};
	window.affibloomFieldDialog( {
		modal: fieldModal,
		start: 'library',
		findCreated: true,
		query: function () {
			return { for: 'table', per_page: 6, exclude: fieldsInTable() };
		},
		add: function ( field, button ) {
			tb.enqueue( {
				send: function ( current ) {
					return wp.apiFetch( { path: path + '/groups/' + encodeURIComponent( fieldGroup ) + '/fields', method: 'POST', data: { revision: current, field: field } } );
				},
				done: function () {
					window.affibloomModal.hide();
				},
				failed: function ( text ) {
					button.disabled = false;
					window.affibloomToast( text, true );
				},
			} );
		},
	} );
	tb.addField = function ( group, opener ) {
		fieldGroup = group;
		window.affibloomModal.show( fieldModal, opener );
	};

	// The removals: each asks, then sends; the dialog closes when the canvas is drawn again.
	var confirmer = function ( modalId, attr, send ) {
		var modal = document.getElementById( modalId );
		var confirm = modal.querySelector( '[' + attr + ']' );
		var chosen = null;
		confirm.addEventListener( 'click', function () {
			var what = chosen;
			var label = confirm.textContent;
			confirm.disabled = true;
			confirm.textContent = confirm.getAttribute( 'data-busy-label' );
			var restore = function () {
				confirm.disabled = false;
				confirm.textContent = label;
				window.affibloomModal.hide();
			};
			tb.enqueue( {
				send: function ( current ) {
					return send( what, current );
				},
				done: restore,
				failed: function ( text ) {
					restore();
					tb.say( text );
				},
			} );
		} );
		return {
			ask: function ( what, opener, title ) {
				chosen = what;
				if ( title ) {
					modal.querySelector( '.affibloom-modal__title' ).textContent = title;
				}
				window.affibloomModal.show( modal, opener );
			},
			modal: modal,
		};
	};
	var removeProduct = confirmer( 'affibloom-table-remove-product', 'data-table-remove-confirm', function ( product, current ) {
		return wp.apiFetch( { path: path + '/products/' + product + '?revision=' + current, method: 'DELETE' } );
	} );
	var removeField = confirmer( 'affibloom-table-remove-field', 'data-table-remove-field-confirm', function ( what, current ) {
		return wp.apiFetch( { path: path + '/groups/' + encodeURIComponent( what.group ) + '/fields/' + what.field + '?revision=' + current, method: 'DELETE' } );
	} );
	var removeRow = confirmer( 'affibloom-table-remove-row', 'data-table-remove-row-confirm', function ( group, current ) {
		return wp.apiFetch( { path: path + '/groups/' + encodeURIComponent( group ) + '?revision=' + current, method: 'DELETE' } );
	} );
	tb.removeProduct = function ( product, opener ) {
		var head = region.querySelector( '.affibloom-table-cell--head[data-table-product="' + product + '"]' );
		removeProduct.ask( product, opener, removeProduct.modal.dataset.titleTemplate.replace( '%s', head ? head.getAttribute( 'data-product-name' ) : '' ) );
	};
	tb.removeField = function ( group, field, name, opener ) {
		removeField.ask( { group: group, field: field }, opener, removeField.modal.dataset.titleTemplate.replace( '%s', name ) );
	};
	tb.removeRow = function ( group, opener ) {
		var across = 'row' === page.getAttribute( 'data-table-style' );
		removeRow.ask( group, opener, removeRow.modal.getAttribute( across ? 'data-column-title' : 'data-row-title' ) );
	};

	// An image (R29): the media library, saved at once.
	var pickImage = function ( button ) {
		var cell = cellOf( button );
		window.affibloomMedia.pick( { title: page.dataset.mediaTitle, button: page.dataset.mediaButton }, function ( picked ) {
			putValue( cell, picked.url, function ( data ) {
				var field = fieldIn( cell );
				var target = field && field.querySelector( '[data-table-image]' );
				if ( ! target ) {
					return;
				}
				var image = target.querySelector( 'img' );
				if ( ! image ) {
					image = document.createElement( 'img' );
					image.className = 'affibloom-table-field__image';
					image.alt = '';
					image.draggable = false;
					target.replaceChild( image, target.querySelector( '.affibloom-table-field__no-image' ) );
				}
				image.src = data.value;
			} );
		} );
	};

	// A price (R29, D10): one rich editor at a time, in the cell being edited; closed (and saved) before another write.
	var priceSettings = JSON.parse( page.dataset.priceEditor || '{}' );
	var priceEditor = null;
	var closePrice = function () {
		if ( ! priceEditor ) {
			return;
		}
		var open = priceEditor;
		priceEditor = null;
		window.clearTimeout( open.timer );
		var editor = tinymce.get( open.id );
		var value = editor ? editor.getContent() : open.saved;
		wp.editor.remove( open.id );
		open.box.innerHTML = value;
		if ( open.holder.isConnected ) {
			open.holder.replaceWith( open.box );
		}
		if ( value !== open.sent ) {
			putValue( open.cell, value, function ( data ) {
				var field = fieldIn( open.cell );
				var box = field && field.querySelector( '[data-table-price]' );
				if ( box ) {
					box.innerHTML = data.value;
				}
			} );
		}
	};
	tb.pending( closePrice );
	var editPrice = function ( box ) {
		closePrice();
		var id = 'affibloom-table-price-editor';
		var holder = document.createElement( 'div' );
		holder.className = 'affibloom-editor affibloom-table-field__price-editor';
		var area = document.createElement( 'textarea' );
		area.id = id;
		area.value = box.innerHTML;
		holder.appendChild( area );
		box.replaceWith( holder );
		var open = { id: id, box: box, holder: holder, cell: cellOf( holder ), saved: box.innerHTML, sent: box.innerHTML, timer: 0 };
		priceEditor = open;
		wp.editor.initialize( id, {
			quicktags: false,
			mediaButtons: false,
			tinymce: Object.assign( {}, priceSettings, {
				setup: function ( editor ) {
					window.affibloomEditorPlaceholder( editor );
					editor.on( 'init', function () {
						editor.focus();
					} );
					editor.on( 'input change keyup', function () {
						window.clearTimeout( open.timer );
						open.timer = window.setTimeout( function () {
							var value = editor.getContent();
							if ( priceEditor === open && value !== open.sent ) {
								open.sent = value;
								putValue( open.cell, value );
							}
						}, 500 );
					} );
					editor.on( 'blur', function () {
						window.setTimeout( function () {
							if ( priceEditor === open && ! holder.contains( document.activeElement ) ) {
								closePrice();
							}
						}, 150 );
					} );
				},
			} ),
		} );
	};

	// "Description Editor" (R32): Save stores the cell and draws it where the cell is now.
	var describer = document.getElementById( 'affibloom-table-description' );
	var describeSave = describer.querySelector( '[data-table-description-save]' );
	var describeError = describer.querySelector( '[data-table-description-error]' );
	var describing = null;
	var describe = function ( opener ) {
		describing = cellOf( opener );
		var editor = tinymce.get( 'affibloom_table_description' );
		if ( editor ) {
			editor.setContent( opener.parentNode.querySelector( '[data-table-rich]' ).innerHTML );
		}
		describeError.hidden = true;
		window.affibloomModal.show( describer, opener );
	};
	describeSave.addEventListener( 'click', function () {
		var editor = tinymce.get( 'affibloom_table_description' );
		var cell = describing;
		var label = describeSave.textContent;
		describeSave.disabled = true;
		describeSave.textContent = describeSave.getAttribute( 'data-busy-label' );
		describeError.hidden = true;
		var restore = function () {
			describeSave.disabled = false;
			describeSave.textContent = label;
		};
		tb.enqueue( {
			value: true,
			send: function ( current ) {
				return wp.apiFetch( { path: path + '/values/' + cell.product + '/' + cell.field, method: 'PUT', data: { revision: current, value: editor ? editor.getContent() : '' } } );
			},
			done: function ( data ) {
				restore();
				var field = fieldIn( cell );
				var rich = field && field.querySelector( '[data-table-rich]' );
				if ( rich ) {
					rich.innerHTML = data.value;
				}
				window.affibloomModal.hide();
			},
			failed: function ( text ) {
				restore();
				describeError.textContent = text;
				describeError.hidden = false;
			},
		} );
	} );

	// The page's buttons (the toolbar and the canvas share them).
	page.addEventListener( 'click', function ( event ) {
		var target = event.target;
		if ( target.closest( '[data-table-action="add-product"]' ) ) {
			window.affibloomModal.show( picker, target.closest( 'button' ) );
			return;
		}
		if ( target.closest( '[data-table-action="add-group"]' ) ) {
			window.affibloomModal.show( groupPicker, target.closest( 'button' ) );
			return;
		}
		if ( target.closest( '[data-table-action="add-line"]' ) ) {
			tb.enqueue( {
				send: function ( current ) {
					return wp.apiFetch( { path: path + '/groups', method: 'POST', data: { revision: current } } );
				},
			} );
			return;
		}
		if ( ! region.contains( target ) ) {
			return;
		}
		var add = target.closest( '[data-table-add-field]' );
		if ( add ) {
			tb.addField( add.closest( '[data-table-group]' ).getAttribute( 'data-table-group' ), add );
			return;
		}
		var drop = target.closest( '[data-table-remove-field]' );
		if ( drop ) {
			var holder = drop.closest( '[data-table-field]' );
			tb.removeField( drop.closest( '[data-table-group]' ).getAttribute( 'data-table-group' ), holder.dataset.tableField, holder.dataset.fieldName, drop );
			return;
		}
		var row = target.closest( '[data-table-remove-row]' );
		if ( row ) {
			tb.removeRow( row.closest( '[data-table-group]' ).getAttribute( 'data-table-group' ), row );
			return;
		}
		var remove = target.closest( '[data-table-remove]' );
		if ( remove ) {
			tb.removeProduct( remove.closest( '[data-table-product]' ).getAttribute( 'data-table-product' ), remove );
			return;
		}
		var image = target.closest( '[data-table-image]' );
		if ( image ) {
			pickImage( image );
			return;
		}
		var price = target.closest( '[data-table-price]' );
		if ( price ) {
			editPrice( price );
			return;
		}
		var description = target.closest( '[data-table-description]' );
		if ( description ) {
			describe( description );
		}
	} );
	// A price box opens on Enter or Space too.
	region.addEventListener( 'keydown', function ( event ) {
		var price = event.target.closest( '[data-table-price]' );
		if ( price && ( 'Enter' === event.key || ' ' === event.key ) ) {
			event.preventDefault();
			editPrice( price );
		}
	} );
}() );
