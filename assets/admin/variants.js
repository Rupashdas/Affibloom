/* global wp */
/**
 * The product page's Variants tab (F27). The search narrows the rows as you type, by name, key or address in any case,
 * without a request (Lasso's url-variants.js 72-81, 548-553); Copy puts a row's address on the clipboard and says
 * "Copied!" for 1.5 seconds (D5). Task V2, Lasso's editing (297-361, 437-530): Add Variant adds a row in edit mode with
 * Lasso's suggestion; Edit turns a row into its two fields; Save and Remove store the whole list through
 * PUT /products/{id}/variants, one save at a time; the answer's rows replace the list and the tab's count follows. A
 * refusal is shown under the row's key field; the rows not being saved are sent as they were stored (R25).
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var tab = document.querySelector( '[data-variants]' );
		if ( ! tab ) {
			return;
		}
		var search = document.querySelector( '[data-variant-search]' );
		var table = tab.querySelector( '[data-variant-table]' );
		var body = tab.querySelector( '[data-variant-rows]' );
		var empty = tab.querySelector( '[data-variant-empty]' );
		var noMatch = tab.querySelector( '[data-variant-no-match]' );
		var editTemplate = tab.querySelector( '[data-variant-edit-template]' );
		var removeModal = document.getElementById( 'affibloom-variant-remove-modal' );
		var count = document.querySelector( '.affibloom-tabs__tab[aria-current] .affibloom-tabs__count' );
		var path = '/affibloom/v1/products/' + tab.dataset.productId + '/variants';
		var saving = false;
		var removing = null;
		var editors = 0;

		var rows = function () {
			return Array.prototype.slice.call( body.children );
		};

		/** Table or empty state, by whether a row is there. */
		var showState = function () {
			var any = 0 !== body.children.length;
			table.hidden = ! any;
			empty.hidden = any;
		};

		var filter = function () {
			var term = search ? search.value.trim().toLowerCase() : '';
			var shown = 0;
			rows().forEach( function ( row ) {
				var match = ! row.hasAttribute( 'data-variant-row' ) || '' === term || -1 !== ( row.dataset.search || '' ).indexOf( term );
				row.hidden = ! match;
				shown += match ? 1 : 0;
			} );
			noMatch.hidden = 0 !== shown || '' === term;
		};
		if ( search ) {
			search.addEventListener( 'input', filter );
		}

		/** A row in edit mode for a stored row (its values) or a new one (Lasso's suggestion); the view row is kept to come back to. */
		var editRow = function ( values, view ) {
			var row = editTemplate.content.firstElementChild.cloneNode( true );
			var id = 'affibloom-variant-' + ( ++editors );
			var name = row.querySelector( '[data-variant-name]' );
			var key = row.querySelector( '[data-variant-key]' );
			var error = row.querySelector( '[data-variant-error]' );
			name.id = id + '-name';
			key.id = id + '-key';
			error.id = id + '-error';
			row.querySelector( '[data-variant-label="name"]' ).htmlFor = name.id;
			row.querySelector( '[data-variant-label="key"]' ).htmlFor = key.id;
			name.value = values.name;
			key.value = values.key;
			row.view = view;
			key.addEventListener( 'input', function () {
				showError( row, '' );
			} );
			row.addEventListener( 'keydown', function ( event ) {
				if ( 'Enter' === event.key && event.target.matches( 'input' ) ) {
					event.preventDefault();
					save( row );
				} else if ( 'Escape' === event.key ) {
					cancel( row );
				}
			} );
			return row;
		};

		var showError = function ( row, message ) {
			var error = row.querySelector( '[data-variant-error]' );
			var key = row.querySelector( '[data-variant-key]' );
			error.textContent = message;
			error.hidden = '' === message;
			key.classList.toggle( 'is-invalid', '' !== message );
			if ( '' === message ) {
				key.removeAttribute( 'aria-describedby' );
			} else {
				key.setAttribute( 'aria-describedby', error.id );
			}
		};

		/** Lasso's suggestion (R10): the site's tracking ID, or `<slug>-<n>` from 2 up, the first not in the list. */
		var suggestedKey = function () {
			if ( tab.dataset.amazon ) {
				return tab.dataset.trackingId || '';
			}
			var used = rows().map( function ( row ) {
				return row.hasAttribute( 'data-variant-row' ) ? row.dataset.key : row.querySelector( '[data-variant-key]' ).value;
			} );
			var n = 2;
			while ( -1 !== used.indexOf( tab.dataset.baseSlug + '-' + n ) ) {
				n++;
			}
			return tab.dataset.baseSlug + '-' + n;
		};

		var add = function () {
			var row = editRow( { name: tab.dataset.defaultName, key: suggestedKey() }, null );
			body.appendChild( row );
			showState();
			row.scrollIntoView( { block: 'nearest' } );
			row.querySelector( '[data-variant-name]' ).focus();
		};

		var cancel = function ( row ) {
			if ( row.view ) {
				body.replaceChild( row.view, row );
				row.view.querySelector( '[data-variant-edit]' ).focus();
			} else {
				body.removeChild( row );
				showState();
			}
		};

		/**
		 * The list to store: each stored row as it was (R25), the saved row with its fields; a new row not being saved is
		 * left out. `without` drops a row (Remove).
		 */
		var list = function ( saved, without ) {
			var sent = [];
			rows().forEach( function ( row ) {
				if ( without && ( row === without || row.view === without ) ) {
					return;
				}
				if ( row === saved ) {
					sent.push( { row: row, name: row.querySelector( '[data-variant-name]' ).value, key: row.querySelector( '[data-variant-key]' ).value } );
				} else if ( row.hasAttribute( 'data-variant-row' ) ) {
					sent.push( { row: row, name: row.dataset.name, key: row.dataset.key } );
				} else if ( row.view ) {
					sent.push( { row: row, name: row.view.dataset.name, key: row.view.dataset.key } );
				}
			} );
			return sent;
		};

		/** Stores the list; the answer's rows replace them all. Returns the request, or null while another save runs. */
		var store = function ( sent ) {
			if ( saving ) {
				return null;
			}
			saving = true;
			return wp.apiFetch( {
				path: path,
				method: 'PUT',
				data: { variants: sent.map( function ( item ) {
					return { name: item.name, key: item.key };
				} ) },
			} ).then( function ( answer ) {
				body.innerHTML = answer.data.html;
				body.dispatchEvent( new CustomEvent( 'affibloom:enhance', { bubbles: true } ) );
				if ( count ) {
					count.textContent = String( answer.data.count );
				}
				showState();
				filter();
				return answer;
			} ).finally( function () {
				saving = false;
			} );
		};

		/** A refusal under its row's key (`variants.<i>.key`), else the failure toast. */
		var refused = function ( problem, sent ) {
			var fields = problem && problem.data && problem.data.fields ? problem.data.fields : {};
			var shown = false;
			Object.keys( fields ).forEach( function ( field ) {
				var match = /^variants\.(\d+)\.key$/.exec( field );
				var item = match ? sent[ Number( match[ 1 ] ) ] : null;
				if ( item && item.row.hasAttribute( 'data-variant-editing' ) ) {
					showError( item.row, fields[ field ] );
					item.row.querySelector( '[data-variant-key]' ).focus();
					shown = true;
				}
			} );
			if ( ! shown ) {
				window.affibloomToast( problem && problem.message ? problem.message : tab.dataset.failed, true );
			}
		};

		var busy = function ( button, on ) {
			if ( on ) {
				button.dataset.label = button.textContent;
				button.textContent = button.dataset.busyLabel;
			} else if ( button.dataset.label ) {
				button.textContent = button.dataset.label;
			}
			button.disabled = on;
			button.classList.toggle( 'is-busy', on );
		};

		var save = function ( row ) {
			var button = row.querySelector( '[data-variant-save]' );
			var sent = list( row, null );
			var request = store( sent );
			if ( ! request ) {
				return;
			}
			busy( button, true );
			request.catch( function ( problem ) {
				busy( button, false );
				refused( problem, sent );
			} );
		};

		body.addEventListener( 'click', function ( event ) {
			var row = event.target.closest( 'tr' );
			if ( event.target.closest( '[data-variant-edit]' ) ) {
				var editing = editRow( { name: row.dataset.name, key: row.dataset.key }, row );
				body.replaceChild( editing, row );
				editing.querySelector( '[data-variant-name]' ).focus();
			} else if ( event.target.closest( '[data-variant-save]' ) ) {
				save( row );
			} else if ( event.target.closest( '[data-variant-cancel]' ) ) {
				cancel( row );
			} else if ( event.target.closest( '[data-variant-remove]' ) ) {
				removing = row;
				window.affibloomModal.show( removeModal, event.target.closest( '[data-variant-remove]' ) );
			} else if ( event.target.closest( '[data-variant-copy]' ) ) {
				copy( event.target.closest( '[data-variant-copy]' ) );
			}
		} );

		removeModal.querySelector( '[data-variant-remove-confirm]' ).addEventListener( 'click', function ( event ) {
			var button = event.currentTarget;
			var request = removing ? store( list( null, removing ) ) : null;
			if ( ! request ) {
				return;
			}
			busy( button, true );
			request.then( function () {
				busy( button, false );
				window.affibloomModal.hide();
				removing = null;
			} ).catch( function ( problem ) {
				busy( button, false );
				window.affibloomModal.hide();
				window.affibloomToast( problem && problem.message ? problem.message : removeModal.dataset.failedText, true );
			} );
		} );

		Array.prototype.forEach.call( document.querySelectorAll( '[data-variant-add]' ), function ( button ) {
			button.addEventListener( 'click', add );
		} );

		var timers = new WeakMap();
		var copy = function ( button ) {
			var label = button.querySelector( '[data-copy-label]' );
			window.affibloomCopy( button.dataset.variantCopy, function () {
				var name = button.dataset.copyName || label.textContent;
				button.dataset.copyName = name;
				window.clearTimeout( timers.get( button ) );
				label.textContent = button.dataset.copied;
				timers.set( button, window.setTimeout( function () {
					label.textContent = name;
				}, 1500 ) );
			}, function () {
				window.affibloomToast( button.dataset.copyFailed, true );
			} );
		};
	} );
}() );
