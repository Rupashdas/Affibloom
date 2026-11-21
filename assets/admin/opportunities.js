/* global wp */
/**
 * The Opportunities screens (F22 D3, D8; Tasks O1-O3): the list redrawn in place by in-place-list.js (search, the
 * sortable headers, pages, the product filter it keeps, and Content's filter bar, whose every change reloads page 1,
 * R25), the Type icon's preview (location-preview.js), and the intro box's ×, which closes it for good
 * (POST /opportunities/intros/{box}); a failed save puts the box back.
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
		var filters = document.getElementById( 'affibloom-content-filters' );
		var list = window.affibloomInPlaceList( {
			region: region,
			search: document.getElementById( 'affibloom-opportunities-search' ),
			status: document.getElementById( 'affibloom-list-status' ),
			errorTemplate: document.getElementById( 'affibloom-list-error' ),
			path: region.getAttribute( 'data-list-path' ),
			args: filters
				? function () {
					var values = {};
					new FormData( filters ).forEach( function ( value, key ) {
						if ( 'page' !== key && 'tab' !== key ) {
							values[ key ] = value;
						}
					} );
					return values;
				}
				: args,
		} );
		if ( filters ) {
			filters.addEventListener( 'change', function () {
				list.reload();
			} );
			filters.addEventListener( 'submit', function ( event ) {
				event.preventDefault();
				list.reload();
			} );
		}
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
