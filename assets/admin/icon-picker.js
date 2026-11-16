/**
 * Icon picker for button and badge texts (F28, Task 14G; register U11).
 *
 * Each `[data-icon-field]` (templates/admin/partials/icon-text-field.php) holds the stored text field, a words box and
 * a picker button with its menu. This script hides the stored field, shows the other two, points the label at the
 * words box, and writes the stored field from them on every change, in the site's own form:
 * `<span class="<class>"></span> <words>`, or the words alone (D3). Opening and closing the menu (click, a click
 * elsewhere, Escape) is admin.js's shared Menu; this adds the choice, the keyboard and the position.
 */
( function () {
	'use strict';

	/**
	 * Places an open menu against the viewport under its button (above it when there is no room), so a scrolling
	 * rail or a card's clip cannot hide it, as the help tips do.
	 *
	 * @param {HTMLElement} button The picker button.
	 * @param {HTMLElement} menu   Its menu.
	 */
	function place( button, menu ) {
		var box = button.getBoundingClientRect();
		var height = menu.offsetHeight;
		var below = box.bottom + 6 + height <= window.innerHeight;
		menu.style.top = ( below ? box.bottom + 6 : Math.max( 8, box.top - 6 - height ) ) + 'px';
		menu.style.left = Math.max( 8, Math.min( box.left, window.innerWidth - menu.offsetWidth - 8 ) ) + 'px';
	}

	/**
	 * @param {HTMLElement} field The `[data-icon-field]` wrapper.
	 */
	function picker( field ) {
		var stored = field.querySelector( '[data-icon-raw]' );
		var words = field.querySelector( '.affibloom-icon-field__words' );
		var button = field.querySelector( '.affibloom-icon-field__button' );
		var menu = field.querySelector( '.affibloom-icon-menu' );
		// Read each time: Add icon and Remove on the Display screen change the menu (display-settings.js).
		var items = function () {
			return Array.prototype.slice.call( menu.querySelectorAll( '[role="menuitemradio"]' ) );
		};
		var label = document.querySelector( 'label[for="' + stored.id + '"]' );
		var chosen = field.dataset.iconClass || '';

		stored.hidden = true;
		words.hidden = false;
		button.hidden = false;
		if ( label ) {
			label.htmlFor = words.id;
		}

		var compose = function () {
			var text = words.value;
			stored.value = chosen ? '<span class="' + chosen + '"></span>' + ( text ? ' ' + text : '' ) : text;
			// Forms that watch for changes (Save kept disabled until one) see the stored field change.
			stored.dispatchEvent( new Event( 'input', { bubbles: true } ) );
			stored.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		};

		var choose = function ( item ) {
			chosen = item.dataset.icon;
			items().forEach( function ( other ) {
				other.setAttribute( 'aria-checked', other === item ? 'true' : 'false' );
			} );
			var name = item.querySelector( '.affibloom-menu__title' ).textContent.trim();
			button.replaceChildren( item.firstElementChild.cloneNode( true ) );
			button.title = name;
			button.setAttribute( 'aria-label', field.dataset.nameFormat.replace( '%1$s', field.dataset.label ).replace( '%2$s', name ) );
			compose();
			menu.hidden = true;
			button.setAttribute( 'aria-expanded', 'false' );
			button.focus();
		};

		words.addEventListener( 'input', compose );

		// After admin.js has opened the menu: place it and put the focus on the current choice.
		button.addEventListener( 'click', function () {
			window.setTimeout( function () {
				if ( ! menu.hidden ) {
					place( button, menu );
					( menu.querySelector( '[aria-checked="true"]' ) || items()[ 0 ] ).focus( { preventScroll: true } );
				}
			}, 0 );
		} );

		menu.addEventListener( 'click', function ( event ) {
			var item = event.target.closest( '[role="menuitemradio"]' );
			if ( item ) {
				choose( item );
			}
		} );

		menu.addEventListener( 'keydown', function ( event ) {
			var all = items();
			var index = all.indexOf( document.activeElement );
			var next = {
				ArrowDown: all[ ( index + 1 ) % all.length ],
				ArrowUp: all[ ( index - 1 + all.length ) % all.length ],
				Home: all[ 0 ],
				End: all[ all.length - 1 ],
			}[ event.key ];
			if ( next ) {
				event.preventDefault();
				next.focus( { preventScroll: true } );
			} else if ( 'Tab' === event.key ) {
				menu.hidden = true;
				button.setAttribute( 'aria-expanded', 'false' );
			}
		} );

		// A fixed menu would drift from its button: when the page or the rail scrolls it follows the button, and closes
		// once the button has left the screen. The page also scrolls by itself (a preview finishing its height moves
		// what is above), so a scroll alone is no reason to close. Scrolling inside the menu itself changes nothing.
		window.addEventListener( 'scroll', function ( event ) {
			if ( menu.hidden || menu.contains( event.target ) ) {
				return;
			}
			var box = button.getBoundingClientRect();
			if ( box.bottom < 0 || box.top > window.innerHeight ) {
				menu.hidden = true;
				button.setAttribute( 'aria-expanded', 'false' );
				return;
			}
			place( button, menu );
		}, true );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-icon-field]' ), picker );
	} );

	// A field added after load (the product page's "Add Another Button", F29) gets its picker too.
	document.addEventListener( 'affibloom:enhance', function ( event ) {
		Array.prototype.forEach.call( event.target.querySelectorAll( '[data-icon-field]' ), picker );
	} );
} )();
