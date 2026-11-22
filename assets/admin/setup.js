/**
 * The setup wizard (F38 R47-R56, AC10, AC11; Tasks ON6, ON7). One step shows at a time; the step bar marks the current
 * step (`aria-current="step"`) and lets the editor go back to any step reached; the focus moves to the new step's
 * heading. A theme card picks the theme and opens Customize, whose live box redraws as its fields change (the Display
 * screen's preview route). Save and Continue sends the step's own form to its route (SettingsService's `setup_*` tabs)
 * and moves on; Skip moves on without saving; Go to Dashboard ends the wizard (POST /setup/done). Without this script
 * every step shows, one under the other.
 */
( function () {
	'use strict';

	var root = document.querySelector( '[data-setup]' );
	if ( ! root || ! window.wp || ! window.wp.apiFetch ) {
		return;
	}

	var order = [ 'theme', 'customize', 'amazon', 'analytics', 'done' ];
	var reached = 0;
	var steps = {};
	Array.prototype.forEach.call( root.querySelectorAll( '[data-step]' ), function ( section ) {
		steps[ section.dataset.step ] = section;
	} );
	var bar = root.querySelectorAll( '.affibloom-steps [data-step-go]' );

	/**
	 * Shows a step, marks it in the bar and moves the focus to its heading.
	 *
	 * @param {string}  name  The step.
	 * @param {boolean} focus Whether to move the focus (not on the first draw).
	 */
	function go( name, focus ) {
		var at = order.indexOf( name );
		reached = Math.max( reached, at );
		order.forEach( function ( step ) {
			steps[ step ].hidden = step !== name;
		} );
		Array.prototype.forEach.call( bar, function ( button ) {
			var index = order.indexOf( button.dataset.stepGo );
			if ( index === at ) {
				button.setAttribute( 'aria-current', 'step' );
			} else {
				button.removeAttribute( 'aria-current' );
			}
			button.classList.toggle( 'is-done', index <= reached && index !== at );
			button.disabled = index > reached;
		} );
		if ( 'customize' === name ) {
			draw();
		}
		if ( focus ) {
			var heading = steps[ name ].querySelector( '.affibloom-setup__title' );
			if ( heading ) {
				heading.focus();
			}
		}
	}

	/**
	 * The step after this one.
	 *
	 * @param {string} name The step.
	 * @return {string} The next step.
	 */
	function next( name ) {
		return order[ Math.min( order.indexOf( name ) + 1, order.length - 1 ) ];
	}

	// The live box (R50): the Customize form as it stands, drawn by the Display screen's preview route.
	var customize = root.querySelector( '[data-setup-form="customize"]' );
	var preview = root.querySelector( '[data-setup-preview]' );
	var latest = 0;
	var timer = null;

	function draw() {
		var ask = ++latest;
		preview.classList.add( 'is-loading' );
		window.wp.apiFetch( { path: '/affibloom/v1/display-preview', method: 'POST', body: new window.FormData( customize ) } ).then( function ( answer ) {
			if ( ask !== latest ) {
				return;
			}
			window.affibloomBoxFrame( preview, answer, preview.dataset.previewTitle, function () {
				preview.classList.remove( 'is-loading' );
			} );
		} ).catch( function () {
			if ( ask === latest ) {
				preview.classList.remove( 'is-loading' );
				window.affibloomToast( preview.dataset.previewError, true );
			}
		} );
	}

	customize.addEventListener( 'input', function () {
		window.clearTimeout( timer );
		timer = window.setTimeout( draw, 250 );
	} );
	Array.prototype.forEach.call( customize.querySelectorAll( '[data-affibloom-swatch]' ), window.affibloomSwatch );

	// Theme (R49): a card picks the theme and opens Customize with the box drawn in it.
	root.addEventListener( 'click', function ( event ) {
		var card = event.target.closest( '[data-theme]' );
		if ( card ) {
			Array.prototype.forEach.call( root.querySelectorAll( '[data-theme]' ), function ( other ) {
				other.setAttribute( 'aria-pressed', other === card ? 'true' : 'false' );
			} );
			customize.querySelector( '[data-setup-theme]' ).value = card.dataset.theme;
			go( 'customize', true );
			return;
		}
		var to = event.target.closest( '[data-step-go]' );
		if ( to && ! to.disabled ) {
			go( to.dataset.stepGo, true );
			return;
		}
		var skip = event.target.closest( '[data-setup-skip]' );
		if ( skip ) {
			go( next( skip.closest( '[data-step]' ).dataset.step ), true );
			return;
		}
		var done = event.target.closest( '[data-setup-done]' );
		if ( done ) {
			event.preventDefault();
			// The wizard ends whatever the answer: the Dashboard is where the editor goes next anyway.
			window.wp.apiFetch( { path: '/affibloom/v1/setup/done', method: 'POST', parse: false } ).finally( function () {
				window.location.assign( done.href );
			} );
		}
	} );

	// Save and Continue: the step's own fields to its route, then the next step.
	Array.prototype.forEach.call( root.querySelectorAll( '[data-setup-form]' ), function ( form ) {
		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			var button = form.querySelector( '[type="submit"]' );
			button.disabled = true;
			button.classList.add( 'is-busy' );
			window.wp.apiFetch( { path: form.dataset.restSave, method: 'POST', body: window.affibloomFormFields( form ) } ).then( function ( answer ) {
				var data = answer && answer.data;
				if ( ! data || 'string' !== typeof data.message ) {
					throw new Error( 'not saved' );
				}
				// The Amazon step's Product Data API card is drawn again from the answer (amazon-settings.js).
				form.dispatchEvent( new CustomEvent( 'affibloom:answer', { bubbles: true, detail: data } ) );
				if ( ( data.refused && data.refused.length ) || data.failed ) {
					window.affibloomToast( data.message, true );
					return;
				}
				go( next( form.closest( '[data-step]' ).dataset.step ), true );
			} ).catch( function () {
				window.affibloomToast( root.dataset.failed, true );
			} ).finally( function () {
				button.disabled = false;
				button.classList.remove( 'is-busy' );
			} );
		} );
	} );

	/**
	 * Each theme card's own box, drawn small: one preview at a time, so a slow server is not asked eight times at once.
	 *
	 * @param {Array<HTMLElement>} cards The cards still to draw.
	 */
	function thumbnails( cards ) {
		var card = cards.shift();
		if ( ! card ) {
			return;
		}
		var body = new window.FormData();
		body.append( 'type', 'single' );
		body.append( 'display[single][theme]', card.dataset.theme );
		window.wp.apiFetch( { path: '/affibloom/v1/display-preview', method: 'POST', body: body } ).then( function ( answer ) {
			window.affibloomBoxFrame( card.querySelector( '[data-theme-shot]' ), answer, card.dataset.theme, function () {
				thumbnails( cards );
			} );
		} ).catch( function () {
			thumbnails( cards );
		} );
	}

	root.classList.add( 'is-stepped' );
	go( 'theme', false );
	thumbnails( Array.prototype.slice.call( root.querySelectorAll( '[data-theme]' ) ) );
}() );
