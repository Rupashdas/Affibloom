/* global wp, tinymce */
/**
 * A table's builder (F25 R13-R23, D4, D5; Tasks T3-T4; Lasso's table-details.js and lasso-tables.js): every action
 * saves as it happens, as Lasso's builder does.
 *
 * - One queue: a write waits for the one before it and carries the table's revision; the head says Saving, then Saved.
 *   Each answer moves the revision on and, when it carries `html`, redraws the builder (D5). A refusal stops the queue
 *   and shows the server's words.
 * - Settings (T3): the heading follows the name while typing; the name saves when it changes and is not empty; Style,
 *   Theme and the two switches save at once (changes made while a write is out go together in the next). A table with
 *   no id yet is created by naming it, then the page moves to its builder.
 * - Products (T4): Add Product opens "Add Product to Table" (product-search.js; searched on first opening only); a
 *   choice adds the product and closes the dialog, a refusal stays in it. A badge saves on leaving it. Remove asks
 *   'Remove "<name>"?' and takes the product out.
 * - Groups (T5): Add Group opens "Add group to Table" (the same search over GET /groups); a choice adds the group's
 *   products not yet in the table.
 * - Rows, columns and fields (T6): Add Row / Add Column adds an empty one; Add field (+) and "Click to add a Field"
 *   open "Add Field" (field-dialog.js) on the library of fields not in the table, six a page, and add the pick to that
 *   row or column for every product. A field's × asks 'Remove "<field>"?'; Vertical's "Remove same field →" takes the
 *   whole row out. A value saves 500 ms after it changes (a rating is kept between 1 and 5 while typing).
 * - Images, prices, descriptions (T7): an image opens the media library and saves the picked address at once; a price
 *   opens one rich editor in place (wp.editor, Lasso's price toolbar), saved 500 ms after a change and on leaving it;
 *   a description's edit icon opens "Description Editor" and Save stores it.
 * - Buttons (T8): the edit icon opens 'Edit "<field>"' (the Primary's address read only); Save stores the text and
 *   the Secondary's address and draws the builder again; a missing one gets Lasso's sentence under its box.
 * - Order (T9; D9): a product, a row or column and a field are dragged by their grips (pointer events: the item under
 *   the pointer is marked, the drop sends the new order) or moved one place by their Move buttons; a field also moves
 *   to the previous or next row (column), or is dropped on another one. Each move redraws the builder.
 */
