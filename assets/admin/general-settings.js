/**
 * Settings → General: the rules that tie the controls together, as Lasso's page has them (F9; Tasks 9D, 9F, 9G, 9H).
 *
 * - Update Link Settings: changing New Window / Tab, NoFollow / NoIndex, Sponsored or Show Disclosure asks whether to
 *   push the value to every existing product (9D).
 * - Google Analytics: Click Tracking needs a Tracking ID, Pageview and IP Anonymization need Click Tracking; Pageview
 *   asks first; the disabled Click Tracking explains itself (9F).
 * - Cloaked Link Prefix: what is typed becomes its slug form, and the example line follows (9G).
 * - Performance Mode: choosing a preset sets its numbers; only Custom shows the CPU level (9H).
 * - Rebuild Database: "Ready to rebuild?", then the link index is rebuilt in the background (F21 R52, Task 35G).
 * - Link Index: the card's figures, redrawn every 5 seconds (F21 R57, Task 9J).
 *
 * The server applies the same rules where it can, so the form still saves correctly without this script.
 */
( function () {
	'use strict';

	/**
	 * Tells the form something changed without an input event, so Save Changes (and the unsaved-changes guard) know.
	 *
	 * @param {HTMLElement} element Any element in the form.
	 */
	function changed( element ) {
		var form = element.closest( 'form' );
		if ( form ) {
			form.dispatchEvent( new Event( 'affibloom:change' ) );
		}
	}

	/**
	 * Update Link Settings (F9 R47-R48): the dialog opens when a link default that products carry is switched; "Override
	 * All Links" sets the value on every product at once, apart from Save, and says so in a toast.
	 */
	function linkDefaults() {
		var modal = document.querySelector( '[data-link-settings-modal]' );
		var inputs = document.querySelectorAll( 'input[data-link-default]' );
		if ( ! modal || ! inputs.length ) {
			return;
		}
		var text = modal.querySelector( '#affibloom-link-settings-text' );
		var override = modal.querySelector( '[data-link-settings-override]' );
		var busy = false;

		Array.prototype.forEach.call( inputs, function ( input ) {
			input.addEventListener( 'change', function () {
				modal.dataset.key = input.dataset.linkDefault;
				modal.dataset.value = input.checked ? '1' : '0';
				text.textContent = ( input.checked ? text.dataset.enable : text.dataset.disable ) + ' ' + input.dataset.linkLabel;
				window.affibloomModal.show( modal, input );
			} );
		} );

		override.addEventListener( 'click', function () {
			if ( busy ) {
				return;
			}
			busy = true;
			override.disabled = true;
			wp.apiFetch( {
				path: '/affibloom/v1/settings/link-defaults/apply',
				method: 'POST',
				data: { key: modal.dataset.key, value: '1' === modal.dataset.value },
			} )
				.then( function () {
					window.affibloomModal.hide();
					window.affibloomToast( modal.dataset.doneText, false );
				} )
				.catch( function ( error ) {
					window.affibloomModal.hide();
					window.affibloomToast( error && error.message ? error.message : modal.dataset.failedText, true );
				} )
				.then( function () {
					busy = false;
					override.disabled = false;
				} );
		} );
	}

	/**
	 * Rebuild Database (F21 R52, D17): the button asks "Ready to rebuild?"; Confirm starts the background rebuild and the
	 * toast says "Rebuild started." or why not ("A rebuild is already running."). The Link Index card's poll shows the
	 * progress (Task 9J).
	 */
	function rebuild() {
		var modal = document.querySelector( '[data-rebuild-modal]' );
		if ( ! modal ) {
			return;
		}
		var confirm = modal.querySelector( '[data-rebuild-confirm]' );
		var busy = false;

		document.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( '[data-link-index-rebuild]' );
			if ( button ) {
				event.preventDefault();
				window.affibloomModal.show( modal, button );
			}
		} );

		confirm.addEventListener( 'click', function () {
			if ( busy ) {
				return;
			}
			busy = true;
			confirm.classList.add( 'is-busy' );
			wp.apiFetch( { path: '/affibloom/v1/link-index/rebuild', method: 'POST' } )
				.then( function ( response ) {
					window.affibloomModal.hide();
					window.affibloomToast( response.message, false );
					document.dispatchEvent( new CustomEvent( 'affibloom:link-index', { detail: response.status } ) );
				} )
				.catch( function ( error ) {
					window.affibloomModal.hide();
					window.affibloomToast( error && error.message ? error.message : modal.dataset.failedText, true );
				} )
				.then( function () {
					busy = false;
					confirm.classList.remove( 'is-busy' );
				} );
		} );
	}

	/**
	 * The Link Index card (F21 R51, R57): Build Progress, ETA and the background sentence while a rebuild runs, else
	 * Last Updated and Links Indexed (nothing while the index is empty). Polled every 5 seconds while the tab is shown,
	 * and redrawn at once when a rebuild starts.
	 */
	function linkIndex() {
		var card = document.querySelector( '[data-link-index]' );
		if ( ! card ) {
			return;
		}
		var part = function ( name ) {
			return card.querySelector( '[data-link-index-' + name + ']' );
		};

		function draw( figures ) {
			var running = 'running' === figures.status;
			part( 'running' ).hidden = ! running;
			part( 'sentence' ).hidden = ! running;
			part( 'done' ).hidden = running || figures.links_indexed < 1;
			part( 'progress' ).textContent = figures.progress_text;
			part( 'eta' ).textContent = figures.eta_text;
			part( 'updated' ).textContent = figures.last_updated_text;
			part( 'links' ).textContent = figures.links_text;
		}

		document.addEventListener( 'affibloom:link-index', function ( event ) {
			if ( event.detail ) {
				draw( event.detail );
			}
		} );
		window.setInterval( function () {
			if ( document.hidden ) {
				return;
			}
			wp.apiFetch( { path: '/affibloom/v1/link-index' } ).then( draw ).catch( function () {} );
		}, 5000 );
	}

	/**
	 * Google Analytics (F9 R12-R14).
	 */
	function analytics() {
		var card = document.querySelector( '[data-ga-card]' );
		if ( ! card ) {
			return;
		}
		var id = card.querySelector( '[data-ga-id]' );
		var click = card.querySelector( '[data-ga="click"]' );
		var pageview = card.querySelector( '[data-ga="pageview"]' );
		var anonymize = card.querySelector( '[data-ga="anonymize"]' );
		var warning = card.querySelector( '[data-ga-warning]' );
		var modal = document.querySelector( '[data-ga-pageview-modal]' );
		var confirmed = false;

		// Click Tracking waits for an ID; Pageview and IP Anonymization wait for Click Tracking. A switch that cannot be on
		// is switched off, as the form sends nothing for it.
		var sync = function () {
			var hasId = '' !== id.value.trim();
			click.disabled = ! hasId;
			if ( ! hasId ) {
				click.checked = false;
			}
			var clicking = hasId && click.checked;
			[ pageview, anonymize ].forEach( function ( item ) {
				item.disabled = ! clicking;
				if ( ! clicking ) {
					item.checked = false;
				}
			} );
		};

		id.addEventListener( 'input', function () {
			sync();
			if ( '' !== id.value.trim() ) {
				warning.hidden = true;
				id.classList.remove( 'is-invalid' );
			}
			changed( id );
		} );
		click.addEventListener( 'change', function () {
			sync();
			changed( click );
		} );

		// A disabled switch gets no click of its own, but the row around it does: the warning shows there, once.
		click.closest( '.affibloom-toggle-row' ).addEventListener( 'click', function () {
			if ( click.disabled && warning.hidden ) {
				warning.hidden = false;
				id.classList.add( 'is-invalid' );
				id.focus();
			}
		} );

		if ( modal ) {
			pageview.addEventListener( 'change', function () {
				if ( pageview.checked ) {
					confirmed = false;
					window.affibloomModal.show( modal, pageview );
				}
			} );
			modal.querySelector( '[data-ga-pageview-enable]' ).addEventListener( 'click', function () {
				confirmed = true;
				window.affibloomModal.hide();
			} );
			// Cancel, × and Escape all end here: anything but Enable turns Pageview back off.
			modal.addEventListener( 'affibloom:close', function () {
				if ( ! confirmed ) {
					pageview.checked = false;
					changed( pageview );
				}
				confirmed = false;
			} );
		}

		sync();
	}

	/**
	 * Cloaked Link Prefix (F9 R17): Lasso's keyup rule, then the example line.
	 */
	function prefix() {
		var field = document.querySelector( '[data-slug-field]' );
		var slot = document.querySelector( '[data-prefix-slot]' );
		if ( ! field ) {
			return;
		}
		field.addEventListener( 'input', function () {
			field.value = field.value.trim().toLowerCase().replace( /[\W_]+/g, '-' );
			if ( slot ) {
				var shown = field.value.replace( /^-+|-+$/g, '' );
				slot.textContent = '' === shown ? '' : shown + '/';
			}
		} );
	}

	/**
	 * Performance Mode (F9 R22-R23): a preset sets the CPU level and, where the support view is on the page, the cron
	 * interval and the process limit; Custom keeps what is there and shows the CPU level. Loading never changes a value.
	 */
	function performance() {
		var select = document.querySelector( '[data-performance-mode]' );
		var cpuField = document.querySelector( '[data-cpu-field]' );
		if ( ! select || ! cpuField ) {
			return;
		}
		var set = function ( name, value ) {
			var input = document.getElementById( 'affibloom-setting-' + name );
			if ( input ) {
				input.value = value;
			}
		};

		select.addEventListener( 'change', function () {
			var option = select.options[ select.selectedIndex ];
			var custom = undefined === option.dataset.cpu;
			cpuField.hidden = ! custom;
			if ( ! custom ) {
				set( 'cpu_threshold', option.dataset.cpu );
				set( 'cron_interval', option.dataset.interval );
				set( 'manual_process_limit', option.dataset.limit );
			}
			changed( select );
		} );
	}

	/**
	 * "Disable Help Tooltips" (F9): the server adds the body class on the next load (HelpTips); a save that stays on the
	 * page (settings-save.js) sets it at once.
	 */
	function helpTips() {
		var toggle = document.getElementById( 'affibloom-setting-disable_help_tips' );
		if ( ! toggle ) {
			return;
		}
		document.addEventListener( 'affibloom:saved', function () {
			document.body.classList.toggle( 'affibloom-no-tips', toggle.checked );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		linkDefaults();
		analytics();
		prefix();
		performance();
		helpTips();
		rebuild();
		linkIndex();
	} );
} )();
