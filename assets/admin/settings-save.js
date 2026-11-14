/**
 * Settings: Save Changes and Reset Settings as Lasso does them (settings.js 2379-2464 and 2139-2168; F9 R41). A form
 * that carries `data-ajax-save` is sent to admin-ajax.php behind "Updating Your Site" (admin.js's save dialog) instead
 * of leaving the page; the server answers with the address to load (SaveAnswer), and that address is loaded, so the
 * page comes back as it was with the toast. A failure closes the dialog and shows Lasso's "Try again." as a red toast;
 * the changes stay in the form.
 *
 * After the load the notice is taken out of the address, so the page's address is the one the editor was on and
 * reloading it does not show the toast again. Without this script the forms post to admin-post.php as before.
 */
( function () {
	'use strict';

	// What the server puts in the address for the toast and the refused fields; read once by the page, then dropped.
	var ONE_TIME = [ 'affibloom_notice', 'refused' ];

	function cleanAddress() {
		if ( ! window.history || ! window.history.replaceState || ! window.URL ) {
			return;
		}
		var url = new URL( window.location.href );
		var had = ONE_TIME.filter( function ( name ) {
			return url.searchParams.has( name );
		} );
		if ( ! had.length ) {
			return;
		}
		had.forEach( function ( name ) {
			url.searchParams.delete( name );
		} );
		window.history.replaceState( window.history.state, '', url.toString() );
	}

	/**
	 * @param {HTMLFormElement} form The form that was being saved.
	 */
	function failed( form ) {
		window.affibloomSaveDialog.hide();
		window.affibloomToast( form.dataset.failedText, true );
		// The leave guard watches for this: the changes are still unsaved (settings-guard.js).
		form.dispatchEvent( new CustomEvent( 'affibloom:save-failed', { bubbles: true } ) );
	}

	/**
	 * @param {HTMLFormElement} form The form to send.
	 */
	function send( form ) {
		var dialog = window.affibloomSaveDialog;
		var modal = dialog.modal();
		form.dataset.saving = '1';
		dialog.open( modal.dataset.textToggles, form.dataset.saveTitle );

		window.fetch( window.ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: new FormData( form ),
		} )
			.then( function ( response ) {
				return response.ok ? response.json() : null;
			} )
			.then( function ( answer ) {
				if ( ! answer || ! answer.success || ! answer.data || ! answer.data.redirect_to ) {
					throw new Error( 'not saved' );
				}
				// The dialog stays up, its bar full, until the page has loaded again (Lasso reloads the same way).
				modal.querySelector( '[data-save-bar]' ).style.width = '100%';
				window.location.assign( answer.data.redirect_to );
			} )
			.catch( function () {
				delete form.dataset.saving;
				failed( form );
			} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		cleanAddress();

		if ( ! window.fetch || ! window.FormData || ! window.ajaxurl || ! window.affibloomSaveDialog || ! window.affibloomSaveDialog.modal() ) {
			return;
		}

		// On the document, so the form's own listeners (Reset's confirmation, the Display tab's desktop layout) run first.
		document.addEventListener( 'submit', function ( event ) {
			var form = event.target;
			if ( ! form.matches( 'form[data-ajax-save]' ) || event.defaultPrevented ) {
				return;
			}
			event.preventDefault();
			// A second click while saving does nothing.
			if ( ! form.dataset.saving ) {
				send( form );
			}
		} );
	} );
} )();
