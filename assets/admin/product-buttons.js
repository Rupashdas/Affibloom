/**
 * The product page's buttons (F29, Task 14H; register U12; the user, 2026-11-13).
 *
 * One button by default, the product's own link. "Add Another Button" clones the page's <template> (giving its ids a
 * key of their own); any block, the default one included, is dragged by its grip to a new place, as Lasso drags its
 * rows (drag-rows.js; Arrow Up and Arrow Down on the grip from the keyboard; the user, 2026-11-16); Remove takes
 * an added block out, asking first (in the page's own dialog, Task 2X1) when its link is filled. After every change the blocks are renumbered: each group's
 * name "Button <n>", the added buttons' field names buttons[<i>][…] in their order, the hidden primary_position (where
 * the default button sits among them), and the grip's and Remove's names. Task 15 saves exactly that. A new block is
 * announced with "affibloom:enhance" so the icon picker and the help tips wire themselves up inside it.
 */
( function () {
	'use strict';

	var format = function ( template, value ) {
		return template.replace( '%s', value );
	};

	/**
	 * @param {HTMLElement} box The `[data-buttons]` wrapper.
	 */
	function buttons( box ) {
		var list = box.querySelector( '[data-button-list]' );
		var position = box.querySelector( '[data-main-position]' );
		var template = document.getElementById( 'affibloom-extra-button-template' );
		var add = box.querySelector( '[data-add-button]' );
		var created = 0;

		var blocks = function () {
			return Array.prototype.slice.call( list.querySelectorAll( '[data-button]' ) );
		};

		var renumber = function () {
			var all = blocks();
			var added = 0;
			all.forEach( function ( block, place ) {
				var name = format( box.dataset.legendFormat, String( place + 1 ) );
				var remove = block.querySelector( '[data-remove]' );
				block.querySelector( 'legend' ).textContent = name;
				if ( block.hasAttribute( 'data-main-button' ) ) {
					position.value = String( added );
				} else {
					Array.prototype.forEach.call( block.querySelectorAll( '[name^="buttons["]' ), function ( field ) {
						field.name = field.name.replace( /^buttons\[[^\]]*\]/, 'buttons[' + added + ']' );
					} );
					added += 1;
				}
				block.querySelector( '[data-grip]' ).setAttribute( 'aria-label', format( box.dataset.gripFormat, name ) );
				if ( remove ) {
					remove.setAttribute( 'aria-label', format( box.dataset.removeFormat, name ) );
				}
			} );
		};

		/** Takes an added block out; the focus goes to "Add Another Button", as the block it was on is gone. */
		var take = function ( block ) {
			block.remove();
			renumber();
			add.focus();
		};

		// "Remove this button?" (partials/delete-modal.php): the block waiting for the answer.
		var asking = document.getElementById( 'affibloom-button-remove-modal' );
		var pending = null;
		asking.querySelector( '[data-remove-confirm]' ).addEventListener( 'click', function () {
			var block = pending;
			pending = null;
			window.affibloomModal.hide();
			if ( block ) {
				take( block );
			}
		} );
		asking.addEventListener( 'affibloom:close', function () {
			pending = null;
		} );

		var wire = function ( block ) {
			block.querySelector( '.affibloom-button-block__actions' ).hidden = false;
			block.querySelector( '[data-grip]' ).hidden = false;
			block.addEventListener( 'click', function ( event ) {
				var remove = event.target.closest( '[data-remove]' );
				if ( remove ) {
					var link = block.querySelector( 'input[name$="[url]"]' );
					if ( link && '' !== link.value.trim() ) {
						// A filled button asks first, in the page's own dialog; its Remove calls take().
						pending = block;
						window.affibloomModal.show( asking, remove );
					} else {
						take( block );
					}
				}
			} );
		};

		add.hidden = false;
		add.addEventListener( 'click', function () {
			var holder = document.createElement( 'div' );
			created += 1;
			holder.innerHTML = template.innerHTML.replace( /__k__/g, 'new' + created ).trim();
			var block = holder.firstElementChild;
			list.appendChild( block );
			wire( block );
			renumber();
			block.dispatchEvent( new CustomEvent( 'affibloom:enhance', { bubbles: true } ) );
			block.querySelector( 'input[name$="[url]"]' ).focus();
		} );

		blocks().forEach( wire );
		renumber();

		// A block dropped in a new place renumbers the names and tells the page an edit was made.
		window.affibloomDragRows( list, {
			row: '[data-button]',
			movable: blocks,
			dropped: function () {
				renumber();
				list.dispatchEvent( new Event( 'input', { bubbles: true } ) );
			},
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var box = document.querySelector( '[data-buttons]' );
		if ( box ) {
			buttons( box );
		}
	} );
} )();
