/* global wp */
/**
 * A product's Locations tab (F12 L4-L9, D22, D23; Task 36B): the list redrawn in place by in-place-list.js (search,
 * pages and the Content sort), and a post's group row opening and closing the places under it (L6). A row's link text
 * or Type icon opens "Link with Context" (L10, D24; Task 36C): the server's paragraph, already escaped down to its
 * links, or the display drawn in the shared preview frame.
 */
( function () {
	'use strict';

	var region = document.getElementById( 'affibloom-locations-list' );
	if ( ! region || ! window.affibloomInPlaceList ) {
		return;
	}

	window.affibloomInPlaceList( {
		region: region,
		search: document.getElementById( 'affibloom-locations-search' ),
		status: document.getElementById( 'affibloom-list-status' ),
		errorTemplate: document.getElementById( 'affibloom-list-error' ),
		path: region.getAttribute( 'data-locations-path' ),
	} );

	// The group row's toggle; the rows are redrawn with each page, so the click is caught on the region.
	region.addEventListener( 'click', function ( event ) {
		var toggle = event.target.closest( '[data-location-toggle]' );
		if ( ! toggle ) {
			return;
		}
		var open = 'true' !== toggle.getAttribute( 'aria-expanded' );
		toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		region.querySelectorAll( '[data-location-of="' + toggle.getAttribute( 'data-location-toggle' ) + '"]' ).forEach( function ( row ) {
			row.hidden = ! open;
		} );
	} );

	var modal = document.getElementById( 'affibloom-location-preview' );
	if ( ! modal ) {
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
}() );
