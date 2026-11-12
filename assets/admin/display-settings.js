/**
 * Settings → Display: the rail's tabs, the colour pickers, the density chips and steppers, the layout rows and
 * the phone layout behind the device toggle, the summaries, the Disclosure mirror, Save enabled only after a
 * change, and Reset Settings with Lasso's confirmation.
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
			changed( box );
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

		panel.querySelectorAll( '.affibloom-options input[data-density-field]' ).forEach( function ( radio ) {
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

	/**
	 * An image position in the vocabulary of an orientation (Lasso's coerce_display_layout_image_position).
	 *
	 * @param {string} position    left, right, top or bottom.
	 * @param {string} orientation side-by-side or stacked.
	 * @return {string} The position the orientation allows.
	 */
	function coerce( position, orientation ) {
		if ( 'side-by-side' === orientation ) {
			return 'top' === position || 'left' === position ? 'left' : 'right';
		}
		return 'right' === position || 'bottom' === position ? 'bottom' : 'top';
	}

	/**
	 * The Layout accordion: desktop edits go into the radios, which the form posts; with the device toggle on
	 * Mobile, the same rows show and edit the phone layout, whose overrides go into the hidden phone fields.
	 * Phones start from desktop with the buttons centred (Lasso's get_display_layout_mobile_base).
	 *
	 * @param {HTMLElement}  panel  The Layout & spacing panel.
	 * @param {?HTMLElement} toggle The toolbar's device toggle.
	 */
	function layoutRows( panel, toggle ) {
		var box = panel.querySelector( '[data-layout]' );
		if ( ! box ) {
			return;
		}

		var PHONE_KEYS = [ 'orientation', 'imagePosition', 'align', 'buttonAlign', 'ctaWidth', 'imageFit' ];
		var KEYS = PHONE_KEYS.concat( [ 'mobileOverflow' ] );
		var isGrid = '1' === box.dataset.grid;
		var isList = '1' === box.dataset.list;
		var form = box.closest( 'form' );
		var note = box.querySelector( '[data-phone-note]' );
		var noteText = box.querySelector( '[data-phone-note-text]' );
		var reset = box.querySelector( '[data-phone-reset]' );
		var summary = panel.querySelector( '[data-layout-summary]' );
		var mode = 'desktop';
		var desktop = { orientation: box.dataset.desktopOrientation };

		var radios = function ( key ) {
			return Array.prototype.slice.call( box.querySelectorAll( 'input[data-layout-field="' + key + '"]' ) );
		};
		var phoneField = function ( key ) {
			return box.querySelector( '[data-phone-field="' + key + '"]' );
		};
		var overrides = function () {
			var set = {};
			PHONE_KEYS.forEach( function ( key ) {
				var field = phoneField( key );
				if ( field && ! field.disabled ) {
					set[ key ] = field.value;
				}
			} );
			return set;
		};
		var phoneLayout = function () {
			var values = {};
			PHONE_KEYS.forEach( function ( key ) {
				values[ key ] = desktop[ key ];
			} );
			values.buttonAlign = 'center';
			if ( isGrid ) {
				values.orientation = 'stacked';
			}
			Object.assign( values, overrides() );
			values.imagePosition = coerce( values.imagePosition, values.orientation );
			return values;
		};

		KEYS.forEach( function ( key ) {
			radios( key ).forEach( function ( radio ) {
				if ( radio.checked ) {
					desktop[ key ] = radio.value;
				}
			} );
		} );

		// Shows a layout in the rows: the options checked, the image-position pair, and the rows and phone note
		// that belong to the current device.
		var show = function () {
			var values = 'mobile' === mode ? Object.assign( {}, desktop, phoneLayout() ) : desktop;
			var columns = box.querySelector( 'input[data-columns]:checked' );
			var overflow = box.querySelector( '[data-layout-row="mobileOverflow"]' );
			var custom = Object.keys( overrides() ).length > 0;
			var parts = [];

			KEYS.forEach( function ( key ) {
				radios( key ).forEach( function ( radio ) {
					radio.checked = radio.value === values[ key ];
				} );
			} );
			box.querySelectorAll( '[data-layout-row="imagePosition"] [data-orientation]' ).forEach( function ( option ) {
				option.hidden = option.dataset.orientation !== values.orientation;
			} );
			if ( overflow ) {
				overflow.hidden = ! ( 'mobile' === mode && isList );
			}

			note.hidden = 'mobile' !== mode;
			reset.hidden = ! custom;
			noteText.textContent = custom ? noteText.dataset.custom : noteText.dataset.inherit;

			if ( isGrid && columns ) {
				parts.push( format( summary.dataset.columnsFormat, [ parseInt( columns.value, 10 ) ] ) );
			}
			parts.push( 'side-by-side' === values.orientation ? summary.dataset.side : summary.dataset.stacked );
			parts.push( summary.dataset.imageFormat.replace( '%s', values.imagePosition ) );
			summary.textContent = parts.join( ' · ' );
		};

		box.addEventListener( 'change', function ( event ) {
			var key = event.target.dataset.layoutField;
			var field;
			var position;
			if ( ! key ) {
				show(); // the column count only changes the summary
				return;
			}

			if ( 'mobile' === mode && PHONE_KEYS.indexOf( key ) !== -1 ) {
				field = phoneField( key );
				field.disabled = false;
				field.value = event.target.value;
				if ( 'orientation' === key ) {
					position = phoneField( 'imagePosition' );
					position.value = coerce( position.disabled ? phoneLayout().imagePosition : position.value, event.target.value );
					position.disabled = false;
				}
			} else {
				desktop[ key ] = event.target.value;
				if ( 'orientation' === key ) {
					desktop.imagePosition = coerce( desktop.imagePosition, desktop.orientation );
				}
			}
			show();
		} );

		reset.addEventListener( 'click', function () {
			PHONE_KEYS.forEach( function ( key ) {
				phoneField( key ).disabled = true;
			} );
			show();
			changed( box );
		} );

		// The radios carry the desktop layout to the server, so they show it again before the form is sent.
		form.addEventListener( 'submit', function () {
			mode = 'desktop';
			show();
		} );

		if ( toggle ) {
			toggle.hidden = false;
			toggle.querySelectorAll( '[data-device]' ).forEach( function ( button ) {
				button.addEventListener( 'click', function () {
					mode = button.dataset.device;
					toggle.querySelectorAll( '[data-device]' ).forEach( function ( other ) {
						other.classList.toggle( 'is-active', other === button );
						other.setAttribute( 'aria-pressed', other === button ? 'true' : 'false' );
					} );
					show();
				} );
			} );
		}

		show();
	}

	/**
	 * Lasso keeps Save Changes disabled until something on the screen changes. Steppers, chips and the phone
	 * reset are clicks rather than input events, so they announce themselves with an `affibloom:change` event.
	 *
	 * @param {HTMLFormElement} form The Display form.
	 */
	function saveWhenChanged( form ) {
		var save = document.getElementById( 'affibloom-settings-save' );
		var enable = function () {
			save.disabled = false;
		};
		if ( ! save ) {
			return;
		}

		save.disabled = true;
		form.addEventListener( 'input', enable );
		form.addEventListener( 'change', enable );
		form.addEventListener( 'affibloom:change', enable );
	}

	/**
	 * Tells the form something changed that was not an input event.
	 *
	 * @param {HTMLElement} from Any element inside the form.
	 */
	function changed( from ) {
		var form = from.closest( 'form' );
		if ( form ) {
			form.dispatchEvent( new Event( 'affibloom:change' ) );
		}
	}

	/**
	 * A switch that shows and changes another field: the Elements panel's Disclosure row mirrors the
	 * Disclosure & behavior toggle for the type being edited (requirements B5).
	 *
	 * @param {HTMLInputElement} mirrorSwitch The mirroring switch.
	 */
	function mirror( mirrorSwitch ) {
		var target = document.getElementById( mirrorSwitch.dataset.mirror );
		if ( ! target ) {
			return;
		}

		// Each side passes a change on only when the other differs, so the two never bounce events forever, and
		// the Elements summary hears about a change made on the Disclosure & behavior side too.
		var follow = function ( from, to ) {
			from.addEventListener( 'change', function () {
				if ( to.checked !== from.checked ) {
					to.checked = from.checked;
					to.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				}
			} );
		};

		mirrorSwitch.disabled = false;
		follow( mirrorSwitch, target );
		follow( target, mirrorSwitch );
	}

	/**
	 * Reset Settings, with Lasso's confirmation. The button is shown only with this script, so a reset is never
	 * sent without the question being asked.
	 *
	 * @param {HTMLFormElement} form The reset form.
	 */
	function resetSettings( form ) {
		var button = document.querySelector( '[data-reset-button]' );
		if ( button ) {
			button.hidden = false;
		}
		form.addEventListener( 'submit', function ( event ) {
			if ( ! window.confirm( form.dataset.confirm ) ) {
				event.preventDefault();
			}
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var rail = document.querySelector( '.affibloom-split__rail' );
		var layout = document.querySelector( '[data-rail-panel="layout"]' );

		var form = document.querySelector( 'form.affibloom-settings' );
		var resetForm = document.querySelector( '[data-reset-form]' );

		Array.prototype.forEach.call( document.querySelectorAll( '[data-affibloom-swatch]' ), connect );
		Array.prototype.forEach.call( document.querySelectorAll( '[data-mirror]' ), mirror );
		if ( form ) {
			saveWhenChanged( form );
		}
		if ( resetForm ) {
			resetSettings( resetForm );
		}
		if ( rail ) {
			tabs( rail );
		}
		if ( layout ) {
			density( layout );
			elements( layout );
			layoutRows( layout, document.querySelector( '[data-device-toggle]' ) );
		}
	} );
} )();
