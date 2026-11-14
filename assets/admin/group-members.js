/* global wp */
/**
 * Affibloom group Links tab (F23, Task G4): the group's products in its order, the selection bar, Remove, the order by
 * dragging or by the Move buttons, and the "Add to Group" dialog.
 *
 * The rows are drawn by the server (partials/group-member-row.php); this file moves, ticks and removes them and sends
 * the order back. Every change is sent as it is made, as Lasso's drag is (R19); a refused request says so in a toast.
 */
( function () {
	'use strict';

	var root = document.getElementById( 'affibloom-members' );
	if ( ! root ) {
		return;
	}

	var groupId = parseInt( root.dataset.groupId, 10 );
	var list = root.querySelector( '[data-members]' );
	var empty = root.querySelector( '[data-members-empty]' );
	var bar = root.querySelector( '.affibloom-selection' );
	var status = document.getElementById( 'affibloom-members-status' );
	var base = '/affibloom/v1/groups/' + groupId;

	function rows() {
		return Array.prototype.slice.call( list.querySelectorAll( '.affibloom-member' ) );
	}

	function toast( message ) {
		if ( window.affibloomToast ) {
			window.affibloomToast( message, true );
		}
	}

	function say( text ) {
		status.textContent = text;
	}

	/** The tab's number, the empty message and the buttons that cannot move further. */
	function refresh() {
		var all = rows();
		var tab = document.querySelector( '.affibloom-tabs__tab' );
		if ( tab ) {
			tab.textContent = tab.dataset.template ? tab.dataset.template.replace( '%s', all.length ) : tab.textContent.replace( /\d[\d,.]*\s*$/, String( all.length ) );
		}
		var del = document.querySelector( '[data-group-delete]' );
		if ( del ) {
			del.dataset.members = String( all.length );
		}
		empty.hidden = all.length > 0;
		all.forEach( function ( row, index ) {
			row.querySelector( '[data-move="-1"]' ).disabled = 0 === index;
			row.querySelector( '[data-move="1"]' ).disabled = all.length - 1 === index;
		} );
		syncSelection();
	}

	/* ---------------------------------------------------------------------------------------------------------
	 * Selection: the Dashboard's bar, here with Remove.
	 * ------------------------------------------------------------------------------------------------------- */
	function ticked() {
		return rows().filter( function ( row ) {
			return row.querySelector( '.affibloom-check' ).checked;
		} );
	}

	function syncSelection() {
		var on = ticked();
		var count = bar.querySelector( '.affibloom-selection__count' );
		bar.hidden = 0 === on.length;
		count.textContent = ( 1 === on.length ? count.dataset.one : count.dataset.many ).replace( '%s', String( on.length ) );
		rows().forEach( function ( row ) {
			row.classList.toggle( 'is-selected', row.querySelector( '.affibloom-check' ).checked );
		} );
	}

	list.addEventListener( 'change', function ( event ) {
		if ( event.target.classList.contains( 'affibloom-check' ) ) {
			syncSelection();
		}
	} );
	bar.querySelector( '[data-select-all]' ).addEventListener( 'click', function () {
		rows().forEach( function ( row ) {
			row.querySelector( '.affibloom-check' ).checked = true;
		} );
		syncSelection();
	} );
	bar.querySelector( '[data-clear-selection]' ).addEventListener( 'click', function () {
		rows().forEach( function ( row ) {
			row.querySelector( '.affibloom-check' ).checked = false;
		} );
		syncSelection();
	} );

	/* ---------------------------------------------------------------------------------------------------------
	 * Remove: one row's button or the bar's; the dialogs are Lasso's two questions. Only the group's link goes.
	 * ------------------------------------------------------------------------------------------------------- */
	var pending = [];

	list.addEventListener( 'click', function ( event ) {
		var one = event.target.closest( '[data-remove-one]' );
		if ( one ) {
			pending = [ parseInt( one.dataset.removeOne, 10 ) ];
		}
	} );
	bar.addEventListener( 'click', function ( event ) {
		if ( event.target.closest( '[data-affibloom-open="affibloom-members-remove-many"]' ) ) {
			pending = ticked().map( function ( row ) {
				return parseInt( row.dataset.productId, 10 );
			} );
		}
	} );

	Array.prototype.forEach.call( document.querySelectorAll( '[data-remove-confirm]' ), function ( confirm ) {
		var label = confirm.textContent;
		var working = false;
		confirm.addEventListener( 'click', function () {
			if ( working || 0 === pending.length ) {
				return;
			}
			working = true;
			confirm.disabled = true;
			confirm.textContent = confirm.dataset.busyLabel;
			var query = pending.map( function ( id ) {
				return 'ids[]=' + id;
			} ).join( '&' );
			wp.apiFetch( { path: base + '/products?' + query, method: 'DELETE' } ).then( function ( response ) {
				response.data.removed.forEach( function ( id ) {
					var row = list.querySelector( '[data-product-id="' + id + '"]' );
					if ( row ) {
						row.remove();
					}
				} );
				pending = [];
				refresh();
			} ).catch( function () {
				toast( root.dataset.failedText );
			} ).then( function () {
				working = false;
				confirm.disabled = false;
				confirm.textContent = label;
				window.affibloomModal.hide();
			} );
		} );
	} );

	/* ---------------------------------------------------------------------------------------------------------
	 * Order: the whole list is sent after each move or drop (R19, D7).
	 * ------------------------------------------------------------------------------------------------------- */
	function saveOrder() {
		var ids = rows().map( function ( row ) {
			return parseInt( row.dataset.productId, 10 );
		} );
		return wp.apiFetch( { path: base + '/order', method: 'PUT', data: { ids: ids } } ).catch( function () {
			toast( root.dataset.orderFailedText );
		} );
	}

	list.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '[data-move]' );
		if ( ! button || button.disabled ) {
			return;
		}
		var row = button.closest( '.affibloom-member' );
		var direction = parseInt( button.dataset.move, 10 );
		var other = direction < 0 ? row.previousElementSibling : row.nextElementSibling;
		if ( ! other ) {
			return;
		}
		if ( direction < 0 ) {
			list.insertBefore( row, other );
		} else {
			list.insertBefore( other, row );
		}
		refresh();
		// The button that moved with the row keeps the focus, unless it can go no further that way.
		( button.disabled ? row.querySelector( '[data-move="' + ( -direction ) + '"]' ) : button ).focus();
		say( row.dataset.name + ': ' + ( rows().indexOf( row ) + 1 ) + ' / ' + rows().length );
		saveOrder();
	} );

	// Dragging by the grip, with pointer events: the row follows the pointer through its neighbours.
	var dragging = null;

	list.addEventListener( 'pointerdown', function ( event ) {
		var grip = event.target.closest( '[data-grip]' );
		if ( ! grip || ( 0 !== event.button && 'mouse' === event.pointerType ) ) {
			return;
		}
		event.preventDefault();
		dragging = grip.closest( '.affibloom-member' );
		dragging.classList.add( 'is-dragging' );
		grip.setPointerCapture( event.pointerId );
	} );
	list.addEventListener( 'pointermove', function ( event ) {
		if ( ! dragging ) {
			return;
		}
		var others = rows().filter( function ( row ) {
			return row !== dragging;
		} );
		var before = null;
		others.some( function ( row ) {
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
	function drop() {
		if ( ! dragging ) {
			return;
		}
		dragging.classList.remove( 'is-dragging' );
		dragging = null;
		refresh();
		saveOrder();
	}
	list.addEventListener( 'pointerup', drop );
	list.addEventListener( 'pointercancel', drop );

	refresh();

	/* ---------------------------------------------------------------------------------------------------------
	 * Add to Group (R20): search, ten a page, Add on each result; the row appears last at once.
	 * ------------------------------------------------------------------------------------------------------- */
	var modal = document.getElementById( 'affibloom-add-to-group' );
	if ( ! modal ) {
		return;
	}
	var input = modal.querySelector( '#affibloom-add-to-group-search' );
	var results = modal.querySelector( '[data-add-results]' );
	var pages = modal.querySelector( '[data-add-pages]' );
	var failure = modal.querySelector( '[data-add-error]' );
	var term = '';
	var page = 1;
	var requests = 0;
	var timer = null;

	function problem( text ) {
		failure.hidden = '' === text;
		failure.textContent = text;
	}

	function resultRow( item ) {
		var li = document.createElement( 'li' );
		li.className = 'affibloom-add-results__item';
		li.dataset.productId = String( item.id );
		var picture = document.createElement( 'span' );
		picture.className = 'affibloom-add-results__image';
		if ( item.image_url ) {
			var img = document.createElement( 'img' );
			img.className = 'affibloom-thumb';
			img.src = item.image_url;
			img.alt = '';
			img.width = 40;
			img.height = 40;
			picture.appendChild( img );
		}
		var text = document.createElement( 'span' );
		text.className = 'affibloom-add-results__text';
		var name = document.createElement( 'strong' );
		name.textContent = item.name;
		var url = document.createElement( 'span' );
		url.textContent = item.url;
		text.appendChild( name );
		text.appendChild( url );
		var add = document.createElement( 'button' );
		add.type = 'button';
		add.className = 'affibloom-button affibloom-button--small affibloom-button--secondary';
		add.textContent = item.in_group ? modal.dataset.addedLabel : modal.dataset.addLabel;
		add.disabled = item.in_group;
		add.setAttribute( 'aria-label', add.textContent + ': ' + item.name );
		add.addEventListener( 'click', function () {
			addProduct( item, add );
		} );
		li.appendChild( picture );
		li.appendChild( text );
		li.appendChild( add );
		return li;
	}

	function pager( data ) {
		pages.innerHTML = '';
		pages.hidden = data.pages < 2;
		if ( data.pages < 2 ) {
			return;
		}
		[ [ -1, modal.dataset.prevLabel, data.page > 1 ], [ 1, modal.dataset.nextLabel, data.page < data.pages ] ].forEach( function ( step ) {
			var button = document.createElement( 'button' );
			button.type = 'button';
			button.className = 'affibloom-button affibloom-button--small affibloom-button--secondary';
			button.textContent = step[ 1 ];
			button.disabled = ! step[ 2 ];
			button.addEventListener( 'click', function () {
				page = data.page + step[ 0 ];
				search();
			} );
			pages.appendChild( button );
		} );
		var where = document.createElement( 'span' );
		where.textContent = data.page + ' / ' + data.pages;
		pages.insertBefore( where, pages.lastChild );
	}

	function search() {
		var mine = ++requests;
		var args = new URLSearchParams();
		args.set( 'search', term );
		args.set( 'page', String( page ) );
		args.set( 'group', String( groupId ) );
		wp.apiFetch( { path: '/affibloom/v1/products?' + args.toString() } ).then( function ( response ) {
			if ( mine !== requests ) {
				return;
			}
			var data = response.data;
			results.innerHTML = '';
			data.items.forEach( function ( item ) {
				results.appendChild( resultRow( item ) );
			} );
			if ( 0 === data.items.length ) {
				var none = document.createElement( 'li' );
				none.className = 'affibloom-add-results__none';
				none.textContent = modal.dataset.noneText;
				results.appendChild( none );
			}
			pager( data );
		} ).catch( function () {
			if ( mine === requests ) {
				problem( modal.dataset.failedText );
			}
		} );
	}

	function addProduct( item, button ) {
		problem( '' );
		button.disabled = true;
		wp.apiFetch( { path: base + '/products', method: 'POST', data: { product: item.id } } ).then( function ( response ) {
			var holder = document.createElement( 'ul' );
			holder.innerHTML = response.data.html;
			list.appendChild( holder.firstElementChild );
			button.textContent = modal.dataset.addedLabel;
			refresh();
		} ).catch( function ( error ) {
			if ( error && 'affibloom_already_in_group' === error.code ) {
				button.textContent = modal.dataset.addedLabel;
				problem( modal.dataset.existsText );
			} else {
				button.disabled = false;
				problem( modal.dataset.failedText );
			}
		} );
	}

	input.addEventListener( 'input', function () {
		window.clearTimeout( timer );
		timer = window.setTimeout( function () {
			term = input.value;
			page = 1;
			search();
		}, 250 );
	} );
	modal.addEventListener( 'affibloom:open', function () {
		problem( '' );
		term = input.value;
		page = 1;
		search();
	} );
}() );
