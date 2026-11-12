/**
 * Affibloom admin: what every settings screen shares — help tips that float clear of scrolling areas, and Save
 * Changes kept disabled until something changes (a form opts in with data-save-when-changed). Extracted from the
 * Display screen's script when the Amazon tab became its second user.
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

	document.addEventListener( 'DOMContentLoaded', function () {
		Array.prototype.forEach.call( document.querySelectorAll( '.affibloom-help' ), floatingTip );
		Array.prototype.forEach.call( document.querySelectorAll( 'form[data-save-when-changed]' ), saveWhenChanged );
	} );
} )();
