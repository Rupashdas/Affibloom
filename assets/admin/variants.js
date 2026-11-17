/**
 * The product page's Variants tab (F27, Task V1): the search narrows the rows as you type, by name, key or address in
 * any case, without a request (Lasso's url-variants.js 72-81, 548-553); Copy puts a row's address on the clipboard and
 * says "Copied!" for 1.5 seconds (D5).
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var tab = document.querySelector( '[data-variants]' );
		if ( ! tab ) {
			return;
		}
		var search = document.querySelector( '[data-variant-search]' );
		var body = tab.querySelector( '[data-variant-rows]' );
		var noMatch = tab.querySelector( '[data-variant-no-match]' );

		var filter = function () {
			var term = search ? search.value.trim().toLowerCase() : '';
			var shown = 0;
			Array.prototype.forEach.call( body.querySelectorAll( '[data-variant-row]' ), function ( row ) {
				var match = '' === term || -1 !== ( row.dataset.search || '' ).indexOf( term );
				row.hidden = ! match;
				shown += match ? 1 : 0;
			} );
			noMatch.hidden = 0 !== shown || '' === term;
		};
		if ( search ) {
			search.addEventListener( 'input', filter );
		}

		var timers = new WeakMap();
		body.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( '[data-variant-copy]' );
			if ( ! button ) {
				return;
			}
			var label = button.querySelector( '[data-copy-label]' );
			window.affibloomCopy( button.dataset.variantCopy, function () {
				var name = button.dataset.copyName || label.textContent;
				button.dataset.copyName = name;
				window.clearTimeout( timers.get( button ) );
				label.textContent = button.dataset.copied;
				timers.set( button, window.setTimeout( function () {
					label.textContent = name;
				}, 1500 ) );
			}, function () {
				window.affibloomToast( button.dataset.copyFailed, true );
			} );
		} );
	} );
}() );
