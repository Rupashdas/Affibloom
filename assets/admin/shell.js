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

	/*
	 * Where WP-Cron cannot spawn, the open Affibloom page runs the due Affibloom jobs itself (Task 32D; F11 R34, D22;
	 * Lasso's lasso_check_crons): POST /jobs/run-due at load and every 15 seconds for 5 minutes, by one tab at a time
	 * (the last call's time is shared in storage); the poll stops once the server says "Cron is working well.".
	 */
	var CRON_AT = 'affibloom_cron_time';

	function cronCheck() {
		var mine = true;
		try {
			var at = Number( window.localStorage.getItem( CRON_AT ) );
			mine = ! at || Date.now() - at >= EVERY;
			if ( mine ) {
				window.localStorage.setItem( CRON_AT, String( Date.now() ) );
			}
		} catch ( error ) {
			mine = true;
		}
		var next = function ( working ) {
			if ( ! working && Date.now() - started < FOR ) {
				window.setTimeout( cronCheck, EVERY );
			}
		};
		if ( ! mine ) {
			next( false );
			return;
		}
		wp.apiFetch( { path: '/affibloom/v1/jobs/run-due', method: 'POST' } )
			.then( function ( data ) {
				next( !! ( data && data.status ) );
			} )
			.catch( function () {
				next( false );
			} );
	}

	poll();
	cronCheck();
} )();
