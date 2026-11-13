/* global wp */
/**
 * Affibloom Dashboard: Lasso's selection (Task 13C), the list without page reloads (Task 13D), and the filters
 * panel with saved views (Task 13E).
 *
 * The server renders every piece of markup (the list, the saved-view bar) with the same partials the page printed,
 * so this file never builds a row or a pill: it asks, swaps what comes back in, and keeps the address bar in step
 * so reload, Back and bookmarks work. Without JavaScript every control still submits or links.
 */
( function () {
	'use strict';

	var region = document.getElementById( 'affibloom-list' );
	var bar = document.querySelector( '.affibloom-selection' );
	if ( ! region || ! bar ) {
		return;
	}

	/* ---------------------------------------------------------------------------------------------------------
	 * Selection (Task 13C), as Lasso's (view-js.php, dashboard_selected_ids): a row's box, the page's box, and the
	 * bar that appears with the first tick, with Select All (every product in the list, on every page) and Clear
	 * Selection. Delete and Add to Group arrive with Task 17 and the Groups area. Every listener is on the list
	 * region, so the rows can be replaced and the selection survives: "all" is a mode with the rows unticked since.
	 * ------------------------------------------------------------------------------------------------------- */

	var countLabel = bar.querySelector( '.affibloom-selection__count' );
	var selectAll = bar.querySelector( '[data-select-all]' );
	var selection = { all: false, ids: {}, except: {} };

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

		// With rows ticked, the selection bar takes the saved-view bar's place in the toolbar.
		var views = document.querySelector( '[data-views]' );
		if ( views ) {
			views.hidden = n > 0;
		}
	}

	/** Ticks or unticks a row: in "all" mode an untick is remembered as an exception. */
	function set( id, on ) {
		var list = selection.all ? selection.except : selection.ids;
		if ( on !== selection.all ) {
			list[ id ] = true;
		} else {
			delete list[ id ];
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

	/* ---------------------------------------------------------------------------------------------------------
	 * The list without page reloads (Task 13D), as Lasso's is an AJAX view.
	 * ------------------------------------------------------------------------------------------------------- */

	var search = document.getElementById( 'affibloom-search' );
	var linkType = document.getElementById( 'affibloom-filter-link-type' );
	var status = document.getElementById( 'affibloom-list-status' );
	var errorTemplate = document.getElementById( 'affibloom-list-error' );
	var sizeSelect = document.getElementById( 'affibloom-per-page' );
	var params = new URLSearchParams( window.location.search );
	var current = {
		s: params.get( 's' ) || '',
		link_type: params.get( 'link_type' ) || '',
		paged: parseInt( params.get( 'paged' ), 10 ) || 1,
		// The size the page was printed with (remembered per editor), so the first search keeps it.
		per_page: sizeSelect ? parseInt( sizeSelect.value, 10 ) : 0,
	};
	var requests = 0;

	function listPath( list ) {
		var args = new URLSearchParams();
		args.set( 's', list.s );
		args.set( 'link_type', list.link_type );
		args.set( 'paged', String( list.paged ) );
		args.set( 'per_page', String( list.per_page ) );
		return '/affibloom/v1/dashboard/products?' + args.toString();
	}

	/** The current list with some of it changed; any change but a page link starts again at page 1. */
	function listWith( changes ) {
		var next = { s: current.s, link_type: current.link_type, paged: 1, per_page: current.per_page };
		Object.keys( changes ).forEach( function ( key ) {
			next[ key ] = changes[ key ];
		} );
		return next;
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

	/** Marks the pill whose list is on screen, as Lasso marks the active view; "All links" when none is. */
	function markActivePill() {
		var pills = Array.prototype.slice.call( document.querySelectorAll( '[data-views] .affibloom-pill' ) );
		var match = null;
		pills.slice( 1 ).forEach( function ( pill ) {
			if ( ! match && pill.getAttribute( 'data-view-s' ) === current.s &&
				pill.getAttribute( 'data-view-link-type' ) === current.link_type &&
				parseInt( pill.getAttribute( 'data-view-per-page' ), 10 ) === current.per_page ) {
				match = pill;
			}
		} );
		if ( ! match && pills[ 0 ] && '' === current.s && '' === current.link_type ) {
			match = pills[ 0 ];
		}
		pills.forEach( function ( pill ) {
			if ( pill === match ) {
				pill.setAttribute( 'aria-current', 'true' );
			} else {
				pill.removeAttribute( 'aria-current' );
			}
		} );
	}

	/**
	 * The controls outside the list follow it: the search box, the link type, the filter count, the pills.
	 *
	 * The search box is left alone while it has the focus, so an answer arriving while the editor types on does not
	 * overwrite the newer text; but Back and Forward (fromHistory) always set it, since the list they bring back is a
	 * different search (the focus is often still in the box after the search that is being undone).
	 *
	 * @param {boolean} fromHistory The list answers Back or Forward.
	 */
	function reflect( fromHistory ) {
		if ( search && ( fromHistory || document.activeElement !== search ) ) {
			search.value = current.s;
		}
		if ( linkType ) {
			linkType.value = current.link_type;
		}
		var filters = current.link_type ? 1 : 0;
		var badge = document.querySelector( '.affibloom-filters__count' );
		if ( badge ) {
			badge.textContent = String( filters );
			badge.hidden = 0 === filters;
		}
		markActivePill();
	}

	/**
	 * Loads a list. Only the newest request may draw: a slow answer to an older one is dropped, so searching and
	 * paging quickly cannot leave the list showing something that was not asked for last.
	 *
	 * @param {Object}  list     The search, link type, page and size.
	 * @param {boolean} remember Push the result's address into the history (not when answering Back).
	 * @param {boolean} focus    Move the focus to the list (after a page link, which the new list replaces).
	 */
	function load( list, remember, focus ) {
		var mine = ++requests;
		busy( true );

		wp.apiFetch( { path: listPath( list ) } ).then( function ( response ) {
			if ( mine !== requests ) {
				return;
			}
			var data = response.data;
			var otherList = data.term !== current.s || data.link_type !== current.link_type;
			current = { s: data.term, link_type: data.link_type, paged: data.page, per_page: data.per_page };

			region.innerHTML = data.html;
			busy( false );
			if ( remember ) {
				window.history.pushState( current, '', data.url );
			}
			if ( otherList ) {
				clear(); // A different list: the old selection does not belong to it.
			} else {
				sync();
			}
			reflect( ! remember );
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
			load( listWith( { s: search.value } ), true, false );
		} );
	}

	// Page links. A Ctrl-, Cmd- or Shift-click still opens a new tab or window, as any link does.
	region.addEventListener( 'click', function ( event ) {
		var link = event.target.closest( '.affibloom-pagination a' );
		if ( ! link || 0 !== event.button || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey ) {
			return;
		}
		event.preventDefault();
		load( listWith( { paged: parseInt( new URL( link.href ).searchParams.get( 'paged' ), 10 ) || 1 } ), true, true );
	} );

	// The page size: a new size starts again at page 1, as Lasso's does.
	region.addEventListener( 'change', function ( event ) {
		if ( 'affibloom-per-page' === event.target.id ) {
			load( listWith( { per_page: parseInt( event.target.value, 10 ) } ), true, false );
		}
	} );
	region.addEventListener( 'submit', function ( event ) {
		if ( event.target.closest( '.affibloom-pager__size' ) ) {
			event.preventDefault(); // The Apply button only shows without JavaScript.
		}
	} );

	// Back and Forward: draw the list the address names, without pushing it again.
	window.addEventListener( 'popstate', function () {
		var back = new URLSearchParams( window.location.search );
		load( {
			s: back.get( 's' ) || '',
			link_type: back.get( 'link_type' ) || '',
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

	/* ---------------------------------------------------------------------------------------------------------
	 * The filters panel and saved views (Task 13E), as Lasso's. "Filters apply as you toggle": changing one loads
	 * the list at once.
	 * ------------------------------------------------------------------------------------------------------- */

	var toggle = document.querySelector( '.affibloom-filters__toggle' );
	var panel = document.getElementById( 'affibloom-filters-panel' );

	function openPanel( open ) {
		panel.hidden = ! open;
		toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		if ( open && linkType ) {
			linkType.focus();
		}
	}

	if ( toggle && panel && linkType ) {
		toggle.addEventListener( 'click', function () {
			openPanel( panel.hidden );
		} );
		panel.querySelector( '.affibloom-filters__close' ).addEventListener( 'click', function () {
			openPanel( false );
			toggle.focus();
		} );
		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key && ! panel.hidden && ! document.body.classList.contains( 'affibloom-has-modal' ) ) {
				openPanel( false );
				toggle.focus();
			}
		} );
		// A click anywhere outside the panel and its button closes it, as a menu does.
		document.addEventListener( 'click', function ( event ) {
			if ( ! panel.hidden && ! panel.contains( event.target ) && ! toggle.contains( event.target ) ) {
				openPanel( false );
			}
		} );

		linkType.addEventListener( 'change', function () {
			load( listWith( { link_type: linkType.value } ), true, false );
		} );

		// Lasso's "Reset filters": search, filters and page size back to the defaults.
		panel.querySelector( '.affibloom-filters__reset' ).addEventListener( 'click', function () {
			if ( search ) {
				search.value = '';
			}
			load( { s: '', link_type: '', paged: 1, per_page: 10 }, true, false );
		} );
	}

	var toolbar = document.querySelector( '.affibloom-toolbar' );
	var deleteModal = document.getElementById( 'affibloom-view-delete' );
	var pendingDelete = null;

	function replaceViews( html ) {
		var old = document.querySelector( '[data-views]' );
		var holder = document.createElement( 'div' );
		holder.innerHTML = html.trim();
		old.replaceWith( holder.firstElementChild );
		sync();
		markActivePill();
	}

	if ( toolbar ) {
		toolbar.addEventListener( 'click', function ( event ) {
			// A pill loads its list. It is a real link, so a modified click still opens it elsewhere.
			var pill = event.target.closest( '.affibloom-pill' );
			if ( pill && 0 === event.button && ! event.ctrlKey && ! event.metaKey && ! event.shiftKey && ! event.altKey ) {
				event.preventDefault();
				load( {
					s: pill.getAttribute( 'data-view-s' ),
					link_type: pill.getAttribute( 'data-view-link-type' ),
					paged: 1,
					per_page: parseInt( pill.getAttribute( 'data-view-per-page' ), 10 ) || current.per_page,
				}, true, false );
				return;
			}

			// The ×: admin.js opens the confirmation (data-affibloom-open); this runs first and fills in the name.
			var remove = event.target.closest( '.affibloom-pill__remove' );
			if ( remove && deleteModal ) {
				pendingDelete = remove.getAttribute( 'data-view-id' );
				deleteModal.querySelector( '[data-view-delete-name]' ).textContent = remove.getAttribute( 'data-view-name' );
			}
		} );
	}

	if ( deleteModal ) {
		var confirmDelete = deleteModal.querySelector( '[data-view-delete-confirm]' );
		confirmDelete.addEventListener( 'click', function () {
			var id = pendingDelete;
			if ( ! id ) {
				return;
			}
			confirmDelete.disabled = true;
			wp.apiFetch( { path: '/affibloom/v1/dashboard/views/' + encodeURIComponent( id ), method: 'DELETE' } ).then( function ( response ) {
				replaceViews( response.data.html );
			} ).catch( function () {
				// Nothing was deleted; the pill stays and the editor can try again.
			} ).then( function () {
				confirmDelete.disabled = false;
				pendingDelete = null;
				deleteModal.querySelector( '[data-affibloom-close]' ).click();
				var allLinks = document.querySelector( '[data-views] .affibloom-pill' );
				if ( allLinks ) {
					allLinks.focus(); // The × that opened the dialog is gone with its pill.
				}
			} );
		} );
	}

	// "Save as": the current search, link type and page size, under a name.
	var saveForm = document.querySelector( '.affibloom-filters__save' );
	if ( saveForm ) {
		var nameField = document.getElementById( 'affibloom-view-name' );
		var saveError = document.getElementById( 'affibloom-view-error' );

		saveForm.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			saveError.textContent = '';
			nameField.classList.remove( 'is-invalid' );
			nameField.removeAttribute( 'aria-invalid' );

			wp.apiFetch( {
				path: '/affibloom/v1/dashboard/views',
				method: 'POST',
				data: { name: nameField.value, s: current.s, link_type: current.link_type, per_page: current.per_page },
			} ).then( function ( response ) {
				replaceViews( response.data.html );
				nameField.value = '';
			} ).catch( function ( error ) {
				saveError.textContent = ( error && error.message ) || '';
				nameField.classList.add( 'is-invalid' );
				nameField.setAttribute( 'aria-invalid', 'true' );
				nameField.focus();
			} );
		} );
	}
} )();
