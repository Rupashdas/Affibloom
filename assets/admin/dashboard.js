/* global wp */
/**
 * Affibloom Dashboard: Lasso's selection (Task 13C) and the list without page reloads (Task 13D).
 *
 * Selecting works as Lasso's does (view-js.php, dashboard_selected_ids): a row's box, the page's box, and the
 * bar that appears with the first tick, with Select All (every product in the list, on every page) and Clear
 * Selection. Delete and Add to Group arrive with Task 17 and the Groups area.
 *
 * Every listener is on the list region, not on the rows, so the rows can be replaced (Task 13D) and the
 * selection survives: "all" is kept as a mode with the rows unticked since, not as a list of IDs.
 */
( function () {
	'use strict';

	var region = document.getElementById( 'affibloom-list' );
	var bar = document.querySelector( '.affibloom-selection' );
	if ( ! region || ! bar ) {
		return;
	}

	var countLabel = bar.querySelector( '.affibloom-selection__count' );
	var selectAll = bar.querySelector( '[data-select-all]' );

	var selection = {
		all: false, // Select All: every product in the list.
		ids: {}, // Ticked rows, when not "all".
		except: {}, // Rows unticked after Select All.
	};

	/** Products behind the list, from the card the server rendered. */
	function total() {
		var card = region.querySelector( '[data-total]' );
		return card ? parseInt( card.getAttribute( 'data-total' ), 10 ) || 0 : 0;
	}

	function isSelected( id ) {
		return selection.all ? ! selection.except[ id ] : !! selection.ids[ id ];
	}

	function count() {
		return selection.all ? total() - Object.keys( selection.except ).length : Object.keys( selection.ids ).length;
	}

	function rowBoxes() {
		return Array.prototype.slice.call( region.querySelectorAll( 'tbody .affibloom-check' ) );
	}

	/** Makes the boxes, the rows and the bar say what the selection is. */
	function sync() {
		var boxes = rowBoxes();
		var ticked = 0;
		boxes.forEach( function ( box ) {
			var on = isSelected( box.value );
			box.checked = on;
			box.closest( 'tr' ).classList.toggle( 'is-selected', on );
			ticked += on ? 1 : 0;
		} );

		var page = region.querySelector( '[data-select-page]' );
		if ( page ) {
			page.checked = boxes.length > 0 && ticked === boxes.length;
			page.indeterminate = ticked > 0 && ticked < boxes.length;
		}

		var n = count();
		bar.hidden = 0 === n;
		countLabel.textContent = countLabel.getAttribute( 'data-template' ).replace( '%s', n.toLocaleString() );
		selectAll.hidden = selection.all && 0 === Object.keys( selection.except ).length;
	}

	function set( id, on ) {
		if ( selection.all ) {
			if ( on ) {
				delete selection.except[ id ];
			} else {
				selection.except[ id ] = true;
			}
		} else if ( on ) {
			selection.ids[ id ] = true;
		} else {
			delete selection.ids[ id ];
		}
	}

	function clear() {
		selection.all = false;
		selection.ids = {};
		selection.except = {};
		sync();
	}

	region.addEventListener( 'change', function ( event ) {
		var box = event.target;
		if ( ! box.classList || ! box.classList.contains( 'affibloom-check' ) ) {
			return;
		}
		if ( box.hasAttribute( 'data-select-page' ) ) {
			rowBoxes().forEach( function ( row ) {
				set( row.value, box.checked );
			} );
		} else {
			set( box.value, box.checked );
		}
		sync();
	} );

	selectAll.addEventListener( 'click', function () {
		selection.all = true;
		selection.ids = {};
		selection.except = {};
		sync();
	} );

	bar.querySelector( '[data-clear-selection]' ).addEventListener( 'click', clear );

	// Buttons whose action comes later stay focusable (so "Coming soon" is announced) but do nothing.
	document.addEventListener( 'click', function ( event ) {
		if ( event.target.closest( '.affibloom-dashboard [aria-disabled="true"]' ) ) {
			event.preventDefault();
		}
	} );

	sync();

	/*
	 * The list without page reloads (Task 13D), as Lasso's is an AJAX view. The server renders the list region with
	 * the same partial the page printed and sends the screen's own address for it, which goes into the browser's
	 * history: reload, Back and bookmarks keep working, and without JavaScript every control still submits.
	 */
	var search = document.getElementById( 'affibloom-search' );
	var status = document.getElementById( 'affibloom-list-status' );
	var errorTemplate = document.getElementById( 'affibloom-list-error' );
	var params = new URLSearchParams( window.location.search );
	var sizeSelect = document.getElementById( 'affibloom-per-page' );
	var current = {
		s: params.get( 's' ) || '',
		paged: parseInt( params.get( 'paged' ), 10 ) || 1,
		// The size the page was printed with (remembered per editor), so the first search keeps it.
		per_page: sizeSelect ? parseInt( sizeSelect.value, 10 ) : 0,
	};
	var requests = 0;

	function query( list ) {
		var args = new URLSearchParams();
		args.set( 's', list.s );
		args.set( 'paged', String( list.paged ) );
		args.set( 'per_page', String( list.per_page ) );
		return '/affibloom/v1/dashboard/products?' + args.toString();
	}

	function busy( on ) {
		region.classList.toggle( 'is-loading', on );
		region.setAttribute( 'aria-busy', on ? 'true' : 'false' );
	}

	/** Says what the list now shows, for screen readers: the range line, or the empty state's message. */
	function announce() {
		var said = region.querySelector( '.affibloom-pager__range, .affibloom-empty__message' );
		status.textContent = said ? said.textContent.trim() : '';
	}

	/**
	 * Loads a list. Only the newest request may draw: a slow answer to an older one is dropped, so typing a search
	 * and paging quickly cannot leave the list showing something that was not asked for last.
	 *
	 * @param {Object}  list     The search, page and size.
	 * @param {boolean} remember Push the result's address into the history (not when answering Back).
	 * @param {boolean} focus    Move the focus to the list (after a page link, which the new list replaces).
	 */
	function load( list, remember, focus ) {
		var mine = ++requests;
		busy( true );

		wp.apiFetch( { path: query( list ) } ).then( function ( response ) {
			if ( mine !== requests ) {
				return;
			}
			var data = response.data;
			var newSearch = data.term !== current.s;
			current = { s: data.term, paged: data.page, per_page: data.per_page };

			region.innerHTML = data.html;
			busy( false );
			if ( remember ) {
				window.history.pushState( current, '', data.url );
			}
			if ( newSearch ) {
				clear(); // A different list: Lasso keeps only the selected rows that are still in it.
			} else {
				sync();
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
	}

	// Search on Enter, as Lasso's box does; the page goes back to 1.
	if ( search && search.form ) {
		search.form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			load( { s: search.value, paged: 1, per_page: current.per_page }, true, false );
		} );
	}

	// Page links.
	region.addEventListener( 'click', function ( event ) {
		var link = event.target.closest( '.affibloom-pagination a' );
		// A Ctrl-, Cmd- or Shift-click still opens the page in a new tab or window, as any link does.
		if ( ! link || 0 !== event.button || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey ) {
			return;
		}
		event.preventDefault();
		var paged = parseInt( new URL( link.href ).searchParams.get( 'paged' ), 10 ) || 1;
		load( { s: current.s, paged: paged, per_page: current.per_page }, true, true );
	} );

	// The page size: a new size starts again at page 1, as Lasso's does.
	region.addEventListener( 'change', function ( event ) {
		if ( 'affibloom-per-page' !== event.target.id ) {
			return;
		}
		load( { s: current.s, paged: 1, per_page: parseInt( event.target.value, 10 ) }, true, false );
	} );
	region.addEventListener( 'submit', function ( event ) {
		if ( event.target.closest( '.affibloom-pager__size' ) ) {
			event.preventDefault(); // The Apply button only shows without JavaScript; Enter on the select still lands here.
		}
	} );

	// Back and Forward: draw the list the address names, without pushing it again.
	window.addEventListener( 'popstate', function () {
		var back = new URLSearchParams( window.location.search );
		if ( search ) {
			search.value = back.get( 's' ) || '';
		}
		load( {
			s: back.get( 's' ) || '',
			paged: parseInt( back.get( 'paged' ), 10 ) || 1,
			per_page: parseInt( back.get( 'per_page' ), 10 ) || 0,
		}, false, false );
	} );

	/*
	 * Ctrl+Shift+L (Cmd+Shift+L on a Mac) goes to the search box. Lasso's shortcut opens its command palette, which
	 * is a Phase 8 task; until then it does the part that exists. Like Lasso's, it does not fire from inside another
	 * text field.
	 */
	var kbd = document.getElementById( 'affibloom-search-kbd' );
	if ( kbd && /Mac|iPhone|iPad/.test( window.navigator.platform || '' ) ) {
		kbd.textContent = '⌘⇧L';
	}
	document.addEventListener( 'keydown', function ( event ) {
		if ( ! search || ! event.shiftKey || ! ( event.ctrlKey || event.metaKey ) || 'l' !== event.key.toLowerCase() ) {
			return;
		}
		var typing = event.target.closest( 'input, textarea, select, [contenteditable]' );
		if ( typing && typing !== search ) {
			return;
		}
		event.preventDefault();
		search.focus();
		search.select();
	} );
} )();