( function () {
	'use strict';

	var page = document.querySelector( '[data-table-builder]' );
	if ( ! page ) {
		return;
	}
	var form = page.querySelector( '[data-table-settings]' );
	var name = form.querySelector( '[name="title"]' );
	var style = form.querySelector( '[name="style"]' );
	var headersRow = form.querySelector( '[data-table-headers-row]' );
	var heading = document.getElementById( 'affibloom-table-heading' );
	var status = page.querySelector( '[data-table-status]' );
	var error = page.querySelector( '[data-table-error]' );
	var region = page.querySelector( '[data-table-builder-region]' );
	var id = parseInt( page.getAttribute( 'data-table-id' ), 10 ) || 0;
	var path = '/affibloom/v1/tables/' + id;
	var jobs = [];
	var busy = false;
	var settings = null;
	var saved = name.value;

	var say = function ( text ) {
		error.textContent = text;
		error.hidden = '' === text;
	};
	var revision = function () {
		return parseInt( page.getAttribute( 'data-revision' ), 10 ) || 0;
	};
	var words = function ( failure ) {
		var fields = failure && failure.data && failure.data.fields;
		return ( fields && fields.title ) || ( failure && failure.message ) || page.getAttribute( 'data-failed' );
	};

	// A job: { send( revision ) → apiFetch promise, done( data ), failed( text ), fields( { field: text } ) }.
	var next = function () {
		if ( busy || 0 === jobs.length ) {
			return;
		}
		var job = jobs.shift();
		busy = true;
		status.textContent = page.getAttribute( 'data-saving' );
		say( '' );
		job.send( revision() )
			.then( function ( answer ) {
				var data = answer.data;
				if ( undefined !== data.revision ) {
					page.setAttribute( 'data-revision', String( data.revision ) );
				}
				if ( undefined !== data.html ) {
					region.innerHTML = data.html;
				}
				status.textContent = page.getAttribute( 'data-saved' );
				if ( job.done ) {
					job.done( data );
				}
			} )
			.catch( function ( failure ) {
				jobs = [];
				settings = null;
				status.textContent = '';
				var fields = failure && failure.data && failure.data.fields;
				if ( fields && job.fields ) {
					job.fields( fields );
					return;
				}
				( job.failed || say )( words( failure ) );
			} )
			.then( function () {
				busy = false;
				next();
			} );
	};
	var enqueue = function ( job ) {
		jobs.push( job );
		next();
	};

	// Settings (T3): one PATCH for the changes gathered until it is sent.
	var save = function ( field, value ) {
		if ( null !== settings ) {
			settings[ field ] = value;
			return;
		}
		settings = {};
		settings[ field ] = value;
		enqueue( {
			send: function ( current ) {
				var changes = settings;
				settings = null;
				if ( ! id ) {
					return wp.apiFetch( { path: '/affibloom/v1/tables', method: 'POST', data: { title: changes.title || name.value, style: style.value } } );
				}
				return wp.apiFetch( { path: path, method: 'PATCH', data: Object.assign( { revision: current }, changes ) } );
			},
			done: function ( data ) {
				if ( ! id ) {
					window.location.assign( data.redirect_to );
					return;
				}
				saved = data.title;
			},
		} );
	};

	name.addEventListener( 'input', function () {
		heading.textContent = '' !== name.value.trim() ? name.value : page.getAttribute( 'data-untitled' );
	} );
	name.addEventListener( 'change', function () {
		var value = name.value.trim();
		if ( '' === value || value === saved ) {
			return;
		}
		save( 'title', value );
	} );
	// Enter in the name saves it rather than submitting the form.
	form.addEventListener( 'submit', function ( event ) {
		event.preventDefault();
		name.blur();
	} );
	style.addEventListener( 'change', function () {
		headersRow.hidden = 'row' !== style.value;
		if ( id ) {
			save( 'style', style.value );
		}
	} );
	form.querySelector( '[name="theme"]' ).addEventListener( 'change', function ( event ) {
		if ( id ) {
			save( 'theme', event.target.value );
		}
	} );
	[ 'show_headers', 'show_field_name' ].forEach( function ( toggle ) {
		form.querySelector( '[name="' + toggle + '"]' ).addEventListener( 'change', function ( event ) {
			if ( id ) {
				save( toggle, event.target.checked );
			}
		} );
	} );

	if ( ! id ) {
		return;
	}

	// Add Product (T4; R17, R18).
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
		button: function () {
			return { label: picker.dataset.chooseLabel, disabled: false };
		},
		choose: function ( item, button ) {
			button.disabled = true;
			enqueue( {
				send: function ( current ) {
					return wp.apiFetch( { path: path + '/products', method: 'POST', data: { revision: current, product: item.id } } );
				},
				done: function () {
					button.disabled = false;
					window.affibloomModal.hide();
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
		if ( ! searched ) {
			searched = true;
			finder.search( true );
		}
	} );

	// Add Group (T5; R24).
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
			enqueue( {
				send: function ( current ) {
					return wp.apiFetch( { path: path + '/products', method: 'POST', data: { revision: current, group: item.id } } );
				},
				done: function () {
					button.disabled = false;
					window.affibloomModal.hide();
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
		if ( ! groupsSearched ) {
			groupsSearched = true;
			groupFinder.search( true );
		}
	} );

	// Add Row / Add Column, Add Field, removing fields and rows (T6; R25-R28, R31, R33).
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
			enqueue( {
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

	var fieldRemover = document.getElementById( 'affibloom-table-remove-field' );
	var fieldConfirm = fieldRemover.querySelector( '[data-table-remove-field-confirm]' );
	var removingField = null;
	fieldConfirm.addEventListener( 'click', function () {
		var chosen = removingField;
		var label = fieldConfirm.textContent;
		fieldConfirm.disabled = true;
		fieldConfirm.textContent = fieldConfirm.getAttribute( 'data-busy-label' );
		var restore = function () {
			fieldConfirm.disabled = false;
			fieldConfirm.textContent = label;
			window.affibloomModal.hide();
		};
		enqueue( {
			send: function ( current ) {
				return wp.apiFetch( { path: path + '/groups/' + encodeURIComponent( chosen.group ) + '/fields/' + chosen.field + '?revision=' + current, method: 'DELETE' } );
			},
			done: restore,
			failed: function ( text ) {
				restore();
				say( text );
			},
		} );
	} );

	// A cell's value (R30): which product and field an element of the cell stands for, and its save.
	var cellOf = function ( element ) {
		return {
			product: element.closest( '[data-table-product]' ).getAttribute( 'data-table-product' ),
			field: element.closest( '[data-table-field]' ).dataset.tableField,
		};
	};
	var putValue = function ( cell, value, done ) {
		enqueue( {
			send: function ( current ) {
				return wp.apiFetch( { path: path + '/values/' + cell.product + '/' + cell.field, method: 'PUT', data: { revision: current, value: value } } );
			},
			done: done,
		} );
	};
	// A typed value, 500 ms after its last change.
	var timers = {};
	var saveValue = function ( input ) {
		var cell = cellOf( input );
		var key = cell.product + '-' + cell.field;
		window.clearTimeout( timers[ key ] );
		timers[ key ] = window.setTimeout( function () {
			var value = input.value;
			putValue( cell, value, function ( data ) {
				// The stored value (a rating rounded, empty lines dropped) shows once the box is left.
				if ( document.activeElement !== input && input.value === value ) {
					input.value = data.value;
				}
			} );
		}, 500 );
	};

	// An image (T7; R29): the media library, saved at once.
	var pickImage = function ( button ) {
		var cell = cellOf( button );
		window.affibloomMedia.pick( { title: page.dataset.mediaTitle, button: page.dataset.mediaButton }, function ( picked ) {
			putValue( cell, picked.url, function ( data ) {
				var target = region.querySelector( '[data-table-product="' + cell.product + '"] [data-table-field="' + cell.field + '"] [data-table-image]' );
				var image = target && target.querySelector( 'img' );
				if ( target && ! image ) {
					image = document.createElement( 'img' );
					image.className = 'affibloom-table-field__image';
					image.alt = '';
					var none = target.querySelector( '.affibloom-table-field__no-image' );
					target.replaceChild( image, none );
				}
				if ( image ) {
					image.src = data.value;
				}
			} );
		} );
	};

	// A price (T7; R29, D10): one rich editor at a time, in the cell being edited.
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
		open.holder.replaceWith( open.box );
		if ( value !== open.saved ) {
			putValue( open.cell, value, function ( data ) {
				open.box.innerHTML = data.value;
			} );
		}
	};
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
		var open = { id: id, box: box, holder: holder, cell: cellOf( holder ), saved: box.innerHTML, timer: 0 };
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
							if ( priceEditor === open && value !== open.saved ) {
								open.saved = value;
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

	// "Description Editor" (T7; R32).
	var describer = document.getElementById( 'affibloom-table-description' );
	var describeSave = describer.querySelector( '[data-table-description-save]' );
	var describeError = describer.querySelector( '[data-table-description-error]' );
	var describing = null;
	describeSave.addEventListener( 'click', function () {
		var editor = tinymce.get( 'affibloom_table_description' );
		var chosen = describing;
		var label = describeSave.textContent;
		describeSave.disabled = true;
		describeSave.textContent = describeSave.getAttribute( 'data-busy-label' );
		describeError.hidden = true;
		var restore = function () {
			describeSave.disabled = false;
			describeSave.textContent = label;
		};
		enqueue( {
			send: function ( current ) {
				return wp.apiFetch( { path: path + '/values/' + chosen.cell.product + '/' + chosen.cell.field, method: 'PUT', data: { revision: current, value: editor ? editor.getContent() : '' } } );
			},
			done: function ( data ) {
				restore();
				chosen.rich.innerHTML = data.value;
				window.affibloomModal.hide();
			},
			failed: function ( text ) {
				restore();
				describeError.textContent = text;
				describeError.hidden = false;
			},
		} );
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
			var stars = input.parentNode.querySelector( '[data-table-stars]' );
			stars.style.setProperty( '--rating', String( parseFloat( input.value ) || 0 ) );
		}
		saveValue( input );
	} );

	// Remove this product (T4; R23).
	var remover = document.getElementById( 'affibloom-table-remove-product' );
	var confirm = remover.querySelector( '[data-table-remove-confirm]' );
	var removing = 0;
	confirm.addEventListener( 'click', function () {
		var product = removing;
		var label = confirm.textContent;
		confirm.disabled = true;
		confirm.textContent = confirm.getAttribute( 'data-busy-label' );
		var restore = function () {
			confirm.disabled = false;
			confirm.textContent = label;
		};
		enqueue( {
			send: function ( current ) {
				return wp.apiFetch( { path: path + '/products/' + product + '?revision=' + current, method: 'DELETE' } );
			},
			done: function () {
				restore();
				window.affibloomModal.hide();
			},
			failed: function ( text ) {
				restore();
				window.affibloomModal.hide();
				say( text );
			},
		} );
	} );

	region.addEventListener( 'click', function ( event ) {
		var add = event.target.closest( '[data-table-action="add-product"]' );
		if ( add ) {
			window.affibloomModal.show( picker, add );
			return;
		}
		var addGroup = event.target.closest( '[data-table-action="add-group"]' );
		if ( addGroup ) {
			window.affibloomModal.show( groupPicker, addGroup );
			return;
		}
		if ( event.target.closest( '[data-table-action="add-line"]' ) ) {
			enqueue( {
				send: function ( current ) {
					return wp.apiFetch( { path: path + '/groups', method: 'POST', data: { revision: current } } );
				},
			} );
			return;
		}
		var addField = event.target.closest( '[data-table-add-field]' );
		if ( addField ) {
			fieldGroup = addField.closest( '[data-table-group]' ).getAttribute( 'data-table-group' );
			window.affibloomModal.show( fieldModal, addField );
			return;
		}
		var removeField = event.target.closest( '[data-table-remove-field]' );
		if ( removeField ) {
			var holder = removeField.closest( '[data-table-field]' );
			removingField = { group: removeField.closest( '[data-table-group]' ).getAttribute( 'data-table-group' ), field: holder.dataset.tableField };
			fieldRemover.querySelector( '.affibloom-modal__title' ).textContent = fieldRemover.dataset.titleTemplate.replace( '%s', holder.dataset.fieldName );
			window.affibloomModal.show( fieldRemover, removeField );
			return;
		}
		var image = event.target.closest( '[data-table-image]' );
		if ( image ) {
			pickImage( image );
			return;
		}
		var price = event.target.closest( '[data-table-price]' );
		if ( price ) {
			editPrice( price );
			return;
		}
		var stepButton = event.target.closest( '[data-table-step]' );
		if ( stepButton ) {
			stepped( stepButton, parseInt( stepButton.dataset.tableStep, 10 ) );
			return;
		}
		var shiftButton = event.target.closest( '[data-table-shift]' );
		if ( shiftButton ) {
			shifted( shiftButton, parseInt( shiftButton.dataset.tableShift, 10 ) );
			return;
		}
		var editButton = event.target.closest( '[data-table-button]' );
		if ( editButton ) {
			openButton( editButton );
			return;
		}
		var describe = event.target.closest( '[data-table-description]' );
		if ( describe ) {
			describing = { cell: cellOf( describe ), rich: describe.parentNode.querySelector( '[data-table-rich]' ) };
			var editor = tinymce.get( 'affibloom_table_description' );
			if ( editor ) {
				editor.setContent( describing.rich.innerHTML );
			}
			describeError.hidden = true;
			window.affibloomModal.show( describer, describe );
			return;
		}
		var removeRow = event.target.closest( '[data-table-remove-row]' );
		if ( removeRow ) {
			var row = removeRow.closest( '[data-table-group]' ).getAttribute( 'data-table-group' );
			enqueue( {
				send: function ( current ) {
					return wp.apiFetch( { path: path + '/groups/' + encodeURIComponent( row ) + '?revision=' + current, method: 'DELETE' } );
				},
			} );
			return;
		}
		var remove = event.target.closest( '[data-table-remove]' );
		if ( remove ) {
			var cell = remove.closest( '[data-table-product]' );
			removing = parseInt( cell.getAttribute( 'data-table-product' ), 10 );
			remover.querySelector( '.affibloom-modal__title' ).textContent = remover.dataset.titleTemplate.replace( '%s', cell.getAttribute( 'data-product-name' ) );
			window.affibloomModal.show( remover, remove );
		}
	} );

	// 'Edit "<button>"' (T8; R35).
	var buttoner = document.getElementById( 'affibloom-table-button' );
	var buttonForm = buttoner.querySelector( '[data-table-button-form]' );
	var buttonSave = buttoner.querySelector( '[data-table-button-save]' );
	var buttonText = buttonForm.querySelector( '[name="button_text"]' );
	var buttonUrl = buttonForm.querySelector( '[name="button_url"]' );
	var buttonCell = null;
	var buttonProblem = function ( fields, text ) {
		buttonForm.querySelectorAll( '[data-error-for]' ).forEach( function ( line ) {
			var message = ( fields && fields[ line.dataset.errorFor ] ) || '';
			line.textContent = message;
			line.hidden = '' === message;
		} );
		var general = buttonForm.querySelector( '[data-table-button-error]' );
		general.textContent = text || '';
		general.hidden = ! text;
	};
	var openButton = function ( trigger ) {
		var holder = trigger.closest( '.affibloom-table-field__buttons' );
		var field = trigger.closest( '[data-table-field]' );
		buttonCell = cellOf( trigger );
		buttoner.querySelector( '.affibloom-modal__title' ).textContent = buttoner.dataset.titleTemplate.replace( '%s', field.dataset.fieldName );
		buttonText.value = holder.dataset.buttonText;
		buttonUrl.value = holder.dataset.buttonUrl;
		buttonUrl.readOnly = '1' === holder.dataset.buttonPrimary;
		buttonProblem( null, '' );
		window.affibloomModal.show( buttoner, trigger );
	};
	buttonSave.addEventListener( 'click', function () {
		var chosen = buttonCell;
		var label = buttonSave.textContent;
		var value = JSON.stringify( { button_text: buttonText.value, url: buttonUrl.value } );
		buttonSave.disabled = true;
		buttonSave.textContent = buttonSave.getAttribute( 'data-busy-label' );
		buttonProblem( null, '' );
		var restore = function () {
			buttonSave.disabled = false;
			buttonSave.textContent = label;
		};
		enqueue( {
			send: function ( current ) {
				return wp.apiFetch( { path: path + '/values/' + chosen.product + '/' + chosen.field, method: 'PUT', data: { revision: current, value: value } } );
			},
			done: function () {
				restore();
				window.affibloomModal.hide();
			},
			failed: function ( text ) {
				restore();
				buttonProblem( null, text );
			},
			fields: function ( fields ) {
				restore();
				buttonProblem( fields, '' );
			},
		} );
	} );
	buttonForm.addEventListener( 'input', function ( event ) {
		var line = buttonForm.querySelector( '[data-error-for="' + event.target.name + '"]' );
		if ( line ) {
			line.hidden = true;
		}
	} );
	buttonForm.addEventListener( 'submit', function ( event ) {
		event.preventDefault();
		buttonSave.click();
	} );

	// Order (T9; R36-R39, D9).
	var unique = function ( list ) {
		return list.filter( function ( item, index ) {
			return list.indexOf( item ) === index;
		} );
	};
	var productOrder = function () {
		return unique( Array.prototype.map.call( region.querySelectorAll( '.affibloom-table-cell--head' ), function ( head ) {
			return head.getAttribute( 'data-table-product' );
		} ) );
	};
	// The groups as the first product's cells hold them, the heading group first.
	var groupOrder = function () {
		var first = productOrder()[ 0 ];
		return Array.prototype.map.call( region.querySelectorAll( '.affibloom-table-cell[data-table-product="' + first + '"]' ), function ( cell ) {
			return cell.getAttribute( 'data-table-group' );
		} );
	};
	var fieldOrder = function ( cell ) {
		return Array.prototype.map.call( cell.querySelectorAll( '[data-table-field]' ), function ( field ) {
			return parseInt( field.dataset.tableField, 10 );
		} );
	};
	var sendOrder = function ( data ) {
		enqueue( {
			send: function ( current ) {
				return wp.apiFetch( { path: path + '/order', method: 'PUT', data: Object.assign( { revision: current }, data ) } );
			},
		} );
	};
	var moveField = function ( from, field, to, position ) {
		enqueue( {
			send: function ( current ) {
				return wp.apiFetch( {
					path: path + '/groups/' + encodeURIComponent( from ) + '/fields/' + field + '/move',
					method: 'POST',
					data: { revision: current, to_group: to, position: position },
				} );
			},
		} );
	};
	// An item placed before or after another in a list.
	var place = function ( list, item, target, after ) {
		var rest = list.filter( function ( entry ) {
			return entry !== item;
		} );
		var at = rest.indexOf( target ) + ( after ? 1 : 0 );
		rest.splice( at, 0, item );
		return rest;
	};
	var step = function ( list, item, by ) {
		var at = list.indexOf( item );
		var to = at + by;
		if ( at < 0 || to < 0 || to >= list.length ) {
			return null;
		}
		var moved = list.slice();
		moved.splice( at, 1 );
		moved.splice( to, 0, item );
		return moved;
	};
	// What a drag or a step moves: { kind, item, group, cell } for the grip or button inside it.
	var moving = function ( control ) {
		var kind = control.closest( '[data-table-move]' ).dataset.tableMove;
		var cell = control.closest( '[data-table-group]' );
		if ( 'product' === kind ) {
			return { kind: kind, item: cell.getAttribute( 'data-table-product' ) };
		}
		if ( 'group' === kind ) {
			return { kind: kind, item: cell.getAttribute( 'data-table-group' ) };
		}
		return { kind: kind, item: parseInt( control.closest( '[data-table-field]' ).dataset.tableField, 10 ), group: cell.getAttribute( 'data-table-group' ), cell: cell };
	};
	var stepped = function ( control, by ) {
		var what = moving( control );
		var order;
		if ( 'product' === what.kind ) {
			order = step( productOrder(), what.item, by );
			return order && sendOrder( { products: order.map( Number ) } );
		}
		if ( 'group' === what.kind ) {
			order = step( groupOrder().slice( 1 ), what.item, by );
			return order && sendOrder( { groups: order } );
		}
		order = step( fieldOrder( what.cell ), what.item, by );
		return order && sendOrder( { cell: { group: what.group, fields: order } } );
	};
	var shifted = function ( button, by ) {
		var cell = button.closest( '[data-table-group]' );
		var groups = groupOrder();
		var to = groups[ groups.indexOf( cell.getAttribute( 'data-table-group' ) ) + by ];
		if ( to ) {
			moveField( cell.getAttribute( 'data-table-group' ), parseInt( button.closest( '[data-table-field]' ).dataset.tableField, 10 ), to, 9999 );
		}
	};

	// Dragging by a grip: the item under the pointer is the drop target.
	var dragging = null;
	var targetAt = function ( event ) {
		var under = document.elementFromPoint( event.clientX, event.clientY );
		if ( ! under || ! region.contains( under ) ) {
			return null;
		}
		if ( 'product' === dragging.kind ) {
			return under.closest( '[data-table-product]' );
		}
		if ( 'group' === dragging.kind ) {
			var cell = under.closest( '.affibloom-table-cell:not(.affibloom-table-cell--head)' );
			return cell;
		}
		return under.closest( '[data-table-field]' ) || under.closest( '[data-table-group]' );
	};
	var mark = function ( element ) {
		region.querySelectorAll( '.is-drop-target' ).forEach( function ( marked ) {
			marked.classList.remove( 'is-drop-target' );
		} );
		if ( element ) {
			element.classList.add( 'is-drop-target' );
		}
	};
	region.addEventListener( 'pointerdown', function ( event ) {
		var grip = event.target.closest( '[data-table-drag]' );
		if ( ! grip || 0 !== event.button ) {
			return;
		}
		event.preventDefault();
		dragging = moving( grip );
		dragging.grip = grip;
		grip.setPointerCapture( event.pointerId );
		region.classList.add( 'is-dragging' );
	} );
	region.addEventListener( 'pointermove', function ( event ) {
		if ( dragging ) {
			mark( targetAt( event ) );
		}
	} );
	var drop = function ( event ) {
		if ( ! dragging ) {
			return;
		}
		var what = dragging;
		var target = targetAt( event );
		dragging = null;
		mark( null );
		region.classList.remove( 'is-dragging' );
		if ( ! target ) {
			return;
		}
		var box = target.getBoundingClientRect();
		var after = 'product' === what.kind && ! target.closest( '.affibloom-table-builder--row' )
			? event.clientX > box.left + box.width / 2
			: event.clientY > box.top + box.height / 2;
		if ( 'product' === what.kind ) {
			var product = target.getAttribute( 'data-table-product' );
			if ( product !== what.item ) {
				sendOrder( { products: place( productOrder(), what.item, product, after ).map( Number ) } );
			}
			return;
		}
		if ( 'group' === what.kind ) {
			var group = target.getAttribute( 'data-table-group' );
			var across = !! target.closest( '.affibloom-table-builder--row' );
			after = across ? event.clientX > box.left + box.width / 2 : after;
			if ( group !== what.item && groupOrder().indexOf( group ) > 0 ) {
				sendOrder( { groups: place( groupOrder().slice( 1 ), what.item, group, after ) } );
			}
			return;
		}
		var cell = target.closest( '[data-table-group]' );
		var to = cell.getAttribute( 'data-table-group' );
		var onField = target.hasAttribute( 'data-table-field' ) ? parseInt( target.dataset.tableField, 10 ) : null;
		if ( to === what.group ) {
			if ( null !== onField && onField !== what.item ) {
				sendOrder( { cell: { group: to, fields: place( fieldOrder( what.cell ), what.item, onField, after ) } } );
			}
			return;
		}
		var fields = fieldOrder( cell );
		moveField( what.group, what.item, to, null === onField ? fields.length : fields.indexOf( onField ) + ( after ? 1 : 0 ) );
	};
	region.addEventListener( 'pointerup', drop );
	region.addEventListener( 'pointercancel', function () {
		dragging = null;
		mark( null );
		region.classList.remove( 'is-dragging' );
	} );

	// A price box opens on Enter or Space too.
	region.addEventListener( 'keydown', function ( event ) {
		var price = event.target.closest( '[data-table-price]' );
		if ( price && ( 'Enter' === event.key || ' ' === event.key ) ) {
			event.preventDefault();
			editPrice( price );
		}
	} );

	// Badge (T4; R21): saved trimmed on leaving it, when it changed.
	region.addEventListener( 'focusout', function ( event ) {
		var badge = event.target.closest( '[data-table-badge]' );
		if ( ! badge || badge.value.trim() === badge.getAttribute( 'data-saved' ) ) {
			return;
		}
		var product = badge.closest( '[data-table-product]' ).getAttribute( 'data-table-product' );
		var value = badge.value.trim();
		enqueue( {
			send: function ( current ) {
				return wp.apiFetch( { path: path + '/products/' + product, method: 'PATCH', data: { revision: current, badge: value } } );
			},
			done: function () {
				badge.setAttribute( 'data-saved', value );
			},
		} );
	} );
}() );
