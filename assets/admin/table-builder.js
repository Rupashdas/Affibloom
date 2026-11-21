/* global wp */
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

	// A job: { send( revision ) → apiFetch promise, done( data ), failed( text ) }.
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
		var remove = event.target.closest( '[data-table-remove]' );
		if ( remove ) {
			var cell = remove.closest( '[data-table-product]' );
			removing = parseInt( cell.getAttribute( 'data-table-product' ), 10 );
			remover.querySelector( '.affibloom-modal__title' ).textContent = remover.dataset.titleTemplate.replace( '%s', cell.getAttribute( 'data-product-name' ) );
			window.affibloomModal.show( remover, remove );
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
