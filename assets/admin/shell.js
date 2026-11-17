/**
 * The admin shell's Automations (F38 R40-R43, D13; Task ON3): the header's button spins with a count while background
 * jobs run, and its menu lists them as "<name> (<done>/<total>)", or Lao Tzu when none runs. GET /affibloom/v1/jobs is
 * asked at load, then every 15 seconds for 5 minutes; an answer another tab got in the last 15 seconds is reused
 * (Lasso's localStorage pair). Each answer is passed on as `affibloom:jobs` for the notices that follow a job (ON4).
 */
( function () {
	'use strict';

	var button = document.querySelector( '[data-jobs]' );
	if ( ! button ) {
		return;
	}
	var panel = document.getElementById( button.getAttribute( 'aria-controls' ) );
	var list = panel.querySelector( '[data-jobs-list]' );
	var idle = panel.querySelector( '[data-jobs-idle]' );
	var count = button.querySelector( '[data-jobs-count]' );
	var KEY = 'affibloom_jobs';
	var AT = 'affibloom_jobs_time';
	var EVERY = 15000;
	var FOR = 5 * 60 * 1000;
	var started = Date.now();

	function draw( data ) {
		var jobs = data && Array.isArray( data.jobs ) ? data.jobs : [];
		button.classList.toggle( 'is-running', jobs.length > 0 );
		count.hidden = jobs.length < 1;
		count.textContent = String( jobs.length );
		list.textContent = '';
		jobs.forEach( function ( job ) {
			var item = document.createElement( 'li' );
			item.textContent = job.line;
			list.appendChild( item );
		} );
		idle.hidden = jobs.length > 0;
		document.dispatchEvent( new CustomEvent( 'affibloom:jobs', { detail: data } ) );
	}

	/* Storage can be refused (a private window, blocked site data): the poll then simply asks the server. */
	function recent() {
		try {
			var at = Number( window.localStorage.getItem( AT ) );
			if ( at && Date.now() - at < EVERY ) {
				return JSON.parse( window.localStorage.getItem( KEY ) );
			}
		} catch ( error ) {
			return null;
		}
		return null;
	}

	function keep( data ) {
		try {
			window.localStorage.setItem( KEY, JSON.stringify( data ) );
			window.localStorage.setItem( AT, String( Date.now() ) );
		} catch ( error ) {
			// Nothing to keep; the next poll asks again.
		}
	}

	function poll() {
		var reused = recent();
		if ( reused ) {
			draw( reused );
		} else {
			wp.apiFetch( { path: '/affibloom/v1/jobs' } )
				.then( function ( data ) {
					keep( data );
					draw( data );
				} )
				.catch( function () {} );
		}
		if ( Date.now() - started < FOR ) {
			window.setTimeout( poll, EVERY );
		}
	}

	poll();
} )();
