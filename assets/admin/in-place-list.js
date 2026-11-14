/* global wp */
/**
 * A list that redraws without a page reload (F26 D15, Groups' D13), taken out of groups.js on its second user, the
 * Fields list (Task FL1).
 *
 * The server renders the list with the partial the page printed and answers `{ html, term, page, url }`; this script
 * swaps the markup in and keeps the address bar in step, so reload, Back and bookmarks work. It never builds markup of
 * its own. Without JavaScript the search is a form and the pages are links.
 *
 * window.affibloomInPlaceList( { region, search, status, errorTemplate, path } ) wires one list:
 *  - region:        the element the list's partial fills;
 *  - search:        the search box, whose form submits the search on Enter;
 *  - status:        a polite live region for what the list now shows;
 *  - errorTemplate: a <template> holding the "could not load" card, with a [data-retry] button;
 *  - path:          the list's REST route, e.g. /affibloom/v1/fields/list.
 */
( function () {
	'use strict';

	window.affibloomInPlaceList = function ( options ) {
		var region = options.region;
		var search = options.search;
		var status = options.status;
		var errorTemplate = options.errorTemplate;
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

			wp.apiFetch( { path: options.path + '?' + args.toString() } ).then( function ( response ) {
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
	};
}() );
