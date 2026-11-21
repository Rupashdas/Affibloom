/**
 * A product's Locations tab (F12 L4-L9, D22, D23; Task 36B): the list redrawn in place by in-place-list.js (search,
 * pages and the Content sort), and a post's group row opening and closing the places under it (L6). A row's link text
 * or Type icon opens "Link with Context" (L10, D24; Task 36C; location-preview.js).
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

	// Link with Context (Task 36C), shared with the Opportunities screens.
	if ( window.affibloomLocationPreview ) {
		window.affibloomLocationPreview( region );
	}
}() );
