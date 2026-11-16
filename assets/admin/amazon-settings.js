/**
 * Settings → Amazon: Lasso's live tracking-ID check, the whitelist as removable tags, and the whitelist disabled
 * while Allow Multiple Tracking IDs is off (docs/features/F17/requirements.md), and the Product Data API card's
 * Validate (C1A).
 *
 * The server applies the same rules, so the form is correct without this script: the whitelist is then a plain
 * field of IDs separated by spaces.
 */
( function () {
	'use strict';

	/**
	 * Whether a value is a tracking ID by Lasso's rule (Lasso_Amazon_Api::TRACKING_ID_REGEX, case-insensitive).
	 *
	 * @param {string} rule  The rule, from the field's data attribute.
	 * @param {string} value The value.
	 * @return {boolean} Whether it is valid.
	 */
	function isTrackingId( rule, value ) {
		return new RegExp( rule, 'i' ).test( value );
	}

	/**
	 * Shows or hides a field's error, and tells assistive technology.
	 *
	 * @param {HTMLElement} field The field.
	 * @param {boolean}     wrong Whether the value is wrong.
	 */
	function mark( field, wrong ) {
		var error = document.getElementById( field.id + '-error' );
		field.classList.toggle( 'is-invalid', wrong );
		if ( wrong ) {
			field.setAttribute( 'aria-invalid', 'true' );
		} else {
			field.removeAttribute( 'aria-invalid' );
		}
		if ( error ) {
			error.hidden = ! wrong;
			// From now on the message describes what is being typed, not a refused save.
			if ( error.dataset.live ) {
				error.textContent = error.dataset.live;
			}
		}
	}

	/**
	 * The tracking ID field: an empty value or a valid ID is fine, anything else shows Lasso's message.
	 *
	 * @param {HTMLInputElement} field The tracking ID field.
	 */
	function trackingId( field ) {
		field.addEventListener( 'input', function () {
			var value = field.value.trim();
			mark( field, '' !== value && ! isTrackingId( field.dataset.trackingRule, value ) );
		} );
	}

	/**
	 * Turns the space-separated whitelist field into tags with a remove button each, an input to add one, and a
	 * button to clear them all, as Lasso's tag list has. The original field stays the value that is sent.
	 *
	 * @param {HTMLInputElement} field The whitelist field.
	 */
	function tagList( field ) {
		var box = document.createElement( 'div' );
		var add = document.createElement( 'input' );
		var clear = document.createElement( 'button' );
		var hint = document.querySelector( '[data-tag-hint]' );
		var ids = field.value.split( /\s+/ ).filter( Boolean );

		var sync = function () {
			field.value = ids.join( ' ' );
			field.form.dispatchEvent( new Event( 'affibloom:change' ) );
		};
		var draw = function () {
			Array.prototype.slice.call( box.querySelectorAll( '.affibloom-tag' ) ).forEach( function ( tag ) {
				tag.remove();
			} );
			ids.forEach( function ( id ) {
				var tag = document.createElement( 'span' );
				var remove = document.createElement( 'button' );
				tag.className = 'affibloom-tag';
				tag.textContent = id;
				remove.type = 'button';
				remove.className = 'affibloom-tag__remove';
				remove.setAttribute( 'aria-label', field.dataset.removeLabel.replace( '%s', id ) );
				remove.textContent = '×';
				remove.disabled = field.disabled;
				remove.addEventListener( 'click', function () {
					ids = ids.filter( function ( other ) {
						return other !== id;
					} );
					sync();
					draw();
					add.focus();
				} );
				tag.appendChild( remove );
				box.insertBefore( tag, add );
			} );
			clear.hidden = 0 === ids.length;
			clear.disabled = field.disabled;
			add.disabled = field.disabled;
			box.classList.toggle( 'is-disabled', field.disabled );
		};
		// Adds every complete ID in the box, however it got there (typed, pasted, several at once), and leaves only
		// the invalid ones in the box, marked.
		var commit = function () {
			var parts = add.value.split( /[\s,]+/ ).filter( Boolean );
			var rejected = [];
			var added = false;
			parts.forEach( function ( id ) {
				if ( ! isTrackingId( field.dataset.trackingRule, id ) ) {
					rejected.push( id );
				} else if ( ids.indexOf( id ) === -1 ) {
					ids.push( id );
					added = true;
				}
			} );
			if ( added ) {
				ids.sort();
				sync();
			}
			add.value = rejected.join( ' ' );
			mark( add, rejected.length > 0 );
			draw();
		};

		box.className = 'affibloom-tags';
		add.type = 'text';
		add.id = field.id + '-add';
		add.className = 'affibloom-tags__input';
		add.placeholder = field.placeholder;
		add.setAttribute( 'aria-describedby', field.id + '-add-error' );
		add.setAttribute( 'aria-label', document.querySelector( 'label[for="' + field.id + '"]' ).textContent.trim() );
		clear.type = 'button';
		clear.className = 'affibloom-tags__clear';
		clear.setAttribute( 'aria-label', field.dataset.clearLabel );
		clear.textContent = '×';

		var error = document.getElementById( field.id + '-error' );
		if ( error ) {
			error.id = field.id + '-add-error';
		}

		box.appendChild( add );
		box.appendChild( clear );
		field.insertAdjacentElement( 'afterend', box );
		field.hidden = true;
		if ( hint ) {
			hint.hidden = true;
		}

		// A space or comma finishes an ID, whether it was typed or pasted. An emptied box has nothing wrong in it any
		// more: its message goes at once, not on leaving the box, where it would move Save Changes under the pointer.
		add.addEventListener( 'input', function () {
			if ( /[\s,]/.test( add.value ) ) {
				commit();
			} else if ( '' === add.value ) {
				mark( add, false );
			}
		} );
		add.addEventListener( 'keydown', function ( event ) {
			if ( 'Enter' === event.key ) {
				event.preventDefault();
				commit();
			} else if ( 'Backspace' === event.key && '' === add.value && ids.length ) {
				ids.pop();
				sync();
				draw();
			}
		} );
		add.addEventListener( 'blur', commit );
		clear.addEventListener( 'click', function () {
			ids = [];
			sync();
			draw();
			add.focus();
		} );
		box.addEventListener( 'click', function ( event ) {
			if ( event.target === box ) {
				add.focus();
			}
		} );

		draw();
		return draw;
	}

	/**
	 * The Product Data API card's Validate (C1 R19-R22): checks the typed credentials with POST /amazon/validate (a blank
	 * Credential ID or Secret means the stored one), shows Lasso's message under the fields and the card's new state.
	 * The button is disabled with a spinner while Amazon is asked.
	 *
	 * @param {HTMLElement} card The card.
	 */
	function apiCard( card ) {
		var button = card.querySelector( '[data-api-validate]' );
		var message = card.querySelector( '[data-api-message]' );
		var chip = card.querySelector( '[data-api-state]' );
		var states = JSON.parse( card.dataset.states );
		var value = function ( id ) {
			return document.getElementById( id ).value;
		};
		var show = function ( text, tone, state ) {
			message.textContent = text;
			message.className = 'affibloom-api-card__message' + ( tone ? ' is-' + tone : '' );
			if ( state && states[ state ] ) {
				chip.className = 'affibloom-state affibloom-state--' + state;
				chip.textContent = states[ state ];
			}
		};

		button.addEventListener( 'click', function () {
			button.disabled = true;
			button.classList.add( 'is-busy' );
			show( card.dataset.validatingText, '' );

			window.wp.apiFetch( {
				path: '/affibloom/v1/amazon/validate',
				method: 'POST',
				data: {
					credential_id: value( 'affibloom-api-credential_id' ),
					secret: value( 'affibloom-api-secret' ),
					partner_tag: value( 'affibloom-api-partner_tag' ),
					version: value( 'affibloom-api-version' ),
					country: value( 'affibloom-setting-default_country' ),
				},
			} )
				.then( function ( answer ) {
					show( answer.data.message, 'success', answer.data.state );
					// Validated again: the earlier refusal no longer applies (C1Q0).
					var refusal = card.querySelector( '[data-api-refusal]' );
					if ( refusal ) {
						refusal.hidden = true;
					}
				} )
				.catch( function ( error ) {
					var known = error && error.code && 0 === error.code.indexOf( 'affibloom_' );
					show( known ? error.message : card.dataset.failedText, 'error', known && error.data ? error.data.state : '' );
				} )
				.finally( function () {
					button.disabled = false;
					button.classList.remove( 'is-busy' );
				} );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var card = document.querySelector( '[data-amazon-api]' );
		if ( card && window.wp && window.wp.apiFetch ) {
			apiCard( card );
			// After Save Changes (settings-save.js) the card shows what is stored: its state, the kept credentials
			// (the typed Credential ID and Secret are not shown again) and any refusal.
			document.addEventListener( 'affibloom:answer', function ( event ) {
				var shown = document.querySelector( '[data-amazon-api]' );
				if ( ! event.detail.api_card || ! shown ) {
					return;
				}
				var holder = document.createElement( 'div' );
				holder.innerHTML = event.detail.api_card;
				var fresh = holder.querySelector( '[data-amazon-api]' );
				if ( fresh ) {
					shown.replaceWith( fresh );
					apiCard( fresh );
				}
			} );
		}

		var tag = document.getElementById( 'affibloom-setting-tracking_id' );
		var list = document.querySelector( '[data-tag-list]' );
		var multiple = document.getElementById( 'affibloom-setting-multiple_tracking_ids' );
		var redraw = null;

		if ( tag ) {
			trackingId( tag );
		}
		if ( list ) {
			redraw = tagList( list );
		}
		// A disabled whitelist is not sent, so the server keeps it as it was, as Lasso's save does.
		if ( list && multiple ) {
			multiple.addEventListener( 'change', function () {
				list.disabled = ! multiple.checked;
				if ( redraw ) {
					redraw();
				}
			} );
		}
	} );
} )();
