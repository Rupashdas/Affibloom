/**
 * The Dashboard tour (F38 R59-R63, AC12; Task ON8). A spotlight over each step's part of the page, the step's text in a
 * tooltip beside it with dots, Back, Next (Done on the last) and Skip; a step whose part is not on the page (or not
 * shown, as the header tabs folded into Menu on a phone) is dropped; the Filters panel is open for its two steps. The
 * shade takes every click, so Escape and the backdrop end nothing; only Done or Skip end the tour, for every user
 * (POST /tour/done). The focus stays in the tooltip.
 */
( function () {
	'use strict';

	var root = document.querySelector( '[data-tour]' );
	if ( ! root || ! window.wp || ! window.wp.apiFetch ) {
		return;
	}

	var spot = root.querySelector( '[data-tour-spot]' );
	var tip = root.querySelector( '[data-tour-tip]' );
	var text = root.querySelector( '[data-tour-text]' );
	var count = root.querySelector( '[data-tour-count]' );
	var dots = root.querySelector( '[data-tour-dots]' );
	var back = root.querySelector( '[data-tour-back]' );
	var next = root.querySelector( '[data-tour-next]' );
	var skip = root.querySelector( '[data-tour-skip]' );
	var toggle = document.querySelector( '.affibloom-filters__toggle' );
	var panel = document.getElementById( 'affibloom-filters-panel' );
	var steps = Array.prototype.slice.call( root.querySelectorAll( '[data-tour-step]' ) );
	var at = 0;

	/**
	 * Opens or closes the Filters panel through its own toggle, as the editor would.
	 *
	 * @param {boolean} open Whether it should be open.
	 */
	function filters( open ) {
		if ( toggle && panel && open === panel.hidden ) {
			toggle.click();
		}
	}

	/**
	 * Whether a step's part is on the page and shown (the panel counts when its toggle is shown: it opens for its step).
	 *
	 * @param {HTMLElement} step A step.
	 * @return {boolean} Whether the step can show.
	 */
	function present( step ) {
		var target = document.querySelector( step.dataset.target );
		if ( ! target ) {
			return false;
		}
		if ( target === panel ) {
			return !! toggle && null !== toggle.offsetParent;
		}
		return null !== target.offsetParent;
	}

	/**
	 * Shows one step.
	 *
	 * @param {number} index The step.
	 */
	function show( index ) {
		at = index;
		var step = steps[ at ];
		filters( step.hasAttribute( 'data-filters' ) );
		var target = document.querySelector( step.dataset.target );
		target.scrollIntoView( { block: 'center' } );
		var box = target.getBoundingClientRect();
		var pad = 6;
		spot.style.top = ( box.top - pad ) + 'px';
		spot.style.left = ( box.left - pad ) + 'px';
		spot.style.width = ( box.width + ( pad * 2 ) ) + 'px';
		spot.style.height = ( box.height + ( pad * 2 ) ) + 'px';

		text.textContent = step.textContent.trim();
		count.textContent = count.dataset.format.replace( '%1$d', String( at + 1 ) ).replace( '%2$d', String( steps.length ) );
		Array.prototype.forEach.call( dots.children, function ( dot, n ) {
			dot.classList.toggle( 'is-current', n === at );
		} );
		back.disabled = 0 === at;
		next.textContent = at === steps.length - 1 ? next.dataset.done : next.dataset.next;

		// The tooltip goes under the part, else above it, else beside it (a tall part, as the Filters panel); inside the
		// window always.
		var width = Math.min( 360, window.innerWidth - 32 );
		tip.style.width = width + 'px';
		var height = tip.offsetHeight;
		var gap = pad + 12;
		var left = Math.max( 16, Math.min( box.left, window.innerWidth - width - 16 ) );
		var top = box.bottom + gap;
		if ( top + height > window.innerHeight - 16 ) {
			top = box.top - gap - height;
		}
		if ( top < 16 ) {
			top = Math.max( 16, Math.min( box.top, window.innerHeight - height - 16 ) );
			if ( box.left - gap - width >= 16 ) {
				left = box.left - gap - width;
			} else if ( box.right + gap + width <= window.innerWidth - 16 ) {
				left = box.right + gap;
			}
		}
		tip.style.left = left + 'px';
		tip.style.top = top + 'px';
		next.focus();
	}

	/**
	 * Keeps Tab inside the tooltip; Escape ends nothing (R62).
	 *
	 * @param {KeyboardEvent} event The key.
	 */
	function keys( event ) {
		if ( 'Escape' === event.key ) {
			event.preventDefault();
			event.stopPropagation();
			return;
		}
		if ( 'Tab' !== event.key ) {
			return;
		}
		var controls = Array.prototype.filter.call( tip.querySelectorAll( 'button' ), function ( button ) {
			return ! button.disabled;
		} );
		var index = controls.indexOf( document.activeElement );
		event.preventDefault();
		controls[ ( index + ( event.shiftKey ? -1 : 1 ) + controls.length ) % controls.length ].focus();
	}

	/**
	 * Ends the tour for every user.
	 */
	function end() {
		root.hidden = true;
		filters( false );
		document.removeEventListener( 'keydown', keys, true );
		window.wp.apiFetch( { path: '/affibloom/v1/tour/done', method: 'POST', parse: false } ).catch( function () {} );
	}

	function start() {
		steps = steps.filter( present );
		if ( ! steps.length ) {
			return;
		}
		steps.forEach( function () {
			dots.appendChild( document.createElement( 'span' ) );
		} );
		root.hidden = false;
		document.addEventListener( 'keydown', keys, true );
		// The tour's clicks stay in the tour: the page's "a click outside closes it" would shut the Filters panel.
		root.addEventListener( 'click', function ( event ) {
			event.stopPropagation();
		} );
		next.addEventListener( 'click', function () {
			if ( at === steps.length - 1 ) {
				end();
				return;
			}
			show( at + 1 );
		} );
		back.addEventListener( 'click', function () {
			show( Math.max( 0, at - 1 ) );
		} );
		skip.addEventListener( 'click', end );
		window.addEventListener( 'resize', function () {
			if ( ! root.hidden ) {
				show( at );
			}
		} );
		show( 0 );
	}

	// After the page has loaded, so the parts are where they will stay.
	if ( 'complete' === document.readyState ) {
		start();
	} else {
		window.addEventListener( 'load', start );
	}
}() );
