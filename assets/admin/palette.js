/**
 * "Search links" (F38 R65-R72, D19-D21, AC13; Task ON9), Lasso's command palette. Ctrl+Shift+L (⌘⇧L on a Mac) opens
 * it and closes it again, except while typing in a field outside it. It lists the ten most recently changed products
 * at once and searches after a pause of typing, with the Dashboard's rule (GET /products?search=); ↓ and ↑ move between
 * the box and the results, Enter or a click opens the product page, Escape closes, Tab stays inside, and the focus
 * goes back to where it was. Nothing else: the palette has no commands (R72).
 */
( function () {
	'use strict';

	var template = document.getElementById( 'affibloom-palette-template' );
	if ( ! template || ! window.wp || ! window.wp.apiFetch ) {
		return;
	}

	var mac = /^Mac/.test( window.navigator.platform || '' );
	var keys = mac ? template.dataset.keysMac : template.dataset.keysPc;
	var root = null;
	var input = null;
	var list = null;
	var status = null;
	var opener = null;
	var timer = 0;
	var asked = 0;
	var request = null;

	// Every place that shows the shortcut shows the user's own keys (R71): the Dashboard's search box.
	Array.prototype.forEach.call( document.querySelectorAll( '[data-affibloom-shortcut]' ), function ( label ) {
		label.textContent = keys;
	} );

	/**
	 * The dialog, copied from its template the first time it opens.
	 *
	 * @return {HTMLElement} The palette.
	 */
	function build() {
		if ( root ) {
			return root;
		}
		document.body.appendChild( template.content.cloneNode( true ) );
		root = document.querySelector( '[data-palette]' );
		input = root.querySelector( '[data-palette-input]' );
		list = root.querySelector( '[data-palette-results]' );
		status = root.querySelector( '[data-palette-status]' );
		var hint = root.querySelector( '[data-palette-hint]' );
		hint.textContent = hint.dataset.format.replace( '%s', keys );

		Array.prototype.forEach.call( root.querySelectorAll( '[data-palette-close]' ), function ( closer ) {
			closer.addEventListener( 'click', close );
		} );
		input.addEventListener( 'input', function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( function () {
				search( input.value );
			}, 280 );
		} );
		list.addEventListener( 'click', function ( event ) {
			var option = event.target.closest( '[role="option"]' );
			if ( option ) {
				go( option );
			}
		} );
		root.addEventListener( 'keydown', inside );
		return root;
	}

	/**
	 * Searches the products; an answer to an older search is dropped (R68).
	 *
	 * @param {string} term What was typed.
	 */
	function search( term ) {
		var mine = ++asked;
		if ( request ) {
			request.abort();
		}
		request = 'function' === typeof window.AbortController ? new window.AbortController() : null;
		list.textContent = '';
		status.textContent = template.dataset.loading;
		window.wp.apiFetch( {
			path: '/affibloom/v1/products?per_page=10&search=' + encodeURIComponent( term.trim() ),
			signal: request ? request.signal : undefined,
		} ).then( function ( answer ) {
			if ( mine !== asked ) {
				return;
			}
			var items = answer && answer.data ? answer.data.items : [];
			status.textContent = items.length ? '' : template.dataset.empty;
			items.forEach( show );
		} ).catch( function ( error ) {
			if ( mine !== asked || ( error && 'AbortError' === error.name ) ) {
				return;
			}
			status.textContent = template.dataset.failed;
		} );
	}

	/**
	 * Adds one product to the results: its picture, name and link (D21).
	 *
	 * @param {{id: number, name: string, url: string, image_url: string}} item A product.
	 */
	function show( item ) {
		var option = document.createElement( 'li' );
		option.className = 'affibloom-palette__result';
		option.setAttribute( 'role', 'option' );
		option.setAttribute( 'aria-selected', 'false' );
		option.tabIndex = -1;
		option.dataset.href = template.dataset.productUrl + '&id=' + encodeURIComponent( String( item.id ) );

		var picture = document.createElement( 'span' );
		picture.className = 'affibloom-palette__thumb';
		if ( item.image_url ) {
			var image = document.createElement( 'img' );
			image.src = item.image_url;
			image.alt = '';
			image.loading = 'lazy';
			picture.appendChild( image );
		}
		var words = document.createElement( 'span' );
		words.className = 'affibloom-palette__words';
		var name = document.createElement( 'span' );
		name.className = 'affibloom-palette__name';
		name.textContent = item.name;
		var url = document.createElement( 'span' );
		url.className = 'affibloom-palette__url';
		url.textContent = item.url;
		words.appendChild( name );
		words.appendChild( url );
		option.appendChild( picture );
		option.appendChild( words );
		list.appendChild( option );
	}

	/**
	 * Opens a result's product page.
	 *
	 * @param {HTMLElement} option The result.
	 */
	function go( option ) {
		window.location.href = option.dataset.href;
	}

	/**
	 * Moves the focus to a result or the box, and marks the result chosen.
	 *
	 * @param {HTMLElement} target The result, or the box.
	 */
	function focus( target ) {
		Array.prototype.forEach.call( list.children, function ( option ) {
			option.setAttribute( 'aria-selected', option === target ? 'true' : 'false' );
		} );
		target.focus();
	}

	/**
	 * Keys inside the palette (R70).
	 *
	 * @param {KeyboardEvent} event The key.
	 */
	function inside( event ) {
		var options = Array.prototype.slice.call( list.children );
		var at = options.indexOf( document.activeElement );
		if ( 'Escape' === event.key ) {
			event.preventDefault();
			event.stopPropagation();
			close();
		} else if ( 'ArrowDown' === event.key && options.length ) {
			event.preventDefault();
			focus( options[ Math.min( at + 1, options.length - 1 ) ] );
		} else if ( 'ArrowUp' === event.key && at >= 0 ) {
			event.preventDefault();
			focus( 0 === at ? input : options[ at - 1 ] );
		} else if ( 'Enter' === event.key && at >= 0 ) {
			event.preventDefault();
			go( options[ at ] );
		} else if ( 'Tab' === event.key ) {
			trap( event );
		}
	}

	/**
	 * Keeps Tab inside the palette: the close button, the box and the results.
	 *
	 * @param {KeyboardEvent} event The Tab key.
	 */
	function trap( event ) {
		var items = Array.prototype.slice.call( root.querySelectorAll( 'button, input, [role="option"]' ) );
		var at = items.indexOf( document.activeElement );
		event.preventDefault();
		items[ ( at + ( event.shiftKey ? -1 : 1 ) + items.length ) % items.length ].focus();
	}

	function open() {
		build();
		opener = document.activeElement;
		root.hidden = false;
		document.documentElement.classList.add( 'affibloom-palette-open' );
		input.value = '';
		input.focus();
		search( '' );
	}

	function close() {
		window.clearTimeout( timer );
		asked++;
		if ( request ) {
			request.abort();
			request = null;
		}
		root.hidden = true;
		document.documentElement.classList.remove( 'affibloom-palette-open' );
		if ( opener && opener.isConnected && 'function' === typeof opener.focus ) {
			opener.focus();
		}
	}

	// The shortcut toggles it; outside the palette it does nothing while a text field, text area or list is being used.
	// Heard first and kept: another script listening for the same keys (a palette left from before the move) would
	// open a second dialog under this one and take the focus.
	document.addEventListener( 'keydown', function ( event ) {
		if ( ! event.shiftKey || event.altKey || ! ( mac ? event.metaKey : event.ctrlKey ) || 'l' !== String( event.key ).toLowerCase() ) {
			return;
		}
		var shown = root && ! root.hidden;
		if ( ! shown && event.target.closest && event.target.closest( 'input, textarea, select' ) ) {
			return;
		}
		event.preventDefault();
		event.stopImmediatePropagation();
		if ( shown ) {
			close();
		} else {
			open();
		}
	}, true );
}() );
