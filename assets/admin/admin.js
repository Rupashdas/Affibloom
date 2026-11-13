/* global wp */
/**
 * Affibloom admin: what every screen shares — help tips that float clear of scrolling areas, Save Changes kept
 * disabled until something changes (a form opts in with data-save-when-changed), modals, and "Add A New Link" in
 * the header. The first two were extracted from the Display screen's script when the Amazon tab became their
 * second user.
 */
( function () {
	'use strict';

	/**
	 * Help tips that float against the viewport, as Lasso's rail does it: above the "?" when there is room, below
	 * it when there is not, kept inside the window. Fixed positioning lets a tip escape the rail's scrolling area
	 * and the card's rounded clip. The tip stays while the pointer is over it, and Escape closes it (WCAG 1.4.13).
	 *
	 * @param {HTMLElement} tip The `.affibloom-help` wrapper.
	 */
	function floatingTip( tip ) {
		var button = tip.querySelector( '.affibloom-help__button' );
		var text = tip.querySelector( '.affibloom-help__text' );
		var GAP = 8;
		var timer = null;
		if ( ! button || ! text ) {
			return;
		}

		var open = function () {
			window.clearTimeout( timer );
			tip.classList.add( 'is-open' );

			var anchor = button.getBoundingClientRect();
			var width = text.offsetWidth;
			var height = text.offsetHeight;
			var top = anchor.top - height - GAP;
			if ( top < GAP ) {
				top = anchor.bottom + GAP;
			}
			var left = Math.min( Math.max( GAP, anchor.left - GAP ), document.documentElement.clientWidth - width - GAP );

			text.style.top = Math.round( top ) + 'px';
			text.style.left = Math.round( left ) + 'px';
		};
		var close = function () {
			tip.classList.remove( 'is-open' );
		};
		var closeSoon = function () {
			// A short pause lets the pointer cross the gap from the "?" to the tip without it closing.
			timer = window.setTimeout( close, 120 );
		};

		tip.classList.add( 'is-floating' );
		button.addEventListener( 'mouseenter', open );
		button.addEventListener( 'focus', open );
		button.addEventListener( 'blur', close );
		tip.addEventListener( 'mouseleave', closeSoon );
		text.addEventListener( 'mouseenter', function () {
			window.clearTimeout( timer );
		} );
		button.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key ) {
				close();
			}
		} );
		// A floating tip would stay where it was while the page moves under it.
		window.addEventListener( 'scroll', close, true );
		window.addEventListener( 'resize', close );
	}

	/**
	 * Lasso keeps Save Changes disabled until something on the screen changes. Steppers, chips and the phone
	 * reset are clicks rather than input events, so they announce themselves with an `affibloom:change` event.
	 *
	 * @param {HTMLFormElement} form The Display form.
	 */
	function saveWhenChanged( form ) {
		var save = form.querySelector( '#affibloom-settings-save' ) || document.getElementById( 'affibloom-settings-save' );
		var enable = function () {
			save.disabled = false;
		};
		if ( ! save ) {
			return;
		}

		save.disabled = true;
		form.addEventListener( 'input', enable );
		form.addEventListener( 'change', enable );
		form.addEventListener( 'affibloom:change', enable );
	}


	/**
	 * Modals (design system, "Modal"): `[data-affibloom-open="<id>"]` opens one, `[data-affibloom-close]`, Escape or
	 * the backdrop closes it. While it is open, Tab stays inside it; on closing, the focus goes back to the opener.
	 * The modal hears `affibloom:open` (with the opener's `data-affibloom-mode`, if any) and `affibloom:close`, so it
	 * can set itself up and reset itself.
	 */
	var FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]):not([type=hidden]), textarea:not([disabled]), select:not([disabled])';
	var openModal = null;
	var opener = null;

	function showModal( modal, trigger ) {
		var first = modal.querySelector( '.affibloom-modal__body input, .affibloom-modal__body textarea' ) || modal.querySelector( FOCUSABLE );
		// A menu item closes its menu, so the focus goes back to the button that opened the menu.
		opener = trigger.closest( '.affibloom-menu' ) ? document.querySelector( '[aria-controls="' + trigger.closest( '.affibloom-menu' ).id + '"]' ) : trigger;
		openModal = modal;
		modal.hidden = false;
		document.body.classList.add( 'affibloom-has-modal' );
		if ( first ) {
			first.focus();
		}
		modal.dispatchEvent( new CustomEvent( 'affibloom:open', { detail: { mode: trigger.getAttribute( 'data-affibloom-mode' ) || 'single' } } ) );
	}

	function hideModal() {
		var modal = openModal;
		if ( ! modal ) {
			return;
		}
		modal.hidden = true;
		openModal = null;
		document.body.classList.remove( 'affibloom-has-modal' );
		modal.dispatchEvent( new CustomEvent( 'affibloom:close' ) );
		if ( opener ) {
			opener.focus();
		}
	}

	// For a page script that opens a modal itself rather than from a [data-affibloom-open] button: the product page's
	// "Unsaved Changes" (Task 15D), opened by the link that was clicked.
	window.affibloomModal = { show: showModal, hide: hideModal };

	function trapTab( event ) {
		var items = Array.prototype.filter.call( openModal.querySelectorAll( FOCUSABLE ), function ( item ) {
			return null !== item.offsetParent;
		} );
		var first = items[ 0 ];
		var last = items[ items.length - 1 ];
		if ( ! first ) {
			return;
		}
		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	}

	/**
	 * Menus (design system, "Menu"): a button with `aria-controls` and `aria-expanded` shows the menu card under it;
	 * choosing an item, Escape or a click elsewhere closes it.
	 */
	var openMenu = null;

	function setMenu( toggle, open ) {
		var menu = document.getElementById( toggle.getAttribute( 'aria-controls' ) );
		menu.hidden = ! open;
		toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		openMenu = open ? toggle : null;
	}

	document.addEventListener( 'click', function ( event ) {
		var toggle = event.target.closest( '[aria-controls][aria-expanded]' );
		var controlled = toggle ? document.getElementById( toggle.getAttribute( 'aria-controls' ) ) : null;
		var trigger = event.target.closest( '[data-affibloom-open]' );
		var modal = trigger ? document.getElementById( trigger.getAttribute( 'data-affibloom-open' ) ) : null;

		if ( openMenu && openMenu !== toggle ) {
			setMenu( openMenu, false );
		}
		if ( controlled && controlled.classList.contains( 'affibloom-menu' ) ) {
			setMenu( toggle, 'false' === toggle.getAttribute( 'aria-expanded' ) );
		} else if ( modal ) {
			event.preventDefault();
			showModal( modal, trigger );
		} else if ( openModal && event.target.closest( '[data-affibloom-close]' ) ) {
			hideModal();
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( openMenu && 'Escape' === event.key ) {
			var toggle = openMenu;
			setMenu( toggle, false );
			toggle.focus();
			return;
		}
		if ( ! openModal ) {
			return;
		}
		if ( 'Escape' === event.key ) {
			event.preventDefault();
			hideModal();
		} else if ( 'Tab' === event.key ) {
			trapTab( event );
		}
	} );

	/**
	 * "Add A New Link" (F18), Lasso's two modes in one dialog.
	 *
	 * Single (Task 11A): Enter or Add Link submits; an empty box is refused without a request ("URL is incorrect.");
	 * while the request runs the field and button are disabled; typing clears the message; on success the browser goes
	 * where the server says.
	 *
	 * Bulk (Task 11C): Shift + Enter in the field, or the header menu's "Bulk add links", swaps the field for a
	 * textarea with the caret on a new line; there Enter and Shift + Enter both start a new line, and closing the
	 * dialog goes back to one link. The counter shows how many of the ten remain; on
	 * leaving the textarea its links show as a numbered list with a pencil to edit them. Add Link asks first, starts a
	 * background run and follows it every 3 seconds until it is done, then goes where the server says. A run already
	 * going locks the textarea and offers Reload, as in Lasso.
	 *
	 * @param {HTMLFormElement} form The `.affibloom-add-form`.
	 */
	function addLink( form ) {
		var modal = form.closest( '.affibloom-modal' );
		var field = form.querySelector( '#affibloom-add-url' );
		var lines = form.querySelector( '#affibloom-add-urls' );
		var preview = form.querySelector( '.affibloom-add-form__preview' );
		var previewList = form.querySelector( '.affibloom-add-form__preview-list' );
		var edit = form.querySelector( '.affibloom-add-form__edit' );
		var count = form.querySelector( '#affibloom-add-count' );
		var progress = form.querySelector( '.affibloom-progress' );
		var bar = form.querySelector( '.affibloom-progress__bar' );
		var hint = form.querySelector( '#affibloom-add-hint' );
		var error = form.querySelector( '#affibloom-add-error' );
		var title = modal.querySelector( '#affibloom-add-title' );
		var intro = modal.querySelector( '#affibloom-add-intro' );
		var submit = form.querySelector( '#affibloom-add-submit' );
		var reload = form.querySelector( '.affibloom-add-form__reload' );
		var label = submit.querySelector( '.affibloom-add-form__label' );
		var idleLabel = label.textContent;
		var idlePlaceholder = lines.getAttribute( 'placeholder' );
		var limit = parseInt( form.getAttribute( 'data-limit' ), 10 );
		var text = function ( name ) {
			return form.getAttribute( 'data-' + name );
		};
		var bulk = false;
		var polling = null;

		var control = function () {
			return bulk ? lines : field;
		};
		var nonBlank = function () {
			return lines.value.split( /\r?\n/ ).map( function ( line ) {
				return line.trim();
			} ).filter( Boolean );
		};

		var showError = function ( message ) {
			error.textContent = message;
			error.hidden = false;
			control().classList.add( 'is-invalid' );
			control().setAttribute( 'aria-invalid', 'true' );
		};
		var clearError = function () {
			error.textContent = '';
			error.hidden = true;
			[ field, lines ].forEach( function ( input ) {
				input.classList.remove( 'is-invalid' );
				input.removeAttribute( 'aria-invalid' );
			} );
		};
		var busy = function ( isBusy ) {
			control().disabled = isBusy;
			submit.disabled = isBusy;
			label.textContent = isBusy ? text( 'label-busy' ) : idleLabel;
		};

		var updateCount = function () {
			count.textContent = text( 'label-remaining' ).replace( '%d', String( Math.max( 0, limit - nonBlank().length ) ) );
		};
		var showLines = function () {
			preview.hidden = true;
			lines.hidden = false;
			count.hidden = false;
		};
		var showPreview = function () {
			previewList.replaceChildren();
			nonBlank().slice( 0, limit ).forEach( function ( link ) {
				var item = document.createElement( 'li' );
				item.textContent = link;
				previewList.append( item );
			} );
			lines.hidden = true;
			count.hidden = true;
			preview.hidden = false;
		};
		var setProgress = function ( percent ) {
			// Lasso starts the bar at 3% and never shows less than 5% while it runs.
			bar.style.width = percent + '%';
			progress.setAttribute( 'aria-valuenow', String( percent ) );
		};
		var stopPolling = function () {
			window.clearInterval( polling );
			polling = null;
		};

		var lockRunning = function () {
			stopPolling();
			showLines();
			busy( false );
			lines.disabled = true;
			lines.rows = 2;
			lines.value = '';
			lines.setAttribute( 'placeholder', text( 'message-running' ) );
			count.hidden = true;
			progress.hidden = true;
			submit.hidden = true;
			reload.hidden = false;
		};
		var unlock = function () {
			lines.disabled = false;
			lines.rows = 7;
			lines.setAttribute( 'placeholder', idlePlaceholder );
			submit.hidden = false;
			reload.hidden = true;
		};

		var enterBulk = function () {
			if ( bulk ) {
				return;
			}
			bulk = true;
			clearError();
			lines.value = '' !== lines.value.trim() ? lines.value : field.value;
			field.hidden = true;
			hint.hidden = true;
			title.textContent = text( 'title-bulk' );
			intro.textContent = text( 'intro-bulk' );
			showLines();
			updateCount();
			lines.focus();
			wp.apiFetch( { path: '/affibloom/v1/products/bulk' } ).then( function ( response ) {
				if ( bulk && response.data.running ) {
					lockRunning();
				}
			} ).catch( function () {} );
		};
		var leaveBulk = function () {
			if ( ! bulk ) {
				return;
			}
			bulk = false;
			stopPolling();
			clearError();
			unlock();
			field.value = nonBlank()[ 0 ] || '';
			lines.hidden = true;
			preview.hidden = true;
			count.hidden = true;
			progress.hidden = true;
			field.hidden = false;
			hint.hidden = false;
			title.textContent = text( 'title-single' );
			intro.textContent = text( 'intro-single' );
		};

		var follow = function () {
			polling = window.setInterval( function () {
				wp.apiFetch( { path: '/affibloom/v1/products/bulk' } ).then( function ( response ) {
					setProgress( Math.max( 5, response.data.percent ) );
					if ( ! response.data.running ) {
						stopPolling();
						window.location.assign( response.data.redirect_to );
					}
				} ).catch( function () {} );
			}, 3000 );
		};

		var addOne = function () {
			if ( '' === field.value.trim() ) {
				showError( text( 'message-empty' ) );
				field.focus();
				return;
			}
			busy( true );
			wp.apiFetch( { path: '/affibloom/v1/products', method: 'POST', data: { url: field.value } } )
				.then( function ( response ) {
					window.location.assign( response.data.redirect_to );
				} )
				.catch( function ( failure ) {
					var fields = failure && failure.data && failure.data.fields;
					busy( false );
					showError( ( fields && fields.url ) || ( failure && failure.message ) || text( 'message-failed' ) );
					field.focus();
				} );
		};

		var addMany = function () {
			var urls = nonBlank().slice( 0, limit );
			if ( ! urls.length ) {
				showLines();
				showError( text( 'message-empty' ) );
				lines.focus();
				return;
			}
			if ( ! window.confirm( text( 'confirm-bulk' ) ) ) {
				return;
			}
			showPreview();
			busy( true );
			edit.hidden = true;
			progress.hidden = false;
			setProgress( 3 );
			wp.apiFetch( { path: '/affibloom/v1/products/bulk', method: 'POST', data: { urls: urls } } )
				.then( follow )
				.catch( function ( failure ) {
					var fields = failure && failure.data && failure.data.fields;
					edit.hidden = false;
					progress.hidden = true;
					if ( failure && 'affibloom_bulk_running' === failure.code ) {
						lockRunning();
						return;
					}
					busy( false );
					showLines();
					showError( ( fields && fields.urls ) || ( failure && failure.message ) || text( 'message-failed' ) );
					lines.focus();
				} );
		};

		field.addEventListener( 'input', clearError );
		lines.addEventListener( 'input', function () {
			clearError();
			updateCount();
		} );
		// Shift + Enter in the field means "next link": the textarea opens with the caret on a new line. In the
		// textarea it keeps the browser's own newline (Lasso swaps back to the field there; deviation 75).
		field.addEventListener( 'keydown', function ( event ) {
			if ( 'Enter' === event.key && event.shiftKey ) {
				event.preventDefault();
				enterBulk();
				if ( bulk && ! lines.disabled && '' !== lines.value.trim() ) {
					lines.value = lines.value.replace( /\s+$/, '' ) + '\n';
					lines.setSelectionRange( lines.value.length, lines.value.length );
				}
			}
		} );
		lines.addEventListener( 'blur', function () {
			if ( bulk && ! lines.disabled && '' !== lines.value.trim() ) {
				showPreview();
			}
		} );
		// Pressing Add Link would blur the textarea, whose preview is shorter, so the button would move away before the
		// click lands. Keeping the focus until the click submits it (Lasso's mousedown workaround; deviation 77).
		submit.addEventListener( 'mousedown', function ( event ) {
			if ( document.activeElement === lines ) {
				event.preventDefault();
			}
		} );
		edit.addEventListener( 'click', function () {
			showLines();
			lines.focus();
		} );
		reload.addEventListener( 'click', function () {
			window.location.reload();
		} );

		modal.addEventListener( 'affibloom:open', function ( event ) {
			if ( 'bulk' === event.detail.mode ) {
				enterBulk();
			}
		} );
		modal.addEventListener( 'affibloom:close', function () {
			leaveBulk();
			field.value = '';
			lines.value = '';
			edit.hidden = false;
			clearError();
			busy( false );
		} );

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			clearError();
			if ( bulk ) {
				addMany();
			} else {
				addOne();
			}
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		Array.prototype.forEach.call( document.querySelectorAll( '.affibloom-help' ), floatingTip );
		Array.prototype.forEach.call( document.querySelectorAll( 'form[data-save-when-changed]' ), saveWhenChanged );
		Array.prototype.forEach.call( document.querySelectorAll( '.affibloom-add-form' ), addLink );
	} );

	// Content added after load (the product page's "Add Another Button", F29) announces itself for its help tips.
	document.addEventListener( 'affibloom:enhance', function ( event ) {
		Array.prototype.forEach.call( event.target.querySelectorAll( '.affibloom-help' ), floatingTip );
	} );
} )();
