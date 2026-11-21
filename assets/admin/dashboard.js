/* global wp */
/**
 * Affibloom Dashboard: Lasso's selection (Tasks 13C and 17B), the list without page reloads (Task 13D), the filters
 * panel with saved views (Task 13E), the row's quick edit (Tasks 16A-16C), and the selection's Delete (Task 17B).
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
	 * Selection, as Lasso's (view-js.php, dashboard_selected_ids; Tasks 13C and 17B, F1 D36): a row's box, the page's
	 * box, and the bar that appears with the first tick ("1 link selected", "N links selected"). The selection is the
	 * page's: Select All ticks the page's rows, and every list load keeps only the IDs it draws, so Delete never reaches
	 * a product the editor cannot see. Every listener is on the list region, since the rows are replaced.
	 * ------------------------------------------------------------------------------------------------------- */

	var countLabel = bar.querySelector( '.affibloom-selection__count' );
	var selectAll = bar.querySelector( '[data-select-all]' );
	var selected = {};

	function ids() {
		return Object.keys( selected );
	}

	/** The page's boxes; a disabled one (a product in use, Task 36) cannot be ticked, as Lasso's. */
	function rowBoxes() {
		return Array.prototype.slice.call( region.querySelectorAll( 'tbody .affibloom-check' ) );
	}

	/**
	 * A count in words from an element's one / many templates: data-one and data-many, or with a prefix
	 * (data-deleted-one …).
	 *
	 * @param {Element} element Holds the templates.
	 * @param {number}  n       The count.
	 * @param {string}  prefix  The attributes' prefix, '' for data-one / data-many.
	 */
	function counted( element, n, prefix ) {
		return element.getAttribute( 'data-' + prefix + ( 1 === n ? 'one' : 'many' ) ).replace( '%s', n.toLocaleString() );
	}

	/** Makes the boxes, the rows and the bar say what the selection is. */
	function sync() {
		var boxes = rowBoxes();
		var ticked = 0;
		boxes.forEach( function ( box ) {
			var on = !! selected[ box.value ];
			box.checked = on;
			box.closest( 'tr' ).classList.toggle( 'is-selected', on );
			ticked += on ? 1 : 0;
		} );

		var page = region.querySelector( '[data-select-page]' );
		if ( page ) {
			page.checked = boxes.length > 0 && ticked === boxes.length;
			page.indeterminate = ticked > 0 && ticked < boxes.length;
		}

		var n = ids().length;
		bar.hidden = 0 === n;
		// Add to Group's picker goes back to its button when nothing is selected (Task G5).
		var bulkGroups = bar.querySelector( '[data-bulk-groups]' );
		if ( 0 === n && bulkGroups && ! bulkGroups.hidden ) {
			bulkGroups.hidden = true;
			bar.querySelector( '[data-add-to-group]' ).hidden = false;
		}
		countLabel.textContent = 0 === n ? '' : counted( countLabel, n, '' );

		// With rows ticked, the selection bar takes the saved-view bar's place in the toolbar.
		var views = document.querySelector( '[data-views]' );
		if ( views ) {
			views.hidden = n > 0;
		}
	}

	function set( id, on ) {
		if ( on ) {
			selected[ id ] = true;
		} else {
			delete selected[ id ];
		}
	}

	function clear() {
		selected = {};
		sync();
	}

	/** After a list load: only the rows drawn stay selected (Lasso filters dashboard_selected_ids the same way). */
	function keepDrawn() {
		var drawn = {};
		rowBoxes().forEach( function ( box ) {
			if ( selected[ box.value ] ) {
				drawn[ box.value ] = true;
			}
		} );
		selected = drawn;
		sync();
	}

	region.addEventListener( 'change', function ( event ) {
		var box = event.target;
		if ( ! box.classList || ! box.classList.contains( 'affibloom-check' ) ) {
			return;
		}
		if ( box.hasAttribute( 'data-select-page' ) ) {
			rowBoxes().forEach( function ( row ) {
				if ( ! row.disabled ) {
					set( row.value, box.checked );
				}
			} );
		} else {
			set( box.value, box.checked );
		}
		sync();
	} );

	selectAll.addEventListener( 'click', function () {
		rowBoxes().forEach( function ( box ) {
			if ( ! box.disabled ) {
				set( box.value, true );
			}
		} );
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
	var groupFilter = document.getElementById( 'affibloom-filter-group' );
	var placementFilter = document.getElementById( 'affibloom-filter-placements' );
	var status = document.getElementById( 'affibloom-list-status' );
	var errorTemplate = document.getElementById( 'affibloom-list-error' );
	var sizeSelect = document.getElementById( 'affibloom-per-page' );
	var params = new URLSearchParams( window.location.search );
	var current = {
		s: params.get( 's' ) || '',
		link_type: params.get( 'link_type' ) || '',
		// The Group filter (Task G5): 0 all groups, -1 "Not in Groups", else a group's ID.
		group: parseInt( params.get( 'group' ), 10 ) || 0,
		// The Placements filter (Task 36D): '', has_placements or no_placements.
		placements: params.get( 'placements' ) || '',
		// Lasso's ?post_id= (Task 13F; F19 D24): one product, kept by every list change until Reset or a pill.
		post_id: parseInt( params.get( 'post_id' ), 10 ) || 0,
		paged: parseInt( params.get( 'paged' ), 10 ) || 1,
		// The Alert sort (Task 31A: Lasso's order_by=detection_date&order_type=) and the alert tab (Task 31B: filter=).
		order: 'detection_date' === params.get( 'order_by' ) ? params.get( 'order_type' ) || '' : '',
		filter: params.get( 'filter' ) || '',
		// The size the page was printed with (remembered per editor), so the first search keeps it.
		per_page: sizeSelect ? parseInt( sizeSelect.value, 10 ) : 0,
	};
	var requests = 0;
	// The quick edit's state (Task 16A), here because load() reads it: the open row's product, whether its save is
	// on its way, and a list asked for meanwhile.
	var quick = { id: null, saving: false, held: null, opened: '' };
	// The address of the list on screen: × after Back or Forward puts it back (Task 16B, D29).
	var shownUrl = window.location.href;

	function listPath( list ) {
		var args = new URLSearchParams();
		args.set( 's', list.s );
		args.set( 'link_type', list.link_type );
		args.set( 'group', String( list.group || 0 ) );
		args.set( 'placements', list.placements || '' );
		args.set( 'post_id', String( list.post_id || 0 ) );
		args.set( 'paged', String( list.paged ) );
		args.set( 'per_page', String( list.per_page ) );
		if ( list.order ) {
			args.set( 'order_by', 'detection_date' );
			args.set( 'order_type', list.order );
		}
		if ( list.filter ) {
			args.set( 'filter', list.filter );
		}
		return '/affibloom/v1/dashboard/products?' + args.toString();
	}

	/** The current list with some of it changed; any change but a page link starts again at page 1. */
	function listWith( changes ) {
		var next = { s: current.s, link_type: current.link_type, group: current.group, placements: current.placements, post_id: current.post_id, paged: 1, per_page: current.per_page, order: current.order, filter: current.filter };
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

	/**
	 * The library's size in the Products tile and the search placeholder, from the list answer (Task 13I; F19 D18):
	 * Lasso rewrites its counts after every load, so a delete, a revive or a quick edit shows at once. Not announced:
	 * the status line says the range only.
	 *
	 * @param {number} library Published products.
	 */
	function counts( library ) {
		if ( 'number' !== typeof library ) {
			return;
		}
		var shown = new Intl.NumberFormat( document.documentElement.lang || undefined ).format( library );
		var tile = document.querySelector( '[data-library-count]' );
		if ( tile ) {
			tile.textContent = shown;
		}
		if ( search && search.getAttribute( 'data-placeholder' ) ) {
			search.placeholder = search.getAttribute( 'data-placeholder' ).replace( '%s', shown );
		}
	}

	/**
	 * The alert tabs (Task 31B; F11 R23-R25, D16): their counts from the list answer (they follow the search and the
	 * filters), the warm-up clock and line, and the tab on screen marked.
	 *
	 * @param {Object|null} alerts The answer's counts, or nothing to only mark the tab.
	 */
	function alertTabs( alerts ) {
		var holder = document.querySelector( '[data-alert-tiles]' );
		if ( ! holder ) {
			return;
		}
		var format = new Intl.NumberFormat( document.documentElement.lang || undefined );
		holder.querySelectorAll( '[data-alert-tab]' ).forEach( function ( tile ) {
			var tab = tile.getAttribute( 'data-alert-tab' );
			if ( tab === current.filter ) {
				tile.setAttribute( 'aria-current', 'true' );
			} else {
				tile.removeAttribute( 'aria-current' );
			}
			if ( ! alerts ) {
				return;
			}
			var n = 'broken-links' === tab ? alerts.broken : alerts.out_of_stock;
			tile.querySelectorAll( '[data-alert-count]' ).forEach( function ( count ) {
				count.textContent = format.format( n );
			} );
			tile.setAttribute( 'aria-label', tile.getAttribute( 'data-alert-name' ) + ' (' + format.format( n ) + ')' );
			tile.querySelector( '[data-alert-warm]' ).hidden = ! alerts.warm_up;
			tile.setAttribute( 'aria-busy', alerts.warm_up ? 'true' : 'false' );
			var tip = [ tile.getAttribute( 'data-base-tip' ) ];
			if ( alerts.warm_up ) {
				tip.push( holder.getAttribute( 'data-warm-line' ) );
			}
			if ( tile.hasAttribute( 'data-amazon-tip' ) ) {
				tip.push( tile.getAttribute( 'data-amazon-tip' ) );
			}
			tile.setAttribute( 'data-tip', tip.join( '\n' ) );
		} );
	}

	// A tab lists only its products from page 1, oldest issue first; the tab on screen clicked again lists them all
	// (Lasso's view-js.php 1185-1262). A real link, so a modified click still opens it elsewhere.
	var alertTiles = document.querySelector( '[data-alert-tiles]' );
	if ( alertTiles ) {
		alertTiles.addEventListener( 'click', function ( event ) {
			var tile = event.target.closest( '[data-alert-tab]' );
			if ( ! tile || 0 !== event.button || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey ) {
				return;
			}
			event.preventDefault();
			var tab = tile.getAttribute( 'data-alert-tab' );
			var on = tab !== current.filter;
			guarded( function () {
				load( listWith( { filter: on ? tab : '', order: on ? 'asc' : '' } ), true, false );
			}, tile, true );
		} );
	}

	/** Marks the pill whose list is on screen, as Lasso marks the active view; "All links" when none is. */
	function markActivePill() {
		var pills = Array.prototype.slice.call( document.querySelectorAll( '[data-views] .affibloom-pill' ) );
		var match = null;
		pills.slice( 1 ).forEach( function ( pill ) {
			if ( ! match && ! current.post_id && pill.getAttribute( 'data-view-s' ) === current.s &&
				pill.getAttribute( 'data-view-link-type' ) === current.link_type &&
				parseInt( pill.getAttribute( 'data-view-group' ), 10 ) === current.group &&
				( pill.getAttribute( 'data-view-filter' ) || '' ) === current.filter &&
				( pill.getAttribute( 'data-view-placements' ) || '' ) === current.placements &&
				parseInt( pill.getAttribute( 'data-view-per-page' ), 10 ) === current.per_page ) {
				match = pill;
			}
		} );
		if ( ! match && pills[ 0 ] && ! current.post_id && '' === current.s && '' === current.link_type && 0 === current.group && '' === current.placements && '' === current.filter ) {
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
		if ( groupFilter ) {
			groupFilter.value = String( current.group );
		}
		if ( placementFilter ) {
			placementFilter.value = current.placements;
		}
		var filters = ( current.link_type ? 1 : 0 ) + ( current.group ? 1 : 0 ) + ( current.placements ? 1 : 0 );
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
	 * @param {Function} after   Called once the new list is drawn (optional).
	 */
	function load( list, remember, focus, after ) {
		// While a quick edit is being saved the list waits: a good save loads the list asked for, a refused one keeps
		// the open row (Task 16A).
		if ( quick.saving ) {
			quick.held = { list: list, remember: remember, focus: focus };
			return;
		}
		var mine = ++requests;
		busy( true );

		wp.apiFetch( { path: listPath( list ) } ).then( function ( response ) {
			if ( mine !== requests ) {
				return;
			}
			var data = response.data;
			current = { s: data.term, link_type: data.link_type, group: data.group, placements: data.placements || '', post_id: data.post_id, paged: data.page, per_page: data.per_page, order: data.order || '', filter: data.filter || '' };

			region.innerHTML = data.html;
			quick.id = null; // An open quick edit went with the old rows.
			busy( false );
			if ( remember ) {
				window.history.pushState( current, '', data.url );
			}
			keepDrawn();
			shownUrl = window.location.href;
			counts( data.library );
			alertTabs( data.alerts );
			reflect( ! remember );
			announce();
			if ( focus ) {
				var card = region.querySelector( '.affibloom-card' );
				if ( card ) {
					card.setAttribute( 'tabindex', '-1' );
					card.focus();
				}
			}
			if ( after ) {
				after();
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
			guarded( function () {
				load( listWith( { s: search.value } ), true, false );
			}, search, true );
		} );
	}

	// Page links. A Ctrl-, Cmd- or Shift-click still opens a new tab or window, as any link does.
	// Export CSV on the broken and out-of-stock tabs (Task 31D; F11 R28, D18): the list as it stands, downloaded as
	// the server names it; "Exported N links." on success, the server's message (or Lasso's) on failure.
	region.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '[data-export-csv]' );
		if ( ! button || button.disabled ) {
			return;
		}
		var label = button.querySelector( '[data-export-label]' );
		var text = label.textContent;
		var args = new URLSearchParams( { s: current.s, link_type: current.link_type, group: String( current.group || 0 ), placements: current.placements, filter: current.filter } );
		if ( current.order ) {
			args.set( 'order_by', 'detection_date' );
			args.set( 'order_type', current.order );
		}
		button.disabled = true;
		button.classList.add( 'is-busy' );
		label.textContent = button.getAttribute( 'data-exporting' );
		wp.apiFetch( { path: '/affibloom/v1/dashboard/export?' + args.toString(), parse: false } ).then( function ( response ) {
			var count = parseInt( response.headers.get( 'X-Affibloom-Exported' ), 10 ) || 0;
			var name = ( /filename="([^"]+)"/.exec( response.headers.get( 'Content-Disposition' ) || '' ) || [] )[ 1 ] || 'affibloom-export.csv';
			return response.blob().then( function ( blob ) {
				var link = document.createElement( 'a' );
				link.href = URL.createObjectURL( blob );
				link.download = name;
				document.body.appendChild( link );
				link.click();
				link.remove();
				URL.revokeObjectURL( link.href );
				window.affibloomToast( 1 === count ? button.getAttribute( 'data-exported-one' ) : button.getAttribute( 'data-exported-many' ).replace( '%s', String( count ) ), false );
			} );
		} ).catch( function ( error ) {
			var shown = button.getAttribute( 'data-failed' );
			if ( error && 'function' === typeof error.json ) {
				return error.json().then( function ( body ) {
					window.affibloomToast( ( body && body.message ) || shown, true );
				}, function () {
					window.affibloomToast( shown, true );
				} );
			}
			window.affibloomToast( ( error && error.message ) || shown, true );
		} ).then( function () {
			button.disabled = false;
			button.classList.remove( 'is-busy' );
			label.textContent = text;
		} );
	} );

	// The Alert header sorts by the issue's date (Task 31A; F11 R20, AC9): ascending, descending, then off.
	region.addEventListener( 'click', function ( event ) {
		var sort = event.target.closest( '[data-alert-sort]' );
		if ( ! sort ) {
			return;
		}
		guarded( function () {
			load( listWith( { order: sort.getAttribute( 'data-alert-sort' ) } ), true, false, function () {
				var again = region.querySelector( '[data-alert-sort]' );
				if ( again ) {
					again.focus();
				}
			} );
		}, sort, true );
	} );

	region.addEventListener( 'click', function ( event ) {
		var link = event.target.closest( '.affibloom-pagination a' );
		if ( ! link || 0 !== event.button || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey ) {
			return;
		}
		event.preventDefault();
		guarded( function () {
			load( listWith( { paged: parseInt( new URL( link.href ).searchParams.get( 'paged' ), 10 ) || 1 } ), true, true );
		}, link, true );
	} );

	// The page size: a new size starts again at page 1, as Lasso's does.
	region.addEventListener( 'change', function ( event ) {
		var size = event.target;
		if ( 'affibloom-per-page' === size.id ) {
			guarded( function () {
				load( listWith( { per_page: parseInt( size.value, 10 ) } ), true, false );
			}, size, true, function () {
				size.value = String( current.per_page ); // × keeps the list, so the size it shows.
			} );
		}
	} );
	region.addEventListener( 'submit', function ( event ) {
		if ( event.target.closest( '.affibloom-pager__size' ) ) {
			event.preventDefault(); // The Apply button only shows without JavaScript.
		}
	} );

	// Back and Forward: draw the list the address names, without pushing it again.
	// With a quick edit's changes open it asks first (D29); × puts the list's own address back.
	window.addEventListener( 'popstate', function () {
		var back = new URLSearchParams( window.location.search );
		guarded( function () {
			load( {
				s: back.get( 's' ) || '',
				link_type: back.get( 'link_type' ) || '',
				group: parseInt( back.get( 'group' ), 10 ) || 0,
				placements: back.get( 'placements' ) || '',
				post_id: parseInt( back.get( 'post_id' ), 10 ) || 0,
				paged: parseInt( back.get( 'paged' ), 10 ) || 1,
				per_page: parseInt( back.get( 'per_page' ), 10 ) || 0,
				order: 'detection_date' === back.get( 'order_by' ) ? back.get( 'order_type' ) || '' : '',
				filter: back.get( 'filter' ) || '',
			}, false, false );
		}, null, true, function () {
			window.history.pushState( current, '', shownUrl );
		} );
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

	// Whether the panel was left open (Task 13J; F19 R17, D16), as Lasso's lasso_dashboard_filters_panel_open. Lasso
	// writes it from the Filters button and × only, so a panel closed with Escape or an outside click came back on
	// every load; here every way of closing it writes it (deviation, see progress.md). Storage that throws (a private
	// window) is ignored, and the panel then opens closed.
	var PANEL_KEY = 'affibloom_filters_open';

	function rememberPanel( open ) {
		try {
			window.localStorage.setItem( PANEL_KEY, open ? '1' : '0' );
		} catch ( e ) {}
	}

	function panelWasOpen() {
		try {
			return '1' === window.localStorage.getItem( PANEL_KEY );
		} catch ( e ) {
			return false;
		}
	}

	/**
	 * Opens or closes the panel.
	 *
	 * @param {boolean} open  Open it.
	 * @param {boolean} focus Move the focus into it (not when it opens on load).
	 */
	function openPanel( open, focus ) {
		panel.hidden = ! open;
		toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		if ( open && focus !== false && linkType ) {
			linkType.focus();
		}
	}

	if ( toggle && panel && linkType ) {
		if ( panelWasOpen() ) {
			openPanel( true, false );
		}
		toggle.addEventListener( 'click', function () {
			openPanel( panel.hidden );
			rememberPanel( ! panel.hidden );
		} );
		panel.querySelector( '.affibloom-filters__close' ).addEventListener( 'click', function () {
			openPanel( false );
			rememberPanel( false );
			toggle.focus();
		} );
		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key && ! panel.hidden && ! document.body.classList.contains( 'affibloom-has-modal' ) ) {
				openPanel( false );
				rememberPanel( false );
				toggle.focus();
			}
		} );
		// A click anywhere outside the panel and its button closes it, as a menu does.
		document.addEventListener( 'click', function ( event ) {
			if ( ! panel.hidden && ! panel.contains( event.target ) && ! toggle.contains( event.target ) ) {
				openPanel( false );
				rememberPanel( false );
			}
		} );

		linkType.addEventListener( 'change', function () {
			guarded( function () {
				load( listWith( { link_type: linkType.value } ), true, false );
			}, linkType, true, function () {
				linkType.value = current.link_type;
			} );
		} );

		// The filters' form is for a page without JavaScript (Task 13F); here they load as you toggle.
		var filtersForm = panel.querySelector( '.affibloom-filters__form' );
		if ( filtersForm ) {
			filtersForm.addEventListener( 'submit', function ( event ) {
				event.preventDefault();
			} );
		}

		// The Group filter, as the link type is (Task G5; F23 R22).
		if ( groupFilter ) {
			groupFilter.addEventListener( 'change', function () {
				guarded( function () {
					load( listWith( { group: parseInt( groupFilter.value, 10 ) || 0 } ), true, false );
				}, groupFilter, true, function () {
					groupFilter.value = String( current.group );
				} );
			} );
		}

		// The Placements filter, as the group is (Task 36D; F12 L13).
		if ( placementFilter ) {
			placementFilter.addEventListener( 'change', function () {
				guarded( function () {
					load( listWith( { placements: placementFilter.value } ), true, false );
				}, placementFilter, true, function () {
					placementFilter.value = current.placements;
				} );
			} );
		}

		// Lasso's "Reset filters": search, filters and page size back to the defaults.
		panel.querySelector( '.affibloom-filters__reset' ).addEventListener( 'click', function ( event ) {
			guarded( reset, event.currentTarget, true );
		} );
	}

	/**
	 * Lasso's Reset filters (view-js.php 1252-1299): All links, an empty search, all types and groups, 10 a page, page 1.
	 * Also what the active pill clicked again, and deleting the active view, do (Task 13J; F19 R13, R15). Callers go
	 * through guarded(), so a quick edit with changes asks first.
	 */
	function reset() {
		if ( search ) {
			search.value = '';
		}
		load( { s: '', link_type: '', group: 0, placements: '', post_id: 0, paged: 1, per_page: 10, order: '', filter: '' }, true, false );
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
				// "All links", or the marked pill clicked again, is Lasso's Reset filters (R13).
				if ( pill === document.querySelector( '[data-views] .affibloom-pill' ) || 'true' === pill.getAttribute( 'aria-current' ) ) {
					guarded( reset, pill, true );
					return;
				}
				guarded( function () {
					load( {
						s: pill.getAttribute( 'data-view-s' ),
						link_type: pill.getAttribute( 'data-view-link-type' ),
						group: parseInt( pill.getAttribute( 'data-view-group' ), 10 ) || 0,
						placements: pill.getAttribute( 'data-view-placements' ) || '',
						paged: 1,
						per_page: parseInt( pill.getAttribute( 'data-view-per-page' ), 10 ) || current.per_page,
						filter: pill.getAttribute( 'data-view-filter' ) || '',
						order: pill.getAttribute( 'data-view-filter' ) ? 'asc' : '',
					}, true, false );
				}, pill, true );
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
			// Whether the view being deleted is the one on screen: then All links comes back, as Lasso's does (R15).
			var remove = document.querySelector( '.affibloom-pill__remove[data-view-id="' + CSS.escape( id ) + '"]' );
			var pill = remove ? remove.parentNode.querySelector( '.affibloom-pill' ) : null;
			var wasActive = !! pill && 'true' === pill.getAttribute( 'aria-current' );
			confirmDelete.disabled = true;
			wp.apiFetch( { path: '/affibloom/v1/dashboard/views/' + encodeURIComponent( id ), method: 'DELETE' } ).then( function ( response ) {
				replaceViews( response.data.html );
				window.affibloomToast( panel.dataset.deletedText, false );
				if ( wasActive ) {
					guarded( reset, null, true );
				}
			} ).catch( function ( error ) {
				// Nothing was deleted; the pill stays and the editor can try again.
				window.affibloomToast( error && error.message ? error.message : panel.dataset.deleteFailedText, true );
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

		var saveButton = saveForm.querySelector( '[type="submit"]' );
		var refuse = function ( message ) {
			saveError.textContent = message;
			nameField.classList.add( 'is-invalid' );
			nameField.setAttribute( 'aria-invalid', 'true' );
			nameField.focus();
		};

		saveForm.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			saveError.textContent = '';
			nameField.classList.remove( 'is-invalid' );
			nameField.removeAttribute( 'aria-invalid' );

			// Lasso checks the name before it asks the server (R14): an empty one sends nothing.
			var name = nameField.value.trim();
			if ( '' === name ) {
				refuse( panel.dataset.nameFirstText );
				return;
			}

			saveButton.disabled = true;
			wp.apiFetch( {
				path: '/affibloom/v1/dashboard/views',
				method: 'POST',
				data: { name: name, s: current.s, link_type: current.link_type, group: current.group, placements: current.placements, per_page: current.per_page, filter: current.filter },
			} ).then( function ( response ) {
				replaceViews( response.data.html );
				nameField.value = '';
				window.affibloomToast( panel.dataset.savedText, false );
			} ).catch( function ( error ) {
				refuse( ( error && error.message ) || panel.dataset.saveFailedText );
			} ).then( function () {
				saveButton.disabled = false;
			} );
		} );
	}

	/* ---------------------------------------------------------------------------------------------------------
	 * Quick edit (Task 16A; F1 requirements 10), as Lasso's (view-js.php 735-863): Edit turns the row into a form
	 * with the name, the link and the image; Save Changes stores those three through the product page's route, so
	 * every other field stays as stored (Q6, D28), and loads the list again (Q7); Cancel brings the row back without
	 * asking. One row at a time (Q4). The form is the row's template, printed by the server with the stored values
	 * (D26).
	 * ------------------------------------------------------------------------------------------------------- */

	/**
	 * What a save sends from a row's form (D28); also what counts as a change (Q9: name, link, image).
	 *
	 * @param {HTMLFormElement} form The row's form.
	 */
	function quickValues( form ) {
		var value = function ( name ) {
			return form.elements.namedItem( name ).value;
		};
		// The row's groups (Task G5; F1 D28 gains `groups`): IDs of groups, names for new ones.
		var picker = form.querySelector( '[data-group-picker]' );
		return {
			name: value( 'name' ),
			url: value( 'url' ),
			image_url: value( 'image_url' ),
			image_id: parseInt( value( 'image_id' ), 10 ) || 0,
			groups: picker && picker.affibloomPicker ? picker.affibloomPicker.values() : [],
		};
	}

	function editButton( id ) {
		return region.querySelector( '[data-quick-edit="' + id + '"]' );
	}

	function productRow( id ) {
		return region.querySelector( 'tr[data-product-id="' + id + '"]' );
	}

	/**
	 * Puts the row back in place of its form.
	 *
	 * @param {boolean} focusEdit Move the focus to the row's Edit (after Cancel, D27).
	 */
	function closeQuick( focusEdit ) {
		var id = quick.id;
		if ( null === id ) {
			return;
		}
		quick.id = null;
		var editing = region.querySelector( '[data-quick-edit-for="' + id + '"]' );
		if ( editing ) {
			editing.remove();
		}
		var row = productRow( id );
		if ( row ) {
			row.hidden = false;
		}
		var button = editButton( id );
		if ( button ) {
			button.setAttribute( 'aria-expanded', 'false' );
			if ( focusEdit ) {
				button.focus();
			}
		}
	}

	/**
	 * Opens a row's form; another open row closes first, and the open row's own Edit does nothing (Q4).
	 *
	 * @param {string} id The product.
	 */
	function openQuick( id ) {
		var row = productRow( id );
		var template = region.querySelector( 'template[data-quick-edit-template="' + id + '"]' );
		if ( quick.id === id || quick.saving || ! row || ! template ) {
			return;
		}
		closeQuick( false );
		var editing = template.content.firstElementChild.cloneNode( true );
		row.after( editing );
		row.hidden = true;
		window.affibloomGroupPicker.init( editing );
		quick.id = id;
		quick.opened = JSON.stringify( quickValues( editing.querySelector( 'form' ) ) );
		editButton( id ).setAttribute( 'aria-expanded', 'true' );
		editing.querySelector( '[name="name"]' ).focus();
	}

	/**
	 * Lasso's media frame for the image (Q5): the picture shows in the frame; its link and media item are sent.
	 *
	 * @param {HTMLButtonElement} frame The image frame.
	 */
	function pickImage( frame ) {
		window.affibloomMedia.pick( { title: frame.dataset.mediaTitle, button: frame.dataset.mediaButton }, function ( image ) {
			var picture = frame.querySelector( '[data-quick-picture]' );
			picture.src = image.url;
			picture.hidden = false;
			frame.querySelector( '[data-quick-none]' ).hidden = true;
			frame.form.elements.namedItem( 'image_url' ).value = image.url;
			frame.form.elements.namedItem( 'image_id' ).value = String( image.id );
		} );
	}

	/**
	 * Saves the open row (Q6-Q8): "Updating Your Site" with the product page's line (D22), then "Product saved." and
	 * the list loaded again, the saved row's Edit focused when it is on the page (D27); a refused field gets Lasso's
	 * sentence under it and the failure toast, and the row stays open with its edits.
	 *
	 * @param {HTMLFormElement} form The row's form.
	 * @param {Function}        then What to do after a good save instead of loading the list again (Task 16B: the
	 *                               action "Unsaved Changes" held back); never called after a refused save (Q9).
	 */
	function saveQuick( form, then ) {
		if ( quick.saving ) {
			return; // Lasso ignores a second click while saving.
		}
		var id = quick.id;
		var submit = form.querySelector( '[type="submit"]' );
		var sent = quickValues( form );
		var dialog = window.affibloomSaveDialog;

		quick.saving = true;
		submit.disabled = true;
		window.affibloomFieldErrors.clear( form );
		dialog.open( dialog.modal().dataset.textDefault );

		wp.apiFetch( { path: '/affibloom/v1/products/' + id, method: 'PATCH', data: sent } ).then( function () {
			dialog.close( function () {
				var held = quick.held;
				quick.saving = false;
				quick.held = null;
				closeQuick( false );
				window.affibloomToast( region.dataset.savedText, false );
				if ( then ) {
					then();
					return;
				}
				if ( held ) {
					load( held.list, held.remember, held.focus );
					return;
				}
				load( current, false, false, function () {
					var button = editButton( id );
					var card = region.querySelector( '.affibloom-card' );
					if ( button ) {
						button.focus();
					} else if ( card ) {
						card.setAttribute( 'tabindex', '-1' );
						card.focus();
					}
				} );
			} );
		} ).catch( function ( error ) {
			// Another product has the new link (Task 16C; Q10, D21): nothing was saved, and the dialog turns into Lasso's
			// "This Product Already Exists". Cancel keeps the row open with the typed link; Confirm opens the kept
			// product. Lasso's own Dashboard drops this offer and says "saved".
			if ( error && 'affibloom_duplicate' === error.code && error.data && error.data.to ) {
				quick.saving = false;
				quick.held = null;
				submit.disabled = false;
				window.affibloomRepoint.offer( parseInt( id, 10 ), error.data, sent.url, {
					cancelled: function () {
						form.elements.namedItem( 'url' ).focus();
					},
					leave: go,
					failedText: region.dataset.failedText,
				} );
				return;
			}
			dialog.close( function () {
				var fields = error && error.data && error.data.fields;
				quick.saving = false;
				quick.held = null;
				submit.disabled = false;
				if ( fields ) {
					window.affibloomFieldErrors.show( form, fields );
					window.affibloomToast( region.dataset.failedText, true );
				} else {
					window.affibloomToast( error && error.message ? error.message : region.dataset.failedText, true );
				}
			} );
		} );
	}

	region.addEventListener( 'click', function ( event ) {
		var edit = event.target.closest( '[data-quick-edit]' );
		if ( edit ) {
			// Another row's Edit asks first (Q9); after a save the list is drawn again before that row opens.
			guarded( function () {
				openQuick( edit.getAttribute( 'data-quick-edit' ) );
			}, edit, false );
		} else if ( event.target.closest( '[data-quick-cancel]' ) ) {
			closeQuick( true );
		} else if ( event.target.closest( '[data-quick-image]' ) ) {
			pickImage( event.target.closest( '[data-quick-image]' ) );
		} else if ( event.target.closest( '[data-copy-shortcode]' ) ) {
			copyShortcode( event.target.closest( '[data-copy-shortcode]' ) );
		}
	} );

	/**
	 * The row's Shortcode button (F31 R30, Task 18S10): copies `[affibloom id]` and its label reads "Copied" for 1.6 s; a
	 * row in quick edit ignores it, as Lasso's does; a refused copy says so.
	 *
	 * @param {HTMLButtonElement} button The button.
	 */
	function copyShortcode( button ) {
		var row = button.closest( '[data-product-id]' );
		var label = button.querySelector( '[data-copy-label]' );
		if ( button.disabled || '' === button.dataset.copyShortcode || ( row && quick.id === row.getAttribute( 'data-product-id' ) ) ) {
			return;
		}
		label.dataset.name = label.dataset.name || label.textContent;
		window.affibloomCopy( button.dataset.copyShortcode, function () {
			window.clearTimeout( button.copiedTimer );
			label.textContent = button.dataset.copied;
			button.copiedTimer = window.setTimeout( function () {
				label.textContent = label.dataset.name;
			}, 1600 );
		}, function () {
			window.affibloomToast( button.dataset.copyFailed, true );
		} );
	}
	region.addEventListener( 'submit', function ( event ) {
		var form = event.target.closest( '[data-quick-form]' );
		if ( form ) {
			event.preventDefault();
			saveQuick( form );
		}
	} );
	// A refused field's note goes as soon as it is edited, as on the product page.
	region.addEventListener( 'input', function ( event ) {
		var form = event.target.closest( '[data-quick-form]' );
		if ( form && event.target.id && document.getElementById( event.target.id + '-save-error' ) ) {
			window.affibloomFieldErrors.clear( form );
		}
	} );

	/* ---------------------------------------------------------------------------------------------------------
	 * Unsaved changes (Task 16B; F1 requirements 10 Q9, D29), as Lasso's run_page_action and its link and
	 * beforeunload guards (view-js.php 163-165, 692-733, 927-934, 999-1021). With a row open and its name, link or
	 * image changed, anything that would redraw the list or leave the page asks "Unsaved Changes" first: Leave
	 * Without Saving drops the edits and goes on; Save Changes saves and goes on only after a good save; × (or
	 * Escape) stays. Closing or reloading the tab gets the browser's own question.
	 * ------------------------------------------------------------------------------------------------------- */

	var unsaved = document.querySelector( '[data-unsaved-modal]' );
	// The action the dialog holds: what to run, whether it draws the list itself, and what × must undo.
	var pending = null;
	var leaving = false;

	function quickChanged() {
		var form = null === quick.id ? null : region.querySelector( '[data-quick-form="' + quick.id + '"]' );
		return !! form && JSON.stringify( quickValues( form ) ) !== quick.opened;
	}

	/**
	 * Runs an action that would lose an open row's edits, or asks first (Lasso's run_page_action).
	 *
	 * @param {Function}         action  What the editor asked for.
	 * @param {HTMLElement|null} opener  Where the focus returns when the dialog closes.
	 * @param {boolean}          redraws The action draws the list (or leaves the page) itself; otherwise, after a
	 *                                   save, the list is drawn again first so the saved row shows what was stored.
	 * @param {Function}         stay    What × must put back (a select's value, the address after Back); optional.
	 */
	function guarded( action, opener, redraws, stay ) {
		// While the row is being saved, its edits are not lost: a list load waits for the save (16A).
		if ( quick.saving || ! quickChanged() ) {
			action();
			return;
		}
		pending = { action: action, redraws: redraws, stay: stay || null };
		var form = region.querySelector( '[data-quick-form="' + quick.id + '"]' );
		window.affibloomModal.show( unsaved, opener || form.elements.namedItem( 'name' ) );
	}

	/** Takes the held action off the dialog, so closing it is not read as ×. */
	function takePending() {
		var held = pending;
		pending = null;
		return held;
	}

	function go( href ) {
		leaving = true;
		window.location.assign( href );
	}

	if ( unsaved ) {
		unsaved.querySelector( '[data-unsaved-leave]' ).addEventListener( 'click', function () {
			var held = takePending();
			window.affibloomModal.hide();
			closeQuick( false );
			if ( held ) {
				held.action();
			}
		} );
		unsaved.querySelector( '[data-unsaved-save]' ).addEventListener( 'click', function () {
			var held = takePending();
			var form = region.querySelector( '[data-quick-form="' + quick.id + '"]' );
			window.affibloomModal.hide();
			if ( ! held || ! form ) {
				return;
			}
			saveQuick( form, held.redraws ? held.action : function () {
				load( current, false, false, held.action );
			} );
		} );
		// × or Escape: stay, and put back what the asking control already changed.
		unsaved.addEventListener( 'affibloom:close', function () {
			var held = takePending();
			if ( held && held.stay ) {
				held.stay();
			}
		} );
	}

	// A link that leaves the page: not "#…", "javascript:", a new tab, a modified or middle click, a link inside a
	// dialog or inside the open row (Lasso's exceptions), and not one a list control has already taken.
	document.addEventListener( 'click', function ( event ) {
		var link = event.target.closest( 'a[href]' );
		if ( ! link || event.defaultPrevented || event.button || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey ) {
			return;
		}
		var href = link.getAttribute( 'href' );
		if ( '' === href || '#' === href.charAt( 0 ) || 0 === href.indexOf( 'javascript:' ) || '_blank' === link.target ||
			link.closest( '.affibloom-modal, .affibloom-quick' ) || ! quickChanged() ) {
			return;
		}
		event.preventDefault();
		guarded( function () {
			go( link.href );
		}, link, true );
	} );

	window.addEventListener( 'beforeunload', function ( event ) {
		if ( leaving || quick.saving || ! quickChanged() ) {
			return;
		}
		event.preventDefault();
		event.returnValue = '';
	} );

	/* ---------------------------------------------------------------------------------------------------------
	 * Delete on the selection bar (Task 17B; F1 requirements 11, X7, D36-D40), as Lasso's dashboard_process_delete_links:
	 * "Delete the selected links?", then DELETE /affibloom/v1/products?ids[]=… moves the ticked rows to the trash; the
	 * selection is cleared, the dialog closes and the same list is loaded again (the server shows the last page when
	 * this one is now past it), with "N products deleted.". The button goes through the quick edit's guard (D38): the
	 * reload would wipe an open row's edits. A failure keeps the selection and shows the failure toast.
	 * ------------------------------------------------------------------------------------------------------- */

	var bulkDelete = document.getElementById( 'affibloom-bulk-delete' );
	var deleteSelected = bar.querySelector( '[data-delete-selected]' );
	if ( bulkDelete && deleteSelected ) {
		var confirmBulk = bulkDelete.querySelector( '[data-delete-confirm]' );
		var confirmLabel = confirmBulk.textContent;
		var deleting = false;
		var deletingNow = function ( on ) {
			deleting = on;
			confirmBulk.disabled = on;
			confirmBulk.textContent = on ? confirmBulk.dataset.busyLabel : confirmLabel;
		};

		deleteSelected.addEventListener( 'click', function () {
			guarded( function () {
				if ( ids().length ) {
					window.affibloomModal.show( bulkDelete, deleteSelected );
				}
			}, deleteSelected, false );
		} );

		confirmBulk.addEventListener( 'click', function () {
			var chosen = ids();
			if ( deleting || ! chosen.length ) {
				return;
			}
			deletingNow( true );
			var query = chosen.map( function ( id ) {
				return 'ids[]=' + encodeURIComponent( id );
			} ).join( '&' );
			wp.apiFetch( { path: '/affibloom/v1/products?' + query, method: 'DELETE' } ).then( function ( response ) {
				var gone = response.data.deleted.length;
				deletingNow( false );
				window.affibloomModal.hide();
				clear();
				load( current, false, true, function () {
					window.affibloomToast( counted( bar, gone, 'deleted-' ), false );
				} );
			} ).catch( function ( error ) {
				deletingNow( false );
				window.affibloomModal.hide();
				window.affibloomToast( error && error.message ? error.message : bulkDelete.dataset.failedText, true );
			} );
		} );
	}

	/* ---------------------------------------------------------------------------------------------------------
	 * Add to Group on the selection bar (Task G5; F23 R23), as Lasso's (view-js.php 456-575): the button turns into the
	 * group picker and Save Changes. The products join the chosen groups (new names become groups) and keep the ones
	 * they have; with none chosen, "Select at least one group."; then the list is drawn again. The button goes through
	 * the quick edit's guard, as Delete does, because the reload would wipe an open row's edits.
	 * ------------------------------------------------------------------------------------------------------- */

	var addToGroup = bar.querySelector( '[data-add-to-group]' );
	var bulkGroups = bar.querySelector( '[data-bulk-groups]' );
	if ( addToGroup && bulkGroups ) {
		var bulkPicker = bulkGroups.querySelector( '[data-group-picker]' );
		var bulkSave = bulkGroups.querySelector( '[data-bulk-groups-save]' );
		var bulkError = bulkGroups.querySelector( '[data-bulk-groups-error]' );
		var adding = false;

		addToGroup.addEventListener( 'click', function () {
			guarded( function () {
				if ( ! ids().length ) {
					return;
				}
				window.affibloomGroupPicker.init( bulkGroups );
				bulkPicker.affibloomPicker.clear();
				bulkError.textContent = '';
				addToGroup.hidden = true;
				bulkGroups.hidden = false;
				bulkPicker.querySelector( 'input' ).focus();
			}, addToGroup, false );
		} );

		bulkSave.addEventListener( 'click', function () {
			var values = bulkPicker.affibloomPicker.values();
			var chosen = ids();
			if ( adding || ! chosen.length ) {
				return;
			}
			if ( 0 === values.length ) {
				bulkError.textContent = bulkGroups.dataset.noneText;
				return;
			}
			adding = true;
			bulkSave.disabled = true;
			bulkError.textContent = '';
			wp.apiFetch( {
				path: '/affibloom/v1/products/groups',
				method: 'POST',
				data: { ids: chosen.map( function ( id ) {
					return parseInt( id, 10 );
				} ), groups: values },
			} ).then( function ( response ) {
				var updated = response.data.updated;
				clear();
				load( current, false, true, function () {
					window.affibloomToast( counted( bulkGroups, updated, 'done-' ), false );
				} );
			} ).catch( function () {
				bulkError.textContent = bulkGroups.dataset.failedText;
			} ).then( function () {
				adding = false;
				bulkSave.disabled = false;
			} );
		} );
	}
} )();
