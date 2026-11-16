/**
 * Settings: Save Changes and Reset Settings. A form that carries `data-rest-save` (its REST path) is sent there behind
 * "Updating Your Site" (admin.js's save dialog), as Lasso sends its settings (settings.js 2379-2464 and 2139-2168;
 * F9 R41), and the page stays where it is, as the product, group and field screens do (the user, 2026-11-16:
 * deviations 141, 143; Lasso reloads). The answer's words show as the toast, and fields the server refused are marked
 * (`#affibloom-setting-<key>`, with its `-error` message when it has one). A failure closes the dialog and shows
 * Lasso's "Try again." as a red toast; the changes stay in the form.
 *
 * The server cleans some values as it stores them (a domain loses its https://, a list its doubles). On a form with
 * `data-refresh-values` the page is fetched again in the background and its text fields take the stored values, as a
 * reload would show them; a refused field keeps what was typed, to be corrected.
 *
 * Events on the form: `affibloom:answer` (the answer, for a screen's own part of it: the Amazon tab's card), then
 * `affibloom:saved` when everything was saved, so the leave guard and Save Changes take the form as saved, or
 * `affibloom:save-failed` when some of it was not. The guard's "Save Changes" then goes where the reader was going, only
 * after a full save and only inside this site's admin. Reset Settings changes every field, so its answer names the
 * address that shows them, and that address is loaded.
 */
