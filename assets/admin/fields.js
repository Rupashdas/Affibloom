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
	 * The field form: a new field is created (POST /fields) and the Fields list opens with the toast; an existing field's
	 * Details are saved in place (PATCH /fields/{id}) with the toast and the new name in the heading (F26 D19). Delete
	 * This Field asks, then deletes and opens the list; a field a product has answers 409 and "Hold Up" opens.
	 * ------------------------------------------------------------------------------------------------------- */
	var form = document.getElementById( 'affibloom-field-form' );
	if ( form && window.affibloomFieldErrors && window.affibloomSaveDialog ) {
		var errors = window.affibloomFieldErrors;
		var dialog = window.affibloomSaveDialog;
		var id = form.dataset.fieldId || '';
		var sending = false;

		var fail = function ( response ) {
			dialog.hide();
			sending = false;
			var fields = response && response.data && response.data.fields;
			if ( fields ) {
				errors.show( form, fields );
			} else if ( window.affibloomToast ) {
				window.affibloomToast( response && response.message ? response.message : form.dataset.failedText, true );
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

			// A built-in field's type and description are locked (disabled): only its title is sent, as the server takes only it.
			var data = { name: name };
			[ 'type', 'description' ].forEach( function ( key ) {
				var control = form.elements.namedItem( key );
				if ( ! control.disabled ) {
					data[ key ] = control.value;
				}
			} );

			sending = true;
			dialog.open( form.dataset.savingText, form.dataset.savingTitle );
			wp.apiFetch( {
				path: '/affibloom/v1/fields' + ( id ? '/' + id : '' ),
				method: id ? 'PATCH' : 'POST',
				data: data,
			} ).then( function ( response ) {
				dialog.close( function () {
					sending = false;
					if ( ! id ) {
						window.location.assign( response.data.redirect_to );
						return;
					}
					// The tab stays: the toast, and the new title in the heading.
					window.affibloomToast( form.dataset.savedText, false );
					var heading = document.getElementById( 'affibloom-field-title' );
					if ( heading ) {
						heading.textContent = response.data.name;
					}
					form.elements.namedItem( 'name' ).value = response.data.name;
				} );
			} ).catch( fail );
		} );

		// Delete This Field: the question's Delete button (delete-modal.php, found by data-field-delete-confirm).
		var question = document.getElementById( 'affibloom-field-delete' );
		var holdUp = document.getElementById( 'affibloom-field-holdup' );
		var confirmButton = question ? question.querySelector( '[data-field-delete-confirm]' ) : null;
		if ( id && confirmButton && holdUp ) {
			confirmButton.addEventListener( 'click', function () {
				if ( sending ) {
					return;
				}
				sending = true;
				window.affibloomModal.hide();
				dialog.open( form.dataset.deletingText, form.dataset.deletingTitle );
				wp.apiFetch( { path: '/affibloom/v1/fields/' + id, method: 'DELETE' } ).then( function ( response ) {
					dialog.close( function () {
						window.location.assign( response.data.redirect_to );
					} );
				} ).catch( function ( problem ) {
					dialog.hide();
					sending = false;
					// A product has the field since the page was drawn: Lasso's "Hold Up", which its page never showed.
					if ( problem && 'affibloom_field_in_use' === problem.code ) {
						window.affibloomModal.show( holdUp, document.querySelector( '[data-affibloom-open="affibloom-field-delete"]' ) );
					} else {
						window.affibloomToast( problem && problem.message ? problem.message : question.dataset.failedText, true );
					}
				} );
			} );
		}
	}
}() );
