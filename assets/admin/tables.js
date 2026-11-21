/**
 * The Tables list (F25 R8, D16; Task T1): search on Enter and the page links redraw the list in place through
 * GET /tables (in-place-list.js), keeping `s` and `paged` in the address, as Lasso's list does.
 */
( function () {
	'use strict';

	var region = document.getElementById( 'affibloom-tables-list' );
	if ( ! region || ! window.affibloomInPlaceList ) {
		return;
	}
	window.affibloomInPlaceList( {
		region: region,
		search: document.getElementById( 'affibloom-tables-search' ),
		status: document.getElementById( 'affibloom-list-status' ),
		errorTemplate: document.getElementById( 'affibloom-list-error' ),
		path: region.getAttribute( 'data-list-path' ),
		args: {},
	} );
}() );