( function () {
	'use strict';

	/**
	 * Takes the toast's notice out of the address after a load (Reset Settings, an old bookmark), so reloading the page
	 * does not show the toast again.
	 */
	function cleanAddress() {
		if ( ! window.history || ! window.history.replaceState || ! window.URL ) {
			return;
		}
		var url = new URL( window.location.href );
		if ( ! url.searchParams.has( 'affibloom_notice' ) ) {
			return;
		}
		url.searchParams.delete( 'affibloom_notice' );
		window.history.replaceState( window.history.state, '', url.toString() );
	}

	/**
	 * Where the guard's "Save Changes" goes after the save: an address inside this site's admin, or ''.
	 *
	 * @param {HTMLFormElement} form The saved form.
	 * @return {string} The address.
	 */
	function leaveTo( form ) {
		var target = form.querySelector( 'input[name="redirect_to"]' );
		if ( ! target || ! target.value ) {
			return '';
		}
		var admin = new URL( '.', new URL( window.ajaxurl, window.location.href ) ).href;
		var href = new URL( target.value, window.location.href ).href;
		return 0 === href.indexOf( admin ) ? href : '';
	}

	/**
	 * Clears the marks of the last save, then marks the fields this one refused.
	 *
	 * @param {HTMLFormElement} form    The saved form.
	 * @param {Array<string>}   refused The refused keys.
	 */
	function mark( form, refused ) {
		Array.prototype.forEach.call( form.querySelectorAll( '[data-refused-mark]' ), function ( field ) {
			field.classList.remove( 'is-invalid' );
			field.removeAttribute( 'aria-invalid' );
			field.removeAttribute( 'data-refused-mark' );
		} );
		Array.prototype.forEach.call( form.querySelectorAll( '[data-refused-shown]' ), function ( error ) {
			error.hidden = true;
			if ( error.dataset.live ) {
				error.textContent = error.dataset.live;
			}
			error.removeAttribute( 'data-refused-shown' );
		} );

		refused.forEach( function ( key ) {
			var field = document.getElementById( 'affibloom-setting-' + key );
			var error = document.getElementById( 'affibloom-setting-' + key + '-error' );
			if ( ! field || ! form.contains( field ) ) {
				return;
			}
			field.classList.add( 'is-invalid' );
			if ( 'FIELDSET' !== field.tagName ) {
				field.setAttribute( 'aria-invalid', 'true' );
			}
			field.setAttribute( 'data-refused-mark', '' );
			// A message that only checks the typing (data-live without data-refused) says nothing about the save.
			if ( error && ( error.dataset.refused || ! error.dataset.live ) ) {
				if ( error.dataset.refused ) {
					error.textContent = error.dataset.refused;
				}
				error.hidden = false;
				error.setAttribute( 'data-refused-shown', '' );
			}
		} );
	}

	/**
	 * Gives the form's text fields the values the server stored, from the page as it loads now. A field still marked
	 * refused, a field being typed in, and a script's own field (the whitelist's tags) are left alone.
	 *
	 * @param {HTMLFormElement} form The saved form.
	 * @return {Promise} Settled when done; a failed fetch leaves the form as it is.
	 */
	function refresh( form ) {
		if ( undefined === form.dataset.refreshValues || ! window.fetch || ! window.DOMParser ) {
			return Promise.resolve();
		}
		return window.fetch( window.location.href, { credentials: 'same-origin' } )
			.then( function ( response ) {
				return response.ok ? response.text() : '';
			} )
			.then( function ( html ) {
				var page = new window.DOMParser().parseFromString( html, 'text/html' );
				var fresh = page.querySelector( 'form[data-rest-save="' + form.dataset.restSave + '"]' );
				if ( ! fresh ) {
					return;
				}
				Array.prototype.forEach.call( form.querySelectorAll( 'textarea[name], input[name]' ), function ( field ) {
					var text = 'TEXTAREA' === field.tagName || [ 'text', 'number', 'url', 'email', 'search' ].indexOf( field.type ) !== -1;
					if ( ! text || field === document.activeElement || field.hasAttribute( 'data-refused-mark' ) || field.hasAttribute( 'data-tag-list' ) ) {
						return;
					}
					var stored = fresh.querySelector( '[name="' + field.name + '"]' );
					if ( stored && stored.value !== field.value ) {
						field.value = stored.value;
					}
				} );
			} )
			.catch( function () {} );
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

		window.wp.apiFetch( { path: form.dataset.restSave, method: 'POST', body: window.affibloomFormFields ? window.affibloomFormFields( form ) : new FormData( form ) } )
			.then( function ( answer ) {
				var data = answer && answer.data;
				if ( ! data || 'string' !== typeof data.message ) {
					throw new Error( 'not saved' );
				}
				if ( data.reload ) {
					// The dialog stays up, its bar full, until the page has loaded again.
					modal.querySelector( '[data-save-bar]' ).style.width = '100%';
					window.location.assign( data.reload );
					return;
				}

				delete form.dataset.saving;
				dialog.hide();
				var refused = data.refused || [];
				var target = refused.length || data.failed ? '' : leaveTo( form );
				mark( form, refused );
				form.dispatchEvent( new CustomEvent( 'affibloom:answer', { bubbles: true, detail: data } ) );
				window.affibloomToast( data.message, !! data.failed );
				// Going on to another page: what it shows comes from the server anyway.
				return ( target ? Promise.resolve() : refresh( form ) ).then( function () {
					// Saved, even when the Amazon credentials could not be validated (their warning is the toast).
					form.dispatchEvent( new CustomEvent( refused.length ? 'affibloom:save-failed' : 'affibloom:saved', { bubbles: true } ) );
					if ( target ) {
						window.location.assign( target );
					}
				} );
			} )
			.catch( function () {
				delete form.dataset.saving;
				failed( form );
			} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		cleanAddress();

		if ( ! window.FormData || ! window.URL || ! window.ajaxurl || ! window.wp || ! window.wp.apiFetch || ! window.affibloomSaveDialog || ! window.affibloomSaveDialog.modal() ) {
			return;
		}

		// On the document, so the form's own listeners (Reset's confirmation, the Display tab's desktop layout) run first.
		document.addEventListener( 'submit', function ( event ) {
			var form = event.target;
			if ( ! form.matches( 'form[data-rest-save]' ) || event.defaultPrevented ) {
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
