/**
 * The display modal (F4 D1, Task 23A): one modal for the block, the Classic editor's button and the Elementor widget.
 * Its markup is printed by EditorAssets (templates/editor/display-modal.php); the block loads this script on first use
 * (blocks/product-box/modal-loader.js).
 *
 * - open( { opener, insert } ): shows "Choose a Display Type" (R9). `insert( attributes )` receives the display a
 *   step adds; `opener` gets the focus back on close.
 * - Close: ×, Escape (the topmost modal first, R11) or a click on the backdrop; closing resets the modal to the chooser
 *   and every step (R10).
 * - A tile opens its step (`data-opens`); steps register with step( name, { show, reset } ) (Tasks 23B-23F).
 * - window.affibloomModalStack: other modals (Add to Group, the quick detail) push and pop themselves, so Escape closes
 *   the topmost.
 * Focus stays inside the open modal (Tab and Shift+Tab cycle).
 */
( function () {
	'use strict';

	const root = document.querySelector( '[data-display-modal]' );
	if ( ! root ) {
		return;
	}
	const panel = root.querySelector( '.affibloom-modal__panel' );
	let stack = [];
	const steps = {};
	let context = null;

	const focusable = function ( within ) {
		return Array.prototype.filter.call(
			within.querySelectorAll(
				'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
			),
			function ( element ) {
				return (
					! element.disabled &&
					! element.closest( '[hidden]' ) &&
					element.offsetParent !== null
				);
			}
		);
	};

	/** The modals open now, topmost last; Escape closes the topmost (R11). */
	const modalStack = {
		push( modal, closer ) {
			stack.push( { modal, close: closer } );
		},
		pop( modal ) {
			stack = stack.filter( function ( entry ) {
				return entry.modal !== modal;
			} );
		},
		top() {
			return stack.length ? stack[ stack.length - 1 ] : null;
		},
	};

	/**
	 * Shows one step; the dialog takes that step's heading as its name.
	 *
	 * @param {string} name The step (`data-step`).
	 * @param {Object} data What the step is told, e.g. the chosen type.
	 */
	function showStep( name, data ) {
		Array.prototype.forEach.call(
			root.querySelectorAll( '[data-step]' ),
			function ( section ) {
				section.hidden = section.getAttribute( 'data-step' ) !== name;
			}
		);
		const section = root.querySelector( '[data-step="' + name + '"]' );
		panel.setAttribute(
			'aria-labelledby',
			section.getAttribute( 'aria-labelledby' )
		);
		if ( steps[ name ] && steps[ name ].show ) {
			steps[ name ].show( data || {}, context );
		}
		const first = focusable( section )[ 0 ];
		if ( first ) {
			first.focus();
		}
	}

	function close() {
		if ( root.hidden ) {
			return;
		}
		root.hidden = true;
		modalStack.pop( root );
		Object.keys( steps ).forEach( function ( name ) {
			if ( steps[ name ].reset ) {
				steps[ name ].reset();
			}
		} );
		showStep( 'type' );
		const opener = context && context.opener;
		context = null;
		if ( opener && opener.isConnected ) {
			opener.focus();
		}
	}

	function open( options ) {
		context = options || {};
		root.hidden = false;
		modalStack.push( root, close );
		showStep( 'type' );
	}

	root.addEventListener( 'click', function ( event ) {
		if ( event.target.closest( '[data-modal-close]' ) ) {
			close();
			return;
		}
		const tile = event.target.closest( '.affibloom-type-tile' );
		if ( ! tile || 'true' === tile.getAttribute( 'aria-disabled' ) ) {
			return;
		}
		context.type = tile.getAttribute( 'data-type' );
		showStep( tile.getAttribute( 'data-opens' ), { type: context.type } );
	} );

	document.addEventListener(
		'keydown',
		function ( event ) {
			const top = modalStack.top();
			if ( ! top ) {
				return;
			}
			if ( 'Escape' === event.key ) {
				event.preventDefault();
				event.stopPropagation();
				top.close();
				return;
			}
			if ( 'Tab' === event.key && top.modal === root ) {
				const items = focusable( panel );
				if ( ! items.length ) {
					return;
				}
				const first = items[ 0 ];
				const last = items[ items.length - 1 ];
				const active = root.ownerDocument.activeElement;
				if ( event.shiftKey && active === first ) {
					event.preventDefault();
					last.focus();
				} else if (
					! event.shiftKey &&
					( active === last || ! panel.contains( active ) )
				) {
					event.preventDefault();
					first.focus();
				}
			}
		},
		true
	);

	window.affibloomModalStack = modalStack;
	window.affibloomDisplayModal = {
		open,
		close,
		show: showStep,
		/**
		 * A step's Add: hands the display to the block (or the Classic editor, the widget) and closes.
		 *
		 * @param {Object} attributes The display's block attributes.
		 */
		insert( attributes ) {
			const target = context;
			close();
			if ( target && target.insert ) {
				target.insert( attributes );
			}
		},
		step( name, handlers ) {
			steps[ name ] = handlers;
		},
	};
} )();
