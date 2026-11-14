/**
 * Settings → General: the rules that tie the controls together, as Lasso's page has them (F9; Tasks 9D, 9F, 9G, 9H).
 *
 * - Update Link Settings: changing New Window / Tab, NoFollow / NoIndex, Sponsored or Show Disclosure asks whether to
 *   push the value to every existing product (9D).
 * - Google Analytics: Click Tracking needs a Tracking ID, Pageview and IP Anonymization need Click Tracking; Pageview
 *   asks first; the disabled Click Tracking explains itself (9F).
 * - Cloaked Link Prefix: what is typed becomes its slug form, and the example line follows (9G).
 * - Performance Mode: choosing a preset sets its numbers; only Custom shows the CPU level (9H).
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

	document.addEventListener( 'DOMContentLoaded', function () {
		linkDefaults();
		analytics();
		prefix();
		performance();
	} );
} )();
