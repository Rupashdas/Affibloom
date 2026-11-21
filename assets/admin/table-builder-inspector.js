/* global wp */
/**
 * The table builder's selection and inspector (deviation 151; F25 R21, R23, R31, R33, R35).
 *
 * - Selecting: a product by its name, its header cell or its badge; a field by its card (every product's card of that
 *   field is marked, as a field is the same for all); a row or column by its gutter. Escape or the panel's × clears it.
 *   The selection outlives a redraw: it is found again by its ids.
 * - The panel shows what the selection can change. A product: its badge, an icon from the library and words, saved when
 *   the words box is left or an icon is chosen (PATCH …/products/{id}; the badge on the canvas is drawn here, as the
 *   answer has no canvas). A field: Remove from every product; for a button, the selected product's text (icon and
 *   words) and address, saved with "Save button" (PUT …/values, `{"button_text", "url"}`; the Primary's address is the
 *   product's link, read only). A row or column: Remove.
 * - An icon box (`[data-icon-input]`) is a button that opens the icon picker (icon-popover.js) and a box for the words;
 *   the stored text is F28's `<span class="icon"></span> words`, built here and never typed.
 */
( function () {
	'use strict';

	var tb = window.affibloomTableBuilder;
	if ( ! tb || ! tb.id ) {
		return;
	}
	var page = tb.page;
	var region = tb.region;
	var panel = page.querySelector( '[data-table-inspector]' );
	if ( ! panel ) {
		return;
	}
	// The library; an icon added from a picker is pushed into it, so every box has it.
	var icons = JSON.parse( panel.getAttribute( 'data-icons' ) || '[]' );
	var title = panel.querySelector( '[data-inspector-title]' );
	var clear = panel.querySelector( '[data-inspector-clear]' );
	var parts = {};
	panel.querySelectorAll( '[data-inspector-part]' ).forEach( function ( part ) {
		parts[ part.getAttribute( 'data-inspector-part' ) ] = part;
	} );
	var badgeNone = panel.querySelector( '[data-inspector-badge-none]' );
	var unknownIcon = panel.querySelector( '[data-inspector-unknown-icon]' );
	var selected = null;

	var labels = {
		dialog: panel.getAttribute( 'data-icon-dialog' ),
		search: panel.getAttribute( 'data-icon-search' ),
		none: panel.getAttribute( 'data-no-icon' ),
		empty: panel.getAttribute( 'data-icon-empty' ),
		add: panel.getAttribute( 'data-icon-add' ),
		media: panel.getAttribute( 'data-icon-media' ),
		mediaButton: panel.getAttribute( 'data-icon-media-button' ),
		failed: panel.getAttribute( 'data-icon-failed' ),
	};
	var format = function ( words, values ) {
		values.forEach( function ( value, index ) {
			words = words.replace( '%' + ( index + 1 ) + '$s', value ).replace( '%s', value );
		} );
		return words;
	};

	// F28's stored text: an empty span with the icon's class, a space, the words (IconText).
	var SHAPE = /^<span class="([^"<>]+)"><\/span> ?([^<>]*)$/;
	var split = function ( value ) {
		value = value || '';
		if ( -1 === value.indexOf( '<' ) && -1 === value.indexOf( '>' ) ) {
			return { icon: '', words: value };
		}
		var match = SHAPE.exec( value );
		if ( match ) {
			return { icon: match[ 1 ], words: match[ 2 ] };
		}
		var scratch = document.createElement( 'div' );
		scratch.innerHTML = value;
		return { icon: '', words: scratch.textContent.trim() };
	};
	var compose = function ( icon, words ) {
		if ( '' === icon ) {
			return words;
		}
		return '<span class="' + icon + '"></span>' + ( '' === words ? '' : ' ' + words );
	};
	var iconOf = function ( cssClass ) {
		return icons.filter( function ( icon ) {
			return icon.class === cssClass;
		} )[ 0 ] || null;
	};
	// An icon as the canvas draws it: its picture, or a warning for a class the library does not have.
	var picture = function ( cssClass, size ) {
		var icon = iconOf( cssClass );
		if ( icon && icon.src && ! icon.missing ) {
			var img = document.createElement( 'img' );
			img.className = 'affibloom-table-icon';
			img.src = icon.src;
			img.alt = '';
			img.width = size;
			img.height = size;
			return img;
		}
		var warning = unknownIcon.content.firstElementChild.cloneNode( true );
		warning.title = cssClass;
		return warning;
	};

	// An icon box: its chosen class lives on the box; the button shows it and opens the picker.
	var IconBox = function ( box, chosen ) {
		var button = box.querySelector( '[data-icon-choose]' );
		var glyph = box.querySelector( '[data-icon-glyph]' );
		var words = box.querySelector( '[data-icon-words]' );
		var self = this;
		this.words = words;
		this.icon = '';
		this.set = function ( value ) {
			var text = split( value );
			self.show( text.icon );
			words.value = text.words;
		};
		this.show = function ( cssClass ) {
			self.icon = cssClass;
			glyph.innerHTML = '';
			glyph.classList.toggle( 'is-none', '' === cssClass );
			if ( '' !== cssClass ) {
				glyph.appendChild( picture( cssClass, 18 ) );
			}
			var icon = iconOf( cssClass );
			var name = '' === cssClass ? labels.none : ( icon ? icon.name : cssClass );
			button.setAttribute( 'aria-label', format( button.getAttribute( 'data-label-format' ), [ button.getAttribute( 'data-label' ), name ] ) );
			button.title = name;
		};
		this.value = function () {
			return compose( self.icon, words.value.trim() );
		};
		button.addEventListener( 'click', function () {
			window.affibloomIconPopover.open( {
				anchor: button,
				icons: icons,
				current: self.icon,
				labels: labels,
				canAdd: '1' === panel.getAttribute( 'data-can-add-icon' ),
				choose: function ( cssClass ) {
					self.show( cssClass );
					chosen();
				},
			} );
		} );
	};

	// The canvas's elements of the selection.
	var headOf = function ( product ) {
		return region.querySelector( '.affibloom-table-cell--head[data-table-product="' + product + '"]' );
	};
	var gutterOf = function ( group ) {
		return region.querySelector( '.affibloom-table-cell--gutter[data-table-group="' + group + '"]' );
	};
	var cardOf = function ( product, field ) {
		return region.querySelector( '.affibloom-table-cell[data-table-product="' + product + '"] .affibloom-table-field[data-table-field="' + field + '"]' );
	};
	var marks = function () {
		if ( ! selected ) {
			return [];
		}
		var selector = {
			product: '[data-table-grid] > .affibloom-table-cell[data-table-product="' + selected.product + '"]',
			field: '.affibloom-table-field[data-table-field="' + selected.field + '"]',
			row: '[data-table-grid] > .affibloom-table-cell[data-table-group="' + selected.group + '"]:not(.affibloom-table-cell--head)',
		}[ selected.kind ];
		return Array.prototype.slice.call( region.querySelectorAll( selector ) );
	};

	var show = function ( name ) {
		Object.keys( parts ).forEach( function ( key ) {
			parts[ key ].hidden = key !== name;
		} );
	};

	// The product part.
	var badgeBox = new IconBox( parts.product.querySelector( '[data-icon-input]' ), function () {
		saveBadge();
	} );
	var productName = parts.product.querySelector( '[data-inspector-product-name]' );
	var productLink = parts.product.querySelector( '[data-inspector-product-link]' );
	var drawBadge = function ( product, badge ) {
		var head = headOf( product );
		var chip = head && head.querySelector( '[data-table-badge-edit]' );
		if ( ! chip ) {
			return;
		}
		head.setAttribute( 'data-badge', badge );
		chip.innerHTML = '';
		chip.classList.toggle( 'affibloom-table-badge--none', '' === badge );
		if ( '' === badge ) {
			chip.appendChild( badgeNone.content.cloneNode( true ) );
			return;
		}
		var text = split( badge );
		if ( '' !== text.icon ) {
			chip.appendChild( picture( text.icon, 16 ) );
		}
		var words = document.createElement( 'span' );
		words.className = 'affibloom-table-icon-text__words';
		words.textContent = text.words;
		chip.appendChild( words );
	};
	var saveBadge = function () {
		if ( ! selected || 'product' !== selected.kind ) {
			return;
		}
		var product = selected.product;
		var head = headOf( product );
		var badge = badgeBox.value();
		if ( ! head || badge === head.getAttribute( 'data-badge' ) ) {
			return;
		}
		var before = head.getAttribute( 'data-badge' );
		drawBadge( product, badge );
		tb.enqueue( {
			value: true,
			send: function ( current ) {
				return wp.apiFetch( { path: tb.path + '/products/' + product, method: 'PATCH', data: { revision: current, badge: badge } } );
			},
			failed: function ( text ) {
				drawBadge( product, before );
				tb.say( text );
			},
		} );
	};
	badgeBox.words.addEventListener( 'change', saveBadge );
	badgeBox.words.addEventListener( 'keydown', function ( event ) {
		if ( 'Enter' === event.key ) {
			event.preventDefault();
			saveBadge();
		}
	} );
	var fillProduct = function () {
		var head = headOf( selected.product );
		productName.textContent = head.getAttribute( 'data-product-name' );
		productLink.href = panel.getAttribute( 'data-product-url' ).replace( /([?&]id=)0\b/, '$1' + selected.product );
		badgeBox.set( head.getAttribute( 'data-badge' ) );
	};

	// The field part, with a button's editor.
	var fieldName = parts.field.querySelector( '[data-inspector-field-name]' );
	var fieldKind = parts.field.querySelector( '[data-inspector-field-kind]' );
	var form = parts.field.querySelector( '[data-inspector-button]' );
	var buttonFor = form.querySelector( '[data-inspector-button-for]' );
	var buttonUrl = form.querySelector( '[name="button_url"]' );
	var primaryHint = form.querySelector( '[data-inspector-primary-hint]' );
	var formError = form.querySelector( '[data-inspector-button-error]' );
	var submit = form.querySelector( '[type="submit"]' );
	var buttonBox = new IconBox( form.querySelector( '[data-icon-input]' ), function () {
		buttonBox.words.focus();
	} );
	var errors = function ( fields ) {
		form.querySelectorAll( '[data-error-for]' ).forEach( function ( line ) {
			var text = fields[ line.getAttribute( 'data-error-for' ) ] || '';
			line.textContent = text;
			line.hidden = '' === text;
		} );
	};
	var fillField = function () {
		var card = cardOf( selected.product, selected.field ) || region.querySelector( '.affibloom-table-field[data-table-field="' + selected.field + '"]' );
		fieldName.textContent = card.getAttribute( 'data-field-name' );
		fieldKind.textContent = card.getAttribute( 'data-field-kind' );
		var chip = card.querySelector( '[data-table-button]' );
		form.hidden = ! chip;
		errors( {} );
		formError.hidden = true;
		if ( ! chip ) {
			return;
		}
		selected.product = card.closest( '[data-table-product]' ).getAttribute( 'data-table-product' );
		var head = headOf( selected.product );
		buttonFor.textContent = format( panel.getAttribute( 'data-button-for' ), [ head ? head.getAttribute( 'data-product-name' ) : '' ] );
		buttonBox.set( chip.getAttribute( 'data-button-text' ) );
		var primary = '1' === chip.getAttribute( 'data-button-primary' );
		buttonUrl.value = chip.getAttribute( 'data-button-url' );
		buttonUrl.readOnly = primary;
		primaryHint.hidden = ! primary;
	};
	form.addEventListener( 'submit', function ( event ) {
		event.preventDefault();
		if ( ! selected || 'field' !== selected.kind || submit.disabled ) {
			return;
		}
		var product = selected.product;
		var field = selected.field;
		var value = JSON.stringify( { button_text: buttonBox.value(), url: buttonUrl.value.trim() } );
		var label = submit.textContent;
		submit.disabled = true;
		submit.textContent = submit.getAttribute( 'data-busy-label' );
		errors( {} );
		formError.hidden = true;
		var done = function () {
			submit.disabled = false;
			submit.textContent = label;
		};
		tb.enqueue( {
			send: function ( current ) {
				return wp.apiFetch( { path: tb.path + '/values/' + product + '/' + field, method: 'PUT', data: { revision: current, value: value } } );
			},
			done: function () {
				done();
				window.affibloomToast( panel.getAttribute( 'data-button-saved' ), false );
			},
			fields: function ( fields ) {
				done();
				errors( fields );
			},
			failed: function ( text ) {
				done();
				formError.textContent = text;
				formError.hidden = false;
			},
		} );
	} );

	// The row part.
	var rowName = parts.row.querySelector( '[data-inspector-row-name]' );
	var rowRemove = parts.row.querySelector( '[data-inspector-row-remove]' );
	var fillRow = function () {
		var gutter = gutterOf( selected.group );
		rowName.textContent = gutter.getAttribute( 'data-line-name' );
		rowRemove.textContent = gutter.querySelector( '[data-table-remove-row]' ).getAttribute( 'title' );
	};

	// The selection.
	var exists = function ( what ) {
		if ( ! what ) {
			return false;
		}
		if ( 'product' === what.kind ) {
			return !! headOf( what.product );
		}
		if ( 'field' === what.kind ) {
			return !! region.querySelector( '.affibloom-table-field[data-table-field="' + what.field + '"]' );
		}
		return !! gutterOf( what.group );
	};
	var select = function ( what ) {
		marks().forEach( function ( element ) {
			element.classList.remove( 'is-selected', 'is-selected-own' );
		} );
		selected = exists( what ) ? what : null;
		clear.hidden = ! selected;
		if ( ! selected ) {
			title.textContent = title.getAttribute( 'data-default' );
			show( 'none' );
			return;
		}
		marks().forEach( function ( element ) {
			element.classList.add( 'is-selected' );
		} );
		if ( 'field' === selected.kind ) {
			var own = cardOf( selected.product, selected.field );
			if ( own ) {
				own.classList.add( 'is-selected-own' );
			}
		}
		show( selected.kind );
		( { product: fillProduct, field: fillField, row: fillRow } )[ selected.kind ]();
		title.textContent = 'product' === selected.kind ? productName.textContent : ( 'field' === selected.kind ? fieldName.textContent : rowName.textContent );
		tb.emit( 'select', selected );
	};
	var same = function ( what ) {
		return selected && what && selected.kind === what.kind && selected.product === what.product && selected.field === what.field && selected.group === what.group;
	};

	// What a click or a focus in the canvas selects.
	var target = function ( element ) {
		var card = element.closest( '.affibloom-table-field' );
		if ( card ) {
			return {
				kind: 'field',
				field: card.getAttribute( 'data-table-field' ),
				product: card.closest( '[data-table-product]' ).getAttribute( 'data-table-product' ),
			};
		}
		var gutter = element.closest( '.affibloom-table-cell--gutter' );
		if ( gutter ) {
			return { kind: 'row', group: gutter.getAttribute( 'data-table-group' ) };
		}
		var head = element.closest( '.affibloom-table-cell--head' );
		if ( head ) {
			return { kind: 'product', product: head.getAttribute( 'data-table-product' ) };
		}
		return null;
	};
	// The parts of the canvas that do their own thing on a click (open a dialog, remove) still select, except Remove.
	var IGNORE = '[data-table-remove], [data-table-remove-field], [data-table-remove-row], [data-table-add-field]';
	region.addEventListener( 'click', function ( event ) {
		if ( event.target.closest( IGNORE ) ) {
			return;
		}
		var what = target( event.target );
		if ( ! what ) {
			if ( event.target.closest( '[data-table-grid]' ) ) {
				select( null );
			}
			return;
		}
		if ( ! same( what ) ) {
			select( what );
		}
		if ( event.target.closest( '[data-table-badge-edit]' ) ) {
			badgeBox.words.focus();
		} else if ( event.target.closest( '[data-table-button]' ) ) {
			buttonBox.words.focus();
		}
	} );
	// The keyboard: tabbing into a field's editor selects its field (a grip does not, so a keyboard drag stays a drag).
	region.addEventListener( 'focusin', function ( event ) {
		if ( event.target.closest( '[data-table-drag], ' + IGNORE ) ) {
			return;
		}
		var what = target( event.target );
		if ( what && 'field' === what.kind && ! same( what ) ) {
			select( what );
		}
	} );
	clear.addEventListener( 'click', function () {
		select( null );
	} );
	panel.querySelectorAll( '[data-inspector-remove-product], [data-inspector-remove-field], [data-inspector-remove-row]' ).forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			if ( ! selected ) {
				return;
			}
			if ( 'product' === selected.kind ) {
				tb.removeProduct( selected.product, button );
			} else if ( 'field' === selected.kind ) {
				var card = region.querySelector( '.affibloom-table-field[data-table-field="' + selected.field + '"]' );
				var cell = card.closest( '[data-table-group]' );
				tb.removeField( cell.getAttribute( 'data-table-group' ), selected.field, card.getAttribute( 'data-field-name' ), button );
			} else {
				tb.removeRow( selected.group, button );
			}
		} );
	} );
	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' !== event.key || ! selected || event.defaultPrevented ) {
			return;
		}
		if ( document.querySelector( '.affibloom-modal:not([hidden]), .affibloom-icon-popover' ) || page.classList.contains( 'is-table-dragging' ) ) {
			return;
		}
		select( null );
	} );

	// A redraw: the same selection on the new canvas, its part filled again (a save may have changed it).
	tb.on( 'drawn', function () {
		var keep = selected;
		selected = null;
		select( keep );
	} );
}() );
