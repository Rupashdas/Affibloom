/* global wp */
/**
 * "Link with Context" (F12 L10, D24; Task 36C; F22 R13, D21): a [data-location-preview="<row>"] button in a list opens
 * the page's #affibloom-location-preview dialog with GET /locations/{row}/preview: the paragraph, already escaped down
 * to its links by the server, or the display drawn in the shared preview frame. Taken out of product-locations.js on
 * its second use (the Opportunities screens, Task O1).
 *
 * window.affibloomLocationPreview( region ) wires the buttons inside `region`, which may be redrawn: the click is caught
 * on the region itself.
 */
( function () {
	'use strict';

	window.affibloomLocationPreview = function ( region ) {
		var modal = document.getElementById( 'affibloom-location-preview' );
		if ( ! region || ! modal ) {
			return;
		}
		var body = modal.querySelector( '[data-location-preview-body]' );
		var host = modal.querySelector( '[data-location-preview-host]' );
		var asked = 0;

		region.addEventListener( 'click', function ( event ) {
			var trigger = event.target.closest( '[data-location-preview]' );
			if ( ! trigger ) {
				return;
			}
			var mine = ++asked;
			host.textContent = '';
			body.innerHTML = '<span class="affibloom-skeleton affibloom-location-preview__loading" aria-hidden="true"></span>';
			window.affibloomModal.show( modal, trigger );

			wp.apiFetch( { path: '/affibloom/v1/locations/' + trigger.getAttribute( 'data-location-preview' ) + '/preview' } ).then( function ( answer ) {
				// Only the last preview asked for may draw.
				if ( mine !== asked ) {
					return;
				}
				host.textContent = answer.host;
				if ( 'display' === answer.kind ) {
					body.innerHTML = '';
					window.affibloomBoxFrame( body, answer, modal.querySelector( '.affibloom-modal__title' ).textContent, function () {} );
					return;
				}
				// The route escaped the paragraph down to text and links (LocationPreviewController).
				body.innerHTML = '<p class="affibloom-location-preview__text">' + answer.html + '</p>';
			} ).catch( function ( error ) {
				if ( mine !== asked ) {
					return;
				}
				body.textContent = ( error && error.message ) || modal.getAttribute( 'data-failed' );
			} );
		} );
	};
}() );
