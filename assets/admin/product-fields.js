/* global wp */
/**
 * Custom fields on the product page (F1, Task 14C). Every change is stored as it is made, as Lasso's page does: a value
 * when it changes (on leaving its box), a switch when it is turned, the order when a row moves; adding and removing a
 * field redraw the rows. The "Add Field" dialog: "Create new" stores a field and opens the library, as Lasso's does;
 * "Add from library" searches by name, ten a page, and adds one. Opening and closing the dialogs is admin.js's.
 */
( function () {
	'use strict';

	var format = function ( template, values ) {
		return values.reduce( function ( text, value, index ) {
			return text.replace( '%' + ( index + 1 ) + '$s', value ).replace( '%s', value );
		}, template );
	};

	document.addEventListener( 'DOMContentLoaded', function () {
		var box = document.querySelector( '[data-product-fields]' );
		var modal = document.querySelector( '[data-field-modal]' );
		var removeModal = document.querySelector( '[data-field-remove-modal]' );
		if ( ! box || ! modal || ! removeModal ) {
			return;
		}
		var list = box.querySelector( '[data-field-list]' );
		var path = '/affibloom/v1/products/' + box.dataset.productId + '/fields';
		var pending = null;

		var close = function ( dialog ) {
			dialog.querySelector( '.affibloom-modal__head [data-affibloom-close]' ).click();
		};

		/** The rows drawn again by the server; the help tips inside them are wired up. */
		var draw = function ( html ) {
			list.innerHTML = html;
			list.dispatchEvent( new CustomEvent( 'affibloom:enhance', { bubbles: true } ) );
		};

		var rows = function () {
			return Array.prototype.slice.call( list.querySelectorAll( '[data-field-row]' ) );
		};

		/** A failure shown as the failure toast: the server's words, else the page's (Task 2X1; no change goes by silently). */
		var failed = function ( problem, fallback ) {
			window.affibloomToast( problem && problem.message ? problem.message : fallback, true );
		};

		/** The order, values and switches, as the page shows them (Lasso's lasso_save_field_positions). */
		var save = function () {
			return wp.apiFetch( {
				path: path,
				method: 'PUT',
				data: {
					rows: rows().map( function ( row ) {
						var name = row.querySelector( '[data-field-show-name]' );
						return {
							field: parseInt( row.dataset.fieldId, 10 ),
							value: row.querySelector( '[data-field-value]' ).value,
							visible: row.querySelector( '[data-field-visible]' ).checked,
							show_name: name ? name.checked : false,
						};
					} ),
				},
			} ).catch( function ( problem ) {
				failed( problem, box.dataset.saveFailed );
			} );
		};

		/** Move buttons: none above Primary Rating (fixed first), none past the ends. */
		var ends = function () {
			var movable = rows().filter( function ( row ) {
				return row.querySelector( '[data-field-move]' );
			} );
			movable.forEach( function ( row, position ) {
				row.querySelector( '[data-field-move="up"]' ).disabled = 0 === position;
				row.querySelector( '[data-field-move="down"]' ).disabled = movable.length - 1 === position;
			} );
		};

		list.addEventListener( 'change', function ( event ) {
			if ( event.target.matches( '[data-field-value], [data-field-visible], [data-field-show-name]' ) ) {
				save();
			}
		} );

		// Lasso's rating box: 1 to 5, one decimal, the stars following as it is typed.
		list.addEventListener( 'input', function ( event ) {
			var stars = event.target.closest( '[data-field-row]' ) && event.target.closest( '[data-field-row]' ).querySelector( '[data-field-stars]' );
			if ( stars && event.target.matches( '[data-field-value]' ) ) {
				var value = parseFloat( event.target.value );
				stars.style.setProperty( '--rating', String( isNaN( value ) ? 0 : Math.min( 5, Math.max( 0, value ) ) ) );
			}
		} );
		list.addEventListener( 'focusout', function ( event ) {
			if ( event.target.matches( '.affibloom-input--rating' ) ) {
				var stars = parseFloat( event.target.value );
				stars = isNaN( stars ) || 0 >= stars ? 1 : Math.min( 5, stars );
				event.target.value = stars.toFixed( 1 );
				event.target.closest( '[data-field-row]' ).querySelector( '[data-field-stars]' ).style.setProperty( '--rating', event.target.value );
			}
		} );

		list.addEventListener( 'click', function ( event ) {
			var move = event.target.closest( '[data-field-move]' );
			var remove = event.target.closest( '[data-field-remove]' );
			var row = event.target.closest( '[data-field-row]' );
			if ( move ) {
				if ( 'up' === move.dataset.fieldMove && row.previousElementSibling ) {
					list.insertBefore( row, row.previousElementSibling );
				} else if ( 'down' === move.dataset.fieldMove && row.nextElementSibling ) {
					list.insertBefore( row.nextElementSibling, row );
				}
				ends();
				( move.disabled ? row.querySelector( '[data-field-move]:not([disabled])' ) : move ).focus();
				save();
			} else if ( remove ) {
				// Runs before admin.js opens the dialog: name the field in its question.
				pending = row;
				var title = removeModal.querySelector( '[data-field-remove-title]' );
				title.textContent = format( title.dataset.format, [ row.dataset.fieldName ] );
				removeProblem.hidden = true;
			}
		} );

		// A remove that fails keeps the question open, with the reason, so it can be tried again or cancelled.
		var removeProblem = removeModal.querySelector( '[data-field-remove-error]' );
		removeModal.querySelector( '[data-field-remove-confirm]' ).addEventListener( 'click', function () {
			if ( ! pending ) {
				return;
			}
			var id = pending.dataset.fieldId;
			wp.apiFetch( { path: path + '/' + id, method: 'DELETE' } ).then( function ( response ) {
				pending = null;
				draw( response.data.html );
				close( removeModal );
			} ).catch( function ( problem ) {
				removeProblem.textContent = problem && problem.message ? problem.message : removeProblem.dataset.failed;
				removeProblem.hidden = false;
			} );
		} );

		// The dialog's tabs.
		var tabs = Array.prototype.slice.call( modal.querySelectorAll( '[data-field-tab]' ) );
		var show = function ( name ) {
			tabs.forEach( function ( tab ) {
				var on = tab.dataset.fieldTab === name;
				tab.setAttribute( 'aria-selected', on ? 'true' : 'false' );
				tab.tabIndex = on ? 0 : -1;
				modal.querySelector( '[data-field-panel="' + tab.dataset.fieldTab + '"]' ).hidden = ! on;
			} );
			if ( 'library' === name ) {
				library( 1 );
			}
		};
		tabs.forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				show( tab.dataset.fieldTab );
			} );
			tab.addEventListener( 'keydown', function ( event ) {
				if ( 'ArrowRight' === event.key || 'ArrowLeft' === event.key ) {
					var next = tabs[ ( tabs.indexOf( tab ) + 1 ) % tabs.length ];
					next.focus();
					show( next.dataset.fieldTab );
				}
			} );
		} );

		// "Create new".
		var title = modal.querySelector( '[data-field-title]' );
		var error = modal.querySelector( '[data-field-error]' );
		var describe = modal.querySelector( '[data-field-description]' );
		var fail = function ( message ) {
			error.textContent = message;
			error.hidden = '' === message;
			title.classList.toggle( 'is-invalid', '' !== message );
		};
		title.addEventListener( 'input', function () {
			fail( '' );
		} );
		modal.querySelector( '[data-field-create]' ).addEventListener( 'click', function () {
			wp.apiFetch( {
				path: '/affibloom/v1/fields',
				method: 'POST',
				data: { name: title.value, type: modal.querySelector( '[data-field-type]' ).value, description: describe.value },
			} ).then( function () {
				title.value = '';
				describe.value = '';
				fail( '' );
				show( 'library' );
				modal.querySelector( '[data-field-tab="library"]' ).focus();
			} ).catch( function ( problem ) {
				fail( problem && problem.message ? problem.message : '' );
				title.focus();
			} );
		} );

		// "Add from library": ten a page, searched by name.
		var shelf = modal.querySelector( '[data-field-library]' );
		var search = modal.querySelector( '[data-field-search]' );
		var pager = modal.querySelector( '[data-field-pager]' );
		var page = 1;
		var pages = 1;
		var requests = 0;
		var timer = null;

		var library = function ( wanted ) {
			var mine = ++requests;
			wp.apiFetch( { path: '/affibloom/v1/fields?' + new URLSearchParams( { search: search.value, page: String( wanted ) } ).toString() } ).then( function ( response ) {
				if ( mine !== requests ) {
					return; // A newer search has been asked for.
				}
				var data = response.data;
				var attached = rows().map( function ( row ) {
					return row.dataset.fieldId;
				} );
				page = data.page;
				pages = data.pages;
				shelf.replaceChildren();
				data.items.forEach( function ( field ) {
					var item = document.createElement( 'li' );
					var text = document.createElement( 'span' );
					var name = document.createElement( 'strong' );
					var meta = document.createElement( 'span' );
					var add = document.createElement( 'button' );
					var has = -1 !== attached.indexOf( String( field.id ) );
					item.className = 'affibloom-field-library__item';
					text.className = 'affibloom-field-library__text';
					name.textContent = field.name;
					meta.className = 'affibloom-field-library__meta';
					meta.textContent = field.type_label + ( field.description ? ' · ' + field.description : '' );
					add.type = 'button';
					add.className = 'affibloom-button affibloom-button--small ' + ( has ? 'affibloom-button--secondary' : 'affibloom-button--primary' );
					add.textContent = has ? shelf.dataset.addedLabel : shelf.dataset.addText;
					add.disabled = has;
					add.setAttribute( 'aria-label', format( shelf.dataset.addLabel, [ field.name ] ) );
					add.dataset.fieldAdd = String( field.id );
					text.append( name, meta );
					item.append( text, add );
					shelf.appendChild( item );
				} );
				if ( 0 === data.items.length ) {
					var none = document.createElement( 'li' );
					none.className = 'affibloom-field-library__empty';
					none.textContent = shelf.dataset.empty;
					shelf.appendChild( none );
				}
				pager.hidden = pages <= 1;
				pager.querySelector( '[data-field-page="previous"]' ).disabled = page <= 1;
				pager.querySelector( '[data-field-page="next"]' ).disabled = page >= pages;
				var label = pager.querySelector( '[data-field-page-text]' );
				label.textContent = format( label.dataset.format, [ String( page ), String( pages ) ] );
			} );
		};

		search.addEventListener( 'input', function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( function () {
				library( 1 );
			}, 250 );
		} );
		pager.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( '[data-field-page]' );
			if ( button ) {
				library( 'next' === button.dataset.fieldPage ? page + 1 : page - 1 );
			}
		} );
		shelf.addEventListener( 'click', function ( event ) {
			var add = event.target.closest( '[data-field-add]' );
			if ( ! add ) {
				return;
			}
			add.disabled = true;
			wp.apiFetch( { path: path, method: 'POST', data: { field: parseInt( add.dataset.fieldAdd, 10 ) } } ).then( function ( response ) {
				draw( response.data.html );
				close( modal );
			} ).catch( function ( problem ) {
				add.disabled = false;
				failed( problem, box.dataset.addFailed );
			} );
		} );

		// Each opening starts on "Create new", as Lasso's dialog does.
		modal.addEventListener( 'affibloom:open', function () {
			search.value = '';
			show( 'create' );
		} );
	} );
} )();
