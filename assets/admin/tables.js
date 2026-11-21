/* global wp */
/**
 * The Tables list (F25 R8, D16; Task T1): search on Enter and the page links redraw the list in place through
 * GET /tables (in-place-list.js), keeping `s` and `paged` in the address, as Lasso's list does.
 *
 * Create Table (R12; Task T2): an empty name is refused without a request; otherwise POST /tables, a busy button, and
 * the address the answer names; a refusal shows the server's words and gives the button back. `&create=1` (the
 * editor's link) opens the dialog and leaves the address.
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

	var region = document.getElementById( 'affibloom-tables-list' );
	if ( ! region || ! window.affibloomInPlaceList ) {
		return;
	}
	window.affibloomInPlaceList( {
		region: region,
		search: document.getElementById( 'affibloom-tables-search' ),
		status: document.getElementById( 'affibloom-list-status' ),
		errorTemplate: document.getElementById( 'affibloom-list-error' ),
		path: region.getAttribute( 'data-list-path' ),
		args: {},
	} );
}() );
