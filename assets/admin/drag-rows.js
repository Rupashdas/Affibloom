/**
 * Rows put in a new order by dragging their grip ([data-grip]), with pointer events (a group's Links tab, Task G4; a
 * product's fields, Task FL5; its buttons and features, as Lasso drags its rows, the user 2026-11-16). Taken out of
 * group-members.js on its second use.
 *
 * The drag looks like a card board's (Trello's; the user, 2026-11-16): the row lifts out of the list under the
 * pointer, tilted, with a deep shadow ("is-dragging"); a grey slot the row's size holds its place; the other rows
 * slide out of the slot's way; on release the row glides into the slot and dropped() runs, to save the order. Only the
 * rows movable() returns take part, so a pinned row keeps its place (Primary Rating). A grip that is a button also
 * moves its row from the keyboard: Arrow Up and Arrow Down swap it with the movable row before or after it, the focus
 * staying on the grip. With "reduce motion" asked for, nothing slides or glides.
 */
( function () {
	'use strict';

	var SLIDE = 150; // ms, how long a row takes to slide aside or into its slot.

	/**
	 * Says a keyboard move's new place to screen readers, in one polite live region for the page (U3; Task 37C: a move
	 * by Arrow Up or Down was silent).
	 *
	 * @param {string} words What to say.
	 */
	var announce = function ( words ) {
		var region = document.getElementById( 'affibloom-move-status' );
		if ( ! region ) {
			region = document.createElement( 'p' );
			region.id = 'affibloom-move-status';
			region.className = 'screen-reader-text';
			region.setAttribute( 'role', 'status' );
			region.setAttribute( 'aria-live', 'polite' );
			document.body.appendChild( region );
		}
		region.textContent = '';
		window.setTimeout( function () {
			region.textContent = words;
		}, 50 );
	};

	var still = function () {
		return window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	};

	/**
	 * Moves rows in the page and lets the ones that moved slide from where they were (First, Last, Invert, Play).
	 *
	 * @param {HTMLElement[]} rows   The rows that may move.
	 * @param {Function}      change Changes the page.
	 */
	var slide = function ( rows, change ) {
		if ( still() ) {
			change();
			return;
		}
		var before = rows.map( function ( row ) {
			return row.getBoundingClientRect().top;
		} );
		change();
		rows.forEach( function ( row, index ) {
			var shift = before[ index ] - row.getBoundingClientRect().top;
			if ( 0 === shift ) {
				return;
			}
			row.style.transition = 'none';
			row.style.transform = 'translateY(' + shift + 'px)';
			row.getBoundingClientRect(); // Applies the shift before the slide starts.
			row.style.transition = 'transform ' + SLIDE + 'ms ease';
			row.style.transform = '';
		} );
	};

	/**
	 * @param {HTMLElement} list    The rows' parent.
	 * @param {Object}      options row: a row's selector; movable(): the rows that move; dropped(): after a drop.
	 */
	window.affibloomDragRows = function ( list, options ) {
		var drag = null; // { row, slot, dx, dy } while a row is held.

		var others = function () {
			return options.movable().filter( function ( row ) {
				return ! drag || row !== drag.row;
			} );
		};

		/** The held row follows the pointer; the slot goes before the first row whose middle is below the pointer. */
		var follow = function ( x, y ) {
			drag.row.style.left = x - drag.dx + 'px';
			drag.row.style.top = y - drag.dy + 'px';

			var rows = others();
			var before = null;
			rows.some( function ( row ) {
				// Where the row sits in the layout, without the slide it may be part-way through.
				var box = row.getBoundingClientRect();
				var sliding = new window.DOMMatrixReadOnly( window.getComputedStyle( row ).transform ).m42;
				if ( y < box.top - sliding + box.height / 2 ) {
					before = row;
					return true;
				}
				return false;
			} );
			// The slot's neighbours, not counting the held row, which still sits in the list.
			var beside = function ( key ) {
				var next = drag.slot[ key ];
				return next === drag.row ? next[ key ] : next;
			};
			var last = rows[ rows.length - 1 ];
			if ( ! last || ( before ? beside( 'nextElementSibling' ) === before : beside( 'previousElementSibling' ) === last ) ) {
				return; // Already there.
			}
			// Before that row; past the last movable row, right after it (a pinned row stays first).
			var anchor = before || last.nextElementSibling;
			slide( rows, function () {
				list.insertBefore( drag.slot, anchor );
			} );
		};

		/** Lifts the row out of the list, a slot of its size in its place. */
		var lift = function ( row, event ) {
			var box = row.getBoundingClientRect();
			var style = window.getComputedStyle( row );
			var slot = document.createElement( 'LI' === row.tagName ? 'li' : 'div' );
			slot.className = 'affibloom-drag-slot';
			slot.setAttribute( 'aria-hidden', 'true' );
			slot.style.height = box.height + 'px';
			slot.style.marginTop = style.marginTop;
			slot.style.marginBottom = style.marginBottom;
			slot.style.borderRadius = style.borderRadius;
			list.insertBefore( slot, row );

			drag = { row: row, slot: slot, dx: event.clientX - box.left, dy: event.clientY - box.top };
			row.style.position = 'fixed';
			row.style.zIndex = '100000';
			row.style.width = box.width + 'px';
			row.style.height = box.height + 'px';
			row.style.margin = '0';
			row.style.pointerEvents = 'none';
			row.style.left = box.left + 'px';
			row.style.top = box.top + 'px';
			row.classList.add( 'is-dragging' );
			document.documentElement.classList.add( 'affibloom-is-dragging' );
		};

		/** Lets go: the row glides into its slot, then takes the slot's place. */
		var drop = function () {
			if ( ! drag ) {
				return;
			}
			var held = drag;
			drag = null;
			var settle = function () {
				[ 'position', 'zIndex', 'width', 'height', 'margin', 'pointerEvents', 'left', 'top', 'transition' ].forEach( function ( key ) {
					held.row.style[ key ] = '';
				} );
				list.insertBefore( held.row, held.slot );
				held.slot.remove();
				document.documentElement.classList.remove( 'affibloom-is-dragging' );
				options.dropped();
			};
			held.row.classList.remove( 'is-dragging' );
			if ( still() ) {
				settle();
				return;
			}
			var box = held.slot.getBoundingClientRect();
			held.row.style.transition = 'left ' + SLIDE + 'ms ease, top ' + SLIDE + 'ms ease, transform ' + SLIDE + 'ms ease, box-shadow ' + SLIDE + 'ms ease';
			held.row.style.left = box.left + 'px';
			held.row.style.top = box.top + 'px';
			window.setTimeout( settle, SLIDE );
		};

		list.addEventListener( 'pointerdown', function ( event ) {
			var grip = event.target.closest( '[data-grip]' );
			if ( drag || ! grip || ! list.contains( grip ) || ( 0 !== event.button && 'mouse' === event.pointerType ) ) {
				return;
			}
			event.preventDefault();
			grip.setPointerCapture( event.pointerId );
			lift( grip.closest( options.row ), event );
		} );
		list.addEventListener( 'pointermove', function ( event ) {
			if ( ! drag ) {
				return;
			}
			// Near the window's top or bottom edge the page scrolls, so a row can travel the whole list.
			var edge = 60;
			if ( event.clientY < edge ) {
				window.scrollBy( 0, -12 );
			} else if ( event.clientY > window.innerHeight - edge ) {
				window.scrollBy( 0, 12 );
			}
			follow( event.clientX, event.clientY );
		} );
		list.addEventListener( 'pointerup', drop );
		list.addEventListener( 'pointercancel', drop );

		list.addEventListener( 'keydown', function ( event ) {
			var grip = event.target.closest( '[data-grip]' );
			if ( drag || ! grip || ! list.contains( grip ) || ( 'ArrowUp' !== event.key && 'ArrowDown' !== event.key ) ) {
				return;
			}
			event.preventDefault();
			var row = grip.closest( options.row );
			var rows = options.movable();
			var up = 'ArrowUp' === event.key;
			var other = rows[ rows.indexOf( row ) + ( up ? -1 : 1 ) ];
			if ( ! other ) {
				return;
			}
			slide( [ row, other ], function () {
				list.insertBefore( row, up ? other : other.nextElementSibling );
			} );
			grip.focus(); // Moving the row in the page can drop the focus.
			var moved = options.movable();
			announce( window.wp.i18n.sprintf(
				/* translators: 1: the row's new position, 2: the number of rows. */
				window.wp.i18n.__( 'Moved to position %1$d of %2$d.', 'affibloom' ),
				moved.indexOf( row ) + 1,
				moved.length
			) );
			options.dropped();
		} );
	};
} )();
