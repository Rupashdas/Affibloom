/**
 * The Copy to Clipboard field's button (F5 R36; Task 21C): a click on `.affibloom-copy-clipboard-btn` copies its
 * `data-copy-value` with the Clipboard API, else through a hidden textarea and `execCommand( 'copy' )`, as Lasso's
 * lasso-copy-clipboard.min.js does. No visible confirmation, as in Lasso. Loaded with the box's assets.
 */
( function () {
	'use strict';

	var fallback = function ( text ) {
		var area = document.createElement( 'textarea' );
		area.value = text;
		area.setAttribute( 'readonly', '' );
		area.style.position = 'absolute';
		area.style.left = '-9999px';
		document.body.appendChild( area );
		area.select();
		try {
			document.execCommand( 'copy' );
		} catch ( e ) {
			// Nothing else to try: the value stays visible beside the button.
		}
		document.body.removeChild( area );
	};

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest && event.target.closest( '.affibloom-copy-clipboard-btn' );
		if ( ! button ) {
			return;
		}
		event.preventDefault();
		var value = button.getAttribute( 'data-copy-value' ) || '';
		if ( window.navigator.clipboard && window.navigator.clipboard.writeText ) {
			window.navigator.clipboard.writeText( value ).catch( function () {
				fallback( value );
			} );
		} else {
			fallback( value );
		}
	} );
} )();
