/**
 * Affibloom's click counter and Google Analytics events (F12 R5-R7, D3, D4; Task 33A; C3 R7-R10, D1-D4, D6; Task C3B).
 *
 * One capture listener for `click` and `auxclick` (the middle button) on the document. A link that carries
 * `data-affibloom-id` (a box's buttons, title and image; a tied link in the content):
 * - is counted, when Page and Link-Level Tracking is on (`count`): `{ p, h, l, b }` to the site's own route with
 *   `navigator.sendBeacon`, or `fetch` with `keepalive`, or nothing;
 * - sends one Google Analytics event, when GA Click Tracking is on (`ga`): `gtag( 'event', … )` when gtag is on the
 *   page, else a push onto Google Tag Manager's `dataLayer`, else analytics.js's `ga( 'send', 'event', … )`, else nothing.
 * The link is never held or changed, no cookie is set, and a failure is silent (AC16). The script and its settings are
 * the same for every visitor of a page, so cached pages count (AC17).
 */
( function () {
	'use strict';

	var settings = window.affibloomClicks;
	if ( ! settings || ! settings.url ) {
		return;
	}

	/**
	 * What was clicked (D4): a button's place (the box numbers the second and later ones with `data-affibloom-btn`; the
	 * first is its `affibloom-button-1`), the box's title or image, or an in-content link.
	 *
	 * @param {HTMLAnchorElement} link The clicked link.
	 * @return {string} The button.
	 */
	function button( link ) {
		if ( link.getAttribute( 'data-affibloom-btn' ) ) {
			return link.getAttribute( 'data-affibloom-btn' );
		}
		if ( link.classList.contains( 'affibloom-button-1' ) ) {
			return '1';
		}
		if ( link.classList.contains( 'affibloom-image' ) || link.closest( '.affibloom-image' ) ) {
			return 'image';
		}
		return 'true' === link.getAttribute( 'data-affibloom-box-trackable' ) ? 'title' : 'link';
	}

	/**
	 * Counts the click on the site.
	 *
	 * @param {HTMLAnchorElement} link   The clicked link.
	 * @param {string}            which  What was clicked.
	 */
	function count( link, which ) {
		var body = JSON.stringify( {
			p: link.getAttribute( 'data-affibloom-id' ),
			h: String( settings.post || 0 ),
			l: link.getAttribute( 'data-affibloom-location' ) || '0',
			b: which,
		} );
		if ( navigator.sendBeacon && navigator.sendBeacon( settings.url, new Blob( [ body ], { type: 'text/plain' } ) ) ) {
			return;
		}
		if ( window.fetch ) {
			window.fetch( settings.url, { method: 'POST', body: body, keepalive: true, credentials: 'omit', headers: { 'Content-Type': 'text/plain' } } ).catch( function () {} );
		}
	}

	/**
	 * The nearest element around the link that holds the box's first button: the display the link belongs to.
	 *
	 * @param {HTMLAnchorElement} link The clicked link.
	 * @return {?Element} The display, or null for a link outside one.
	 */
	function display( link ) {
		for ( var scope = link.parentElement; scope && scope !== document.body; scope = scope.parentElement ) {
			if ( scope.querySelector( '.affibloom-button-1' ) ) {
				return scope;
			}
		}
		return null;
	}

	function textOf( element ) {
		return element ? ( element.textContent || '' ).replace( /\s+/g, ' ' ).trim() : '';
	}

	/**
	 * Sends Affibloom's event for the click (C3 R8-R10; names decided 2026-11-14): Primary for the first button, the
	 * title and the image (labelled with the first button's text); Secondary for the second and later buttons (their own
	 * text, F29 D3); Link for an in-content link (its text).
	 *
	 * @param {HTMLAnchorElement} link  The clicked link.
	 * @param {string}            which What was clicked.
	 */
	function track( link, which ) {
		var box = 'link' === which ? null : display( link );
		var primary = 'link' !== which && ( '1' === which || 'title' === which || 'image' === which );
		var name = 'link' === which ? 'affibloom_link_click' : ( primary ? 'affibloom_primary_click' : 'affibloom_secondary_click' );
		var product = link.getAttribute( 'title' ) || link.getAttribute( 'data-affibloom-name' ) || '';
		var label = primary && box ? textOf( box.querySelector( '.affibloom-button-1' ) ) : textOf( link );
		label = label || textOf( link ) || product;
		var params = {
			event_type: 'click',
			event_category: product || label,
			event_label: label,
			affibloom_title: product,
			affibloom_href: link.getAttribute( 'href' ) || '',
			affibloom_text: label,
			affibloom_badge: textOf( box ? box.querySelector( '.affibloom-badge-text' ) : null ),
		};

		if ( 'function' === typeof window.gtag ) {
			window.gtag( 'event', name, params );
		} else if ( window.dataLayer && 'function' === typeof window.dataLayer.push ) {
			window.dataLayer.push( Object.assign( { event: name }, params ) );
		} else if ( 'function' === typeof window.ga ) {
			// The default tracker (R10's named tracker was the old plugin's own and is not used; deviation 146).
			window.ga( 'send', 'event', params.event_category, name, params.affibloom_href );
		}
	}

	function onClick( event ) {
		// The primary and middle buttons only; a right-click opens a menu, not the link.
		if ( event.button > 1 ) {
			return;
		}
		var link = event.target && event.target.closest ? event.target.closest( 'a[data-affibloom-id]' ) : null;
		if ( ! link ) {
			return;
		}
		var which = button( link );
		try {
			if ( settings.count ) {
				count( link, which );
			}
			if ( settings.ga ) {
				track( link, which );
			}
		} catch ( ignored ) {
			// A blocked beacon or a broken analytics library changes nothing for the reader (AC16).
		}
	}

	document.addEventListener( 'click', onClick, true );
	document.addEventListener( 'auxclick', onClick, true );
}() );
