/* global wp */
/**
 * The table builder's settings and page actions (F25 R13-R16, R41, R54; Tasks T3, T11, T15, 18S13; deviation 151).
 *
 * - The heading follows the name while typing; the name saves when it changes and is not empty; Style, Theme and the
 *   two switches save at once (changes made while a write is out go together in the next). A table with no id yet is
 *   created by naming it, then the page moves to its builder; until then Theme and the switches are disabled.
 * - The toolbar sits outside the redrawn canvas, so its Add Row / Add Column label follows the style here.
 * - Preview Table: the reader's table (GET /tables/{id}/preview) in a frame in place of the canvas; Edit Table returns;
 *   every redraw draws the preview again.
 * - Copy Shortcode copies `[affibloom type="table" id="<id>"]`; Delete This Table asks, or says "Hold Up" when the table
 *   is in use (the server counts again).
 */
( function () {
	'use strict';

	var tb = window.affibloomTableBuilder;
	if ( ! tb ) {
		return;
	}
	var page = tb.page;
	var form = page.querySelector( '[data-table-settings]' );
	var name = form.querySelector( '[name="title"]' );
	var style = form.querySelector( '[name="style"]' );
	var theme = form.querySelector( '[name="theme"]' );
	var headersRow = form.querySelector( '[data-table-headers-row]' );
	var heading = document.getElementById( 'affibloom-table-heading' );
	var toolbar = page.querySelector( '[data-table-toolbar]' );
	var settings = null;
	var saved = name.value;

	var lineLabel = function () {
		var line = toolbar && toolbar.querySelector( '[data-table-action="add-line"] [data-line-label]' );
		if ( line ) {
			line.textContent = line.getAttribute( 'row' === style.value ? 'data-column' : 'data-row' );
		}
		page.setAttribute( 'data-table-style', style.value );
	};

	// One PATCH for the changes gathered until it is sent (a new table: POST, then its builder).
	var save = function ( field, value ) {
		if ( null !== settings ) {
			settings[ field ] = value;
			return;
		}
		settings = {};
		settings[ field ] = value;
		tb.enqueue( {
			send: function ( current ) {
				var changes = settings;
				settings = null;
				if ( ! tb.id ) {
					return wp.apiFetch( { path: '/affibloom/v1/tables', method: 'POST', data: { title: changes.title || name.value, style: style.value } } );
				}
				return wp.apiFetch( { path: tb.path, method: 'PATCH', data: Object.assign( { revision: current }, changes ) } );
			},
			done: function ( data ) {
				if ( ! tb.id ) {
					window.location.assign( data.redirect_to );
					return;
				}
				saved = data.title;
			},
		} );
	};

	name.addEventListener( 'input', function () {
		heading.textContent = '' !== name.value.trim() ? name.value : page.getAttribute( 'data-untitled' );
	} );
	name.addEventListener( 'change', function () {
		var value = name.value.trim();
		if ( '' === value || value === saved ) {
			return;
		}
		save( 'title', value );
	} );
	// Enter in the name saves it rather than submitting the form.
	form.addEventListener( 'submit', function ( event ) {
		event.preventDefault();
		name.blur();
	} );
	style.addEventListener( 'change', function () {
		headersRow.hidden = 'row' !== style.value;
		lineLabel();
		if ( tb.id ) {
			save( 'style', style.value );
		}
	} );
	theme.addEventListener( 'change', function () {
		save( 'theme', theme.value );
	} );
	[ 'show_headers', 'show_field_name' ].forEach( function ( toggle ) {
		form.querySelector( '[name="' + toggle + '"]' ).addEventListener( 'change', function ( event ) {
			save( toggle, event.target.checked );
		} );
	} );
	lineLabel();

	if ( ! tb.id ) {
		// Saved with the table's creation: the name and the style; the rest once it exists.
		[ theme ].concat( Array.prototype.slice.call( form.querySelectorAll( '[name="show_headers"], [name="show_field_name"]' ) ) ).forEach( function ( control ) {
			control.disabled = true;
			control.setAttribute( 'aria-describedby', 'affibloom-table-name-first' );
		} );
		return;
	}

	// Preview Table (T15).
	var previewing = false;
	var previewBox = page.querySelector( '[data-table-preview]' );
	var previewButton = toolbar.querySelector( '[data-table-action="preview"]' );
	var previewLabel = function () {
		previewButton.setAttribute( 'aria-pressed', previewing ? 'true' : 'false' );
		previewButton.querySelector( '[data-preview-text]' ).textContent = previewing ? previewButton.dataset.editLabel : previewButton.dataset.previewLabel;
	};
	var preview = function () {
		page.classList.add( 'is-previewing' );
		previewBox.hidden = false;
		previewLabel();
		wp.apiFetch( { path: tb.path + '/preview' } ).then( function ( answer ) {
			if ( previewing ) {
				window.affibloomBoxFrame( previewBox, answer.data, previewBox.dataset.frameTitle, function () {} );
			}
		} ).catch( function ( failure ) {
			tb.say( tb.words( failure ) );
		} );
	};
	previewButton.addEventListener( 'click', function () {
		previewing = ! previewing;
		if ( previewing ) {
			tb.flush();
			preview();
			return;
		}
		page.classList.remove( 'is-previewing' );
		previewBox.hidden = true;
		previewLabel();
	} );
	tb.on( 'drawn', function () {
		if ( previewing ) {
			preview();
		}
	} );

	// Copy Shortcode (18S13).
	var copyButton = document.querySelector( '[data-table-copy]' );
	if ( copyButton && '' !== copyButton.dataset.tableCopy ) {
		var copyLabel = copyButton.querySelector( '[data-copy-label]' );
		var copyName = copyLabel.textContent;
		var copyTimer = 0;
		copyButton.addEventListener( 'click', function () {
			window.affibloomCopy( copyButton.dataset.tableCopy, function () {
				window.clearTimeout( copyTimer );
				copyLabel.textContent = copyButton.dataset.copied;
				copyTimer = window.setTimeout( function () {
					copyLabel.textContent = copyName;
				}, 1500 );
			}, function () {
				window.affibloomToast( copyButton.dataset.copyFailed, true );
			} );
		} );
	}

	// Delete This Table (T11).
	var deleter = document.getElementById( 'affibloom-table-delete' );
	var deleteConfirm = deleter.querySelector( '[data-table-delete-confirm]' );
	var holdUp = document.getElementById( 'affibloom-table-holdup' );
	var deleteButton = page.querySelector( '[data-table-delete]' );
	deleteButton.addEventListener( 'click', function () {
		wp.apiFetch( { path: tb.path + '/locations' } ).then( function ( answer ) {
			window.affibloomModal.show( answer.data.total > 0 ? holdUp : deleter, deleteButton );
		} ).catch( function ( failure ) {
			tb.say( tb.words( failure ) );
		} );
	} );
	deleteConfirm.addEventListener( 'click', function () {
		var label = deleteConfirm.textContent;
		deleteConfirm.disabled = true;
		deleteConfirm.textContent = deleteConfirm.getAttribute( 'data-busy-label' );
		wp.apiFetch( { path: tb.path, method: 'DELETE' } ).then( function ( answer ) {
			window.location.assign( answer.data.redirect_to );
		} ).catch( function ( failure ) {
			deleteConfirm.disabled = false;
			deleteConfirm.textContent = label;
			window.affibloomModal.hide();
			if ( failure && 'affibloom_table_in_use' === failure.code ) {
				window.affibloomModal.show( holdUp, deleteButton );
				return;
			}
			tb.say( tb.words( failure ) );
		} );
	} );
}() );
