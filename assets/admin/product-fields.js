/* global wp */
/**
 * Custom fields on the product page (F1, Task 14C). Every change is stored as it is made, as Lasso's page does: a value
 * when it changes (on leaving its box), a switch when it is turned, the order when a row is dragged, by pointer or Arrow keys (Task FL5);
 * adding and removing a field redraw the rows. A value the server refuses is named under its row (Task FL5). The "Add Field" dialog is
 * field-dialog.js's; this file adds the field chosen. Opening and closing the dialogs is admin.js's.
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

		/**
		 * The words under a row's box: the server's refusal ("rows.<field>"), or none.
		 *
		 * @param {HTMLElement} row     The row.
		 * @param {string}      message The words; '' clears them.
		 */
		var rowError = function ( row, message ) {
			var note = row.querySelector( '[data-field-value-error]' );
			var input = row.querySelector( '[data-field-value]' );
			var described = ( input.getAttribute( 'aria-describedby' ) || '' ).split( ' ' ).filter( function ( id ) {
				return id && id !== note.id;
			} );
			note.textContent = message;
			note.hidden = '' === message;
			input.classList.toggle( 'is-invalid', '' !== message );
			if ( '' === message ) {
				input.removeAttribute( 'aria-invalid' );
			} else {
				input.setAttribute( 'aria-invalid', 'true' );
				described.push( note.id );
			}
			if ( described.length ) {
				input.setAttribute( 'aria-describedby', described.join( ' ' ) );
			} else {
				input.removeAttribute( 'aria-describedby' );
			}
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
			} ).then( function () {
				rows().forEach( function ( row ) {
					rowError( row, '' );
				} );
			} ).catch( function ( problem ) {
				// A refused value (over 1,000 characters) is named under its row; nothing was stored.
				var fields = problem && problem.data && problem.data.fields ? problem.data.fields : {};
				var named = rows().filter( function ( row ) {
					var message = fields[ 'rows.' + row.dataset.fieldId ] || '';
					rowError( row, message );
					return '' !== message;
				} );
				if ( 0 === named.length ) {
					failed( problem, box.dataset.saveFailed );
				}
			} );
		};

		// Dragging a row by its grip, or Arrow Up / Down on it (drag-rows.js); Primary Rating has none, so it stays first.
		window.affibloomDragRows( list, {
			row: '[data-field-row]',
			movable: function () {
				return rows().filter( function ( row ) {
					return row.querySelector( '[data-grip]' );
				} );
			},
			dropped: save,
		} );

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
			var remove = event.target.closest( '[data-field-remove]' );
			var row = event.target.closest( '[data-field-row]' );
			if ( remove ) {
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
			var confirm = this;
			if ( ! pending || confirm.disabled ) {
				return;
			}
			var id = pending.dataset.fieldId;
			// Busy while it is asked (design system, "State chip"): one click, one request.
			confirm.disabled = true;
			confirm.classList.add( 'is-busy' );
			var idle = function () {
				confirm.disabled = false;
				confirm.classList.remove( 'is-busy' );
			};
			wp.apiFetch( { path: path + '/' + id, method: 'DELETE' } ).then( function ( response ) {
				idle();
				pending = null;
				draw( response.data.html );
				close( removeModal );
			} ).catch( function ( problem ) {
				idle();
				removeProblem.textContent = problem && problem.message ? problem.message : removeProblem.dataset.failed;
				removeProblem.hidden = false;
			} );
		} );

		// The "Add Field" dialog (field-dialog.js): each opening starts on "Create new", as Lasso's dialog does; the
		// library marks the fields the product has.
		window.affibloomFieldDialog( {
			modal: modal,
			start: 'create',
			has: function ( field ) {
				return rows().some( function ( row ) {
					return row.dataset.fieldId === String( field.id );
				} );
			},
			add: function ( field, button ) {
				wp.apiFetch( { path: path, method: 'POST', data: { field: field } } ).then( function ( response ) {
					draw( response.data.html );
					close( modal );
				} ).catch( function ( problem ) {
					button.disabled = false;
					failed( problem, box.dataset.addFailed );
				} );
			},
		} );
	} );
} )();
