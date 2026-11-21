/* global wp */
/**
 * Lasso's "Add Field" dialog (modals/field-create.php; templates/admin/partials/field-modal.php): the tabs, "Create new"
 * (stores a field, then opens the library) and "Add from library" (GET /fields, searched by name, a page at a time).
 * Taken out of product-fields.js (F1, Task 14C) on its second use, the table builder (F25 R26-R27, Task T6).
 *
 * window.affibloomFieldDialog( options ) returns { show( tab ) }:
 * - options.modal: the dialog.
 * - options.start: the tab each opening starts on ('create' on the product page, 'library' in a table).
 * - options.query(): more arguments for GET /fields (the table's `for`, `per_page` and `exclude[]`).
 * - options.has( field ): whether the field is already there ("Added", disabled).
 * - options.add( field, button ): the caller adds it; it gives the button back on a refusal.
 * - options.findCreated: after Create Field the library is searched for the new title (Lasso's table dialog).
 */
( function () {
	'use strict';

	var format = function ( template, values ) {
		return values.reduce( function ( text, value, index ) {
			return text.replace( '%' + ( index + 1 ) + '$s', value ).replace( '%s', value );
		}, template );
	};

	window.affibloomFieldDialog = function ( options ) {
		var modal = options.modal;

		// "Add from library".
		var shelf = modal.querySelector( '[data-field-library]' );
		var search = modal.querySelector( '[data-field-search]' );
		var pager = modal.querySelector( '[data-field-pager]' );
		var page = 1;
		var pages = 1;
		var requests = 0;
		var timer = null;

		var library = function ( wanted ) {
			var mine = ++requests;
			var args = new URLSearchParams( { search: search.value, page: String( wanted ) } );
			var more = options.query ? options.query() : {};
			Object.keys( more ).forEach( function ( key ) {
				( Array.isArray( more[ key ] ) ? more[ key ] : [ more[ key ] ] ).forEach( function ( value ) {
					args.append( Array.isArray( more[ key ] ) ? key + '[]' : key, String( value ) );
				} );
			} );
			// Until the answer comes, the list shows the rows' shapes (design system, "Skeleton").
			shelf.replaceChildren();
			window.affibloomSkeleton( shelf, 4, false );
			wp.apiFetch( { path: '/affibloom/v1/fields?' + args.toString() } ).then( function ( response ) {
				if ( mine !== requests ) {
					return; // A newer search has been asked for.
				}
				window.affibloomSkeleton.done( shelf );
				var data = response.data;
				page = data.page;
				pages = data.pages;
				shelf.replaceChildren();
				data.items.forEach( function ( field ) {
					var item = document.createElement( 'li' );
					var text = document.createElement( 'span' );
					var name = document.createElement( 'strong' );
					var meta = document.createElement( 'span' );
					var add = document.createElement( 'button' );
					var has = options.has ? options.has( field ) : false;
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
			} ).catch( function () {
				if ( mine !== requests ) {
					return;
				}
				// A failed load says so instead of leaving the shapes; searching or paging asks again.
				window.affibloomSkeleton.done( shelf );
				var failed = document.createElement( 'li' );
				failed.className = 'affibloom-field-library__empty';
				failed.setAttribute( 'role', 'alert' );
				failed.textContent = shelf.dataset.failed;
				shelf.replaceChildren( failed );
			} );
		};

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
			var create = this;
			if ( create.disabled ) {
				return;
			}
			create.disabled = true;
			create.classList.add( 'is-busy' );
			var idle = function () {
				create.disabled = false;
				create.classList.remove( 'is-busy' );
			};
			var created = title.value;
			wp.apiFetch( {
				path: '/affibloom/v1/fields',
				method: 'POST',
				data: { name: title.value, type: modal.querySelector( '[data-field-type]' ).value, description: describe.value },
			} ).then( function () {
				idle();
				title.value = '';
				describe.value = '';
				fail( '' );
				search.value = options.findCreated ? created : '';
				show( 'library' );
				modal.querySelector( '[data-field-tab="library"]' ).focus();
			} ).catch( function ( problem ) {
				idle();
				fail( problem && problem.message ? problem.message : '' );
				title.focus();
			} );
		} );

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
			if ( add ) {
				add.disabled = true;
				options.add( parseInt( add.dataset.fieldAdd, 10 ), add );
			}
		} );

		modal.addEventListener( 'affibloom:open', function () {
			search.value = '';
			show( options.start );
		} );

		return { show: show };
	};
}() );
