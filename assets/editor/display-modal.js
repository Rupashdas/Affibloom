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

	/**
	 * A link's address as a link may carry it: http(s) only; anything else becomes "#" (R15).
	 *
	 * @param {string} address The address from the answer.
	 * @return {string} A safe href.
	 */
	const safeHref = function ( address ) {
		return /^https?:\/\//i.test( address || '' ) ? address : '#';
	};

	/**
	 * Five grey row shapes while a list waits for its answer (the design system's skeleton, deviation 145).
	 *
	 * @param {HTMLElement} list The list to fill.
	 */
	const skeleton = function ( list ) {
		list.innerHTML = '';
		for ( let i = 0; i < 5; i++ ) {
			const row = document.createElement( 'li' );
			row.className = 'affibloom-skeleton-row';
			row.setAttribute( 'aria-hidden', 'true' );
			row.innerHTML =
				'<span class="affibloom-skeleton affibloom-skeleton--thumb"></span>' +
				'<span class="affibloom-skeleton-row__lines"><span class="affibloom-skeleton affibloom-skeleton--line"></span>' +
				'<span class="affibloom-skeleton affibloom-skeleton--line affibloom-skeleton--short"></span></span>' +
				'<span class="affibloom-skeleton affibloom-skeleton--button"></span>';
			list.appendChild( row );
		}
	};

	/**
	 * Page buttons under a list: previous, the pages (the current one marked), next (R14).
	 *
	 * @param {HTMLElement}            nav     The list's page navigation.
	 * @param {number}                 current The page shown.
	 * @param {number}                 pages   How many pages there are.
	 * @param {string}                 label   "Page %d".
	 * @param {(page: number) => void} go      Loads a page.
	 */
	const pageLinks = function ( nav, current, pages, label, go ) {
		nav.innerHTML = '';
		nav.hidden = pages < 2;
		if ( pages < 2 ) {
			return;
		}
		const add = function ( text, page, name, isCurrent ) {
			const button = document.createElement( 'button' );
			button.type = 'button';
			button.className = 'page-numbers' + ( isCurrent ? ' current' : '' );
			button.textContent = text;
			button.setAttribute( 'aria-label', name );
			if ( isCurrent ) {
				button.setAttribute( 'aria-current', 'page' );
			}
			button.addEventListener( 'click', function () {
				go( page );
			} );
			nav.appendChild( button );
		};
		if ( current > 1 ) {
			add( '‹', current - 1, nav.getAttribute( 'data-previous' ), false );
		}
		for ( let page = 1; page <= pages; page++ ) {
			// The first, the last and the pages next to the current one; a gap stands for the rest.
			if (
				page === 1 ||
				page === pages ||
				Math.abs( page - current ) <= 1
			) {
				add(
					String( page ),
					page,
					label.replace( '%d', page ),
					page === current
				);
			} else if ( Math.abs( page - current ) === 2 ) {
				const gap = document.createElement( 'span' );
				gap.className = 'page-numbers dots';
				gap.textContent = '…';
				nav.appendChild( gap );
			}
		}
		if ( current < pages ) {
			add( '›', current + 1, nav.getAttribute( 'data-next' ), false );
		}
	};

	/**
	 * A step that lists search results (Tasks 23B, 23D; R14): five a page, a search on Enter only, grey rows while the
	 * answer is on its way, page buttons, and the step's empty and failure messages. Extracted on its second use.
	 *
	 * @param {Object}                                 options        The step.
	 * @param {string}                                 options.name   The step (`data-step`).
	 * @param {(query: URLSearchParams) => string}     options.path   The route to ask, with the search and page.
	 * @param {(item: Object, row: Element) => void}   options.fill   Fills a row from an item of the answer.
	 * @param {(button: HTMLElement) => Object | null} options.choose The display a row's Add inserts.
	 * @return {Object|null} The step's own search reload, for steps that redraw it (23E).
	 */
	const searchStep = function ( options ) {
		const section = root.querySelector(
			'[data-step="' + options.name + '"]'
		);
		if ( ! section ) {
			return null;
		}
		const search = section.querySelector( '[data-step-search]' );
		const list = section.querySelector( '[data-step-list]' );
		const message = section.querySelector( '[data-step-message]' );
		const nav = section.querySelector( '[data-step-pages]' );
		const template = section.querySelector( '[data-step-row]' );
		let latest = 0;
		let shown = 1;

		const say = function ( text ) {
			message.textContent = text;
			message.hidden = '' === text;
		};

		const load = function ( page ) {
			const ask = ++latest;
			shown = page;
			skeleton( list );
			say( '' );
			const query = new URLSearchParams( {
				search: search.value.trim(),
				page: String( page ),
				per_page: '5',
			} );
			window.wp
				.apiFetch( { path: options.path( query ) } )
				.then( function ( answer ) {
					if ( ask !== latest ) {
						return;
					}
					const data = answer.data;
					list.innerHTML = '';
					data.items.forEach( function ( item ) {
						const row =
							template.content.firstElementChild.cloneNode(
								true
							);
						options.fill( item, row );
						list.appendChild( row );
					} );
					say(
						data.items.length
							? ''
							: section.getAttribute( 'data-empty' )
					);
					pageLinks(
						nav,
						data.page,
						data.pages,
						section.getAttribute( 'data-page-label' ),
						load
					);
				} )
				.catch( function () {
					if ( ask !== latest ) {
						return;
					}
					list.innerHTML = '';
					nav.hidden = true;
					say( section.getAttribute( 'data-failed' ) );
				} );
		};

		// Add (R16, R18): the chosen type with this row's target; the modal closes and the block draws it.
		list.addEventListener( 'click', function ( event ) {
			const add = event.target.closest( '[data-row-add]' );
			const display = add ? options.choose( add ) : null;
			if ( display ) {
				window.affibloomDisplayModal.insert( {
					type: context.type,
					...display,
				} );
			}
		} );

		search.addEventListener( 'keydown', function ( event ) {
			if ( 'Enter' === event.key ) {
				event.preventDefault();
				load( 1 );
			}
		} );

		window.affibloomDisplayModal.step( options.name, {
			show() {
				search.value = '';
				load( 1 );
			},
			reset() {
				latest++;
				search.value = '';
				list.innerHTML = '';
				nav.innerHTML = '';
				say( '' );
			},
		} );

		return {
			section,
			list,
			reload() {
				load( shown );
			},
		};
	};

	/** "Choose a Link" (Task 23B; R12-R16): products, newest changed first, with their picture and link. */
	searchStep( {
		name: 'links',
		path: ( query ) => '/affibloom/v1/products?' + query.toString(),
		fill( item, row ) {
			const image = row.querySelector( '[data-row-image]' );
			if ( item.image_url ) {
				image.src = item.image_url;
				image.hidden = false;
				row.querySelector( '[data-row-none]' ).hidden = true;
			}
			const name = row.querySelector( '[data-row-name]' );
			name.textContent = item.name;
			name.title = item.name;
			const link = row.querySelector( '[data-row-url]' );
			link.textContent = item.url;
			link.href = safeHref( item.url );
			row.querySelector( '[data-row-add]' ).dataset.target = String(
				item.id
			);
		},
		choose: ( button ) => ( {
			productId: parseInt( button.dataset.target, 10 ),
		} ),
	} );

	/** "Choose a Group" (Task 23D; R17-R18): groups with "1 link" / "N links"; Add inserts the group as the chosen type. */
	searchStep( {
		name: 'groups',
		path: ( query ) => '/affibloom/v1/groups?' + query.toString(),
		fill( item, row ) {
			const section = root.querySelector( '[data-step="groups"]' );
			const name = row.querySelector( '[data-row-name]' );
			name.textContent = item.name;
			name.title = item.name;
			const words =
				1 === item.count
					? section.getAttribute( 'data-one' )
					: section.getAttribute( 'data-many' );
			row.querySelector( '[data-row-count]' ).textContent = words.replace(
				'%s',
				item.count.toLocaleString()
			);
			row.querySelector( '[data-row-add]' ).dataset.target = String(
				item.id
			);
		},
		choose: ( button ) => ( {
			groupId: parseInt( button.dataset.target, 10 ),
		} ),
	} );
} )();
