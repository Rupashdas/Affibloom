/**
 * Settings: the unsaved-changes guard (Task 9E; F9 R44-R46, decision C7). A settings form that carries
 * `data-unsaved-guard` remembers what it held once the page was ready, and while it differs:
 *
 * - closing the tab, or leaving through the browser's own controls, gets the browser's prompt;
 * - following any link opens "Unsaved Changes": × and Escape stay, "Leave Without Saving" goes, "Save Changes" saves
 *   the form and then goes where the link led (the server follows a validated `redirect_to`).
 *
 * Nothing is asked when nothing changed, and a submit (Save Changes, Reset Settings) never asks. Without this script
 * the settings forms work as before.
 */
( function () {
	'use strict';

	/**
	 * What a form would send, as text, so two states can be compared field by field. The order of the fields does not
	 * matter, and an unticked checkbox sends nothing, as in the browser.
	 *
	 * @param {HTMLFormElement} form The form.
	 * @return {string} The state.
	 */
	function snapshot( form ) {
		var entries = [];
		new FormData( form ).forEach( function ( value, name ) {
			// What the guard itself adds is not the editor's change.
			if ( 'redirect_to' !== name && 'string' === typeof value ) {
				entries.push( name + '=' + value );
			}
		} );
		return entries.sort().join( '\n' );
	}

	/**
	 * @param {HTMLFormElement} form The guarded form.
	 * @param {HTMLElement}     modal The "Unsaved Changes" dialog.
	 */
	function guard( form, modal ) {
		var baseline = null;
		var leaving = false;
		var save = document.getElementById( 'affibloom-settings-save' );

		var changed = function () {
			return null !== baseline && snapshot( form ) !== baseline;
		};
		var go = function ( href ) {
			leaving = true;
			window.location.href = href;
		};

		// After every script has set the page up (display-settings.js settles some fields on load).
		window.addEventListener( 'load', function () {
			window.setTimeout( function () {
				baseline = snapshot( form );
			}, 0 );
		} );

		// Submitting is the way to keep the changes (or, for Reset Settings, to drop them on purpose).
		document.addEventListener( 'submit', function () {
			leaving = true;
		} );

		window.addEventListener( 'beforeunload', function ( event ) {
			if ( leaving || ! changed() ) {
				return;
			}
			event.preventDefault();
			event.returnValue = '';
		} );

		// A link that leaves the page: not "#…", "javascript:", a new tab, a modified click, or a link inside a dialog.
		document.addEventListener( 'click', function ( event ) {
			var link = event.target.closest( 'a[href]' );
			if ( ! link || event.defaultPrevented || event.button || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey ) {
				return;
			}
			var href = link.getAttribute( 'href' );
			if ( '' === href || '#' === href.charAt( 0 ) || 0 === href.indexOf( 'javascript:' ) || '_blank' === link.target || link.closest( '.affibloom-modal' ) || ! changed() ) {
				return;
			}
			event.preventDefault();
			modal.dataset.href = link.href;
			window.affibloomModal.show( modal, link );
		} );

		modal.querySelector( '[data-unsaved-leave]' ).addEventListener( 'click', function () {
			window.affibloomModal.hide();
			go( modal.dataset.href );
		} );
		modal.querySelector( '[data-unsaved-save]' ).addEventListener( 'click', function () {
			var target = form.querySelector( 'input[name="redirect_to"]' );
			if ( ! target ) {
				target = document.createElement( 'input' );
				target.type = 'hidden';
				target.name = 'redirect_to';
				form.appendChild( target );
			}
			target.value = modal.dataset.href;
			window.affibloomModal.hide();
			// A disabled submit button cannot send the form, and a change made by a script may not have enabled it.
			if ( save ) {
				save.disabled = false;
			}
			form.requestSubmit( save || undefined );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var form = document.querySelector( 'form[data-unsaved-guard]' );
		var modal = document.querySelector( '[data-unsaved-modal]' );
		if ( form && modal && window.affibloomModal ) {
			guard( form, modal );
		}
	} );
} )();
