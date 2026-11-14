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
		// After a server error Lasso's button reads "Continue Anyway" (data-continue=1) until the link is changed;
		// here the click sends the same link again (the user's choice, 2026-11-14).
		var setContinue = function ( on ) {
			if ( on ) {
				submit.setAttribute( 'data-continue', '1' );
				label.textContent = text( 'label-continue' );
			} else if ( submit.hasAttribute( 'data-continue' ) ) {
				submit.removeAttribute( 'data-continue' );
				label.textContent = idleLabel;
			}
		};
		var clearError = function () {
			error.textContent = '';
			error.hidden = true;
			setContinue( false );
			[ field, lines ].forEach( function ( input ) {
				input.classList.remove( 'is-invalid' );
				input.removeAttribute( 'aria-invalid' );
			} );
		};
		var busy = function ( isBusy ) {
			control().disabled = isBusy;
			submit.disabled = isBusy;
			label.textContent = isBusy ? text( 'label-busy' ) : idleLabel;
			if ( ! isBusy ) {
				submit.removeAttribute( 'data-continue' );
			}
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
					setContinue( true );
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

	/* ---------------------------------------------------------------------------------------------------------
	 * Shared by the product page and the Dashboard's quick edit: the toast, the save dialog and its repoint panel (16C),
	 * field errors and the media frame. Moved here from product-save.js and product.js when the quick edit became their second user
	 * (Task 16A); the Display tab's icon card took the media frame as well (its third user).
	 * ------------------------------------------------------------------------------------------------------- */

	/**
	 * Shows a toast like the server's (partials/toast.php), from the page's template (partials/toast-template.php).
	 *
	 * @param {string}  message The words.
	 * @param {boolean} failed  The failure variant, with a cross.
	 */
	function toast( message, failed ) {
		var template = document.getElementById( 'affibloom-toast-template' );
		var host = document.querySelector( '.affibloom-admin' );
		if ( ! template || ! host ) {
			return;
		}
		var element = template.content.firstElementChild.cloneNode( true );
		element.querySelector( '.affibloom-toast__message' ).textContent = message;
		element.querySelector( '[data-toast-icon="success"]' ).hidden = failed;
		element.querySelector( '[data-toast-icon="error"]' ).hidden = ! failed;
		element.classList.toggle( 'affibloom-toast--error', failed );
		host.appendChild( element );
		window.setTimeout( function () {
			element.remove();
		}, 4500 );
	}

	/**
	 * Lasso's "Updating Your Site" (url-save.php; F1 requirements 9 S14, partials/save-modal.php): a static dialog with
	 * a moving bar and one line, which closes itself a second after the answer (url-details.php 1980-1985). Its second
	 * panel, "This Product Already Exists", is shown with panel( true ).
	 */
	var CLOSE_AFTER = 1000;
	var saveDialog = {
		modal: function () {
			return document.querySelector( '[data-save-modal]' );
		},
		// One dialog, two panels: its name and text follow the panel shown.
		panel: function ( repointing ) {
			var modal = saveDialog.modal();
			var dialog = modal.querySelector( '[role="dialog"]' );
			modal.querySelector( '[data-save-progress]' ).hidden = repointing;
			modal.querySelector( '[data-repoint]' ).hidden = ! repointing;
			dialog.setAttribute( 'aria-labelledby', repointing ? 'affibloom-repoint-title' : 'affibloom-save-title' );
			dialog.setAttribute( 'aria-describedby', repointing ? 'affibloom-repoint-text' : 'affibloom-save-text' );
		},
		/**
		 * @param {string} text The line under the bar.
		 */
		open: function ( text ) {
			var modal = saveDialog.modal();
			var bar = modal.querySelector( '[data-save-bar]' );
			saveDialog.panel( false );
			modal.querySelector( '[data-save-text]' ).textContent = text;
			bar.style.width = '0';
			modal.hidden = false;
			document.body.classList.add( 'affibloom-has-modal' );
			modal.querySelector( '[role="dialog"]' ).focus();
			window.requestAnimationFrame( function () {
				bar.style.width = '90%';
			} );
		},
		hide: function () {
			saveDialog.modal().hidden = true;
			document.body.classList.remove( 'affibloom-has-modal' );
		},
		/**
		 * @param {Function} then Called once the dialog has gone.
		 */
		close: function ( then ) {
			saveDialog.modal().querySelector( '[data-save-bar]' ).style.width = '100%';
			window.setTimeout( function () {
				saveDialog.hide();
				then();
			}, CLOSE_AFTER );
		},
		closeAfter: CLOSE_AFTER,
	};

	/**
	 * "This Product Already Exists" (Lasso's repoint panel, the save dialog's second panel; F1 S10, D14, D21): the
	 * product being saved and the one that already has its new link. Cancel (or Escape) closes it and saves nothing;
	 * Confirm retires the product in favour of the other and opens that one's page. Moved here from product-save.js
	 * when the Dashboard's quick edit became its second user (Task 16C).
	 */
	var offered = null;

	function repointParts() {
		var panel = saveDialog.modal().querySelector( '[data-repoint]' );
		return {
			panel: panel,
			cancel: panel.querySelector( '[data-repoint-cancel]' ),
			confirm: panel.querySelector( '[data-repoint-confirm]' ),
		};
	}

	/** Binds Cancel, Escape and Confirm once; each offer leaves its own callbacks in `offered`. */
	function bindRepoint() {
		var parts = repointParts();
		if ( parts.panel.dataset.bound ) {
			return parts;
		}
		parts.panel.dataset.bound = '1';
		parts.cancel.addEventListener( 'click', function () {
			var offer = offered;
			offered = null;
			saveDialog.hide();
			if ( offer ) {
				offer.cancelled();
			}
		} );
		parts.panel.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key && ! parts.cancel.disabled ) {
				parts.cancel.click();
			}
		} );
		parts.confirm.addEventListener( 'click', function () {
			var offer = offered;
			if ( ! offer ) {
				return;
			}
			parts.cancel.disabled = true;
			parts.confirm.disabled = true;
			wp.apiFetch( { path: '/affibloom/v1/products/' + offer.from + '/repoint', method: 'POST', data: { to: offer.to, url: offer.url } } )
				.then( function ( response ) {
					offer.leave( response.data.redirect_to );
				} )
				.catch( function ( error ) {
					parts.cancel.disabled = false;
					parts.confirm.disabled = false;
					toast( error && error.message ? error.message : offer.failedText, true );
				} );
		} );
		return parts;
	}

	var repoint = {
		/**
		 * @param {number} from    The product being saved.
		 * @param {Object} data    The 409 answer's data: { from, to }, each { id, name, url, page }.
		 * @param {string} url     The link as typed, which the repoint checks again.
		 * @param {Object} options { cancelled: after Cancel (gives the focus back), leave( href ): goes to the kept
		 *                         product (the page quiets its leave guard first), failedText: the failure toast's words }.
		 */
		offer: function ( from, data, url, options ) {
			var parts = bindRepoint();
			offered = { from: from, to: data.to.id, url: url, cancelled: options.cancelled, leave: options.leave, failedText: options.failedText };
			[ 'from', 'to' ].forEach( function ( side ) {
				var box = parts.panel.querySelector( '[data-repoint-side="' + side + '"]' );
				var item = data[ side ] || { name: '', url: '', page: '#' };
				box.querySelector( '[data-repoint-name]' ).textContent = item.name;
				box.querySelector( '[data-repoint-url]' ).textContent = item.url;
				box.querySelector( '[data-repoint-view]' ).href = item.page;
			} );
			parts.cancel.disabled = false;
			parts.confirm.disabled = false;
			saveDialog.panel( true );
			parts.cancel.focus();
		},
	};

	/**
	 * Lasso's sentence under each refused field, in the design system's field error (F1 D11). A field is found by its
	 * name in the form, and must have an id.
	 */
	var fieldErrors = {
		/**
		 * @param {HTMLFormElement} form   The form.
		 * @param {Object}          fields Field name => message, as the REST answer's data.fields.
		 */
		show: function ( form, fields ) {
			var first = null;
			Object.keys( fields ).forEach( function ( name ) {
				var input = form.elements.namedItem( name );
				if ( ! input || input.disabled || ! input.id ) {
					return;
				}
				var note = document.createElement( 'p' );
				note.className = 'affibloom-field__error';
				note.id = input.id + '-save-error';
				note.dataset.saveError = input.id;
				note.textContent = fields[ name ];
				( input.closest( '.affibloom-field' ) || input.parentNode ).appendChild( note );
				input.classList.add( 'is-invalid' );
				input.setAttribute( 'aria-invalid', 'true' );
				input.setAttribute( 'aria-describedby', ( ( input.getAttribute( 'aria-describedby' ) || '' ) + ' ' + note.id ).trim() );
				first = first || input;
			} );
			if ( first ) {
				first.focus();
			}
		},
		/**
		 * @param {HTMLFormElement} form The form.
		 */
		clear: function ( form ) {
			Array.prototype.forEach.call( form.querySelectorAll( '[data-save-error]' ), function ( note ) {
				var input = document.getElementById( note.dataset.saveError );
				if ( input ) {
					input.classList.remove( 'is-invalid' );
					input.removeAttribute( 'aria-invalid' );
					// Only the note's id goes; a field described by nothing else loses the attribute.
					var described = ( input.getAttribute( 'aria-describedby' ) || '' ).split( /\s+/ ).filter( function ( id ) {
						return '' !== id && id !== note.id;
					} ).join( ' ' );
					if ( '' === described ) {
						input.removeAttribute( 'aria-describedby' );
					} else {
						input.setAttribute( 'aria-describedby', described );
					}
				}
				note.remove();
			} );
		},
	};

	/**
	 * WordPress's media frame for one item, as Lasso opens it: "Select an Image", images only, one, "Select Image" on
	 * the product page and in quick edit. A frame is kept per title and file types, as each caller kept its own; the
	 * newest caller's `then` gets the chosen item.
	 */
	var frames = {};
	var media = {
		available: function () {
			return !! ( window.wp && wp.media );
		},
		/**
		 * @param {Object}   options       { title, button, types }: the frame's title and button, the library's types
		 *                                 ('image' when not given).
		 * @param {Function} then          Gets the chosen attachment as JSON (id, url, …).
		 */
		pick: function ( options, then ) {
			if ( ! media.available() ) {
				return;
			}
			var types = options.types || 'image';
			var key = options.title + '|' + String( types );
			var frame = frames[ key ];
			if ( ! frame ) {
				frame = wp.media( {
					title: options.title,
					multiple: false,
					library: { type: types },
					button: { text: options.button },
				} );
				frame.on( 'select', function () {
					frame.affibloomThen( frame.state().get( 'selection' ).first().toJSON() );
				} );
				frames[ key ] = frame;
			}
			frame.affibloomThen = then;
			frame.open();
		},
	};

	window.affibloomToast = toast;
	window.affibloomSaveDialog = saveDialog;
	window.affibloomFieldErrors = fieldErrors;
	window.affibloomMedia = media;
	window.affibloomRepoint = repoint;

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
