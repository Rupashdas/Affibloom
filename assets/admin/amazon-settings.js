/**
 * Settings → Amazon: Lasso's live tracking-ID check, the whitelist as removable tags, and the whitelist disabled
 * while Allow Multiple Tracking IDs is off (docs/features/F17/requirements.md).
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

		// A space or comma finishes an ID, whether it was typed or pasted.
		add.addEventListener( 'input', function () {
			if ( /[\s,]/.test( add.value ) ) {
				commit();
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

	document.addEventListener( 'DOMContentLoaded', function () {
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
