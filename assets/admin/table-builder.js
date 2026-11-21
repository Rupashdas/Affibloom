/* global wp */
/**
 * A table's builder (F25 R13-R16, D4, D5; Task T3; Lasso's table-details.js): every setting saves as it changes, as
 * Lasso's builder does. The heading follows the name while typing; the name saves when it changes and is not empty;
 * Style, Theme and the two switches save at once. One request at a time: a change made while one is out waits and is
 * sent with the next. Each answer carries the new revision and the builder drawn again; the head says Saving, then
 * Saved. A table with no id yet (no `id` in the address) is created by naming it, then the page moves to its builder.
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
	var pending = {};
	var busy = false;
	var saved = name.value;

	var say = function ( text ) {
		error.textContent = text;
		error.hidden = '' === text;
	};

	var send = function () {
		if ( busy || 0 === Object.keys( pending ).length ) {
			return;
		}
		var changes = pending;
		pending = {};
		busy = true;
		status.textContent = page.getAttribute( 'data-saving' );
		say( '' );
		var request = id
			? wp.apiFetch( {
				path: '/affibloom/v1/tables/' + id,
				method: 'PATCH',
				data: Object.assign( { revision: parseInt( page.getAttribute( 'data-revision' ), 10 ) || 0 }, changes ),
			} )
			: wp.apiFetch( { path: '/affibloom/v1/tables', method: 'POST', data: { title: changes.title || name.value, style: style.value } } );
		request
			.then( function ( answer ) {
				if ( ! id ) {
					window.location.assign( answer.data.redirect_to );
					return;
				}
				page.setAttribute( 'data-revision', String( answer.data.revision ) );
				region.innerHTML = answer.data.html;
				saved = answer.data.title;
				status.textContent = page.getAttribute( 'data-saved' );
				busy = false;
				send();
			} )
			.catch( function ( failure ) {
				var fields = failure && failure.data && failure.data.fields;
				busy = false;
				pending = {};
				status.textContent = '';
				say( ( fields && fields.title ) || ( failure && failure.message ) || page.getAttribute( 'data-failed' ) );
			} );
	};

	var save = function ( field, value ) {
		pending[ field ] = value;
		send();
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
}() );
