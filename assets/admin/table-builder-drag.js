/* global wp */
/**
 * The table builder's drag-and-drop (F25 R36-R39, D9 as changed by deviation 151; Lasso's sortable, without jQuery UI).
 *
 * - A product moves by its grip or its header cell; a row (Vertical) or column (Horizontal) by its gutter; a field by
 *   its grip or its card (never from its inputs, editors, image or buttons: Lasso's cancel list). A drag starts after
 *   the pointer has moved a few pixels, so a click stays a click.
 * - While dragging, the others make room: a product's or a row's cells are placed again on the grid (grid-row and
 *   grid-column) as the pointer passes the others' middles; a field card moves into the list under the pointer, in
 *   every product at once (a field is the same for all). Each change is animated (FLIP). It looks as every other drag
 *   in the plugin (drag-rows.js; the user, 2026-11-21): what is held floats under the pointer, tilted, with a deep
 *   shadow ("is-dragging"), while grey slots its size hold its place; on the drop it glides into them. The grid scrolls
 *   when the pointer nears its edge, the window near its top or bottom. Escape puts everything back.
 * - On the drop the new order is saved (PUT /order, or POST …/move for a field that changed row or column) and the
 *   server's canvas, already the same, is drawn.
 * - Keyboard: a grip is a button. Space or Enter lifts; the arrow keys move one place (a field past the end of its cell
 *   goes on to the next row or column); Space or Enter drops; Escape or leaving the grip puts it back. Each step is
 *   announced.
 */
