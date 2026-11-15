/**
 * The bulk Amazon sync banner (C1I; Lasso's amazon-bulk-sync-banner.js, R43): Sync Amazon data starts a run
 * ("Starting…"), the run's status is read every 3 seconds ("Syncing… N%"), and when it ends the button is itself again
 * and the summary says how it went, with the first five failures; the banner goes when nothing is pending any more.
 * × hides it for this person until the pending set changes. Its words are the banner's data attributes.
 */
( function () {
	'use strict';

	var banner = document.querySelector( '[data-bulk-sync]' );
	if ( ! banner || ! window.wp || ! window.wp.apiFetch ) {
		return;
	}

	var start = banner.querySelector( '[data-bulk-sync-start]' );
	var summary = banner.querySelector( '[data-bulk-sync-summary]' );
	var words = banner.dataset;
	var path = '/affibloom/v1/amazon/bulk-sync';
	var timer = null;

	/** The summary of a finished run, line by line, each failure as "#id: message" (Lasso's renderSummary). */
	function showSummary( status ) {
		if ( ! status.completed ) {
			return;
		}
		var lines = [
			words.complete,
			words.succeeded + ' ' + status.counts.succeeded,
			words.skipped + ' ' + status.counts.skipped,
			words.failed + ' ' + status.counts.failed,
		];
		if ( status.failures.length ) {
			lines.push( words.failures + ' ' + status.failures.slice( 0, 5 ).map( function ( failure ) {
				return '#' + failure.id + ': ' + failure.message;
			} ).join( ' | ' ) );
		}
		summary.replaceChildren();
		lines.forEach( function ( line, index ) {
			if ( index > 0 ) {
				summary.appendChild( document.createElement( 'br' ) );
			}
			summary.appendChild( document.createTextNode( line ) );
		} );
		summary.hidden = false;
	}

	/** The banner after a status: running, or done with its summary (and gone when nothing is pending). */
	function show( status ) {
		if ( status.running ) {
			start.disabled = true;
			start.textContent = words.syncing + ' ' + status.percent + '%';
			return;
		}
		stop();
		start.disabled = status.pending <= 0;
		start.textContent = words.sync;
		showSummary( status );
		if ( status.pending <= 0 ) {
			banner.classList.add( 'is-leaving' );
			window.setTimeout( function () {
				banner.remove();
			}, 400 );
		}
	}

	function poll() {
		window.wp.apiFetch( { path: path } ).then( function ( answer ) {
			show( answer.data );
		} ).catch( function () {
			// The next poll tries again, as Lasso's.
		} );
	}

	function watch() {
		if ( ! timer ) {
			timer = window.setInterval( poll, 3000 );
		}
		poll();
	}

	function stop() {
		window.clearInterval( timer );
		timer = null;
	}

	start.addEventListener( 'click', function () {
		start.disabled = true;
		start.textContent = words.starting;
		window.wp.apiFetch( { path: path, method: 'POST' } ).then( function ( answer ) {
			show( answer.data );
			watch();
		} ).catch( function ( error ) {
			start.disabled = false;
			start.textContent = words.sync;
			if ( window.affibloomNotice && error && error.message ) {
				window.affibloomNotice( 'error', error.message );
			}
		} );
	} );

	banner.querySelector( '[data-bulk-sync-dismiss]' ).addEventListener( 'click', function () {
		window.wp.apiFetch( { path: path + '/dismiss', method: 'POST', parse: false } ).then( function () {
			stop();
			banner.remove();
		} );
	} );

	// A run that was going when the page loaded is followed at once.
	if ( '1' === words.running ) {
		watch();
	}
}() );
