/**
 * The phone layout of an advanced box (F5 R48, D5; Task 18C): below 767.98px every wrapper's data-layout-m-<key> is
 * copied onto data-layout-<key>, the desktop value kept in data-layout-d-<key>, and put back above it; box-layout.css
 * keys on data-layout-<key>. Runs on load, on DOMContentLoaded and when the width crosses the line. Without
 * JavaScript the desktop layout stays, as in Lasso.
 */
( function () {
	'use strict';

	var phone = window.matchMedia && window.matchMedia( '(max-width: 767.98px)' );
	if ( ! phone ) {
		return;
	}
	var keys = [ 'orientation', 'image-position', 'align', 'button-align', 'cta-width', 'image-fit' ];

	var run = function () {
		document.querySelectorAll( '[data-layout-m-orientation]' ).forEach( function ( box ) {
			keys.forEach( function ( key ) {
				var shown = 'data-layout-' + key;
				var desktop = 'data-layout-d-' + key;
				if ( ! box.hasAttribute( desktop ) ) {
					box.setAttribute( desktop, box.getAttribute( shown ) || '' );
				}
				box.setAttribute( shown, phone.matches ? box.getAttribute( 'data-layout-m-' + key ) : box.getAttribute( desktop ) );
			} );
		} );
	};

	run();
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', run );
	}
	if ( phone.addEventListener ) {
		phone.addEventListener( 'change', run );
	} else if ( phone.addListener ) {
		phone.addListener( run );
	}
} )();