( function () {
	'use strict';

	var tb = window.affibloomTableBuilder;
	if ( ! tb || ! tb.id ) {
		return;
	}
	var region = tb.region;
	var page = tb.page;
	var announcer = page.querySelector( '[data-table-announce]' );
	var THRESHOLD = 5;
	var EDGE = 48;
	var SLIDE = 150; // ms, as drag-rows.js: how long things take to slide aside or into the slot.
	var CANCEL = 'input, textarea, select, a, [contenteditable], .affibloom-editor, .affibloom-table-field__price, [data-table-image], [data-table-rich], button:not([data-table-drag]):not([data-table-select])';
	var drag = null;
	var armed = null;
	var swallowClick = false;

	var text = function ( name, values ) {
		var words = page.getAttribute( 'data-drag-' + name ) || '';
		( values || [] ).forEach( function ( value, index ) {
			words = words.replace( '%' + ( index + 1 ) + '$s', value );
		} );
		return words;
	};
	var announce = function ( words ) {
		announcer.textContent = '';
		window.setTimeout( function () {
			announcer.textContent = words;
		}, 30 );
	};

	var grid = function () {
		return region.querySelector( '[data-table-grid]' );
	};
	var across = function () {
		return 'row' === page.getAttribute( 'data-table-style' );
	};
	var ordered = function ( selector, attribute, at ) {
		return Array.prototype.slice.call( region.querySelectorAll( selector ) )
			.sort( function ( a, b ) {
				return parseInt( a.getAttribute( at ), 10 ) - parseInt( b.getAttribute( at ), 10 );
			} )
			.map( function ( element ) {
				return element.getAttribute( attribute );
			} );
	};
	var productOrder = function () {
		return ordered( '.affibloom-table-cell--head', 'data-table-product', 'data-product-at' );
	};
	var groupOrder = function () {
		return ordered( '.affibloom-table-cell--gutter', 'data-table-group', 'data-group-at' );
	};
	var heading = function () {
		var head = region.querySelector( '.affibloom-table-cell--head' );
		return head ? head.getAttribute( 'data-table-group' ) : '';
	};

	// FLIP: what moves slides from where it was.
	var animate = function ( elements, change ) {
		var before = elements.map( function ( element ) {
			return element.getBoundingClientRect();
		} );
		change();
		elements.forEach( function ( element, index ) {
			var after = element.getBoundingClientRect();
			var dx = before[ index ].left - after.left;
			var dy = before[ index ].top - after.top;
			if ( ! dx && ! dy ) {
				return;
			}
			element.style.transition = 'none';
			element.style.transform = 'translate(' + dx + 'px, ' + dy + 'px)';
			element.getBoundingClientRect();
			element.style.transition = 'transform ' + SLIDE + 'ms ease';
			element.style.transform = '';
		} );
	};
	var cells = function () {
		return Array.prototype.slice.call( region.querySelectorAll( '[data-table-grid] > .affibloom-table-cell' ) );
	};

	// A product's or a row's cells placed again for an order of products and of body groups.
	var arrange = function ( products, groups ) {
		var horizontal = across();
		animate( cells(), function () {
			cells().forEach( function ( cell ) {
				var kind = cell.getAttribute( 'data-table-kind' );
				if ( 'corner' === kind ) {
					return;
				}
				var groupAt = 'head' === kind ? 0 : groups.indexOf( cell.getAttribute( 'data-table-group' ) ) + 1;
				if ( 'gutter' === kind ) {
					cell.style.gridRow = horizontal ? '1' : String( groupAt + 1 );
					cell.style.gridColumn = horizontal ? String( groupAt + 1 ) : '1';
					return;
				}
				var productAt = products.indexOf( cell.getAttribute( 'data-table-product' ) );
				cell.style.gridRow = String( horizontal ? productAt + 2 : groupAt + 1 );
				cell.style.gridColumn = String( horizontal ? groupAt + 1 : productAt + 2 );
			} );
		} );
	};

	// Where an item would go in a list, from the pointer along the list's axis.
	var slotOf = function ( elements, point, vertical ) {
		var slot = 0;
		elements.forEach( function ( element ) {
			var box = element.getBoundingClientRect();
			var middle = vertical ? box.top + box.height / 2 : box.left + box.width / 2;
			if ( point > middle ) {
				slot++;
			}
		} );
		return slot;
	};
	var moved = function ( list, item, slot ) {
		var rest = list.filter( function ( entry ) {
			return entry !== item;
		} );
		rest.splice( Math.max( 0, Math.min( slot, rest.length ) ), 0, item );
		return rest;
	};
	var same = function ( a, b ) {
		return a.length === b.length && a.every( function ( entry, index ) {
			return entry === b[ index ];
		} );
	};

	// Field cards: every product's list of a group, and the card of one field in it.
	var lists = function ( group ) {
		return Array.prototype.slice.call( region.querySelectorAll( '.affibloom-table-cell[data-table-group="' + group + '"] [data-table-fields]' ) );
	};
	var cardsIn = function ( list ) {
		return Array.prototype.slice.call( list.querySelectorAll( ':scope > .affibloom-table-field' ) );
	};
	var fieldIds = function ( list ) {
		return cardsIn( list ).map( function ( card ) {
			return card.getAttribute( 'data-table-field' );
		} );
	};
	var groupOf = function ( element ) {
		var cell = element.closest( '.affibloom-table-cell' );
		return cell ? cell.getAttribute( 'data-table-group' ) : '';
	};
	// The field moved to a group at a place, in every product.
	var placeField = function ( field, group, slot ) {
		var everyCard = Array.prototype.slice.call( region.querySelectorAll( '.affibloom-table-field, [data-table-grid] > .affibloom-table-cell' ) );
		animate( everyCard, function () {
			lists( group ).forEach( function ( list ) {
				var product = list.closest( '[data-table-product]' ).getAttribute( 'data-table-product' );
				var card = region.querySelector( '[data-table-product="' + product + '"] .affibloom-table-field[data-table-field="' + field + '"]' );
				if ( ! card ) {
					return;
				}
				var others = cardsIn( list ).filter( function ( other ) {
					return other !== card;
				} );
				var before = others[ slot ] || null;
				if ( card.parentNode !== list || card.nextElementSibling !== before ) {
					list.insertBefore( card, before );
				}
			} );
		} );
	};

	// What a drag carries.
	var describe = function ( kind, source ) {
		if ( 'product' === kind ) {
			var head = source.closest( '.affibloom-table-cell--head' );
			return { kind: kind, item: head.getAttribute( 'data-table-product' ), name: head.getAttribute( 'data-product-name' ) };
		}
		if ( 'group' === kind ) {
			var gutter = source.closest( '.affibloom-table-cell--gutter' );
			return { kind: kind, item: gutter.getAttribute( 'data-table-group' ), name: gutter.getAttribute( 'data-line-name' ) };
		}
		var card = source.closest( '.affibloom-table-field' );
		return {
			kind: kind,
			item: card.getAttribute( 'data-table-field' ),
			name: card.getAttribute( 'data-field-name' ),
			product: card.closest( '[data-table-product]' ).getAttribute( 'data-table-product' ),
			card: card,
		};
	};

	// What a pointer drag holds: a copy of the card, or of the product's (row's) cells as they stand, floating where they
	// were; the originals become the slots. Typed values are copied too (a copy has only the markup's).
	var float = function ( what, x, y ) {
		var parts = 'field' === what.kind ? [ what.card ] : Array.prototype.slice.call( region.querySelectorAll( {
			product: '[data-table-grid] > .affibloom-table-cell[data-table-product="' + what.item + '"]',
			group: '[data-table-grid] > .affibloom-table-cell[data-table-group="' + what.item + '"]:not(.affibloom-table-cell--head)',
		}[ what.kind ] ) );
		var boxes = parts.map( function ( part ) {
			return part.getBoundingClientRect();
		} );
		var left = Math.min.apply( null, boxes.map( function ( box ) {
			return box.left;
		} ) );
		var top = Math.min.apply( null, boxes.map( function ( box ) {
			return box.top;
		} ) );
		var held = document.createElement( 'div' );
		held.className = 'affibloom-table-held affibloom-table-held--' + what.kind;
		held.setAttribute( 'aria-hidden', 'true' );
		held.style.left = left + 'px';
		held.style.top = top + 'px';
		held.style.width = Math.max.apply( null, boxes.map( function ( box ) {
			return box.right;
		} ) ) - left + 'px';
		held.style.height = Math.max.apply( null, boxes.map( function ( box ) {
			return box.bottom;
		} ) ) - top + 'px';
		parts.forEach( function ( part, index ) {
			var copy = part.cloneNode( true );
			var fields = part.querySelectorAll( 'input, textarea' );
			copy.querySelectorAll( 'input, textarea' ).forEach( function ( field, at ) {
				field.value = fields[ at ].value;
			} );
			// A picture only: no ids, no data the builder's scripts look for, nothing to focus.
			[ copy ].concat( Array.prototype.slice.call( copy.querySelectorAll( '*' ) ) ).forEach( function ( element ) {
				Array.prototype.slice.call( element.attributes ).forEach( function ( attribute ) {
					if ( 'id' === attribute.name || 'for' === attribute.name || 0 === attribute.name.indexOf( 'data-' ) || 0 === attribute.name.indexOf( 'aria-' ) ) {
						element.removeAttribute( attribute.name );
					}
				} );
				if ( element.matches( 'button, input, textarea, [tabindex]' ) ) {
					element.setAttribute( 'tabindex', '-1' );
				}
			} );
			copy.classList.remove( 'is-selected', 'is-selected-own' );
			copy.style.position = 'absolute';
			copy.style.left = boxes[ index ].left - left + 'px';
			copy.style.top = boxes[ index ].top - top + 'px';
			copy.style.width = boxes[ index ].width + 'px';
			copy.style.height = boxes[ index ].height + 'px';
			copy.style.margin = '0';
			held.appendChild( copy );
		} );
		// Inside the admin wrapper, for its colours, fonts and the shared drag look.
		( page.closest( '.affibloom-admin' ) || document.body ).appendChild( held );
		held.getBoundingClientRect();
		held.classList.add( 'is-dragging' );
		return { element: held, dx: x - left, dy: y - top };
	};
	// The held copy glides into the slots (where the item now is), then the slots show it again.
	var settle = function ( done ) {
		var held = done.held;
		lifted( done, false );
		if ( ! held ) {
			return;
		}
		var slots = Array.prototype.slice.call( region.querySelectorAll( slotSelector( done ) ) );
		held.element.classList.remove( 'is-dragging' );
		if ( 0 === slots.length ) {
			held.element.remove();
			return;
		}
		lifted( done, true );
		var first = slots[ 0 ].getBoundingClientRect();
		var lefts = slots.map( function ( slot ) {
			return slot.getBoundingClientRect().left;
		} );
		var tops = slots.map( function ( slot ) {
			return slot.getBoundingClientRect().top;
		} );
		held.element.style.transition = 'left ' + SLIDE + 'ms ease, top ' + SLIDE + 'ms ease, transform ' + SLIDE + 'ms ease, box-shadow ' + SLIDE + 'ms ease';
		held.element.style.left = ( 'field' === done.kind ? first.left : Math.min.apply( null, lefts ) ) + 'px';
		held.element.style.top = ( 'field' === done.kind ? first.top : Math.min.apply( null, tops ) ) + 'px';
		window.setTimeout( function () {
			held.element.remove();
			lifted( done, false );
		}, SLIDE );
	};

	var lift = function ( kind, source, keyboard, x, y ) {
		tb.flush();
		var what = describe( kind, source );
		drag = Object.assign( what, {
			keyboard: keyboard,
			grip: source.closest( '.affibloom-table-cell, .affibloom-table-field' ).querySelector( '[data-table-drag]' ),
			products: productOrder(),
			groups: groupOrder(),
			from: 'field' === kind ? groupOf( what.card ) : '',
		} );
		drag.startProducts = drag.products.slice();
		drag.startGroups = drag.groups.slice();
		if ( 'field' === kind ) {
			drag.group = drag.from;
			drag.slot = fieldIds( what.card.parentNode ).indexOf( what.item );
			drag.startSlot = drag.slot;
			// Each product's card of this field, where it was, to put back on Escape.
			drag.homes = Array.prototype.map.call( region.querySelectorAll( '.affibloom-table-field[data-table-field="' + what.item + '"]' ), function ( card ) {
				return { card: card, parent: card.parentNode, next: card.nextElementSibling };
			} );
		}
		if ( ! keyboard ) {
			drag.held = float( what, x, y );
		}
		lifted( drag, true );
		page.classList.add( 'is-table-dragging' );
		document.documentElement.classList.add( 'affibloom-is-dragging' );
		announce( text( 'lifted', [ what.name, String( position() ), String( total() ) ] ) );
	};

	// The lifted item's elements, marked.
	var slotSelector = function ( what ) {
		return {
			product: '[data-table-grid] > .affibloom-table-cell[data-table-product="' + what.item + '"]',
			group: '[data-table-grid] > .affibloom-table-cell[data-table-group="' + what.item + '"]:not(.affibloom-table-cell--head)',
			field: 'field' === what.kind && what.product ? '.affibloom-table-cell[data-table-product="' + what.product + '"] .affibloom-table-field[data-table-field="' + what.item + '"]' : '',
		}[ what.kind ];
	};
	// A pointer drag leaves grey slots (every product's card of a field); a keyboard drag outlines what it holds.
	var lifted = function ( what, on ) {
		var selector = 'field' === what.kind ? '.affibloom-table-field[data-table-field="' + what.item + '"]' : slotSelector( what );
		region.querySelectorAll( selector ).forEach( function ( element ) {
			element.classList.toggle( what.keyboard ? 'is-lifted' : 'is-slot', on );
		} );
	};
	var position = function () {
		if ( 'product' === drag.kind ) {
			return drag.products.indexOf( drag.item ) + 1;
		}
		if ( 'group' === drag.kind ) {
			return drag.groups.indexOf( drag.item ) + 1;
		}
		return drag.slot + 1;
	};
	var total = function () {
		if ( 'product' === drag.kind ) {
			return drag.products.length;
		}
		if ( 'group' === drag.kind ) {
			return drag.groups.length;
		}
		var list = region.querySelector( '[data-table-product="' + drag.product + '"].affibloom-table-cell[data-table-group="' + drag.group + '"] [data-table-fields]' );
		return list ? cardsIn( list ).length : 1;
	};

	// The pointer at (x, y): the lifted item goes where it points.
	var follow = function ( x, y ) {
		if ( drag.held ) {
			drag.held.element.style.left = x - drag.held.dx + 'px';
			drag.held.element.style.top = y - drag.held.dy + 'px';
		}
		var horizontal = across();
		if ( 'product' === drag.kind || 'group' === drag.kind ) {
			var isProduct = 'product' === drag.kind;
			var others = Array.prototype.slice.call( region.querySelectorAll( isProduct ? '.affibloom-table-cell--head' : '.affibloom-table-cell--gutter' ) )
				.filter( function ( cell ) {
					return cell.getAttribute( isProduct ? 'data-table-product' : 'data-table-group' ) !== drag.item;
				} );
			// Products run across a Vertical table and down a Horizontal one; rows and columns the other way.
			var vertical = isProduct ? horizontal : ! horizontal;
			var slot = slotOf( others, vertical ? y : x, vertical );
			var list = isProduct ? drag.products : drag.groups;
			var next = moved( list, drag.item, slot );
			if ( ! same( next, list ) ) {
				if ( isProduct ) {
					drag.products = next;
				} else {
					drag.groups = next;
				}
				arrange( drag.products, drag.groups );
			}
			return;
		}
		// A field: the group under the pointer (a gutter counts for its row or column), and the place in that list.
		var under = document.elementFromPoint( x, y );
		var cell = under && region.contains( under ) ? under.closest( '[data-table-grid] > .affibloom-table-cell' ) : null;
		if ( ! cell || cell.classList.contains( 'affibloom-table-cell--corner' ) ) {
			return;
		}
		var group = cell.getAttribute( 'data-table-group' );
		var list = cell.querySelector( '[data-table-fields]' ) || lists( group )[ 0 ];
		if ( ! list ) {
			return;
		}
		var others = cardsIn( list ).filter( function ( card ) {
			return card.getAttribute( 'data-table-field' ) !== drag.item;
		} );
		var slot = slotOf( others, y, true );
		if ( group !== drag.group || slot !== drag.slot ) {
			drag.group = group;
			drag.slot = slot;
			placeField( drag.item, group, slot );
		}
	};

	// The grid and the window scroll while the pointer is near their edges.
	var autoscroll = function () {
		if ( ! drag || drag.keyboard || ! drag.point ) {
			return;
		}
		var scroller = region.querySelector( '[data-table-scroll]' );
		var x = drag.point.x;
		var y = drag.point.y;
		var stepped = false;
		if ( scroller ) {
			var box = scroller.getBoundingClientRect();
			if ( x < box.left + EDGE && scroller.scrollLeft > 0 ) {
				scroller.scrollLeft -= 12;
				stepped = true;
			} else if ( x > box.right - EDGE && scroller.scrollLeft < scroller.scrollWidth - scroller.clientWidth ) {
				scroller.scrollLeft += 12;
				stepped = true;
			}
		}
		if ( y < EDGE ) {
			window.scrollBy( 0, -12 );
			stepped = true;
		} else if ( y > window.innerHeight - EDGE ) {
			window.scrollBy( 0, 12 );
			stepped = true;
		}
		if ( stepped ) {
			follow( x, y );
		}
		drag.frame = window.requestAnimationFrame( autoscroll );
	};

	var finish = function ( keep ) {
		if ( ! drag ) {
			return;
		}
		var done = drag;
		drag = null;
		window.cancelAnimationFrame( done.frame );
		page.classList.remove( 'is-table-dragging' );
		document.documentElement.classList.remove( 'affibloom-is-dragging' );

		if ( ! keep ) {
			if ( 'field' === done.kind ) {
				done.homes.slice().reverse().forEach( function ( home ) {
					home.parent.insertBefore( home.card, home.next && home.next.parentNode === home.parent ? home.next : null );
				} );
			} else {
				arrange( done.startProducts, done.startGroups );
			}
			settle( done );
			announce( text( 'cancelled', [ done.name ] ) );
			if ( done.keyboard && done.grip ) {
				done.grip.focus();
			}
			return;
		}
		settle( done );
		save( done );
	};

	var save = function ( done ) {
		var send = null;
		if ( 'product' === done.kind && ! same( done.products, done.startProducts ) ) {
			send = { products: done.products.map( Number ) };
		} else if ( 'group' === done.kind && ! same( done.groups, done.startGroups ) ) {
			send = { groups: done.groups };
		}
		if ( send ) {
			tb.enqueue( {
				send: function ( current ) {
					return wp.apiFetch( { path: tb.path + '/order', method: 'PUT', data: Object.assign( { revision: current }, send ) } );
				},
			} );
		}
		if ( 'field' === done.kind ) {
			var list = region.querySelector( '[data-table-product="' + done.product + '"].affibloom-table-cell[data-table-group="' + done.group + '"] [data-table-fields]' );
			var order = list ? fieldIds( list ) : [];
			if ( done.group !== done.from ) {
				tb.enqueue( {
					send: function ( current ) {
						return wp.apiFetch( {
							path: tb.path + '/groups/' + encodeURIComponent( done.from ) + '/fields/' + done.item + '/move',
							method: 'POST',
							data: { revision: current, to_group: done.group, position: Math.max( 0, order.indexOf( done.item ) ) },
						} );
					},
				} );
			} else if ( done.slot !== done.startSlot ) {
				tb.enqueue( {
					send: function ( current ) {
						return wp.apiFetch( {
							path: tb.path + '/order',
							method: 'PUT',
							data: { revision: current, cell: { group: done.group, fields: order.map( Number ) } },
						} );
					},
				} );
			}
		}
		announce( text( 'dropped', [ done.name, String( positionOf( done ) ), String( totalOf( done ) ) ] ) );
	};
	var positionOf = function ( done ) {
		var keep = drag;
		drag = done;
		var at = position();
		drag = keep;
		return at;
	};
	var totalOf = function ( done ) {
		var keep = drag;
		drag = done;
		var count = total();
		drag = keep;
		return count;
	};

	// The pointer: a press on a grip, a header cell, a gutter or a field card arms a drag; moving a few pixels starts it.
	var sourceOf = function ( target ) {
		var grip = target.closest( '[data-table-drag]' );
		if ( grip ) {
			return { kind: grip.getAttribute( 'data-table-drag' ), element: grip };
		}
		if ( target.closest( CANCEL ) ) {
			return null;
		}
		var card = target.closest( '.affibloom-table-field' );
		if ( card ) {
			return { kind: 'field', element: card };
		}
		var gutter = target.closest( '.affibloom-table-cell--gutter' );
		if ( gutter ) {
			return { kind: 'group', element: gutter };
		}
		var bar = target.closest( '.affibloom-table-cell__bar' );
		if ( bar ) {
			return { kind: 'product', element: bar };
		}
		return null;
	};
	region.addEventListener( 'pointerdown', function ( event ) {
		if ( drag || 0 !== event.button || ! grid() ) {
			return;
		}
		var source = sourceOf( event.target );
		if ( ! source ) {
			return;
		}
		armed = { source: source, x: event.clientX, y: event.clientY, id: event.pointerId };
	} );
	document.addEventListener( 'pointermove', function ( event ) {
		if ( armed && ! drag ) {
			if ( Math.abs( event.clientX - armed.x ) + Math.abs( event.clientY - armed.y ) < THRESHOLD ) {
				return;
			}
			lift( armed.source.kind, armed.source.element, false, armed.x, armed.y );
			armed = null;
			drag.frame = window.requestAnimationFrame( autoscroll );
		}
		if ( drag && ! drag.keyboard ) {
			event.preventDefault();
			drag.point = { x: event.clientX, y: event.clientY };
			follow( event.clientX, event.clientY );
		}
	} );
	document.addEventListener( 'pointerup', function () {
		armed = null;
		if ( drag && ! drag.keyboard ) {
			swallowClick = true;
			window.setTimeout( function () {
				swallowClick = false;
			}, 0 );
			finish( true );
		}
	} );
	document.addEventListener( 'pointercancel', function () {
		armed = null;
		if ( drag && ! drag.keyboard ) {
			finish( false );
		}
	} );
	// A drag's release is not a click on what lies under it.
	region.addEventListener( 'click', function ( event ) {
		if ( swallowClick ) {
			event.preventDefault();
			event.stopPropagation();
		}
	}, true );
	region.addEventListener( 'dragstart', function ( event ) {
		event.preventDefault();
	} );

	// The keyboard.
	var stepKeyboard = function ( by ) {
		if ( 'product' === drag.kind || 'group' === drag.kind ) {
			var isProduct = 'product' === drag.kind;
			var list = isProduct ? drag.products : drag.groups;
			var at = list.indexOf( drag.item );
			if ( at + by < 0 || at + by >= list.length ) {
				return;
			}
			var next = moved( list, drag.item, at + by );
			if ( isProduct ) {
				drag.products = next;
			} else {
				drag.groups = next;
			}
			arrange( drag.products, drag.groups );
		} else {
			var all = [ heading() ].concat( drag.groups );
			var count = total();
			var slot = drag.slot + by;
			var group = drag.group;
			if ( slot < 0 || slot >= count ) {
				var g = all.indexOf( group ) + ( by < 0 ? -1 : 1 );
				if ( g < 0 || g >= all.length ) {
					return;
				}
				group = all[ g ];
				var target = region.querySelector( '[data-table-product="' + drag.product + '"].affibloom-table-cell[data-table-group="' + group + '"] [data-table-fields]' );
				slot = by < 0 && target ? cardsIn( target ).length : 0;
			}
			drag.group = group;
			drag.slot = slot;
			placeField( drag.item, group, slot );
			drag.grip.focus();
		}
		announce( text( 'moved', [ drag.name, String( position() ), String( total() ) ] ) );
	};
	region.addEventListener( 'keydown', function ( event ) {
		var grip = event.target.closest( '[data-table-drag]' );
		if ( ! grip ) {
			return;
		}
		var key = event.key;
		if ( ! drag ) {
			if ( ' ' === key || 'Enter' === key ) {
				event.preventDefault();
				lift( grip.getAttribute( 'data-table-drag' ), grip, true );
			}
			return;
		}
		if ( ! drag.keyboard ) {
			return;
		}
		if ( ' ' === key || 'Enter' === key ) {
			event.preventDefault();
			finish( true );
		} else if ( 'Escape' === key ) {
			event.preventDefault();
			finish( false );
		} else if ( 'ArrowUp' === key || 'ArrowLeft' === key ) {
			event.preventDefault();
			stepKeyboard( -1 );
		} else if ( 'ArrowDown' === key || 'ArrowRight' === key ) {
			event.preventDefault();
			stepKeyboard( 1 );
		} else if ( 'Tab' === key ) {
			finish( false );
		}
	} );
	document.addEventListener( 'keydown', function ( event ) {
		if ( drag && ! drag.keyboard && 'Escape' === event.key ) {
			event.preventDefault();
			finish( false );
		}
	} );
	// A redraw while dragging (a save that was out) ends the drag where the server put things.
	tb.on( 'drawn', function () {
		if ( drag ) {
			if ( drag.held ) {
				drag.held.element.remove();
			}
			window.cancelAnimationFrame( drag.frame );
			drag = null;
			page.classList.remove( 'is-table-dragging' );
			document.documentElement.classList.remove( 'affibloom-is-dragging' );
		}
	} );
}() );
