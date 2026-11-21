/* global wp */
/**
 * The Opportunities screens (F22 D3, D8; Task O1): the list redrawn in place by in-place-list.js (search, the sortable
 * headers, pages, and the product filter it keeps), the Type icon's preview (location-preview.js), and the intro box's
 * ×, which closes it for good (POST /opportunities/intros/{box}); a failed save puts the box back.
 */
( function () {
	'use strict';

	var region = document.getElementById( 'affibloom-opportunities-list' );
	if ( region && window.affibloomInPlaceList ) {
		var args = {};
		try {
			args = JSON.parse( region.getAttribute( 'data-list-args' ) || '{}' );
		} catch ( ignored ) {
			args = {};
		}
		window.affibloomInPlaceList( {
			region: region,
			search: document.getElementById( 'affibloom-opportunities-search' ),
			status: document.getElementById( 'affibloom-list-status' ),
			errorTemplate: document.getElementById( 'affibloom-list-error' ),
			path: region.getAttribute( 'data-list-path' ),
			args: args,
		} );
		if ( window.affibloomLocationPreview ) {
			window.affibloomLocationPreview( region );
		}
	}

	document.addEventListener( 'click', function ( event ) {
		var close = event.target.closest( '[data-intro-close]' );
		if ( ! close ) {
			return;
		}
		var box = close.closest( '[data-intro]' );
		box.hidden = true;
		wp.apiFetch( { path: '/affibloom/v1/opportunities/intros/' + close.getAttribute( 'data-intro-close' ), method: 'POST' } ).catch( function () {
			box.hidden = false;
		} );
	} );
}() );
