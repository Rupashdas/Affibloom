/**
 * Affibloom Fields (F26): the Fields list without page reloads (Task FL1). The list is in-place-list.js's; later tasks
 * add the field form's save and the delete dialogs here.
 */
( function () {
	'use strict';

	var region = document.getElementById( 'affibloom-fields-list' );
	if ( region && window.affibloomInPlaceList ) {
		window.affibloomInPlaceList( {
			region: region,
			search: document.getElementById( 'affibloom-fields-search' ),
			status: document.getElementById( 'affibloom-list-status' ),
			errorTemplate: document.getElementById( 'affibloom-list-error' ),
			path: '/affibloom/v1/fields/list',
		} );
	}
}() );
