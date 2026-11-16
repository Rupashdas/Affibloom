/**
 * Settings → Display: the rail's tabs, the colour pickers, the density chips and steppers, the layout rows and
 * the phone layout behind the device toggle, the summaries, the Disclosure mirror, Save enabled only after a
 * change (see admin.js), and Reset Settings with Lasso's confirmation.
 *
 * Single, Grid and List switch in place, as in Lasso (settings.js 2073-2087; deviation 144): each type's part of the
 * rail (theme, Layout & spacing, colours and labels) is printed for all three, the others in a <template>. Choosing a
 * type keeps the shown part, with its edits, aside and puts the chosen one in; Save Changes sends every type that was
 * shown. Below 992px the preview stays on Mobile, as Lasso's does.
 *
 * Every control posts its own field, so the form saves without this script; the script keeps the controls in
 * step the way Lasso's does (admin/assets/js/settings.js): a preset chip fills the steppers, a stepper moves
 * by its step and clamps to its range, and any edit turns the preset into Custom.
 *
 * Colours: the text field stays the value that is saved, because Lasso stores CSS keywords, rgb()/rgba()/hsl() and
 * 8-digit hex as well as 6-digit hex, and a colour input can only hold #rrggbb. The picker shows the nearest opaque
 * colour of that value and writes a hex back only when the editor picks one (task 2X3).
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
		var chips = Array.prototype.slice.call( box.querySelectorAll( 'input[name$="[density][preset]"]' ) );

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

		// What the form sends for the layout: the desktop rows, whichever device the radios show (displayTypes reads
		// it, so the save and the leave guard never take the phone view for a change).
		box.affibloomDesktopLayout = function () {
			var sent = {};
			KEYS.forEach( function ( key ) {
				var radio = radios( key )[ 0 ];
				if ( radio && undefined !== desktop[ key ] ) {
					sent[ radio.name ] = desktop[ key ];
				}
			} );
			return sent;
		};

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
		// A type shown after load (displayTypes) was printed with the stored value; the shared switch may have moved.
		if ( mirrorSwitch.checked !== target.checked ) {
			mirrorSwitch.checked = target.checked;
			mirrorSwitch.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		}
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

	// The display types' parts that are not in the page at the moment (displayTypes), for iconMenus().
	var asideRegions = function () {
		return [];
	};

	/**
	 * Every icon picker menu of the screen: in the page, in the other types' templates and in the types set aside.
	 *
	 * @return {Array<HTMLElement>} The menus.
	 */
	function iconMenus() {
		var roots = [ document ].concat( asideRegions() );
		Array.prototype.forEach.call( document.querySelectorAll( 'template[data-display-type]' ), function ( template ) {
			roots.push( template.content );
		} );
		return roots.reduce( function ( menus, root ) {
			return menus.concat( Array.prototype.slice.call( root.querySelectorAll( '.affibloom-icon-menu' ) ) );
		}, [] );
	}

	/**
	 * The "Button icons" card (F28, Task 14F): "Choose image" opens WordPress's media library for one SVG, PNG or WebP
	 * image, which fills the hidden attachment and the preview; Remove asks first, as the reset does. Add icon and
	 * Remove go over REST and stay on the page (deviation 143).
	 *
	 * @param {HTMLElement} card The card.
	 */
	function iconSet( card ) {
		var add = card.querySelector( '[data-icon-add]' );
		var choose = card.querySelector( '[data-icon-choose]' );

		if ( add && choose && window.affibloomMedia && window.affibloomMedia.available() ) {
			choose.addEventListener( 'click', function () {
				// admin.js's media frame, with the icon formats only.
				window.affibloomMedia.pick( {
					title: choose.textContent.trim(),
					button: choose.textContent.trim(),
					types: [ 'image/svg+xml', 'image/png', 'image/webp' ],
				}, function ( image ) {
					var preview = add.querySelector( '[data-icon-preview]' );
					add.querySelector( '[data-icon-attachment]' ).value = image.id;
					preview.src = image.url;
					add.querySelector( '[data-icon-preview-tile]' ).hidden = false;
				} );
			} );
		}

		var list = card.querySelector( '.affibloom-icon-set__list' );
		var busy = false;
		// Both answer with the toast's words; a request that did not go through shows the card's failure text.
		var ask = function ( options, done ) {
			busy = true;
			window.wp.apiFetch( options ).then( function ( answer ) {
				window.affibloomToast( answer.data.message, !! answer.data.failed );
				if ( ! answer.data.failed ) {
					done( answer.data );
				}
			} ).catch( function () {
				window.affibloomToast( card.dataset.failedText, true );
			} ).finally( function () {
				busy = false;
			} );
		};

		// Add icon (F28): the new icon joins the card's list and every picker on the page, unticked.
		if ( add ) {
			add.addEventListener( 'submit', function ( event ) {
				event.preventDefault();
				if ( busy ) {
					return;
				}
				ask( { path: '/affibloom/v1/icons', method: 'POST', body: new FormData( add ) }, function ( data ) {
					if ( data.row ) {
						list.insertAdjacentHTML( 'beforeend', data.row );
					}
					if ( data.item ) {
						iconMenus().forEach( function ( menu ) {
							var unknown = menu.querySelector( '[data-unknown]' );
							var holder = document.createElement( 'span' );
							holder.innerHTML = data.item;
							menu.insertBefore( holder.firstElementChild, unknown );
						} );
					}
					add.reset();
					add.querySelector( '[data-icon-attachment]' ).value = '0';
					add.querySelector( '[data-icon-preview-tile]' ).hidden = true;
				} );
			} );
		}

		// Remove, after Lasso's kind of question: the icon leaves the list and the pickers that have not chosen it (a
		// text that uses it keeps it until another is chosen, as after a reload it shows as unknown).
		card.addEventListener( 'click', function ( event ) {
			var remove = event.target.closest( '[data-icon-remove]' );
			if ( ! remove || busy || ! window.confirm( remove.dataset.confirm ) ) {
				return;
			}
			var css = remove.dataset.iconRemove;
			ask( { path: '/affibloom/v1/icons/' + encodeURIComponent( css ), method: 'DELETE' }, function () {
				var row = remove.closest( '.affibloom-icon-set__item' );
				if ( row ) {
					row.remove();
				}
				iconMenus().forEach( function ( menu ) {
					Array.prototype.forEach.call( menu.querySelectorAll( '[data-icon]' ), function ( item ) {
						if ( item.dataset.icon === css && 'true' !== item.getAttribute( 'aria-checked' ) ) {
							item.remove();
						}
					} );
				} );
			} );
		} );
	}

	/**
	 * The live preview (F5 R94-R99, F16 AC2; Task 10D): the form's unsaved values go to the preview route, which draws
	 * Lasso's sample box; it is written into a frame of its own with the box's stylesheets, the unsaved :root block and
	 * the phone script. Every change redraws it (the latest answer wins); links are inert; the device toggle sizes it.
	 *
	 * @param {HTMLFormElement} form   The Display form.
	 * @param {HTMLElement}     frame  The preview frame.
	 * @param {HTMLElement|null} toggle The device toggle.
	 */
	function preview( form, frame, toggle ) {
		var latest = 0;
		var timer = null;

		var write = function ( answer ) {
			if ( ! frame.querySelector( 'iframe' ) ) {
				frame.textContent = '';
			}
			window.affibloomBoxFrame( frame, answer, frame.dataset.previewTitle, function () {
				frame.classList.remove( 'is-loading' );
			} );
		};

		var draw = function () {
			var ask = ++latest;
			var body = new window.FormData( form );
			// The form's own nonce is Save's; REST would read a `_wpnonce` field as its nonce and refuse the request.
			body.delete( '_wpnonce' );
			frame.classList.add( 'is-loading' );
			window.wp.apiFetch( { path: '/affibloom/v1/display-preview', method: 'POST', body: body } ).then( function ( answer ) {
				if ( ask === latest ) {
					write( answer );
				}
			} ).catch( function () {
				if ( ask !== latest ) {
					return;
				}
				frame.classList.remove( 'is-loading' );
				if ( window.affibloomToast ) {
					window.affibloomToast( frame.dataset.previewError, true );
				}
			} );
		};

		var soon = function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( draw, 250 );
		};

		form.addEventListener( 'input', soon );
		form.addEventListener( 'change', soon );
		form.addEventListener( 'affibloom:type-changed', soon );
		if ( toggle ) {
			toggle.addEventListener( 'click', function ( event ) {
				var button = event.target.closest( '[data-device]' );
				if ( button ) {
					frame.dataset.device = button.dataset.device;
					soon();
				}
			} );
		}
		draw();
	}

	/**
	 * Sets up one display type's part of the rail: the colour pickers, the Disclosure mirror, and in the Layout &
	 * spacing part the density, the Elements summary and the layout rows.
	 *
	 * @param {HTMLElement}  region The part.
	 * @param {?HTMLElement} toggle The toolbar's device toggle.
	 */
	function setUpRegion( region, toggle ) {
		Array.prototype.forEach.call( region.querySelectorAll( '[data-affibloom-swatch]' ), connect );
		Array.prototype.forEach.call( region.querySelectorAll( '[data-mirror]' ), mirror );
		if ( 'layout' === region.dataset.typeRegion ) {
			density( region );
			elements( region );
			layoutRows( region, toggle );
		}
	}

	/**
	 * Single, Grid and List without a reload (deviation 144). A part shown once stays in memory with its edits and its
	 * scripts; one never shown is made from its <template> and set up. The address follows the type, so a reload or
	 * a later visit opens it again.
	 *
	 * @param {HTMLFormElement} form   The Display form.
	 * @param {?HTMLElement}    toggle The toolbar's device toggle.
	 */
	function displayTypes( form, toggle ) {
		var links = Array.prototype.slice.call( document.querySelectorAll( '.affibloom-segment__item[data-display-type]' ) );
		var fields = Array.prototype.slice.call( document.querySelectorAll( '[data-display-type-field]' ) );
		var current = fields.length ? fields[ 0 ].value : '';
		// Each type's parts by region, once shown: { grid: { theme: el, layout: el, colors: el } }.
		var shown = {};
		shown[ current ] = {};
		Array.prototype.forEach.call( form.querySelectorAll( '.affibloom-type-region[data-display-type="' + current + '"]' ), function ( region ) {
			shown[ current ][ region.dataset.typeRegion ] = region;
		} );

		asideRegions = function () {
			var aside = [];
			Object.keys( shown ).forEach( function ( type ) {
				if ( type !== current ) {
					Object.keys( shown[ type ] ).forEach( function ( key ) {
						aside.push( shown[ type ][ key ] );
					} );
				}
			} );
			return aside;
		};

		// What the form sends: its own fields (the type shown) and the parts of the other types that were shown, each
		// type's layout as its desktop rows (layoutRows), even while the radios show the phone layout.
		form.affibloomFields = function () {
			var data = new window.FormData( form );
			Object.keys( shown ).forEach( function ( type ) {
				Object.keys( shown[ type ] ).forEach( function ( key ) {
					var region = shown[ type ][ key ];
					var box = region.querySelector( '[data-layout]' );
					if ( type !== current ) {
						window.affibloomFieldEntries( region ).forEach( function ( entry ) {
							data.append( entry[ 0 ], entry[ 1 ] );
						} );
					}
					if ( box && box.affibloomDesktopLayout ) {
						var desktop = box.affibloomDesktopLayout();
						Object.keys( desktop ).forEach( function ( name ) {
							data.set( name, desktop[ name ] );
						} );
					}
				} );
			} );
			return data;
		};

		var show = function ( type ) {
			var fresh = [];
			if ( type === current || ! shown[ current ] ) {
				return;
			}
			if ( ! shown[ type ] ) {
				shown[ type ] = {};
				Array.prototype.forEach.call( document.querySelectorAll( 'template[data-display-type="' + type + '"]' ), function ( template ) {
					shown[ type ][ template.dataset.typeRegion ] = template.content.firstElementChild.cloneNode( true );
					fresh.push( shown[ type ][ template.dataset.typeRegion ] );
				} );
			}
			Object.keys( shown[ current ] ).forEach( function ( key ) {
				var leaving = shown[ current ][ key ];
				if ( shown[ type ][ key ] ) {
					leaving.replaceWith( shown[ type ][ key ] );
				}
			} );
			current = type;

			// A part shown for the first time: its scripts, its help tips and icon pickers, and the phone layout if the
			// toolbar is on Mobile; the leave guard takes its values as stored, not as a change.
			fresh.forEach( function ( region ) {
				setUpRegion( region, toggle );
				region.dispatchEvent( new CustomEvent( 'affibloom:enhance', { bubbles: true } ) );
				form.dispatchEvent( new CustomEvent( 'affibloom:fields-added', { detail: window.affibloomFieldEntries( region ) } ) );
			} );
			var mobile = toggle && toggle.querySelector( '[data-device="mobile"].is-active' );
			if ( fresh.length && mobile ) {
				mobile.click();
			}

			fields.forEach( function ( field ) {
				field.value = type;
			} );
			links.forEach( function ( link ) {
				var active = link.dataset.displayType === type;
				link.classList.toggle( 'is-active', active );
				if ( active ) {
					link.setAttribute( 'aria-current', 'page' );
				} else {
					link.removeAttribute( 'aria-current' );
				}
			} );
			if ( window.history && window.history.replaceState ) {
				var address = new URL( window.location.href );
				address.searchParams.set( 'type', type );
				window.history.replaceState( window.history.state, '', address.toString() );
			}
			// The preview draws the type now shown.
			form.dispatchEvent( new Event( 'affibloom:type-changed' ) );
		};

		links.forEach( function ( link ) {
			link.addEventListener( 'click', function ( event ) {
				if ( event.ctrlKey || event.metaKey || event.shiftKey || event.button ) {
					return; // a new tab opens the type's own address
				}
				event.preventDefault();
				show( link.dataset.displayType );
			} );
		} );
	}

	/**
	 * Below 992px the preview stays on Mobile and Desktop does nothing, as in Lasso (settings.js 1276-1300): a narrow
	 * window cannot show the 1280px desktop frame.
	 *
	 * @param {?HTMLElement} toggle The toolbar's device toggle.
	 */
	function narrowViewport( toggle ) {
		var desktop = toggle && toggle.querySelector( '[data-device="desktop"]' );
		var mobile = toggle && toggle.querySelector( '[data-device="mobile"]' );
		if ( ! desktop || ! mobile || ! window.matchMedia ) {
			return;
		}
		var narrow = window.matchMedia( '(max-width: 991.98px)' );
		var follow = function () {
			desktop.disabled = narrow.matches;
			if ( narrow.matches && ! mobile.classList.contains( 'is-active' ) ) {
				mobile.click();
			}
		};
		follow();
		if ( narrow.addEventListener ) {
			narrow.addEventListener( 'change', follow );
		}
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var rail = document.querySelector( '.affibloom-split__rail' );
		var icons = document.querySelector( '.affibloom-icon-set' );
		var toggle = document.querySelector( '[data-device-toggle]' );
		var form = rail ? rail.closest( 'form' ) : null;
		if ( icons && window.wp && window.wp.apiFetch ) {
			iconSet( icons );
		}

		var resetForm = document.querySelector( '[data-reset-form]' );

		// The shared rows (Disclosure & behavior) and the type shown; the other types are set up when first shown.
		Array.prototype.forEach.call( document.querySelectorAll( '[data-affibloom-swatch]' ), connect );
		Array.prototype.forEach.call( document.querySelectorAll( '[data-mirror]' ), mirror );
		if ( resetForm ) {
			resetSettings( resetForm );
		}
		if ( rail ) {
			tabs( rail );
		}
		var layout = document.querySelector( '.affibloom-type-region[data-type-region="layout"]' );
		if ( layout ) {
			density( layout );
			elements( layout );
			layoutRows( layout, toggle );
		}
		if ( form ) {
			displayTypes( form, toggle );
		}
		var frame = document.querySelector( '[data-preview-frame]' );
		if ( frame && frame.closest( 'form' ) && window.wp && window.wp.apiFetch ) {
			preview( frame.closest( 'form' ), frame, toggle );
		} else if ( frame ) {
			// The frame is printed loading; with nothing to draw it, the placeholder shows.
			frame.classList.remove( 'is-loading' );
		}
		narrowViewport( toggle );
	} );
} )();
