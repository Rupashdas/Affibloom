/**
 * Settings → Display: the rail's tabs, the colour pickers, the density chips and steppers, and the summaries.
 *
 * Every control posts its own field, so the form saves without this script; the script keeps the controls in
 * step the way Lasso's does (admin/assets/js/settings.js): a preset chip fills the steppers, a stepper moves
 * by its step and clamps to its range, and any edit turns the preset into Custom.
 *
 * Colours: the text field stays the value that is saved, because Lasso stores CSS keywords as well as hex and a
 * colour input can only hold hex. The picker shows that value and writes a hex back when the editor picks one.
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
	 * Keeps one colour picker and its text field in step.
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

	/**
	 * Fills a "%1$d of %2$d" style format with numbers.
	 *
	 * @param {string}        template The translated format.
	 * @param {Array<number>} values   The numbers, in order.
	 * @return {string} The text.
	 */
	function format( template, values ) {
		var next = 0;
		return template.replace( /%(\d+)\$d|%d/g, function ( match, position ) {
			return String( values[ position ? parseInt( position, 10 ) - 1 : next++ ] );
		} );
	}

	/**
	 * The rail's tabs, with arrow keys as in the WAI-ARIA tabs pattern. Without this script both panels show.
	 *
	 * @param {HTMLElement} rail The rail.
	 */
	function tabs( rail ) {
		var buttons = Array.prototype.slice.call( rail.querySelectorAll( '[data-rail-tab]' ) );
		var field = rail.querySelector( '[data-rail-panel-field]' );

		var open = function ( button, focus ) {
			buttons.forEach( function ( other ) {
				var active = other === button;
				var panel = document.getElementById( other.getAttribute( 'aria-controls' ) );
				other.classList.toggle( 'is-active', active );
				other.setAttribute( 'aria-selected', active ? 'true' : 'false' );
				other.tabIndex = active ? 0 : -1;
				if ( panel ) {
					panel.classList.toggle( 'is-active', active );
				}
			} );
			if ( field ) {
				field.value = button.dataset.railTab;
			}
			// Switching display type keeps the open tab, as it does in Lasso.
			document.querySelectorAll( '.affibloom-segment__item' ).forEach( function ( link ) {
				var url = new URL( link.href );
				url.searchParams.set( 'panel', button.dataset.railTab );
				link.href = url.toString();
			} );
			if ( focus ) {
				button.focus();
			}
		};

		buttons.forEach( function ( button, index ) {
			button.addEventListener( 'click', function () {
				open( button, false );
			} );
			button.addEventListener( 'keydown', function ( event ) {
				var step = { ArrowRight: 1, ArrowLeft: -1 }[ event.key ];
				if ( step ) {
					event.preventDefault();
					open( buttons[ ( index + step + buttons.length ) % buttons.length ], true );
				}
			} );
		} );

		rail.classList.add( 'has-tabs' );
	}

	/**
	 * The density chips, steppers and line clamps, and the summaries that report them.
	 *
	 * @param {HTMLElement} panel The Layout & spacing panel.
	 */
	function density( panel ) {
		var box = panel.querySelector( '.affibloom-density' );
		if ( ! box ) {
			return;
		}

		var presets = JSON.parse( box.dataset.presets );
		var hints = JSON.parse( box.dataset.hints );
		var base = box.querySelector( '[data-density-base]' );
		var hint = box.querySelector( '[data-density-hint]' );
		var chips = Array.prototype.slice.call( box.querySelectorAll( 'input[name="display[density][preset]"]' ) );

		var stepper = function ( field ) {
			return panel.querySelector( '.affibloom-stepper[data-density-field="' + field + '"]' );
		};
		var read = function ( field ) {
			var control = stepper( field );
			var checked = panel.querySelector( 'input[data-density-field="' + field + '"]:checked' );
			if ( control ) {
				return parseInt( control.querySelector( 'input' ).value, 10 );
			}
			return checked ? parseInt( checked.value, 10 ) : 0;
		};
		var write = function ( field, value ) {
			var control = stepper( field );
			if ( control ) {
				control.querySelector( 'input' ).value = value;
				control.querySelector( 'output' ).textContent = value;
				return;
			}
			panel.querySelectorAll( 'input[data-density-field="' + field + '"]' ).forEach( function ( radio ) {
				radio.checked = parseInt( radio.value, 10 ) === value;
			} );
		};
		var summarise = function () {
			panel.querySelectorAll( '[data-group-summary]' ).forEach( function ( summary ) {
				summary.textContent = format( summary.dataset.format, summary.dataset.groupSummary.split( ',' ).map( read ) );
			} );
		};
		// Any edit makes the density Custom. The form keeps the preset the edit started from, so the values the
		// editor did not touch are kept exactly (a preset can exceed a stepper's range, as spacious's image does).
		var customise = function () {
			chips.forEach( function ( chip ) {
				if ( chip.checked ) {
					base.value = chip.value;
					chip.checked = false;
				}
			} );
			hint.textContent = hints.custom;
			summarise();
		};

		chips.forEach( function ( chip ) {
			chip.addEventListener( 'change', function () {
				Object.keys( presets[ chip.value ] ).forEach( function ( field ) {
					write( field, presets[ chip.value ][ field ] );
				} );
				base.value = chip.value;
				hint.textContent = hints[ chip.value ];
				summarise();
			} );
		} );

		panel.querySelectorAll( '.affibloom-stepper' ).forEach( function ( control ) {
			var min = parseInt( control.dataset.min, 10 );
			var max = parseInt( control.dataset.max, 10 );
			var step = parseInt( control.dataset.step, 10 );
			control.querySelectorAll( '[data-step-dir]' ).forEach( function ( button ) {
				button.addEventListener( 'click', function () {
					var current = parseInt( control.querySelector( 'input' ).value, 10 );
					var next = ( isNaN( current ) ? min : current ) + step * parseInt( button.dataset.stepDir, 10 );
					write( control.dataset.densityField, Math.min( max, Math.max( min, next ) ) );
					customise();
				} );
			} );
		} );

		panel.querySelectorAll( '.affibloom-clamp input' ).forEach( function ( radio ) {
			radio.addEventListener( 'change', customise );
		} );
	}

	/**
	 * The Elements summary, "n of m visible".
	 *
	 * @param {HTMLElement} panel The Layout & spacing panel.
	 */
	function elements( panel ) {
		var summary = panel.querySelector( '[data-elements-summary]' );
		var toggles = Array.prototype.slice.call( panel.querySelectorAll( 'input[data-element]' ) );
		if ( ! summary ) {
			return;
		}

		toggles.forEach( function ( toggle ) {
			toggle.addEventListener( 'change', function () {
				var shown = toggles.filter( function ( other ) {
					return other.checked;
				} ).length;
				summary.textContent = format( summary.dataset.format, [ shown, parseInt( summary.dataset.total, 10 ) ] );
			} );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var rail = document.querySelector( '.affibloom-split__rail' );
		var layout = document.querySelector( '[data-rail-panel="layout"]' );

		Array.prototype.forEach.call( document.querySelectorAll( '[data-affibloom-swatch]' ), connect );
		if ( rail ) {
			tabs( rail );
		}
		if ( layout ) {
			density( layout );
			elements( layout );
		}
	} );
} )();
