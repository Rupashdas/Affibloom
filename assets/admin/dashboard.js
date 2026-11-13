/**
 * Affibloom Dashboard: Lasso's selection (Task 13C).
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
} )();
