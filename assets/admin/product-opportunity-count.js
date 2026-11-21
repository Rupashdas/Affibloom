/* global wp */
/**
 * The product page's Opportunities badge after Save Changes (F22 R34, AC5; Task O5): a save can change the product's
 * domain or its Detect Opportunities, so the badge reads the product's Link Opportunities again. product-save.js
 * announces the save with `affibloom:product-saved`; a failed read leaves the badge as it was. On the Opportunities tab
 * the badge follows the list as it is drawn again (a dismissal or its Undo, Task O7), unless a search narrows it.
 */
( function () {
	'use strict';

	var region = document.getElementById( 'affibloom-opportunities-list' );
	if ( region ) {
		region.addEventListener( 'affibloom:list-loaded', function ( event ) {
			var badge = document.querySelector( '[data-tab-count="opportunities"]' );
			if ( badge && '' === event.detail.term ) {
				badge.textContent = Number( event.detail.total ).toLocaleString();
			}
		} );
	}

	document.addEventListener( 'affibloom:product-saved', function ( event ) {
		var badge = document.querySelector( '[data-tab-count="opportunities"]' );
		var form = event.target;
		var id = form && form.dataset ? form.dataset.productId : '';
		if ( ! badge || ! id ) {
			return;
		}
		wp.apiFetch( { path: '/affibloom/v1/opportunities/links?product=' + encodeURIComponent( id ) } ).then( function ( answer ) {
			badge.textContent = Number( answer.data.total ).toLocaleString();
		} ).catch( function () {} );
	} );
}() );
