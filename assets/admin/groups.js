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
	 * The group form (Lasso's settings.js 3100-3185): an empty name is refused before anything is sent; a good save
	 * shows "Updating Group", stores the group (POST for a new one, PATCH for an existing one) and opens its Details
	 * tab with "Group saved.".
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
			if ( sending ) {
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
			var id = parseInt( form.dataset.groupId, 10 ) || 0;
			wp.apiFetch( {
				path: id ? '/affibloom/v1/groups/' + id : '/affibloom/v1/groups',
				method: id ? 'PATCH' : 'POST',
				data: { name: name, description: form.elements.namedItem( 'description' ).value },
			} ).then( function ( response ) {
				dialog.close( function () {
					window.location.assign( response.data.redirect_to );
				} );
			} ).catch( fail );
		} );
	}

	/* ---------------------------------------------------------------------------------------------------------
	 * Delete This Group (Lasso's settings.js 3149-3185). A group that shows links opens "Hold Up" and nothing else; the
	 * count is the page's, but the server checks again (D4), so a group filled meanwhile answers 409 and opens it too.
	 * Delete asks first, then goes to the Groups list with "Successfully deleted the Group.".
	 * ------------------------------------------------------------------------------------------------------- */
	var remove = document.querySelector( '[data-group-delete]' );
	var confirmModal = document.getElementById( 'affibloom-group-delete-modal' );
	var holdUp = document.getElementById( 'affibloom-group-holdup' );
	if ( remove && confirmModal && holdUp && window.affibloomModal ) {
		// The button opens the question, or Hold Up straight away when the page knows the group has links.
		remove.addEventListener( 'click', function ( event ) {
			if ( parseInt( remove.dataset.members, 10 ) > 0 ) {
				event.preventDefault();
				event.stopImmediatePropagation();
				window.affibloomModal.show( holdUp, remove );
			}
		}, true );
		remove.setAttribute( 'data-affibloom-open', 'affibloom-group-delete-modal' );

		var confirm = confirmModal.querySelector( '[data-delete-confirm]' );
		var label = confirm.textContent;
		var deleting = false;
		var busy = function ( on ) {
			deleting = on;
			confirm.disabled = on;
			confirm.textContent = on ? confirm.dataset.busyLabel : label;
		};
		confirm.addEventListener( 'click', function () {
			if ( deleting ) {
				return;
			}
			busy( true );
			wp.apiFetch( { path: '/affibloom/v1/groups/' + remove.dataset.groupId, method: 'DELETE' } ).then( function ( response ) {
				window.location.assign( response.data.redirect_to );
			} ).catch( function ( error ) {
				busy( false );
				window.affibloomModal.hide();
				if ( error && 'affibloom_group_in_use' === error.code ) {
					window.affibloomModal.show( holdUp, remove );
				} else if ( window.affibloomToast ) {
					window.affibloomToast( error && error.message ? error.message : confirmModal.dataset.failedText, true );
				}
			} );
		} );
	}

	/* ---------------------------------------------------------------------------------------------------------
	 * A group's Copy Shortcode (F31 R32, Task 18S12): copies its grid shortcode; the tooltip and the label read
	 * "Copied!" for 1.5 seconds, as the Share kit's labels do; a refused copy says so.
	 * ------------------------------------------------------------------------------------------------------- */
	var copyButton = document.querySelector( '[data-copy-group-shortcode]' );
	if ( copyButton && '' !== copyButton.dataset.copyGroupShortcode ) {
		var copyLabel = copyButton.querySelector( '[data-copy-label]' );
		var copyName = copyLabel.textContent;
		var copyTitle = copyButton.title;
		var copyTimer = 0;
		copyButton.addEventListener( 'click', function () {
			window.affibloomCopy( copyButton.dataset.copyGroupShortcode, function () {
				window.clearTimeout( copyTimer );
				copyButton.title = copyButton.dataset.copied;
				copyLabel.textContent = copyButton.dataset.copied;
				copyTimer = window.setTimeout( function () {
					copyButton.title = copyTitle;
					copyLabel.textContent = copyName;
				}, 1500 );
			}, function () {
				window.affibloomToast( copyButton.dataset.copyFailed, true );
			} );
		} );
	}
}() );
