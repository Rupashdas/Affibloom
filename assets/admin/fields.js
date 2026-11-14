/* global wp */
/**
 * Affibloom Fields (F26): the Fields list without page reloads (Task FL1) and the field form (Task FL2). The list is
 * in-place-list.js's. The form (Lasso's settings.js 3191-3241): an empty title is refused before anything is sent; a good
 * save shows "Updating Field", creates the field and opens the Fields list with the toast. Later tasks add the Details
 * tab's save and the delete dialogs here.
 */
( function () {
	'use strict';

	/* ---------------------------------------------------------------------------------------------------------
	 * The Fields list: search on Enter and the page links redraw it in place.
	 * ------------------------------------------------------------------------------------------------------- */
	var region = document.getElementById( 'affibloom-fields-list' );
	if ( region && window.affibloomInPlaceList ) {
		window.affibloomInPlaceList( {
			region: region,
			search: document.getElementById( 'affibloom-fields-search' ),
			status: document.getElementById( 'affibloom-list-status' ),
			errorTemplate: document.getElementById( 'affibloom-list-error' ),
			path: '/affibloom/v1/fields/list',
		} );
	}

	/* ---------------------------------------------------------------------------------------------------------
	 * The field form.
	 * ------------------------------------------------------------------------------------------------------- */
	var form = document.getElementById( 'affibloom-field-form' );
	if ( form && window.affibloomFieldErrors && window.affibloomSaveDialog ) {
		var errors = window.affibloomFieldErrors;
		var dialog = window.affibloomSaveDialog;
		var sending = false;

		var fail = function ( response ) {
			dialog.hide();
			sending = false;
			var fields = response && response.data && response.data.fields;
			if ( fields ) {
				errors.show( form, fields );
			} else if ( window.affibloomToast ) {
				window.affibloomToast( form.dataset.failedText, true );
			}
		};

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			if ( sending ) {
				return;
			}
			errors.clear( form );

			var name = form.elements.namedItem( 'name' ).value.trim();
			if ( '' === name ) {
				errors.show( form, { name: form.dataset.titleRequired } );
				return;
			}

			sending = true;
			dialog.open( form.dataset.savingText, form.dataset.savingTitle );
			wp.apiFetch( {
				path: '/affibloom/v1/fields',
				method: 'POST',
				data: {
					name: name,
					type: form.elements.namedItem( 'type' ).value,
					description: form.elements.namedItem( 'description' ).value,
				},
			} ).then( function ( response ) {
				dialog.close( function () {
					window.location.assign( response.data.redirect_to );
				} );
			} ).catch( fail );
		} );
	}
}() );
