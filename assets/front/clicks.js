/**
 * Affibloom's click counter (F12 R5-R7, D3, D4; Task 33A). One capture listener for `click` and `auxclick` (the middle
 * button) on the document: a link that carries `data-affibloom-id` (a box's buttons, title and image; a tied link in
 * the content) sends `{ p, h, l, b }` to the site's own route with `navigator.sendBeacon`, or `fetch` with
 * `keepalive`, or nothing. The link is never held or changed, no cookie is set, and a failure is silent (AC16). The
 * script and its settings are the same for every visitor of a page, so cached pages count (AC17).
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

	function send( event ) {
		// The primary and middle buttons only; a right-click opens a menu, not the link.
		if ( event.button > 1 ) {
			return;
		}
		var link = event.target && event.target.closest ? event.target.closest( 'a[data-affibloom-id]' ) : null;
		if ( ! link ) {
			return;
		}
		var body = JSON.stringify( {
			p: link.getAttribute( 'data-affibloom-id' ),
			h: String( settings.post || 0 ),
			l: link.getAttribute( 'data-affibloom-location' ) || '0',
			b: button( link ),
		} );
		try {
			if ( navigator.sendBeacon && navigator.sendBeacon( settings.url, new Blob( [ body ], { type: 'text/plain' } ) ) ) {
				return;
			}
			if ( window.fetch ) {
				window.fetch( settings.url, { method: 'POST', body: body, keepalive: true, credentials: 'omit', headers: { 'Content-Type': 'text/plain' } } ).catch( function () {} );
			}
		} catch ( ignored ) {
			// A blocked or failed beacon changes nothing for the reader (AC16).
		}
	}

	document.addEventListener( 'click', send, true );
	document.addEventListener( 'auxclick', send, true );
}() );
