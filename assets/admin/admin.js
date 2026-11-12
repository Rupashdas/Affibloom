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
	 * the backdrop closes it. While it is open, Tab stays inside it; on closing, the focus goes back to the opener
	 * and the modal hears an `affibloom:close` event so it can reset itself.
	 */
	var FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]):not([type=hidden]), textarea:not([disabled]), select:not([disabled])';
	var openModal = null;
	var opener = null;

	function showModal( modal, trigger ) {
		var first = modal.querySelector( '.affibloom-modal__body input, .affibloom-modal__body textarea' ) || modal.querySelector( FOCUSABLE );
		opener = trigger;
		openModal = modal;
		modal.hidden = false;
		document.body.classList.add( 'affibloom-has-modal' );
		if ( first ) {
			first.focus();
		}
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

	document.addEventListener( 'click', function ( event ) {
		var trigger = event.target.closest( '[data-affibloom-open]' );
		var modal = trigger ? document.getElementById( trigger.getAttribute( 'data-affibloom-open' ) ) : null;
		if ( modal ) {
			event.preventDefault();
			showModal( modal, trigger );
		} else if ( openModal && event.target.closest( '[data-affibloom-close]' ) ) {
			hideModal();
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
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
	 * "Add A New Link", single-link mode (F18, Task 11A). As in Lasso: Enter or Add Link submits; an empty box is
	 * refused here without a request ("URL is incorrect."); while the request runs, the field and button are
	 * disabled; typing clears the message. On success the browser goes where the server says.
	 *
	 * @param {HTMLFormElement} form The `.affibloom-add-form`.
	 */
	function addLink( form ) {
		var field = form.querySelector( '#affibloom-add-url' );
		var error = form.querySelector( '#affibloom-add-error' );
		var submit = form.querySelector( '#affibloom-add-submit' );
		var label = submit.querySelector( '.affibloom-add-form__label' );
		var idleLabel = label.textContent;

		var showError = function ( message ) {
			error.textContent = message;
			error.hidden = false;
			field.classList.add( 'is-invalid' );
			field.setAttribute( 'aria-invalid', 'true' );
		};
		var clearError = function () {
			error.textContent = '';
			error.hidden = true;
			field.classList.remove( 'is-invalid' );
			field.removeAttribute( 'aria-invalid' );
		};
		var busy = function ( isBusy ) {
			field.disabled = isBusy;
			submit.disabled = isBusy;
			label.textContent = isBusy ? form.getAttribute( 'data-label-busy' ) : idleLabel;
		};

		field.addEventListener( 'input', clearError );

		form.closest( '.affibloom-modal' ).addEventListener( 'affibloom:close', function () {
			field.value = '';
			clearError();
			busy( false );
		} );

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			clearError();
			if ( '' === field.value.trim() ) {
				showError( form.getAttribute( 'data-message-empty' ) );
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
					showError( ( fields && fields.url ) || ( failure && failure.message ) || form.getAttribute( 'data-message-failed' ) );
					field.focus();
				} );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		Array.prototype.forEach.call( document.querySelectorAll( '.affibloom-help' ), floatingTip );
		Array.prototype.forEach.call( document.querySelectorAll( 'form[data-save-when-changed]' ), saveWhenChanged );
		Array.prototype.forEach.call( document.querySelectorAll( '.affibloom-add-form' ), addLink );
	} );
} )();
