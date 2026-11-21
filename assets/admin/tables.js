/* global wp */
/**
 * The Tables list (F25 R8, D16; Task T1): search on Enter and the page links redraw the list in place through
 * GET /tables (in-place-list.js), keeping `s` and `paged` in the address, as Lasso's list does.
 *
 * Create Table (R12; Task T2): an empty name is refused without a request; otherwise POST /tables, a busy button, and
 * the address the answer names; a refusal shows the server's words and gives the button back. `&create=1` (the
 * editor's link) opens the dialog and leaves the address.
 *
 * Table Locations (R40, D13; Task T10): a count above 0 opens the dialog with GET /tables/{id}/locations, six posts a
 * page (title to its editor, address, view); each table's page is kept in this browser (localStorage, per viewer).
 *
 * Clone (R10, D8; Task T11): "Do you want to clone <name>" → POST /tables/{id}/clone → the list's last page, where
 * "<name> - Copy" is.
 */
( function () {
	'use strict';

	var dialog = document.getElementById( 'affibloom-table-create' );
	if ( dialog ) {
		var form = dialog.querySelector( '[data-table-create]' );
		var name = form.querySelector( '[name="title"]' );
		var error = form.querySelector( '.affibloom-field__error' );
		var submit = form.querySelector( '[data-table-create-submit]' );
		var label = submit.textContent;
		var say = function ( text ) {
			error.textContent = text;
			error.hidden = '' === text;
			name.setAttribute( 'aria-invalid', '' === text ? 'false' : 'true' );
		};
		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			if ( '' === name.value.trim() ) {
				say( form.getAttribute( 'data-needs-name' ) );
				name.focus();
				return;
			}
			say( '' );
			submit.disabled = true;
			submit.textContent = submit.getAttribute( 'data-busy-label' );
			wp.apiFetch( { path: '/affibloom/v1/tables', method: 'POST', data: { title: name.value, style: form.querySelector( '[name="style"]:checked' ).value } } )
				.then( function ( answer ) {
					window.location.assign( answer.data.redirect_to );
				} )
				.catch( function ( failure ) {
					var fields = failure && failure.data && failure.data.fields;
					say( ( fields && fields.title ) || ( failure && failure.message ) || form.getAttribute( 'data-failed' ) );
					submit.disabled = false;
					submit.textContent = label;
				} );
		} );
		dialog.addEventListener( 'affibloom:close', function () {
			say( '' );
		} );
		var params = new URLSearchParams( window.location.search );
		if ( '1' === params.get( 'create' ) ) {
			params.delete( 'create' );
			window.history.replaceState( null, '', window.location.pathname + '?' + params.toString() );
			window.affibloomModal.show( dialog, document.querySelector( '[data-affibloom-open="affibloom-table-create"]' ) || document.body );
		}
	}

	var places = document.getElementById( 'affibloom-table-locations' );
	if ( places ) {
		var rows = places.querySelector( '[data-locations-rows]' );
		var pager = places.querySelector( '[data-locations-pages]' );
		var problem = places.querySelector( '[data-locations-error]' );
		var STORE = 'affibloom_table_locations';
		var remembered = function () {
			try {
				return JSON.parse( window.localStorage.getItem( STORE ) || '{}' ) || {};
			} catch ( e ) {
				return {};
			}
		};
		var remember = function ( table, page ) {
			try {
				var pages = remembered();
				pages[ table ] = page;
				window.localStorage.setItem( STORE, JSON.stringify( pages ) );
			} catch ( e ) {
				// Without storage the dialog starts on its first page.
			}
		};
		var link = function ( href, text, label ) {
			var anchor = document.createElement( 'a' );
			anchor.href = href;
			anchor.target = '_blank';
			anchor.rel = 'noopener';
			anchor.textContent = text;
			if ( label ) {
				anchor.setAttribute( 'aria-label', label );
			}
			return anchor;
		};
		var load = function ( table, page ) {
			problem.hidden = true;
			rows.innerHTML = '';
			rows.setAttribute( 'aria-busy', 'true' );
			wp.apiFetch( { path: '/affibloom/v1/tables/' + table + '/locations?page=' + page } ).then( function ( answer ) {
				var data = answer.data;
				rows.removeAttribute( 'aria-busy' );
				remember( table, data.page );
				data.items.forEach( function ( post ) {
					var tr = document.createElement( 'tr' );
					var content = document.createElement( 'td' );
					var title = link( post.edit_url, post.title );
					title.className = 'affibloom-table-locations__title';
					var address = link( post.url, post.url );
					address.className = 'affibloom-table-locations__url';
					content.append( title, address );
					var action = document.createElement( 'td' );
					action.className = 'affibloom-table-locations__action';
					var view = link( post.url, '', places.dataset.viewLabel.replace( '%s', post.title ) );
					view.className = 'affibloom-icon-button';
					view.innerHTML = '<svg viewBox="0 0 20 20" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M2.5 10s2.75-5 7.5-5 7.5 5 7.5 5-2.75 5-7.5 5-7.5-5-7.5-5z"/><circle cx="10" cy="10" r="2.25"/></svg>';
					action.appendChild( view );
					tr.append( content, action );
					rows.appendChild( tr );
				} );
				pager.innerHTML = '';
				pager.hidden = data.pages < 2;
				[ [ -1, places.dataset.prevLabel, data.page > 1 ], [ 1, places.dataset.nextLabel, data.page < data.pages ] ].forEach( function ( step ) {
					var button = document.createElement( 'button' );
					button.type = 'button';
					button.className = 'affibloom-button affibloom-button--small affibloom-button--secondary';
					button.textContent = step[ 1 ];
					button.disabled = ! step[ 2 ];
					button.addEventListener( 'click', function () {
						load( table, data.page + step[ 0 ] );
					} );
					pager.appendChild( button );
				} );
				var where = document.createElement( 'span' );
				where.textContent = data.page + ' / ' + data.pages;
				pager.insertBefore( where, pager.lastChild );
			} ).catch( function () {
				rows.removeAttribute( 'aria-busy' );
				problem.textContent = places.dataset.failed;
				problem.hidden = false;
			} );
		};
		document.addEventListener( 'click', function ( event ) {
			var opener = event.target.closest( '[data-table-locations]' );
			if ( opener ) {
				var table = opener.getAttribute( 'data-table-locations' );
				load( table, remembered()[ table ] || 1 );
				window.affibloomModal.show( places, opener );
			}
		} );
	}

	var region = document.getElementById( 'affibloom-tables-list' );
	if ( ! region || ! window.affibloomInPlaceList ) {
		return;
	}
	var list = window.affibloomInPlaceList( {
		region: region,
		search: document.getElementById( 'affibloom-tables-search' ),
		status: document.getElementById( 'affibloom-list-status' ),
		errorTemplate: document.getElementById( 'affibloom-list-error' ),
		path: region.getAttribute( 'data-list-path' ),
		args: {},
	} );

	// Clone (T11).
	var cloner = document.getElementById( 'affibloom-table-clone' );
	var cloneConfirm = cloner.querySelector( '[data-table-clone-confirm]' );
	var cloning = 0;
	region.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '[data-table-clone]' );
		if ( button ) {
			cloning = parseInt( button.getAttribute( 'data-table-clone' ), 10 );
			cloner.querySelector( '.affibloom-modal__title' ).textContent = cloner.dataset.question.replace( '%s', button.getAttribute( 'data-table-name' ) );
			window.affibloomModal.show( cloner, button );
		}
	} );
	cloneConfirm.addEventListener( 'click', function () {
		var label = cloneConfirm.textContent;
		cloneConfirm.disabled = true;
		cloneConfirm.textContent = cloneConfirm.getAttribute( 'data-busy-label' );
		var restore = function () {
			cloneConfirm.disabled = false;
			cloneConfirm.textContent = label;
		};
		wp.apiFetch( { path: '/affibloom/v1/tables/' + cloning + '/clone', method: 'POST' } ).then( function ( answer ) {
			restore();
			window.affibloomModal.hide();
			list.show( answer.data.page );
		} ).catch( function ( failure ) {
			restore();
			window.affibloomModal.hide();
			window.affibloomToast( ( failure && failure.message ) || cloner.dataset.failed, true );
		} );
	} );
}() );
