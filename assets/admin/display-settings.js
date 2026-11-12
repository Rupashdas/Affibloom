/**
 * Settings → Display: the colour picker beside each colour value.
 *
 * The text field stays the value that is saved, because Lasso stores CSS keywords ("black", "white") as well
 * as hex, and a colour input can only hold hex. The picker shows that value and writes a hex back into the
 * text field when the editor picks one. Without this script the text field still works on its own.
 */
( function () {
	'use strict';

	/**
	 * The hex form of any CSS colour the browser understands, or null when it understands none.
	 *
	 * @param {string} value A CSS colour, such as "black", "#fff" or "rgb(0 0 0)".
	 * @return {?string} "#rrggbb", or null.
	 */
	function toHex( value ) {
		var probe = document.createElement( 'span' );
		probe.style.display = 'none';
		probe.style.color = '';
		probe.style.color = String( value || '' ).trim();
		if ( '' === probe.style.color ) {
			return null;
		}

		document.body.appendChild( probe );
		var computed = window.getComputedStyle( probe ).color;
		probe.remove();

		var parts = computed.match( /\d+(\.\d+)?/g );
		if ( ! parts || parts.length < 3 ) {
			return null;
		}

		return (
			'#' +
			parts
				.slice( 0, 3 )
				.map( function ( part ) {
					return ( '0' + Math.round( parseFloat( part ) ).toString( 16 ) ).slice( -2 );
				} )
				.join( '' )
		);
	}

	/**
	 * Keeps one picker and its text field in step.
	 *
	 * @param {HTMLInputElement} picker The colour input.
	 */
	function connect( picker ) {
		var field = document.getElementById( picker.dataset.affibloomSwatch );
		if ( ! field ) {
			return;
		}

		var show = function () {
			var hex = toHex( field.value );
			if ( hex ) {
				picker.value = hex;
			}
		};

		show();
		field.addEventListener( 'input', show );
		picker.addEventListener( 'input', function () {
			field.value = picker.value;
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var pickers = document.querySelectorAll( '[data-affibloom-swatch]' );
		Array.prototype.forEach.call( pickers, connect );
	} );
} )();
