/* global wp */
/**
 * Affibloom Groups (F23, Task G1): the list without page reloads (D13) and the new-group form.
 *
 * As the Dashboard's script does (13D), this file never builds markup of its own: the server renders the list with the
 * partial the page printed, and the script swaps it in and keeps the address bar in step, so reload, Back and bookmarks
 * work. Without JavaScript the search is a form and the pages are links.
 */
( function () {
	'use strict';

	/* ---------------------------------------------------------------------------------------------------------
	 * The list: search on Enter and the page links redraw it in place.
	 * ------------------------------------------------------------------------------------------------------- */
	var region = document.getElementById( 'affibloom-groups-list' );
	if ( region ) {
		var search = document.getElementById( 'affibloom-groups-search' );
		var status = document.getElementById( 'affibloom-list-status' );
		var errorTemplate = document.getElementById( 'affibloom-list-error' );
		var params = new URLSearchParams( window.location.search );
		var current = { s: params.get( 's' ) || '', paged: parseInt( params.get( 'paged' ), 10 ) || 1 };
		var requests = 0;

		var busy = function ( on ) {
			region.classList.toggle( 'is-loading', on );
			region.setAttribute( 'aria-busy', on ? 'true' : 'false' );
		};

		/** Says what the list now shows, for screen readers: the range line, or the empty state's message. */
		var announce = function () {
			var said = region.querySelector( '.affibloom-pager__range, .affibloom-empty__message' );
			status.textContent = said ? said.textContent.trim() : '';
		};

		/**
		 * Loads a list. Only the newest request may draw: a slow answer to an older one is dropped, so searching and
		 * paging quickly cannot leave the list showing something that was not asked for last.
		 *
		 * @param {Object}  list     The search and the page.
		 * @param {boolean} remember Push the result's address into the history (not when answering Back).
		 * @param {boolean} focus    Move the focus to the list (after a page link, which the new list replaces).
		 */
		var load = function ( list, remember, focus ) {
			var mine = ++requests;
			busy( true );
			var args = new URLSearchParams();
			args.set( 'search', list.s );
			args.set( 'page', String( list.paged ) );

			wp.apiFetch( { path: '/affibloom/v1/groups?' + args.toString() } ).then( function ( response ) {
				if ( mine !== requests ) {
					return;
				}
				var data = response.data;
				current = { s: data.term, paged: data.page };
				region.innerHTML = data.html;
				busy( false );
				if ( remember ) {
					window.history.pushState( current, '', data.url );
				}
				// Back and Forward always put the search text back; a search the editor is still typing is left alone.
				if ( search && ( ! remember || document.activeElement !== search ) ) {
					search.value = current.s;
				}
				announce();
				if ( focus ) {
					var card = region.querySelector( '.affibloom-card' );
					if ( card ) {
						card.setAttribute( 'tabindex', '-1' );
						card.focus();
					}
				}
			} ).catch( function () {
				if ( mine !== requests ) {
					return;
				}
				busy( false );
				region.innerHTML = '';
				region.appendChild( errorTemplate.content.cloneNode( true ) );
				region.querySelector( '[data-retry]' ).addEventListener( 'click', function () {
					load( list, remember, focus );
				} );
				announce();
			} );
		};

		if ( search && search.form ) {
			search.form.addEventListener( 'submit', function ( event ) {
				event.preventDefault();
				load( { s: search.value, paged: 1 }, true, false );
			} );
		}

		// Page links. A Ctrl-, Cmd- or Shift-click still opens a new tab or window, as any link does.
		region.addEventListener( 'click', function ( event ) {
			var link = event.target.closest( '.affibloom-pagination a' );
			if ( ! link || 0 !== event.button || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey ) {
				return;
			}
			event.preventDefault();
			load( { s: current.s, paged: parseInt( new URL( link.href ).searchParams.get( 'paged' ), 10 ) || 1 }, true, true );
		} );

		// Back and Forward: draw the list the address names, without pushing it again.
		window.addEventListener( 'popstate', function () {
			var back = new URLSearchParams( window.location.search );
			load( { s: back.get( 's' ) || '', paged: parseInt( back.get( 'paged' ), 10 ) || 1 }, false, false );
		} );
	}

	/* ---------------------------------------------------------------------------------------------------------
	 * The new-group form (Lasso's settings.js 3100-3145): an empty name is refused before anything is sent; a good
	 * save shows "Updating Group", creates the group and opens its Details tab.
	 * ------------------------------------------------------------------------------------------------------- */
	var form = document.getElementById( 'affibloom-group-form' );
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
			// The Details tab is read only until Task G2: it has no field to send.
			if ( ! form.elements.namedItem( 'name' ) || form.elements.namedItem( 'name' ).readOnly || sending ) {
				return;
			}
			errors.clear( form );

			var name = form.elements.namedItem( 'name' ).value.trim();
			if ( '' === name ) {
				errors.show( form, { name: form.dataset.nameRequired } );
				return;
			}

			sending = true;
			dialog.open( form.dataset.savingText, form.dataset.savingTitle );
			wp.apiFetch( {
				path: '/affibloom/v1/groups',
				method: 'POST',
				data: { name: name, description: form.elements.namedItem( 'description' ).value },
			} ).then( function ( response ) {
				dialog.close( function () {
					window.location.assign( response.data.redirect_to );
				} );
			} ).catch( fail );
		} );
	}
}() );
