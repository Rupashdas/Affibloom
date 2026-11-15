/**
 * Rows put in a new order by dragging their grip ([data-grip]), with pointer events: the row follows the pointer
 * through its neighbours (a group's Links tab, Task G4; a product's fields, Task FL5; its buttons and features, as
 * Lasso drags its rows, the user 2026-11-16). Taken out of group-members.js on its second use. Only the rows movable()
 * returns take part, so a pinned row keeps its place (Primary Rating); the dragged row carries "is-dragging"; dropped()
 * runs when the pointer lets go, to save the order. A grip that is a button also moves its row from the keyboard:
 * Arrow Up and Arrow Down swap it with the movable row before or after it, the focus staying on the grip.
 */
( function () {
	'use strict';

	/**
	 * @param {HTMLElement} list    The rows' parent.
	 * @param {Object}      options row: a row's selector; movable(): the rows that move; dropped(): after a drop.
	 */
	window.affibloomDragRows = function ( list, options ) {
		var dragging = null;

		list.addEventListener( 'pointerdown', function ( event ) {
			var grip = event.target.closest( '[data-grip]' );
			if ( ! grip || ! list.contains( grip ) || ( 0 !== event.button && 'mouse' === event.pointerType ) ) {
				return;
			}
			event.preventDefault();
			dragging = grip.closest( options.row );
			dragging.classList.add( 'is-dragging' );
			grip.setPointerCapture( event.pointerId );
		} );
		list.addEventListener( 'pointermove', function ( event ) {
			if ( ! dragging ) {
				return;
			}
			var before = null;
			options.movable().some( function ( row ) {
				if ( row === dragging ) {
					return false;
				}
				var box = row.getBoundingClientRect();
				if ( event.clientY < box.top + box.height / 2 ) {
					before = row;
					return true;
				}
				return false;
			} );
			if ( before !== dragging.nextElementSibling ) {
				list.insertBefore( dragging, before );
			}
		} );
		var drop = function () {
			if ( ! dragging ) {
				return;
			}
			dragging.classList.remove( 'is-dragging' );
			dragging = null;
			options.dropped();
		};
		list.addEventListener( 'pointerup', drop );
		list.addEventListener( 'pointercancel', drop );

		list.addEventListener( 'keydown', function ( event ) {
			var grip = event.target.closest( '[data-grip]' );
			if ( ! grip || ! list.contains( grip ) || ( 'ArrowUp' !== event.key && 'ArrowDown' !== event.key ) ) {
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
			list.insertBefore( row, up ? other : other.nextElementSibling );
			grip.focus(); // Moving the row in the page can drop the focus.
			options.dropped();
		} );
	};
} )();
