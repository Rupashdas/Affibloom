/* global wp */
/**
 * The Amazon import's status blocks (F35 R35, D19; Task AM4): every 15 seconds GET /amazon-import redraws each
 * `[data-affibloom-amazon-status]` with the server's block; at completion the banner fades out and polling stops
 * unless another block (the Settings card, AM5) still shows; × on the banner posts /amazon-import/dismiss and hides it.
 */
( function () {
	'use strict';

	var EVERY = 15000;

	var blocks = function () {
		return Array.prototype.slice.call( document.querySelectorAll( '[data-affibloom-amazon-status]' ) );
	};
	if ( ! blocks().length || ! window.wp || ! wp.apiFetch ) {
		return;
	}

	var timer = null;
	var poll = function () {
		wp.apiFetch( { path: '/affibloom/v1/amazon-import' } ).then( function ( answer ) {
			var data = answer.data;
			blocks().forEach( function ( block ) {
				block.innerHTML = data.html;
			} );
			var banner = document.querySelector( '[data-amazon-import-notice]' );
			if ( data.complete && banner ) {
				banner.classList.add( 'is-leaving' );
				window.setTimeout( function () {
					banner.remove();
				}, 300 );
			}
			// Stop when it is complete and no other block is left to keep current.
			if ( data.complete && ! blocks().filter( function ( block ) {
				return ! block.closest( '[data-amazon-import-notice]' );
			} ).length ) {
				return;
			}
			timer = window.setTimeout( poll, EVERY );
		} ).catch( function () {
			timer = window.setTimeout( poll, EVERY );
		} );
	};
	timer = window.setTimeout( poll, EVERY );

	document.addEventListener( 'click', function ( event ) {
		var close = event.target.closest( '[data-amazon-import-dismiss]' );
		if ( ! close ) {
			return;
		}
		var banner = close.closest( '[data-amazon-import-notice]' );
		banner.hidden = true;
		wp.apiFetch( { path: '/affibloom/v1/amazon-import/dismiss', method: 'POST' } ).then( function () {
			banner.remove();
		} ).catch( function () {
			banner.hidden = false;
		} );
	} );
}() );
