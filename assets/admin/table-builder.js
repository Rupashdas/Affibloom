/* global wp */
/**
 * A table's builder, its core (F25 D4, D5; Tasks T3-T15; deviation 151). The modules beside it
 * (table-builder-settings.js, -cells.js, -drag.js, -inspector.js) share `window.affibloomTableBuilder`:
 *
 * - enqueue( job ): one write at a time, each carrying the table's revision (D4). A job is { send( revision ) → an
 *   apiFetch promise, done( data ), failed( text ), fields( { field: text } ), value: true for a value or a badge
 *   (no redraw) }. Before any other write, every value still waiting to be sent is sent (pending()), so the builder
 *   the server draws back has it. A failed write fails alone; a stale revision stops every write and offers Reload.
 * - draw( html ): the canvas drawn again from the server's answer (D5), keeping the grid's scroll, the focus and the
 *   selection; 'drawn' is emitted after it.
 * - pending( flush ): a module's way to say "send what you hold now" (typed values waiting for their 500 ms).
 * - on( name, fn ) / emit( name, data ): 'drawn', 'saved', 'select'.
 * - say( text, reload ), words( failure ), revision().
 */
( function () {
	'use strict';

	var page = document.querySelector( '[data-table-builder]' );
	if ( ! page ) {
		return;
	}
	var region = page.querySelector( '[data-table-builder-region]' );
	var status = page.querySelector( '[data-table-status]' );
	var error = page.querySelector( '[data-table-error]' );
	var id = parseInt( page.getAttribute( 'data-table-id' ), 10 ) || 0;
	var jobs = [];
	var busy = false;
	var stopped = false;
	var flushers = [];
	var handlers = {};

	var tb = {
		page: page,
		region: region,
		id: id,
		path: '/affibloom/v1/tables/' + id,
	};

	tb.on = function ( name, fn ) {
		( handlers[ name ] = handlers[ name ] || [] ).push( fn );
	};
	tb.emit = function ( name, data ) {
		( handlers[ name ] || [] ).forEach( function ( fn ) {
			fn( data );
		} );
	};

	tb.say = function ( text, reload ) {
		error.textContent = text;
		if ( reload ) {
			var again = document.createElement( 'button' );
			again.type = 'button';
			again.className = 'affibloom-link-button';
			again.textContent = page.getAttribute( 'data-reload' );
			again.addEventListener( 'click', function () {
				window.location.reload();
			} );
			error.append( ' ', again );
		}
		error.hidden = '' === text;
	};
	tb.words = function ( failure ) {
		var fields = failure && failure.data && failure.data.fields;
		var first = fields ? fields[ Object.keys( fields )[ 0 ] ] : '';
		return first || ( failure && failure.message ) || page.getAttribute( 'data-failed' );
	};
	tb.revision = function () {
		return parseInt( page.getAttribute( 'data-revision' ), 10 ) || 0;
	};

	tb.pending = function ( flush ) {
		flushers.push( flush );
	};
	tb.flush = function () {
		flushers.forEach( function ( flush ) {
			flush();
		} );
	};

	// The element to give the focus back to after a redraw: its id, else the field it edits in its product's cell.
	var focusKey = function ( element ) {
		if ( element.id ) {
			return '#' + window.CSS.escape( element.id );
		}
		var key = element.getAttribute( 'data-focus-key' );
		return key ? '[data-focus-key="' + window.CSS.escape( key ) + '"]' : null;
	};

	tb.draw = function ( html ) {
		var scroll = region.querySelector( '[data-table-scroll]' );
		var left = scroll ? scroll.scrollLeft : 0;
		var active = document.activeElement;
		var key = active && region.contains( active ) ? focusKey( active ) : null;
		region.innerHTML = html;
		scroll = region.querySelector( '[data-table-scroll]' );
		if ( scroll ) {
			scroll.scrollLeft = left;
		}
		var back = key ? region.querySelector( key ) : null;
		if ( back ) {
			back.focus( { preventScroll: true } );
		}
		tb.emit( 'drawn' );
	};

	var show = function ( state ) {
		status.textContent = state ? page.getAttribute( 'data-' + state ) : '';
		status.classList.toggle( 'is-failed', 'unsaved' === state );
	};

	var next = function () {
		if ( busy || stopped || 0 === jobs.length ) {
			return;
		}
		var job = jobs.shift();
		busy = true;
		show( 'saving' );
		job.send( tb.revision() )
			.then( function ( answer ) {
				var data = answer.data;
				if ( undefined !== data.revision ) {
					page.setAttribute( 'data-revision', String( data.revision ) );
				}
				if ( undefined !== data.html ) {
					tb.draw( data.html );
				}
				show( jobs.length ? 'saving' : 'saved' );
				if ( job.done ) {
					job.done( data );
				}
				tb.emit( 'saved', data );
			} )
			.catch( function ( failure ) {
				show( 'unsaved' );
				// D4: another window saved first. Nothing more can be saved over it; the page must be reloaded.
				if ( failure && 'affibloom_table_changed' === failure.code ) {
					stopped = true;
					jobs = [];
					tb.say( tb.words( failure ), true );
					if ( job.failed ) {
						job.failed( tb.words( failure ) );
					}
					return;
				}
				var fields = failure && failure.data && failure.data.fields;
				if ( fields && job.fields ) {
					job.fields( fields );
					return;
				}
				( job.failed || tb.say )( tb.words( failure ) );
			} )
			.then( function () {
				busy = false;
				next();
			} );
	};

	tb.enqueue = function ( job ) {
		if ( stopped ) {
			if ( job.failed ) {
				job.failed( error.textContent );
			}
			return;
		}
		if ( ! job.value ) {
			tb.flush();
		}
		tb.say( '' );
		jobs.push( job );
		next();
	};

	// A write still out when the page is left would be lost: the browser asks first.
	window.addEventListener( 'beforeunload', function ( event ) {
		tb.flush();
		if ( busy || jobs.length ) {
			event.preventDefault();
			event.returnValue = '';
		}
	} );

	window.affibloomTableBuilder = tb;
}() );
