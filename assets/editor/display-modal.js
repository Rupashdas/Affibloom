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
		if ( ! tile ) {
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
			// Focus stays in the topmost modal (this one, or a dialog stacked over it).
			const topPanel = top.modal.querySelector(
				'.affibloom-modal__panel'
			);
			if ( 'Tab' === event.key && topPanel ) {
				const items = focusable( topPanel );
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
					( active === last || ! topPanel.contains( active ) )
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
	 * A searchable list (Tasks 23B, 23D, 23E; R14, R20): five a page, a search on Enter only, grey rows while the answer is
	 * on its way, page buttons, and its section's empty and failure messages (`data-empty`, `data-failed`). Extracted on
	 * its second use; the steps and "Add to Group" use it.
	 *
	 * @param {Object}                               options         The list.
	 * @param {Element}                              options.section Holds the search, list, message, pages and row template.
	 * @param {(query: URLSearchParams) => string}   options.path    The route to ask, with the search and page.
	 * @param {(item: Object, row: Element) => void} options.fill    Fills a row from an item of the answer.
	 * @return {Object} load( page ), reload(), reset(), and the list element.
	 */
	const searchList = function ( options ) {
		const section = options.section;
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

		search.addEventListener( 'keydown', function ( event ) {
			if ( 'Enter' === event.key ) {
				event.preventDefault();
				load( 1 );
			}
		} );

		return {
			list,
			load,
			reload() {
				load( shown );
			},
			reset() {
				latest++;
				search.value = '';
				list.innerHTML = '';
				nav.innerHTML = '';
				say( '' );
			},
		};
	};

	/**
	 * A step of the display modal that lists search results; a row's Add inserts the chosen type with the row's target.
	 *
	 * @param {Object}                                 options        The step.
	 * @param {string}                                 options.name   The step (`data-step`).
	 * @param {(query: URLSearchParams) => string}     options.path   The route to ask.
	 * @param {(item: Object, row: Element) => void}   options.fill   Fills a row.
	 * @param {(button: HTMLElement) => Object | null} options.choose The display a row's Add inserts.
	 * @return {Object|null} The step's list.
	 */
	const searchStep = function ( options ) {
		const section = root.querySelector(
			'[data-step="' + options.name + '"]'
		);
		if ( ! section ) {
			return null;
		}
		const list = searchList( {
			section,
			path: options.path,
			fill: options.fill,
		} );

		// Add (R16, R18): the chosen type with this row's target; the modal closes and the block draws it.
		list.list.addEventListener( 'click', function ( event ) {
			const add = event.target.closest( '[data-row-add]' );
			const display = add ? options.choose( add ) : null;
			if ( display ) {
				window.affibloomDisplayModal.insert( {
					type: context.type,
					...display,
				} );
			}
		} );

		window.affibloomDisplayModal.step( options.name, {
			show() {
				list.reset();
				list.load( 1 );
			},
			reset: list.reset,
		} );

		return list;
	};

	/**
	 * A product row (picture, name, link) in "Choose a Link" and "Add to Group".
	 *
	 * @param {Object}  item The product from GET /products.
	 * @param {Element} row  The row to fill.
	 */
	const fillProduct = function ( item, row ) {
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
	};

	/** "Choose a Link" (Task 23B; R12-R16): products, newest changed first, with their picture and link. */
	searchStep( {
		name: 'links',
		path: ( query ) => '/affibloom/v1/products?' + query.toString(),
		fill( item, row ) {
			fillProduct( item, row );
			row.querySelector( '[data-row-add]' ).dataset.target = String(
				item.id
			);
		},
		choose: ( button ) => ( {
			productId: parseInt( button.dataset.target, 10 ),
		} ),
	} );

	/** "Choose a Table" (Task 23F; F4 R24, F25 R55): the tables, newest first; Add inserts `type=table` for one. */
	searchStep( {
		name: 'tables',
		path: ( query ) => {
			query.set( 'order', 'desc' );
			return '/affibloom/v1/tables?' + query.toString();
		},
		fill( item, row ) {
			const name = row.querySelector( '[data-row-name]' );
			name.textContent = item.title;
			name.title = item.title;
			row.querySelector( '[data-row-add]' ).dataset.target = String(
				item.id
			);
		},
		choose: ( button ) => ( {
			tableId: parseInt( button.dataset.target, 10 ),
		} ),
	} );

	/**
	 * "Choose a Group" (Task 23D; R17-R18): groups with "1 link" / "N links"; Add inserts the group as the chosen type.
	 * A group's name opens "Add to Group" for it (Task 23E), for those who may manage products.
	 */
	const groupsSection = root.querySelector( '[data-step="groups"]' );
	const groupsList = searchStep( {
		name: 'groups',
		path: ( query ) => '/affibloom/v1/groups?' + query.toString(),
		fill( item, row ) {
			const name = row.querySelector( '[data-row-name]' );
			name.textContent = item.name;
			name.title = item.name;
			name.dataset.target = String( item.id );
			const words =
				1 === item.count
					? groupsSection.getAttribute( 'data-one' )
					: groupsSection.getAttribute( 'data-many' );
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

	/**
	 * A dialog stacked over the display modal (Task 23E): Escape and × close it first (R11); the focus returns to what
	 * opened it.
	 *
	 * @param {HTMLElement} element  The dialog's root (`.affibloom-modal`).
	 * @param {() => void}  [onHide] Runs when it closes, however it closes.
	 * @return {Object} show( opener ) and hide().
	 */
	const stacked = function ( element, onHide ) {
		let opener = null;
		const dialog = {
			show( from ) {
				opener = from || null;
				element.hidden = false;
				modalStack.push( element, dialog.hide );
				const first = focusable(
					element.querySelector( '.affibloom-modal__panel' )
				)[ 0 ];
				if ( first ) {
					first.focus();
				}
			},
			hide() {
				if ( element.hidden ) {
					return;
				}
				element.hidden = true;
				modalStack.pop( element );
				if ( onHide ) {
					onHide();
				}
				if ( opener && opener.isConnected ) {
					opener.focus();
				}
			},
		};
		element.addEventListener( 'click', function ( event ) {
			if ( event.target.closest( '[data-modal-close]' ) ) {
				dialog.hide();
			}
		} );
		return dialog;
	};

	/**
	 * A button that waits for an answer: disabled with the design system's spinner (`.is-busy`).
	 *
	 * @param {HTMLButtonElement} button The button.
	 * @param {boolean}           on     Whether it waits.
	 */
	const busy = function ( button, on ) {
		button.disabled = on;
		button.classList.toggle( 'is-busy', on );
	};

	/**
	 * "Add to Group" (Task 23E; R20-R23): the group's search of products, Select to add one ("Added" for members and this
	 * session's adds), Add Group to insert the group as the chosen type, Done to close and redraw the group list.
	 *
	 * @param {HTMLElement} element    The dialog (`[data-add-to-group-modal]`).
	 * @param {Object|null} createLink "Add A New Link" (Task 23G), or null.
	 * @return {Object} open( id, name, opener ).
	 */
	const addToGroupDialog = function ( element, createLink ) {
		const dialog = stacked( element );
		const title = element.querySelector( '[data-group-name]' );
		const error = element.querySelector( '[data-add-error]' );
		let group = 0;
		const added = new Set();

		const markAdded = function ( button ) {
			button.disabled = true;
			button.classList.add( 'is-added' );
			button.querySelector( '[data-select-label]' ).textContent =
				element.getAttribute( 'data-added' );
		};

		const products = searchList( {
			section: element,
			path: ( query ) => {
				query.set( 'group', String( group ) );
				return '/affibloom/v1/products?' + query.toString();
			},
			fill( item, row ) {
				fillProduct( item, row );
				const select = row.querySelector( '[data-row-select]' );
				select.dataset.target = String( item.id );
				if ( item.in_group || added.has( item.id ) ) {
					markAdded( select );
				}
			},
		} );

		// Select (R21): adds the product to the group at once; a refusal says why.
		products.list.addEventListener( 'click', function ( event ) {
			const select = event.target.closest( '[data-row-select]' );
			if ( ! select || select.disabled ) {
				return;
			}
			const product = parseInt( select.dataset.target, 10 );
			error.hidden = true;
			busy( select, true );
			window.wp
				.apiFetch( {
					path: '/affibloom/v1/groups/' + group + '/products',
					method: 'POST',
					data: { product },
				} )
				.then( function () {
					busy( select, false );
					added.add( product );
					markAdded( select );
				} )
				.catch( function ( failure ) {
					busy( select, false );
					error.textContent =
						failure && failure.message
							? failure.message
							: element.getAttribute( 'data-failed' );
					error.hidden = false;
				} );
		} );

		// Add Group (R22): the group as the step's type.
		element
			.querySelector( '[data-add-group]' )
			.addEventListener( 'click', function () {
				dialog.hide();
				window.affibloomDisplayModal.insert( {
					type: context.type,
					groupId: group,
				} );
			} );

		// Create a Link (R29, Task 23G): the new product joins this group, and the list shows it Added.
		const createButton = element.querySelector( '[data-create-link]' );
		if ( createLink && createButton ) {
			createButton.addEventListener( 'click', function () {
				createLink.open( createButton, function ( product ) {
					window.wp
						.apiFetch( {
							path: '/affibloom/v1/groups/' + group + '/products',
							method: 'POST',
							data: { product },
						} )
						.then( function () {
							added.add( product );
							products.reload();
						} )
						.catch( function () {
							error.textContent =
								element.getAttribute( 'data-group-failed' );
							error.hidden = false;
						} );
				} );
			} );
		}

		// Done (R23): closes; the group list shows the new counts.
		element
			.querySelector( '[data-add-done]' )
			.addEventListener( 'click', function () {
				dialog.hide();
				if ( groupsList ) {
					groupsList.reload();
				}
			} );

		return {
			open( id, name, from ) {
				group = id;
				added.clear();
				title.textContent = name;
				error.hidden = true;
				products.reset();
				dialog.show( from );
				products.load( 1 );
			},
		};
	};

	/**
	 * "Add a New Group" (Task 23E; R19): Name and Description; Save Changes creates it (POST /groups) and opens "Add to
	 * Group" for it. An empty name is refused here, a name in use by the server (F23 D3).
	 *
	 * @param {HTMLElement} element    The dialog (`[data-create-group-modal]`).
	 * @param {Object}      addToGroup The "Add to Group" dialog.
	 * @param {HTMLElement} opener     The Create Group button.
	 */
	const createGroupDialog = function ( element, addToGroup, opener ) {
		const dialog = stacked( element );
		const form = element.querySelector( 'form' );
		const name = form.elements.namedItem( 'name' );
		const description = form.elements.namedItem( 'description' );
		const nameError = element.querySelector( '[data-name-error]' );
		const formError = element.querySelector( '[data-form-error]' );
		const save = element.querySelector( '[data-create-save]' );

		const refuse = function ( field, text ) {
			field.textContent = text;
			field.hidden = '' === text;
			if ( field === nameError ) {
				name.setAttribute(
					'aria-invalid',
					'' === text ? 'false' : 'true'
				);
			}
		};

		opener.addEventListener( 'click', function () {
			form.reset();
			refuse( nameError, '' );
			refuse( formError, '' );
			dialog.show( opener );
		} );

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			refuse( nameError, '' );
			refuse( formError, '' );
			if ( '' === name.value.trim() ) {
				refuse( nameError, element.getAttribute( 'data-required' ) );
				name.focus();
				return;
			}
			busy( save, true );
			window.wp
				.apiFetch( {
					path: '/affibloom/v1/groups',
					method: 'POST',
					data: { name: name.value, description: description.value },
				} )
				.then( function ( answer ) {
					busy( save, false );
					dialog.hide();
					addToGroup.open(
						answer.data.id,
						name.value.trim(),
						opener
					);
				} )
				.catch( function ( failure ) {
					busy( save, false );
					const said =
						failure &&
						failure.data &&
						failure.data.fields &&
						failure.data.fields.name;
					if ( said ) {
						refuse( nameError, said );
						name.focus();
						return;
					}
					refuse( formError, element.getAttribute( 'data-failed' ) );
				} );
		} );
	};

	/**
	 * The quick detail (Task 23G; R26-R30): the new product's page, framed to its form and live preview, in the dialog.
	 * + Add saves that form (the product page's own save, PATCH /products/{id}) and hands the product on; a refused field
	 * shows on the page. Cancel, ×, Escape (inside the frame too) close it; the frame goes with it, so a half-edited form
	 * never asks to be saved later.
	 *
	 * @param {HTMLElement} element The dialog (`[data-quick-detail-modal]`).
	 * @return {Object} open( product, opener, done ).
	 */
	const quickDetailDialog = function ( element ) {
		const holder = element.querySelector( '[data-quick-frame]' );
		const add = element.querySelector( '[data-quick-add]' );
		let frame = null;
		let done = null;
		const dialog = stacked( element, function () {
			busy( add, false );
			if ( frame ) {
				frame.remove();
				frame = null;
			}
		} );

		const productForm = function () {
			const page = frame && frame.contentDocument;
			return page
				? page.getElementById( 'affibloom-product-form' )
				: null;
		};

		element
			.querySelector( '[data-quick-cancel]' )
			.addEventListener( 'click', dialog.hide );

		add.addEventListener( 'click', function () {
			const product = productForm();
			const save =
				product &&
				product.ownerDocument.getElementById( 'affibloom-save' );
			if ( ! product || ! save ) {
				return;
			}
			busy( add, true );
			product.requestSubmit( save );
		} );

		return {
			open( product, from, then ) {
				done = then;
				holder.innerHTML = '';
				frame = document.createElement( 'iframe' );
				frame.className = 'affibloom-quick-detail__page';
				frame.title = element.getAttribute( 'data-frame-title' );
				const address = new URL(
					element.getAttribute( 'data-product-page' ),
					window.location.href
				);
				address.searchParams.set( 'id', String( product ) );
				frame.src = address.toString();
				frame.addEventListener( 'load', function () {
					const page = frame && frame.contentDocument;
					if ( ! page ) {
						return;
					}
					// Escape inside the frame closes the dialog, unless the page has a dialog of its own open.
					page.addEventListener( 'keydown', function ( event ) {
						if (
							'Escape' === event.key &&
							! page.querySelector(
								'.affibloom-modal:not([hidden])'
							)
						) {
							event.preventDefault();
							dialog.hide();
						}
					} );
					const saved = productForm();
					if ( ! saved ) {
						return;
					}
					saved.addEventListener(
						'affibloom:product-saved',
						function ( event ) {
							const next = done;
							dialog.hide();
							if ( next ) {
								next( event.detail.id );
							}
						}
					);
					saved.addEventListener(
						'affibloom:product-save-failed',
						function () {
							busy( add, false );
						}
					);
				} );
				holder.appendChild( frame );
				dialog.show( from );
			},
		};
	};

	/**
	 * "Add A New Link", the editor's variant (Task 23G; R25, F18 D6): a URL; Add Link creates the product (POST
	 * /products, F18) and opens its quick detail. A refused link says why under the field.
	 *
	 * @param {HTMLElement} element     The dialog (`[data-create-link-modal]`).
	 * @param {Object}      quickDetail The quick detail.
	 * @return {Object} open( opener, done ): done( product ) runs once the quick detail's + Add has saved it.
	 */
	const createLinkDialog = function ( element, quickDetail ) {
		const dialog = stacked( element );
		const form = element.querySelector( 'form' );
		const url = form.elements.namedItem( 'url' );
		const error = element.querySelector( '[data-url-error]' );
		const save = element.querySelector( '[data-create-link-save]' );
		let opener = null;
		let done = null;

		const refuse = function ( text ) {
			error.textContent = text;
			error.hidden = '' === text;
			url.setAttribute( 'aria-invalid', '' === text ? 'false' : 'true' );
		};

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			refuse( '' );
			busy( save, true );
			window.wp
				.apiFetch( {
					path: '/affibloom/v1/products',
					method: 'POST',
					data: { url: url.value.trim() },
				} )
				.then( function ( answer ) {
					busy( save, false );
					dialog.hide();
					quickDetail.open( answer.data.id, opener, done );
				} )
				.catch( function ( failure ) {
					busy( save, false );
					const said =
						failure &&
						failure.data &&
						failure.data.fields &&
						failure.data.fields.url;
					refuse(
						said ||
							( failure && failure.message ) ||
							element.getAttribute( 'data-failed' )
					);
					url.focus();
				} );
		} );

		return {
			open( from, then ) {
				opener = from;
				done = then;
				form.reset();
				refuse( '' );
				dialog.show( from );
			},
		};
	};

	// The Create dialogs are printed only for those who may manage products (F4 D8, edge case 7).
	const quickDetailElement = document.querySelector(
		'[data-quick-detail-modal]'
	);
	const createLinkElement = document.querySelector(
		'[data-create-link-modal]'
	);
	const createLink =
		quickDetailElement && createLinkElement
			? createLinkDialog(
					createLinkElement,
					quickDetailDialog( quickDetailElement )
				)
			: null;

	// "Choose a Link"'s Create a Link (R25): the new product as the chosen Single, Button or Image display (R28).
	const linksCreate = root.querySelector(
		'[data-step="links"] [data-create-link]'
	);
	if ( createLink && linksCreate ) {
		linksCreate.addEventListener( 'click', function () {
			createLink.open( linksCreate, function ( product ) {
				window.affibloomDisplayModal.insert( {
					type: context.type,
					productId: product,
				} );
			} );
		} );
	}

	const addToGroupElement = document.querySelector(
		'[data-add-to-group-modal]'
	);
	if ( groupsSection && addToGroupElement ) {
		const addToGroup = addToGroupDialog( addToGroupElement, createLink );
		groupsSection.addEventListener( 'click', function ( event ) {
			const name = event.target.closest( '[data-row-open]' );
			if ( name ) {
				addToGroup.open(
					parseInt( name.dataset.target, 10 ),
					name.textContent,
					name
				);
			}
		} );
		const createElement = document.querySelector(
			'[data-create-group-modal]'
		);
		const create = groupsSection.querySelector( '[data-create-group]' );
		if ( createElement && create ) {
			createGroupDialog( createElement, addToGroup, create );
		}
	}
} )();
